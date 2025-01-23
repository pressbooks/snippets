#!/bin/bash

usage() {
  echo "Usage: $0 [<shared-url>]"
  echo "If <shared-url> is provided, the script sets up the environment (ON mode)."
  echo "If no arguments are provided, the script reverts the changes (OFF mode)."
}

# Check if no arguments are provided (OFF mode)
if [ $# -eq 0 ]; then
  echo "Reverting environment to default (OFF mode)..."

  # Step 1: Revert search-replace in WordPress database
  echo "Reverting WP search-replace..."
  CURRENT_SITE=$(grep -E '^DOMAIN_CURRENT_SITE=' .env | cut -d '=' -f2)
  cd web && lando wp search-replace "$CURRENT_SITE" "pressbooks.test" --all-tables && cd ..

  # Step 2: Restore original .env values
  echo "Restoring .env file..."
  sed -i.bak -E "s|(WP_HOME=).*|\1https://pressbooks.test|g" .env
  sed -i.bak -E "s|(DOMAIN_CURRENT_SITE=).*|\1pressbooks.test|g" .env

  # Step 3: Stop `ngrok`
  echo "Stopping ngrok..."
  pkill -f "ngrok http"

  echo "Environment reverted to default."
  exit 0
fi

# If a domain is provided, execute ON mode
SHARED_URL=$1
echo "Setting up environment with shared URL: $SHARED_URL"

# Step 1: Search-replace in WordPress database
echo "Running WP search-replace..."
cd web && lando wp search-replace pressbooks.test "$SHARED_URL" --all-tables && cd ..

# Step 2: Update .env with WP_HOME and DOMAIN_CURRENT_SITE
echo "Updating .env file..."
sed -i.bak -E "s|(WP_HOME=).*|\1https://$SHARED_URL|g" .env
sed -i.bak -E "s|(DOMAIN_CURRENT_SITE=).*|\1$SHARED_URL|g" .env

# Step 3: Extract port from lando info
echo "Extracting localhost port from lando info..."
LANDO_INFO=$(lando info --format json)
# Extract port from the appserver_nginx service in lando info
LOCALHOST_PORT=$(echo "$LANDO_INFO" | jq -r '.[] | select(.service == "appserver_nginx") | .urls[] | select(startswith("http://localhost:"))' | awk -F':' '{print $3}' | awk -F'/' '{print $1}')

if [ -z "$LOCALHOST_PORT" ]; then
  echo "Error: Could not extract port from lando info."
  exit 1
fi
# Step 4: Run ngrok
echo "Starting ngrok..."
ngrok http "$LOCALHOST_PORT" --domain "$SHARED_URL"

# README

## Script Overview
This script is a powerful and convenient tool to manage the dynamic setup and reversion of your WordPress development environment. It works with **Lando**, **ngrok**, and your `.env` file to ensure seamless transitions between local and public-facing development environments. The script offers two modes of operation:

- **ON mode**: Dynamically configures your environment to use a provided ngrok subdomain.
- **OFF mode**: Reverts the environment to its original state.

## Features
1. **Dynamic Domain Configuration**: Automatically updates WordPress URLs and `.env` values to match the provided ngrok subdomain.
2. **Reversion**: Reverts WordPress database and `.env` settings to their original values.
3. **Automatic Port Extraction**: Extracts the correct localhost port from the `appserver_nginx` service in Lando.
4. **Seamless ngrok Integration**: Starts ngrok with the appropriate configurations to expose your local environment.

## Benefits
- **Automation**: Eliminates manual effort to update URLs, ports, and `.env` configurations.
- **Efficiency**: Quickly toggle between local and public environments.
- **Error Reduction**: Automates repetitive tasks, reducing the risk of human error.
- **Dynamic Reversion**: Reads the current domain from `.env` to ensure accurate reversion of changes.

## Usage
### Prerequisites
Ensure the following tools are installed and configured:
- [Lando](https://docs.lando.dev/)
- [ngrok](https://ngrok.com/)
- `jq` for parsing JSON
- A valid `.env` file with the following keys:
  - `WP_HOME`
  - `DOMAIN_CURRENT_SITE`

### Running the Script
#### ON Mode
To set up the environment with a specific ngrok subdomain:
```bash
./pbshare.sh <shared-url>
```
- Replace `<shared-url>` with your desired ngrok subdomain (e.g., `app.ngrok.io`).

#### OFF Mode
To revert the environment to its original state:
```bash
./pbshare.sh
```

## How It Works
### ON Mode
1. **WordPress Search-Replace**: Updates all database tables, replacing `pressbooks.test` with the provided ngrok subdomain.
2. **.env Update**: Updates `WP_HOME` and `DOMAIN_CURRENT_SITE` to use the ngrok subdomain.
3. **Port Extraction**: Dynamically retrieves the correct localhost port from the `appserver_nginx` service in Lando.
4. **ngrok Start**: Launches ngrok with the extracted port and configured domain.

### OFF Mode
1. **Domain Extraction**: Reads the current `DOMAIN_CURRENT_SITE` from `.env`.
2. **WordPress Search-Replace**: Reverts all database tables, replacing the current domain with `pressbooks.test`.
3. **.env Reversion**: Restores `WP_HOME` and `DOMAIN_CURRENT_SITE` to their original values.
4. **ngrok Stop**: Stops any running ngrok processes.

## Example
### Enable Environment
```bash
./pbshare.sh app.ngrok.io
```
This sets up your environment to use the ngrok subdomain `app.ngrok.io`.

### Revert Environment
```bash
./pbshare.sh
```
This reverts the environment to its original state using `pressbooks.test`.

## Troubleshooting
- Ensure `jq` is installed and accessible in your `$PATH`.
- Verify that the `.env` file contains the required keys (`WP_HOME` and `DOMAIN_CURRENT_SITE`).
- Check the output of `lando info --format json` to confirm the `appserver_nginx` service is correctly configured.

## License
This script is open-source and provided under the MIT License. Feel free to modify and use it in your projects.


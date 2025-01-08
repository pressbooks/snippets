# Misc commands

This directory contains various scripts that are not part of the main codebase, but are useful for various tasks, both have dry-run mode.

- fix-order.php

This script is a wp-cli command that can be used to fix the order of the menu items in the database. It is useful when the order of the menu items is incorrect, and you want to fix it without having to manually reorder the items in the admin interface.

```bash
wp eval-file fix-order.php <book-id> dry-run
```

- fix-social-media-share.php

This script is a wp-cli command that can be used to fix the social media metadata options in the database. It is useful when the social media metadata options are incorrect, and you want to fix it without having to manually update the options in the admin interface.

```bash
wp eval-file fix-social.php social fix dry-run
```
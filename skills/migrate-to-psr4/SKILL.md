---
name: migrate-to-psr4
description: Use when refactoring WordPress plugins from legacy patterns (inc/, class-*.php, HM Autoloader) to PSR-4 autoloading. Triggers for namespace migration, directory restructuring, or converting WordPress naming conventions to modern PHP standards.
---

## What this skill does

Migrates WordPress plugins from legacy patterns to PSR-4 autoloading:

| From | To |
|------|-----|
| `inc/` directory | `src/` directory |
| `class-plugins-config.php` | `PluginsConfig.php` |
| HM Autoloader | Composer PSR-4 autoload |
| Flat namespace | Hierarchical namespace |

## Workflow

### Step 1: Detect legacy patterns

Check for:
- `inc/` or `includes/` directory (instead of `src/`)
- `class-*.php` file naming (WordPress style)
- `humanmade/autoloader` in composer.json
- `register_class_path()` calls in main plugin file
- Manual `require_once` statements

### Step 2: Plan the migration

List all files to migrate with their new paths:

```
inc/class-plugins-config.php    → src/PluginsConfig.php
inc/class-network-integrations.php → src/NetworkIntegrations.php
inc/class-mailgunmailer.php     → src/MailgunMailer.php
```

Determine the new namespace structure based on plugin name.

### Step 3: Execute migration

1. Create `src/` directory with subdirectories as needed
2. Move files, renaming to PascalCase
3. Update namespace in each file
4. Update class names (remove underscores)
5. Update composer.json autoload
6. Update main plugin file imports
7. Run `composer dump-autoload`
8. Run `pint` to fix style
9. Run tests
10. Delete old `inc/` directory

## Reference: Before/After Examples

### File renaming

**Before:** `inc/class-plugins-config.php`
```php
<?php

namespace PressbooksConfig;

class Plugins_Config {
    protected static $instance;
    
    public static function init(): Plugins_Config {
        if (is_null(self::$instance)) {
            self::$instance = new static;
        }
        return self::$instance;
    }
}
```

**After:** `src/PluginsConfig.php`
```php
<?php

namespace Pressbooks\PluginsConfig;

class PluginsConfig
{
    protected static ?PluginsConfig $instance = null;

    public static function init(): PluginsConfig
    {
        if (is_null(self::$instance)) {
            self::$instance = new static;
        }

        return self::$instance;
    }
}
```

### composer.json

**Before:**
```json
{
  "require": {
    "humanmade/autoloader": "*"
  }
}
```

**After:**
```json
{
  "autoload": {
    "psr-4": {
      "Pressbooks\\PluginsConfig\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  }
}
```

### Main plugin file

**Before:**
```php
<?php

use function HM\Autoloader\register_class_path;

register_class_path('PressbooksConfig', __DIR__ . '/inc');

add_action('plugins_loaded', ['\PressbooksConfig\Plugins_Config', 'init']);
```

**After:**
```php
<?php

use Pressbooks\PluginsConfig\PluginsConfig;

add_action('plugins_loaded', [PluginsConfig::class, 'init']);
```

## Namespace conventions

| Directory | Namespace |
|-----------|-----------|
| `src/` | `PluginName\` |
| `src/Controllers/` | `PluginName\Controllers\` |
| `src/Models/` | `PluginName\Models\` |
| `src/Services/` | `PluginName\Services\` |
| `src/Support/` | `PluginName\Support\` |
| `tests/` | `Tests\` |

## Migration checklist

- [ ] Create `src/` directory structure
- [ ] Move files from `inc/` to `src/`, renaming to PascalCase
- [ ] Update namespace in each file
- [ ] Update class names (remove underscores, use PascalCase)
- [ ] Add PSR-4 autoload to `composer.json`
- [ ] Remove HM Autoloader dependency if present
- [ ] Remove `register_class_path()` calls from main plugin file
- [ ] Update main plugin file imports
- [ ] Run `composer dump-autoload`
- [ ] Run `./vendor/bin/pint`
- [ ] Run `./vendor/bin/phpstan analyse`
- [ ] Run tests to verify nothing broke
- [ ] Delete old `inc/` directory
- [ ] Commit changes

## Important rules

- Never skip running `composer dump-autoload` after namespace changes
- Always run `pint` after migration to ensure PSR-12 compliance
- Update all references to old class names (search for old namespace)
- Test thoroughly before deleting old `inc/` directory
- If plugin has tests, update test namespaces too

## Follow-up

After migration, run `pressbooks-standards` skill to verify configs point to `src/` and all tools pass.
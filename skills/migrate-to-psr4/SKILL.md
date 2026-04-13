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
7. Run `lando composer dump-autoload`
8. **Review PHPDoc preservation** (see warning below)
9. Run `lando composer standards` to lint
10. Run `lando composer test` to run tests
11. Delete old `inc/` directory

### ⚠️ PHPDoc Warning

Laravel Pint will remove PHPDoc comments that it considers redundant. This includes:

- `@var` annotations on typed properties (redundant with PHP 7.4+ typed properties)
- `@param` annotations when type is already in signature
- `@return` annotations when return type is declared
- **BUT ALSO** any descriptions in those PHPDoc blocks

**Before running Pint**, check for meaningful PHPDoc descriptions that should be preserved:

```bash
grep -r "@var\|@param\|@return" src/ | grep -v "^[^:]*:[^:]*:$"
```

**If meaningful descriptions exist**, either:

1. Add typed properties before running Pint (Pint will preserve descriptions on typed code)
2. Or manually restore descriptions after Pint runs

Example of what gets stripped:

```php
// Before Pint (with description)
/**
 * The role that is allowed to handle this plugin
 *
 * @var string
 */
private $minimumRole = 'manage_sites';

// After Pint (description lost)
private string $minimumRole = 'manage_sites';
```

**Pint will NOT strip:**
- Class-level PHPDoc with descriptions
- Method-level PHPDoc with descriptions (if method has complex logic)
- `@see`, `@link`, `@throws`, `@deprecated` annotations

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
- [ ] Run `lando composer dump-autoload`
- [ ] **Review meaningful PHPDoc descriptions before running Pint**
- [ ] Run `lando composer standards` to lint
- [ ] Run `lando composer test` to verify nothing broke
- [ ] **Restore any lost PHPDoc descriptions if needed**
- [ ] Delete old `inc/` directory
- [ ] Commit changes

## Important rules

- Never skip running `lando composer dump-autoload` after namespace changes
- Always run `lando composer standards` for lint, `lando composer test` for tests
- **Check for meaningful PHPDoc descriptions before running Pint** — it will strip annotations it considers redundant
- Update all references to old class names (search for old namespace)
- Test thoroughly before deleting old `inc/` directory
- If plugin has tests, update test namespaces too

## Follow-up

After migration, run `pressbooks-standards` skill to verify configs point to `src/` and all tools pass.
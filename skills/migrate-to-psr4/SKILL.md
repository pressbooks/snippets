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
9. **Keep the Composer autoloader self-contained** (see warning below)
10. **Check for translation calls that run before `init`** (see warning below)
11. Run `lando composer standards` to lint
12. Run `lando composer test` to run tests
13. Delete old `inc/` directory

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

### ⚠️ Composer Autoloader Warning

Many of these plugins (`Release Asset: true` in the plugin header) are distributed as a **standalone** GitHub release zip, installed directly on networks that have no relation to any monorepo or parent project. That means the plugin **cannot** rely on a host project's root Composer autoloader to resolve its own PSR-4 classes — it must always be able to bootstrap itself.

When migrating off `register_class_path()` / HM Autoloader, **keep (or restore) an unconditional require of the plugin's own `vendor/autoload.php`** in the main plugin file:

```php
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    $title = __('Dependencies Missing', 'plugin-text-domain');
    $body = __('Please run <code>composer install</code> from the root of the plugin directory.', 'plugin-text-domain');
    wp_die("<h1>{$title}</h1><p>{$body}</p>", $title);
}
```

**Do not** gate this behind `class_exists('\SomeThirdPartyLib')` (a legacy pattern seen in sibling SSO plugins) — that incorrectly skips loading the plugin's *own* PSR-4 classes whenever another plugin happens to have already loaded the same third-party dependency. Composer's generated autoloader is safe to require unconditionally; it guards against double registration internally.

**Do not** remove this require entirely to "rely on the global/root composer installation" — that was tried in a real migration (pressbooks-cas-sso PR #229) and caused `Fatal error: Uncaught TypeError: ... class "\Namespace\ClassName" not found` the moment the plugin's code diverged even slightly from whatever the host project's `composer.lock` happened to have installed.

### ⚠️ Translation Timing Warning

This migration pattern typically hooks a class's `init()` method directly to `plugins_loaded` (e.g. `add_action('plugins_loaded', [Admin::class, 'init'])`), which runs **before** WordPress's `init` action. Since WP 6.7, calling any translation function (`__()`, `_e()`, `esc_html__()`, `load_plugin_textdomain()`, etc.) before `init` triggers:

```
Notice: Function _load_textdomain_just_in_time was called incorrectly.
Translation loading for the <domain> domain was triggered too early...
```

Audit every class wired to `plugins_loaded` for translation calls that run **eagerly** — inside `init()`, `hooks()`, or a constructor — rather than lazily inside a callback that only fires on a later hook (`init`, `admin_menu`, `network_admin_notices`, a render method, etc.):

```bash
grep -rn "__(\|_e(\|esc_html__(\|esc_attr__(\|load_plugin_textdomain(" src/
```

For each match, trace back to confirm it only executes from a hook that fires at/after `init`. Common fixes:

```php
// BAD — runs immediately when hooks() is called from plugins_loaded
public static function hooks(Admin $obj)
{
    load_plugin_textdomain('plugin-text-domain', false, 'plugin-text-domain/languages/');
    add_action('network_admin_menu', [$obj, 'addMenu']);
}

// GOOD — deferred to init
public static function hooks(Admin $obj)
{
    add_action('init', function () {
        load_plugin_textdomain('plugin-text-domain', false, 'plugin-text-domain/languages/');
    });
    add_action('network_admin_menu', [$obj, 'addMenu']);
}
```

```php
// BAD — __() evaluated in the constructor, called from plugins_loaded
public function __construct(Admin $admin)
{
    $message = __('Not configured.', 'plugin-text-domain');
    add_action('network_admin_notices', function () use ($message) {
        echo $message;
    });
}

// GOOD — __() evaluated inside the closure, which only runs on network_admin_notices
public function __construct(Admin $admin)
{
    add_action('network_admin_notices', function () {
        echo __('Not configured.', 'plugin-text-domain');
    });
}
```

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

// Keep this — see the Composer Autoloader Warning above. Plugins that ship as a
// standalone release zip cannot assume a host project's autoloader will resolve
// their own PSR-4 classes.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    $title = __('Dependencies Missing', 'plugin-text-domain');
    $body = __('Please run <code>composer install</code> from the root of the plugin directory.', 'plugin-text-domain');
    wp_die("<h1>{$title}</h1><p>{$body}</p>", $title);
}

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
- [ ] **Keep (or restore) the unconditional `require_once __DIR__.'/vendor/autoload.php'` bootstrap in the main plugin file** — never delete it to "rely on a host project's autoloader"
- [ ] **Audit every class hooked to `plugins_loaded` for translation calls (`__()`, `_e()`, `load_plugin_textdomain()`) that run eagerly** — defer them to `init` or later (see Translation Timing Warning)
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
- **Never remove the plugin's own `vendor/autoload.php` bootstrap** from the main plugin file. Plugins that ship as a standalone release zip must be able to load their own PSR-4 classes regardless of what (if anything) installed them — don't make this the one thing that silently breaks the moment the plugin's code and a host project's `composer.lock` drift apart
- **Never gate the autoloader require behind `class_exists('\SomeThirdPartyLib')`** — only gate it on `file_exists(__DIR__.'/vendor/autoload.php')`
- **Trace every class wired to `plugins_loaded` for translation calls that run before `init`** — WP 6.7+ will emit a `_doing_it_wrong` notice for any `__()`/`_e()`/`load_plugin_textdomain()` call reachable eagerly from that hook
- Update all references to old class names (search for old namespace)
- Test thoroughly before deleting old `inc/` directory
- If plugin has tests, update test namespaces too

## Follow-up

After migration, run `pressbooks-standards` skill to verify configs point to `src/` and all tools pass.
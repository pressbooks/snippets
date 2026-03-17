---
name: pressbooks-standards
description: Use when auditing or setting up PHP coding standards and test infrastructure in WordPress plugin/theme repos. Triggers for linting, Laravel Pint setup, PSR-12 compliance, or generating config files (pint.json, phpunit.xml, .editorconfig). Also triggers for setting up tests/ directory.
---

## What this skill enforces

Two core tools with sensible defaults:

1. **Laravel Pint** — PSR-12 code style fixer
2. **PHPUnit** — Testing with WordPress test framework

Plus `.editorconfig` for consistent formatting across editors.

## Workflow

### Step 1: Identify the repo type

- A file with `Plugin Name:` in its header → **WordPress plugin**
- A `style.css` with `Theme Name:` → **WordPress theme**
- A `composer.json` with `"type": "wordpress-plugin"` or `"wordpress-theme"` confirms it

### Step 2: Check existing config files

Look for at the repo root:

- `pint.json`
- `phpunit.xml`
- `.editorconfig`
- `composer.json`

Check `tests/` directory:

- `tests/bootstrap.php`
- `tests/TestCase.php`
- `tests/Unit/SanityTest.php` (at minimum)

Check for legacy patterns:

- `inc/` directory (instead of `src/`)
- `class-*.php` file naming
- HM Autoloader (`humanmade/autoloader`)
- `register_class_path()` calls

**If legacy patterns found, offer migration via `migrate-to-psr4` skill.**

Check GitHub Actions:

- `.github/workflows/tests.yml`
- `.github/dependabot.yml`

**If missing, ask the user then generate from templates below.**

Ask:
1. `requires_pressbooks` — Does this plugin require Pressbooks core? (default: true)
2. `use_mariadb` — Use MariaDB for tests? (default: true)

### Step 3: Audit composer.json

Required dev dependencies:

```json
{
  "require-dev": {
    "laravel/pint": "^1.10",
    "yoast/phpunit-polyfills": "^1.0",
    "mockery/mockery": "^1.6"
  }
}
```

Required scripts:

```json
{
  "scripts": {
    "test": "vendor/bin/phpunit --configuration phpunit.xml",
    "test-coverage": "vendor/bin/phpunit --configuration phpunit.xml --coverage-clover coverage.xml",
    "fix": "vendor/bin/pint",
    "standards": "vendor/bin/pint --test"
  }
}
```

### Step 4: Run audit (if vendor/ exists)

```bash
lando composer standards
lando composer test
```

### Step 5: Report findings

1. Missing config files
2. Missing test files
3. Legacy patterns detected → **recommend `migrate-to-psr4` skill**
4. Config deviations
5. Composer gaps
6. Linting results

### Step 6: Fix

Generate missing configs from templates, create `tests/` directory with required files, update composer.json, run `lando composer fix` if user confirms.

---

## Reference: pint.json

```json
{
    "preset": "psr12",
    "rules": {
        "simplified_null_return": true,
        "new_with_braces": {
            "anonymous_class": false,
            "named_class": false
        }
    },
    "exclude": [
        "wp-content"
    ]
}
```

## Reference: phpunit.xml

```xml
<?xml version="1.0"?>
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
    bootstrap="tests/bootstrap.php"
    colors="true"
    backupGlobals="false"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./src</directory>
        </include>
    </coverage>
    <testsuites>
        <testsuite name="Pressbooks">
            <directory suffix="Test.php">./tests/</directory>
        </testsuite>
    </testsuites>
    <php>
        <const name="WP_TESTS_MULTISITE" value="1"/>
    </php>
</phpunit>
```

## Reference: .editorconfig

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_size = 4
indent_style = space
insert_final_newline = true
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false

[*.{yml,yaml}]
indent_size = 2

[*.{js,jsx,ts,tsx,json,css,scss}]
indent_size = 2

[Makefile]
indent_style = tab
```

## Reference: Test files

### tests/bootstrap.php

```php
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$tests_dir = getenv('WP_TESTS_DIR') ?: '/tmp/wordpress-tests-lib';

require_once "{$tests_dir}/includes/functions.php";

tests_add_filter('muplugins_loaded', function () {
    require_once __DIR__ . '/../../pressbooks/pressbooks.php';
});

require_once "{$tests_dir}/includes/bootstrap.php";
```

### tests/TestCase.php

```php
<?php

namespace Tests;

use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

abstract class TestCase extends PolyfillTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
```

### tests/Unit/SanityTest.php

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class SanityTest extends TestCase
{
    public function testTrueIsTrue(): void
    {
        $this->assertTrue(true);
    }

    public function testPluginConstantsAreDefined(): void
    {
        $this->assertTrue(defined('ABSPATH'));
    }
}
```

---

## Expected directory structure

```
plugin-name/
├── .github/
│   ├── workflows/
│   │   └── tests.yml
│   └── dependabot.yml
├── plugin-name.php          # Main plugin file
├── composer.json
├── pint.json
├── phpunit.xml
├── .editorconfig
├── .gitignore
├── src/                     # PSR-4 autoloaded classes
│   ├── Bootstrap.php
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Support/
├── resources/
│   ├── assets/
│   └── views/
├── languages/
├── tests/
│   ├── bootstrap.php
│   ├── TestCase.php
│   └── Unit/
└── vendor/                  # gitignored
```

---

## Reference: GitHub Actions

### .github/workflows/tests.yml

```yaml
name: Run Tests 🧪

on:
  push:
    branches:
      - dev
    paths:
      - '**/*.php'
      - '**/*.js'
      - '**/*.css'
      - '**/*.json'
      - '**/*.yml'
      - 'composer.lock'
      - 'composer.json'
      - '!languages/**'
      - '!docs/**'
      - '!**/*.md'
  pull_request:
    branches:
      - dev
    paths:
      - '**/*.php'
      - '**/*.js'
      - '**/*.css'
      - '**/*.json'
      - '**/*.yml'
      - 'composer.lock'
      - 'composer.json'
      - '!languages/**'
      - '!docs/**'
      - '!**/*.md'
  workflow_dispatch:

jobs:
  plugin-tests:
    uses: pressbooks/reusable-workflows/.github/workflows/pb-plugin-tests.yml@main
    secrets: inherit
    with:
      requires_pressbooks: <requires_pressbooks>
      use_mariadb: <use_mariadb>
```

### .github/dependabot.yml

```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
    allow:
      - dependency-type: "direct"
    open-pull-requests-limit: 5
    versioning-strategy: "increase-if-necessary"
    ignore:
      - dependency-name: "*"
        update-types: [ "version-update:semver-major" ]
    groups:
      composer-dependencies:
        dependency-type: "production"
      composer-dev-dependencies:
        dependency-type: "development"

  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
    open-pull-requests-limit: 5
    groups:
      all-github-actions:
        patterns:
          - ".*"
```

---

## Legacy patterns → migrate-to-psr4

If detected, use `migrate-to-psr4` skill:

| Pattern | Action |
|---------|--------|
| `inc/` directory | Migrate to `src/` |
| `class-*.php` files | Rename to PascalCase |
| HM Autoloader | Replace with PSR-4 autoload |
| `register_class_path()` | Remove, use composer autoload |

## Important rules

- Never auto-fix without showing changes first
- Preserve `.dist` naming conventions
- PSR-12 and WordPress Coding Standards conflict — enforce PSR-12 only
- Legacy repos without `src/` should be migrated using `migrate-to-psr4` skill
- After migration, use `post-migration-recommendations` skill to identify refactoring opportunities
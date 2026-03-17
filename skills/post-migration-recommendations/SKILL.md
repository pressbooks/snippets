---
name: post-migration-recommendations
description: Use after PSR-4 migration to identify refactoring opportunities, namespace inconsistencies, and code quality improvements. Generates a REFACTORING.md file for developers to track technical debt and improvement tasks.
---

## What this skill does

After migration, analyze the codebase and generate `REFACTORING.md` with:

1. Namespace inconsistencies (non-WordPress code not following PSR-4)
2. Refactoring patterns to apply
3. Code quality improvements
4. Architecture recommendations

## Workflow

### Step 1: Scan for namespace issues

Look for:

- `use` statements pointing to non-existent or inconsistent namespaces
- Direct class references that don't match PSR-4 structure
- `new \ClassName()` with backslash (root namespace lookups)
- Static calls to classes in other namespaces

**Exclude WordPress core/functions:**
- `add_action`, `add_filter`, `do_action`, `apply_filters`
- `get_option`, `update_option`, `get_post`, etc.
- Any `WP_*` or `WordPress\*` namespace

### Step 2: Identify refactoring patterns

| Pattern | Detection | Recommendation |
|---------|-----------|---------------|
| Singleton abuse | `protected static $instance` | Use dependency injection |
| God class | > 500 lines or > 20 methods | Split into focused classes |
| Long method | > 50 lines | Extract to smaller methods |
| Missing return types | Methods without `: type` | Add return type declarations |
| Direct instantiation | `new ClassName()` in methods | Inject via constructor |
| Static coupling | `ClassName::method()` everywhere | Convert to instance methods |
| Anonymous functions | Inline closures in hooks | Extract to named methods |

### Step 3: Check architecture patterns

- Controllers directly accessing database?
- Models with business logic?
- Missing service layer for complex operations?
- Views/templates mixed with logic?

### Step 4: Generate REFACTORING.md

Create the file with sections:

```markdown
# Refactoring Recommendations

Generated: [date]

## Priority: High

[Items that affect maintainability]

## Priority: Medium

[Items that improve code quality]

## Priority: Low

[Nice-to-have improvements]

## Namespace Issues

[Non-compliant namespace references]

## Technical Debt Log

| File | Issue | Effort | Status |
|------|-------|--------|--------|
| ... | ... | S/M/L | TODO |
```

## Detection patterns

### Namespace inconsistencies

```bash
# Find use statements with inconsistent casing
grep -r "^use " src/ | grep -v "Pressbooks\\\\PluginsConfig"

# Find direct instantiations with root namespace
grep -r "new \\\\" src/
```

### Singleton detection

```php
// Pattern to flag
protected static $instance;
public static function getInstance() { ... }
```

### Missing type hints

```php
// Flag methods without return type
public function doSomething($param) { }
// Should be:
public function doSomething(string $param): void { }
```

## REFACTORING.md template

```markdown
# Refactoring Recommendations

Generated: [DATE]

## Namespace Issues

### Files with external namespace dependencies

| File | External Namespace | Action |
|------|-------------------|--------|
| `src/SomeClass.php` | `OtherPlugin\SomeClass` | Verify dependency exists |

### Root namespace lookups

| File | Line | Code | Recommendation |
|------|------|------|----------------|
| `src/Service.php` | 42 | `new \GuzzleHttp\Client()` | Add `use GuzzleHttp\Client;` |

---

## Architecture Recommendations

### Service Layer

[Recommendations for service extraction]

### Dependency Injection

[Classes that would benefit from DI]

---

## Code Quality

### God Classes

| Class | Lines | Methods | Recommendation |
|-------|-------|---------|----------------|
| `PluginsConfig` | 540 | 25 | Split into Services |

### Long Methods

| File | Method | Lines | Recommendation |
|------|--------|-------|----------------|
| `src/PluginsConfig.php` | `hooks()` | 300+ | Extract to separate classes |

### Missing Return Types

[List methods without return type declarations]

---

## Technical Debt Log

| ID | File | Issue | Effort | Status |
|----|------|-------|--------|--------|
| 001 | src/PluginsConfig.php | Singleton pattern | M | TODO |
| 002 | src/NetworkIntegrations.php | God class | L | TODO |

---

## Progress Tracking

- [ ] High priority items addressed
- [ ] Namespace issues resolved
- [ ] Return types added
- [ ] Documentation updated
```

## Important rules

- Never modify code directly — only generate recommendations
- Exclude WordPress core functions and hooks from namespace checks
- Focus on actionable, specific recommendations
- Include file paths and line numbers
- Estimate effort (S/M/L) for each item
- Update existing REFACTORING.md if it exists (append new findings)

## Follow-up

After generating REFACTORING.md, suggest running `pressbooks-standards` to ensure configs are correct, then commit the recommendations file.
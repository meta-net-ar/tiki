# CI Utilities (`src/ci/`)

This folder contains scripts and helpers used by CI to validate code, build artifacts, and generate reports. Most scripts are standalone and can be invoked locally for pre-commit checks.

Common usage pattern:
- PHP scripts: php src/ci/script_name.php [options]
- Shell scripts: bash src/ci/script_name.sh [options]

## Build and packaging
- build_frontend.sh — Builds frontend assets (Vite) for production CI jobs.
- build_package.sh — Produces a distributable package (archives, cleaned tree).

## Composer and vendor management
- check_composer_extensions.php — Ensures required PHP extensions match composer.json constraints.
- check_composer_stability.php — Verifies allowed minimum-stability / prefer-stable.
- update_composer_lock.php — Synchronizes composer.lock with composer.json.
- update_vendor_bundled.php — Refreshes vendor_bundled contents used in deployments.

## Coding standards and static checks
- check_smarty_syntax.php — Lints Smarty templates for syntax errors.
- check_template_translation_standards.php — Validates i18n strings in templates.
- check_unix_ending_line.php — Verifies files use LF line endings.
- check_bom_encoding.php — Detects UTF-8 BOM or invalid encodings.
- check_caret_operator.php — Flags undesirable caret (^) constraints in composer requirements.
- stripcomments.php — Strips comments for specific validation/build workflows.

## Database schema and SQL checks
- check_schema_naming_convention.php — Enforces naming conventions for tables, indexes, FKs.
- check_schema_sql_drop.php — Detects unsafe DROP statements or patterns.
- check_schema_upgrade.php — Validates upgrade scripts ordering and safety.
- check_sql_engine.php — Ensures required/default storage engines are respected.
- check_sql_engine_conversion.php — Audits or suggests engine conversions.

## Links and external references
- check_external_links.php — Scans documentation/templates for broken or disallowed external links.

## Platform and environment
- check_platform_binaries.php — Verifies required CLI tools are available (e.g., zip, node).

## Source conventions
- check_alphabetical_list.php — Ensures sorted lists (e.g., dependencies in package.json, composer.json) stay alphabetical.

## Security
- securitycheck.php — Runs security-oriented validations (basic policy checks).

## Changelog and release notes
- generate_changelog.php — Produces/updates CHANGELOG artifacts from git history.

## Additional configuration
- smartyl.rules.xml — Custom ruleset for Smarty code style checks.

Notes:
- Run scripts from the repository root to ensure relative paths resolve correctly.
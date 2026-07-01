---
name: tiki-testing
description: Use when writing or running a Tiki PHPUnit test, choosing between the unit and integration group, or debugging test failures like warnings-as-errors or a hanging run. Covers the TikiTestCase base class, the file/class naming rule, @group unit vs integration, the run commands, and the baseline runner.
---

# Tiki PHPUnit testing

How to write and run a Tiki unit/integration test. Paths below are relative to
the Tiki source-tree root — every one is a real file you can open.

## Where tests live + naming rule

All tests live under **`lib/test/`**, in a subfolder that mirrors the code
area they exercise (e.g. `lib/test/auth/`, `lib/test/Core/Transition/`).
The PHPUnit testsuite scans only this directory (`phpunit.xml.dist:30`), so a
test placed anywhere else is never collected.

- **File and class name both END with `Test`** (`MembershipTest`,
  `WikiParserTest`). This is how PHPUnit discovers the class.
- **Class-name prefix mirrors the directory** — e.g. `Transition_BasicTest` in
  `Core/Transition/`. (In practice prefixes are loose; match neighbours in the
  same folder rather than inventing one.)
- Extend **`TikiTestCase`** (`lib/test/TikiTestCase.php:20`, an abstract
  subclass of `PHPUnit\Framework\TestCase` with `$backupGlobals = false` plus
  helpers like `assertTriggeredError`). A purely isolated unit test with no Tiki
  bootstrap may extend `PHPUnit\Framework\TestCase` directly, as
  `lib/test/Core/Transition/BasicTest.php:12` does.

## @group unit vs integration — pick one

| Group | Tag | Scope | DB? |
|---|---|---|---|
| Unit | `@group unit` | one class in isolation | no |
| Integration | `@group integration` | multiple libs, hits the DB | **yes** |

The tag goes in the class docblock. Choose `integration` the moment your test
touches `TikiLib::lib(...)`, a `*Lib` object, or persisted data — those need the
test DB (see **tiki-database**).

## Minimal real-shaped test

Modeled on `lib/test/auth/MembershipTest.php`. Note the `: void` return
types — required under the PHPUnit 10.5 that Tiki ships (`phpunit.xml.dist:10`):

```php
<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * @group integration
 */
class MembershipTest extends TikiTestCase
{
    protected $userlib;

    protected function setUp(): void
    {
        parent::setUp();                 // always call parent first
        $this->userlib = new UsersLib();
        $this->userlib->remove_user('membershiptest_a');   // clean prior run
        $this->userlib->add_user('membershiptest_a', 'abc', 'a@example.com');
    }

    protected function tearDown(): void
    {
        $this->userlib->remove_user('membershiptest_a');
        parent::tearDown();
    }

    public function testUserIsInGroup(): void
    {
        $this->assertTrue($this->userlib->user_exists('membershiptest_a'));
    }
}
```

For a pure `@group unit` test, drop the DB setup and just construct the class
under test (see `lib/test/Core/Transition/BasicTest.php`).

## Setup (once)

1. Create a **dedicated** test DB and symlink the config — copy
   `lib/test/local.php.dist` → `lib/test/local.php` (or
   `ln -s lib/test/local.php.dist lib/test/local.php` if the defaults suit). It
   holds `$dbs_tiki = 'tiki_unit_test'` and a `tiki_tester` user.
2. Tiki 22+: `php console.php dev:configure` provisions the test setup; Composer
   pulls PHPUnit in. (Build details: **tiki-build-composer**.)

## Running

Run from the Tiki root. `phpunit` is the Composer-installed runner:

```bash
php phpunit                                   # whole suite
php phpunit --filter testUserIsInGroup        # one method
php phpunit --filter MembershipTest           # one class
php phpunit --exclude-group gui,GoogleTranslate   # skip Selenium + proxy-bound
```

`phpunit.xml.dist` already excludes `disable`, `marked-as-skipped`,
`marked-as-incomplete`, `GoogleTranslate`, `RequiresWebserver`,
`api-integration-test` (lines 48–58); add `gui` on the CLI when Selenium isn't up.

### Baseline runner — only NEW failures

A full suite has known-failing legacy tests. To see only what *your* change broke
(or fixed) versus the recorded baseline:

```bash
cd lib/test && php phpunit_with_baseline.php
```

Driven by `lib/test/phpunit_with_baseline.php` →
`lib/test/TestRunnerWithBaseline.php`. Use this to judge a change, not a raw
`php phpunit` red count.

## Gotchas

- **E_WARNING becomes an exception.** PHPUnit is configured to surface warnings
  (`displayDetailsOnTestsThatTriggerWarnings="true"`, `phpunit.xml.dist:13`),
  so legacy code that emits an `E_WARNING` fails the test as a "warning". It's
  the tested code, not your assertion — fix the warning or guard the legacy call
  with `error_reporting()`.
- **The test DB gets erased.** Tests truncate and rewrite tables. Point
  `local.php` at a throwaway schema (`tiki_unit_test`), never a dev/prod DB.
- **A hanging run almost always = MySQL down.** Start the DB service (XAMPP /
  mysqld) before blaming the test. Second suspect: `memory_limit` — the config
  sets `-1` (unlimited), but a stricter `php.ini` on the CLI can still OOM.

## Cross-cutting reflexes

- Integration test schema, fixtures, and query patterns → **tiki-database**;
  schema migrations under test → **tiki-schema-upgrade**.
- Testing a wiki plugin (`lib/test/wiki-plugins/`) → **tiki-wiki-plugins**;
  testing a service handler → **tiki-services**.
- New code header + PSR-12/tabs rules apply to test files too → **tiki-page-bootstrap**.

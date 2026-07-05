---
name: tiki-tracker-field-coverage
description: Use when adding characterization test coverage for a Tiki tracker field type together with a developer — picking the next uncovered type from the coverage checklist, agreeing where its stored value comes from (submitted, generated at save, or an external service), building the coverage contract (every code branch, every option value, every standard edge input: pinned or waived in writing), writing a record-don't-design test on FieldCharacterizationTestCase, judging every recorded value with the developer, investigating suspicious values through the real save path, and delivering one field type as one commit / one merge request. This is a guided collaborative process with mandatory stop points, not an autonomous sweep.
---

# Tracker field-type coverage, one field at a time

Tiki has 53 tracker field classes; most had zero direct tests. Trackers were
named the most challenging area of the codebase in a community meeting when
discussing where AI effort should go — and tests don't hurt. The goal of this
process is twofold: build the characterization coverage that makes refactoring
the tracker subsystem safe, and let developers learn test-driven, agent-assisted
development on a real task, one small reviewable unit at a time.

You (the agent) conduct the process. The developer decides. Every session
covers **one field type** and ends in **one commit → one merge request**, which
the community discusses. Progress is tracked in
`lib/test/Core/Tracker/Field/Characterization/COVERAGE.md`.

The installed base is 24 years of sites worldwide, holding every data pattern
you can imagine and many you can't: pre-1970 birthdates, option values
containing commas, `'0'` everywhere PHP treats it as empty, multibyte
everything, API clients sending arrays and millisecond timestamps. Happy-path
coverage protects none of that. The coverage contract in step 3 exists so
every session covers the unexpected patterns, not just the demo inputs.

## Ground rules — read these first, they override your instincts

1. **One field type per session.** A class registering several type codes
   (Dropdown = `d`/`D`/`R`/`M`) is still one test. Only after the developer has
   landed two solo fields may you offer to pair two trivially similar
   stores-what-was-submitted types in one session — their call.
2. **Never edit production code while characterizing.** Characterization
   records what the code does. If a recorded value looks wrong, that is the
   *suspicious-value subflow* below — never a quick fix, never silent.
3. **Never adjust an expectation just to make a test green.** An expectation
   may only contain a value the developer has looked at and judged. A test
   that refuses to go green is information, not an obstacle (see the
   AutoIncrement story below).
4. **Stop at every GATE.** The steps below mark gates where you present
   findings and wait for the developer. Do not run ahead, even if the next
   action seems obvious. The developer's understanding is a deliverable of
   equal rank with the test.
5. **Record, don't design.** Pin actual current behavior, including oddities.
   Quirky pins get case names that say so: `'non-numeric string passes through
   unchanged (pins current behavior)'`.
6. **A branch you read is a branch you cover — or waive in writing.** The
   coverage contract (step 3) turns the class reading into a table; the
   session is not done until every row is a pinned case or a one-line waiver
   in the test docblock. "The examples had four cases" is not a stopping
   criterion, and neither is "the happy path is green".
7. **A pin's name states the layer it proves.** `getFieldData()`, the
   replace_item save, `isValid()`, CSV render and full render are different
   layers with different behavior. "value outside options is NOT rejected" is
   a wrong pin name if `isValid()` rejects it on the services path — name it
   `'…accepted at getFieldData level (isValid() rejects it on the services
   path — see testIsValid)'`.
8. **Tests must pass on any machine.** The harness pins the display/server
   timezone prefs and PHP's default timezone to UTC for you; don't defeat
   that with expectations computed from the host environment, and prefer
   `gmdate()` over `date()` in closure expectations.
9. **Speak the developer's language, not this file's.** "Pin", "waiver",
   "characterization", "coverage contract" are internal vocabulary of this
   skill — the developer has not read it and is not expected to. Define
   every such term in plain words the first time you use it in a session
   ("pinning means: writing a test that asserts what the code does today,
   right or wrong"; "a waiver is just a written note saying we chose not to
   test something and why") and re-explain at any sign of confusion. Never
   invent numbered categories when talking to the developer — say what a
   thing does, not which bucket it's in. If the developer cannot say in
   their own words why a case exists or what a gate is deciding, that gate
   is not passed — their understanding is a deliverable (rule 4), and
   undefined jargon is how it silently fails. Jargon also does its damage
   quietly: it makes testing sound harder than it is, and a developer who
   leaves a session thinking tests are arcane will not come back.

## The pieces you work with

| Path | What it is |
|---|---|
| `lib/test/TestHelpers/Tracker/FieldCharacterizationTestCase.php` | Abstract base: drives a field through input handling, creation and update persistence round-trips, the stored-value (edit-form) fallback, render (CSV + full HTML), indexing, capability interfaces. Pins UTC for the class. |
| `lib/test/TestHelpers/Tracker/TrackerFixtureFactory.php` | Creates real trackers/fields/items through `TrackerLib::replace_item()` (a data-array save including **all** definition fields, missing ones as `''`); `updateItem()` re-saves an existing item (the only fixture path where `handleSave()` sees a non-null `$oldValue`); `cleanup()` removes everything |
| `lib/test/Core/Tracker/Field/Characterization/` | One `<Type>Test.php` per characterized class; `RegistryConformanceTest.php` pins registry + capability interfaces for all 53 |
| `lib/test/Core/Tracker/Field/Characterization/COVERAGE.md` | The checklist: which types are done, planned, conformance-only |
| `lib/test/tools/tracker-capability-matrix.php` | CLI generator for the full type × capability × coverage matrix (prints markdown to stdout) |
| `lib/test/tools/uncovered-lines.php` | Prints the uncovered line ranges of one file from a Clover coverage report — the mechanical coverage audit in step 7 |
| `lib/core/Tracker/Field/<Type>.php` | The class under test; `lib/core/Tracker/Field/Factory.php` maps codes → classes and pref gating |

Existing tests to imitate: `TextTest.php` (simple), `DropdownTest.php` (quirky
input semantics), `TextAreaTest.php` (render opt-out), `CheckboxTest.php`
(value normalization).

Validation layering — verify against the current code, then rely on it:
`TrackerLib::replace_item()` does **not** run a handler's `isValid()`; only
the services path does (`Services\Tracker\Utilities::validateItem()` →
`TrackerLib::check_field_values()`). So the fixture can store values the UI
would reject — that difference is itself behavior to pin (see the isValid
pattern in step 4 and ground rule 7).

## Session flow

### 0. Environment check

Ask the developer how they run PHPUnit here (local PHP, Docker, ddev — setups
vary; see **tiki-testing** for the test-DB setup). Then baseline:

```bash
php phpunit --filter 'Characterization\\'
```

Must be green before anything else — skipped tests are fine (earlier test
classes that haven't declared update/stored-fallback cases skip those with a
reminder; each skip is an open contract row of a previous session, not a
failure). Two operational traps to state up front:

- **Never judge by a full `php phpunit` run** — the suite has a known
  silent-exit bug partway through. Use `--filter` / `--group tracker` runs.
- `--filter 'FooTest'` substring-matches unrelated classes; scope with the
  namespace as above, or use the exact class name.

### 1. Pick a field — GATE

Open `COVERAGE.md`, show the developer what is open in the current batch, and
let them pick. Prefer checklist order; any open type is acceptable.

### 2. Where does the stored value come from? — GATE

Read `getFieldData()` and `handleSave()` of the class, then agree on the
answer **before** writing anything. Three possible answers, each changing
how the test is written:

| The stored value… | Meaning | Test approach |
|---|---|---|
| …is what was submitted (a **value-like** field) | submitted value == stored value | full characterization, as-is |
| …is generated at save time (a **generated-value** field) | the field produces its own value: a counter, a computation, an upload | characterization with closure expectations for the generated values; may need fixture design — see "First real generated-value field" below |
| …depends on an external service (an **external** field) | needs LDAP, BBB, remote service… | conformance-only (already covered); a per-type test only if the developer argues it's worth the fixture cost |

Deciding signals: does `getFieldData()` return the request value unchanged?
Does `handleSave()` compute something (check what it does when `$oldValue` is
null — that means "item being created")? Does anything read `$_FILES`,
session, or an external service?

The bold names exist only so later sections of this file can refer back —
they are ordinary words that self-explain if said aloud. With the developer,
put the question exactly as this step's title does, and describe each answer
by what the field does ("this one stores exactly what you type"), never as a
category number or level (ground rule 9).

### 3. Read the class, build the coverage contract — GATE

Walk through `getFieldData()`, `handleSave()` if present, `isValid()` if
overridden, `renderInput()` / `renderOutput()` / `renderInnerOutput()`, the
`templates/trackerinput/<type>.tpl` template — **and every helper they call
into**. The branches often live in the helper, not the field class (DateTime's
split-widget path is really `TrackerLib::build_date()`, which has its own
null-handling, Meridian/12-hour arm and per-format arms).

While reading, build the **coverage contract**: a table the session is
audited against at step 5. One row per:

- **Submission shape.** Count them from the code, not from comments, docs, or
  what the visible widget sends. Fields routinely accept more shapes than the
  form shows: a raw API value, split widget inputs, JSON/array shapes from
  newer front-ends, side-channel keys (`other_<ins>`, `<ins>_old`). If your
  test docblock is about to say "two shapes", the code must show two arms —
  recount before writing.
- **Option, per allowed value.** Every param in `getManagedTypesInfo()` with
  an options enum: each value is a row. Both branches of a binary option — a
  pin of the default branch proves nothing about the other one. Free-form
  params (a max, a step): one row for a normal value, one for out-of-range.
- **Validation and error branch.** Every place that sets `error`, throws, or
  rejects — including `isValid()`, which the fixture save path does not run
  (see "Validation layering" above). Include the *holes*: a guard like
  `is_numeric()` in front of a validator means some invalid values never
  reach it, and a heuristic like "13 digits ending in 000" has a
  neighbor-value that slips past (a real millisecond timestamp rarely ends
  in 000) — the slip-through is a row of its own.
- **Fallback.** Every "no input → use stored / use default / use now" arm —
  each twice: with an empty stored value and with a non-empty one (the
  edit-form case; `storedFallbackCaseProvider()` exists for exactly this).
- **Create vs update.** If the class has `handleSave()` or any behavior
  keyed on `$oldValue`, the update path is a mandatory row
  (`updateCaseProvider()`); otherwise one row confirming update ≡ create, or
  a waiver.
- **Standard edge inputs** and the **type-family boundaries** below.

Mark every row `case` (will be pinned) or `waive: <reason>` (unreachable in
the harness, disproportionate fixture cost, developer decision). Present the
table; the developer trims or extends it — GATE.

Then ask the developer to **predict** stored/rendered outcomes for a handful
of rows before running anything: a normal value, an empty value, an absent
key, and the two or three contract rows they feel least sure about. Write the
predictions down. Wrong predictions are where the learning is.

#### Standard edge inputs — every type, every session

| Input | Why it bites |
|---|---|
| `'0'` (string zero) | `empty('0')` is true in PHP — silently falls through "no input" arms |
| Array where a scalar is expected | the API path sends arrays; `!empty([..])` passes, string functions warn or corrupt |
| `'<i>x</i><script>alert("q")</script>'` | pins **which layer escapes**: getFieldData, CSV render and full render each answer differently |
| Multibyte value (`'héllo — wörld 🐘'`) | round-trip integrity; any length/truncation option (strlen vs mb_strlen) |
| Absent key / empty string / whitespace-only | usually three different arms, only one of them intended |
| Non-empty stored value + absent input | the edit-form fallback; an "absent input" pin on a new item only proves the empty-stored variant |

#### Type-family boundaries — worked examples, NOT a catalog

The table below is what past sessions found for the families they touched.
It is deliberately incomplete: rows exist only for families someone has
already characterized, and even those rows list what *that* type exposed.
Treat it as evidence of the kind of boundary that exists, never as the list
to satisfy — **derive your type's boundary rows from its own value domain
and its own code**, the way these were derived: ask what 24 years of real
data could hold in this value (extremes, legacy formats, values from other
locales, values written by code that no longer exists) and what the code's
guards single out.

| Family | Example rows from past sessions |
|---|---|
| date/time | epoch `0`; a negative timestamp (pre-1970 birthdates exist in real data); a 13-digit millisecond value ending in `000` **and** one not ending in `000`; every mode of the type's format option (date-only arms carried the timezone arithmetic); 12-hour/Meridian inputs |
| numeric-like | negative; `'1e5'` (`is_numeric()` says yes); comma decimal `'3,5'` (European input); leading zeros `'007'`; formatting options (decimals/separators/prepend/append) at render |
| option-list | `value=label` (equal-sign) syntax; quoted options; **an option value containing a comma** (CSV-stored multi-values shear apart); option value `'0'`; duplicated option (default-value logic); a value not in the options list — at getFieldData level and through `isValid()` |
| free text | a value longer than any max/truncation option, in multibyte; wiki/plugin syntax if the type parses content; the multilingual configuration if the type supports it (JSON storage, string→array migration arm) |

If your type's family has no row here, or its row misses boundaries you
derived, **extend this table in the same commit as the test** — one line,
what you added and why it bites. The table grows from sessions, not from
anyone's up-front analysis; a family with no row means nobody has been
there yet, not that there is nothing to find.

Rows whose family doesn't apply: skip silently. Rows that apply but won't be
pinned this session: waive in writing.

### 4. Write the test, record the actuals

Create `lib/test/Core/Tracker/Field/Characterization/<Type>Test.php`:

```php
/**
 * Characterization of Tracker_Field_Foo (type 'X').
 * <one paragraph: what the type does, its known quirks>
 * Records current behavior — see FieldCharacterizationTestCase.
 *
 * Not covered: <one line per waived contract row, with the reason>
 *
 * @group integration
 * @group tracker
 */
class FooTest extends FieldCharacterizationTestCase
{
    protected static function typeCode(): string { /* 'X' */ }
    protected static function fieldSpecs(): array { /* permName 'charFoo', type 'X'; add option-variant fields after it */ }
    public static function inputCaseProvider(): array { /* requestData with {{ins}} placeholder => expected getFieldData() subset */ }
    public static function roundTripCaseProvider(): array { /* stored => expected raw => expected getValue() */ }
    public static function renderCaseProvider(): array { /* stored => exact CSV output => renderInput markers => renderOutput markers */ }
    public static function updateCaseProvider(): array { /* initial => update => expected raw => expected getValue(); skip-with-reminder if omitted */ }
    public static function storedFallbackCaseProvider(): array { /* stored => expected getFieldData([]) subset; skip-with-reminder if omitted */ }
}
```

Optional hooks when needed: `extraPrefs()` (date formats etc. — it can also
override the harness's UTC timezone pins when timezone behavior is itself
under test), `createSupportFixtures()` (categories, second tracker…),
`rendersFullHtml()` returning `false` when `renderInput()` pulls in an editor
widget that cannot run headless (TextArea does this — pin the CSV path only
and say so in a comment). Pref gating is automatic: the base reads the type's
`prefs` list from the factory and enables them.

Contract rows the three main providers can't reach — patterns:

- **Option variants** (both branches of a binary option, a second format
  mode): declare extra fields in `fieldSpecs()` (e.g. permName `charFooBlank`
  with the other option value) and pin them in custom test methods.
  `handlerWithoutItem($permName)`, `handlerForItem($itemId, $permName)` and
  `renderHandlerForItem($itemId, $permName)` all take the variant's permName.
- **isValid()**: not run by the fixture save. Call it directly on a bound
  handler in a custom test method and pin the return value (`true` or the
  translated message string). Name the case by layer (ground rule 7).
- **Thrown exceptions**: the provider shape can't pin a throw; use a
  dedicated test method with `expectException()`.
- **Values the UI could never submit but the DB can hold** (legacy data):
  `roundTripCaseProvider()` stores through replace_item, which skips
  validation — use it to pin how render/getValue cope with dirty stored data.

Fill expectations with the developer's predictions, run, and record what
actually happens. Expected differences between prediction and reality are the
material for step 5.

### 5. Judge every recorded value — GATE

First **audit the contract**: walk the step-3 table; every row must now point
at a pinned case name or carry its waiver, and waivers must appear as "Not
covered:" lines in the test docblock. (This is the judgment audit — the
mechanical one, line coverage against the class itself, runs in step 7 and
will catch what this walk misses.) Only then go through each recorded
value with the developer. Three verdicts each:

- **Correct** — pin it.
- **Quirky but tolerable** — pin it with a case name flagging it
  (`'…(pins current behavior)'`).
- **Suspicious** — enter the subflow below. Do not pin, do not skip.

### 6. Suspicious-value subflow

This is the disciplined path from "that looks wrong" to a decision:

1. **Reproduce through the real path.** The fixture saves via a data array;
   a browser form goes through `Services_Tracker_Utilities::insertItem()` /
   `updateItem()`. Write a small probe script (not a test yet) driving the
   services path. Same wrong value there = real bug candidate. Different
   value = you learned something about save-path shapes; reassess where the
   stored value really comes from (step 2 — maybe it is generated after all).
2. **Check history.** `git log -L<start>,<end>:<file>` on the code involved.
   A recent semantic change in a drive-by hunk of an unrelated feature commit
   is the classic shape.
3. **A/B if you found a candidate commit.** Temporarily restore the old code
   in the working tree, rerun the probe, revert. Now you have proof, not
   suspicion.
4. **GATE — the developer decides:** pin the behavior as a documented quirk,
   record it for a bug report, or fix it now. A fix means test-first, as two
   commits: first the **green feature pins** for whatever the breaking change
   was built for (its behavior must not regress), then the **red regression
   pins together with the minimal fix** that turns everything green. Bisect
   stays clean and the review shows the feature was protected.

**Worked example (this really happened).** Characterizing AutoIncrement (`q`),
the value refused to round-trip — stored nothing, ever. Instead of forcing the
test green, the trace led to `TrackerLib::replace_item()`: an unrelated
ItemsList feature commit had changed `$old_value = $fil[$fieldId] ?? null` to
a derivation that never yields null, killing every handler that uses
`is_null($oldValue)` to detect item creation. AutoIncrement had silently
stored nothing for every item created in ~4.5 months, on every insert path;
GroupSelector autoassign was collateral damage. The fix landed test-first as
two commits — ItemsList transfer-mode feature pins first (green), then the
red pins plus the fix (see `AutoIncrementTest.php` and `ItemsListTest.php`,
outside the Characterization dir, and their commit messages). The lesson to
pass on: *the harness refusing to go green found a silent data-loss bug the
whole community had missed.* This is also why `updateCaseProvider()` exists:
that bug lived entirely on the create-vs-update axis, which creation-only
round trips can never see.

### 7. Verify

```bash
php phpunit --filter '<Type>Test' lib/test/Core/Tracker/Field/Characterization/<Type>Test.php
php phpunit --group tracker --exclude-group api-integration-test   # run TWICE (order independence)
TZ=Pacific/Kiritimati php phpunit --filter '<Type>Test' lib/test/Core/Tracker/Field/Characterization/<Type>Test.php
```

The third run catches host-timezone leakage (ground rule 8): the harness pins
prefs and PHP to UTC, but an expectation computed with `date()` at provider
build time, or a pin recorded from a non-UTC machine, still slips through.

#### Mechanical coverage audit

The step-5 contract audit is judgment; this one is measurement, and it is
deliberately independent of anyone's opinion of where the gaps should be —
it compares the test against the code itself:

```bash
php -d pcov.directory=lib/core/Tracker/Field lib/test/phpunit.php \
    --filter 'Characterization.*<Type>Test' --coverage-clover /tmp/cov.xml
php lib/test/tools/uncovered-lines.php /tmp/cov.xml lib/core/Tracker/Field/<Type>.php
```

Requires the pcov extension (`apt-get install php8.4-pcov`, or Xdebug in
coverage mode); takes ~30s because the whole run is instrumented.

Rule: **every line range the report prints must be explained** — either by a
"Not covered:" waiver already in the test docblock, or by adding one now
(with the developer's sign-off, it's a step-5 verdict), or by writing the
missing case. An uncovered range nobody can account for means the contract
of step 3 was incomplete: go back, add the row, cover or waive it.

Ranges that are normally waivable in one line: the `getManagedTypesInfo()`
declaration array (data, not behavior); interface plumbing already pinned by
`RegistryConformanceTest`; branches of capability methods
(tabular/filter/facets) beyond the base smoke test — if the developer agrees
the fixture cost is disproportionate. Everything else is presumed to be
behavior somebody's data depends on.

If the type's `getFieldData()`/`handleSave()` logic lives partly in a shared
helper (`TrackerLib::build_date()` style), audit the helper's lines too:
widen `pcov.directory` (e.g. `-d pcov.directory=lib`) and run the report a
second time against the helper's file.

No new warnings or notices beyond the known pre-existing ones (currently: two
`checkbox.tpl` "Undefined array key defaultvalue" notices). PHPUnit surfaces
warnings as test issues — a new warning is a finding, treat it as step 5.
Skipped update/stored-fallback tests from **older** test classes are known
open rows; your own class must not skip — declare the providers or waive in
the docblock.

### 8. Close: checklist, commit, merge request

- Update `COVERAGE.md`: check the box, move the entry to "Characterized" with
  the test filename.
- Optionally regenerate the matrix to review the type's capability claims:
  `php lib/test/tools/tracker-capability-matrix.php`.
- One commit, test + checklist update together:
  `[ENH] tests: characterize <Type> tracker field type` — body: what the type
  does, which quirks were pinned, what was waived and why, anything sent to
  the suspicious-value subflow and how it was resolved.
- One merge request. The developer takes it to the community — discussion of
  pinned quirks belongs to people, in the MR and wherever the team
  synchronizes; that part is out of your hands by design.

## First real generated-value field

There is no request-shaped fixture yet (`createItemFromRequest()` does not
exist). Note that `TrackerFixtureFactory::createItem()` already includes all
definition fields in the save, so generation-style fields do run their
`handleSave()` — and `updateItem()` drives the non-null-`$oldValue` arm —
closure expectations may be all you need. If a type genuinely needs
request/upload state (`$_FILES` for Files/File, computed-from-siblings for
Computed): STOP, design the fixture extension with the developer, and
propose it to the community before building — fixture API is shared
infrastructure, not a per-session decision.

## Harness gotchas

- `AbstractItemField::getValue($default = '')` returns `''`, never null — do
  not use "getValue returned null" in reasoning about stored state.
- Cross-test Smarty state bleeds in long runs: pin the exact **CSV** inner
  output, use `assertStringContainsString` markers for full HTML, never exact
  full-HTML snapshots.
- `renderOutput()` needs a context in some types (`['list_mode' => '']`) —
  the base passes it; remember it in probe scripts.
- Non-deterministic values (DateTime "now" default, AutoIncrement counter):
  closure expectations, not fixed strings.
- The base pins `display_timezone`/`server_timezone` prefs and PHP's default
  timezone to UTC per class; `extraPrefs()` overrides the prefs when timezone
  behavior is itself the thing under characterization.
- PHPUnit parses annotations anywhere in a docblock — never write a data
  provider annotation as prose inside a test-method docblock.

## Cross-cutting reflexes

- Test-DB setup, run commands, warnings-as-errors → **tiki-testing**.
- Field pref gating and enabling `trackerfield_*` prefs → **tiki-preferences**.
- Reading/writing tracker data in probes → **tiki-database**.

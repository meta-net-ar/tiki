# Tracker field-type characterization coverage

Tracks which of the 53 tracker field classes have a per-type characterization
test and which do not yet. The process for working this list — one field type
per session, one commit, one merge request, developer and agent together — is
the `tiki-tracker-field-coverage` skill (the folder above this one). This
`docs/` folder is the provisional home for the workflow's documentation; the
intended final home is dev.tiki.org. The generated capability view is
`php lib/test/tools/tracker-capability-matrix.php`.

The tests themselves live in `lib/test/Core/Tracker/Field/Characterization/`.
All 53 classes are pinned by `RegistryConformanceTest.php` there (registered
type-to-class mapping and required type metadata) regardless of the checkboxes
below. `[x]` = characterized through the skill process; `[/]` = a
per-type test exists but it predates the skill — a skill pass is still
missing; `[ ]` = no per-type test yet.

Status: **0 characterized, 2 harness examples pending a skill pass, 8 conformance-only by design, 43 to go.**

## Harness examples (characterization still pending)

The `[/]` entries below are starter examples shipped with the harness. A
per-type test exists for each, but neither has been through the skill process
(coverage contract, standard edge inputs, dirty-store render probes,
mechanical audit). A skill pass is still missing and each test is expected to
grow cases when that happens.

- [/] Text — `t` — `TextTest.php` (harness example — skill pass pending)
- [/] Dropdown — `d`, `D`, `R`, `M` — `DropdownTest.php` (harness example — skill pass pending)

## Covered by targeted tests, characterization still pending

These have behavior tests in `lib/test/Core/Tracker/Field/`; the box stays
open until a characterization test exists in the Characterization directory
too.

- [ ] ItemLink — `r` — legacy `ItemLinkTest.php` (link semantics, mirror-field indexing); value may be generated at save — check handleSave()
- [ ] Math — `math` — legacy `MathTest.php` (calculation; render-bleed workaround patterns)
- [ ] Url — `L` — legacy `UrlTest.php` (URL validation)

## Planned batch: choice/date

- [ ] DateTime — `f` — non-deterministic now-default: closure expectations
- [ ] GroupSelector — `g` — generated-value candidate: autoassign gates on `is_null($oldValue)`
- [ ] Category — `e` — support fixture: category via categlib, cleanup removes it

## Planned batch: relational/computed

ItemLink and Math are listed above because they already have targeted tests;
they remain part of this planned batch.

- [ ] ItemsList — `l` — support fixture: second tracker + linked items
- [ ] Relation — `REL`
- [ ] Files — `FG` — generated-value candidate: `$_FILES` upload context; file-gallery fixture
- [ ] Computed — `C` — generated-value candidate: derived from other fields
- [ ] AutoIncrement — `q` — generated value (the field computes it at save); use closure expectations

## Long tail

- [ ] Articles — `articles`
- [ ] BarCode — `barcode`
- [ ] CalendarItem — `CAL`
- [ ] Checkbox — `c`
- [ ] CountrySelector — `y` — Enumerable
- [ ] Currency — `b`
- [ ] Duration — `DUR`
- [ ] DynamicList — `w` — Enumerable
- [ ] Email — `m`
- [ ] EmailFolder — `EF`
- [ ] File (Attachment) — `A` — generated-value candidate: `$_FILES` upload context
- [ ] Freetags — `F`
- [ ] GeographicFeature — `GF` — round-trip coordinates only, exclude render/geocode (decide finally during implementation)
- [ ] Heading — `h`
- [ ] Icon — `icon`
- [ ] Image — `i`
- [ ] InGroup — `N`
- [ ] Ip — `I`
- [ ] JsCalendar — `j`
- [ ] Language — `LANG`
- [ ] Location — `G` — round-trip coordinates only, exclude render/geocode (decide finally during implementation)
- [ ] PageSelector — `k`
- [ ] Rating — `STARS`, `*`, `s`
- [ ] Slider — `SLIDER`
- [ ] StaticText — `S`
- [ ] Numeric — `n`
- [ ] TextArea — `a`
- [ ] UserGroups — `usergroups`
- [ ] UserPreference — `p`
- [ ] UserSelector — `u` — only AutoSyncable type in the registry
- [ ] UserSubscription — `U`
- [ ] Wiki — `wiki`

## Conformance-only by design (external-service types — no characterization planned)

External dependency makes a per-type behavior test disproportionate; each is
pinned by `RegistryConformanceTest.php` with the exclusion reason as a
constant. Re-open only if a targeted test becomes warranted.

- [x] Action — `x` — posts the item form to an external URL
- [x] BigBlueButton — `bbb` — requires a BigBlueButton server API
- [x] Kaltura — `kaltura` — requires a Kaltura video service
- [x] Ldap — `P` — requires an LDAP server
- [x] PeerTube — `peertube` — requires a PeerTube instance
- [x] ShowTikiOrg — `STO` — requires the show.tiki.org remote service (experimental)
- [x] TikiManager — `TM` — requires a Tiki Manager instance (remote shell)
- [x] WebService — `W` — calls an arbitrary external webservice

## Updating this file

Check the box, move the entry to "Characterized" with the test filename, and
adjust the status line. If the session produced `known-defect` tests (failing
tests asserting intended behavior, excluded from default runs — see the
suspicious-value subflow in the skill), list each on the type's entry:
test method name and one line on what is wrong. The update ships in the same
commit as the test.

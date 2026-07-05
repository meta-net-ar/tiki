# Tracker field-type characterization coverage

Tracks which of the 53 tracker field classes have a per-type characterization
test and which do not yet. The process for working this list — one field type
per session, one commit, one merge request, developer and agent together — is
the `tiki-tracker-field-coverage` skill (`.agents/skills/`). The generated
capability view is `php lib/test/tools/tracker-capability-matrix.php`.

All 53 classes are pinned by `RegistryConformanceTest.php` in this directory
(registry, capability interfaces, `getFieldData()` signature) regardless of
the checkboxes below. A checked box = a per-type characterization test in
this directory.

Status: **5 characterized, 8 conformance-only by design, 40 to go.**

## Characterized

- [x] Text — `t` — `TextTest.php`
- [x] Dropdown — `d`, `D`, `R`, `M` — `DropdownTest.php`
- [x] TextArea — `a` — `TextAreaTest.php` (tier 1; full-HTML render opted out, editor widget can't run headless)
- [x] Numeric — `n` — `NumericTest.php`
- [x] Checkbox — `c` — `CheckboxTest.php`

## Covered by targeted tests, characterization still pending

These have behavior tests in `lib/test/Core/Tracker/Field/`; the box stays
open until a characterization test exists in this directory too.

- [ ] AutoIncrement — `q` — regression test `AutoIncrementTest.php` (services + data-array insert paths, itemId mode). Tier 2: generated value, use closure expectations. Backstory: the characterization attempt found a silent regression in `replace_item()` — see the worked example in the skill.
- [ ] ItemsList — `l` — behavior test `ItemsListTest.php` (transfer-save synchronization, two-tracker fixture)
- [ ] ItemLink — `r` — legacy `ItemLinkTest.php` (link semantics, mirror-field indexing); possibly tier 2
- [ ] Math — `math` — legacy `MathTest.php` (calculation; render-bleed workaround patterns)
- [ ] Url — `L` — legacy `UrlTest.php` (URL validation)

## Planned batch: choice/date

- [ ] DateTime — `f` — non-deterministic now-default: closure expectations
- [ ] UserSelector — `u` — only AutoSyncable type in the registry
- [ ] GroupSelector — `g` — autoassign gates on `is_null($oldValue)`; worth pinning explicitly (it was dead during the `replace_item()` regression)
- [ ] Category — `e` — support fixture: category via categlib, cleanup removes it

## Planned batch: relational/computed

- [ ] ItemLink — `r` — support fixture: second tracker + item (see above)
- [ ] ItemsList — `l` — two-tracker fixture exists in `ItemsListTest.php` (see above)
- [ ] Relation — `REL`
- [ ] Files — `FG` — tier 2 candidate: `$_FILES` upload context; file-gallery fixture
- [ ] Math — `math` (see above)
- [ ] Computed — `C` — tier 2 candidate: derived from other fields

## Long tail

- [ ] Articles — `articles`
- [ ] BarCode — `barcode`
- [ ] CalendarItem — `CAL`
- [ ] CountrySelector — `y` — Enumerable
- [ ] Currency — `b`
- [ ] Duration — `DUR`
- [ ] DynamicList — `w` — Enumerable
- [ ] Email — `m`
- [ ] EmailFolder — `EF`
- [ ] File (Attachment) — `A` — tier 2 candidate: `$_FILES` upload context
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
- [ ] UserGroups — `usergroups`
- [ ] UserPreference — `p`
- [ ] UserSubscription — `U`
- [ ] Wiki — `wiki`

## Conformance-only by design (tier 3 — no characterization planned)

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
adjust the status line. The update ships in the same commit as the test.

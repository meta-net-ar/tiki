---
name: tiki-database
description: Use when querying, inserting, updating, or deleting rows in the Tiki database from PHP — choosing between the array-built Table layer and raw PDO, building conditions (like/in/between/greaterThan), or avoiding the accidental-bulk delete/update trap. MySQL/MariaDB only.
---

# Tiki database access

Two ways to hit the DB from PHP. Prefer the **Table layer** (builds SQL from
arrays, injection-safe). Drop to **raw PDO** only for joins/subqueries the Table
layer can't express. Paths below are relative to the Tiki source-tree root.

PDO is the default abstraction since Tiki 4 (ADOdb before). **MySQL/MariaDB only** —
write MySQL-specific SQL freely (backticks, full-text, date functions); portability
was dropped ~2010.

## Table layer — the default

Get a table handle off any `TikiDb`/`TikiDb_Bridge` (i.e. `$tikilib`, `$this`
inside a `*lib`, or `TikiDb::get()`). Factory is `lib/core/TikiDb.php:408`;
the class is `TikiDb_Table` in `lib/core/TikiDb/Table.php`.

```php
$pages = $tikilib->table('tiki_pages');
// inside a *Lib class extending the DB bridge:  $pages = $this->table('tiki_pages');
// standalone:                                    $pages = TikiDb::get()->table('tiki_pages');
```

### Fetch

`fetchAll($fields, $conditions, $max, $offset, $order)` returns an array of assoc
rows. Real: `lib/tikilib.php:5167`, `lib/tikilib.php:4414`.

```php
$rows = $tikilib->table('tiki_pages')->fetchAll(
    ['pageName', 'description', 'hits'],   // fields ([] = all columns)
    ['lang' => 'en'],                       // conditions (AND-ed)
    $maxRecords, $offset,
    ['pageName' => 'ASC']                   // ORDER BY
);
```

Row/scalar helpers (`lib/core/TikiDb/Table.php:157-273`):

| Method | Returns |
|---|---|
| `fetchOne($field, $conditions)` | single scalar (or `false`) |
| `fetchRow($fields, $conditions)` | one assoc row |
| `fetchFullRow($conditions)` | one row, all columns |
| `fetchColumn($field, $conditions)` | flat array of one column |
| `fetchMap($keyField, $valField, $conditions)` | `key => value` array |
| `fetchCount($conditions)` | int row count |
| `fetchBool($conditions)` | bool — does a match exist? |

### Insert / update / delete

```php
$id = $pages->insert(['pageName' => 'HelloWorld', 'data' => 'Content']); // returns new auto-inc id

$pages->update(
    ['hits' => $pages->increment(1)],   // hits = hits + 1
    ['page_id' => 42]
);

$pages->delete(['page_id' => 42]);
```

Also: `insertOrUpdate($data, $keys)` → `INSERT ... ON DUPLICATE KEY UPDATE`
(`lib/core/TikiDb/Table.php:70`).

**Gotcha — `delete()` and `update()` silently append `LIMIT 1`**
(`Table.php:104`, `Table.php:118`). They touch at most one row. For bulk, use
`deleteMultiple($conditions)` / `updateMultiple($values, $conditions)`
(`Table.php:127`, `Table.php:149`). Real bulk delete: `lib/schedulerslib.php:316`;
bulk update: `lib/webservicelib.php:134`.

```php
$sessions->deleteMultiple([
    'expiry'     => $sessions->lesserThan(time()),
    'session_id' => session_id(),
]);
```

### Conditions & expressions

Bare scalars mean `= value`. Wrap in a method for anything else — all defined in
`lib/core/TikiDb/Table.php:363-457`:

| Expression | SQL |
|---|---|
| `like($v)` / `unlike($v)` | `LIKE ?` / `NOT LIKE ?` |
| `contains($v)` | `LIKE '%v%'` |
| `in($arr)` / `notIn($arr)` | `IN (...)` / `NOT IN (...)` |
| `between([$lo,$hi])` | `BETWEEN ? AND ?` |
| `greaterThan($v)` / `lesserThan($v)` | `> ?` / `< ?` |
| `not($v)` | `<> ?` (null-safe when `$v` empty) |
| `exactly($v)` | `BINARY $$ = ?` (case/binary-exact) |

Field/aggregate helpers for the `$fields` arg: `all()` (=`*`), `count()`,
`sum($f)`, `max($f)`, `min($f)`. Update-value helpers: `increment($n)`,
`decrement($n)`.

```php
// conditional IN — build the array, only add the key when relevant
$files = $this->table('tiki_files');
$conditions = [];
if (! empty($galleryIds)) {
    $conditions['galleryId'] = $files->in($galleryIds);
}
$total = $files->fetchOne($files->sum('filesize'), $conditions);
```

For anything the shorthands can't express, `expr($sqlFragment, $bindArgs)` — `$$`
is replaced by the escaped field name in conditions (`Table.php:319`). Example:
`['id' => $t->expr('$$ IS NOT NULL AND $$ > 0')]` (`lib/crypt/encryptionlib.php:58`).

## Raw PDO — legacy / complex joins

When the Table layer can't build it (joins, correlated subqueries, `FROM_UNIXTIME`,
etc.), write SQL by hand. **Backtick every identifier; `?` placeholders only —
never interpolate values.**

`fetchAll($query, $bindvars, $max, $offset)` returns rows directly
(`lib/tikilib.php:719`):

```php
$query  = "SELECT `pageName`, `hits` FROM `tiki_pages` WHERE `lang` = ? ORDER BY `hits` DESC";
$rows   = $this->fetchAll($query, ['en'], $maxRecords, $offset);
foreach ($rows as $row) { /* ... */ }
```

`query($query, $bindvars)` returns a result object you step with `->fetchRow()`
(`lib/tikilib.php:1872-1886`):

```php
$result = $this->query("SELECT * FROM `tiki_pages` WHERE `pageName` = ?", [$page]);
if ($result && $row = $result->fetchRow()) { /* ... */ }
```

**Gotcha — string-building a query with a user value (`"... WHERE x = '$v'"`) is
an injection hole.** Every dynamic value goes through a `?` bind. See
`tiki-input-filtering` (filter at the boundary) and `tiki-security`.

## Cross-cutting reflexes

- **Complex multi-filter listings → don't hand-roll SQL.** Use Unified Search
  instead (separate concern — the Unified Search / Unified Index layer).
- **DDL (CREATE/ALTER TABLE, new columns) never goes through this layer** — it's a
  schema change: patch `installer/schema/` *and* `db/tiki.sql`. See
  `tiki-schema-upgrade`.
- `tiki_`-prefixed table names, lowercase, ≤26 chars; backtick all identifiers.

## Verify against source

```bash
grep -rn "->table('tiki_" lib | head        # real Table-layer usages
grep -rn "deleteMultiple(\|updateMultiple(" lib | head
sed -n '360,460p' lib/core/TikiDb/Table.php  # condition/expr definitions
```

Good models: `lib/schedulerslib.php` (Table CRUD throughout),
`lib/webservicelib.php:132` (`TikiDb::get()->table` + `updateMultiple`),
`lib/tikilib.php:1872` (raw `query()` + `fetchRow()` loop).

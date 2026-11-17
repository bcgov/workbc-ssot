# SSoT ingestion

The pipeline converts source workbooks, CSV files, and directory exports into
dataset CSVs, then loads them into PostgreSQL using pgloader.

**Adding a dataset does not require editing the driver.** Register its source in
[`load/sources.csv`](./load/sources.csv), describe its conversion in
[`conversion.json`](./conversion.json), and provide its pgloader definition.

## Running ingestion

From the repository root, with the Compose services running:

```bash
# Inspect a workbook's datasets and preparation dependencies without writing.
docker-compose exec migrator php src/ssot_ingest.php \
  --file "Workbook.xlsx" --dry-run

# Convert and load all datasets registered for that workbook.
docker-compose exec migrator php src/ssot_ingest.php --file "Workbook.xlsx"

# Select several workbooks.
docker-compose exec migrator php src/ssot_ingest.php \
  --file "First.xlsx" --file "Second.xlsx"
```

`--file` accepts a workbook basename, with or without the XLSX extension.
Extension matching is case-insensitive; the rest of the filename is exact.
Omit `--file` for a full rebuild, including non-XLSX datasets.

For interactive use, keep Compose's default TTY allocation: **do not add `-T`**.
This preserves terminal newline formatting and delivers Ctrl-C as SIGINT.
Some Compose versions put the host terminal into raw mode even with `-T`, then
forward Ctrl-C as unread stdin rather than a signal and display stair-stepped
output. If an earlier run left your terminal in that state, run `stty sane`.
Calling PHP directly needs no additional `bash -c` wrapper.

On SIGINT or SIGTERM, the driver stops the active child command, removes its
temporary files, and exits with status 130 or 143 respectively. A child that
does not stop after SIGTERM is killed after two seconds. Cancellation does not
roll back datasets that have already loaded.

Use `-T` for noninteractive automation, such as the GitHub workflow below.
Interrupting a local non-TTY Docker exec client is not a reliable way to signal
the process inside its container; prefer the interactive invocation when you
need Ctrl-C.

For a clean database rebuild:

```bash
docker-compose exec -T postgres psql --username workbc ssot < ssot-reset.sql
docker-compose exec -T postgres psql --username workbc ssot < postgres-init/init.sql
docker-compose exec migrator php src/ssot_ingest.php
```

These commands change the target database. Use an isolated environment before
publishing an update. The migrator's `PGHOST`, `PGUSER`, and `PGPASSWORD` configure
connections. pgloader definitions specify the database themselves; setting
`PGDATABASE` alone does not override their `postgresql:///ssot` target.

PHP commands can also run directly where PHP CLI with PCNTL, Gnumeric's `ssconvert`,
pgloader, and `psql` are installed. The driver changes to this directory
regardless of the caller's working directory. All recipe paths are relative to
this directory, not the shell's directory.

## Architecture

### Three separate responsibilities

| File | Responsibility |
|---|---|
| [`load/sources.csv`](./load/sources.csv) | Authoritative dataset inventory and public provenance: filename, sheet, range, label, author, date, and period. |
| [`conversion.json`](./conversion.json) | Private execution recipes keyed by the CSV's `endpoint`. |
| [`load/*.load`](./load/) | Database columns, types, constraints, comments, and loading SQL. |

[`src/ssot_ingest.php`](./src/ssot_ingest.php) is a generic driver. It contains no
endpoint-name switches or dataset-specific transformation lists.
Domain-specific work belongs in PHP readers/transforms selected by recipes.
A JSON recipe without a corresponding inventory record is never executed.

### Execution phases

1. **Plan and validate.** Read the CSV using proper CSV parsing, look up each
   dataset's recipe, apply workbook selection, resolve preparation dependencies,
   expand placeholders, and validate selected inputs, helpers, loaders, output
   paths, ranges, and partitions. Datapoint-specific provenance rows are skipped.
   An endpoint whose entire record is provenance-only must have a recipe with
   `"source": {"type": "metadata"}`.
2. **Prepare CSVs.** Visit dependencies before their consumers. Read or convert
   each source, execute transformation steps in array order, and copy the result
   to its declared output. Shared raw inputs are never modified or moved.
3. **Load selected datasets.** Only after every preparation succeeds, sort
   selected records by `load.order`, retaining CSV order for ties, and invoke
   pgloader with `--on-error-stop` and `SOURCE` set to the absolute output path.
4. **Refresh provenance.** Load the unchanged CSV format through
   [`load/sources.load`](./load/sources.load), after all selected datasets succeed.

Temporary conversion files are private to each invocation and removed on
success or failure. Generated output CSVs remain for inspection and publication.
A preparation failure prevents database loading, but may leave already-prepared
output CSVs. Database loads are **not one transaction**: a failure after loading
starts may leave partially updated tables. Do not publish a failed run.
Do not run two ingestion processes against the same migration directory or
database concurrently.

### Shared workbooks and sheets

The grouping comes from the CSV, not from duplicated JSON workbook definitions:

```text
Workbook.xlsx                         one ssconvert invocation
  Shared sheet.csv                    one immutable raw sheet CSV
    dataset A: extract range A -> transform -> load/A.csv
    dataset B: extract range B -> transform -> load/B.csv
  Another sheet.csv
    dataset C: extract range C -> transform -> load/C.csv
```

Workbooks are cached by resolved path for the duration of a run. `ssconvert`
exports every sheet once; recipes referencing the same workbook and sheet read
the same raw CSV. Each transformation writes a separate temporary file.
Changing a filename, sheet, or range requires changing only source metadata.

### Preparation dependencies versus loading order

Some transformations need another generated CSV. For example, industry-key
generation reads `load/industries.csv`. Such a recipe declares:

```json
"prepare_requires": ["industries"]
```

The prerequisite must have exactly one ingestible record in the inventory.
Dependencies are resolved recursively; cycles and missing/ambiguous records fail
before conversion. A prerequisite shared by several consumers is prepared once.

**Preparation is not database loading.** During a filtered report import,
industries can be prepared without replacing the industries database table.
During a full rebuild it is also selected for loading; its lower `load.order`
places it before tables that depend on it. Use both declarations when both kinds
of dependency exist.

## Adding a new dataset: worked example

This fictitious dataset, `example_codes`, illustrates the same operations
exercised by the isolated pipeline tests.

### 1. Add the source workbook

Place `Example_Statistics.xlsx` in [`data/`](./data/). Suppose its `Summary` sheet
contains:

| Row | A: code | B: value |
|---|---|---|
| 1 | code | value |
| 2 | #7 | NA |
| 3 | #8 | 12 |

We want identifiers `007` and `008`, and SQL NULL for the unavailable value.

### 2. Register its provenance

Add this record to [`load/sources.csv`](./load/sources.csv), keeping its existing
header and column order:

```csv
"Example Statistics","example_codes",,"Example_Statistics","Summary","A2-B3","Example Office","2026/11/16 08:00","2026/11/01 08:00"
```

- `endpoint` identifies the dataset and its recipe.
- XLSX filenames are normally stored without the extension.
- `sheet` is the exact worksheet title.
- `range` uses inclusive spreadsheet coordinates. Both `A2-B3` and `A2:B3`
  are accepted. Use `;` for disjoint rectangles, e.g. `A2-B3;A6-B8`.
- Empty `datapoint` means dataset-level provenance. Nonempty values describe
  individual datapoints and do not create ingestion jobs.
- `date` records the update/delivery date; `period` records validity.

### 3. Add its recipe

Add this entry inside `conversion.json`'s existing `datasets` object:

```json
"example_codes": {
  "source": {"type": "xlsx"},
  "output": "load/example_codes.csv",
  "steps": [
    ["csv_extract.php", "--range", "{range}"],
    ["csv_trimpad.php", "--column", "1:L:0:0:#"],
    ["csv_trimpad.php", "--column", "1:L:3:0"],
    ["csv_null.php", "--loader", "{loader}"]
  ],
  "load": {
    "file": "load/example_codes.load",
    "order": 100,
    "mode": "snapshot"
  }
}
```

This extracts the CSV-declared range, strips leading hashes from column 1,
pads that column to three characters, then normalizes unavailable values in
columns designated by the loader. Step order matters: trimming before padding
produces `007`, not `0#7`.

The expected generated CSV is:

```csv
007,__SSOT_NULL__
008,12
```

### 4. Add its loader

Create `load/example_codes.load`:

```sql
load csv
    from '{{ SOURCE }}'
    (
        code,
        value [null if '__SSOT_NULL__']
    )
    into postgresql:///ssot
    target table example_codes
    with
        fields optionally enclosed by '"',
        fields terminated by ',',
        drop indexes
    before load do
    $$ drop table if exists example_codes; $$,
    $$ create table example_codes
    (
        code text primary key,
        value numeric
    ); $$
    after load do
    $$ comment on table example_codes is 'Example Statistics'; $$,
    $$ comment on column example_codes.code is 'Example identifier'; $$,
    $$ comment on column example_codes.value is 'Example value'; $$
;
```

Loader input columns must match the final CSV's order and width. Snapshot
recipes delegate table replacement to their loader. Prefer `{{ SOURCE }}` for
new loaders so the recipe's output determines the input file.

Keep PostgreSQL identifiers within its 63-byte limit. Use stable column names
such as `current` or `next10y`, not year-specific names. Preserve source header
descriptions in column comments, with changing years in braces, e.g. `{2026}`,
where clients expect to parse them.

To check a loader definition without loading data, run inside the migrator
container, supplying any template variables:

```bash
SOURCE="$PWD/load/example_codes.csv" pgloader --dry-run --on-error-stop load/example_codes.load
```

`--dry-run` parses the loader definition and checks database connections, so it
is not an offline syntax-only check. Embedded SQL blocks still need testing
against an isolated database.

### 5. Validate and ingest

First run the tests from the repository root:

```bash
php migration/tests/ingestion_test.php
php migration/tests/conversion_test.php
php migration/tests/source_metadata_test.php
```

Then inspect the plan and run against an isolated database:

```bash
docker-compose exec migrator php src/ssot_ingest.php \
  --file "Example_Statistics.xlsx" --dry-run
docker-compose exec migrator php src/ssot_ingest.php \
  --file "Example_Statistics.xlsx"
docker-compose exec -T postgres psql --username workbc ssot \
  -c 'SELECT * FROM example_codes ORDER BY code'
```

Check exact identifiers, SQL NULLs, row counts, source metadata, repeatability,
and preservation of unrelated tables. Extend the fixture tests for any new
transformation behaviour. A genuinely new transformation requires a helper and
focused tests, not a driver endpoint branch.

### 6. Multiple datasets from the same workbook

Add another CSV record with a new endpoint, the same filename, and its own sheet
and range. Add that endpoint's recipe and loader. Nothing else is needed:
workbook conversion and raw-sheet sharing happen automatically.

For later updates to an existing dataset, update its CSV metadata and workbook;
change its recipe only if the transformation or loading rules change.

## Recipe reference

The top-level object contains `"version": 1` and a `datasets` object keyed by
endpoint. Each ingestible recipe has:

| Property | Meaning |
|---|---|
| `source` | Source type and, for non-XLSX inputs, optional reader/input settings. |
| `prepare_requires` | Optional array of dataset endpoints whose CSVs must be prepared first. |
| `output` | CSV destination under `load/`; its parent directory must already exist. |
| `steps` | Ordered array of PHP argument arrays; `[]` means copy the reader/source output unchanged. |
| `load.file` | Existing pgloader definition under `load/`. |
| `load.order` | Integer database loading order, lowest first. |
| `load.mode` | `snapshot` or `partition`. |
| `load.table` | Required for partition mode: the target table name. |
| `load.partition` | Required for partition mode: column-to-value mapping. |

Unknown fields are rejected, so recipe typos cannot silently change behaviour.
Helper names must identify existing PHP files directly inside `src/`. Arguments
are strings passed directly to PHP, never executed by a shell. There are no
implicit extraction, padding, hash removal, or NULL-normalization steps.

### Placeholders

| Placeholder | Value |
|---|---|
| `{input}` | Resolved source workbook/file/directory path. |
| `{range}` | The CSV record's extraction range. |
| `{loader}` | The recipe's pgloader definition path. |
| `{year}` | Four-digit year from the CSV's `period`. |
| `{month}` | Month without a leading zero. |
| `{month02}` | Two-digit month. |

Placeholders work in step/reader arguments, output paths, and partition values.
Using `{range}` requires a nonempty range. Period placeholders require a valid
`Y/m/d H:i` period. Unknown or unavailable placeholders fail during planning.

### Non-XLSX sources

Read a CSV directly, using `data/` plus the CSV manifest's `filename`:

```json
"source": {"type": "csv"}
```

Optionally supply a reader. CSV readers receive the source on stdin and may
also use its path as an argument:

```json
"source": {
  "type": "csv",
  "reader": ["titles.php", "{input}"]
}
```

For directory exports, specify the concrete directory and reader:

```json
"source": {
  "type": "directory",
  "input": "data/EDM/",
  "reader": ["industries.php", "{input}"]
}
```

`source.input` can also override a CSV input path. It must remain under `data/`.
This accommodates provenance filenames such as `EDM/*` without executing shell
globs. XLSX recipes cannot override their CSV-supplied filename or sheet.

### Partitioned datasets

Monthly datasets use the same machinery, with their specialized parser selected
by their recipe:

```json
"monthly_labour_market_updates": {
  "source": {"type": "xlsx"},
  "output": "load/updates/monthly_labour_market_updates_{year}_{month02}.csv",
  "steps": [
    ["csv_empty.php"],
    ["monthly_labour_market_update.php", "{year}", "{month}"],
    ["csv_null.php", "--loader", "{loader}", "--pad-missing"]
  ],
  "load": {
    "file": "load/updates/monthly_labour_market_updates.load",
    "order": 100,
    "mode": "partition",
    "table": "monthly_labour_market_updates",
    "partition": {"year": "{year}", "month": "{month}"}
  }
}
```

Filtered runs delete only matching partition rows immediately before loading
their replacement. Full runs drop each partitioned target once, before its
first load. Consequently a partition loader must create the table if absent
and must not drop/truncate it on every invocation. Partition values are quoted
as SQL literals; table and column names are validated identifiers.

Each period remains a separate CSV inventory record, all sharing this recipe.
Conflicting outputs or duplicate target partitions fail before preparation.

## Transformation helpers

| Helper | Purpose |
|---|---|
| [`csv_extract.php`](./src/csv_extract.php) | Extract inclusive spreadsheet rectangles or numeric row ranges; repeated `--range` and semicolon-separated ranges are supported. |
| [`csv_empty.php`](./src/csv_empty.php) | Remove empty rows. |
| [`csv_refill.php`](./src/csv_refill.php) | Fill designated empty cells from previous rows. |
| [`csv_trimpad.php`](./src/csv_trimpad.php) | Trim selected characters and pad selected columns. |
| [`csv_colkey.php`](./src/csv_colkey.php) | Insert keys derived from labels, industries, or regions. |
| [`csv_null.php`](./src/csv_null.php) | Normalize unavailable values in loader-designated nullable fields. |

Helpers document their CLI arguments in their source. Extraction supports
`--column-offset` where Gnumeric omits an empty leading spreadsheet column.
Declare offsets explicitly in the affected recipe.

For NULL normalization, nullable loader input fields declare
`[null if '__SSOT_NULL__']`. The marker is reserved and rejected if it already
occurs in such a source field. Case-insensitive `NA`, `N/A`, `N.A.`, literal `-`,
and space-only values normalize to NULL only in designated columns.
`--pad-missing` pads short historical records to the loader's column count.
Empty identifier fields remain empty and load as SQL NULL.

## Automated updates and publication

Drupal uploads only the workbook, then dispatches `ssot_update` with its Git blob
SHA, period/date, notes, and `metadata: {label, author, datasets}`. Recipes remain
repository-owned; the client neither sends nor changes `conversion.json`.

The workflow restores the current database snapshot and runs:

```bash
docker-compose exec -T migrator php src/register_sources.php \
  load/sources.csv data < "$GITHUB_EVENT_PATH"
docker-compose exec -T migrator php src/ssot_ingest.php --file "$SOURCE_FILENAME"
```

[`register_sources.php`](./src/register_sources.php) verifies the workbook SHA
and atomically updates the manifest. Each dispatched dataset identifies its
endpoint, sheet, range, and matching rule: `endpoint` for a snapshot or `period`
for a monthly update. Registration preserves unrelated periods and
datapoint-specific provenance. Ingestion rejects a dataset without a recipe.

After ingestion, reapply grants and export from outside the container:

```bash
docker-compose exec -T postgres psql --username workbc ssot < ssot-grants.sql \
&& docker-compose exec -T postgres pg_dump --clean --username workbc ssot | gzip > ssot-full.sql.gz \
&& gunzip -k -c ssot-full.sql.gz > ssot-full.sql
```

The workflow commits metadata, generated CSVs, and snapshots together only
after ingestion/export succeed. A non-forced push rejects concurrent branch
updates. Failed or stale runs require an explicit retry; dispatch acceptance is
not evidence that ingestion succeeded.

Deploy backend recipes, helpers, loaders, and workflow changes before enabling
uploads for a new dataset. A workbook can exist before its metadata is published;
avoid manual ingestion during that pending interval. Provenance-only edits can
be loaded separately with `pgloader --on-error-stop load/sources.load`.

## Tests and directory layout

Run the three PHP test commands from the worked example. They require PHP 8.1+
with PCNTL/POSIX (provided by the migrator image), and no running database:

- [`tests/conversion_test.php`](./tests/conversion_test.php) creates a disposable
  inventory and recipes for fictitious datasets. It runs the actual driver,
  readers, and transformations with process-recording stand-ins for `ssconvert`,
  pgloader, and `psql`. Tests cover shared sheets/workbooks, transformation order,
  non-XLSX inputs, dependencies, loading order, full/filtered partition behaviour,
  repeatability, validation, failure propagation, SIGINT/SIGTERM cancellation
  during preparation/loading, forced termination, and cleanup. Fixtures never
  connect to an application database.
- [`tests/ingestion_test.php`](./tests/ingestion_test.php) checks extraction and
  NULL/identifier transformations, plus real-inventory dry-run selection.
- [`tests/source_metadata_test.php`](./tests/source_metadata_test.php) checks
  provenance registration, quoting, repeatability, and stale-upload rejection.

The stand-ins do not test Gnumeric or PostgreSQL themselves. Validate new source
formats and loader SQL using the real migrator against an isolated database.

Directory contents:

- [`conversion.json`](./conversion.json): explicit dataset recipes.
- [`src/`](./src/): generic driver, registration command, readers, and transforms.
- [`tests/`](./tests/): PHP regression suites and small process fixtures.
- [`data/`](./data/): source workbooks and NOC/EDM inputs.
- [`load/`](./load/): inventory, pgloader definitions, and generated CSVs.
- [`Dockerfile`](./Dockerfile): migrator image definition.

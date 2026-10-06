# LotoSorter

[![Tests](https://github.com/Gloas/LotoSorter/actions/workflows/tests.yml/badge.svg)](https://github.com/Gloas/LotoSorter/actions/workflows/tests.yml)

[Version française](README.fr.md)

Tool that sorts a list of donations (lots) collected for a Loto evening into
balanced prize lots: Quine, Double-quine and Carton for each round, plus
optional "Gros lot" and "Pas de bol" rounds, for adults and kids.

The donations are tracked in a Google Sheet (one tab per area). Each tab is
exported as CSV, then sorted from a **web interface** or the **command line**.

## Requirements

Either:

- PHP 8.3 or newer and [Composer](https://getcomposer.org/), then
  `composer install`;
- or only Docker (with Compose), see [Docker](#docker).

## Web interface

```sh
composer install
composer serve        # then open http://localhost:8080
```

1. Upload the CSV exports of your Google Sheet (several files at once).
2. Check the rounds and amounts (pre-filled from `loto_config.ini`).
3. Click **Trier les dons**: the page shows every round and lot with its total
   against its target, the gifts left over, and the files to download.

Files are processed in memory and never stored on the server. Download
"Tout en un" and import it in Google Sheets.

## Command line

```sh
cat data/*.csv > all.csv
php sort_donation.php all.csv          # or: composer sort -- all.csv
```

It reads `./loto_config.ini` and writes the result CSVs in the current
directory. To try it on the example:

```sh
cp examples/loto_config.exemple.ini loto_config.ini
php sort_donation.php examples/lots_loto_exemple.csv
```

## Workflow

1. **Collect donations in a Google Sheet.** Start from
   [`examples/lots_loto_exemple.csv`](examples/lots_loto_exemple.csv) (also
   downloadable from the web interface): in Google Sheets, *File > Import >
   Upload*, then *Replace spreadsheet* or *Insert new sheet(s)*.
2. **Export every tab as CSV** (*File > Download > Comma-separated values*).
   Keep them in `data/`: `data/`, `all.csv` and the generated CSVs are
   git-ignored, as they hold personal data (names, phone numbers, addresses).
3. **Sort** with the web interface or the command line.
4. **Import the result** (`auto_sort_loto_donations.csv`) back into Google
   Sheets. The totals are Google Sheets formulas, so each lot shows its sum
   next to its target.

## Google Sheet format

Only the first five columns are read; their **position** matters, not their
title. Any other column is kept as-is in the output.

| Col | Example header          | Used for                                                       |
|-----|-------------------------|----------------------------------------------------------------|
| A   | `COMMERCES`             | Donor. Two gifts from the same donor never go in the same lot. |
| B   | `Démarcheurs APE`       | Free (who collected the gift).                                 |
| C   | `Montant du DON`        | Value: `37€`, `25,00 €`, `6,5 €`, `12.50`... Rows without one are ignored. |
| D   | `Commentaire don`       | Gift label. Two identical labels never go in the same lot.     |
| E   | `ENFANT / ADULTE / MIX` | Audience: `Adulte`, `Enfant` or `Mix` (Mix can go to both).    |
| F…  | free                    | Gender, city, notes...                                         |

- **One row per physical gift**: two identical vouchers = two rows.
- Gifts under **5 €** are never used.
- Prospects, refusals, "waiting for answer"... can stay: leave the amount empty.

## Configuration: `loto_config.ini`

```ini
[adult]
round = 10          ; number of rounds
quine = 100         ; target value of each Quine (€)
double_quine = 200  ; target value of each Double-quine (€)
carton = 350        ; target value of each Carton (€)
gros_lot = 770      ; a single gift worth at least this amount (0 = none)
pas_de_bol = 300    ; consolation lot (0 = none)

[kid]
round = 4
quine = 100
double_quine = 200
carton = 300
gros_lot = 280
pas_de_bol = 400

[sorting]           ; optional, defaults in examples/loto_config.exemple.ini
allowed_donors[] = "APE de Valleiry"   ; gifts that may share a lot
```

The `[sorting]` section also sets the thresholds below (`min_amount`,
`quine_max_share`, `double_quine_max_share`, `carton_min_share`,
`tolerance_below`, `tolerance_above`, `max_vouchers_per_lot`, `max_attempts`).
The web interface shows them under *Réglages avancés*.

## How the sort works

Donations are shuffled, then for each audience in the config order, each round
is filled lot by lot with a greedy pass over the remaining gifts:

| Lot          | Gifts accepted                                    | Complete when                               |
|--------------|---------------------------------------------------|---------------------------------------------|
| Quine        | each at most 25 % of the target                   | total ≥ target − 1 % (up to + 10 %)         |
| Double-quine | each at most 30 % of the target                   | total ≥ target − 1 % (up to + 10 %)         |
| Carton       | the first one at least 17 % of the target         | total ≥ target − 1 % (up to + 10 %)         |
| Gros lot     | a single gift                                     | the gift is worth ≥ target − 1 %            |
| Pas de bol   | like a Carton                                     | total ≥ target − 1 % (up to + 10 %)         |

In every lot: adult lots take `Adulte` and `Mix` gifts, kid lots `Enfant` and
`Mix`; never the same donor or label twice (except the `allowed_donors`); at
most 2 vouchers (labels with the word "bon").

A lot that cannot reach its target keeps the gifts it got. If a lot gets no
gift at all, everything is reshuffled and retried, up to 300 times; then the
sort fails with `Trop de boucles…`: lower the amounts or the number of rounds,
or collect more gifts. The result is random: run it again for another one.

## Output files

| File                            | Content                                                        |
|---------------------------------|----------------------------------------------------------------|
| `auto_sort_loto_donations.csv`  | Everything in one sheet: summary, unsorted gifts, then the grids. Import this one. |
| `sorted_donations.csv`          | The grids only (rounds, lots, gifts and totals).               |
| `not_sorted_donations.csv`      | Gifts left over.                                               |

## Docker

The image holds PHP, Composer and the code; your data never goes into it
(see `.dockerignore`).

```sh
docker compose build
docker compose up web                   # web interface on http://localhost:8080
docker compose run --rm sort all.csv    # command line, in the project folder
docker compose run --rm test            # coding style, static analysis and tests
```

Rebuild the image (`docker compose build`) after changing the code.

## Development

```sh
composer check      # all of the below
composer lint       # PSR-12 coding style (PHP_CodeSniffer), composer lint:fix to fix it
composer analyse    # static analysis (PHPStan, level max)
composer test       # PHPUnit: unit, command line and web tests
```

GitHub Actions runs the style and analysis checks, the tests on PHP 8.3, 8.4
and 8.5, and the Docker image (checks, a sort and the web interface) on every
push to `main` and every pull request.

```
src/
├── Domain/          Donation, Lot, Round, Grid, SortResult... (no I/O)
├── Config/          loto_config.ini and form values → LotoConfig, with validation
├── Sorting/         DonationSorter → GridBuilder → LotFiller, one class per lot rule (Rule/)
├── Csv/             reads the Google Sheet exports, writes CSV
├── Report/          summary and spreadsheet layout of the result
├── Application/     SortDonations: the use case shared by the CLI and the web
├── Cli/             SortCommand (sort_donation.php)
└── Web/             Slim actions and form handling (public/index.php, templates/)
config/container.php PHP-DI container, the only place where services are wired
```

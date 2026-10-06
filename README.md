# LotoSorter

Tool that sorts a list of donations (lots) collected for a Loto evening into
balanced prize lots: Quine, Double-quine and Carton for each round, plus
optional "Gros lot" and "Pas de bol" rounds, for adults and kids.

The donations are tracked in a Google Sheet (one tab per area). Each tab is
exported as CSV, the CSVs are concatenated, and `sort_donation.php` builds the
grids.

## Requirements

Either:

- PHP 8.1 or newer (CLI) to sort, plus PHP 8.3 and
  [Composer](https://getcomposer.org/) to run the tests;
- or only Docker (with Compose), see [Docker](#docker).

## Quick start with the example

```sh
cp examples/loto_config.exemple.ini loto_config.ini
php sort_donation.php examples/lots_loto_exemple.csv
# or: composer sort -- examples/lots_loto_exemple.csv
# or: docker compose run --rm sort examples/lots_loto_exemple.csv
```

> `sort_donation.php` always reads `./loto_config.ini` and writes its output
> files in the current directory. Back up your real `loto_config.ini` before
> trying the example.

## Workflow

1. **Collect donations in a Google Sheet.** Start from
   [`examples/lots_loto_exemple.csv`](examples/lots_loto_exemple.csv):
   in Google Sheets, *File > Import > Upload*, then pick *Replace spreadsheet*
   (or *Insert new sheet(s)* to add it as a tab). One tab per area is
   convenient (e.g. "Saint Julien", "Pays de Gex", "Achats pour script"...).
2. **Export every tab as CSV** (*File > Download > Comma-separated values*)
   into the `data/` folder. `data/`, `all.csv` and the generated CSVs are
   git-ignored: they hold personal data (names, phone numbers, addresses) and
   must never be committed.
3. **Concatenate them** into a single file:
   ```sh
   cat data/*.csv > all.csv
   ```
   Header rows and rows without an amount are ignored, so there is no need to
   strip them.
4. **Tune the Loto** in `loto_config.ini` (see below).
5. **Run the sort:**
   ```sh
   php sort_donation.php all.csv
   ```
6. **Import the result** (`auto_sort_loto_donations.csv`) back into Google
   Sheets. The totals are written as Google Sheets formulas, so each lot shows
   its sum next to the target amount.

## Google Sheet format

Only the first five columns are read; their **position** matters, not their
title. Any other column (canvasser, contact, follow-up notes...) is kept as-is
in the output.

| Col | Example header         | Used for                                               |
|-----|------------------------|--------------------------------------------------------|
| A   | `COMMERCES`            | Donor. Two gifts from the same donor never go in the same lot. |
| B   | `Démarcheurs APE`      | Free (who collected the gift).                         |
| C   | `Montant du DON`       | Value of the gift, e.g. `25,00 €` or `37€`. Rows with no value are ignored. |
| D   | `Commentaire don`      | Gift label. Two identical labels never go in the same lot. |
| E   | `ENFANT / ADULTE / MIX`| Audience: exactly `Adulte`, `Enfant` or `Mix` (Mix can go to both). |
| F…  | free                   | Gender, "at the local", website/logo, city, notes...   |

Rules to follow when filling the sheet:

- **One row per physical gift.** Two identical vouchers = two rows.
- Amounts can be written `37€`, `37,00 €`, `6,5 €` or `12.50`; a `,` or `.`
  followed by one or two digits is the decimal separator.
- Gifts under **5 €** are never used (they stay in the "not sorted" list).
- Prospects, refusals, "waiting for answer"... can stay in the sheet: just
  leave the amount empty.
- Purchases made by the association (e.g. the gros lot) go in their own tab
  (e.g. "Achats pour script") with the same first five columns.

## Configuration: `loto_config.ini`

One section per audience, amounts in euros:

```ini
[adult]
round = 10          ; number of rounds
quine = 100         ; target value of each Quine lot
double_quine = 200  ; target value of each Double-quine lot
carton = 350        ; target value of each Carton lot
gros_lot = 770      ; single gift worth at least this amount (0 = disabled)
pas_de_bol = 300    ; consolation lot (0 = disabled)

[kid]
round = 4
quine = 100
double_quine = 200
carton = 300
gros_lot = 280
pas_de_bol = 400
```

Keep the `quine`, `double_quine`, `carton`, `gros_lot` and `pas_de_bol`
values different from each other within a section: the round type is
recognised by its amount.

## How the sort works

Donations are shuffled, then for adults first and kids second, each round is
filled lot by lot with a greedy pass over the remaining gifts:

- a lot is closed once its total reaches the target minus 1 %, and may go up
  to the target plus 10 %;
- a **Quine** only takes gifts worth at most 25 % of its target, a
  **Double-quine** at most 30 %, so the big gifts are kept for the Cartons;
- a **Carton** must start with a gift worth at least 17 % of its target;
- the **Gros lot** is a single gift worth at least its target;
- the same donor or the same label is never used twice in a lot (except for
  the donors listed in `$_allowed_as_multiples_donator`, e.g. the APE's own
  purchases), and the number of vouchers ("... bon ...") in a lot is limited.

A lot that cannot reach its target keeps the gifts it got (check the
"Mise de" totals). If a lot gets no gift at all, everything is reshuffled and
retried, up to 300 times. If it still fails, the script prints `Trop de boucles…`: lower the
amounts or the number of rounds in `loto_config.ini`, or collect more gifts.

These thresholds are constants at the top of `SortDonationsForKid` in
`sort_donation.php` (`$_variance_up`, `$_variance_down`, `$_quine_max`,
`$_double_quine_max`, `$_carton_min`, `$_min_price`).

## Docker

The image contains PHP, Composer and the tests; your data never goes into it
(see `.dockerignore`). The project folder is mounted as the working
directory, so `loto_config.ini` is read from it and the CSVs are written to it.

```sh
docker compose build
docker compose run --rm sort all.csv    # same as: php sort_donation.php all.csv
docker compose run --rm test            # run the test suite
```

Rebuild the image (`docker compose build`) after changing the code.

## Tests

```sh
composer install
composer test
```

## Output files

| File                            | Content                                                        |
|---------------------------------|----------------------------------------------------------------|
| `auto_sort_loto_donations.csv`  | Everything in one sheet: run summary, unsorted gifts, then the grids. Import this one. |
| `sorted_donations.csv`          | The grids only (rounds, lots, gifts and totals).               |
| `not_sorted_donations.csv`      | Gifts left over (too cheap, or not needed).                    |

The run summary is also printed on the terminal: number of retries, gifts
sorted and left over (per audience), and the configuration used.

## Known limitations

- `loto_config.ini` and the output paths are fixed (current directory).
- The donors allowed to appear several times in a lot are hard-coded in
  `sort_donation.php`.
- The result is random: run it again to get another distribution.

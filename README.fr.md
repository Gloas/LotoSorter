# LotoSorter

[![Tests](https://github.com/Gloas/LotoSorter/actions/workflows/tests.yml/badge.svg)](https://github.com/Gloas/LotoSorter/actions/workflows/tests.yml)

[English version](README.md)

LotoSorter répartit automatiquement les dons récoltés pour une soirée loto en
lots équilibrés : Quine, Double-quine et Carton pour chaque partie, plus un
« Gros lot » et un lot « Pas de bol » optionnels, pour les adultes et pour les
enfants.

Les dons sont suivis dans un Google Sheet (un onglet par secteur). Chaque
onglet est exporté en CSV, puis trié depuis une **interface web** ou en
**ligne de commande**.

## Prérequis

Au choix :

- PHP 8.3 ou plus et [Composer](https://getcomposer.org/), puis
  `composer install` ;
- ou seulement Docker (avec Compose), voir [Docker](#docker).

## Interface web

```sh
composer install
composer serve        # puis ouvrir http://localhost:8080
```

1. Envoyer les exports CSV du Google Sheet (plusieurs fichiers à la fois).
2. Vérifier les parties et les montants (pré-remplis depuis `loto_config.ini`).
3. Cliquer sur **Trier les dons** : la page affiche chaque partie et chaque lot
   avec son total face à sa cible, les dons restants et les fichiers à
   télécharger.

Les fichiers sont traités en mémoire et jamais conservés sur le serveur.
Télécharger « Tout en un » et l'importer dans Google Sheets.

## Ligne de commande

```sh
cat data/*.csv > all.csv
php sort_donation.php all.csv          # ou : composer sort -- all.csv
```

Le script lit `./loto_config.ini` et écrit les CSV du résultat dans le dossier
courant. Pour l'essayer sur l'exemple :

```sh
cp examples/loto_config.exemple.ini loto_config.ini
php sort_donation.php examples/lots_loto_exemple.csv
```

## Déroulement

1. **Suivre les dons dans un Google Sheet.** Partir du modèle
   [`examples/lots_loto_exemple.csv`](examples/lots_loto_exemple.csv) (aussi
   téléchargeable depuis l'interface web) : dans Google Sheets, *Fichier >
   Importer > Importer*, puis *Remplacer la feuille de calcul* ou *Insérer de
   nouvelles feuilles*.
2. **Exporter chaque onglet en CSV** (*Fichier > Télécharger > Valeurs
   séparées par des virgules*). Les garder dans `data/` : `data/`, `all.csv`
   et les CSV produits sont ignorés par git, car ils contiennent des données
   personnelles (noms, téléphones, adresses).
3. **Trier** avec l'interface web ou la ligne de commande.
4. **Réimporter le résultat** (`auto_sort_loto_donations.csv`) dans Google
   Sheets. Les totaux sont des formules : chaque lot affiche sa somme à côté de
   sa cible.

## Format du Google Sheet

Seules les cinq premières colonnes sont lues, et c'est leur **position** qui
compte, pas leur titre. Les autres colonnes sont recopiées telles quelles.

| Col | Titre d'exemple         | Rôle                                                           |
|-----|-------------------------|----------------------------------------------------------------|
| A   | `COMMERCES`             | Donateur. Deux dons du même donateur ne vont jamais dans le même lot. |
| B   | `Démarcheurs APE`       | Libre (bénévole qui a récupéré le don).                        |
| C   | `Montant du DON`        | Valeur : `37€`, `25,00 €`, `6,5 €`, `12.50`… Ligne ignorée si vide. |
| D   | `Commentaire don`       | Libellé. Deux libellés identiques ne vont jamais dans le même lot. |
| E   | `ENFANT / ADULTE / MIX` | Public : `Adulte`, `Enfant` ou `Mix` (Mix sert aux deux).      |
| F…  | libre                   | Fille / Garçon, ville, notes…                                  |

- **Une ligne par cadeau physique** : deux bons identiques = deux lignes.
- Les dons de moins de **5 €** ne sont jamais utilisés.
- Commerces à relancer, refus, réponses en attente… peuvent rester : laisser
  le montant vide.

## Configuration : `loto_config.ini`

```ini
[adult]
round = 10          ; nombre de parties
quine = 100         ; valeur visée de chaque Quine (€)
double_quine = 200  ; valeur visée de chaque Double-quine (€)
carton = 350        ; valeur visée de chaque Carton (€)
gros_lot = 770      ; un seul cadeau d'au moins ce montant (0 = aucun)
pas_de_bol = 300    ; lot de consolation (0 = aucun)

[kid]
round = 4
quine = 100
double_quine = 200
carton = 300
gros_lot = 280
pas_de_bol = 400

[sorting]           ; optionnel, valeurs par défaut dans examples/loto_config.exemple.ini
allowed_donors[] = "APE de Valleiry"   ; cadeaux autorisés plusieurs fois dans un lot
```

La section `[sorting]` règle aussi les seuils ci-dessous (`min_amount`,
`quine_max_share`, `double_quine_max_share`, `carton_min_share`,
`tolerance_below`, `tolerance_above`, `max_vouchers_per_lot`, `max_attempts`).
L'interface web les présente sous *Réglages avancés*.

## Fonctionnement du tri

Les dons sont mélangés puis, pour chaque public dans l'ordre de la
configuration, chaque partie est remplie lot par lot en piochant dans les dons
restants :

| Lot          | Cadeaux acceptés                                  | Complet quand                               |
|--------------|---------------------------------------------------|---------------------------------------------|
| Quine        | chacun au plus 25 % de la cible                   | total ≥ cible − 1 % (jusqu'à + 10 %)        |
| Double-quine | chacun au plus 30 % de la cible                   | total ≥ cible − 1 % (jusqu'à + 10 %)        |
| Carton       | le premier au moins 17 % de la cible              | total ≥ cible − 1 % (jusqu'à + 10 %)        |
| Gros lot     | un seul cadeau                                    | le cadeau vaut ≥ cible − 1 %                |
| Pas de bol   | comme un Carton                                   | total ≥ cible − 1 % (jusqu'à + 10 %)        |

Dans chaque lot : les lots adultes prennent les dons `Adulte` et `Mix`, les
lots enfants `Enfant` et `Mix` ; jamais deux fois le même donateur ni le même
libellé (sauf `allowed_donors`) ; au plus 2 bons d'achat (libellés contenant
le mot « bon »).

Un lot qui n'atteint pas sa cible garde les cadeaux trouvés. Si un lot ne
reçoit **aucun** cadeau, tout est remélangé et recommencé, jusqu'à 300 fois ;
ensuite le tri échoue avec `Trop de boucles…` : baisser les montants ou le
nombre de parties, ou récolter plus de dons. Le résultat est aléatoire :
relancer le tri en donne un autre.

## Fichiers produits

| Fichier                         | Contenu                                                         |
|---------------------------------|-----------------------------------------------------------------|
| `auto_sort_loto_donations.csv`  | Tout en un : résumé, dons non triés, puis les grilles. C'est celui à importer. |
| `sorted_donations.csv`          | Les grilles seules (parties, lots, cadeaux et totaux).          |
| `not_sorted_donations.csv`      | Les dons restants.                                              |

## Docker

L'image contient PHP, Composer et le code ; vos données n'y entrent jamais
(voir `.dockerignore`).

```sh
docker compose build
docker compose up web                   # interface web sur http://localhost:8080
docker compose run --rm sort all.csv    # ligne de commande, dans le dossier du projet
docker compose run --rm test            # style, analyse statique et tests
```

Reconstruire l'image (`docker compose build`) après chaque modification du
code.

## Développement

```sh
composer check      # tout ce qui suit
composer lint       # style PSR-12 (PHP_CodeSniffer), composer lint:fix pour corriger
composer analyse    # analyse statique (PHPStan, niveau max)
composer test       # PHPUnit : tests unitaires, ligne de commande et web
```

GitHub Actions vérifie le style et l'analyse statique, lance les tests sur
PHP 8.3, 8.4 et 8.5, et teste l'image Docker (vérifications, un tri et
l'interface web) à chaque push sur `main` et à chaque pull request.

```
src/
├── Domain/          Donation, Lot, Round, Grid, SortResult… (aucune entrée/sortie)
├── Config/          loto_config.ini et valeurs du formulaire → LotoConfig, avec validation
├── Sorting/         DonationSorter → GridBuilder → LotFiller, une classe par règle de lot (Rule/)
├── Csv/             lecture des exports Google Sheet, écriture des CSV
├── Report/          résumé et mise en page du résultat
├── Application/     SortDonations : le cas d'usage commun à la ligne de commande et au web
├── Cli/             SortCommand (sort_donation.php)
└── Web/             actions Slim et formulaire (public/index.php, templates/)
config/container.php conteneur PHP-DI, seul endroit où les services sont assemblés
```

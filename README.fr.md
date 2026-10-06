# LotoSorter

[![Tests](https://github.com/Gloas/LotoSorter/actions/workflows/tests.yml/badge.svg)](https://github.com/Gloas/LotoSorter/actions/workflows/tests.yml)

[English version](README.md)

LotoSorter répartit automatiquement les dons récoltés pour une soirée loto en
lots équilibrés : Quine, Double-quine et Carton pour chaque partie, plus un
« Gros lot » et un lot « Pas de bol » optionnels, pour les adultes et pour les
enfants.

Les dons sont suivis dans un Google Sheet (un onglet par secteur). Chaque
onglet est exporté en CSV, les fichiers sont regroupés, puis
`sort_donation.php` construit les grilles.

## Prérequis

Au choix :

- PHP 8.1 ou plus (ligne de commande) pour trier, plus PHP 8.3 et
  [Composer](https://getcomposer.org/) pour lancer les tests ;
- ou seulement Docker (avec Compose), voir [Docker](#docker).

## Essai rapide avec l'exemple

```sh
cp examples/loto_config.exemple.ini loto_config.ini
php sort_donation.php examples/lots_loto_exemple.csv
# ou : composer sort -- examples/lots_loto_exemple.csv
# ou : docker compose run --rm sort examples/lots_loto_exemple.csv
```

> `sort_donation.php` lit toujours `./loto_config.ini` et écrit ses résultats
> dans le dossier courant. Sauvegardez votre vrai `loto_config.ini` avant
> d'essayer l'exemple.

## Déroulement

1. **Suivre les dons dans un Google Sheet.** Partir du modèle
   [`examples/lots_loto_exemple.csv`](examples/lots_loto_exemple.csv) :
   dans Google Sheets, *Fichier > Importer > Importer*, puis *Remplacer la
   feuille de calcul* (ou *Insérer de nouvelles feuilles* pour l'ajouter comme
   onglet). Un onglet par secteur est pratique (« Saint Julien », « Pays de
   Gex », « Achats pour script »…).
2. **Exporter chaque onglet en CSV** (*Fichier > Télécharger > Valeurs
   séparées par des virgules*) dans le dossier `data/`. `data/`, `all.csv` et
   les CSV produits sont ignorés par git : ils contiennent des données
   personnelles (noms, téléphones, adresses) et ne doivent jamais être
   publiés.
3. **Regrouper les fichiers** en un seul :
   ```sh
   cat data/*.csv > all.csv
   ```
   Les lignes de titre et les lignes sans montant sont ignorées, inutile de
   les retirer.
4. **Ajuster le loto** dans `loto_config.ini` (voir plus bas).
5. **Lancer le tri :**
   ```sh
   php sort_donation.php all.csv
   ```
6. **Réimporter le résultat** (`auto_sort_loto_donations.csv`) dans Google
   Sheets. Les totaux sont des formules Google Sheets : chaque lot affiche sa
   somme à côté de sa cible.

## Format du Google Sheet

Seules les cinq premières colonnes sont lues, et c'est leur **position** qui
compte, pas leur titre. Les autres colonnes (démarcheur, contact, suivi…) sont
recopiées telles quelles dans le résultat.

| Col | Titre d'exemple         | Rôle                                                       |
|-----|-------------------------|------------------------------------------------------------|
| A   | `COMMERCES`             | Donateur. Deux dons du même donateur ne vont jamais dans le même lot. |
| B   | `Démarcheurs APE`       | Libre (bénévole qui a récupéré le don).                    |
| C   | `Montant du DON`        | Valeur du don, par ex. `25,00 €` ou `37€`. Ligne ignorée si vide. |
| D   | `Commentaire don`       | Libellé du don. Deux libellés identiques ne vont jamais dans le même lot. |
| E   | `ENFANT / ADULTE / MIX` | Public : exactement `Adulte`, `Enfant` ou `Mix` (Mix sert aux deux). |
| F…  | libre                   | Fille / Garçon, présent au local, site, ville, notes…      |

Règles de saisie :

- **Une ligne par cadeau physique** : deux bons identiques = deux lignes.
- Le montant s'écrit `37€`, `37,00 €`, `6,5 €` ou `12.50` ; une `,` ou un `.`
  suivi d'un ou deux chiffres est le séparateur décimal.
- Les dons de moins de **5 €** ne sont jamais utilisés (ils restent dans la
  liste des non triés).
- Les commerces à relancer, refus, réponses en attente… peuvent rester dans le
  tableau : il suffit de laisser le montant vide.
- Les achats de l'association (le gros lot par exemple) vont dans un onglet à
  part (par ex. « Achats pour script ») avec les mêmes cinq premières
  colonnes.

## Configuration : `loto_config.ini`

Une section par public, montants en euros :

```ini
[adult]
round = 10          ; nombre de parties
quine = 100         ; valeur visée de chaque Quine
double_quine = 200  ; valeur visée de chaque Double-quine
carton = 350        ; valeur visée de chaque Carton
gros_lot = 770      ; un seul cadeau d'au moins ce montant (0 = désactivé)
pas_de_bol = 300    ; lot de consolation (0 = désactivé)

[kid]
round = 4
quine = 100
double_quine = 200
carton = 300
gros_lot = 280
pas_de_bol = 400
```

Dans une même section, les montants `quine`, `double_quine`, `carton`,
`gros_lot` et `pas_de_bol` doivent être différents : le type de lot est
reconnu à son montant.

## Fonctionnement du tri

Les dons sont mélangés, puis, pour les adultes d'abord et les enfants ensuite,
chaque partie est remplie lot par lot en piochant dans les dons restants :

- un lot est fermé quand son total atteint la cible moins 1 %, et peut monter
  jusqu'à la cible plus 10 % ;
- une **Quine** ne prend que des cadeaux valant au plus 25 % de sa cible, une
  **Double-quine** au plus 30 %, pour garder les gros cadeaux pour les
  Cartons ;
- un **Carton** doit commencer par un cadeau valant au moins 17 % de sa cible ;
- le **Gros lot** est un seul cadeau valant au moins sa cible ;
- jamais deux fois le même donateur ni le même libellé dans un lot (sauf pour
  les donateurs listés dans `$_allowed_as_multiples_donator`, comme les achats
  de l'APE), et le nombre de bons d'achat (« … bon … ») par lot est limité.

Un lot qui n'atteint pas sa cible garde les cadeaux trouvés (vérifier les
totaux « Mise de »). Si un lot ne reçoit **aucun** cadeau, tout est remélangé
et recommencé, jusqu'à 300 fois. Si cela échoue encore, le script affiche
`Trop de boucles…` : baisser les montants ou le nombre de parties dans
`loto_config.ini`, ou récolter plus de dons.

Ces seuils sont des constantes en tête de `SortDonationsForKid` dans
`sort_donation.php` (`$_variance_up`, `$_variance_down`, `$_quine_max`,
`$_double_quine_max`, `$_carton_min`, `$_min_price`).

## Docker

L'image contient PHP, Composer et les tests ; vos données n'y entrent jamais
(voir `.dockerignore`). Le dossier du projet est monté comme dossier de
travail : `loto_config.ini` y est lu et les CSV y sont écrits.

```sh
docker compose build
docker compose run --rm sort all.csv    # équivaut à : php sort_donation.php all.csv
docker compose run --rm test            # lance les tests
```

Reconstruire l'image (`docker compose build`) après chaque modification du
code.

## Tests

```sh
composer install
composer test
```

Les tests tournent aussi sur GitHub Actions à chaque push sur `main` et à
chaque pull request : PHPUnit sur PHP 8.3, 8.4 et 8.5, puis construction de
l'image Docker, tests et tri de l'exemple dans l'image.

## Fichiers produits

| Fichier                         | Contenu                                                         |
|---------------------------------|-----------------------------------------------------------------|
| `auto_sort_loto_donations.csv`  | Tout en un : résumé du tri, dons non triés, puis les grilles. C'est celui à importer. |
| `sorted_donations.csv`          | Les grilles seules (parties, lots, cadeaux et totaux).          |
| `not_sorted_donations.csv`      | Les dons restants (trop petits ou inutiles).                    |

Le résumé s'affiche aussi dans le terminal : nombre de boucles, dons triés et
restants (par public) et configuration utilisée.

## Limites connues

- Le nom `loto_config.ini` et le dossier de sortie sont fixes (dossier
  courant).
- Les donateurs autorisés plusieurs fois dans un même lot sont écrits en dur
  dans `sort_donation.php`.
- Le résultat est aléatoire : relancer le tri donne une autre répartition.

# Créer un site et le tenir à jour depuis le socle

Un site client est créé depuis le dépôt socle, puis vit dans son propre dépôt. Les corrections
apportées au socle se récupèrent ensuite par fusion, site par site.

Deux situations, dans l'ordre où elles se présentent le plus souvent : mettre à jour un site
qui existe, et en installer un nouveau.

## Mettre à jour un site

Tout se passe dans le dépôt du site, sur un poste de développement. La mise en ligne vient
après, à l'étape 6.

### La commande

```bash
php bin/maj-socle.php                  # où en est le site, et ce qui existe
php bin/maj-socle.php --vers=X.Y.Z     # fusionne et enchaîne les commandes qui suivent
```

`X.Y.Z` est à remplacer par le numéro visé, sans le `v` : la première commande les liste.

Elle déroule les étapes 1 à 5 décrites plus bas : branche dédiée, fusion de l'étiquette, puis
`composer install`, `install-cockpit.php --force`, `purge-cache.php` et les tests.

Elle s'arrête, sans rien faire, dans quatre cas :

| Cas | Pourquoi |
|---|---|
| Arbre de travail non propre | Une fusion par-dessus des modifications en cours est irrattrapable |
| Version inconnue, ou numéro mal formé | La liste des versions disponibles est affichée |
| Version plus ancienne que celle du site | Elle n'apporterait rien |
| Version **majeure** | Elle demande une intervention manuelle, décrite sous la version dans le journal |

En cas de conflit, elle s'arrête et affiche les fichiers à reprendre. Elle ne pousse pas et ne
fusionne jamais dans `main` : la relecture reste humaine.

Les étapes qui suivent décrivent ce que fait cette commande. Les lire reste utile pour
comprendre, pour reprendre la main après un conflit, ou pour une version majeure.

### 1. Voir où en est le site

```bash
cd <site-client>
cat VERSION
```

Le numéro affiché est celui du socle installé. S'il n'y a pas de fichier `VERSION`, le site est
antérieur à `v1.0.0` : la première fusion le posera.

### 2. Choisir la version à prendre

```bash
git fetch socle --tags
git tag -l 'v*' --sort=v:refname
```

`git tag -l` sans motif listerait aussi les étiquettes propres au site ; `'v*'` ne garde que
celles du socle.

> **Le préfixe `v` appartient au socle.** Une étiquette posée par le site ne commence jamais
> par `v`. C'est ce qui permet au filtre ci-dessus de fonctionner, et ce qui évite le refus
> décrit juste en dessous.

#### Si le `fetch` affiche « would clobber existing tag »

```
 ! [rejected]  v2.0.6 -> v2.0.6  (would clobber existing tag)
```

Le site porte déjà une étiquette de ce nom, qui désigne autre chose. Git refuse de la déplacer,
et c'est une protection.

**Le refus ne porte que sur cette étiquette.** Toutes les autres sont arrivées. `git fetch`
sort malgré tout en erreur, ce qui donne à la commande entière l'apparence d'un échec : ce n'en
est pas un, et la version visée est le plus souvent déjà là.

Regarder ce que l'étiquette locale désigne avant de décider :

```bash
git show --no-patch --format='%H %ci %s' v2.0.6
```

| Ce que c'est | Ce qu'il faut faire |
|---|---|
| Une étiquette propre au site, qui porte mal son nom | la renommer sans le préfixe `v` : `git tag nouveau-nom v2.0.6` puis `git tag -d v2.0.6` |
| Une ancienne copie de l'étiquette du socle | la remplacer : `git fetch socle --tags --force` |

Reprendre ensuite `git tag -l 'v*' --sort=v:refname`.

`--sort=v:refname` n'est pas un détail : le tri par défaut est alphabétique, et il place
`v2.0.10` entre `v2.0.1` et `v2.0.2`. La dernière version de la liste ne serait pas la plus
récente.

Lire ensuite ce qu'apporte chaque version entre celle du site et celle visée, dans
[CHANGELOG.md](../CHANGELOG.md).

> **Le premier chiffre est le seul qui coûte.** `1.4.0` → `2.0.0` annonce une intervention
> manuelle — déplacer des fichiers, modifier `.env`, migrer des données. La marche à suivre est
> donnée sous la version dans le journal, et se prépare **avant** de fusionner : elle ne
> s'improvise pas sur un site en service. Tout autre changement — `1.3.0` → `1.4.0`,
> `1.3.0` → `1.3.1` — se fusionne sans rien de plus.

Rien n'oblige à passer les versions une par une : fusionner directement la plus récente reprend
tout ce qui la précède. Si plusieurs versions majeures sont franchies d'un coup, appliquer
leurs interventions dans l'ordre du journal.

### 3. Fusionner

Se positionner sur la branche principale et récupérer les dernières modifications :

```bash
git checkout main
git pull
```

Créer une branche dédiée à la mise à jour du socle. **Son nom porte la version visée**, sinon
la deuxième mise à jour butera sur la branche laissée par la première :

```bash
git checkout -b maj-socle-X.Y.Z
```

C'est le nom que pose `bin/maj-socle.php`. Si git répond `a branch named … already exists`,
cette mise à jour est déjà commencée : la reprendre avec `git checkout maj-socle-X.Y.Z`, ou la
supprimer avec `git branch -d maj-socle-X.Y.Z` si elle a déjà été fusionnée.

Fusionner ensuite la nouvelle version du socle :

```bash
git merge vX.Y.Z --allow-unrelated-histories
```

`X.Y.Z` est partout le numéro choisi à l'étape 2. Ces lignes ne se recopient pas telles quelles.

L’option `--allow-unrelated-histories` permet d’autoriser la fusion lorsque les deux historiques Git n’ont pas d’ancêtre commun.

Ce cas peut notamment se présenter lorsque le dépôt du projet vient d’être initialisé et que la version du socle utilisée pour effectuer la mise à jour possède un historique Git différent.

Toujours sur une branche : la fusion se relit avant d'entrer dans `main`.

`VERSION` fait partie de la fusion, donc le site porte le nouveau numéro sans qu'on ait à
l'écrire.

L'étiquette est fusionnée plutôt que `socle/main` : ce qui entre dans le site est un état
publié et décrit dans le journal, et non l'avancement du moment.

### 4. Régler les conflits, s'il y en a

```bash
git diff --name-only --diff-filter=U
```

Vide, il n'y en a pas — passer à l'étape suivante. Sinon, ouvrir chaque fichier listé, garder
les deux apports, puis :

```bash
git add <fichier>
git commit
```

La liste des fichiers susceptibles de rentrer en conflit est plus bas.

### 5. Après la fusion

Les quatre commandes, dans cet ordre, sans condition :

```bash
composer install                       # sans effet si composer.lock n'a pas bougé
php bin/install-cockpit.php --force    # obligatoire, voir ci-dessous
php bin/purge-cache.php
composer test
```

**`--force` n'est pas facultatif.** `public/admin/` n'est pas versionné : une fusion qui touche
`cockpit/config.php`, `cockpit/bootstrap.php`, `cockpit/models/` ou `cockpit/addons/` ne change
rien tant que le script ne les a pas recopiés. Sans `--force`, il constate que la version de
Cockpit est déjà installée et s'arrête sans rien faire.

La commande est sans danger si rien n'a changé : elle réinstalle le cœur de Cockpit, laisse
`var/`, `public/medias/` et `public/admin/.env` intacts, et ne retélécharge l'archive que si
elle est absente.

Ouvrir enfin une page du site et `/admin`, puis pousser pour relecture :

```bash
git push -u origin maj-socle-X.Y.Z
```

### 6. Mettre à jour le site en ligne

Les étapes précédentes n'ont touché que le dépôt. Une fois la branche fusionnée dans `main`,
reporter le socle sur l'hébergement. Deux cas, selon ce que l'hébergeur permet.

#### Le modèle de contenu existe en deux exemplaires

Un seul se modifie :

| Fichier | Ce que c'est |
|---|---|
| `cockpit/models/pages.model.php` | **la source**, versionnée, celle qu'on modifie |
| `public/admin/storage/content/pages.model.php` | la copie installée, réécrite à chaque `install-cockpit.php` |

Modifier la copie puis lancer l'installation annule la modification, sans message. Le dépôt fait
foi.

#### Avec un accès SSH et un dépôt git sur le serveur

```bash
cd ~/<dossier-du-site>
git status                             # doit être propre : la prod n'a rien à committer
git pull
php bin/install-cockpit.php --force
php bin/purge-cache.php
```

À poser **une fois par hébergement**, avant la première mise à jour :

```bash
git config pull.ff only
```

Sur une production saine, `git pull` fait exactement ce que ferait `git merge --ff-only
origin/main` : le serveur n'a aucun commit local, la fusion est une simple avance rapide. Les
deux commandes sont indiscernables — jusqu'au jour où le serveur a dérivé, parce qu'un
correctif y a été committé sur place ou qu'une reprise y a laissé un commit.

- `git pull` fusionne quand même, et crée un commit de fusion que le serveur est seul à
  porter. Chaque mise à jour suivante en ajoute un.
- Si la fusion conflicte, git écrit les marqueurs `<<<<<<< HEAD` **dans les fichiers** — au
  milieu d'un `public/index.php` que PHP est en train de servir.
- Avec `pull.ff only`, git s'arrête sur `Not possible to fast-forward, aborting`. Rien n'est
  touché, le site continue de répondre, et la dérive se règle à froid.

Ce n'est donc pas une autre opération, c'est la même avec un garde-fou. Le réglage fixe au
passage le comportement de `git pull`, qui dépend sinon de la version de git et de la
configuration de la machine.

> **`install-cockpit.php --force` a besoin du réseau avant de copier quoi que ce soit.** Il
> réinstalle le cœur de Cockpit depuis une archive officielle, qu'il télécharge si elle est
> absente du serveur ou si son empreinte ne correspond pas. Sur un hébergement sans accès
> sortant, la commande s'arrête **avant** d'avoir rien copié.
>
> Le contournement tient en une ligne : envoyer aussi le dossier `var/tmp/install/` du poste de
> développement, où l'archive est déjà présente et vérifiée. `var/` n'étant pas versionné, elle
> n'arrive pas avec `git pull`.

#### Par envoi de fichiers

Voir [installation-mutualise.md](installation-mutualise.md), qui décrit aussi les droits
d'écriture à ouvrir. Après l'envoi, relancer les deux commandes ci-dessus si la ligne de
commande existe.

Sans ligne de commande, `install-cockpit.php` ne peut pas tourner sur place : les fichiers de
`public/admin/` doivent être préparés en local puis envoyés. Il reste alors deux choses à faire
à la main, que rien ne signale si on les oublie.

| Ce qui a changé | Ce qu'il faut faire |
|---|---|
| N'importe quoi | enregistrer un contenu depuis l'administration : cela vide le cache de pages |
| Un gabarit de `templates/` ou `templates-client/` | rien de plus, **si le transfert a daté les fichiers du jour**. Twig recompile un gabarit dont la date a changé. Un client de transfert réglé pour conserver les dates d'origine le laisse passer pour inchangé : supprimer alors le contenu de `var/cache/twig` |
| Un modèle de `cockpit/models/` | supprimer en plus `var/data/app.memory.sqlite`, où Cockpit garde les modèles analysés |

Le dernier point est celui qui coûte : sans cette suppression, le modèle est bien en place sur
l'hébergement et l'administration continue d'afficher les champs d'avant, sans erreur. Cockpit
reconstruit le fichier à la requête suivante.

## Ce qui entre en conflit

| Fichiers | À la fusion |
|---|---|
| `templates-client/`, `cockpit/sections-client/` | jamais — le socle n'y écrit pas |
| `public/assets/css/client.css` | jamais — il est fait pour être écrit par le site |
| `.env`, `var/`, `public/medias/`, `public/admin/` | jamais — non versionnés (sauf `public/medias/.htaccess`) |
| `templates/`, `src/`, `bin/`, `cockpit/sections/`, `cockpit/lib/`, le reste de `public/assets/` | conflit s'ils ont été modifiés dans le site |
| `cockpit/models/pages.model.php` | conflit si le site y a déclaré des types de section |

Le dernier cas est le plus courant, et il se supprime en déclarant le type dans
`cockpit/sections-client/` plutôt que dans le modèle. Voir
[guide-integration.md](guide-integration.md).

Placer toute personnalisation dans `templates-client/` — voir
[guide-integration.md](guide-integration.md).

## Installer un nouveau site

Trois étapes, une seule fois par client.

### 1. Créer le dépôt du site

Sur GitHub, ouvrir le dépôt socle et cliquer **Use this template → Create a new repository**.

| Champ | Valeur |
|---|---|
| Nom | celui du client, par exemple `site-boulangerie-martin` |
| Visibilité | **Private** |
| Include all branches | décoché |

Puis cloner et installer :

```bash
git clone https://github.com/<compte>/<site-client>.git
cd <site-client>
composer install
php bin/install-cockpit.php
cp .env.example .env
php bin/cockpit-init.php
```

La suite de l'installation locale est dans
[developpement-local.md](developpement-local.md).

### 2. Déclarer le socle

```bash
git remote add socle https://github.com/jean-ely-pro/site-vitrine-cockpit-php.git
git fetch socle --tags
git remote -v
```

`origin` est le dépôt du site. `socle` ne sert qu'à lire : ne jamais y pousser.

### 3. Première fusion

Un dépôt créé depuis un template démarre sur un historique neuf, sans ancêtre commun avec le
socle. La première fusion demande donc une option que les suivantes n'auront pas :

```bash
git checkout -b maj-socle-X.Y.Z
git merge vX.Y.Z --allow-unrelated-histories
```

Les fichiers identiques des deux côtés fusionnent sans intervention, et ceux que le socle a
ajoutés depuis sont repris. Seuls ceux dont le contenu diffère sont signalés.

**Si le site n'a pas encore été personnalisé**, prendre le socle en bloc :

```bash
git checkout --theirs -- .
git add -A
git commit -m "Fusionner le socle"
```

**Si le site a déjà été personnalisé**, ouvrir chaque fichier listé par
`git diff --name-only --diff-filter=U` et garder les deux apports.

Reprendre ensuite à l'étape 5 de la mise à jour.

## Cas particuliers

### Reprendre un seul correctif

Quand une seule correction est attendue, sans prendre le reste :

```bash
git fetch socle
git log --oneline socle/main
git cherry-pick <empreinte>
```

Le site garde alors son numéro de version : `VERSION` n'est pas repris. Le noter dans le suivi
du client, sans quoi l'écart devient invisible.

### Revenir à la version précédente

Chaque version majeure décrit son retour arrière sous son entrée dans
[CHANGELOG.md](../CHANGELOG.md) : commit à annuler, fichiers à replacer, adresses déjà
publiées.

### Comment les étiquettes sont posées

Une étiquette ne se pose pas à chaque fusion sur le socle : plusieurs correctifs peuvent
attendre la même. On en ajoute une quand il y a quelque chose qu'un site doit pouvoir
reprendre — c'est l'étiquette, et non la branche, qui est l'unité livrée.

### Une version publiée ne change plus

Une fois la version publiée, son étiquette ne peut plus être ni déplacée ni supprimée, et les
fichiers qui l'accompagnent sont figés. Un numéro désigne donc toujours le même contenu : deux
sites qui fusionnent `v2.1.0`, à six mois d'intervalle, reçoivent exactement la même chose.

Ce que cela change en pratique :

- la commande de mise à jour peut être relancée sans crainte qu'une étiquette ait bougé entre
  deux `git fetch` ;
- comparer la version d'un site avec le journal suffit à savoir ce qu'il a reçu ;
- une erreur dans une version publiée se corrige par la version suivante, jamais en reprenant
  le même numéro.

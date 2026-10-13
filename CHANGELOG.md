# Journal des versions

Les numéros suivent le [versionnage sémantique](https://semver.org/lang/fr/), avec le sens
qu'il prend pour un socle recopié chez chaque client :

| Rang | Ce qui change | Ce que la mise à jour d'un site demande |
|---|---|---|
| **MAJEUR** | l'installation elle-même : emplacement de fichiers, configuration, données | une intervention manuelle — la marche à suivre et le retour arrière sont décrits sous la version |
| **MINEUR** | une capacité nouvelle | rien de plus que la fusion |
| **CORRECTIF** | une correction | rien de plus que la fusion |

La version installée est inscrite dans le fichier `VERSION`, à la racine. La procédure de
fusion est dans [docs/mise-a-jour-socle.md](docs/mise-a-jour-socle.md).

## 2.2.1 — 2026-10-12

Les six modèles de contenu déclaraient `'localize' => false` sur 42 champs. Cockpit ne lit pas
cette clé : celle qu'il lit pour un champ traduisible est `i18n`.

**`cockpit/models/*.model.php`** n'en portent plus. Le comportement ne change pas, la valeur
était `false` partout et le produit est en français seul. La clé laissait croire qu'un réglage
de traduction était en place, et se recopiait dans chaque nouveau champ écrit sur le modèle d'un
existant.

Rien à faire sur un site existant au-delà de la fusion.

## 2.2.0 — 2026-10-12

Un type de section se déclarait à quatre endroits de `cockpit/models/pages.model.php` : une
variable de condition, une option du champ `type`, les listes partagées des champs communs, et
ses propres champs. Ce fichier appartient au socle : tout site qui ajoutait un type entrait en
conflit à chaque mise à jour.

**Un type peut désormais se déclarer dans son propre fichier**, `cockpit/sections-client/` pour
un site, `cockpit/sections/` pour le socle. `cockpit/models/pages.model.php` n'est plus touché.

**`cockpit/lib/Registry.php`** découvre ces fichiers, les valide, écrit la condition de chaque
champ depuis le nom du type, et étend les listes partagées demandées par `communs`.
`bin/install-cockpit.php` installe le modèle assemblé.

**Les deux écritures cohabitent.** Un site monte de version sans reprendre aucun de ses types
existants, et déclare les nouveaux par fichier. Le client ne voit aucune différence. Le seul cas
refusé est un même type déclaré des deux côtés : l'installation s'arrête en nommant les deux
endroits.

L'écriture dans `pages.model.php` sera dépréciée dans une version à venir. Pour un type nouveau,
prendre le fichier de section.

**Les refus**, chacun nommant le fichier en cause : un type sans gabarit, un fichier dont le nom
ne correspond pas au type déclaré, un champ portant déjà une `condition`, un champ commun
inexistant, un type déclaré deux fois.

**Un type de section « Questions fréquentes » est livré**, le premier écrit au nouveau format.
Les questions sont des `details` : elles s'ouvrent sans JavaScript et leur contenu est dans le
HTML de la première réponse. Ses styles sont dans `site.css`, ses classes sont stables.

Rien à faire sur un site existant au-delà de la fusion, y compris s'il a déjà son propre type
« faq » : dans ce cas **la déclaration du site l'emporte**, celle du socle est laissée de côté,
et l'installation le dit sans s'arrêter. Le contenu saisi par le client n'est pas touché.
`docs/guide-integration.md` décrit comment prendre la version du socle à la place, le jour où on
le décide.

## 2.1.5 — 2026-10-11

La mise en ligne laissait trois choses à deviner, dont une a bloqué un site en service le
9 octobre 2026.

**`docs/mise-a-jour-socle.md`**, étape 6, dit que le modèle de contenu existe en deux
exemplaires et lequel fait foi. Modifier la copie installée de `public/admin/` puis lancer
l'installation annulait la modification, sans message.

**Le besoin de réseau de `install-cockpit.php --force`** est écrit : la commande télécharge
l'archive de Cockpit avant toute copie, et s'arrête sans rien faire sur un hébergement sans
accès sortant. Le contournement est donné, envoyer aussi `var/tmp/install/`, que `git pull`
n'apporte pas puisque `var/` n'est pas versionné.

**La mise en ligne sans ligne de commande** gagne le tableau de ce qu'il reste à purger à la
main, selon ce qui a changé : le cache de pages par un enregistrement dans l'administration,
`var/data/app.memory.sqlite` quand un modèle a changé, et `var/cache/twig` quand le transfert a
conservé les dates d'origine des fichiers.

Rien à faire sur un site existant au-delà de la fusion.

## 2.1.4 — 2026-10-11

La procédure de mise à jour écrite à la main était exacte une fois, puis fausse. Trois écarts
relevés sur une montée de version réelle le 8 octobre 2026.

**`docs/mise-a-jour-socle.md`**, étape 2, traite le refus
`would clobber existing tag` : il ne porte que sur l'étiquette nommée, les autres sont bien
arrivées, et `git fetch` sort malgré tout en erreur. Un tableau dit quoi faire selon ce que
l'étiquette locale désigne. La règle qui évite le cas est énoncée : le préfixe `v` appartient au
socle, une étiquette propre à un site ne commence jamais par `v`.

**Étape 3**, la branche porte désormais la version visée, `maj-socle-X.Y.Z`, le nom que pose
déjà `bin/maj-socle.php`. Le nom fixe précédent était pris dès la deuxième mise à jour, et le
document ne disait pas quoi faire du refus. Le cas est décrit.

**Les numéros de version des exemples** sont remplacés par `X.Y.Z`. Recopiés tels quels, ils
faisaient fusionner une version qui n'était ni celle du site ni celle visée.

Deux garde-fous ajoutés : la branche documentée est celle que construit la commande, et aucune
ligne de commande du document ne porte de numéro de version à recopier.

Rien à faire sur un site existant au-delà de la fusion.

## 2.1.3 — 2026-10-11

Deux caches pouvaient servir un contenu périmé sans jamais le signaler. Un travail juste,
monté sur un hébergement, restait sans effet et sans message.

**`public/index.php`** renseigne `auto_reload` pour Twig. Sans lui, l'option suivait `debug`,
donc fausse en production : les gabarits compilés dans `var/cache/twig` étaient servis
indéfiniment, sans que la date du gabarit source soit jamais comparée.

**`bin/purge-cache.php`** vide aussi `var/cache/twig`, par la nouvelle classe
`App\Cache\TemplateCache`. Aucune commande du socle ne le faisait. La mise à jour d'un site en
bénéficie sans changement, puisqu'elle appelle déjà cette purge.

**`bin/install-cockpit.php`** retire `var/data/app.memory.sqlite`, où Cockpit garde les modèles
de contenu analysés. Le cache des modules était purgé, celui des modèles non : un type de
section ajouté était copié au bon endroit sans que l'administration le montre. Constaté sur un
site en service le 9 octobre 2026.

**`docs/tests.md`** n'annonce plus le nombre de tests. Il avait déjà divergé une fois et rien ne
le reliait à la suite. Une ligne est ajoutée au tableau des garde-fous pour les trois caches.

Rien à faire sur un site existant au-delà de la fusion.

## 2.1.2 — 2026-10-06

Documentation seule. `docs/tests.md` annonçait 250 tests alors que la suite en exécute 255 : les
cinq refus de `bin/maj-socle.php`, arrivés en 2.1.0, n'étaient comptés ni décrits.

**`docs/tests.md`** annonce le bon nombre et ajoute une ligne au tableau des garde-fous pour la
mise à jour d'un site : arbre de travail non propre, version inconnue, version plus ancienne que
celle du site, version majeure.

Rien à faire sur un site existant au-delà de la fusion.

## 2.1.1 — 2026-10-06

Documentation seule. La procédure de mise à jour expliquait que l'étiquette est l'unité livrée,
sans dire qu'une étiquette publiée est désormais définitive.

**`docs/mise-a-jour-socle.md`** précise qu'une version publiée ne change plus : son étiquette
n'est ni déplaçable ni supprimable, et un numéro désigne toujours le même contenu. Deux sites
qui fusionnent la même version reçoivent donc exactement la même chose, quel que soit le moment.

Rien à faire sur un site existant au-delà de la fusion.

## 2.1.0 — 2026-10-05

Mettre un site à jour depuis le socle demandait six étapes manuelles, dont `install-cockpit.php
--force`, qui une fois oubliée laissait croire que la fusion n'avait rien changé.

**Une commande déroule la mise à jour.**

```bash
php bin/maj-socle.php                 # où en est le site, et ce qui existe
php bin/maj-socle.php --vers=2.0.13   # fusionne et enchaîne les commandes qui suivent
```

Sans argument, elle affiche la version installée, les versions plus récentes, et les entrées de
journal qui les séparent, lues depuis le socle. Avec `--vers`, elle crée une branche dédiée,
fusionne l'étiquette, puis lance `composer install`, `php bin/install-cockpit.php --force`,
`php bin/purge-cache.php` et les tests.

**Elle refuse de s'exécuter dans quatre cas** : arbre de travail non propre, version inconnue ou
mal formée, version plus ancienne que celle du site, et version majeure. Cette dernière demande
une intervention manuelle décrite sous la version dans ce journal, qu'une commande ne remplace
pas.

En cas de conflit, elle s'arrête et affiche les fichiers à reprendre. Elle ne pousse pas et ne
fusionne jamais dans `main`.

Les tests sont ignorés sur un site installé sans dépendances de développement, plutôt que de
faire échouer la mise à jour sur un outil délibérément absent.

`docs/mise-a-jour-socle.md` présente désormais la commande en tête, la procédure manuelle
restant dessous.

Rien à faire sur un site existant au-delà de la fusion.

## 2.0.13 — 2026-10-05

Documentation seule. Les deux addons arrivés en 2.0.8 et 2.0.9 n'étaient décrits que dans le
README, et le nombre de tests annoncé datait d'avant leur arrivée.

**`docs/architecture.md`** cite désormais `AdminClient` et `Passerelle` dans le tableau des
responsabilités, à côté d'`EditorGuards`.

**`docs/guide-client.md`** décrit ce que le client voit réellement : une administration en
français, les actions interdites qui ne lui sont plus proposées, et la pastille qui relie le
site à son administration.

**`docs/securite.md`** documente le cookie de la passerelle : ce qu'il contient, pourquoi il
n'est pas `httponly`, et dans quels cas la pastille ne s'affiche pas.

**`docs/tests.md`** annonce 250 tests, et son tableau couvre les trois familles ajoutées
depuis : administration du client, passerelle, version.

Rien à faire sur un site existant au-delà de la fusion.

## 2.0.12 — 2026-10-05

La pastille de la passerelle restait affichée sur le site alors qu'elle n'avait plus lieu
d'être : addon Passerelle désactivé ou retiré, ou session d'administration terminée.

**La pastille ne s'affiche plus que sur une réponse affirmative.** L'addon répond désormais
lui-même sur `/passerelle/etat`. Désactivé, il ne répond plus, et le site efface la pastille et
son cookie, que l'administration redépose à sa page suivante. Auparavant, le site interrogeait
`/check-session`, qui appartient à Cockpit et répondait même quand l'addon n'était plus là.

**La vérification fonctionne aussi en développement.** Site et administration y occupent deux
origines, et le navigateur bloquait la question : la pastille s'affichait alors par défaut, même
après la fin de la session. Seule l'adresse inscrite dans `SITE_URL` peut maintenant poser la
question depuis une autre origine.

**La question ne prolonge pas la session.** Elle suit les mêmes règles que le contrôle natif de
Cockpit : l'inactivité met fin à la session, et l'interrogation de fond ne la rafraîchit pas.

Deux tests ajoutés dans `GardeFous/PasserelleTest`.

Rien à faire sur un site existant au-delà de la fusion et de `php bin/install-cockpit.php
--force`, qui recopie l'addon modifié dans `public/admin/`.

Proposé par Pascal Fortunati (#53).

## 2.0.11 — 2026-10-05

`composer.json` déclarait `ext-curl` et `ext-json`, mais pas `ext-pdo_sqlite` ni `ext-gd`. Les
deux sont pourtant indispensables : la première porte la base de données de l'administration,
la seconde fabrique les copies allégées des images. Le README les présentait d'ailleurs comme
des prérequis.

**L'installation échoue désormais au bon endroit.** Sur un hébergement dépourvu de ces
extensions, `composer install` réussissait et l'échec survenait plus loin, à l'installation de
Cockpit ou au premier envoi d'image, avec un message qui ne nommait pas la cause. Composer
refuse maintenant d'installer et indique l'extension manquante.

Rien à faire sur un site existant : un site en service tourne forcément sur un hébergement qui
possède ces extensions, sans quoi il ne fonctionnerait pas.

## 2.0.10 — 2026-10-03

Le fichier `VERSION` était resté à 2.0.6 alors que les versions 2.0.7, 2.0.8 et 2.0.9 ont été
publiées. Un site qui fusionnait la dernière version affichait donc un numéro faux, et la
première étape de la mise à jour, la lecture de `VERSION`, ne disait pas la vérité.

**`VERSION` porte de nouveau le numéro publié.** Un test le vérifie désormais à chaque
exécution de la suite : `tests/GardeFous/VersionTest.php` compare `VERSION` à la première
entrée de ce journal et refuse un numéro qui ne suit pas la forme `X.Y.Z`.

**Les étiquettes se listent dans l'ordre des versions.** `docs/mise-a-jour-socle.md` indique
`git tag -l 'v*' --sort=v:refname`. Le tri alphabétique par défaut plaçait `v2.0.10` entre
`v2.0.1` et `v2.0.2`.

**L'étiquette `v2.0.6`, jamais posée, l'a été sur l'état qui portait ce numéro.**

Rien à faire sur un site existant au-delà de la fusion.

## 2.0.9 — 2026-10-02

Une passerelle facilite désormais la navigation entre le site public et l’administration pour la personne connectée, sans exposer l’adresse de l’administration aux visiteurs.

**Le site indique discrètement qu’une session d’administration est active.** Une pastille affichant l’avatar de la personne connectée et un point vert « en ligne » apparaît en bas à gauche du site. Un clic ouvre le tableau de bord de l’administration dans un nouvel onglet. La pastille n’est jamais affichée aux visiteurs et son interface s’adapte aux écrans mobiles.

**L’administration permet de revenir rapidement sur le site.** Les actions « Voir le site » et « Voir cette page » permettent de passer de Cockpit au site public sans avoir à rechercher manuellement la page correspondante.

**L’adresse de l’administration n’est pas inscrite dans les fichiers publics.** La passerelle repose sur un cookie de session déposé uniquement dans le navigateur authentifié et supprimé à la déconnexion. Lorsque le site et l’administration partagent la même origine, le site vérifie la session via `/check-session` avant d’afficher la passerelle.

**Le cache des pages publiques reste utilisable.** La pastille n’est pas intégrée au HTML mis en cache : elle est ajoutée dans le navigateur après vérification de la session. Une même page en cache peut ainsi être servie aux visiteurs comme aux utilisateurs connectés sans exposer d’information liée à l’administration.

Quatre tests dans `GardeFous/PasserelleTest` couvrent le fonctionnement et les garde-fous associés à cette passerelle.

Rien à faire sur un site existant au-delà de la fusion et des commandes habituelles d’après-fusion décrites dans `docs/mise-a-jour-socle.md`.

Proposé par Pascal Fortunati (#49).

## 2.0.8 — 2026-10-02

L'administration Cockpit est désormais francisée et n'affiche plus aux comptes non administrateurs les actions qu'ils ne peuvent pas utiliser.

**L'administration est disponible en français.** L'addon `AdminClient` fournit 530 traductions dans `i18n/fr.php`, chargées lorsque la langue de Cockpit est réglée sur `fr`. Les libellés conservent leur casse telle qu'elle est définie dans les traductions : « Nom du site », par exemple, n'est plus transformé en « Nom Du Site ».

**Les comptes non administrateurs ne voient plus les actions qui leur sont interdites.** « Modifier le modèle », l'objet JSON et le menu `⋮` des modèles sont masqués lorsque l'utilisateur n'est pas administrateur. Un menu `…` qui ne contient plus aucune action est également retiré. Cockpit affichait auparavant ces possibilités à tous les utilisateurs avant de répondre `Unauthorized request` lorsqu'un compte non autorisé tentait de les utiliser.

**Les nouveaux addons sont chargés immédiatement après leur installation.** `bin/install-cockpit.php` vide désormais `storage/cache/modules.cache.php`. Sur une installation déjà en service, un addon nouvellement ajouté pouvait jusque-là être correctement copié dans l'administration sans être détecté par Cockpit à cause du cache des modules.

Le README a également été mis à jour : la section « Bon à savoir » ne présente plus la francisation de l'administration comme un travail restant à faire.

Quatre tests couvrent ces changements dans `GardeFous/AdminClientTest`.

**La procédure de mise à jour du socle couvre les dépôts sans historique commun.** La documentation utilise désormais `--allow-unrelated-histories` lors de la fusion d'une version du socle. Cette option permet notamment d'effectuer la mise à jour lorsqu'un dépôt vient d'être initialisé et que son historique Git ne partage pas d'ancêtre avec celui du socle.

Rien à faire sur un site existant au-delà de la fusion et des commandes habituelles d'après-fusion décrites dans `docs/mise-a-jour-socle.md`.

Proposé par Pascal Fortunati (#44).

## 2.0.7 — 2026-10-02

Deux corrections concernant l'administration Cockpit et l'environnement de développement.

**La page « Identité du site » ne bloque plus le navigateur.** Deux scripts, `cockpit/addons/EditorGuards/assets/contraste-couleurs.js` et `cockpit/addons/Media/assets/poids-images.js`, utilisaient des `MutationObserver` dont les fonctions `annotate()` modifiaient elles-mêmes le DOM à chaque passage. Chaque modification relançait l'observateur et pouvait créer une boucle infinie, particulièrement sur cette page qui réunit des champs de couleur et d'images.

Le contrôle du contraste ne modifie désormais la classe ou le texte de son alerte que lorsque leur valeur change réellement. Le contrôle du poids des images conserve de la même manière l'alerte existante au lieu de la supprimer puis de la recréer à chaque passage. Les deux traitements sont ainsi idempotents et ne redéclenchent plus inutilement leurs observateurs.

**Les processus Composer ne sont plus interrompus par le timeout par défaut.** `composer.json` définit désormais `"process-timeout": 0`. Les commandes longues lancées par les scripts Composer peuvent ainsi rester actives sans être arrêtées automatiquement.

Rien à faire sur un site existant au-delà de la fusion et de `php bin/install-cockpit.php --force` pour recopier les addons modifiés dans `public/admin/`.

Proposé par Céline Devaux (#42).

## 2.0.6 — 2026-09-02

Trois corrections relevées en vérifiant une mise en ligne.

**`bin/message-test.php` envoyait chercher au mauvais endroit.** Quand l'e-mail ne partait pas,
le script nommait la cause puis concluait toujours par « Vérifier l'envoi de courrier de
l'hébergement » — y compris lorsque la cause était une adresse à renseigner dans l'identité du
site. `notify()` distingue désormais les deux situations par une clé `cause`, et le script
adapte sa conclusion. Le bouton de l'administration était déjà correct.

**`public/.htaccess` porte une explication.** La règle qui refuse `/admin/storage/` ne
s'applique pas tant que `public/admin/.htaccess` existe : mod_rewrite n'hérite jamais d'un
`.htaccess` parent. Elle joue exactement dans le cas pour lequel elle est écrite — ce fichier
manquant à l'envoi — mais paraît morte à qui la teste sur une installation complète. Le
commentaire dit de ne pas la retirer.

**`docs/mise-a-jour-socle.md` couvre la mise à jour par git sur le serveur.** L'étape 6 ne
décrivait que l'envoi de fichiers. Elle donne maintenant la marche à suivre en SSH, et le
réglage `git config pull.ff only` à poser une fois par hébergement, avec ce qu'il évite : un
commit de fusion propre au serveur, et des marqueurs de conflit écrits dans un fichier que PHP
est en train de servir.

Rien à faire sur un site existant au-delà de la fusion — `php bin/install-cockpit.php --force`
étant, comme toujours, nécessaire pour que l'addon modifié atteigne `public/admin/`.

## 2.0.5 — 2026-09-02

Documentation seule. Rien à faire sur un site existant au-delà de la fusion.

`docs/mise-a-jour-socle.md` restructuré : la mise à jour d'un site, qui se fait souvent, passe
en tête ; l'installation d'un nouveau site, qui se fait une fois, la suit. Les règles de
version, jusque-là en préambule, sont ramenées à l'endroit où l'on choisit une étiquette.

Une consigne était fausse et l'est restée depuis la 1.0.0 : `php bin/install-cockpit.php
--force` n'était présenté comme nécessaire que si la version de Cockpit avait changé. Il l'est
en réalité après **toute** fusion touchant `cockpit/` — configuration, modèles ou addons — car
`public/admin/` n'est pas versionné et le script s'arrête sans rien faire quand la version
installée correspond. La condition est supprimée : les quatre commandes d'après-fusion se
lancent désormais sans se poser de question.

Ajouts : comment lire la version installée, comment lister les étiquettes du socle sans celles
du site, le traitement des conflits lors d'une mise à jour courante, et la distinction entre la
mise à jour du dépôt et celle du site en ligne.

## 2.0.4 — 2026-09-01

Documentation seule. Rien à faire sur un site existant au-delà de la fusion.

Trois points perdus lors de l'allégement du README en 2.0.2, replacés dans le document qui les
concerne :

- `docs/architecture.md` — ce qui n'est jamais mis en cache, et le rôle de l'en-tête
  `X-Page-Cache`.
- `docs/guide-client.md` — les horaires sont un champ libre affiché tel quel, converti pour les
  moteurs de recherche seulement lorsqu'il se lit sans ambiguïté.
- `docs/guide-integration.md` — le nombre de tests annoncé, resté à 229.

## 2.0.3 — 2026-09-01

Le `robots.txt` public n'annonce plus l'emplacement de l'administration.

- `Disallow: /admin` retiré du `robots.txt` généré. Ce fichier est public et figure parmi les
  premières adresses demandées par un outil de reconnaissance ; la directive y donnait
  l'adresse du panneau, tout en interdisant l'exploration et non l'indexation.
- `public/.htaccess` envoie `X-Robots-Tag: noindex, nofollow` sur la zone d'administration.
  L'instruction n'est reçue que par le robot qui demande la page.

Rien à faire sur un site existant au-delà de la fusion et de la purge du cache, qui suit toute
fusion.

## 2.0.2 — 2026-09-01

Documentation seule. Rien à faire sur un site existant au-delà de la fusion.

- README ramené à l'installation et à l'orientation, de 353 à 174 lignes. Les sujets traités
  dans `docs/` y sont résumés en une ligne, suivie du renvoi.
- `docs/tests.md` — ce que couvre la suite de tests et ce que lit le contrôle avant mise en
  ligne, jusque-là dans le README.
- `LICENSE` — conditions d'utilisation, et licences des composants tiers installés par
  Composer et par `bin/install-cockpit.php`. Le dépôt n'en portait aucune.

## 2.0.1 — 2026-09-01

Documentation seule. Rien à faire sur un site existant au-delà de la fusion.

- Procédure de retour à la version précédente, sous 2.0.0 : annulation de la fusion,
  identification du commit à annuler, sort des adresses d'images déjà publiées.
- Le tableau des rangs, en tête de ce journal, annonce désormais que toute version majeure
  décrit aussi son retour arrière.

## 2.0.0 — 2026-09-01

Les médias quittent le dossier de l'administration.

### Pourquoi

Les pages publiques servaient leurs images depuis `/admin/storage/uploads/`. Chaque page
publiait ainsi l'emplacement du panneau d'administration, et l'arborescence des fichiers
désignait le CMS employé. Le reste de `/admin` n'était par ailleurs tenu fermé que par des
fichiers `.htaccess` cachés, qu'un envoi par FTP peut omettre sans que rien ne le signale.

### Ce qui change

- Les médias sont servis depuis `public/medias/`, à l'adresse `/medias`. Aucune page publique
  ne nomme plus `/admin`.
- `public/medias/.htaccess`, versionné et livré avec le site, ouvre le dossier à la
  consultation et refuse ce qui pourrait s'y exécuter.
- `public/.htaccess` refuse `/admin/storage/` — la règle est posée dans le fichier sans lequel
  le site ne fonctionne pas, et non plus seulement dans les fichiers cachés de `public/admin/`.
- `MEDIA_BASE_URL` vaut `/medias` en production, `http://localhost:8080/medias` en
  développement. L'administration s'en sert aussi pour ses aperçus.
- `bin/install-cockpit.php` crée `public/medias/variantes/` et ne crée plus
  `public/admin/storage/uploads/`.

### Mettre à jour un site existant

Après la fusion, sur l'hébergement :

1. Déplacer les fichiers.

   ```bash
   mv public/admin/storage/uploads/* public/medias/
   rm -rf public/admin/storage/uploads
   ```

   `*` ne reprend pas les fichiers cachés, et c'est voulu : l'ancien dossier avait sa propre
   règle d'accès, qui écraserait celle livrée dans `public/medias/`. Vérifier après coup que
   `public/medias/.htaccess` contient bien la ligne `Options -Indexes`.

2. Dans `.env`, remplacer la valeur par `MEDIA_BASE_URL=/medias`.

3. Vérifier que `public/medias/` est inscriptible par le serveur — `755`, parfois `775`.

4. Purger le cache : `php bin/purge-cache.php`, ou enregistrer n'importe quel contenu depuis
   l'administration.

5. Contrôler.

   ```bash
   curl -s https://domaine-du-client.tld/ | grep -c 'admin/storage'    # 0
   curl -sI https://domaine-du-client.tld/admin/storage/ | head -1     # 403
   ```

Les enregistrements d'images portent un chemin relatif, jamais une adresse : **aucune
modification de la base de données n'est nécessaire.**

### Revenir à la version précédente

Le retour déplace des fichiers et ne touche pas davantage à la base de données. À exécuter dans
cet ordre, sur l'hébergement.

1. Annuler la fusion dans le dépôt du site.

   ```bash
   git revert -m 1 <fusion>
   ```

   > [!NOTE]
   > **`<fusion>`** est l'empreinte du commit de **fusion** qui a introduit la version 2.0.0
   > dans le dépôt du site — jamais celle d'un commit du socle.
   >
   > Le retrouver :
   >
   > ```bash
   > git log --oneline --first-parent -5
   > ```
   >
   > Le confirmer avant d'agir. Au commit cherché, `VERSION` vaut `2.0.0` ; chez son premier
   > parent, `1.0.0` :
   >
   > ```bash
   > git show <fusion>:VERSION      # 2.0.0
   > git show <fusion>^1:VERSION    # 1.0.0
   > ```
   >
   > Ce contrôle ne dépend pas du message de commit, qui diffère selon que la fusion vient
   > d'une demande de tirage ou d'un `git merge v2.0.0`.
   >
   > `-m 1` désigne ce premier parent : la ligne du site, celle qu'il faut conserver.
   >
   > `git log --merges -- VERSION` ne renvoie rien — Git écarte les fusions quand l'historique
   > est restreint à un chemin. Passer par `--first-parent`.

2. Recréer le dossier d'origine et sa règle d'accès, écrite par le script d'installation et
   absente du dépôt.

   ```bash
   php bin/install-cockpit.php --force
   ```

3. Replacer les fichiers.

   ```bash
   mv public/medias/2026 public/medias/variantes public/admin/storage/uploads/
   ```

   Nommer les dossiers un à un plutôt qu'écrire `public/medias/*` : `public/medias/.htaccess`
   doit rester où il est. `ls public/medias` donne la liste quand l'administration a servi plus
   d'une année.

4. Dans `.env`, remettre `MEDIA_BASE_URL=/admin/storage/uploads`.

5. Purger le cache.

   ```bash
   php bin/purge-cache.php
   ```

6. Contrôler.

   ```bash
   curl -s https://domaine-du-client.tld/ | grep -c '/medias/'        # 0
   curl -s https://domaine-du-client.tld/ | grep -c 'admin/storage'   # au moins 1
   ```

> [!IMPORTANT]
> **Adresses déjà publiées.** Les images servies sous `/medias` pendant la période 2.0.0
> répondent 404 après le retour. Sur un site en ligne depuis plusieurs semaines, poser la
> redirection dans `public/.htaccess` **avant** de purger, au-dessus des règles du cache :
>
> ```apache
> RewriteRule ^medias/(.*)$ /admin/storage/uploads/$1 [R=301,L]
> ```

Pour repasser en 2.0.0 plus tard, annuler ce retour — `git revert <retour>` — plutôt que
fusionner de nouveau : Git tient la fusion pour déjà faite et n'apporterait rien.

## 1.0.0 — 2026-09-01

Première version numérotée. Elle correspond au socle tel qu'il est déployé et vérifié sur un
hébergement mutualisé.

### Le site public

- Rendu côté serveur en PHP 8.3 avec Twig : le contenu est présent dans le HTML de la première
  réponse.
- Cache de pages écrit en HTML statique et servi par Apache sans démarrer PHP, purgé à la
  publication.
- Pages, actualités, mentions légales et politique de confidentialité, menu, plan du site et
  `robots.txt` générés.
- Données structurées `LocalBusiness`, métadonnées sociales et adresse canonique par page.
- Formulaire de contact avec consentement explicite et garde anti-spam.

### L'administration

- Cockpit 2.14.0, installé par `bin/install-cockpit.php` depuis l'archive officielle dont
  l'empreinte SHA-256 est vérifiée. La base vit hors de la racine web.
- Collections et champs prédéfinis, éditeur limité aux niveaux de titre du contenu.
- Contraste des couleurs calculé et signalé à l'édition.
- Copies allégées des images générées à l'envoi, avec point focal et description obligatoire.

### Les outils

- `bin/verifier-accessibilite.php` : contrôle du HTML réellement servi — contrastes, structure
  des titres, adresses canoniques, feuilles de style.
- `bin/purge-cache.php`, `bin/generer-variantes.php`, `bin/message-test.php`,
  `bin/cockpit-cle.php`.
- Suite de tests : 234 tests, 486 assertions.

### La documentation

Installation sur mutualisé, sécurité de l'installation, développement local, intégration d'une
maquette, guide du client, médias, formulaire de contact, prérequis de Cockpit, création et
mise à jour d'un site depuis le socle.

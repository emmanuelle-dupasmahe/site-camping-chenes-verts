# Sécurité

Ce dépôt est un socle recopié chez des clients. Une faille ici n'est pas une faille sur un
site : elle est sur tous ceux qui ont installé la version concernée.

## Signaler une faille

**N'ouvrez pas d'issue publique.** Une issue publie la faille avant qu'un correctif existe, et
chaque site installé reste exposé pendant ce temps.

Passez par le signalement privé de GitHub, onglet **Security** du dépôt, puis **Report a
vulnerability**. Le fil est fermé : seuls les mainteneurs et vous le voyez.

Ce qui aide à traiter vite :

- la version du socle concernée, c'est-à-dire le contenu du fichier `VERSION` ;
- les étapes pour reproduire depuis une installation neuve ;
- ce que la faille permet d'atteindre : contenu, compte d'administration, fichiers, hébergement.

## Ce à quoi vous pouvez vous attendre

| | |
|---|---|
| Première réponse | sous 5 jours ouvrés |
| Point d'avancement | tous les 15 jours tant que le fil est ouvert |
| Publication | après le correctif, jamais avant |

Si vous souhaitez être cité dans les notes de la version qui corrige, dites-le : la mention est
donnée sur demande, pas par défaut.

## Versions suivies

Seule la dernière version publiée reçoit des correctifs. Un site plus ancien se met à jour en
fusionnant la version visée, ce que décrit
[docs/mise-a-jour-socle.md](docs/mise-a-jour-socle.md) ; les versions intermédiaires sont
reprises au passage.

Une version publiée ne change jamais : son étiquette n'est ni déplaçable ni supprimable, et un
numéro désigne toujours le même contenu. Un correctif de sécurité est donc **toujours une
nouvelle version**, jamais une reprise de l'ancienne.

## Les correctifs de sécurité sont distribués à tout le parc

Un correctif de sécurité est publié pour tous les sites, sans condition.

## Ce qui n'est pas une faille de ce dépôt

Le socle est installé sur l'hébergement de chaque client, qui porte sa propre configuration.
Les points suivants se traitent avec l'hébergeur, pas ici :

- une version de PHP ou une extension absente, hors des prérequis de
  [docs/cockpit-prerequis.md](docs/cockpit-prerequis.md) ;
- un certificat expiré, une redirection HTTPS absente ;
- un mot de passe d'administration faible choisi sur un site, le socle imposant déjà longueur
  et variété ;
- un dossier ouvert en écriture au-delà de ce que décrit
  [docs/installation-mutualise.md](docs/installation-mutualise.md).

En cas de doute, signalez : un faux positif coûte moins cher qu'une faille gardée pour soi.

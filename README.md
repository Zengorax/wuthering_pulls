# Wuthering Pulls

Application Symfony locale pour suivre vos invocations de Wuthering Waves par période majeure et par version : totaux réels et estimés, moyennes, 50/50, jauges de pitié, graphiques, stocks et projection de ressources.

## Prérequis

Seul **Docker** (avec Docker Compose) est nécessaire : PHP 8.4, Composer et PostgreSQL tournent dans des conteneurs, rien n’est à installer sur la machine.

## Démarrer

```bash
docker compose up -d --build
```

Au premier lancement, le conteneur `php` installe les dépendances Composer et applique les migrations avant de démarrer le serveur. Ouvrez ensuite <http://localhost:8000>. L’application ne requiert aucun compte : elle est destinée à un usage local.

Toute la configuration Docker est pilotée par le fichier `.env` (versions et identifiants PostgreSQL, port exposé `APP_PORT`, URL de base de données construite à partir de ces variables). Pour la personnaliser, surchargez-la dans `.env.local` (non versionné).

### Commandes utiles

```bash
docker compose exec php php bin/console about          # état de l’application
docker compose exec php composer test                  # migrations de la base de test + PHPUnit
docker compose logs -f php                             # journal du serveur
docker compose down                                    # arrêt (les données restent dans le volume database_data)
```

Les dépendances Composer (`vendor/`) et le cache (`var/`) vivent dans des volumes Docker (le système de fichiers monté depuis Windows est trop lent pour PHP) : ils n’apparaissent donc pas dans le dossier du projet. La console `bin/console` et Composer s’utilisent toujours via `docker compose exec php …`. La base est aussi accessible depuis la machine sur `127.0.0.1:5432` (voir `compose.override.yaml`).

## Saisie de l’historique

1. Créez une période majeure, par exemple `3.X`, puis ses versions (`3.0`, `3.1`…) avec leurs dates facultatives.
2. Depuis le tableau de bord, saisissez une cible : personnage, rareté (5★ ou 4★), élément, pulls personnage et arme, résultat du 50/50 et case **Estimation** si nécessaire. Saisissez `0` pour une cible non invoquée.
3. **Stock & estimations** (modifiable en place) enregistre les stocks de début et de fin de version (lunite, astrites, pulls) et une équivalence au format `S9R8`. Le gain implicite de la version et la projection de la prochaine version en sont déduits.
4. Ajoutez facultativement les origines de ressources depuis la période majeure.

Chaque période, version, ligne d’invocation et origine de ressources peut être modifiée ou supprimée (avec confirmation).

## Portraits des personnages

Les images sont trouvées par le nom du personnage (minuscules, tirets : `lynae`, `xiangli-yao`) :

- `assets/characters/art/<nom>.webp` : art haute définition, utilisé en grand dans l’animation de pull 5★ ;
- `assets/characters/thumb/<nom>.webp` : vignette de ce même art, utilisée sur les cartes d’historique (cadrage sur le visage) ;
- `assets/characters/<nom>.png` : petite icône (150 px), utilisée en secours quand il n’y a pas d’art.

Pour ajouter un art au format PNG, déposez-le dans `assets/characters/art/` puis lancez `docker compose exec php php bin/console app:art:optimize --remove-originals` : il génère l’art WebP et sa vignette. Sans image, l’interface garde son décor par défaut ; pour ajouter ou remplacer une image, déposez le fichier puis lancez `docker compose restart php`. Les visuels fournis viennent de <https://wuthering.gg> ; ils appartiennent à Kuro Games et sont destinés à un usage personnel local, sans redistribution. Quelques personnages récents n’ont pas d’art sur le site et n’ont donc que leur icône.

## Règles de calcul

- 160 astrites = 1 pull ; lunite et astrites sont comptées 1 pour 1.
- Les **totaux** incluent les estimations, mais restent distingués des pulls réels ; les **moyennes** et le taux de 50/50 n’utilisent que les lignes réelles. La moyenne personnage ne compte que les 5★ avec au moins 1 pull ; la moyenne arme exclut les cibles à 0 pull.
- Les constantes de jeu (pitié à 80, pitié douce ≈ 66, moyenne attendue ≈ 62,5 pulls par 5★, 1,5 5★ par personnage vedette) sont regroupées dans `src/Statistics/PullRules.php` : ce sont des approximations communautaires à ajuster si le jeu évolue.

## Architecture

| Élément | Rôle |
| --- | --- |
| `src/Statistics/DashboardBuilder` | Calcule totaux, moyennes, distribution, graphique et projection à partir des périodes chargées |
| `MajorVersionRepository::findAllWithHistory()` | Charge tout l’historique en 2 requêtes (pas de N+1) |
| `src/Controller/*Controller` | Un contrôleur par ressource : tableau de bord, périodes, versions, cibles, ressources |
| `templates/form/theme.html.twig` | Thème de formulaire (champs, segments, interrupteurs) |
| `assets/controllers/*_controller.js` | Contrôleurs Stimulus (graphique SVG, animations, thème d’élément…) |
| `assets/styles/*.css` | Design « Résonance » : tokens, mise en page, composants, animations |
| `translations/` | Textes de l’interface (français, format ICU) |

Le mode par défaut (`APP_DEBUG=0` dans `.env`) privilégie la vitesse sous Docker ; pour développer avec rechargement automatique du code, mettez `APP_DEBUG=1` dans `.env.local`, sinon relancez `docker compose restart php` après une modification du code, des gabarits, du CSS ou du JavaScript : le cache est reconstruit et les fichiers du dossier `assets/` recompilés au démarrage.

Les polices (Cinzel, Rajdhani, Inter) sont hébergées localement dans `assets/fonts/` : l’application fonctionne hors ligne.

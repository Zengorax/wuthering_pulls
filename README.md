# Wuthering Pulls

Application Symfony locale pour suivre les invocations de Wuthering Waves par période et par version.

## Prérequis

- PHP 8.4 ou supérieur et les extensions requises par Symfony/Doctrine
- Composer
- Node.js et npm (compilation locale de Tailwind CSS)
- Docker Compose et PostgreSQL

## Démarrer l’application

```bash
composer install
npm install
npm run build:css
docker compose up -d database
php bin/console doctrine:migrations:migrate --no-interaction
symfony serve
```

Ouvrez ensuite l’URL locale indiquée par Symfony CLI. L’application ne requiert pas de compte utilisateur; elle est destinée à un usage local.

## Saisie de l’historique

1. Créez une période majeure, par exemple `3.X`.
2. Ajoutez ses versions (`3.0`, `3.1`, etc.).
3. Depuis le tableau de bord, saisissez une cible, les pulls personnage et arme, indiquez si le 50/50 personnage a été gagné ou perdu, puis cochez **Estimation** si nécessaire. Le résultat du 50/50 reste facultatif. Saisissez `0` pour une cible non invoquée.
4. Le lien **Stock & estimations** permet de saisir les stocks de début et de fin de version, ainsi qu’une équivalence telle que `S9R8`.
5. Ajoutez facultativement les origines des ressources depuis la période majeure.

Les totaux de pulls sont calculés à partir des lignes enregistrées; l’équivalent en astrites utilise 160 astrites par pull. Les moyennes personnage et arme sont calculées séparément à partir des lignes réelles uniquement et excluent les catégories avec 0 pull. Aucune donnée de démonstration n’est préchargée.

Après une modification des classes Tailwind dans Twig/JavaScript, régénérez sa feuille locale avec `npm run build:css`.

#!/bin/sh
# Prépare l'application au démarrage du conteneur, puis lance la commande demandée.
set -e

cd /app

if [ ! -f vendor/autoload.php ]; then
    echo "[app] Installation des dépendances Composer…"
    composer install --no-interaction --prefer-dist
fi

if [ "${APP_BOOTSTRAP:-1}" = "1" ] && [ "${APP_ENV:-dev}" != "prod" ]; then
    echo "[app] Application des migrations…"
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

if [ "${APP_DEBUG:-0}" = "0" ] && [ "${APP_ENV:-dev}" != "prod" ]; then
    echo "[app] Préparation du cache (mode rapide, APP_DEBUG=0)…"
    # Repartir d'un cache propre : évite les conflits si un autre conteneur (tests…) utilise le même volume.
    rm -rf var/cache/dev
    php bin/console cache:clear --no-interaction
    # Sans debug, AssetMapper ne sert plus les fichiers à la volée : on les compile (public/assets, ignoré par git).
    php bin/console asset-map:compile --no-interaction
fi

exec "$@"

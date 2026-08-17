#!/bin/sh
set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "ERREUR: APP_KEY doit être configurée dans les variables d'environnement Dokploy." >&2
    exit 1
fi

mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data bootstrap/cache storage

# Les credentials restent en mémoire dans les variables du conteneur : aucun
# fichier JSON de compte de service n'est créé sur le serveur.
if [ -n "${FIREBASE_CREDENTIALS_BASE64:-}" ] && [ -z "${FIREBASE_CREDENTIALS:-}" ]; then
    if ! FIREBASE_CREDENTIALS="$(printf '%s' "$FIREBASE_CREDENTIALS_BASE64" | base64 -d)"; then
        echo "ERREUR: FIREBASE_CREDENTIALS_BASE64 n'est pas une valeur base64 valide." >&2
        exit 1
    fi
    export FIREBASE_CREDENTIALS
fi

if [ -n "${FIREBASE_CREDENTIALS:-}" ] && [ -z "${GOOGLE_APPLICATION_CREDENTIALS:-}" ]; then
    export GOOGLE_APPLICATION_CREDENTIALS="$FIREBASE_CREDENTIALS"
fi

if [ ! -L public/storage ]; then
    rm -rf public/storage
    php artisan storage:link --force
fi

# Les variables Dokploy sont disponibles seulement au démarrage du conteneur.
# Le cache Laravel doit donc être créé ici, et non pendant le build de l'image.
php artisan config:clear
php artisan config:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    attempt=1
    max_attempts=10

    until php artisan migrate --force; do
        if [ "$attempt" -ge "$max_attempts" ]; then
            echo "ERREUR: les migrations ont échoué après $max_attempts tentatives." >&2
            exit 1
        fi

        echo "Base de données indisponible, nouvelle tentative dans 3 secondes ($attempt/$max_attempts)..." >&2
        attempt=$((attempt + 1))
        sleep 3
    done
fi

if [ "${SEED_ADMIN:-false}" = "true" ]; then
    php artisan db:seed --class=AdminUserSeeder --force
fi

exec "$@"

#!/bin/bash

MOODLE_DIR="/var/www/moodle"
MOODLE_DATA="/var/www/moodledata"
CONFIG_FILE="${MOODLE_DIR}/config.php"

DB_HOST="${MOODLE_DB_HOST:-mariadb}"
DB_PORT="${MOODLE_DB_PORT:-3306}"
DB_NAME="${MOODLE_DB_NAME:-moodle}"
DB_USER="${MOODLE_DB_USER:-moodle}"
DB_PASS="${MOODLE_DB_PASS:-moodlepass}"
MOODLE_URL="${MOODLE_URL:-http://localhost:8080}"
ADMIN_USER="${MOODLE_ADMIN_USER:-admin}"
ADMIN_PASS="${MOODLE_ADMIN_PASS:-Admin1234!}"
ADMIN_EMAIL="${MOODLE_ADMIN_EMAIL:-admin@codelab.local}"
SITE_NAME="${MOODLE_SITE_NAME:-CodeLab}"

# Esperar a que MariaDB acepte conexiones TCP (sin necesitar cliente MySQL)
echo "Esperando a que la base de datos este lista en ${DB_HOST}:${DB_PORT}..."
MAX=60
COUNT=0
until (echo > /dev/tcp/${DB_HOST}/${DB_PORT}) 2>/dev/null; do
    COUNT=$((COUNT+1))
    if [ $COUNT -ge $MAX ]; then
        echo "ERROR: La base de datos no respondio despues de ${MAX} intentos."
        exit 1
    fi
    echo "  Intento ${COUNT}/${MAX}..."
    sleep 3
done
echo "Base de datos disponible. Esperando 5 segundos adicionales para inicializacion..."
sleep 5

# Instalar Moodle solo si config.php no existe
if [ ! -f "${CONFIG_FILE}" ]; then
    echo ""
    echo "Primera instalacion de Moodle (puede tardar 3-5 minutos)..."
    echo ""

    php "${MOODLE_DIR}/admin/cli/install.php" \
        --chmod=2770 \
        --lang=es \
        --wwwroot="${MOODLE_URL}" \
        --dataroot="${MOODLE_DATA}" \
        --dbtype=mariadb \
        --dbhost="${DB_HOST}" \
        --dbport="${DB_PORT}" \
        --dbname="${DB_NAME}" \
        --dbuser="${DB_USER}" \
        --dbpass="${DB_PASS}" \
        --fullname="${SITE_NAME}" \
        --shortname="codelab" \
        --adminuser="${ADMIN_USER}" \
        --adminpass="${ADMIN_PASS}" \
        --adminemail="${ADMIN_EMAIL}" \
        --non-interactive \
        --agree-license || true

    echo ""
    echo "==========================================="
    echo "  Moodle instalado correctamente!"
    echo "  URL:        ${MOODLE_URL}"
    echo "  Usuario:    ${ADMIN_USER}"
    echo "  Contrasena: ${ADMIN_PASS}"
    echo "==========================================="
else
    echo "Moodle ya instalado. Verificando actualizaciones de plugins..."
    php "${MOODLE_DIR}/admin/cli/upgrade.php" --non-interactive 2>/dev/null || true
fi

# Siempre corregir permisos de config.php al final, sin importar qué pasó antes
if [ -f "${CONFIG_FILE}" ]; then
    chown www-data:www-data "${CONFIG_FILE}"
    chmod 644 "${CONFIG_FILE}"
fi
chown -R www-data:www-data "${MOODLE_DATA}" 2>/dev/null || true

exec "$@"

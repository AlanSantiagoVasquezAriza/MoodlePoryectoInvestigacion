# ============================================================
# Imagen Docker para Moodle con el plugin CodeLab
# Usa la imagen oficial de Moodle HQ que ya tiene todas
# las extensiones PHP necesarias pre-instaladas.
# ============================================================
FROM moodlehq/moodle-php-apache:8.2

# ── Configuración de PHP para Moodle ─────────────────────
RUN { \
    echo 'max_input_vars = 5000'; \
    echo 'max_execution_time = 300'; \
    echo 'upload_max_filesize = 128M'; \
    echo 'post_max_size = 128M'; \
    echo 'memory_limit = 256M'; \
    echo 'date.timezone = America/Bogota'; \
} > /usr/local/etc/php/conf.d/moodle-codelab.ini

# ── Herramientas adicionales ──────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    && rm -rf /var/lib/apt/lists/*

# ── Descargar Moodle 4.5 ─────────────────────────────────
RUN echo "Descargando Moodle 4.5..." \
    && curl -L --retry 3 --progress-bar \
       "https://download.moodle.org/download.php/direct/stable405/moodle-latest-405.tgz" \
       -o /tmp/moodle.tgz \
    && echo "Extrayendo..." \
    && tar -xzf /tmp/moodle.tgz -C /var/www/ \
    && rm /tmp/moodle.tgz \
    && echo "Moodle descargado."

# ── Configurar Apache para Moodle ────────────────────────
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/moodle\n\
    <Directory /var/www/moodle>\n\
        Options FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite 2>/dev/null || true

# ── Directorio de datos y permisos ───────────────────────
RUN mkdir -p /var/www/moodledata \
    && chown -R www-data:www-data /var/www/moodle \
    && chown -R www-data:www-data /var/www/moodledata \
    && chmod -R 755 /var/www/moodle \
    && chmod -R 770 /var/www/moodledata

# ── Copiar el plugin CodeLab ──────────────────────────────
COPY mod/codelab /var/www/moodle/mod/codelab
RUN chown -R www-data:www-data /var/www/moodle/mod/codelab

# ── Script de inicio (convertir saltos de línea Windows → Unix) ──
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]

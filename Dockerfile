FROM php:8.1-apache

# SQLite desteğini etkinleştir
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Apache mod_rewrite'ı etkinleştir
RUN a2enmod rewrite

# Apache DocumentRoot'u ayarla
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Çalışma dizinini ayarla
WORKDIR /var/www/html

# Proje dosyalarını kopyala
COPY . /var/www/html/

# Gerekli izinleri ver
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/database \
    && chown -R www-data:www-data /var/www/html/database \
    && chmod -R 777 /var/www/html/database

# Apache kullanıcısına geç
USER www-data

# Tekrar root'a dön (Apache'nin başlaması için)
USER root

# Apache'yi başlat
EXPOSE 80
CMD ["apache2-foreground"]
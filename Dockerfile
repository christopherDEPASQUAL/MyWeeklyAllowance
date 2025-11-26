# Étape 1 : récupérer Composer depuis l'image officielle
FROM composer:2 AS composer_stage

# Étape 2 : image d'exécution PHP + Apache
FROM php:8.3-apache

# On copie le binaire de Composer depuis l'étape 1
COPY --from=composer_stage /usr/bin/composer /usr/bin/composer

# Variables d'environnement pour Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/app/public

# Installer quelques dépendances systèmes + extensions PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mysqli \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Reconfigurer Apache pour pointer sur /var/www/app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf

# Dossier de travail de l'application
WORKDIR /var/www/app

# (Optionnel) Copier composer.json pour un éventuel composer install pendant le build.
# Pour un environnement dev, on préfère souvent lancer composer dans le container à la main.
COPY composer.json composer.lock* ./

# Exposer le port 80 (déjà fait par l'image php:apache mais on laisse pour clarté)
EXPOSE 80

# 1. Construire et lancer les conteneurs
docker compose up -d --build

# 2. Voir les logs de l'app
docker compose logs -f app

# 3. Installer les dépendances PHP (dans le conteneur)
docker compose exec app composer install

# 4. Lancer les tests PHPUnit
- **Dossier complet** : docker compose exec app ./vendor/bin/phpunit --bootstrap vendor/autoload.php tests
- **Fichier ciblé** : docker compose exec app ./vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Domain/WeekTest.php // Exemple pour le fichier weekTest.php
- **Couverture (Xdebug)** : docker compose exec app env XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text

# 5. Arrêter et supprimer les conteneurs
docker compose down

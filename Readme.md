docker-compose exec php vendor/bin/phpcs
docker-compose exec php vendor/bin/phpcbf
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:database:drop --force
docker compose exec php composer require symfony/webpack-encore-bundle
По официальной установке Encore в Symfony нужны две части: PHP-пакет через Composer и JavaScript-зависимости через yarn install
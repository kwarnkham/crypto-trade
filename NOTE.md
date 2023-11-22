1. make env file
2. generate app key and migrate the database
3. seed the db in non prod env

php artisan down
git pull
composer install
php artisan queue:restart
php artisan optimize
nginx -s reload
php artisan up

docker run --rm -p 6001:6001 quay.io/soketi/soketi:1.0-16-debian

in production

1. review the api key
2. slack channel
3. need to restart supervisorctl if env changed

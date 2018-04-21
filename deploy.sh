mkdir temp
mkdir temp/templates_c
mkdir temp/cache
mkdir temp/thumbnails
mkdir temp/mails
mkdir temp/attachments
mkdir temp/nautaclient
mkdir temp/capcha
mkdir services
mkdir logs
mkdir public/ads
mkdir public/profile
mkdir public/raffle
mkdir public/tienda
mkdir public/temp
mkdir public/download
mkdir public/products
mkdir public/recetas
touch logs/error.log
touch logs/access.log
touch logs/badqueries.log
touch logs/webhook.log
touch logs/crawler.log
touch logs/api.log
touch logs/amazon.log
chmod -R 777 temp
chmod -R 777 logs
chmod 777 services
chmod 777 public/ads
chmod 777 public/profile
chmod 777 public/raffle
chmod 777 public/tienda
chmod 777 public/temp
chmod 777 public/download
chmod 777 public/products
chmod 777 public/recetas
chown www-data logs/*.log
chgrp www-data logs/*.log

a2enmod rewrite
a2enmod expires
apt-get install php-curl
apt-get update
add-apt-repository ppa:ondrej/php
apt-get install php-mailparse
apt-get install php-zip
apt-get install php7.0-zip

service apache2 restart

php composer.phar install

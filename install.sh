./deploy.sh
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

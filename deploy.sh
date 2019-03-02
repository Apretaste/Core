#!/bin/bash
mkdir keys
mkdir temp
mkdir temp/templates_c
mkdir temp/thumbnails
mkdir temp/mails
mkdir temp/attachments
mkdir temp/attach_images
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
chmod 777 keys
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
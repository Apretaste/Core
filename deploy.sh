mkdir temp
mkdir temp/templates_c
mkdir temp/cache
mkdir temp/thumbnails
mkdir temp/mails
mkdir temp/attachments
mkdir services
mkdir logs
mkdir public/ads
mkdir public/profile
mkdir public/raffle
mkdir public/tienda
mkdir public/temp
mkdir public/download
touch logs/error.log
touch logs/access.log
touch logs/badqueries.log
touch logs/mailgun.log
touch logs/mandrill.log
touch logs/webhook.log
touch logs/crawler.log
touch logs/campaigns.log
touch logs/app.log
touch logs/api.log
chmod -R 777 temp
chmod -R 777 logs
chmod 777 services
chmod 777 public/ads
chmod 777 public/profile
chmod 777 public/raffle
chmod 777 public/tienda
chmod 777 public/temp
chmod 777 public/download

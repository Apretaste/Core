cd /var/www/Core
cd /var/www/Apretaste
mv temp delete
./deploy.sh
find delete -type f -delete
rm -rf delete

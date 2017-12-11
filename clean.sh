cd /var/www/Core
cd /var/www/Apretaste
mv temp delete
./deploy.sh
cd delete/
find . -type f -print -delete
cd ..
rm -rfv delete

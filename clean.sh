cd /var/www/Core
cd /var/www/Apretaste
mv temp delete$RANDOM
./deploy.sh
tar -cf delete.tar --remove-files delete
rm -rf delete.tar

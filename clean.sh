#!/bin/bash
STR="delete"$RANDOM
cd /var/www/Core
cd /var/www/Apretaste
mv temp $STR
./deploy.sh
tar -cf $STR.tar --remove-files $STR
rm -rf $STR.tar

#!/bin/bash
set -e

cd nextcloud-app/peppolnext
npm install
cd ../..

./scripts/gencerts.sh
./scripts/rebuild.sh
./scripts/transportp12.sh
docker pull mariadb
docker pull jlesage/firefox:v1.17.1

export PEPPOL_PHP_DIR=`pwd`
docker run -w /var/www/html/apps/peppolnext -v $PEPPOL_PHP_DIR:/var/www/html/apps/peppol-php nc2 make composer
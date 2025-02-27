#!/bin/bash
set -e
if [[ -z "$PEPPOL_PHP_DIR" ]]; then
    echo "Must provide PEPPOL_PHP_DIR in environment" 1>&2
    exit 1
fi
echo Mounting peppol-php code repo from the host, folder: $PEPPOL_PHP_DIR 
docker run -d --network=testnet -e MARIADB_ROOT_PASSWORD=eilohtho9oTahsuongeeTh7reedahPo1Ohwi3aek --name=maria1.docker mariadb --transaction-isolation=READ-COMMITTED --binlog-format=ROW --innodb-file-per-table=1 --skip-innodb-read-only-compressed
docker run -d --network=testnet --name=nc1.docker -v $PEPPOL_PHP_DIR:/var/www/html/apps/peppol-php nc1
docker exec -w /var/www/html/apps/peppolnext nc1.docker make composer

echo "sleeping for 15 seconds"
sleep 15
echo "slept for 15 seconds"

docker exec -it -e DBHOST=maria1.docker -e USER=einstein -e PASS=relativity  -u www-data nc1.docker sh /init.sh

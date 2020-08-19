#!/bin/bash

set -e # exit when any command fails

rm -f deploy/archive.zip

composer install --no-dev --optimize-autoloader

# requires 7-Zip to be installed and directory added to path
7z a deploy/archive.zip @deploy/include_files.txt -xr!.git

scp deploy/archive.zip root@theodorejb.me:~/php-stv.zip
scp deploy/deploy.sh root@theodorejb.me:~/deploy_php-stv.sh

echo "You can now connect to the server and run bash ~/deploy_php-stv.sh"

#!/bin/bash
set -e
cd /var/www/html
php bin/migrate.php
exec apache2-foreground

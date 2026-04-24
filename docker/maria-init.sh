#!/usr/bin/env bash

# Add an external user for other containers to use the db
mariadb -u root -p"$MARIADB_ROOT_PASSWORD" <<-EOSQL
    CREATE USER IF NOT EXISTS '$MARIADB_USER'@'%' IDENTIFIED BY '$MARIADB_PASSWORD';
    GRANT ALL PRIVILEGES ON *.* TO '$MARIADB_USER'@'%' WITH GRANT OPTION;
    FLUSH PRIVILEGES;
EOSQL
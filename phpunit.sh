#!/bin/bash

export CODECLIMATE_REPO_TOKEN=20d76bff65cdc35b5d3450847800ecc6d5646842b55fe9b433cee1da0fa012ba

echo ""
echo "================================================================"
echo ""
echo "   -- PHPUnit tests for Railpage --"
echo ""
echo "Setting up database:"
echo " - Dropping old database"
mysql -h localhost -e 'drop database sparta_unittest;'
echo " - Importing database structure"
mysql -h localhost < tests/data/travis/db.structure.sql
echo ""
echo "Running phpUnit ::"
time /usr/local/bin/phpunit --coverage-clover build/logs/clover.xml
times

if [ -e /usr/local/php7/bin/phpzzlol ]; then
	echo ""
	echo ""
    echo "   -- Resetting test environment for php7 --"
	echo ""
	echo ""

    echo "Setting up database:"
    echo " - Dropping old database"
    mysql -h localhost -e 'drop database sparta_unittest;'
    echo " - Importing database structure"
    mysql -h localhost < tests/data/travis/db.structure.sql
	echo ""
    echo "Running phpUnit with php7 ::"
    
    time /usr/local/php7/bin/php /usr/local/bin/phpunit
fi

echo ""
echo "Code coverage:"

./lib/vendor/bin/test-reporter

echo ""
echo "================================================================"
echo ""


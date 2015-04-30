#!/bin/bash

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
time /usr/local/bin/phpunit
times

if [ -e /usr/local/php7/bin/php ]; then
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
echo "================================================================"
echo ""
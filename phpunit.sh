#!/bin/bash

echo Dropping old database
mysql -h localhost -e 'drop database sparta_unittest;'
echo Importing database structure
mysql -h localhost < tests/data/travis/db.structure.sql
echo Running phpUnit ::
/usr/local/bin/phpunit
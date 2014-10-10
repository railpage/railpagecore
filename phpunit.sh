#!/bin/bash

mysql -h localhost -e 'drop database sparta_unittest';
mysql -h localhost < db.dist/db.structure.travis.sql
/usr/local/bin/phpunit

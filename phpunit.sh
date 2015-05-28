#!/bin/bash

usage() { echo "Usage: $0 [-p <5|7>] [-c <html|clover|none>]" 1>&2; exit 1; }

export CODECLIMATE_REPO_TOKEN=20d76bff65cdc35b5d3450847800ecc6d5646842b55fe9b433cee1da0fa012ba

while getopts ":p:c:" o; do
    case "${o}" in
        p)
            p=${OPTARG}
            ((p == 5 || p == 7)) || usage
            ;;
        c)
            c=${OPTARG}
            ;;
        *)
            usage
            ;;
    esac
done
shift $((OPTIND-1))

if [ -z "${p}" ] || [ -z "${c}" ]; then
    usage
fi

if [ "$p" == 5 ]; then 
	exec=/usr/bin/php
else 
	exec=/usr/local/php7/bin/php
fi

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
echo "Running phpUnit (php$p) ::"

if [ "$c" == "clover" ]; then
	time $exec /usr/local/bin/phpunit --coverage-clover build/logs/clover.xml
elif [ "$c" == "html" ]; then
	time $exec /usr/local/bin/phpunit --coverage-html build/logs/html
else 
	time $exec /usr/local/bin/phpunit
fi

times

echo ""

if [ "$c" == "clover" ]; then
	echo "Code coverage:"
	./lib/vendor/bin/test-reporter
fi

echo ""
echo "================================================================"
echo ""


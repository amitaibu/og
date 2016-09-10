#!/bin/bash

# Run either PHPUnit tests or PHP_CodeSniffer tests on Travis CI, depending
# on the passed in parameter.

case "$1" in
    PHP_CodeSniffer)
        cd $MODULE_DIR
        ls -al
        composer install
        ./vendor/bin/phpcs
        exit $?
        ;;
    *)
        ln -s $MODULE_DIR $DRUPAL_DIR/modules/og
        cd $DRUPAL_DIR
        ls -al
        ./vendor/bin/phpunit -c ./core/phpunit.xml.dist $MODULE_DIR/tests
        exit $?
esac

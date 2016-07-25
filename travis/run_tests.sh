#!/bin/bash

# ---------------------------------------------------------------------------- #
#
# Run the tests.
#
# ---------------------------------------------------------------------------- #

# Do we need to run the coder review?
if [ "$CODE_REVIEW" -ne 0 ]; then
  exit 0
fi

cd $TRAVIS_BUILD_DIR
cd ../drupal

# Run the PHPUnit tests which also include the kernel tests.
phpunit -c ./core/phpunit.xml.dist ./modules/og

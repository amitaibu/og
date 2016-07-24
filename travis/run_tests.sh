#!/bin/sh

# ---------------------------------------------------------------------------- #
#
# Run the tests.
#
# ---------------------------------------------------------------------------- #

# Do we need to run the coder review?
if [ "$CODE_REVIEW" = 1 ]; then
  exit 0
fi

# Run the PHPUnit tests which also include the kernel tests.
./vendor/phpunit/phpunit/phpunit -c ./core/phpunit.xml.dist ./modules/og

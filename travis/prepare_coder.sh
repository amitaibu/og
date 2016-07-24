#!/bin/sh

# ---------------------------------------------------------------------------- #
#
# Installs The coder library so we can use t for code reviews.
#
# ---------------------------------------------------------------------------- #

# Do we need to run the coder review?
if [ "$CODE_REVIEW" -ne 1 ]; then
  exit 0
fi

composer global require squizlabs/php_codesniffer
composer global require drupal/coder:dev-8.x-2.x
ln -s ~/.composer/vendor/drupal/coder/coder_sniffer/Drupal ~/.composer/vendor/squizlabs/php_codesniffer/CodeSniffer/Standards/

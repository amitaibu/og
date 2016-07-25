#!/bin/bash

# ---------------------------------------------------------------------------- #
#
# Installs The coder library so we can use it for code reviews.
#
# ---------------------------------------------------------------------------- #

# Do we need to run the coder review?
if [ "$CODE_REVIEW" -ne 1 ]; then
  exit 0
fi

composer global require drupal/coder:dev-8.x-2.x
phpcs --config-set installed_paths ~/.composer/vendor/drupal/coder/coder_sniffer

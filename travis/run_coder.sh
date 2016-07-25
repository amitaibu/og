#!/bin/bash

# ---------------------------------------------------------------------------- #
#
# Run the coder review.
#
# ---------------------------------------------------------------------------- #

# Do we need to run the coder review?
if [ "$CODE_REVIEW" -ne 1 ]; then
  exit 0
fi

cd $TRAVIS_BUILD_DIR
cd ..

# Check if there any bad coding standards.
phpcs --standard=$REVIEW_STANDARD -p --colors $TRAVIS_BUILD_DIR

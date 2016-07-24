#!/bin/sh

# ---------------------------------------------------------------------------- #
#
# Prepare the env to tests.
#
# ---------------------------------------------------------------------------- #

# Do we need to run the coder review?
if [ "$CODE_REVIEW" -ne 0 ]; then
  exit 0
fi


# Navigate out of module directory to prevent blown stack by recursive module
# lookup.
cd ..

# Create database.
mysql -e 'create database og'

# Export database variable for kernel tests.
export SIMPLETEST_DB=mysql://root:@127.0.0.1/og

# Composer.
sed -i '1i export PATH="$HOME/.composer/vendor/bin:$PATH"' $HOME/.bashrc
source $HOME/.bashrc

# Download Drupal 8 core.
travis_retry git clone --branch $DRUPAL_CORE --depth 1 https://git.drupal.org/project/drupal.git
cd drupal
composer install

# Reference OG in the Drupal site.
ln -s $TESTDIR modules/og

# Start a web server on port 8888 in the background.
nohup php -S localhost:8888 > /dev/null 2>&1 &

# Wait until the web server is responding.
until curl -s localhost:8888; do true; done > /dev/null

# Export web server URL for browser tests.
export SIMPLETEST_BASE_URL=http://localhost:8888

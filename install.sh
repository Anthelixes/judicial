#!/bin/bash

SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
cd "$SCRIPT_DIR/web"

# Site:install command requires write permission on settings.php file
chmod u+w sites/default/ sites/default/settings.php

../vendor/bin/drush site:install config_installer -y
../vendor/bin/drush user:password admin password

# Site:install removes write permission after it's finished
chmod u+w sites/default/ sites/default/settings.php
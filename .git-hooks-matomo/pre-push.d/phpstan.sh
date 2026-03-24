#!/bin/bash

# This hook is called with the following parameters:
#
# $1 -- Name of the remote to which the push is being done
# $2 -- URL to which the push is being done
#
# If pushing without using a named remote those arguments will be equal.
#
# Information about the commits which are being pushed is supplied as lines to
# the standard input in the form:
#
#   <local ref> <local oid> <remote ref> <remote oid>



### Check we're running in the context of a plugin and get helpful dir variables ###

REPO_DIR="$(git rev-parse --show-toplevel)"
echo "Running pre-commit hook in repo: $REPO_DIR"

if [[ "$REPO_DIR" =~ /plugins/(.*) ]]; then
    PLUGIN_PATH="plugins/${BASH_REMATCH[1]}/"
else
    echo "Not a plugin, not running any further checks"
    exit 1
fi
MATOMO_DIR=$(echo "$REPO_DIR" | sed -E 's|/plugins/.*$||')



### Figure out how to run PHPStan - ddev or not. ###

COMMAND=""
# Use local PHP if setup
if command -v php >/dev/null 2>&1; then
    if [ -f "${MATOMO_DIR}/vendor/bin/phpstan" ]; then
      COMMAND="${MATOMO_DIR}/vendor/bin/phpstan"
      PLUGIN_PATH=''
    fi
elif command -v ddev >/dev/null 2>&1; then
  # Use ddev if setup (overridding local setup)
  if [ -d "$MATOMO_DIR/.ddev" ]; then
    cd "$MATOMO_DIR" || exit 1
    if ddev status 2>&1 > /dev/null; then
      COMMAND="ddev exec phpstan"
    fi
  fi
fi
# If no command, exit
if [[ -z "$COMMAND" ]]; then
  echo "No way to run phpstan found."
  exit 1
fi



# Basic setup
cd "$REPO_DIR"
STATUS=0


PHPSTAN_BASE_CONFIG=phpstan.neon
if [[ -f "$PHPSTAN_BASE_CONFIG" ]]; then
    echo "Running PHPstan at a base level on all plugin files"
  $COMMAND analyse -c ${PLUGIN_PATH}/${PHPSTAN_BASE_CONFIG} || STATUS=1
fi

exit $STATUS

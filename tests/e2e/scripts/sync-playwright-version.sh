#!/bin/bash

#
# ---------------------------------------------------------------------
#
# GLPI - Gestionnaire Libre de Parc Informatique
#
# http://glpi-project.org
#
# @copyright 2015-2025 Teclib' and contributors.
# @licence   https://www.gnu.org/licenses/gpl-3.0.html
#
# ---------------------------------------------------------------------
#
# LICENSE
#
# This file is part of GLPI.
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
#
# ---------------------------------------------------------------------
#

set -e -u -o pipefail

# Path to Makefile
MAKEFILE="Makefile"

# Extract the version of @playwright/test from package.json
PLAYWRIGHT_VERSION=$(grep '"@playwright/test"' package.json | sed -E 's/.*"@playwright\/test": *"[^0-9]*([0-9.]+)".*/\1/')

if [ -z "$PLAYWRIGHT_VERSION" ]; then
  echo "Error: Could not find @playwright/test version in package.json"
  exit 1
fi

# Update the PLAYWRIGHT_VERSION variable inside the Makefile
sed -i.bak -E "s/^(PLAYWRIGHT_VERSION *= *).*/\1$PLAYWRIGHT_VERSION/" "$MAKEFILE"

# Cleanup the backup file created by sed (macOS creates .bak; GNU sed works too)
rm -f "$MAKEFILE.bak"

echo "Updated PLAYWRIGHT_VERSION to $PLAYWRIGHT_VERSION"

#!/bin/bash -eu

#
# ---------------------------------------------------------------------
#
# GLPI - Gestionnaire Libre de Parc Informatique
#
# http://glpi-project.org
#
# @copyright 2015-2026 Teclib' and contributors.
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

# Ensure strict mode even when invoked as `bash build_fonts.sh`
set -eu

#
# Generate the TCPDF font assets.
#
# Since TCPDF 7.0, TCPDF is only a facade over "tecnickcom/tc-lib-pdf" and no
# longer bundles any font. Fonts are now JSON definition files that have to be
# generated from "tecnickcom/tc-lib-pdf-font" into its "target/fonts" directory,
# which GLPI exposes to the engine through the `K_PATH_FONTS` constant.
#
# This step is idempotent: it does nothing when the font assets are already
# present.
#

SCRIPT_DIR=$(dirname "$0")
WORKING_DIR=$(readlink -f "$SCRIPT_DIR/..")

FONT_PKG_DIR="$WORKING_DIR/vendor/tecnickcom/tc-lib-pdf-font"
FONT_UTIL_DIR="$FONT_PKG_DIR/util"
SENTINEL="$FONT_PKG_DIR/target/fonts/core/helvetica.json"

if [ ! -d "$FONT_PKG_DIR" ]; then
    echo "tc-lib-pdf-font is not installed; run \"composer install\" first."
    exit 1
fi

if [ -f "$SENTINEL" ]; then
    echo "TCPDF font assets are already generated."
    exit 0
fi

echo "Generating TCPDF font assets..."

# The upstream converter (bulk_convert.php) expects the standalone package
# layout and requires the package-level "vendor/autoload.php". GLPI uses a flat
# (hoisted) vendor layout where that file does not exist, so we bridge it to the
# application autoloader for the duration of the conversion.
AUTOLOAD_SHIM="$FONT_PKG_DIR/vendor/autoload.php"
CREATED_SHIM=0
if [ ! -f "$AUTOLOAD_SHIM" ]; then
    mkdir -p "$FONT_PKG_DIR/vendor"
    echo "<?php require '$WORKING_DIR/vendor/autoload.php';" > "$AUTOLOAD_SHIM"
    CREATED_SHIM=1
fi

# Pin the font sources ("tecnickcom/tc-font-mirror") to the version declared by
# the converter. Upstream declares them with a mutable `main` dist reference, so
# Composer would otherwise download the branch HEAD and the generated font set
# would change whenever the mirror repository is updated.
MIRROR_MANIFEST="$FONT_UTIL_DIR/composer.json"
cp "$MIRROR_MANIFEST" "$MIRROR_MANIFEST.orig"
# Decoded as objects, so that empty objects are not turned into arrays on re-encoding.
php -r '
$file = $argv[1];
$manifest = json_decode(file_get_contents($file));
foreach ($manifest->repositories ?? [] as $repository) {
    if (($repository->package->name ?? null) !== "tecnickcom/tc-font-mirror") {
        continue;
    }
    $repository->package->dist->reference = $repository->package->version;
}
file_put_contents($file, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
' "$MIRROR_MANIFEST"

# Fetch the font sources ("tecnickcom/tc-font-mirror") used by the converter.
composer install --no-dev --no-interaction --quiet --working-dir="$FONT_UTIL_DIR"

mv "$MIRROR_MANIFEST.orig" "$MIRROR_MANIFEST"

# Convert the sources into tc-lib-pdf-font JSON definitions (into target/fonts).
# Discard the per-font progress on stdout; errors (stderr) are kept and a
# non-zero exit still aborts the script.
php "$FONT_UTIL_DIR/bulk_convert.php" > /dev/null

# Remove build-only artifacts so they are not shipped nor left in the tree.
rm -rf "$FONT_UTIL_DIR/vendor"
rm -f "$FONT_UTIL_DIR/composer.lock"
if [ "$CREATED_SHIM" -eq 1 ]; then
    rm -rf "$FONT_PKG_DIR/vendor"
fi

echo "TCPDF font assets successfully generated."

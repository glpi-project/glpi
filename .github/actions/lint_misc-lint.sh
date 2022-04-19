#!/bin/bash -e

echo "Check for missing headers"
vendor/bin/licence-headers-check --ansi --no-interaction

echo "Check for gettext errors/warnings"
vendor/bin/extract-locales 2>&1 | tee extract.log
if [[ -n $(grep "warning" extract.log) ]]; then exit 1; fi

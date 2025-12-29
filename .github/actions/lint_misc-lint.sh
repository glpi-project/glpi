#!/bin/bash
set -e -u -x -o pipefail

bin/console tools:licence_headers_check --ansi --no-interaction

bin/console tools:extract_locales 2>&1 | tee extract.log
if [[ -n $(grep "warning" extract.log) ]]; then exit 1; fi

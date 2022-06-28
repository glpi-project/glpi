#!/bin/bash
set -e -u -x -o pipefail

vendor/bin/licence-headers-check --ansi --no-interaction

vendor/bin/extract-locales 2>&1 | tee extract.log
if [[ -n $(grep "warning" extract.log) ]]; then exit 1; fi

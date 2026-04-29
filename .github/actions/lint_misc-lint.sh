#!/bin/bash
set -e -u -x -o pipefail

bin/console tools:licence_headers_check

bin/console tools:locales:extract 2>&1 | tee extract.log
if [[ -n $(grep "warning" extract.log) ]]; then exit 1; fi

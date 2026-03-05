#!/bin/bash
set -e -u -x -o pipefail

vendor/bin/paratest --display-deprecations --exclude-group "single-thread" $@

#!/bin/bash
set -e -u -x -o pipefail

vendor/bin/paratest --exclude-group "single-thread" $@

#!/bin/bash
set -e -u -x -o pipefail

# The number of workers must match the number of test databases (see `init_clone-databases.sh`)
vendor/bin/paratest -p 4 $@

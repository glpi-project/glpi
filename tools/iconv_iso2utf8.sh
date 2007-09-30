#!/bin/bash
find . -name *.js -exec iconv -f iso-8859-2 -t utf8 {} -o {}.tmp \; -exec mv {}.tmp {} \;

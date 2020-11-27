#!/bin/bash -e

echo "Check for CS vialotions in templates"
vendor/bin/twigcs templates/

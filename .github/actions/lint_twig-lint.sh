#!/bin/bash -e

echo "Check for CS violaotions in templates"
vendor/bin/twigcs templates/

#!/bin/bash -e

echo "Check for CS violations in templates"
vendor/bin/twigcs --ruleset=Glpi\\Tools\\GlpiTwigRuleset templates/

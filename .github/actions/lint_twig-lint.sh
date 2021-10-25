#!/bin/bash -e

echo "Check for syntax errors in templates"
tools/bin/check-twig-templates-syntax --ansi --no-interaction

echo "Check for CS violations in templates"
vendor/bin/twigcs --ansi --no-interaction --ruleset=Glpi\\Tools\\GlpiTwigRuleset templates/

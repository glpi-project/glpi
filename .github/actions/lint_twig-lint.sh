#!/bin/bash -e

echo "Check for syntax errors in templates"
tools/bin/check-twig-templates-syntax

echo "Check for CS violations in templates"
vendor/bin/twigcs --ruleset=Glpi\\Tools\\GlpiTwigRuleset templates/

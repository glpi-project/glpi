#!/bin/bash -e

echo "Check for syntax errors in templates"
bin/console tools:check_twig_templates_syntax

echo "Check for CS violations in templates"
vendor/bin/twigcs --ruleset=Glpi\\Tools\\GlpiTwigRuleset templates/

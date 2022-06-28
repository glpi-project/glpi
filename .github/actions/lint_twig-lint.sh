#!/bin/bash
set -e -u -x -o pipefail

tools/bin/check-twig-templates-syntax --ansi --no-interaction

vendor/bin/twigcs --ansi --no-interaction --ruleset=Glpi\\Tools\\GlpiTwigRuleset templates/

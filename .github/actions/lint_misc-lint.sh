#!/bin/bash
set -e -u -x -o pipefail

vendor/bin/licence-headers-check --ansi --no-interaction

vendor/bin/extract-locales 2>&1 | tee extract.log
if [[ -n $(grep "warning" extract.log) ]]; then exit 1; fi

echo "Check for unresolved git merge conflict markers..."
CONFLICT_MATCHES=$(grep -rn '<<<<<<<\|>>>>>>>' \
  --include='*.php' --include='*.twig' --include='*.js' --include='*.ts' --include='*.vue' \
  --include='*.yml' --include='*.yaml' --include='*.css' --include='*.scss' \
  ajax/ bin/ config/ css/ dependency_injection/ front/ inc/ install/ js/ public/ resources/ routes/ src/ templates/ tests/ tools/ \
  | grep -v '^public/lib/' \
  || true)
if [[ -n "$CONFLICT_MATCHES" ]]; then
  echo "ERROR: Unresolved git merge conflict markers found:"
  echo "$CONFLICT_MATCHES"
  exit 1
else
  echo "✓ No unresolved git merge conflict markers found"
fi


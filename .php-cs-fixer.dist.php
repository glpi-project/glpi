<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        '.git/',
        'config/',
        'files/',
        'marketplace/',
        'node_modules/',
        'plugins/',
        'tests/config/',
        'tests/files/',
        'vendor/',
    ])
;

return (new PhpCsFixer\Config())
    ->setUnsupportedPhpVersionAllowed(true) // allow upcoming PHP versions
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setCacheFile('files/_cache/php-cs-fixer/php-cs-fixer.cache')
    ->setRules([
        '@PER-CS3.0' => true,
        '@PHP84Migration' => true,
        'fully_qualified_strict_types' => ['import_symbols' => true],
        'ordered_imports' => ['imports_order' => ['class', 'const', 'function']],
        'no_unused_imports' => true,
        'heredoc_indentation' => false, // This rule is mandatory due to a bug in `xgettext`, see https://savannah.gnu.org/bugs/?func=detailitem&item_id=62158
        'new_expression_parentheses' => false, // breaks compatibility with PHP < 8.4
    ])
    ->setFinder($finder)
;

<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/**
 * Shared php-cs-fixer baseline for GLPI plugins.
 *
 *     $baseline = require __DIR__ . '/../../PluginsPhpCsFixer.php';
 *     return $baseline(Finder::create()->in(__DIR__)->ignoreVCSIgnored(true)->name('*.php'));
 *
 */
return static fn(Finder $finder): Config => (new Config())
    ->setRules([
        '@PER-CS3x0' => true,
        'fully_qualified_strict_types' => ['import_symbols' => true],
        'ordered_imports' => ['imports_order' => ['class', 'const', 'function']],
        'no_unused_imports' => true,
        'heredoc_indentation' => false, // xgettext bug: https://savannah.gnu.org/bugs/?func=detailitem&item_id=62158
        'phpdoc_scalar' => true,
        'phpdoc_types' => true,
    ])
    ->setFinder($finder)
;

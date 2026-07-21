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

namespace tests\units\Glpi\CodeQuality;

use FilesystemIterator;
use Glpi\Tests\GLPITestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function Safe\file_get_contents;
use function Safe\preg_match_all;

class AjaxUrlRootDocTest extends GLPITestCase
{
    private const SCANNED_DIRECTORIES = ['ajax', 'front', 'inc', 'src', 'templates', 'js'];

    // matched on full file content, not per line, so multi-line calls are caught
    private const AJAX_CALL_PATTERNS = [
        '/\.load\(\s*[\'"](\/(?:ajax|front)\/[A-Za-z0-9_\-]+\.php)[\'"]/s',
        '/\burl\s*:\s*[\'"](\/(?:ajax|front)\/[A-Za-z0-9_\-]+\.php)[\'"]/s',
        '/\$\.(?:get|post|ajax)\(\s*[\'"](\/(?:ajax|front)\/[A-Za-z0-9_\-]+\.php)[\'"]/s',
    ];

    public function testAjaxUrlsArePrefixedWithRootDoc(): void
    {
        $violations = [];

        foreach (self::SCANNED_DIRECTORIES as $directory) {
            $path = GLPI_ROOT . '/' . $directory;
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (!in_array($file->getExtension(), ['php', 'twig'], true)) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                foreach (self::AJAX_CALL_PATTERNS as $pattern) {
                    if (preg_match_all($pattern, $content, $matches)) {
                        foreach ($matches[1] as $url) {
                            $violations[] = sprintf(
                                '%s: "%s" (must be prefixed with root_doc/path())',
                                str_replace(GLPI_ROOT . '/', '', $file->getPathname()),
                                $url
                            );
                        }
                    }
                }
            }
        }

        $this->assertEmpty($violations, "Hardcoded ajax/front URLs found, missing root_doc/path() prefix:\n" . implode("\n", $violations));
    }
}

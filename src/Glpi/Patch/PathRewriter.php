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

namespace Glpi\Patch;

use function Safe\preg_replace;

final class PathRewriter
{
    /**
     * Files that must be skipped when applying the patch.
     */
    private const SKIPED_FILES = ['tests/', 'tools/', 'phpunit/', 'CHANGELOG.md', '/dev/null'];

    /**
     * Folders that live under public/ in a production GLPI installation.
     * In the repository they are at the root, but in production they are
     * prefixed with "public/".
     */
    private const PUBLIC_REMAPPED_FOLDERS = ['js/', 'lib/', 'css/'];

    /**
     * @param string $work_dir   Absolute path to the directory where the patch is being applied.
     */
    public function __construct(
        private readonly string $work_dir,
    ) {}

    /**
     * Adds the absolute workdir path of the current object to the begining of a raw diff path.
     *
     * @param  string $diff_path  Path as it appears in the diff (e.g. "a/src/Foo.php").
     * @return array<string, mixed>  {skiped: bool, path: string}  skiped=true if the file should be skipped, path is the resolved absolute path to the file.
     */
    public function resolve(string $diff_path): array
    {
        $path = $this->stripPrefix($diff_path);
        foreach (self::SKIPED_FILES as $skip) {
            if (str_starts_with($path, $skip)) {
                return [
                    'skiped' => true,
                    'path'   => $this->work_dir . '/' . $path,
                ];
            }
        }

        // GLPI core: remap assets folders to the public/ subfolder
        foreach (self::PUBLIC_REMAPPED_FOLDERS as $folder) {
            if (str_starts_with($path, $folder)) {
                return [
                    'skiped'   => false,
                    'path'     => $this->work_dir . '/public/' . $path
                ];
            }
        }

        return [
            'skiped' => false,
            'path'   => $this->work_dir . '/' . $path,
        ];
    }

    /**
     * Returns true when the given diff path represents /dev/null
     * (used for new-file and deleted-file entries in git diffs).
     */
    public function isDevNull(string $diff_path): bool
    {
        return $this->stripPrefix($diff_path) === '/dev/null';
    }

    /**
     * Returns a clean, human-readable path for display (strips a/ / b/ prefix).
     */
    public function displayPath(string $diff_path): string
    {
        return $this->stripPrefix($diff_path);
    }

    /**
     * Strips the "a/" or "b/" prefix that git adds to diff paths.
     */
    private function stripPrefix(string $diff_path): string
    {
        return (string) preg_replace('#^[ab]/#', '', $diff_path);
    }
}

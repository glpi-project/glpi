<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Toolbox;

use LogicException;

/**
 * Helper class to duplicate user uploads
 *
 * This is needed when the same uploaded files must be submitted multiple times
 * for a single request.
 *
 * For example, when submitting a validation requested to multiple users with an
 * attached file
 */
class DuplicatedFileUpload
{
    /**
     * Store duplicated files infos
     * @var array
     */
    protected array $unique_files;

    /**
     * Duplicate files found in $_POST for each submitted key
     *
     * @param array $unique_keys Unique set of keys that will be used to retrieved
     *                           files using the `loadIntoPostForKey` method
     */
    public function __construct(array $unique_keys)
    {
        $this->unique_files = [];

        if (!$this->hasUploadedFiles()) {
            // Nothing to be done, set empty data for each submitted key
            foreach ($unique_keys as $unique_key) {
                $this->unique_files[$unique_key] = [];
            }
            return;
        }

        // Read file info from $_POST
        $files = $this->getUploadedFilesData();

        // Create an unique copy for each files
        foreach ($unique_keys as $unique_key) {
            // Make a unique copy of each files
            $this->unique_files[$unique_key] = $this->duplicateFiles($files);
        }

        // Delete original files
        foreach ($files as $file) {
            unlink(GLPI_TMP_DIR . "/" . $file['_filename']);
        }
    }

    /**
     * Load a set of duplicated files into $_POST
     *
     * @param mixed $unique_key The unique key used to identify the set of files
     */
    public function loadIntoPostForKey($unique_key): void
    {
        // Try to load files
        $files = $this->unique_files[$unique_key] ?? null;

        // Invalid key
        if ($files === null) {
            throw new LogicException(
                "Invalid '$unique_key' key. Expected keys: " . json_encode(
                    array_keys($this->unique_files)
                )
            );
        }

        if (empty($files)) {
            // No submitted files, make sure no files are referenced in $_POST
            unset($_POST['_filename']);
            unset($_POST['_prefix_filename']);
            unset($_POST['_tag_filename']);
        } else {
            // Load files infos
            $_POST['_filename']        = array_column($files, '_filename');
            $_POST['_prefix_filename'] = array_column($files, '_prefix_filename');
            $_POST['_tag_filename']    = array_column($files, '_tag_filename');
        }
    }

    /**
     * Check if there are any uploaded files in $_POST
     *
     * @return bool
     */
    protected function hasUploadedFiles(): bool
    {
        return isset($_POST['_filename']);
    }

    /**
     * Get data for each uploaded files
     *
     * @return array
     */
    protected function getUploadedFilesData(): array
    {
        $files = [];
        foreach ($_POST['_filename'] as $i => $filename) {
            $files[] = [
                '_filename'        => $filename,
                '_prefix_filename' => $_POST['_prefix_filename'][$i],
                '_tag_filename'    => $_POST['_tag_filename'][$i],
            ];
        }

        return $files;
    }

    /**
     * Duplicate the given files and return their updated data
     *
     * @param array $files
     *
     * @return array
     */
    protected function duplicateFiles(array $files): array
    {
        return array_map(
            function ($file) {
                // Compute a new unique prefix for our file
                $new_prefix = uniqid("", true);

                // Replace prefix in file name
                $old_prefix = $file['_prefix_filename'];
                $old_filename = $file['_filename'];
                $new_filename = $new_prefix . substr(
                    $old_filename,
                    strlen($old_prefix)
                );

                // Copy file on disk
                copy(
                    GLPI_TMP_DIR . "/$old_filename",
                    GLPI_TMP_DIR . "/$new_filename"
                );

                // Return updated file info
                return [
                    '_filename'        => $new_filename,
                    '_prefix_filename' => $new_prefix,
                    '_tag_filename'    => $file['_tag_filename'],
                ];
            },
            $files
        );
    }
}

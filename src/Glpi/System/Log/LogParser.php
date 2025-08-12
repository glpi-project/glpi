<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\System\Log;

use CommonGLPI;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use Toolbox;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\filemtime;
use function Safe\filesize;
use function Safe\preg_match;
use function Safe\preg_replace_callback;
use function Safe\preg_split;
use function Safe\readfile;
use function Safe\realpath;
use function Safe\scandir;
use function Safe\unlink;

final class LogParser extends CommonGLPI
{
    /**
     * Log directory.
     *
     * @var string
     */
    private $directory;

    /**
     * Known files cache.
     *
     * @var array
     */
    private $known_files;

    public function __construct(string $directory = GLPI_LOG_DIR)
    {
        if (!is_dir($directory)) {
            throw new RuntimeException(sprintf('Invalid directory "%s".', $directory));
        }

        $this->directory = $directory;
    }


    /**
     * can we write new log file in the log directory ?
     *
     * @return bool
     */
    public function canWriteLogs(): bool
    {
        return is_writable($this->directory);
    }

    /**
     * Get information about all log files located in given directory.
     *
     * @return array[] Log files information with given keys
     *                  - filepath: file path (relative to directory)
     *                  - datemod: file modification date
     *                  - size: file size
     */
    public function getLogsFilesList(): array
    {
        if ($this->known_files === null) {
            $this->known_files = [];

            $files_names = scandir($this->directory);
            foreach ($files_names as $file_name) {
                if (!preg_match('/^(.+)\.log$/', $file_name)) {
                    continue;
                }
                $this->known_files[$file_name] = [
                    'filepath' => $file_name,
                    'datemod'  => date('Y-m-d H:i:s', filemtime("{$this->directory}/{$file_name}")),
                    'size'     => filesize("{$this->directory}/{$file_name}"),
                ];
            }
        }

        return $this->known_files;
    }

    /**
     * Get information about information given log file.
     *
     * @param string $filepath
     *
     * @return array|null
     */
    public function getLogFileInfo(string $filepath): ?array
    {
        $files_list = $this->getLogsFilesList();
        return $files_list[$filepath] ?? null;
    }

    /**
     * Parse a log file and return an array of log entries.
     *
     * @param string $filepath
     * @param int $max_nb_lines
     *
     * @return array|null
     */
    public function parseLogFile(string $filepath, ?int $max_nb_lines = null): ?array
    {
        global $CFG_GLPI;

        $fullpath = $this->getFullPath($filepath);

        if ($fullpath === null) {
            return null;
        }

        // set max content for files to avoid performance issues
        $max_bytes = 1024 * 1000;
        if ($max_bytes > filesize($fullpath)) {
            $max_bytes = 0;
        }
        if ($max_nb_lines === null) {
            $max_nb_lines = $_SESSION['glpilist_limit'] ?? $CFG_GLPI['list_limit'];
        }

        // explode log files by datetime pattern
        $logs  = file_get_contents($fullpath, false, null, -$max_bytes);
        $datetime_pattern = "\[*\d{4}-\d{1,2}-\d{1,2}\s\d{1,2}:\d{1,2}:\d{1,2}\]*";
        $rawlines = preg_split(
            "/^(?=$datetime_pattern)/m",
            $logs,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $rawlines = array_splice($rawlines, -$max_nb_lines);

        $lines = [];
        $index = 0;
        foreach ($rawlines as $line) {
            $last_date = "";
            $line = preg_replace_callback(
                "/$datetime_pattern/",
                function ($matches) use (&$last_date) {
                    $last_date = $matches[0];
                    return "";
                },
                $line
            );
            $last_date = trim($last_date, "[] ");
            $lines[] = [
                'id'       => Toolbox::slugify($last_date, "date_{$index}_", true),
                'datetime' => $last_date,
                'text'     => trim($line),
            ];
            $index++;
        }

        return $lines;
    }

    /**
     * Send a log file as attachment to the browser.
     *
     * @param string $filepath  Path of file to display (relative to log directory)
     *
     * @return void
     */
    public function download(string $filepath): void
    {
        $fullpath = $this->getFullPath($filepath);

        if ($fullpath === null) {
            header('HTTP/1.0 404 Not Found');
            return;
        }

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($fullpath) . "\"");
        readfile($fullpath);
    }

    /**
     * Clear a log file.
     *
     * @param string $filepath
     *
     * @return bool
     */
    public function empty(string $filepath): bool
    {
        $fullpath = $this->getFullPath($filepath);

        if ($fullpath === null) {
            return false;
        }

        return file_put_contents($fullpath, '') === 0;
    }


    /**
     * Delete a log file.
     *
     * @param string $filepath
     *
     * @return bool
     */
    public function delete(string $filepath): bool
    {
        $fullpath = $this->getFullPath($filepath);

        if ($fullpath === null) {
            return false;
        }

        try {
            unlink($fullpath);
        } catch (FilesystemException) {
            return false;
        }

        return true;
    }

    /**
     * Get full path for given relative path.
     *
     * @param string $filepath  Path of file to display (relative to log directory)
     *
     * @return string|null
     */
    public function getFullPath(string $filepath): ?string
    {
        try {
            $logs_dir_path = realpath($this->directory);
        } catch (FilesystemException) {
            return null;
        }

        try {
            $fullpath = realpath($logs_dir_path . '/' . $filepath);
        } catch (FilesystemException) {
            return null;
        }

        if (!str_starts_with($fullpath, $logs_dir_path)) {
            return null; // Security check
        }

        return !is_dir($fullpath) ? $fullpath : null;
    }
}

<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Event;

class LogViewer extends CommonGLPI
{
    public static $rightname = 'logs';


    public static function getTypeName($nb = 0)
    {
        return _n('Log', 'Logs', $nb);
    }


    public static function getMenuContent()
    {
        $menu = [];

        if (self::canView()) {
            $menu = [
                'title'   => self::getTypeName(Session::getPluralNumber()),
                'page'    => '/front/logs.php',
                'icon'    => static::getIcon(),
                'options' => [
                    'logfile' => [
                        'title' => __('Log file'),
                        'page'  => '/front/logviewer.php',
                        'icon'  => 'ti ti-file',
                    ],
                ],
            ];

            if (Event::canView()) {
                $menu['options']['Glpi\\Event'] = [
                    'title' => Event::getTypeName(Session::getPluralNumber()),
                    'page'  => Event::getSearchURL(false),
                    'icon'  => Event::getIcon(),
                ];
            }
        }

        return $menu;
    }


    /**
     * Display a link for events and a list of log files links
     *
     * @return void
     */
    public function displayList()
    {
        TemplateRenderer::getInstance()->display(
            'pages/admin/logs_list.html.twig',
            [
                'logs' => self::getLogsFilesList(),
            ]
        );
    }


    /** get a list of log files of GLPI
     *
     * @return array of [slug => filename.log]
     */
    protected static function getLogsFilesList(): array
    {
        static $logs_files = [];
        if (count($logs_files) > 0) {
            return $logs_files;
        }

        $logs_files = scandir(GLPI_LOG_DIR);
        $files = [];
        foreach ($logs_files as $log_filename) {
            if (preg_match('/^(.+)\.log$/', $log_filename, $matches)) {
                $filename = $matches[1];
                $filekey  = Toolbox::slugify($filename, '', true);
                $files[$filekey] = "$filename.log";
            }
        }
        return $files;
    }


    /** Display a log file content
     *  We try to explode the log file by searching datetime patterns
     *
     * @param string $fileslug the slug of the log file
     * @param int $nb_lines
     *
     * @return void
     */
    public static function showLogFile(string $fileslug = "", bool $only_content = false, int $nb_lines = null)
    {
        global $CFG_GLPI;

        $filename = self::getFilenameFromSlug($fileslug);
        $filepath = GLPI_LOG_DIR . "/" . $filename;
        if (is_dir($filepath) || !file_exists($filepath)) {
            echo "";
            return false;
        }

        // set max content for files to avoid performance issues
        $max_bytes = 1024 * 1000;
        if ($max_bytes > filesize($filepath)) {
            $max_bytes = 0;
        }
        if (is_null($nb_lines)) {
            $nb_lines = $_SESSION['glpilist_limit'] ?? $CFG_GLPI['list_limit'];
        }

        $logs  = file_get_contents($filepath, false, null, -$max_bytes);
        $datetime_pattern = "\[*\d{4}-\d{1,2}-\d{1,2}\s\d{1,2}:\d{1,2}:\d{1,2}\]*";
        $rawlines = preg_split(
            "/^(?=$datetime_pattern)/m",
            $logs,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $rawlines = array_splice($rawlines, -$nb_lines);

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

        TemplateRenderer::getInstance()->display(
            'pages/admin/log_viewer.html.twig',
            [
                'fileslug'     => $fileslug,
                'filename'     => $filename,
                'log_entries'  => $lines,
                'log_files'    => self::getLogsFilesList(),
                'only_content' => $only_content,
                'href'         => self::getSearchURL() . "?fileslug=$fileslug&",
            ]
        );
    }


    /**
     * Send a log file as attachment to the browser
     *
     * @param string $log_filename
     *
     * @return void
     */
    public static function downloadLogFile(string $fileslug = "")
    {
        $filename = self::getFilenameFromSlug($fileslug);
        $filepath = GLPI_LOG_DIR . "/" . $filename;
        if (is_dir($filepath) || !file_exists($filepath)) {
            return;
        }

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($filepath) . "\"");
        readfile($filepath);
    }


    /**
     * Get the filename from a slug of a log file
     *
     * @param string $fileslug
     *
     * @return string the filename
     */
    protected static function getFilenameFromSlug(string $fileslug): string
    {
        $logfiles = self::getLogsFilesList();
        return $logfiles[$fileslug] ?? "";
    }


    public static function getIcon()
    {
        return "ti ti-news";
    }
}

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
use Glpi\Application\View\TemplateRenderer;
use Glpi\Event;
use Session;

final class LogViewer extends CommonGLPI
{
    /**
     * @var LogParser
     */
    private $log_parser;

    public static $rightname = 'logs';

    public function __construct()
    {
        $this->log_parser = new LogParser();
        parent::__construct();
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Log', 'Logs', $nb);
    }

    public static function getMenuContent(): array
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
                $menu['options'][Event::class] = [
                    'title' => Event::getTypeName(Session::getPluralNumber()),
                    'page'  => Event::getSearchURL(false),
                    'icon'  => Event::getIcon(),
                ];
            }
        }

        return $menu;
    }

    /**
     * Display a link for events and a list of log files links.
     *
     * @param string $order Field used to sort list.
     * @param string $sort  Sort order ('asc' or 'desc').
     *
     * @return void
     */
    public function displayList(string $order = "filename", string $sort = "asc"): void
    {
        $logs = $this->log_parser->getLogsFilesList();

        $order_key_values = array_column($logs, $order);
        if (count($order_key_values)) {
            array_multisort(
                $order_key_values,
                $sort === "desc" ? SORT_DESC : SORT_ASC,
                $logs
            );
        }

        $can_config = Session::haveRight('config', UPDATE);

        TemplateRenderer::getInstance()->display(
            'pages/admin/logs_list.html.twig',
            [
                'logs'       => $logs,
                'order'      => $order,
                'sort'       => $sort,
                'can_clear'  => $can_config,
                'can_delete' => $can_config && $this->log_parser->canWriteLogs(),
            ]
        );
    }

    /**
     * Display a log file content.
     *
     * @param string $filepath      Path of file to display (relative to log directory)
     * @param bool $only_content    If true, don't return the html layout.
     *
     * @return void
     */
    public function showLogFile(string $filepath, bool $only_content = false): void
    {
        $file_info   = $this->log_parser->getLogFileInfo($filepath);
        $log_entries = $this->log_parser->parseLogFile($filepath);

        $log_files = $this->log_parser->getLogsFilesList();
        $can_config = Session::haveRight('config', UPDATE);

        TemplateRenderer::getInstance()->display(
            'pages/admin/log_viewer.html.twig',
            [
                'filepath'     => $filepath,
                'datemod'      => $file_info['datemod'] ?? null,
                'filesize'     => $file_info['size'] ?? null,
                'log_entries'  => $log_entries,
                'log_files'    => $log_files,
                'only_content' => $only_content,
                'can_clear'    => $can_config,
                'can_delete'   => $can_config && $this->log_parser->canWriteLogs(),
                'href'         => self::getSearchURL() . '?filepath=' . urlencode($filepath) . '&',
            ]
        );
    }


    public static function getIcon()
    {
        return "ti ti-news";
    }

    public static function getSearchURL($full = true)
    {
        global $CFG_GLPI;

        return implode(
            '/',
            [
                $full ? $CFG_GLPI['root_doc'] : '',
                'front',
                'logviewer.php',
            ]
        );
    }
}

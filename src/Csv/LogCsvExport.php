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

namespace Glpi\Csv;

use CommonDBTM;
use Log;
use Toolbox;
use User;

class LogCsvExport implements ExportToCsvInterface
{
    /** @var CommonDBTM */
    protected $item;

    /** @var array */
    protected $filter;

    public function __construct(CommonDBTM $item, array $filter)
    {
        $this->item   = $item;
        $this->filter = $filter;
    }

    public function getFileName(): string
    {
        $name = $this->item->getFriendlyName();
        $date = date('Y_m_d', time());

       // Replace name by itemtype + id if empty
        if ($name === '') {
            $name = "{$this->item->getTypeName(1)}_{$this->item->getId()}";
        }

        return Toolbox::filename("{$name}_$date") . ".csv";
    }

    public function getFileHeader(): array
    {
        return [
            __('ID'),
            _n('Date', 'Dates', 1),
            User::getTypeName(1),
            _n('Field', 'Fields', 1),
            _x('name', 'Update'),
        ];
    }

    public function getFileContent(): array
    {
       // Get logs from DB
        $filter = Log::convertFiltersValuesToSqlCriteria($this->filter);
        $logs = Log::getHistoryData($this->item, 0, 0, $filter);

       // Remove uneeded rows
        $logs = array_map(function ($log) {
            unset($log['display_history']);
            unset($log['datatype']);
            return $log;
        }, $logs);

        return $logs;
    }
}

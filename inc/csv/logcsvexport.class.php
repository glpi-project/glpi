<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Csv;

use CommonDBTM;
use Log;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class LogCsvExport implements ExportToCsvInterface
{

   /** @var CommonDBTM */
   protected $item;

   /** @var array */
   protected $filter;

   public function __construct(CommonDBTM $item, array $filter) {
      $this->item   = $item;
      $this->filter = $filter;
   }

   public function getFileName(): string {
      $name = $this->item->getFriendlyName();
      $date = date('Y_m_d', time());

      // Replace name by itemtype + id if empty
      if ($name === '') {
         $name = "{$this->item->getTypeName(1)}_{$this->item->getId()}";
      }

      return  Toolbox::slugify("{$name}_$date") . ".csv";
   }

   public function getFileHeader(): array {
      return [
         __('ID'),
         _n('Date', 'Dates', 1),
         User::getTypeName(1),
         _n('Field', 'Fields', 1),
         _x('name', 'Update'),
      ];
   }

   public function getFileContent(): array {
      // Get logs from DB
      $filter = Log::convertFiltersValuesToSqlCriteria($this->filter);
      $logs = Log::getHistoryData($this->item, 0, 0, $filter);

      // Remove uneeded rows
      $logs = array_map(function($log) {
         unset($log['display_history']);
         unset($log['datatype']);
         return $log;
      }, $logs);

      return $logs;
   }
}

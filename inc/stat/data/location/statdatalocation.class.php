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

namespace Glpi\Stat\Data\Location;

use Glpi\Stat\StatData;
use Stat;
use Toolbox;

/**
 * Data for front/stat.location.php and front/stat.specific.php
 */
abstract class StatDataLocation extends StatData
{
   public function __construct(array $params) {
      parent::__construct($params);

      $data_key = $this->getKey();

      $data = Stat::getData(
         $params['itemtype'],
         $params['type'],
         $params['date1'],
         $params['date2'],
         $params['start'],
         $params['val'],
         $params['value2']
      );

      if (!isset($data[$data_key]) || !is_array($data[$data_key])) {
         return;
      }

      foreach ($data[$data_key] as $key => $val) {
         if ($val > 0) {
            $newkey = Toolbox::stripTags($key, true);
            $this->labels[] = $newkey;
            $this->series[] = ['name' => $newkey, 'data' => $val];
            $this->total += $val;
         }
      }
   }

   public abstract function getKey(): string;
}

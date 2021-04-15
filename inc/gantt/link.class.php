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

namespace Glpi\Gantt;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Class used to hold project task link details
 */
class Link {
   public $id;
   public $source;
   public $source_uuid;
   public $target;
   public $target_uuid;
   public $type; // possible values: "finish_to_start":"0", "start_to_start":"1", "finish_to_finish":"2", "start_to_finish":"3"
   public $lag;
   public $lead;

   public function __construct() {
      $this->id = 0;
      $this->source = 0;
      $this->target = 0;
      $this->source_uuid = "";
      $this->target_uuid = "";
      $this->type = 0;
      $this->lag = 0;
      $this->lead = 0;
   }

   /**
    * Enables Json serialization of Link objects
    */
   public function jsonSerialize() {
      return (array)$this;
   }
}

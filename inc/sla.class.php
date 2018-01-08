<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

/**
 * @since 9.2
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * SLA Class
**/
class SLA extends LevelAgreement {
   static protected $prefix            = 'sla';
   static protected $prefixticket      = '';
   static protected $levelclass        = 'SLALevel';
   static protected $levelticketclass  = 'SlaLevel_Ticket';
   static protected $forward_entity_to = ['SLALevel'];

   static function getTypeName($nb = 0) {
      // Acronymous, no plural
      return __('SLA');
   }

   function showFormWarning() {

   }

   function getAddConfirmation() {
      return [__("The assignment of a SLA to a ticket causes the recalculation of the date."),
              __("Escalations defined in the SLA will be triggered under this new date.")];
   }
}

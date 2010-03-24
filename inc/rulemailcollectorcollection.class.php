<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// OCS Rules collection class
class RuleMailCollectorCollection extends RuleCollection {

   // From RuleCollection
   public $stop_on_first_match=true;
   public $right = 'rule_mailcollector';
   public $menu_option='mailcollector';

   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][70];
   }

   function prepareInputDataForProcess($input,$params) {
      $input['from'] = $params['ticket']['user_email'];
      $input['mailcollector'] = $params['mailcollector'];
      $input['users_id'] = $params['users_id'];

      $fields = $this->getFieldsToLookFor();

      if (in_array('groups',$fields)) {
         foreach (Group_User::getUserGroups($input['users_id']) as $group) {
            $input['GROUPS'][] = $group['id'];
         }
      }

      return $input;
   }

}


?>

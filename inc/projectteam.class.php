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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

//!  ProjectTeam Class
/**
 * This class is used to manage the project team
 * @see Project
 * @author Julien Dombre
 * @since 0.85
 **/
class ProjectTeam extends CommonDBRelation {

   // From CommonDBTM
   public $dohistory                  = true;
   public $no_form_page               = true;

   // From CommonDBRelation
   static public $itemtype_1          = 'Project';
   static public $items_id_1          = 'projects_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

   static public $available_types     = ['User', 'Group', 'Supplier', 'Contact'];


   /**
    * @see CommonDBTM::getNameField()
   **/
   static function getNameField() {
      return 'id';
   }


   static function getTypeName($nb = 0) {
      return _n('Project team', 'Project teams', $nb);
   }


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (self::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Project' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = $item->getTeamCount();
               }
               return self::createTabEntry(self::getTypeName(1), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Project' :
            $item->showTeam($item);
            return true;
      }
   }

   /**
    * Duplicate all teams from a project template to his clone
    *
    * @since 9.2
    *
    * @param integer $oldid        ID of the item to clone
    * @param integer $newid        ID of the item cloned
    **/
   static function cloneProjectTeam ($oldid, $newid) {
      global $DB;

      $team = self::getTeamFor($oldid);
      foreach ($team as $type) {
         foreach ($type as $data) {
            $cd                  = new self();
            unset($data['id']);
            $data['projects_id'] = $newid;
            $cd->add($data);
         }
      }
   }


   /**
    * Get team for a project
    *
    * @param $projects_id
   **/
   static function getTeamFor($projects_id) {
      global $DB;

      $team = [];
      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => ['projects_id' => $projects_id]
      ]);

      while ($data = $iterator->next()) {
         if (!isset($team[$data['itemtype']])) {
            $team[$data['itemtype']] = [];
         }
         $team[$data['itemtype']][] = $data;
      }

      // Define empty types
      foreach (static::$available_types as $type) {
         if (!isset($team[$type])) {
            $team[$type] = [];
         }
      }

      return $team;
   }

}

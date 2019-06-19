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

class RuleSIEMEventFilterCollection extends RuleCollection
{

   // From RuleCollection
   static $rightname             = 'rule_event';
   public $stop_on_first_match   = true;
   public $menu_option           = 'rulesiemeventfilter';


   /**
    * @param $entity (default 0)
   **/
   function __construct($entity = 0)
   {
      $this->entity = $entity;
   }

   static function canView()
   {
      return Session::haveRightsOr(self::$rightname, [READ, RuleSIEMEventFilter::PARENT]);
   }

   function canList()
   {
      return static::canView();
   }

   function getTitle()
   {
      return __('Rules for event filtering');
   }

   function showInheritedTab()
   {
      return (Session::haveRight(self::$rightname, RuleSIEMEventFilter::PARENT) && ($this->entity));
   }

   function showChildrensTab()
   {
      return (Session::haveRight(self::$rightname, READ)
              && (count($_SESSION['glpiactiveentities']) > 1));
   }
}

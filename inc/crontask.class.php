<?php

/*
 * @version $Id: bookmark.class.php 8095 2009-03-19 18:27:00Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * CronTask class
 */
class CronTask extends CommonDBTM{

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_crontasks";
      $this->type=CRONTASK_TYPE;
   }

   /**
    * Translate task description
    *
    * @param $id integer
    * @param $module string : name of plugin (empty for glpi core task)
    * @param $name string : name of the task
    * @return string
    */
   static public function getDescription($id, $module, $name) {
      global $LANG;

      if (empty($module)) {
         if ($id>=1 && $id<=12) {
            return $LANG['crontask'][$id];
         }
         return $LANG['crontask'][30].' '.$id;
      }
      // plugin case
   }

   /**
    * Translate state to string
    *
    * @param $state integer
    * @return string
    */
   static public function getStateName($state) {
      global $LANG;

      switch ($state) {
         case CRONTASK_STATE_RUNNING:
            return $LANG['crontask'][33];
            break;
         case CRONTASK_STATE_WAITING:
            return $LANG['crontask'][32];
            break;
         case CRONTASK_STATE_DISABLE:
            return $LANG['crontask'][31];
            break;
      }
      return '???';
   }

   /**
    * Translate Mode to string
    *
    * @param $mode integer
    * @return string
    */
   static public function getModeName($mode) {
      global $LANG;

      switch ($mode) {
         case CRONTASK_MODE_INTERNAL:
            return $LANG['crontask'][34];
            break;
         case CRONTASK_MODE_EXTERNAL:
            return $LANG['crontask'][35];
            break;
      }
      return '???';
   }
}

?>
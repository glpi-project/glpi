<?php
/*
 * @version $Id: central.php 9334 2009-11-24 17:48:06Z moyo $
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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// class Preference for the current connected User
class Preference extends CommonGLPI {

   public $type = 'prefs';

   static function getTypeName() {
      global $LANG;

      return $LANG['Menu'][11];
   }

   function defineTabs($ID,$withtemplate){
      global $LANG;

      $tabs[1] = $LANG['title'][26];
      $tabs[2] = $LANG['setup'][6];

      return $tabs;
   }
}

?>
<?php
/*
 * @version $Id: ticketplanning.class.php 9519 2009-12-07 13:12:41Z walid $
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// CLASS Planning

class Planning extends CommonDBTM  {

/**
 * Get planning state name
 *
 * @param $value status ID
 */
   static function getState($value) {
      global $LANG;

      switch ($value) {
         case 0 :
            return $LANG['planning'][16];
            break;

         case 1 :
            return $LANG['planning'][17];
            break;

         case 2 :
            return $LANG['planning'][18];
            break;
      }
   }

   /**
    * Dropdown of planning state
    *
    * @param $name select name
    * @param $value default value
    */
   static function dropdownState($name,$value='') {
      global $LANG;

      echo "<select name='$name' id='$name'>";
      echo "<option value='0'".($value==0?" selected ":"").">".$LANG['planning'][16]."</option>";
      echo "<option value='1'".($value==1?" selected ":"").">".$LANG['planning'][17]."</option>";
      echo "<option value='2'".($value==2?" selected ":"").">".$LANG['planning'][18]."</option>";
      echo "</select>";
   }
}

?>

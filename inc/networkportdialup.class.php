<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// NetworkPortDialup class : dialup instantiation of NetworkPort. A dialup connexion also known as
/// point-to-point protocol allows connexion between to sites through specific connexion
/// @since 0.84
class NetworkPortDialup extends NetworkPortInstantiation {


   static function getTypeName($nb=0) {
     return __('Connection by dial line - Dialup Port');
   }


   /**
    * @see NetworkPortInstantiation::getInstantiationHTMLTableHeaders
   **/
   function getInstantiationHTMLTableHeaders(HTMLTableGroup $group, HTMLTableSuperHeader $super,
                                             HTMLTableSuperHeader $internet_super=NULL,
                                             HTMLTableHeader $father=NULL,
                                             array $options=array()) {

      $header = $group->addHeader('Connected', __('Connected to'), $super);

      parent::getInstantiationHTMLTableHeaders($group, $super, $internet_super, $header, $options);
      return NULL;
   }


  /**
   * @see NetworkPortInstantiation::getInstantiationHTMLTable()
  **/
   function getInstantiationHTMLTable(NetworkPort $netport, HTMLTableRow $row,
                                      HTMLTableCell $father=NULL, array $options=array()) {

      return parent::getInstantiationHTMLTableWithPeer($netport, $row, $father, $options);
   }


   /**
    * @see NetworkPortInstantiation::showInstantiationForm()
   **/
   function showInstantiationForm(NetworkPort $netport, $options=array(), $recursiveItems) {

      echo "<tr class='tab_bg_1'>";
      $this->showMacField($netport, $options);

      echo "<td>".__('Connected to').'</td><td>';
      self::showConnection($netport, true);
      echo "</td>";

      echo "</tr>";
   }

}
?>

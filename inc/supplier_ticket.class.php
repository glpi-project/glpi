<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class Supplier_Ticket
 *
 * @since version 0.84
**/
class Supplier_Ticket extends CommonITILActor {

   // From CommonDBRelation
   static public $itemtype_1 = 'Ticket';
   static public $items_id_1 = 'tickets_id';
   static public $itemtype_2 = 'Supplier';
   static public $items_id_2 = 'suppliers_id';


   /**
    * @param $items_id
    * @param $email
    *
    * @since version 0.85
   **/
   function isSupplierEmail($items_id, $email) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                LEFT JOIN `glpi_suppliers`
                      ON (`".$this->getTable()."`.`suppliers_id` = `glpi_suppliers`.`id`)
                WHERE `".$this->getTable()."`.`tickets_id` = '".$items_id."'
                      AND `glpi_suppliers`.`email` = '$email'";

      foreach ($DB->request($query) as $data) {
         return true;
      }
      return false;
   }
   
   /**
    * Print the object user form for notification
    *
    * @param $ID              integer ID of the item
    * @param $options   array
    *
    * @return Nothing (display)
   **/
   function showNotificationForm($ID, $options=array()) {
      global $CFG_GLPI;

      $this->check($ID, UPDATE);

      if (!isset($this->fields['suppliers_id'])) {
         return false;
      }
      $item = new static::$itemtype_1();

      echo "<br><form method='post' action='".$_SERVER['PHP_SELF']."'>";
      echo "<div class='center'>";
      echo "<table class='tab_cadre' width='80%'>";
      echo "<tr class='tab_bg_2'><td>".$item->getTypeName(1)."</td>";
      echo "<td>";
      if ($item->getFromDB($this->fields[static::getItilObjectForeignKey()])) {
         echo $item->getField('name');
      }
      echo "</td></tr>";

      $supplier      = new Supplier();
      $default_email = "";
      if ($supplier->getFromDB($this->fields["suppliers_id"])) {
         $default_email = $supplier->fields['email'];
      }

      echo "<tr class='tab_bg_2'><td>".__('User')."</td>";
      echo "<td>".$supplier->getName()."</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Email Followup')."</td>";
      echo "<td>";
      Dropdown::showYesNo('use_notification', $this->fields['use_notification']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Email')."</td>";
      echo "<td>";
      if (empty($this->fields['alternative_email'])) {
         $this->fields['alternative_email'] = $default_email;
      }
      echo "<input type='text' size='40' name='alternative_email' value='".
               $this->fields['alternative_email']."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='2'>";
      echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();
   }
}
?>

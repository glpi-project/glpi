<?php
/*
 * @version $Id$
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

/**
 * Contact class
 */
class Contact extends CommonDBTM{

   /**
    * Constructor
    **/
   function __construct () {
      $this->table="glpi_contacts";
      $this->type=CONTACT_TYPE;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      $cs = new ContactSupplier();
      $cs->cleanDBonItemDelete($this->type,$ID);
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      if ($ID>0) {
         $ong[1]=$LANG['Menu'][23];
         if (haveRight("document","r")) {
            $ong[5]=$LANG['Menu'][27];
         }
         if (haveRight("link","r")) {
            $ong[7]=$LANG['title'][34];
         }
         if (haveRight("notes","r")) {
            $ong[10]=$LANG['title'][37];
         }
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }
      return $ong;
   }

   /**
    * Get address of the contact (company one)
    *
    *@return string containing the address
    *
    **/
   function GetAddress() {
      global $DB;

      $query = "SELECT `glpi_suppliers`.`name`, `glpi_suppliers`.`address`,
                       `glpi_suppliers`.`postcode`, `glpi_suppliers`.`town`,
                       `glpi_suppliers`.`state`, `glpi_suppliers`.`country`
                FROM `glpi_suppliers`, `glpi_contacts_suppliers`
                WHERE `glpi_contacts_suppliers`.`contacts_id` = '".$this->fields["id"]."'
                      AND `glpi_contacts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            if ($data=$DB->fetch_assoc($result)) {
               return $data;
            }
         }
      }
      return "";
   }

   /**
    * Get website of the contact (company one)
    *
    *@return string containing the website
    *
    **/
   function GetWebsite() {
      global $DB;

      $query = "SELECT `glpi_suppliers`.`website` as website
                FROM `glpi_suppliers`, `glpi_contacts_suppliers`
                WHERE `glpi_contacts_suppliers`.`contacts_id` = '".$this->fields["id"]."'
                      AND `glpi_contacts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            return $DB->result($result, 0, "website");
         } else {
            return "";
         }
      }
   }

   /**
    * Print the contact form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the contact to print
    *@param $withtemplate='' boolean : template or basic item
    *
    *
    *@return Nothing (display)
    *
    **/
   function showForm ($target,$ID,$withtemplate='') {
      global $CFG_GLPI, $LANG;

      if (!haveRight("contact_enterprise","r")) {
         return false;
      }
      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, $withtemplate,getActiveTab($this->type));
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][48]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40,
                              $this->fields["entities_id"]);
      echo "</td>";
      echo "<td rowspan='7' class='middle right'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='center middle' rowspan='7'>.<textarea cols='45' rows='9' name='comment' >"
         .$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][43]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("firstname",$this->table,"firstname",
                              $this->fields["firstname"],40,$this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][35]."&nbsp;: </td>";
      echo "<td>";
      autocompletionTextField("phone",$this->table,"phone",$this->fields["phone"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][35]." 2&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("phone2",$this->table,"phone2",$this->fields["phone2"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][42]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("mobile",$this->table,"mobile",$this->fields["mobile"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][30]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("fax",$this->table,"fax",$this->fields["fax"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][14]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("email",$this->table,"email",$this->fields["email"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][17]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_contactstypes","contactstypes_id",$this->fields["contactstypes_id"]);
      echo "</td>";
      echo "<td></td><td class='center'>";
      if ($ID>0) {
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/contact.vcard.php?id=$ID'>".
                $LANG['common'][46]."</a>";
      }
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

   return true;
   }
}

class ContactSupplier extends CommonDBRelation{

   /**
    * Constructor
    **/
   function __construct () {
      $this->table = 'glpi_contacts_suppliers';
      $this->type = CONTACTSUPPLIER_TYPE;

      $this->itemtype_1 = CONTACT_TYPE;
      $this->items_id_1 = 'contacts_id';

      $this->itemtype_2 = ENTERPRISE_TYPE;
      $this->items_id_2 = 'suppliers_id';
   }

}
?>
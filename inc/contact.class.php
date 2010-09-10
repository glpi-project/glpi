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

   // From CommonDBTM
   public $dohistory = true;

   static function getTypeName() {
      global $LANG;

      return $LANG['common'][92];
   }

   function canCreate() {
      return haveRight('contact_enterprise', 'w');
   }

   function canView() {
      return haveRight('contact_enterprise', 'r');
   }

   function cleanDBonPurge() {

      $cs = new Contact_Supplier();
      $cs->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }

   function defineTabs($options=array()) {
      global $LANG;

      $ong=array();
      if ($this->fields['id'] > 0) {
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
         $ong[12]=$LANG['title'][38];
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
   * @param $ID integer ID of the item
   * @param $options array
   *     - target filename : where to go when done.
   *     - withtemplate boolean : template or basic item
   *
   * @return Nothing (display)
   *
   **/
   function showForm ($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      if (!haveRight("contact_enterprise","r")) {
         return false;
      }
      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][48]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='7' class='middle right'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='center middle' rowspan='7'>.<textarea cols='45' rows='9' name='comment' >"
         .$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][43]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "firstname");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][35]."&nbsp;: </td>";
      echo "<td>";
      autocompletionTextField($this, "phone");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][35]." 2&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "phone2");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][42]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "mobile");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][30]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "fax");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][14]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "email");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][17]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('ContactType', array('value' => $this->fields["contacttypes_id"]));
      echo "</td>";
      echo "<td></td><td class='center'>";
      if ($ID>0) {
         echo "<a target=''_blank' href='".$CFG_GLPI["root_doc"]."/front/contact.form.php?getvcard=1&amp;id=$ID'>".
                $LANG['common'][46]."</a>";
      }
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][48];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[11]['table']     = $this->getTable();
      $tab[11]['field']     = 'firstname';
      $tab[11]['linkfield'] = 'firstname';
      $tab[11]['name']      = $LANG['common'][43];

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'phone';
      $tab[3]['linkfield'] = 'phone';
      $tab[3]['name']      = $LANG['help'][35];

      $tab[4]['table']     = $this->getTable();
      $tab[4]['field']     = 'phone2';
      $tab[4]['linkfield'] = 'phone2';
      $tab[4]['name']      = $LANG['help'][35]." 2";

      $tab[10]['table']     = $this->getTable();
      $tab[10]['field']     = 'mobile';
      $tab[10]['linkfield'] = 'mobile';
      $tab[10]['name']      = $LANG['common'][42];

      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'fax';
      $tab[5]['linkfield'] = 'fax';
      $tab[5]['name']      = $LANG['financial'][30];

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'email';
      $tab[6]['linkfield'] = 'email';
      $tab[6]['name']      = $LANG['setup'][14];
      $tab[6]['datatype']  = 'email';

      $tab[9]['table']     = 'glpi_contacttypes';
      $tab[9]['field']     = 'name';
      $tab[9]['linkfield'] = 'contacttypes_id';
      $tab[9]['name']      = $LANG['common'][17];

      $tab[8]['table']         = 'glpi_suppliers';
      $tab[8]['field']         = 'name';
      $tab[8]['linkfield']     = '';
      $tab[8]['name']          = $LANG['financial'][65];
      $tab[8]['forcegroupby']  = true;
      $tab[8]['datatype']      = 'itemlink';
      $tab[8]['itemlink_type'] = 'Supplier';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[90]['table']     = $this->getTable();
      $tab[90]['field']     = 'notepad';
      $tab[90]['linkfield'] = '';
      $tab[90]['name']      = $LANG['title'][37];

      $tab[80]['table']='glpi_entities';
      $tab[80]['field']='completename';
      $tab[80]['linkfield']='entities_id';
      $tab[80]['name']=$LANG['entity'][0];

      $tab[86]['table']     = $this->getTable();
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = 'is_recursive';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      return $tab;
   }

   /**
    * Print the HTML array for entreprises on the current contact
    *
    *@return Nothing (display)
    *
    **/
   function showSuppliers() {
      global $DB,$CFG_GLPI, $LANG;

      $instID = $this->fields['id'];
      if (!$this->can($instID,'r')) {
         return false;
      }
      $canedit = $this->can($instID,'w');

      $query = "SELECT `glpi_contacts_suppliers`.`id`, `glpi_suppliers`.`id` AS entID,
                       `glpi_suppliers`.`name` AS name, `glpi_suppliers`.`website` AS website,
                       `glpi_suppliers`.`fax` AS fax, `glpi_suppliers`.`phonenumber` AS phone,
                       `glpi_suppliers`.`suppliertypes_id` AS type, `glpi_suppliers`.`is_deleted`,
                       `glpi_entities`.`id` AS entity
                FROM `glpi_contacts_suppliers`, `glpi_suppliers`
                LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`glpi_suppliers`.`entities_id`)
                WHERE `glpi_contacts_suppliers`.`contacts_id` = '$instID'
                      AND `glpi_contacts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`".
                           getEntitiesRestrictRequest(" AND","glpi_suppliers",'','',true) ."
                ORDER BY `glpi_entities`.`completename`, `name`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/contact.form.php\">";
      echo "<br><br><div class='spaced'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>".$LANG['financial'][65]."&nbsp;:</th></tr>";
      echo "<tr><th>".$LANG['financial'][26]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['financial'][79]."</th>";
      echo "<th>".$LANG['help'][35]."</th>";
      echo "<th>".$LANG['financial'][30]."</th>";
      echo "<th>".$LANG['financial'][45]."</th>";
      echo "<th>&nbsp;</th></tr>";

      $used=array();
      if ($number>0) {
         initNavigateListItems('Supplier',$LANG['common'][92]." = ".$this->fields['name']);
         while ($data= $DB->fetch_array($result)) {
            $ID=$data["id"];
            addToNavigateListItems('Supplier',$data["entID"]);
            $used[$data["entID"]]=$data["entID"];
            $website=$data["website"];
            if (!empty($website)) {
               $website=$data["website"];
               if (!preg_match("?https*://?",$website)) {
                  $website="http://".$website;
               }
               $website="<a target=_blank href='$website'>".$data["website"]."</a>";
            }
            echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
            echo "<td class='center'>";
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/supplier.form.php?id=".$data["entID"]."'>".
                   Dropdown::getDropdownName("glpi_suppliers",$data["entID"])."</a></td>";
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",$data["entity"])."</td>";
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_suppliertypes",$data["type"])."</td>";
            echo "<td class='center' width='80'>".$data["phone"]."</td>";
            echo "<td class='center' width='80'>".$data["fax"]."</td>";
            echo "<td class='center'>".$website."</td>";
            echo "<td class='tab_bg_2 center'>";
            if ($canedit) {
               echo "<a href='".
                      $CFG_GLPI["root_doc"]."/front/contact.form.php?deletecontactsupplier=1".
                     "&amp;id=$ID&amp;contacts_id=$instID'><strong>".$LANG['buttons'][6]."</strong></a>";
            } else {
               echo "&nbsp;";
            }
            echo "</td></tr>";
         }
      }
      if ($canedit) {
         if ($this->fields["is_recursive"]) {
            $nb=countElementsInTableForEntity("glpi_suppliers",getSonsOf("glpi_entities",
                                              $this->fields["entities_id"]));
         } else {
            $nb=countElementsInTableForEntity("glpi_suppliers",$this->fields["entities_id"]);
         }
         if ($nb>count($used)) {
            echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center' colspan='4'>";
            echo "<input type='hidden' name='contacts_id' value='$instID'>";
            Dropdown::show('Supplier',
                     array('used'         => $used,
                           'entity'       => $this->fields["entities_id"],
                           'entity_sons'  => $this->fields["is_recursive"]));

            echo "&nbsp;&nbsp;<input type='submit' name='addcontactsupplier' value=\"".
                               $LANG['buttons'][8]."\" class='submit'>";
            echo "</td><td>&nbsp;</td><td>&nbsp;</td>";
            echo "</tr>";
         }
      }
      echo "</table></div></form>";
   }

   /**
    * Generate the Vcard for the current Contact
    *
    *@return Nothing (display)
    *
    **/
   function generateVcard() {

      include (GLPI_ROOT . "/lib/vcardclass/classes-vcard.php");

      if (!$this->can($this->fields['id'],'r')) {
         return false;
      }
      // build the Vcard
      $vcard = new vCard();

      $vcard->setName($this->fields["name"], $this->fields["firstname"], "", "");

      $vcard->setPhoneNumber($this->fields["phone"], "PREF;WORK;VOICE");
      $vcard->setPhoneNumber($this->fields["phone2"], "HOME;VOICE");
      $vcard->setPhoneNumber($this->fields["mobile"], "WORK;CELL");

      $addr=$this->GetAddress();
      if (is_array($addr)) {
         $vcard->setAddress($addr["name"], "", $addr["address"], $addr["town"], $addr["state"],
                            $addr["postcode"], $addr["country"],"WORK;POSTAL");
      }
      $vcard->setEmail($this->fields["email"]);
      $vcard->setNote($this->fields["comment"]);
      $vcard->setURL($this->GetWebsite(), "WORK");

      // send the  VCard
      $output = $vcard->getVCard();
      $filename =$vcard->getFileName();      // "xxx xxx.vcf"

      @Header("Content-Disposition: attachment; filename=\"$filename\"");
      @Header("Content-Length: ".utf8_strlen($output));
      @Header("Connection: close");
      @Header("content-type: text/x-vcard; charset=UTF-8");

      echo $output;
   }
}

?>
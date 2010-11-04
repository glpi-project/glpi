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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Supplier class (suppliers)
 */
class Supplier extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;

/**
 * Name of the type
 *
 * @param $nb : number of item in the type
 *
 * @return $LANG
**/
   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['Menu'][23];
      }
      return $LANG['financial'][26];
   }


   function canCreate() {
      return haveRight('contact_enterprise', 'w');
   }


   function canView() {
      return haveRight('contact_enterprise', 'r');
   }


   function cleanDBonPurge() {
      global $DB;

      $job = new Ticket;

      $cs = new Contract_Supplier();
      $cs->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $cs = new Contact_Supplier();
      $cs->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   function defineTabs($options=array()) {
      global $LANG,$CFG_GLPI;

      $ong = array();
      if ($this->fields['id'] > 0) {
         if (haveRight("contact_enterprise","r")) {
            $ong[1] = $LANG['Menu'][22];
         }
         if (haveRight("contract","r")) {
            $ong[4] = $LANG['Menu'][25];
         }
         if (!isset($options['withtemplate']) || empty($options['withtemplate'])) {
            $ong[15] = $LANG['common'][96];
         }
         if (haveRight("document","r")) {
            $ong[5] = $LANG['Menu'][27];
         }
         if (haveRight("show_all_ticket","1")) {
            $ong[6] = $LANG['title'][28];
         }
         if (haveRight("link","r")) {
            $ong[7] = $LANG['title'][34];
         }
         if (haveRight("notes","r")) {
            $ong[10] = $LANG['title'][37];
         }
         $ong[12] = $LANG['title'][38];

      } else { // New item
         $ong[1] = $LANG['title'][26];
      }

      return $ong;
   }


   /**
    * Print the enterprise form
    *
    * @param $ID Integer : Id of the computer or the template to print
    * @param $options array
    *     - target form target
    *     - withtemplate boolean : template or basic item
    *
    *@return Nothing (display)
   **/
   function showForm ($ID, $options=array()) {
      global $CFG_GLPI,$LANG;

      // Show Supplier or blank form
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
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".$LANG['financial'][79]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('SupplierType', array('value' => $this->fields["suppliertypes_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][35]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "phonenumber");
      echo "</td>";
      echo "<td rowspan='8' class='middle right'>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='8'>";
      echo "<textarea cols='45' rows='13' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][30]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "fax");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][45]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "website");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][14]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "email");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='middle'>".$LANG['financial'][44]."&nbsp;:</td>";
      echo "<td class='middle'>";
      echo "<textarea cols='37' rows='3' name='address'>".$this->fields["address"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][100]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "postcode", array('size' => 10));
      echo "&nbsp;&nbsp;".$LANG['financial'][101]."&nbsp;:&nbsp;";
      autocompletionTextField($this, "town", array('size' => 23));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][102]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "state");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][103]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "country");
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
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table'] = $this->getTable();
      $tab[3]['field'] = 'address';
      $tab[3]['name']  = $LANG['financial'][44];

      $tab[10]['table'] = $this->getTable();
      $tab[10]['field'] = 'fax';
      $tab[10]['name']  = $LANG['financial'][30];

      $tab[11]['table'] = $this->getTable();
      $tab[11]['field'] = 'town';
      $tab[11]['name']  = $LANG['financial'][101];

      $tab[14]['table'] = $this->getTable();
      $tab[14]['field'] = 'postcode';
      $tab[14]['name']  = $LANG['financial'][100];

      $tab[12]['table'] = $this->getTable();
      $tab[12]['field'] = 'state';
      $tab[12]['name']  = $LANG['financial'][102];

      $tab[13]['table'] = $this->getTable();
      $tab[13]['field'] = 'country';
      $tab[13]['name']  = $LANG['financial'][103];

      $tab[4]['table']    = $this->getTable();
      $tab[4]['field']    = 'website';
      $tab[4]['name']     = $LANG['financial'][45];
      $tab[4]['datatype'] = 'weblink';

      $tab[5]['table'] = $this->getTable();
      $tab[5]['field'] = 'phonenumber';
      $tab[5]['name']  = $LANG['help'][35];

      $tab[6]['table']    = $this->getTable();
      $tab[6]['field']    = 'email';
      $tab[6]['name']     = $LANG['setup'][14];
      $tab[6]['datatype'] = 'email';

      $tab[9]['table'] = 'glpi_suppliertypes';
      $tab[9]['field'] = 'name';
      $tab[9]['name']  = $LANG['financial'][79];

      $tab[8]['table']         = 'glpi_contacts';
      $tab[8]['field']         = 'completename';
      $tab[8]['name']          = $LANG['financial'][46];
      $tab[8]['forcegroupby']  = true;
      $tab[8]['datatype']      = 'itemlink';
      $tab[8]['itemlink_type'] = 'Contact';
      $tab[8]['massiveaction'] = false;
      $tab[8]['joinparams']    = array('beforejoin'
                                       => array('table'      => 'glpi_contacts_suppliers',
                                                'joinparams' => array('jointype' => 'child')));

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      $tab[90]['table']         = $this->getTable();
      $tab[90]['field']         = 'notepad';
      $tab[90]['name']          = $LANG['title'][37];
      $tab[90]['massiveaction'] = false;

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['massiveaction'] = false;

      $tab[86]['table']    = $this->getTable();
      $tab[86]['field']    = 'is_recursive';
      $tab[86]['name']     = $LANG['entity'][9];
      $tab[86]['datatype'] = 'bool';

      return $tab;
   }


   /**
    * Get links for an enterprise (website / edit)
    *
    * @param $withname boolean : also display name ?
   **/
   function getLinks($withname=false) {
      global $CFG_GLPI, $LANG;

      $ret = '&nbsp;&nbsp;&nbsp;&nbsp;';

      if ($withname) {
         $ret .= $this->fields["name"];
         $ret .= "&nbsp;&nbsp;";
      }

      if (!empty($this->fields['website'])) {
         $ret .= "<a href='".formatOutputWebLink($this->fields['website'])."' target='_blank'>
                  <img src='".$CFG_GLPI["root_doc"]."/pics/web.png' class='middle'alt='".
                   $LANG['common'][4]."' title='".$LANG['common'][4]."'></a>&nbsp;&nbsp;";
      }

      if ($this->can($this->fields['id'],'r')) {
         $ret .= "<a href='".$CFG_GLPI["root_doc"]."/front/supplier.form.php?id=".
                   $this->fields['id']."'>
                  <img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' class='middle' alt='".
                   $LANG['buttons'][14]."' title='".$LANG['buttons'][14]."'></a>";
      }
      return $ret;
   }


   /**
    * Show contacts asociated to an enterprise
   **/
   function showContacts() {
      global $DB,$CFG_GLPI, $LANG;

      $instID = $this->fields['id'];
      if (!$this->can($instID,'r')) {
         return false;
      }
      $canedit = $this->can($instID,'w');

      $query = "SELECT `glpi_contacts`.*,
                       `glpi_contacts_suppliers`.`id` AS ID_ent,
                       `glpi_entities`.`id` AS entity
                FROM `glpi_contacts_suppliers`, `glpi_contacts`
                LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`glpi_contacts`.`entities_id`)
                WHERE `glpi_contacts_suppliers`.`contacts_id`=`glpi_contacts`.`id`
                      AND `glpi_contacts_suppliers`.`suppliers_id` = '$instID'" .
                      getEntitiesRestrictRequest(" AND", "glpi_contacts", '', '', true) ."
                ORDER BY `glpi_entities`.`completename`, `glpi_contacts`.`name`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      echo "<div class='firstbloc'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='9'>";
      if ($DB->numrows($result)==0) {
         echo $LANG['financial'][40];
      } else if ($DB->numrows($result)==1) {
         echo $LANG['financial'][41];
      } else {
         echo $LANG['financial'][46];
      }
      echo "</th></tr>";

      echo "<tr><th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['help'][35]."</th>";
      echo "<th>".$LANG['help'][35]." 2</th>";
      echo "<th>".$LANG['common'][42]."</th>";
      echo "<th>".$LANG['financial'][30]."</th>";
      echo "<th>".$LANG['setup'][14]."</th>";
      echo "<th>".$LANG['common'][17]."</th>";
      echo "<th>&nbsp;</th></tr>";

      $used = array();
      if ($number) {
         initNavigateListItems('Contact', $LANG['financial'][26]." = ".$this->fields['name']);

         while ($data=$DB->fetch_array($result)) {
            $ID                = $data["ID_ent"];
            $used[$data["id"]] = $data["id"];
            addToNavigateListItems('Contact',$data["id"]);

            echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
            echo "<td class='center'>";
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/contact.form.php?id=".$data["id"]."'>".
                   $data["name"]." ".$data["firstname"]."</a></td>";
            echo "<td class='center' width='100'>".Dropdown::getDropdownName("glpi_entities",
                                                                             $data["entity"])."</td>";
            echo "<td class='center' width='100'>".$data["phone"]."</td>";
            echo "<td class='center' width='100'>".$data["phone2"]."</td>";
            echo "<td class='center' width='100'>".$data["mobile"]."</td>";
            echo "<td class='center' width='100'>".$data["fax"]."</td>";
            echo "<td class='center'>";
            echo "<a href='mailto:".$data["email"]."'>".
                   $DB->result($result, $i, "glpi_contacts.email")."</a></td>";
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_contacttypes",
                                                                 $data["contacttypes_id"])."</td>";
            echo "<td class='center' class='tab_bg_2'>";

            if ($canedit) {
               echo "<a href='".$CFG_GLPI["root_doc"].
                     "/front/contact.form.php?deletecontactsupplier=1&amp;id=$ID&amp;contacts_id=".
                     $data["id"]."'><img src='".$CFG_GLPI["root_doc"]."/pics/delete2.png' alt='".
                     $LANG['buttons'][6]."'></a>";
            } else {
               echo "&nbsp;";
            }
            echo "</td></tr>";
            $i++;
         }
      }

      echo "</table></div>";

      if ($canedit) {
         if ($this->fields["is_recursive"]) {
            $nb = countElementsInTableForEntity("glpi_contacts",
                                                getSonsOf("glpi_entities",
                                                          $this->fields["entities_id"]));
         } else {
            $nb = countElementsInTableForEntity("glpi_contacts", $this->fields["entities_id"]);
         }

         if ($nb>count($used)) {
            echo "<div class='spaced'>";
            echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/contact.form.php\">";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG['financial'][33]."</tr>";
            echo "<tr><td class='tab_bg_2 center'>";
            echo "<input type='hidden' name='suppliers_id' value='$instID'>";

            Dropdown::show('Contact',
                           array('used'        => $used,
                                 'entity'      => $this->fields["entities_id"],
                                 'entity_sons' => $this->fields["is_recursive"]));

            echo "</td><td class='tab_bg_2 center'>";
            echo "<input type='submit' name='addcontactsupplier' value=\"".$LANG['buttons'][8]."\"
                   class='submit'>";
            echo "</td></tr>";
         }
         echo "</table></form></div>";
      }
   }


   /**
    * Print the HTML array for infocoms linked
    *
    *@return Nothing (display)
    *
   **/
   function showInfocoms() {
      global $DB, $CFG_GLPI, $LANG;

      $instID = $this->fields['id'];
      if (!$this->can($instID,'r')) {
         return false;
      }

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_infocoms`
                WHERE `suppliers_id` = '$instID'
                      AND `itemtype` NOT IN ('ConsumableItem', 'CartridgeItem', 'Software')
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      echo "<div class='spaced'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>";
      printPagerForm();
      echo "</th><th colspan='3'>";
      if ($DB->numrows($result)==0) {
         echo $LANG['document'][13];
      } else {
         echo $LANG['document'][19];
      }
      echo "</th></tr>";
      echo "<tr><th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['common'][19]."</th>";
      echo "<th>".$LANG['common'][20]."</th>";
      echo "</tr>";

      $num = 0;
      for ($i=0 ; $i < $number ; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");

         if (!class_exists($itemtype)) {
            continue;
         }
         $item = new $itemtype();

         if ($item->canView()) {
            $linktype  = $itemtype;
            $linkfield = 'id';
            $itemtable = getTableForItemType($itemtype);

            $query = "SELECT `glpi_infocoms`.`entities_id`, `name`, `$itemtable`.*
                      FROM `glpi_infocoms`
                      INNER JOIN `$itemtable` ON (`$itemtable`.`id` = `glpi_infocoms`.`items_id`) ";

            // Set $linktype for entity restriction AND link to search engine
            if ($itemtype == 'Cartridge') {
               $query .= "INNER JOIN `glpi_cartridgeitems`
                            ON (`glpi_cartridgeitems`.`id`=`glpi_cartridges`.`cartridgeitems_id`) ";

               $linktype  = 'CartridgeItem';
               $linkfield = 'cartridgeitems_id';
            }

            if ($itemtype == 'Consumable' ) {
               $query .= "INNER JOIN `glpi_consumableitems`
                            ON (`glpi_consumableitems`.`id`=`glpi_consumables`.`consumableitems_id`) ";

               $linktype  = 'ConsumableItem';
               $linkfield = 'consumableitems_id';
            }

            $linktable = getTableForItemType($linktype);

            $query .= "WHERE `glpi_infocoms`.`itemtype` = '$itemtype'
                             AND `glpi_infocoms`.`suppliers_id` = '$instID'".
                             getEntitiesRestrictRequest(" AND", $linktable) ."
                       ORDER BY `glpi_infocoms`.`entities_id`,
                                `$linktable`.`name`";

            $result_linked = $DB->query($query);
            $nb = $DB->numrows($result_linked);

            // Set $linktype for link to search engine pnly
            if ($itemtype == 'SoftwareLicense' && $nb>$_SESSION['glpilist_limit']) {
               $linktype  = 'Software';
               $linkfield = 'softwares_id';
            }

            if ($nb>$_SESSION['glpilist_limit']) {
               echo "<tr class='tab_bg_1'>";
               echo "<td class='center'>".$item->getTypeName($nb)."&nbsp;:&nbsp;$nb</td>";
               echo "<td class='center' colspan='2'>";
               echo "<a href='". getItemTypeSearchURL($linktype) . "?" .
                      rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$instID) . "&" .
                      rawurlencode("field[0]") . "=53&sort=80&order=ASC&is_deleted=0&start=0". "'>" .
                      $LANG['reports'][57]."</a></td>";

               echo "<td class='center'>-</td><td class='center'>-</td></tr>";

            } else if ($nb) {
               for ($prem=true ; $data=$DB->fetch_assoc($result_linked) ; $prem=false) {
                  $ID = "";
                  if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                     $ID = " (".$data["id"].")";
                  }
                  $link = getItemTypeFormURL($linktype);
                  $name = "<a href=\"".$link."?id=".$data[$linkfield]."\">".$data["name"]."$ID</a>";

                  echo "<tr class='tab_bg_1'>";
                  if ($prem) {
                     echo "<td class='center top' rowspan='$nb'>".$item->getTypeName($nb)
                            .($nb>1?"&nbsp;:&nbsp;$nb</td>":"</td>");
                  }
                  echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                                       $data["entities_id"])."</td>";
                  echo "<td class='center";
                  echo (isset($data['is_deleted']) && $data['is_deleted'] ?" tab_bg_2_2'" :"'").">";
                  echo $name."</td>";
                  echo "<td class='center'>".
                         (isset($data["serial"])?"".$data["serial"]."":"-")."</td>";
                  echo "<td class='center'>".
                         (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
                  echo "</tr>";
               }
            }
            $num += $nb;
         }
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>".($num>0? $LANG['common'][33]."&nbsp;=&nbsp;$num" : "&nbsp;")."</td>";
      echo "<td colspan='4'>&nbsp;</td></tr> ";
      echo "</table></div>";
   }


   /**
    * Print an HTML array with contracts associated to the enterprise
    *
    *@return Nothing (display)
   **/
   function showContracts() {
      global $DB, $CFG_GLPI, $LANG;

      $ID = $this->fields['id'];
      if (!haveRight("contract","r") || !$this->can($ID,'r')) {
         return false;
      }
      $canedit = $this->can($ID,'w');

      $query = "SELECT `glpi_contracts`.*,
                       `glpi_contracts_suppliers`.`id` AS assocID,
                       `glpi_entities`.`id` AS entity
                FROM `glpi_contracts_suppliers`, `glpi_contracts`
                LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`glpi_contracts`.`entities_id`)
                WHERE `glpi_contracts_suppliers`.`suppliers_id` = '$ID'
                      AND `glpi_contracts_suppliers`.`contracts_id`=`glpi_contracts`.`id`".
                      getEntitiesRestrictRequest(" AND", "glpi_contracts", '', '', true)."
                ORDER BY `glpi_entities`.`completename`,
                         `glpi_contracts`.`name`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      echo "<form method='post' action='".$CFG_GLPI["root_doc"]."/front/contract.form.php'>";
      echo "<div class='spaced'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>";
      if ($DB->numrows($result)==0) {
         echo $LANG['financial'][58];
      } else if ($DB->numrows($result)==1) {
         echo $LANG['financial'][63];
      } else {
         echo $LANG['financial'][66];
      }
      echo "</th></tr>";

      echo "<tr><th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['financial'][4]."</th>";
      echo "<th>".$LANG['financial'][6]."</th>";
      echo "<th>".$LANG['search'][8]."</th>";
      echo "<th>".$LANG['financial'][8]."</th>";
      echo "<th>&nbsp;</th>";
      echo "</tr>";

      $used = array();
      while ($data=$DB->fetch_array($result)) {
         $cID        = $data["id"];
         $used[$cID] = $cID;
         $assocID    = $data["assocID"];;

         echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
         echo "<td class='center'>
               <a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?id=$cID'>";
         echo "<strong>".$data["name"];

         if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
            echo " (".$data["id"].")";
         }

         echo "</strong></a></td>";
         echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities", $data["entity"]);
         echo "</td><td class='center'>".$data["num"]."</td>";
         echo "<td class='center'>".
                Dropdown::getDropdownName("glpi_contracttypes",$data["contracttypes_id"])."</td>";
         echo "<td class='center'>".convDate($data["begin_date"])."</td>";
         echo "<td class='center'>".$data["duration"]." ".$LANG['financial'][57];

         if ($data["begin_date"]!='' && !empty($data["begin_date"])) {
            echo " -> ".getWarrantyExpir($data["begin_date"], $data["duration"]);
         }
         echo "</td>";
         echo "<td class='tab_bg_2 center'>";

         if ($canedit) {
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?deletecontractsupplier=".
                   "1&amp;id=$assocID&amp;contracts_id=$cID'>";
            echo "<img src='".$CFG_GLPI["root_doc"]."/pics/delete2.png' alt='".
                   $LANG['buttons'][6]."'></a>";
         } else {
            echo "&nbsp;";
         }
         echo "</td></tr>";
         $i++;
      }

      if ($canedit) {
         if ($this->fields["is_recursive"]) {
            $nb = countElementsInTableForEntity("glpi_contracts",
                                                getSonsOf("glpi_entities",
                                                          $this->fields["entities_id"]));
         } else {
            $nb = countElementsInTableForEntity("glpi_contracts", $this->fields["entities_id"]);
         }

         if ($nb>count($used)) {
            echo "<tr class='tab_bg_1'><td class='center' colspan='5'>";
            echo "<input type='hidden' name='suppliers_id' value='$ID'>";
            Contract::dropdown(array('used'        => $used,
                                    'entity'       => $this->fields["entities_id"],
                                    'entity_sons'  => $this->fields["is_recursive"],
                                    'nochecklimit' => true));
            echo "</td><td class='center'>";
            echo "<input type='submit' name='addcontractsupplier' value=\"".$LANG['buttons'][8]."\"
                   class='submit'>";
            echo "</td>";
            echo "<td>&nbsp;</td></tr>";
         }
      }
      echo "</table></div></form>";
   }

}

?>

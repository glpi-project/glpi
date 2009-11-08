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
 * Enterprise class (suppliers)
 */
class Enterprise extends CommonDBTM {

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_suppliers";
      $this->type=ENTERPRISE_TYPE;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      $job=new Job;

      $cs = new ContractSupplier();
      $cs->cleanDBonItemDelete($this->type,$ID);

      $cs = new ContactSupplier();
      $cs->cleanDBonItemDelete($this->type,$ID);
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG,$CFG_GLPI;

      $ong=array();
      if ($ID>0) {
         if (haveRight("contact_enterprise","r")) {
            $ong[1] = $LANG['Menu'][22];
         }
         if (haveRight("contract","r")) {
            $ong[4] = $LANG['Menu'][26];
         }
         $ong[15] = $LANG['financial'][104];
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
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }

      return $ong;
   }

   /**
    * Print the enterprise form
    *
    *@param $target form target
    *@param $ID Integer : Id of the computer or the template to print
    *@param $withtemplate='' boolean : template or basic computer
    *
    *@return Nothing (display)
    **/
   function showForm ($target,$ID,$withtemplate='') {
      global $CFG_GLPI,$LANG;

      // Show Enterprise or blank form
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
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40,
                              $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['financial'][79]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_supplierstypes", "supplierstypes_id", $this->fields["supplierstypes_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][35]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("phonenumber",$this->table,"phonenumber",
                              $this->fields["phonenumber"],40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td rowspan='8' class='middle right'>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='8'><textarea cols='45' rows='13' name='comment' >".
            $this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][30]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("fax",$this->table,"fax",$this->fields["fax"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][45]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("website",$this->table,"website",$this->fields["website"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][14]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("email",$this->table,"email",$this->fields["email"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='middle'>".$LANG['financial'][44]."&nbsp;:</td>";
      echo "<td class='middle'><textarea cols='45' rows='3' name='address'>".
             $this->fields["address"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][100]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("postcode",$this->table,"postcode",$this->fields["postcode"],7,
                              $this->fields["entities_id"]);
      echo "&nbsp;&nbsp;".$LANG['financial'][101]."&nbsp;:&nbsp;";
      autocompletionTextField("town",$this->table,"town",$this->fields["town"],25,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][102]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("state",$this->table,"state",$this->fields["state"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][103]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("country",$this->table,"country",$this->fields["country"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;

   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = 'glpi_suppliers';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = ENTERPRISE_TYPE;

      $tab[2]['table']     = 'glpi_suppliers';
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = 'glpi_suppliers';
      $tab[3]['field']     = 'address';
      $tab[3]['linkfield'] = 'address';
      $tab[3]['name']      = $LANG['financial'][44];

      $tab[10]['table']     = 'glpi_suppliers';
      $tab[10]['field']     = 'fax';
      $tab[10]['linkfield'] = 'fax';
      $tab[10]['name']      = $LANG['financial'][30];

      $tab[11]['table']     = 'glpi_suppliers';
      $tab[11]['field']     = 'town';
      $tab[11]['linkfield'] = 'town';
      $tab[11]['name']      = $LANG['financial'][101];

      $tab[14]['table']     = 'glpi_suppliers';
      $tab[14]['field']     = 'postcode';
      $tab[14]['linkfield'] = 'postcode';
      $tab[14]['name']      = $LANG['financial'][100];

      $tab[12]['table']     = 'glpi_suppliers';
      $tab[12]['field']     = 'state';
      $tab[12]['linkfield'] = 'state';
      $tab[12]['name']      = $LANG['financial'][102];

      $tab[13]['table']     = 'glpi_suppliers';
      $tab[13]['field']     = 'country';
      $tab[13]['linkfield'] = 'country';
      $tab[13]['name']      = $LANG['financial'][103];

      $tab[4]['table']     = 'glpi_suppliers';
      $tab[4]['field']     = 'website';
      $tab[4]['linkfield'] = 'website';
      $tab[4]['name']      = $LANG['financial'][45];
      $tab[4]['datatype']  = 'weblink';

      $tab[5]['table']     = 'glpi_suppliers';
      $tab[5]['field']     = 'phonenumber';
      $tab[5]['linkfield'] = 'phonenumber';
      $tab[5]['name']      = $LANG['help'][35];

      $tab[6]['table']     = 'glpi_suppliers';
      $tab[6]['field']     = 'email';
      $tab[6]['linkfield'] = 'email';
      $tab[6]['name']      = $LANG['setup'][14];
      $tab[6]['datatype']  = 'email';

      $tab[9]['table']     = 'glpi_supplierstypes';
      $tab[9]['field']     = 'name';
      $tab[9]['linkfield'] = 'supplierstypes_id';
      $tab[9]['name']      = $LANG['financial'][79];

      $tab[8]['table']         = 'glpi_contacts';
      $tab[8]['field']         = 'completename';
      $tab[8]['linkfield']     = '';
      $tab[8]['name']          = $LANG['financial'][46];
      $tab[8]['forcegroupby']  = true;
      $tab[8]['datatype']      = 'itemlink';
      $tab[8]['itemlink_type'] = CONTACT_TYPE;

      $tab[16]['table']     = 'glpi_suppliers';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[90]['table']     = 'glpi_suppliers';
      $tab[90]['field']     = 'notepad';
      $tab[90]['linkfield'] = '';
      $tab[90]['name']      = $LANG['title'][37];

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab[86]['table']     = 'glpi_suppliers';
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = 'is_recursive';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      return $tab;
   }

   /**
    * Get links for an enterprise (website / edit)
    *
    * @param $withname boolean : also display name ?
    */
   function getLinks($withname=false) {
      global $CFG_GLPI,$LANG;

      $ret = '&nbsp;&nbsp;&nbsp;&nbsp;';

      if ($withname) {
         $ret .= $this->fields["name"];
         $ret .= "&nbsp;&nbsp;";
      }

      if (!empty($this->fields['website'])) {
         $ret.= "<a href='".formatOutputWebLink($this->fields['website'])."' target='_blank'>
                 <img src='".$CFG_GLPI["root_doc"]."/pics/web.png' class='middle'
                 alt='".$LANG['common'][4]."' title='".$LANG['common'][4]."'></a>";
         $ret .= "&nbsp;&nbsp;";
      }
      if ($this->can($this->fields['id'],'r')) {
         $ret.= "<a href='".$CFG_GLPI["root_doc"]."/front/enterprise.form.php?id=".$this->fields['id'].
            "'><img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' class='middle' alt='".
            $LANG['buttons'][14]."' title='".$LANG['buttons'][14]."'></a>";
      }
      return $ret;
   }

   /**
    * Show contacts asociated to an enterprise
    *
    */
   function showContacts() {
      global $DB,$CFG_GLPI, $LANG;

      $instID = $this->fields['id'];
      if (!$this->can($instID,'r')) {
         return false;
      }
      $canedit=$this->can($instID,'w');

      $query = "SELECT `glpi_contacts`.*, `glpi_contacts_suppliers`.`id` AS ID_ent,
                       `glpi_entities`.`id` AS entity
                FROM `glpi_contacts_suppliers`, `glpi_contacts`
                LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`glpi_contacts`.`entities_id`)
                WHERE `glpi_contacts_suppliers`.`contacts_id`=`glpi_contacts`.`id`
                      AND `glpi_contacts_suppliers`.`suppliers_id` = '$instID' " .
                          getEntitiesRestrictRequest(" AND","glpi_contacts",'','',true) ."
                ORDER BY `glpi_entities`.`completename`, `glpi_contacts`.`name`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      echo "<br><div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='9'>".$LANG['financial'][46]."&nbsp;:</th></tr>";
      echo "<tr><th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['help'][35]."</th>";
      echo "<th>".$LANG['help'][35]." 2</th>";
      echo "<th>".$LANG['common'][42]."</th>";
      echo "<th>".$LANG['financial'][30]."</th>";
      echo "<th>".$LANG['setup'][14]."</th>";
      echo "<th>".$LANG['common'][17]."</th>";
      echo "<th>&nbsp;</th></tr>";

      $used=array();
      if ($number) {
         initNavigateListItems(CONTACT_TYPE,$LANG['financial'][26]." = ".$this->fields['name']);

         while ($data=$DB->fetch_array($result)) {
            $ID=$data["ID_ent"];
            $used[$data["id"]]=$data["id"];
            addToNavigateListItems(CONTACT_TYPE,$data["id"]);
            echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
            echo "<td class='center'>";
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/contact.form.php?id=".$data["id"]."'>".
                   $data["name"]." ".$data["firstname"]."</a></td>";
            echo "<td class='center' width='100'>".getDropdownName("glpi_entities",$data["entity"]);
            echo "</td>";
            echo "<td class='center' width='100'>".$data["phone"]."</td>";
            echo "<td class='center' width='100'>".$data["phone2"]."</td>";
            echo "<td class='center' width='100'>".$data["mobile"]."</td>";
            echo "<td class='center' width='100'>".$data["fax"]."</td>";
            echo "<td class='center'>";
            echo "<a href='mailto:".$data["email"]."'>".
                   $DB->result($result, $i, "glpi_contacts.email")."</a></td>";
            echo "<td class='center'>".getDropdownName("glpi_contactstypes",$data["contactstypes_id"]);
            echo "</td>";
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

      echo "</table><br>"    ;
      if ($canedit) {
         if ($this->fields["is_recursive"]) {
            $nb=countElementsInTableForEntity("glpi_contacts",
                  getSonsOf("glpi_entities",$this->fields["entities_id"]));
         } else {
            $nb=countElementsInTableForEntity("glpi_contacts",$this->fields["entities_id"]);
         }
         if ($nb>count($used)) {
            echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/contact.form.php\">";
            echo "<table  class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG['financial'][33]."</tr>";
            echo "<tr><td class='tab_bg_2 center'>";
            echo "<input type='hidden' name='suppliers_id' value='$instID'>";
            if ($this->fields["is_recursive"]) {
               dropdown("glpi_contacts","contacts_id",1,
                        getSonsOf("glpi_entities",$this->fields["entities_id"]),$used);
            } else {
               dropdown("glpi_contacts","contacts_id",1,$this->fields["entities_id"],$used);
            }
            echo "</td><td class='tab_bg_2 center'>";
            echo "<input type='submit' name='addcontactsupplier' value=\"".
                   $LANG['buttons'][8]."\" class='submit'>";
            echo "</td></tr>";
         }
         echo "</table></form>";
      }
      echo "</div>";
   }

   /**
    * Print the HTML array for infocoms linked
    *
    *@return Nothing (display)
    *
    **/
   function showInfocoms() {
      global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES,$LINK_ID_TABLE,$SEARCH_PAGES;

      $instID = $this->fields['id'];
      if (!$this->can($instID,'r')) {
         return false;
      }

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_infocoms`
                WHERE `suppliers_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>";
      printPagerForm();
      echo "</th><th colspan='3'>".$LANG['document'][19]."&nbsp;:</th></tr>";
      echo "<tr><th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['common'][19]."</th>";
      echo "<th>".$LANG['common'][20]."</th>";
      echo "</tr>";
      $ci=new CommonItem;
      $num=0;
      while ($i < $number) {
         $itemtype=$DB->result($result, $i, "itemtype");
         if (haveTypeRight($itemtype,"r") && $itemtype!=CONSUMABLEITEM_TYPE
             && $itemtype!=CARTRIDGEITEM_TYPE && $itemtype!=SOFTWARE_TYPE) {
            $linktype = $itemtype;
            $linkfield = 'id';
            $query = "SELECT `entities_id`,`name`,`".$LINK_ID_TABLE[$itemtype]."`.*
                      FROM `glpi_infocoms`
                      INNER JOIN `".$LINK_ID_TABLE[$itemtype]."`
                            ON (`".$LINK_ID_TABLE[$itemtype]."`.`id` = `glpi_infocoms`.`items_id`) ";

            // Set $linktype for entity restriction AND link to search engine
            if ($itemtype==CARTRIDGE_TYPE) {
               $query .= "INNER JOIN `glpi_cartridgesitems`
                               ON (`glpi_cartridgesitems`.`id`=`glpi_cartridges`.`cartridgesitems_id`) ";
               $linktype = CARTRIDGEITEM_TYPE;
               $linkfield = 'cartridgesitems_id';
            }
            if ($itemtype==CONSUMABLE_TYPE ) {
               $query .= "INNER JOIN `glpi_consumablesitems`
                               ON (`glpi_consumablesitems`.`id`=`glpi_consumables`.`consumablesitems_id`) ";
               $linktype = CONSUMABLEITEM_TYPE;
               $linkfield = 'consumablesitems_id';
            }
            $query .= "WHERE `glpi_infocoms`.`itemtype`='$itemtype'
                             AND `glpi_infocoms`.`suppliers_id` = '$instID' ".
                             getEntitiesRestrictRequest(" AND",$LINK_ID_TABLE[$linktype]) ."
                       ORDER BY `entities_id`, `".$LINK_ID_TABLE[$linktype]."`.`name`";

            // Set $linktype for link to search engine pnly
            if ($itemtype==SOFTWARELICENSE_TYPE ) {
               $linktype = SOFTWARE_TYPE;
               $linkfield = 'softwares_id';
            }

            $result_linked=$DB->query($query);
            $nb=$DB->numrows($result_linked);
            $ci->setType($itemtype);
            if ($nb>$_SESSION['glpilist_limit'] && isset($SEARCH_PAGES[$linktype])) {
               echo "<tr class='tab_bg_1'>";
               echo "<td class='center'>".$ci->getType()."&nbsp;:&nbsp;$nb</td>";
               echo "<td class='center' colspan='2'>";
               echo "<a href='". $CFG_GLPI["root_doc"]."/".$SEARCH_PAGES[$linktype] . "?" .
                      rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$instID) . "&" .
                      rawurlencode("field[0]") . "=53&sort=80&order=ASC&is_deleted=0&start=0". "'>" .
                      $LANG['reports'][57]."</a></td>";

               echo "<td class='center'>-</td><td class='center'>-</td></tr>";
            } else if ($nb) {
               for ($prem=true;$data=$DB->fetch_assoc($result_linked);$prem=false) {
                  $ID="";
                  if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                     $ID= " (".$data["id"].")";
                  }
                  $name= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$linktype]."?id=".
                           $data[$linkfield]."\">".$data["name"]."$ID</a>";

                  echo "<tr class='tab_bg_1'>";
                  if ($prem) {
                     echo "<td class='center top' rowspan='$nb'>".$ci->getType()
                            .($nb>1?"&nbsp;:&nbsp;$nb</td>":"</td>");
                  }
                  echo "<td class='center'>".getDropdownName("glpi_entities",$data["entities_id"])."</td>";
                  echo "<td class='center";
                  echo (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
                  echo ">".$name."</td>";
                  echo "<td class='center'>".
                         (isset($data["serial"])? "".$data["serial"]."" :"-")."</td>";
                  echo "<td class='center'>".
                         (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
                  echo "</tr>";
               }
            }
            $num+=$nb;
         }
         $i++;
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>".($num>0? $LANG['common'][33]."&nbsp;=&nbsp;$num</td>" : "&nbsp;</td>");
      echo "<td colspan='4'>&nbsp;</td></tr> ";
      echo "</table></div>"    ;
   }

}

?>

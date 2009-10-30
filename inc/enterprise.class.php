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
}

?>

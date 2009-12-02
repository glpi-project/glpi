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

// CLASSES link
class Link extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_links';
   public $type = LINK_TYPE;
   public $may_be_recursive=true;
   public $entity_assign=true;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][87];
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      $ong[1]=$LANG['title'][26];
      return $ong;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      $query2="DELETE
               FROM `glpi_links_itemtypes`
               WHERE `links_id`='$ID'";
      $DB->query($query2);
   }

   /**
    * Print the link form
    *
    * Print g��al link form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the link to print
    *
    *@return Nothing (display)
    *
    **/
   function showForm ($target,$ID) {
      global $CFG_GLPI, $LANG;

      if (!haveRight("link","r")) {
         return false;
      }
      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, '',getActiveTab($this->type));
      $this->showFormHeader($target,$ID,'',2);

      echo "<tr class='tab_bg_1'><td height='23'>".$LANG['links'][6]."&nbsp;:</td>";
      echo "<td colspan='3'>[LOGIN], [ID], [NAME], [LOCATION], [LOCATIONID], [IP], [MAC], [NETWORK],
                            [DOMAIN], [SERIAL], [OTHERSERIAL], [USER], [GROUP]</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td colspan='3'>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],84);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['links'][1]."&nbsp;:</td>";
      echo "<td colspan='2'>";
      autocompletionTextField("link",$this->table,"link",$this->fields["link"],84);
      echo "</td><td width='1'></td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['links'][9]."&nbsp;:</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='data' rows='10' cols='96'>".$this->fields["data"]."</textarea>";
      echo "</td></tr>";

      $this->showFormButtons($ID,'',2);

      return true;
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = 'glpi_links';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = LINK_TYPE;

      $tab[2]['table']     = 'glpi_links';
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = 'glpi_links';
      $tab[3]['field']     = 'link';
      $tab[3]['linkfield'] = 'link';
      $tab[3]['name']      = $LANG['links'][1];

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      return $tab;
   }
}

?>

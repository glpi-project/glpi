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
 * Entity Data class
 */
class EntityData extends CommonDBTM {

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_entitiesdatas";
      $this->type=-1;
   }

   function getIndexName() {
      return "entities_id";
   }

}


/**
 * Entity class
 */
class Entity extends CommonDBTM {

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_entities";
      $this->type=ENTITY_TYPE;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong[1]=$LANG['title'][26];
      $ong[2]=$LANG['Menu'][14];
      $ong[3]=$LANG['rulesengine'][17];
      if (haveRight("document","r")) {
         $ong[5]=$LANG['Menu'][27];
      }

      return $ong;
   }

   /**
    * Print a good title for entity pages
    *
    *@return nothing (display)
    **/
   function title() {
      global  $LANG,$CFG_GLPI;

      $buttons=array();
      $title=$LANG['Menu'][37];
      if (haveRight("entity","w")) {
         $buttons["entity.tree.php"]=$LANG['entity'][1];
         $title="";
      }
      $buttons["entity.form.php?id=0"]=$LANG['entity'][2];

      displayTitle($CFG_GLPI["root_doc"]."/pics/groupes.png",$LANG['Menu'][37],$title,$buttons);
   }

   /**
    * Print the entity form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the contact to print
    *@param $withtemplate='' boolean : template or basic item
    *
    *@return Nothing (display)
    *
    **/
   function showForm ($target,$ID,$withtemplate='') {
      global $CFG_GLPI, $LANG;

      if (!haveRight("entity","r")) {
         return false;
      }

      $con_spotted=false;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();

         // Special root entity case
         if ($ID==0) {
            $this->fields["name"]=$LANG['entity'][2];
            $this->fields["completename"]="";
         }
      }

      // Get data
      $entdata=new EntityData();
      if (!$entdata->getFromDB($ID)) {
         $entdata->add(array("entities_id"=>$ID));
         if (!$entdata->getFromDB($ID)) {
            $con_spotted=false;
         }
      }

      $canedit=$this->can($ID,'w');

      $this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);

      if ($canedit) {
         echo "<form method='post' name=form action=\"$target\">";
      }
      echo "<div class='center' id='tabsbody' >";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>";
      echo $LANG['entity'][0]." ID $ID";
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='middle'>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td class='middle'>";
      echo $this->fields["name"];
      if ($ID!=0) {
         echo " (".$this->fields["completename"].")";
      }
      echo "</td>";
      echo "<td>".$LANG['entity'][13]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("tag","glpi_entitiesdatas","tag",$entdata->fields["tag"],40);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][35]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("phonenumber","glpi_entitiesdatas","phonenumber",
                              $entdata->fields["phonenumber"],40);
      echo "</td>";
      echo "<td>".$LANG['entity'][12]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("ldap_dn","glpi_entitiesdatas","ldap_dn",$entdata->fields["ldap_dn"],40);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][30]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("fax","glpi_entitiesdatas","fax",$entdata->fields["fax"],40);
      echo "</td>";
      echo "<td>".$LANG['setup'][203]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("admin_email","glpi_entitiesdatas","admin_email",
                              $entdata->fields["admin_email"],40);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][45]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("website","glpi_entitiesdatas","website",$entdata->fields["website"],40);
      echo "</td>";
      echo "<td>".$LANG['setup'][207]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("admin_reply","glpi_entitiesdatas","admin_reply",
                              $entdata->fields["admin_reply"],40);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][14]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("email","glpi_entitiesdatas","email",$entdata->fields["email"],40);
      echo "</td>";
      echo "<td rowspan='6'>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='6'>";
      if ($ID > 0) {
         echo "<textarea cols='45' rows='9' name='comment' >".$this->fields["comment"]."</textarea>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='middle'>".$LANG['financial'][44]."&nbsp;:</td>";
      echo "<td class='middle'><textarea cols='45' rows='3' name='address'>".
             $entdata->fields["address"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][100]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("postcode","glpi_entitiesdatas","postcode",
                              $entdata->fields["postcode"],7);
      echo "&nbsp;".$LANG['financial'][101]."&nbsp;:&nbsp;";
      autocompletionTextField("town","glpi_entitiesdatas","town",$entdata->fields["town"],25);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][102]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("state","glpi_entitiesdatas","state",$entdata->fields["state"],40);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][103]."&nbsp;:</td><td>";
      autocompletionTextField("country","glpi_entitiesdatas","country",$entdata->fields["country"],40);
      echo "</td></tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='entities_id' value=\"$ID\">";
         echo "<input type='hidden' name='id' value=\"".$entdata->fields["id"]."\">";
         echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit' >";
         echo "</td></tr>";
      }
      echo "</table></div></form>";

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   /**
    * Get the ID of entity assigned to the object
    *
    * simply return ID
    *
    * @return ID of the entity
   **/
   function getEntityID () {
      if (isset($this->fields["id"])) {
         return $this->fields["id"];
      }
      return -1;
   }

   /**
    * Is the object recursive
    *
    * Entity are always recursive
    *
    * @return integer (0/1)
   **/
   function isRecursive () {
      return true;
   }

   /**
   * Check right on an entity
   *
   * @param $ID ID of the entity (-1 if new item)
   * @param $right Right to check : r / w / recursive
   * @param $input array of input data (used for adding item)
   *
   * @return boolean
   **/
   function can($ID,$right,&$input=NULL) {
      global $LANG;

      // Get item ID
      if ($ID<0) {
         // No entity define : adding process : use active entity
         if (isset($input['entities_id'])) {
            // this is the parent entity
            $entity_to_check = $input['entities_id'];
         } else {
            $entity_to_check = $_SESSION["glpiactive_entity"];
         }
         // To add, need a recursive right on parent
         // to get a right on new entity
         $right = 'recursive';
      } else {
         if (!isset($this->fields['id']) || $this->fields['id']!=$ID) {
            // Item not found : no right
            if (!$ID) {
               // Hack for 'root' entity which is not stored
               $this->fields=array('id'=>$ID,
                                   'name'=>$LANG['entity'][2]);
            } else if (!$this->getFromDB($ID)) {
               return false;
            }
         }
         $entity_to_check=$ID;
      }

      switch ($right) {
         case 'r':
            if ($this->canView()) {
               return haveAccessToEntity($entity_to_check, true);
            }
            break;

         case 'w':
            if ($this->canCreate()) {
               return haveAccessToEntity($entity_to_check);
            }
            break;

         case 'recursive':
            // Always check
            if ($this->canCreate() && haveAccessToEntity($entity_to_check)) {
               // Can make recursive if recursive access to entity
               return haveRecursiveAccessToEntity($entity_to_check);
            }
            break;
      }
      return false;
   }

}

?>

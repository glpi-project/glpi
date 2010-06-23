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
 * Entity class
 */
class Entity extends CommonTreeDropdown {
 
   var $must_be_replace = true;

   function getFromDB($ID) {
      global $LANG;

      if ($ID==0) {
         $this->fields=array('id'=>0,
                        'name'=>$LANG['entity'][2],
                        'entities_id'=>0,
                        'completename'=>$LANG['entity'][2],
                        'comment'=>'',
                        'level'=>0,
                        'sons_cache'=>'',
                        'ancestors_cache'=>'',
                        );
         return true;
      } else {
         return parent::getFromDB($ID);
      }
   }
   static function getTypeName() {
      global $LANG;

      return $LANG['Menu'][37];
   }

   function canCreate() {
      return haveRight('entity', 'w');
   }

   function canView() {
      return haveRight('entity', 'r');
   }

   function canCreateItem() {
      // Check the parent
      return haveRecursiveAccessToEntity($this->getField('entities_id'));
   }

   function canUpdateItem() {
      // Check the current entity
      return haveAccessToEntity($this->getField('id'));
   }

   function isNewID($ID) {
      return ($ID<0 || !strlen($ID));
   }

   function defineTabs($options=array()) {
      global $LANG;

      $ong[1] = $LANG['title'][26];          // Main
      if (!$this->isNewID($this->fields['id'])) {
         $ong[2] = $LANG['financial'][44];   // Address
         $ong[3] = $LANG['Menu'][14];        // Users
         $ong[4] = $LANG['rulesengine'][17]; // Rules
         $ong[5] = $LANG['entity'][14];      // Advanced
         if (haveRight("document","r")) {
            $ong[6]=$LANG['Menu'][27];       // Docs
         }
         if (haveRight('notification','r')) {
            $ong[7] = $LANG['setup'][704];
         }
         $ong[8] = $LANG['title'][24];      // Automatic close
      }
      return $ong;
   }

   /**
    * Display content of Tab
    *
    * @param $ID of the item
    * @param $tab number of the tab
    *
    * @return true if handled (for class stack)
    */
   function showTabContent ($ID, $tab) {
      global $CFG_GLPI;

      if (!$this->isNewID($ID)) {
         switch ($tab) {
            case -1 :   // All
               $this->showChildren($ID);
               EntityData::showStandardOptions($this);
               Profile_User::showForEntity($this);
               $ocsrule = new RuleOcs;
               $ldaprule = new RuleRight;
               $mailcollector = new RuleMailCollector;
               $ldaprule->showAndAddRuleForm($_POST["id"]);
               if ($CFG_GLPI["use_ocs_mode"]) {
                  $ocsrule->showAndAddRuleForm($_POST["id"]);
               }
               $mailcollector->showAndAddRuleForm($_POST["id"]);
               Document::showAssociated($this);
               EntityData::showNotificationOptions($this);
               EntityData::showHelpdeskOptions($this);
               Plugin::displayAction($this, $tab);
               break;

            case 2 :
               EntityData::showStandardOptions($this);
               break;

            case 3 :
               Profile_User::showForEntity($this);
               break;

            case 4 :
               $ocsrule = new RuleOcs;
               $ldaprule = new RuleRight;
               $mailcollector = new RuleMailCollector;
               $ldaprule->showAndAddRuleForm($_POST["id"]);
               if ($CFG_GLPI["use_ocs_mode"]) {
                  $ocsrule->showAndAddRuleForm($_POST["id"]);
               }
               $mailcollector->showAndAddRuleForm($_POST["id"]);
               break;

            case 5 :
               EntityData::showAdvancedOptions($this);
               break;

            case 6 :
               Document::showAssociated($this);
               break;

            case 7 :
               EntityData::showNotificationOptions($this);
               break;

            case 8 :
               EntityData::showHelpdeskOptions($this);
               break;

            default :
               if (!Plugin::displayAction($this, $tab)) {
                  $this->showChildren($ID);
               }
               return false;
         }
      }
      return false;
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
      $buttons["entity.form.php?id=0"]=$LANG['entity'][2];
      displayTitle($CFG_GLPI["root_doc"]."/pics/groupes.png",$LANG['Menu'][37],$title,$buttons);
   }

   function displayHeader () {
      global $LANG;

      commonHeader($this->getTypeName(),'',"admin","entity");
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

   function isEntityAssign() {
      return true;
   }

   function maybeRecursive() {
      return true;
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

   function post_addItem() {
      CleanFields($this->getTable(), 'sons_cache');

      // Add right to current user - Hack to avoid login/logout
      $_SESSION['glpiactiveentities'][$this->fields['id']] = $this->fields['id'];
      $_SESSION['glpiactiveentities_string'] .= ",'".$this->fields['id']."'";
   }

   function cleanDBonPurge() {
      global $DB,$LANG;

      $query = "DELETE
                FROM `glpi_entitydatas`
                WHERE `entities_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);

      $query="SELECT `rules_id`
              FROM `glpi_ruleactions`
              WHERE `value` = '".$this->fields['id']."'
                  AND `field` = 'entities_id'";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $rule = new Rule();
            $input['is_active']=0;
            while ($data = $DB->fetch_array($result)) {
               $input['id']=$data['rules_id'];
               $rule->update($input);
            }
            addMessageAfterRedirect($LANG['rulesengine'][150]);
         }
      }
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'completename';
      $tab[1]['linkfield']     = 'completename';
      $tab[1]['name']          = $LANG['common'][51];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];



      $tab[3]['table']     = 'glpi_entitydatas';
      $tab[3]['field']     = 'address';
      $tab[3]['linkfield'] = '';
      $tab[3]['name']      = $LANG['financial'][44];


      $tab[4]['table']     = 'glpi_entitydatas';
      $tab[4]['field']     = 'website';
      $tab[4]['linkfield'] = '';
      $tab[4]['name']      = $LANG['financial'][45];

      $tab[5]['table']     = 'glpi_entitydatas';
      $tab[5]['field']     = 'phonenumber';
      $tab[5]['linkfield'] = '';
      $tab[5]['name']      = $LANG['help'][35];

      $tab[6]['table']     = 'glpi_entitydatas';
      $tab[6]['field']     = 'email';
      $tab[6]['linkfield'] = '';
      $tab[6]['name']      = $LANG['setup'][14];
      $tab[6]['datatype']  = 'email';

      $tab[7]['table']     = 'glpi_entitydatas';
      $tab[7]['field']     = 'ldap_dn';
      $tab[7]['linkfield'] = '';
      $tab[7]['name']      = $LANG['entity'][12];

      $tab[8]['table']     = 'glpi_entitydatas';
      $tab[8]['field']     = 'tag';
      $tab[8]['linkfield'] = '';
      $tab[8]['name']      = $LANG['entity'][13];

      $tab[9]['table']     = 'glpi_entitydatas';
      $tab[9]['field']     = 'ldapservers_id';
      $tab[9]['linkfield'] = '';
      $tab[9]['name']      = $LANG['entity'][15];


      $tab[10]['table']     = 'glpi_entitydatas';
      $tab[10]['field']     = 'fax';
      $tab[10]['linkfield'] = '';
      $tab[10]['name']      = $LANG['financial'][30];

      $tab[11]['table']     = 'glpi_entitydatas';
      $tab[11]['field']     = 'town';
      $tab[11]['linkfield'] = '';
      $tab[11]['name']      = $LANG['financial'][101];

      $tab[12]['table']     = 'glpi_entitydatas';
      $tab[12]['field']     = 'state';

      $tab[12]['linkfield'] = '';
      $tab[12]['name']      = $LANG['financial'][102];

      $tab[13]['table']     = 'glpi_entitydatas';
      $tab[13]['field']     = 'country';
      $tab[13]['linkfield'] = '';
      $tab[13]['name']      = $LANG['financial'][103];

      $tab[14]['table']         = $this->getTable();
      $tab[14]['field']         = 'name';
      $tab[14]['linkfield']     = 'name';
      $tab[14]['name']          = $LANG['common'][16];
      $tab[14]['datatype']      = 'itemlink';
      $tab[14]['itemlink_type'] = 'Entity';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[17]['table']     = 'glpi_entitydatas';
      $tab[17]['field']     = 'entity_ldapfilter';
      $tab[17]['linkfield'] = '';
      $tab[17]['name']      = $LANG['entity'][16];

      $tab[18]['table']     = 'glpi_entitydatas';
      $tab[18]['field']     = 'admin_email';
      $tab[18]['linkfield'] = '';
      $tab[18]['name']      = $LANG['setup'][203];

      $tab[19]['table']     = 'glpi_entitydatas';
      $tab[19]['field']     = 'admin_reply';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = $LANG['setup'][207];

      $tab[20]['table']     = 'glpi_entitydatas';
      $tab[20]['field']     = 'mail_domain';
      $tab[20]['linkfield'] = '';
      $tab[20]['name']      = $LANG['setup'][732];

      return $tab;
   }

   /**
    * Display entities of the loaded profile
    *
   * @param $myname select name
    * @param $target target for entity change action
    */
   static function showSelector($target,$myname) {
      global $CFG_GLPI,$LANG;

      $rand=mt_rand();

      echo "<div class='center' ><span class='b'>".$LANG['entity'][10]." ( <img src=\"".
             $CFG_GLPI["root_doc"]."/pics/entity_all.png\" alt=''> ".$LANG['entity'][11].")</span><br>";
      echo "<a style='font-size:14px;' href='".$target."?active_entity=all' title=\"".
             $LANG['buttons'][40]."\">".str_replace(" ","&nbsp;",$LANG['buttons'][40])."</a></div>";

      echo "<div class='left' style='width:100%'>";

      echo "<script type='javascript'>";
      echo "var Tree_Category_Loader$rand = new Ext.tree.TreeLoader({
         dataUrl:'".$CFG_GLPI["root_doc"]."/ajax/entitytreesons.php'
      });";

      echo "var Tree_Category$rand = new Ext.tree.TreePanel({
         collapsible      : false,
         animCollapse     : false,
         border           : false,
         id               : 'tree_projectcategory$rand',
         el               : 'tree_projectcategory$rand',
         autoScroll       : true,
         animate          : false,
         enableDD         : true,
         containerScroll  : true,
         height           : 320,
         width            : 770,
         loader           : Tree_Category_Loader$rand,
         rootVisible     : false
      });";

      // SET the root node.
      echo "var Tree_Category_Root$rand = new Ext.tree.AsyncTreeNode({
         text     : '',
         draggable   : false,
         id    : '-1'                  // this IS the id of the startnode
      });
      Tree_Category$rand.setRootNode(Tree_Category_Root$rand);";

      // Render the tree.
      echo "Tree_Category$rand.render();
            Tree_Category_Root$rand.expand();";

      echo "</script>";

      echo "<div id='tree_projectcategory$rand' ></div>";
      echo "</div>";
   }

   function addRule($input) {
      global $LANG;
      $this->check($_POST["affectentity"],'w');

      $collection = RuleCollection::getClassByType($_POST['sub_type']);
      $rule = $collection->getRuleClass($_POST['sub_type']);
      $ruleid = $rule->add($_POST);

      if ($ruleid) {
         //Add an action associated to the rule
         $ruleAction = new RuleAction;

         //Action is : affect computer to this entity
         $ruleAction->addActionByAttributes("assign", $ruleid, "entities_id", $_POST["affectentity"]);

         switch ($_POST['sub_type']) {
            default:
               break;
            case 'RuleRight':
               if ($_POST["profiles_id"]) {
                  $ruleAction->addActionByAttributes("assign", $ruleid,
                                                     "profiles_id", $_POST["profiles_id"]);
               }
               $ruleAction->addActionByAttributes("assign", $ruleid,
                                                  "is_recursive", $_POST["is_recursive"]);
         }
      }

      Event::log($ruleid, "rules", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][22]);
      glpi_header($_SERVER['HTTP_REFERER']);
   }

   static function getEntitiesToNotify($field,$with_value=false) {
      global $DB,$CFG_GLPI;

      $query = "SELECT `glpi_entities`.`id` as `entity`,
               `glpi_entitydatas`.`$field`
               FROM `glpi_entities`
               LEFT JOIN `glpi_entitydatas` ON (
               `glpi_entitydatas`.`entities_id` = `glpi_entities`.`id`)";
      $query.= " ORDER BY `glpi_entities`.`entities_id` ASC";

      $entities = array();
      foreach ($DB->request($query) as $entitydatas) {
         Entity::getDefaultValueForNotification($field,$entities, $entitydatas);
      }

      //If root entity doesn't have row in glpi_entitydatas
      $query = "SELECT `$field` FROM `glpi_entitydatas` WHERE `entities_id`='0'";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         Entity::getDefaultValueForNotification($field,
                                                $entities,
                                                array('entity'=>0,
                                                      $field=>$DB->result($result,0,$field)));
      }
      elseif ($CFG_GLPI[$field]) {
         $entities[0] = $CFG_GLPI[$field];
      }

      return $entities;
   }

   static function getDefaultValueForNotification($field, &$entities, $entitydatas) {
      global $CFG_GLPI;

      //If there's a configuration for this entity & the value is not the one of the global config
      if (isset($entitydatas[$field]) && $entitydatas[$field] > 0) {
         $entities[$entitydatas['entity']] = $entitydatas[$field];
      }
      //No configuration for this entity : if global config allows notification then add the entity
      //to the array of entities to be notified
      else if ((!isset($entitydatas[$field])
                || (isset($entitydatas[$field]) && $entitydatas[$field] == -1))
               && $CFG_GLPI[$field]) {
         $entities[$entitydatas['entity']] = $CFG_GLPI[$field];
      }
   }
}
?>

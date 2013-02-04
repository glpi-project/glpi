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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Entity class
 */
class Entity extends CommonTreeDropdown {

   public $must_be_replace = true;
   public $dohistory       = true;

   function getFromDB($ID) {
      global $LANG;

      if ($ID==0) {
         $this->fields = array('id'              => 0,
                               'name'            => $LANG['entity'][2],
                               'entities_id'     => 0,
                               'completename'    => $LANG['entity'][2],
                               'comment'         => '',
                               'level'           => 0,
                               'sons_cache'      => '',
                               'ancestors_cache' => '');
         return true;
      }
      return parent::getFromDB($ID);
   }


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['Menu'][37];
      }
      return $LANG['entity'][0];
   }


   function canCreate() {
      return Session::haveRight('entity', 'w');
   }


   function canView() {
      return Session::haveRight('entity', 'r');
   }


   function canCreateItem() {
      // Check the parent
      return Session::haveRecursiveAccessToEntity($this->getField('entities_id'));
   }


   function canUpdateItem() {
      // Check the current entity
      return Session::haveAccessToEntity($this->getField('id'));
   }


   function isNewID($ID) {
      return ($ID<0 || !strlen($ID));
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $this->addStandardTab($this->getType(), $ong, $options);
      $this->addStandardTab('Profile_User',$ong, $options);
      $this->addStandardTab('EntityData', $ong, $options);
      $this->addStandardTab('Rule', $ong, $options);
      $this->addStandardTab('Document',$ong, $options);
      $this->addStandardTab('Note',$ong, $options);
      $this->addStandardTab('Log',$ong, $options);

      return $ong;
   }


   /**
    * Print a good title for entity pages
    *
    *@return nothing (display)
    **/
   function title() {
      global $LANG, $CFG_GLPI;

      $buttons = array();
      $title   = $LANG['Menu'][37];
      $buttons["entity.form.php?id=0"] = $LANG['entity'][2];
      Html::displayTitle($CFG_GLPI["root_doc"]."/pics/groupes.png", $LANG['Menu'][37], $title,
                         $buttons);
   }


   function displayHeader() {
      Html::header($this->getTypeName(), '', "admin", "entity");
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

      parent::post_addItem();

      // Add right to current user - Hack to avoid login/logout
      $_SESSION['glpiactiveentities'][$this->fields['id']] = $this->fields['id'];
      $_SESSION['glpiactiveentities_string'] .= ",'".$this->fields['id']."'";
   }


   function cleanDBonPurge() {
      global $DB, $LANG;

      $query = "DELETE
                FROM `glpi_entitydatas`
                WHERE `entities_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);

      // most use entities_id, RuleDictionnarySoftwareCollection use new_entities_id
      Rule::cleanForItemAction($this, '%entities_id');
      Rule::cleanForItemCriteria($this);

      $gki = new Entity_KnowbaseItem();
      $gki->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gr = new Entity_Reminder();
      $gr->cleanDBonItemDelete($this->getType(), $this->fields['id']);

   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'completename';
      $tab[1]['name']          = $LANG['common'][51];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']         = 'glpi_entitydatas';
      $tab[3]['field']         = 'address';
      $tab[3]['name']          = $LANG['financial'][44];
      $tab[3]['massiveaction'] = false;
      $tab[3]['joinparams']    = array('jointype' => 'child');
      $tab[3]['datatype']      = 'text';

      $tab[4]['table']         = 'glpi_entitydatas';
      $tab[4]['field']         = 'website';
      $tab[4]['name']          = $LANG['financial'][45];
      $tab[4]['massiveaction'] = false;
      $tab[4]['joinparams']    = array('jointype' => 'child');
      $tab[4]['datatype']      = 'string';

      $tab[5]['table']         = 'glpi_entitydatas';
      $tab[5]['field']         = 'phonenumber';
      $tab[5]['name']          = $LANG['help'][35];
      $tab[5]['massiveaction'] = false;
      $tab[5]['joinparams']    = array('jointype' => 'child');
      $tab[5]['datatype']      = 'string';

      $tab[6]['table']         = 'glpi_entitydatas';
      $tab[6]['field']         = 'email';
      $tab[6]['name']          = $LANG['setup'][14];
      $tab[6]['datatype']      = 'email';
      $tab[6]['massiveaction'] = false;
      $tab[6]['joinparams']    = array('jointype' => 'child');

      $tab[7]['table']         = 'glpi_entitydatas';
      $tab[7]['field']         = 'ldap_dn';
      $tab[7]['name']          = $LANG['entity'][12];
      $tab[7]['massiveaction'] = false;
      $tab[7]['joinparams']    = array('jointype' => 'child');
      $tab[7]['datatype']      = 'string';

      $tab[8]['table']         = 'glpi_entitydatas';
      $tab[8]['field']         = 'tag';
      $tab[8]['name']          = $LANG['entity'][13];
      $tab[8]['massiveaction'] = false;
      $tab[8]['joinparams']    = array('jointype' => 'child');
      $tab[8]['datatype']      = 'string';

      $tab[9]['table']         = 'glpi_authldaps';
      $tab[9]['field']         = 'name';
      $tab[9]['name']          = $LANG['entity'][15];
      $tab[9]['massiveaction'] = false;
      $tab[9]['joinparams']    = array('beforejoin'
                                       => array('table'      => 'glpi_entitydatas',
                                                'joinparams' => array('jointype' => 'child')));


      $tab[10]['table']         = 'glpi_entitydatas';
      $tab[10]['field']         = 'fax';
      $tab[10]['name']          = $LANG['financial'][30];
      $tab[10]['massiveaction'] = false;
      $tab[10]['joinparams']    = array('jointype' => 'child');
      $tab[10]['datatype']      = 'string';

      $tab[11]['table']         = 'glpi_entitydatas';
      $tab[11]['field']         = 'town';
      $tab[11]['name']          = $LANG['financial'][101];
      $tab[11]['massiveaction'] = false;
      $tab[11]['joinparams']    = array('jointype' => 'child');
      $tab[11]['datatype']      = 'string';

      $tab[12]['table']         = 'glpi_entitydatas';
      $tab[12]['field']         = 'state';
      $tab[12]['name']          = $LANG['financial'][102];
      $tab[12]['massiveaction'] = false;
      $tab[12]['joinparams']    = array('jointype' => 'child');
      $tab[12]['datatype']      = 'string';

      $tab[13]['table']         = 'glpi_entitydatas';
      $tab[13]['field']         = 'country';
      $tab[13]['name']          = $LANG['financial'][103];
      $tab[13]['massiveaction'] = false;
      $tab[13]['joinparams']    = array('jointype' => 'child');
      $tab[13]['datatype']      = 'string';

      $tab[14]['table']         = $this->getTable();
      $tab[14]['field']         = 'name';
      $tab[14]['name']          = $LANG['common'][16];
      $tab[14]['datatype']      = 'itemlink';
      $tab[14]['itemlink_type'] = 'Entity';
      $tab[14]['massiveaction'] = false;

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[17]['table']         = 'glpi_entitydatas';
      $tab[17]['field']         = 'entity_ldapfilter';
      $tab[17]['name']          = $LANG['entity'][16];
      $tab[17]['massiveaction'] = false;
      $tab[17]['joinparams']    = array('jointype' => 'child');
      $tab[17]['datatype']      = 'string';

      $tab[18]['table']         = 'glpi_entitydatas';
      $tab[18]['field']         = 'admin_email';
      $tab[18]['name']          = $LANG['setup'][203];
      $tab[18]['massiveaction'] = false;
      $tab[18]['joinparams']    = array('jointype' => 'child');
      $tab[18]['datatype']      = 'string';

      $tab[19]['table']         = 'glpi_entitydatas';
      $tab[19]['field']         = 'admin_reply';
      $tab[19]['name']          = $LANG['setup'][207];
      $tab[19]['massiveaction'] = false;
      $tab[19]['joinparams']    = array('jointype' => 'child');
      $tab[19]['datatype']      = 'string';

      $tab[20]['table']         = 'glpi_entitydatas';
      $tab[20]['field']         = 'mail_domain';
      $tab[20]['name']          = $LANG['setup'][732];
      $tab[20]['massiveaction'] = false;
      $tab[20]['joinparams']    = array('jointype' => 'child');
      $tab[20]['datatype']      = 'string';

      $tab[21]['table']         = 'glpi_entitydatas';
      $tab[21]['field']         = 'notification_subject_tag';
      $tab[21]['name']          = $LANG['mailing'][2];
      $tab[21]['joinparams']    = array('jointype' => 'child');
      $tab[21]['datatype']      = 'string';

      $tab[22]['table']         = 'glpi_entitydatas';
      $tab[22]['field']         = 'admin_email_name';
      $tab[22]['name']          = $LANG['setup'][208];
      $tab[22]['joinparams']    = array('jointype' => 'child');
      $tab[22]['datatype']      = 'string';

      $tab[23]['table']         = 'glpi_entitydatas';
      $tab[23]['field']         = 'admin_reply_name';
      $tab[23]['name']          = $LANG['setup'][209];
      $tab[23]['joinparams']    = array('jointype' => 'child');
      $tab[23]['datatype']      = 'string';

      $tab[24]['table']         = 'glpi_entitydatas';
      $tab[24]['field']         = 'mailing_signature';
      $tab[24]['name']          = $LANG['setup'][204];
      $tab[24]['joinparams']    = array('jointype' => 'child');
      $tab[24]['datatype']      = 'text';

      $tab[25]['table']         = 'glpi_entitydatas';
      $tab[25]['field']         = 'postcode';
      $tab[25]['name']          = $LANG['financial'][100];
      $tab[25]['joinparams']    = array('jointype' => 'child');
      $tab[25]['datatype']      = 'string';

      $tab[26]['table']         = 'glpi_entitydatas';
      $tab[26]['field']         = 'cartridges_alert_repeat';
      $tab[26]['name']          = $LANG['setup'][244];
      $tab[26]['joinparams']    = array('jointype' => 'child');
      $tab[26]['massiveaction'] = false;
      $tab[26]['nosearch']      = true;

      $tab[27]['table']         = 'glpi_entitydatas';
      $tab[27]['field']         = 'consumables_alert_repeat';
      $tab[27]['name']          = $LANG['setup'][243];
      $tab[27]['joinparams']    = array('jointype' => 'child');
      $tab[27]['massiveaction'] = false;
      $tab[27]['nosearch']      = true;

      $tab[28]['table']         = 'glpi_entitydatas';
      $tab[28]['field']         = 'notepad';
      $tab[28]['name']          = $LANG['title'][37];
      $tab[28]['joinparams']    = array('jointype' => 'child');
      $tab[28]['datatype']      = 'text';

      $tab[29]['table']         = 'glpi_entitydatas';
      $tab[29]['field']         = 'use_licenses_alert';
      $tab[29]['name']          = $LANG['setup'][264];
      $tab[29]['massiveaction'] = false;
      $tab[29]['nosearch']      = true;
      $tab[29]['joinparams']    = array('jointype' => 'child');

      $tab[30]['table']         = 'glpi_entitydatas';
      $tab[30]['field']         = 'use_contracts_alert';
      $tab[30]['name']          = $LANG['setup'][246];
      $tab[30]['massiveaction'] = false;
      $tab[30]['nosearch']      = true;
      $tab[30]['joinparams']    = array('jointype' => 'child');

      $tab[31]['table']         = 'glpi_entitydatas';
      $tab[31]['field']         = 'use_infocoms_alert';
      $tab[31]['name']          = $LANG['setup'][247];
      $tab[31]['massiveaction'] = false;
      $tab[31]['nosearch']      = true;
      $tab[31]['joinparams']    = array('jointype' => 'child');

      $tab[32]['table']         = 'glpi_entitydatas';
      $tab[32]['field']         = 'use_reservations_alert';
      $tab[32]['name']          = $LANG['setup'][707];
      $tab[32]['massiveaction'] = false;
      $tab[32]['nosearch']      = true;
      $tab[32]['joinparams']    = array('jointype' => 'child');

      $tab[33]['table']         = 'glpi_entitydatas';
      $tab[33]['field']         = 'autoclose_delay';
      $tab[33]['name']          = $LANG['entity'][18];
      $tab[33]['massiveaction'] = false;
      $tab[33]['nosearch']      = true;
      $tab[33]['joinparams']    = array('jointype' => 'child');

      $tab[34]['table']         = 'glpi_entitydatas';
      $tab[34]['field']         = 'notclosed_delay';
      $tab[34]['name']          = $LANG['setup'][708];
      $tab[34]['massiveaction'] = false;
      $tab[34]['nosearch']      = true;
      $tab[34]['joinparams']    = array('jointype' => 'child');

      $tab[35]['table']         = 'glpi_entitydatas';
      $tab[35]['field']         = 'auto_assign_mode';
      $tab[35]['name']          = $LANG['setup'][52];
      $tab[35]['massiveaction'] = false;
      $tab[35]['nosearch']      = true;
      $tab[35]['joinparams']    = array('jointype' => 'child');

      $tab[36]['table']         = 'glpi_entitydatas';
      $tab[36]['field']         = 'calendars_id';        // not a dropdown because of special value
      $tab[36]['name']          = $LANG['buttons'][15];
      $tab[36]['massiveaction'] = false;
      $tab[36]['nosearch']      = true;
      $tab[36]['joinparams']    = array('jointype' => 'child');

      $tab[37]['table']         = 'glpi_entitydatas';
      $tab[37]['field']         = 'tickettype';
      $tab[37]['name']          = $LANG['entity'][28];
      $tab[37]['massiveaction'] = false;
      $tab[37]['nosearch']      = true;
      $tab[37]['joinparams']    = array('jointype' => 'child');

      $tab[38]['table']         = 'glpi_entitydatas';
      $tab[38]['field']         = 'autofill_buy_date';
      $tab[38]['name']          = $LANG['financial'][14];
      $tab[38]['massiveaction'] = false;
      $tab[38]['nosearch']      = true;
      $tab[38]['joinparams']    = array('jointype' => 'child');

      $tab[39]['table']         = 'glpi_entitydatas';
      $tab[39]['field']         = 'autofill_order_date';
      $tab[39]['name']          = $LANG['financial'][28];
      $tab[39]['massiveaction'] = false;
      $tab[39]['nosearch']      = true;
      $tab[39]['joinparams']    = array('jointype' => 'child');

      $tab[40]['table']         = 'glpi_entitydatas';
      $tab[40]['field']         = 'autofill_delivery_date';
      $tab[40]['name']          = $LANG['financial'][27];
      $tab[40]['massiveaction'] = false;
      $tab[40]['nosearch']      = true;
      $tab[40]['joinparams']    = array('jointype' => 'child');

      $tab[41]['table']         = 'glpi_entitydatas';
      $tab[41]['field']         = 'autofill_use_date';
      $tab[41]['name']          = $LANG['financial'][76];
      $tab[41]['massiveaction'] = false;
      $tab[41]['nosearch']      = true;
      $tab[41]['joinparams']    = array('jointype' => 'child');

      $tab[42]['table']         = 'glpi_entitydatas';
      $tab[42]['field']         = 'autofill_warranty_date';
      $tab[42]['name']          = $LANG['financial'][29];
      $tab[42]['massiveaction'] = false;
      $tab[42]['nosearch']      = true;
      $tab[42]['joinparams']    = array('jointype' => 'child');

      $tab[43]['table']         = 'glpi_entitydatas';
      $tab[43]['field']         = 'inquest_config';
      $tab[43]['name']          = $LANG['entity'][19];
      $tab[43]['massiveaction'] = false;
      $tab[43]['nosearch']      = true;
      $tab[43]['joinparams']    = array('jointype' => 'child');

      $tab[44]['table']         = 'glpi_entitydatas';
      $tab[44]['field']         = 'inquest_rate';
      $tab[44]['name']          = $LANG['entity'][21];
      $tab[44]['massiveaction'] = false;
      $tab[44]['joinparams']    = array('jointype' => 'child');
      $tab[44]['datatype']      = 'number';

      $tab[45]['table']         = 'glpi_entitydatas';
      $tab[45]['field']         = 'inquest_delay';
      $tab[45]['name']          = $LANG['entity'][20];
      $tab[45]['massiveaction'] = false;
      $tab[45]['joinparams']    = array('jointype' => 'child');
      $tab[45]['datatype']      = 'number';

      $tab[46]['table']         = 'glpi_entitydatas';
      $tab[46]['field']         = 'inquest_URL';
      $tab[46]['name']          = $LANG['common'][94];
      $tab[46]['massiveaction'] = false;
      $tab[46]['joinparams']    = array('jointype' => 'child');
      $tab[46]['datatype']      = 'string';

      $tab[47]['table']         = 'glpi_entitydatas';
      $tab[47]['field']         = 'tickettemplates_id';  // not a dropdown because of special value
      $tab[47]['name']          = $LANG['job'][58];
      $tab[47]['massiveaction'] = false;
      $tab[47]['nosearch']      = true;
      $tab[47]['joinparams']    = array('jointype' => 'child');

      $tab[48]['table']         = 'glpi_entitydatas';
      $tab[48]['field']         = 'default_contract_alert';
      $tab[48]['name']          = $LANG['setup'][246].' - '.$LANG['setup'][46];
      $tab[48]['massiveaction'] = false;
      $tab[48]['nosearch']      = true;
      $tab[48]['joinparams']    = array('jointype' => 'child');

      $tab[49]['table']         = 'glpi_entitydatas';
      $tab[49]['field']         = 'default_infocom_alert';
      $tab[49]['name']          = $LANG['setup'][247].' - '.$LANG['setup'][46];
      $tab[49]['massiveaction'] = false;
      $tab[49]['nosearch']      = true;
      $tab[49]['joinparams']    = array('jointype' => 'child');

      $tab[50]['table']         = 'glpi_entitydatas';
      $tab[50]['field']         = 'default_alarm_threshold';
      $tab[50]['name']          = $LANG['setup'][115];
      $tab[50]['massiveaction'] = false;
      $tab[50]['nosearch']      = true;
      $tab[50]['datatype']      = 'number';

      $tab[51]['table']         = 'glpi_entitydatas';
      $tab[51]['field']         = 'entities_id_software';   // not a dropdown because of special value
      $tab[51]['name']          = $LANG['software'][10];
      $tab[51]['massiveaction'] = false;
      $tab[51]['nosearch']      = true;

      return $tab;
   }


   /**
    * Display entities of the loaded profile
    *
    * @param $target target for entity change action
    * @param $myname select name
    */
   static function showSelector($target, $myname) {
      global $CFG_GLPI, $LANG;

      $rand = mt_rand();

      echo "<div class='center'>";
      echo "<span class='b'>".$LANG['entity'][10]." ( <img src='".$CFG_GLPI["root_doc"].
            "/pics/entity_all.png' alt=''> ".$LANG['entity'][11].")</span><br>";
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
         rootVisible      : false
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


   /**
    * @since version 0.83 (before addRule)
    *
    * @param $input array of values
    *
   **/
   function executeAddRule($input) {
      global $LANG;

      $this->check($_POST["affectentity"], 'w');

      $collection = RuleCollection::getClassByType($_POST['sub_type']);
      $rule       = $collection->getRuleClass($_POST['sub_type']);
      $ruleid     = $rule->add($_POST);

      if ($ruleid) {
         //Add an action associated to the rule
         $ruleAction = new RuleAction();

         //Action is : affect computer to this entity
         $ruleAction->addActionByAttributes("assign", $ruleid, "entities_id",
                                            $_POST["affectentity"]);

         switch ($_POST['sub_type']) {
            case 'RuleRight' :
               if ($_POST["profiles_id"]) {
                  $ruleAction->addActionByAttributes("assign", $ruleid, "profiles_id",
                                                     $_POST["profiles_id"]);
               }
               $ruleAction->addActionByAttributes("assign", $ruleid, "is_recursive",
                                                  $_POST["is_recursive"]);
         }
      }

      Event::log($ruleid, "rules", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][22]);
      Html::back();
   }


   /**
    * get all entities with a notification option set
    * manage CONFIG_PARENT (or NULL) value
    *
    * @param $field  String name of the field to search (>0)
    *
    * @return Array of id => value
   **/
   static function getEntitiesToNotify($field) {
      global $DB, $CFG_GLPI;

      $entities = array();

      // root entity first
      $ent = new EntityData();
      if ($ent->getFromDB(0)) {  // always exists
         $val = $ent->getField($field);
         if ($val>0) {
            $entities[0] = $val;
         }
      }

      // Others entities in level order (parent first)
      $query = "SELECT `glpi_entities`.`id` AS `entity`,
                       `glpi_entities`.`entities_id` AS `parent`,
                       `glpi_entitydatas`.`$field`
                FROM `glpi_entities`
                LEFT JOIN `glpi_entitydatas`
                     ON (`glpi_entitydatas`.`entities_id` = `glpi_entities`.`id`)
                ORDER BY `glpi_entities`.`level` ASC";


      foreach ($DB->request($query) as $entitydatas) {
         if ((is_null($entitydatas[$field]) || $entitydatas[$field]==EntityData::CONFIG_PARENT)
             && isset($entities[$entitydatas['parent']])) {

            // config inherit from parent
            $entities[$entitydatas['entity']] = $entities[$entitydatas['parent']];

         } else if ($entitydatas[$field] > 0) {

            // config found in entity
            $entities[$entitydatas['entity']] = $entitydatas[$field];
         }
      }

      return $entities;
   }


   function showNotesForm() {

      if (isset($this->fields['id'])) {
         $entitydata = new EntityData();
         if (!$entitydata->getFromDB($this->fields['id'])) {
            $entitydata->add(array('entities_id' => $this->fields['id']));
            $entitydata->getFromDB($this->fields['id']);
         }
         $entitydata->showNotesForm();
      }
   }
}

?>

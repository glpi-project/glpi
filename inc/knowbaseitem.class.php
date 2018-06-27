<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * KnowbaseItem Class
**/
class KnowbaseItem extends CommonDBVisible {


   // From CommonDBTM
   public $dohistory    = true;

   // For visibility checks
   protected $users     = [];
   protected $groups    = [];
   protected $profiles  = [];
   protected $entities  = [];
   protected $items     = [];

   const KNOWBASEADMIN = 1024;
   const READFAQ       = 2048;
   const PUBLISHFAQ    = 4096;
   const COMMENTS      = 8192;

   static $rightname   = 'knowbase';


   static function getTypeName($nb = 0) {
      return __('Knowledge base');
   }


   /**
    * @see CommonGLPI::getMenuShorcut()
    *
    * @since 0.85
   **/
   static function getMenuShorcut() {
      return 'b';
   }

   /**
    * @see CommonGLPI::getMenuName()
    *
    * @since 0.85
   **/
   static function getMenuName() {
      if (!Session::haveRight('knowbase', READ)) {
         return __('FAQ');
      } else {
         return static::getTypeName(Session::getPluralNumber());
      }
   }


   static function canCreate() {

      return Session::haveRightsOr(self::$rightname, [CREATE, self::PUBLISHFAQ]);
   }


   /**
    * @since 0.85
   **/
   static function canUpdate() {
      return Session::haveRightsOr(self::$rightname, [UPDATE, self::KNOWBASEADMIN]);
   }


   static function canView() {
      global $CFG_GLPI;

      return (Session::haveRightsOr(self::$rightname, [READ, self::READFAQ])
              || ((Session::getLoginUserID() === false) && $CFG_GLPI["use_public_faq"]));
   }


   function canViewItem() {
      global $CFG_GLPI;

      if ($this->fields['users_id'] == Session::getLoginUserID()) {
         return true;
      }
      if (Session::haveRight(self::$rightname, self::KNOWBASEADMIN)) {
         return true;
      }

      if ($this->fields["is_faq"]) {
         return ((Session::haveRightsOr(self::$rightname, [READ, self::READFAQ])
                  && $this->haveVisibilityAccess())
                 || ((Session::getLoginUserID() === false) && $this->isPubliclyVisible()));
      }
      return (Session::haveRight(self::$rightname, READ) && $this->haveVisibilityAccess());
   }


   function canUpdateItem() {

      // Personal knowbase or visibility and write access
      return (Session::haveRight(self::$rightname, self::KNOWBASEADMIN)
              || (Session::getCurrentInterface() == "central"
                  && $this->fields['users_id'] == Session::getLoginUserID())
              || ((($this->fields["is_faq"] && Session::haveRight(self::$rightname, self::PUBLISHFAQ))
                   || (!$this->fields["is_faq"]
                       && Session::haveRight(self::$rightname, UPDATE)))
                  && $this->haveVisibilityAccess()));
   }

   /**
    * Check if current user can comment on KB entries
    *
    * @return boolean
    */
   public function canComment() {
      return $this->canViewItem() && Session::haveRight(self::$rightname, self::COMMENTS);
   }

   /**
    * Get the search page URL for the current classe
    *
    * @since 0.84
    *
    * @param $full path or relative one (true by default)
   **/
   static function getSearchURL($full = true) {
      global $CFG_GLPI;

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');

      if (Session::getCurrentInterface() == "central") {
         return "$dir/front/knowbaseitem.php";
      }
      return "$dir/front/helpdesk.faq.php";
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);

      $this->addStandardTab('KnowbaseItemTranslation', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Revision', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Comment', $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case __CLASS__ :
               $ong[1] = $this->getTypeName(1);
               if ($item->canUpdateItem()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = $item->countVisibilities();
                  }
                  $ong[2] = self::createTabEntry(_n('Target', 'Targets', Session::getPluralNumber()),
                                                    $nb);
                  $ong[3] = __('Edit');
               }
               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showFull();
               break;

            case 2 :
               $item->showVisibility();
               break;

            case 3 :
               $item->showForm($item->getID());
               break;
         }
      }
      return true;
   }


   /**
    * Actions done at the end of the getEmpty function
    *
    *@return nothing
   **/
   function post_getEmpty() {

      if (Session::haveRight(self::$rightname, self::PUBLISHFAQ)
          && !Session::haveRight("knowbase", UPDATE)) {
         $this->fields["is_faq"] = 1;
      }
   }


   /**
    * @since 0.85
    * @see CommonDBTM::post_addItem()
   **/
   function post_addItem() {

      // add documents (and replace inline pictures)
      $this->input = $this->addFiles($this->input, ['force_update'  => true,
                                                    'content_field' => 'answer',
                                                    'use_rich_text' => true]);

      if (isset($this->input["_visibility"])
          && isset($this->input["_visibility"]['_type'])
          && !empty($this->input["_visibility"]["_type"])) {

         $this->input["_visibility"]['knowbaseitems_id'] = $this->getID();
         $item                                           = null;

         switch ($this->input["_visibility"]['_type']) {
            case 'User' :
               if (isset($this->input["_visibility"]['users_id'])
                   && $this->input["_visibility"]['users_id']) {
                  $item = new KnowbaseItem_User();
               }
               break;

            case 'Group' :
               if (isset($this->input["_visibility"]['groups_id'])
                   && $this->input["_visibility"]['groups_id']) {
                  $item = new Group_KnowbaseItem();
               }
               break;

            case 'Profile' :
               if (isset($this->input["_visibility"]['profiles_id'])
                   && $this->input["_visibility"]['profiles_id']) {
                  $item = new KnowbaseItem_Profile();
               }
               break;

            case 'Entity' :
               $item = new Entity_KnowbaseItem();
               break;
         }
         if (!is_null($item)) {
            $item->add($this->input["_visibility"]);
            Event::log($this->getID(), "knowbaseitem", 4, "tools",
                     //TRANS: %s is the user login
                     sprintf(__('%s adds a target'), $_SESSION["glpiname"]));
         }
      }

      if (isset($this->input['_do_item_link']) && $this->input['_do_item_link'] == 1) {
         $params = [
            'knowbaseitems_id' => $this->getID(),
            'itemtype'         => $this->input['_itemtype'],
            'items_id'         => $this->input['_items_id']
         ];
         $kb_item_item = new KnowbaseItem_Item();
         $kb_item_item->add($params);
      }
   }


   /**
    * @since 0.83
   **/
   function post_getFromDB() {

      // Users
      $this->users    = KnowbaseItem_User::getUsers($this->fields['id']);

      // Entities
      $this->entities = Entity_KnowbaseItem::getEntities($this->fields['id']);

      // Group / entities
      $this->groups   = Group_KnowbaseItem::getGroups($this->fields['id']);

      // Profile / entities
      $this->profiles = KnowbaseItem_Profile::getProfiles($this->fields['id']);

      //Linked kb items
      $this->knowbase_items = KnowbaseItem_Item::getItems($this);
   }


   /**
    * @see CommonDBTM::cleanDBonPurge()
    *
    * @since 0.83.1
   **/
   function cleanDBonPurge() {

      $class = new KnowbaseItem_User();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Entity_KnowbaseItem();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Group_KnowbaseItem();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new KnowbaseItem_Profile();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new KnowbaseItem_Item();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new KnowbaseItem_Revision();
      $class->deleteByCriteria(['knowbaseitems_id' => $this->getID()]);
      $class = new KnowbaseItem_Comment();
      $class->deleteByCriteria(['knowbaseitems_id' => $this->fields['id']]);
   }

   /**
    * Check is this item if visible to everybody (anonymous users)
    *
    * @since 0.83
    *
    * @return Boolean
   **/
   function isPubliclyVisible() {
      global $CFG_GLPI;

      if (!$CFG_GLPI['use_public_faq']) {
         return false;
      }

      if (isset($this->entities[0])) { // Browse root entity rights
         foreach ($this->entities[0] as $entity) {
            if ($entity['is_recursive']) {
               return true;
            }
         }
      }
      return false;
   }

   public function haveVisibilityAccess() {
      // No public knowbaseitem right : no visibility check
      if (!Session::haveRightsOr(self::$rightname, [self::READFAQ, READ])) {
         return false;
      }

      // KB Admin
      if (Session::haveRight(self::$rightname, self::KNOWBASEADMIN)) {
         return true;
      }

      return parent::haveVisibilityAccess();
   }

   /**
   * Return visibility joins to add to SQL
   *
   * @since 0.83
   *
   * @param $forceall force all joins (false by default)
   *
   * @return string joins to add
   **/
   static function addVisibilityJoins($forceall = false) {

      $join = '';

      // Users
      $join .= " LEFT JOIN `glpi_knowbaseitems_users`
                     ON (`glpi_knowbaseitems_users`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) ";

      // Groups
      if ($forceall
          || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))) {
         $join .= " LEFT JOIN `glpi_groups_knowbaseitems`
                        ON (`glpi_groups_knowbaseitems`.`knowbaseitems_id`
                              = `glpi_knowbaseitems`.`id`) ";
      }

      // Profiles
      if ($forceall
          || (isset($_SESSION["glpiactiveprofile"])
              && isset($_SESSION["glpiactiveprofile"]['id']))) {
         $join .= " LEFT JOIN `glpi_knowbaseitems_profiles`
                        ON (`glpi_knowbaseitems_profiles`.`knowbaseitems_id`
                              = `glpi_knowbaseitems`.`id`) ";
      }

      // Entities
      if ($forceall
          || !Session::getLoginUserID()
          || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))) {
         $join .= " LEFT JOIN `glpi_entities_knowbaseitems`
                        ON (`glpi_entities_knowbaseitems`.`knowbaseitems_id`
                              = `glpi_knowbaseitems`.`id`) ";
      }

      return $join;
   }

   /**
    * Return visibility SQL restriction to add
    *
    * @since 0.83
    *
    * @return string restrict to add
   **/
   static function addVisibilityRestrict() {

      $restrict = '';
      if (Session::getLoginUserID()) {
         $restrict = "(`glpi_knowbaseitems`.`users_id` = '".Session::getLoginUserID()."' ";

         // Users
         $restrict .= " OR `glpi_knowbaseitems_users`.`users_id` = '".Session::getLoginUserID()."' ";

         // Groups
         if (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])) {
            $restrict .= " OR (`glpi_groups_knowbaseitems`.`groups_id`
                                    IN ('".implode("','", $_SESSION["glpigroups"])."')
                               AND (`glpi_groups_knowbaseitems`.`entities_id` < 0
                                    ".getEntitiesRestrictRequest("OR", "glpi_groups_knowbaseitems",
                                                                 '', '', true).")) ";
         }

         // Profiles
         if (isset($_SESSION["glpiactiveprofile"])
             && isset($_SESSION["glpiactiveprofile"]['id'])) {
            $restrict .= " OR (`glpi_knowbaseitems_profiles`.`profiles_id`
                                    = '".$_SESSION["glpiactiveprofile"]['id']."'
                               AND (`glpi_knowbaseitems_profiles`.`entities_id` < 0
                                    ".getEntitiesRestrictRequest("OR", "glpi_knowbaseitems_profiles",
                                                                 '', '', true).")) ";
         }

         // Entities
         if (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
            // Force complete SQL not summary when access to all entities
            $restrict .= getEntitiesRestrictRequest("OR", "glpi_entities_knowbaseitems", '', '',
                                                    true, true);
         }

         $restrict .= ") ";
      } else {
         $restrict = '1';
      }
      return $restrict;
   }

   /**
    * Return visibility joins to add to DBIterator parameters
    *
    * @since 9.2
    *
    * @param boolean $forceall force all joins (false by default)
    *
    * @return array
    */
   static public function getVisibilityCriteria($forceall = false) {
      if (Session::haveRight(self::$rightname, self::KNOWBASEADMIN)) {
         return [
            'LEFT JOIN' => [],
            'WHERE' => [],
         ];
      }

      $join = [];
      $where = [];

      // Users
      $join['glpi_knowbaseitems_users'] = [
         'FKEY' => [
            'glpi_knowbaseitems_users' => 'knowbaseitems_id',
            'glpi_knowbaseitems'       => 'id'
         ]
      ];

      if (Session::getLoginUserID()) {
         $where['`glpi_knowbaseitems_users`.`users_id`'] = Session::getLoginUserID();
      }

      // Groups
      if ($forceall
          || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))) {
         $join['glpi_groups_knowbaseitems'] = [
            'FKEY' => [
               'glpi_groups_knowbaseitems' => 'knowbaseitems_id',
               'glpi_knowbaseitems'        => 'id'
            ]
         ];

         if (Session::getLoginUserID()) {
            $where['`glpi_groups_knowbaseitems`.`groups_id`'] = $_SESSION["glpigroups"];
            $where['`glpi_groups_knowbaseitems`.`entities_id`'] = ['<', '0'];
            $restrict = getEntitiesRestrictCriteria('glpi_groups_knowbaseitems', '', '', true, true);
            if (count($restrict)) {
               if (isset($restrict['OR']) && count($restrict['OR'])) {
                  $where = $where + $restrict['OR'];
               } else if (!isset($restrict['OR'])) {
                  $where = $where + $restrict;
               }
            }
         }
      }

      // Profiles
      if ($forceall
          || (isset($_SESSION["glpiactiveprofile"])
              && isset($_SESSION["glpiactiveprofile"]['id']))) {
         $join['glpi_knowbaseitems_profiles'] = [
            'FKEY' => [
               'glpi_knowbaseitems_profiles' => 'knowbaseitems_id',
               'glpi_knowbaseitems'          => 'id'
            ]
         ];

         if (Session::getLoginUserID()) {
            $where['`glpi_knowbaseitems_profiles`.`profiles_id`'] = $_SESSION["glpiactiveprofile"]['id'];
            $where['`glpi_knowbaseitems_profiles`.`entities_id`'] = ['<', '0'];
            $restrict = getEntitiesRestrictCriteria('glpi_knowbaseitems_profiles', '', '', true, true);
            if (count($restrict)) {
               if (isset($restrict['OR']) && count($restrict['OR'])) {
                  $where = $where + $restrict['OR'];
               } else if (!isset($restrict['OR'])) {
                  $where = $where + $restrict;
               }
            }
         }
      }

      // Entities
      if ($forceall
          || !Session::getLoginUserID()
          || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))) {
         $join['glpi_entities_knowbaseitems'] = [
            'FKEY' => [
               'glpi_entities_knowbaseitems' => 'knowbaseitems_id',
               'glpi_knowbaseitems'          => 'id'
            ]
         ];

         if (Session::getLoginUserID()) {
            $restrict = getEntitiesRestrictCriteria('glpi_entities_knowbaseitems', '', '', true, true);
            if (count($restrict)) {
               if (isset($restrict['OR']) && count($restrict['OR'])) {
                  $where = $where + $restrict['OR'];
               } else if (!isset($restrict['OR'])) {
                  $where = $where + $restrict;
               }
            } else {
               $where['`glpi_entities_knowbaseitems`.`entities_id`'] = null;
            }
         }
      }

      $criteria = ['LEFT JOIN' => $join];
      if (count($where)) {
         $criteria['WHERE'] = ['OR' => $where];
      }

      return $criteria;
   }

   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      // set new date if not exists
      if (!isset($input["date"]) || empty($input["date"])) {
         $input["date"] = $_SESSION["glpi_currenttime"];
      }
      // set users_id

      // set title for question if empty
      if (isset($input["name"]) && empty($input["name"])) {
         $input["name"] = __('New item');
      }

      if (Session::haveRight(self::$rightname, self::PUBLISHFAQ)
          && !Session::haveRight(self::$rightname, UPDATE)) {
         $input["is_faq"] = 1;
      }
      if (!Session::haveRight(self::$rightname, self::PUBLISHFAQ)
          && Session::haveRight(self::$rightname, UPDATE)) {
         $input["is_faq"] = 0;
      }
      return $input;
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      // add documents (and replace inline pictures)
      $input = $this->addFiles($input, ['content_field' => 'answer',
                                        'use_rich_text' => true]);

      // set title for question if empty
      if (isset($input["name"]) && empty($input["name"])) {
         $input["name"] = __('New item');
      }
      return $input;
   }


   /**
    * Print out an HTML "<form>" for knowbase item
    *
    * @param $ID
    * @param $options array
    *     - target for the Form
    *
    * @return nothing (display the form)
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      // show kb item form
      if (!Session::haveRightsOr(self::$rightname,
                                 [UPDATE, self::PUBLISHFAQ, self::KNOWBASEADMIN])) {
         return false;
      }

      $this->initForm($ID, $options);
      $canedit = $this->can($ID, UPDATE);

      $item = null;
      // Load ticket solution
      if (empty($ID)
          && isset($options['item_itemtype']) && !empty($options['item_itemtype'])
          && isset($options['item_items_id']) && !empty($options['item_items_id'])) {

         if ($item = getItemForItemtype($options['item_itemtype'])) {
            if ($item->getFromDB($options['item_items_id'])) {
               $this->fields['name']   = $item->getField('name');
               $solution = new ITILSolution();
               $solution->getFromDBByCrit([
                  'itemtype'     => $item->getType(),
                  'items_id'     => $item->getID(),
                  [
                     'NOT' => ['status'       => CommonITILValidation::REFUSED]
                  ]
               ]);
               $this->fields['answer'] = $solution->getField('content');
               if ($item->isField('itilcategories_id')) {
                  $ic = new ItilCategory();
                  if ($ic->getFromDB($item->getField('itilcategories_id'))) {
                     $this->fields['knowbaseitemcategories_id']
                           = $ic->getField('knowbaseitemcategories_id');
                  }
               }
            }
         }
      }
      $rand = mt_rand();

      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Category name')."</td>";
      echo "<td>";
      echo "<input type='hidden' name='users_id' value=\"".Session::getLoginUserID()."\">";
      KnowbaseItemCategory::dropdown(['value' => $this->fields["knowbaseitemcategories_id"]]);
      echo "</td>";
      echo "<td>";
      if ($this->fields["date"]) {
         //TRANS: %s is the datetime of insertion
         printf(__('Created on %s'), Html::convDateTime($this->fields["date"]));
      }
      echo "</td><td>";
      if ($this->fields["date_mod"]) {
         //TRANS: %s is the datetime of update
         printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
      }
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      if (Session::haveRight(self::$rightname, self::PUBLISHFAQ)) {
         echo "<td>".__('Put this item in the FAQ')."</td>";
         echo "<td>";
         Dropdown::showYesNo('is_faq', $this->fields["is_faq"]);
         echo "</td>";
      } else {
         echo "<td colspan='2'>";
         if ($this->fields["is_faq"]) {
            echo __('This item is part of the FAQ');
         } else {
            echo __('This item is not part of the FAQ');
         }
         echo "</td>";
      }
      echo "<td>";
      $showuserlink = 0;
      if (Session::haveRight('user', READ)) {
         $showuserlink = 1;
      }
      if ($this->fields["users_id"]) {
         //TRANS: %s is the writer name
         printf(__('%1$s: %2$s'), __('Writer'), getUserName($this->fields["users_id"],
                                                            $showuserlink));
      }
      echo "</td><td>";
      //TRANS: %d is the number of view
      if ($ID) {
         printf(_n('%d view', '%d views', $this->fields["view"]), $this->fields["view"]);
      }
      echo "</td>";
      echo "</tr>\n";

      //Link with solution
      if ($item != null) {

         if ($item = getItemForItemtype($options['item_itemtype'])) {
            if ($item->getFromDB($options['item_items_id'])) {
               echo "<tr>";
               echo "<td>".__('Add link')."</td>";
               echo "<td colspan='3'>";
               echo "<input type='checkbox' name='_do_item_link' value='1' checked='checked'/> ";
               echo Html::hidden('_itemtype', ['value' => $item->getType()]);
               echo Html::hidden('_items_id', ['value' => $item->getID()]);
               echo sprintf(
                  __('link with %1$s'),
                  $item->getLink()
               );
               echo "</td>";
               echo "</tr>\n";
            }
         }
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Visible since')."</td><td>";
      Html::showDateTimeField("begin_date", ['value'       => $this->fields["begin_date"],
                                                  'timestep'    => 1,
                                                  'maybeempty' => true,
                                                  'canedit'    => $canedit]);
      echo "</td>";
      echo "<td>".__('Visible until')."</td><td>";
      Html::showDateTimeField("end_date", ['value'       => $this->fields["end_date"],
                                                'timestep'    => 1,
                                                'maybeempty' => true,
                                                'canedit'    => $canedit]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Subject')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='100' rows='1' name='name'>".$this->fields["name"]."</textarea>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Content')."</td>";
      echo "<td colspan='3'>";

      $cols = 100;
      $rows = 30;
      if (isset($options['_in_modal']) && $options['_in_modal']) {
         $rows = 15;
         echo Html::hidden('_in_modal', ['value' => 1]);
      }
      Html::textarea(['name'              => 'answer',
                      'value'             => $this->fields["answer"],
                      'enable_fileupload' => true,
                      'enable_richtext'   => true,
                      'cols'              => $cols,
                      'rows'              => $rows]);
      echo "</td>";
      echo "</tr>";

      if ($this->isNewID($ID)) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>"._n('Target', 'Targets', 1)."</td>";
         echo "<td>";
         $types   = ['Entity', 'Group', 'Profile', 'User'];
         $addrand = Dropdown::showItemTypes('_visibility[_type]', $types);
         echo "</td><td colspan='2'>";
         $params  = ['type'     => '__VALUE__',
                          'right'    => 'knowbase',
                          'prefix'   => '_visibility',
                          'nobutton' => 1];

         Ajax::updateItemOnSelectEvent("dropdown__visibility__type_".$addrand, "visibility$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/visibility.php",
                                       $params);
         echo "<span id='visibility$rand'></span>";
         echo "</td></tr>\n";
      }

      $this->showFormButtons($options);
      return true;
   } // function showForm


   /**
    * Add kb item to the public FAQ
    *
    * @return nothing
   **/
   function addToFaq() {
      global $DB;

      $DB->update(
         $this->getTable(), [
            'is_faq' => 1
         ], [
            'id' => $this->fields['id']
         ]
      );

      if (isset($_SESSION['glpi_faqcategories'])) {
         unset($_SESSION['glpi_faqcategories']);
      }
   }

   /**
    * Increase the view counter of the current knowbaseitem
    *
    * @since 0.83
    */
   function updateCounter() {
      global $DB;

      //update counter view
      $DB->update(
         'glpi_knowbaseitems', [
            'view'   => new \QueryExpression($DB->quoteName('view') . ' + 1')
         ], [
            'id' => $this->getID()
         ]
      );
   }


   /**
    * Print out (html) show item : question and answer
    *
    * @param $options      array of options
    *
    * @return nothing (display item : question and answer)
   **/
   function showFull($options = []) {
      global $DB, $CFG_GLPI;

      if (!$this->can($this->fields['id'], READ)) {
         return false;
      }

      $default_options = [
         'display' => true,
      ];
      $options = array_merge($default_options, $options);

      $out = "";

      $linkusers_id = true;
      // show item : question and answer
      if (((Session::getLoginUserID() === false) && $CFG_GLPI["use_public_faq"])
          || (Session::getCurrentInterface() == "helpdesk")
          || !User::canView()) {
         $linkusers_id = false;
      }

      $this->updateCounter();

      $knowbaseitemcategories_id = $this->fields["knowbaseitemcategories_id"];
      $fullcategoryname          = getTreeValueCompleteName("glpi_knowbaseitemcategories",
                                                            $knowbaseitemcategories_id);

      $tmp = "<a href='".$this->getSearchURL().
             "?knowbaseitemcategories_id=$knowbaseitemcategories_id'>".$fullcategoryname."</a>";
      $out.= "<table class='tab_cadre_fixe'>";
      $out.= "<tr><th colspan='4'>".sprintf(__('%1$s: %2$s'), __('Category'), $tmp);
      $out.= "</th></tr>";

      $out.= "<tr><td class='left' colspan='4'><h2>".__('Subject')."</h2>";
      if (KnowbaseItemTranslation::canBeTranslated($this)) {
         $out.= KnowbaseItemTranslation::getTranslatedValue($this, 'name');
      } else {
         $out.= $this->fields["name"];
      }

      $out.= "</td></tr>";
      $out.= "<tr><td class='left' colspan='4'><h2>".__('Content')."</h2>\n";

      $out.= "<div id='kbanswer'>";
      $out.= $this->getAnswer();
      $out.= "</div>";
      $out.= "</td></tr>";

      $out.= "<tr><th class='tdkb'  colspan='2'>";
      if ($this->fields["users_id"]) {
         // Integer because true may be 2 and getUserName return array
         if ($linkusers_id) {
            $linkusers_id = 1;
         } else {
            $linkusers_id = 0;
         }

         $out.= sprintf(__('%1$s: %2$s'), __('Writer'), getUserName($this->fields["users_id"],
                $linkusers_id));
         $out.= "<br>";
      }

      if ($this->fields["date"]) {
         //TRANS: %s is the datetime of update
         $out.= sprintf(__('Created on %s'), Html::convDateTime($this->fields["date"]));
         $out.= "<br>";
      }
      if ($this->fields["date_mod"]) {
         //TRANS: %s is the datetime of update
         $out.= sprintf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
      }

      $out.= "</th>";
      $out.= "<th class='tdkb' colspan='2'>";
      if ($this->countVisibilities() == 0) {
         $out.= "<span class='red'>".__('Unpublished')."</span><br>";
      }

      $out.= sprintf(_n('%d view', '%d views', $this->fields["view"]), $this->fields["view"]);
      $out.= "<br>";
      if ($this->fields["is_faq"]) {
         $out.= __('This item is part of the FAQ');
      } else {
         $out.= __('This item is not part of the FAQ');
      }
      $out.= "</th></tr>";
      $out.= "</table>";

      if ($options['display']) {
         echo $out;
      } else {
         return $out;
      }

      return true;
   }


   /**
    * Print out an HTML form for Search knowbase item
    *
    * @param $options   $_GET
    *
    * @return nothing (display the form)
   **/
   function searchForm($options) {
      global $CFG_GLPI;

      if (!$CFG_GLPI["use_public_faq"]
          && !Session::haveRightsOr(self::$rightname, [READ, self::READFAQ])) {
         return false;
      }

      // Default values of parameters
      $params["contains"]                  = "";
      $params["target"]                    = $_SERVER['PHP_SELF'];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      echo "<div>";
      echo "<form method='get' action='".$this->getSearchURL()."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><td class='right' width='50%'>";
      echo "<input type='text' size='50' name='contains' value=\"".
             Html::cleanInputText(stripslashes($params["contains"]))."\"></td>";
      echo "<td class='left'>";
      echo "<input type='submit' value=\""._sx('button', 'Search')."\" class='submit'></td></tr>";
      echo "</table>";
      if (isset($options['item_itemtype'])
          && isset($options['item_items_id'])) {
         echo "<input type='hidden' name='item_itemtype' value='".$options['item_itemtype']."'>";
         echo "<input type='hidden' name='item_items_id' value='".$options['item_items_id']."'>";
      }
      Html::closeForm();

      echo "</div>";
   }


   /**
    * Print out an HTML "<form>" for Search knowbase item
    *
    * @since 0.84
    *
    * @param $options   $_GET
    *
    * @return nothing (display the form)
   **/
   function showBrowseForm($options) {
      global $CFG_GLPI;

      if (!$CFG_GLPI["use_public_faq"]
          && !Session::haveRightsOr(self::$rightname, [READ, self::READFAQ])) {
         return false;
      }

      // Default values of parameters
      $params["knowbaseitemcategories_id"] = "";

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }
      $faq = !Session::haveRight(self::$rightname, READ);

      // Category select not for anonymous FAQ
      if (Session::getLoginUserID()
          && !$faq) {
         echo "<div>";
         echo "<form method='get' action='".$this->getSearchURL()."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><td class='right' width='50%'>".__('Category')."&nbsp;";
         KnowbaseItemCategory::dropdown(['value' => $params["knowbaseitemcategories_id"]]);
         echo "</td><td class='left'>";
         echo "<input type='submit' value=\""._sx('button', 'Post')."\" class='submit'></td>";
         echo "</tr></table>";
         if (isset($options['item_itemtype'])
             && isset($options['item_items_id'])) {
            echo "<input type='hidden' name='item_itemtype' value='".$options['item_itemtype']."'>";
            echo "<input type='hidden' name='item_items_id' value='".$options['item_items_id']."'>";
         }
         Html::closeForm();
         echo "</div>";
      }
   }


   /**
    * Print out an HTML form for Search knowbase item
    *
    * @since 0.84
    *
    * @param $options   $_GET
    *
    * @return nothing (display the form)
   **/
   function showManageForm($options) {
      global $CFG_GLPI;

      if (!Session::haveRightsOr(self::$rightname,
                                 [UPDATE, self::PUBLISHFAQ, self::KNOWBASEADMIN])) {
         return false;
      }
      $params['unpublished'] = 'my';
      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $faq = !Session::haveRight(self::$rightname, UPDATE);

      echo "<div>";
      echo "<form method='get' action='".$this->getSearchURL()."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><td class='right' width='50%'>";
      $values = ['myunpublished' => __('My unpublished articles'),
                      'allmy'         => __('All my articles')];
      if (Session::haveRight(self::$rightname, self::KNOWBASEADMIN)) {
         $values['allunpublished'] = __('All unpublished articles');
         $values['allpublished'] = __('All published articles');
      }
      Dropdown::showFromArray('unpublished', $values, ['value' => $params['unpublished']]);
      echo "</td><td class='left'>";
      echo "<input type='submit' value=\""._sx('button', 'Post')."\" class='submit'></td>";
      echo "</tr></table>";
      Html::closeForm();
      echo "</div>";
   }


   /**
    * Build request for showList
    *
    * @since 0.83
    *
    * @param $params array  (contains, knowbaseitemcategories_id, faq)
    * @param $type   string search type : browse / search (default search)
    *
    * @return String : SQL request
   **/
   static function getListRequest(array $params, $type = 'search') {
      global $DB;

      // Lists kb Items
      $where     = "";
      $order     = "";
      $score     = "";
      $addselect = "";
      $join  = self::addVisibilityJoins(true);

      switch ($type) {
         case 'myunpublished' :
            break;

         case 'allmy' :
            break;

         case 'allunpublished' :
            break;

         default :
            // Build query
            if (Session::getLoginUserID() && $type != 'myunpublished') {
               $where = self::addVisibilityRestrict();
            } else {
               // Anonymous access
               if (Session::isMultiEntitiesMode()) {
                  $where = " (`glpi_entities_knowbaseitems`.`entities_id` = 0
                              AND `glpi_entities_knowbaseitems`.`is_recursive` = 1)";
               }
            }
            break;
      }

      if (empty($where)) {
         $where = '1 = 1';
      }

      if ($params['faq']) { // helpdesk
         $where .= " AND (`glpi_knowbaseitems`.`is_faq` = 1)";
      }

      if (KnowbaseItemTranslation::isKbTranslationActive()
          && (countElementsInTable('glpi_knowbaseitemtranslations') > 0)) {
         $join .= "LEFT JOIN `glpi_knowbaseitemtranslations`
                     ON (`glpi_knowbaseitems`.`id` = `glpi_knowbaseitemtranslations`.`knowbaseitems_id`
                         AND `glpi_knowbaseitemtranslations`.`language` = '".$_SESSION['glpilanguage']."')";
         $addselect .= ", `glpi_knowbaseitemtranslations`.`name` AS transname,
                          `glpi_knowbaseitemtranslations`.`answer` AS transanswer ";
      }

      // a search with $contains
      switch ($type) {
         case 'allmy' :
            $where .= " AND `glpi_knowbaseitems`.`users_id` = '".Session::getLoginUserID()."'";
            break;

         case 'myunpublished' :
            $where .= " AND `glpi_knowbaseitems`.`users_id` = '".Session::getLoginUserID()."'
                        AND (`glpi_entities_knowbaseitems`.`entities_id` IS NULL
                              AND `glpi_knowbaseitems_profiles`.`profiles_id` IS NULL
                              AND `glpi_groups_knowbaseitems`.`groups_id` IS NULL
                              AND `glpi_knowbaseitems_users`.`users_id` IS NULL)";
            break;

         case 'allunpublished' :
            // Only published
            $where .= " AND (`glpi_entities_knowbaseitems`.`entities_id` IS NULL
                              AND `glpi_knowbaseitems_profiles`.`profiles_id` IS NULL
                              AND `glpi_groups_knowbaseitems`.`groups_id` IS NULL
                              AND `glpi_knowbaseitems_users`.`users_id` IS NULL)";
            break;

         case 'search' :
            if (strlen($params["contains"]) > 0) {
               $search  = Toolbox::unclean_cross_side_scripting_deep($params["contains"]);
               $search_wilcard = explode(' ', $search);
               $search_wilcard = implode('* ', $search_wilcard).'*';

               $addscore = [];
               if (KnowbaseItemTranslation::isKbTranslationActive()
                   && (countElementsInTable('glpi_knowbaseitemtranslations') > 0)) {
                  $addscore = ['`glpi_knowbaseitemtranslations`.`name`',
                                    '`glpi_knowbaseitemtranslations`.`answer`'];
               }
               $score = " ,(MATCH(`glpi_knowbaseitems`.`name`, `glpi_knowbaseitems`.`answer`)
                           AGAINST('$search_wilcard' IN BOOLEAN MODE)";

               if (!empty($addscore)) {
                  foreach ($addscore as $addscore_field) {
                     $score.= " + MATCH($addscore_field)
                                        AGAINST('$search_wilcard' IN BOOLEAN MODE)";
                  }
               }
               $score .=" ) AS SCORE ";

               $where_1 = $where." AND (MATCH(`glpi_knowbaseitems`.`name`,
                                             `glpi_knowbaseitems`.`answer`)
                          AGAINST('$search_wilcard' IN BOOLEAN MODE) ";

               if (!empty($addscore)) {
                  foreach ($addscore as $addscore_field) {
                     $where_1.= "OR $addscore_field IS NOT NULL
                                    AND MATCH($addscore_field)
                                        AGAINST('$search_wilcard' IN BOOLEAN MODE)";
                  }
               }
               $where_1.= ")";

               // Add visibility date
               $where_1 .= " AND (`glpi_knowbaseitems`.`begin_date` IS NULL
                                   OR `glpi_knowbaseitems`.`begin_date` < NOW())
                             AND (`glpi_knowbaseitems`.`end_date` IS NULL
                                  OR `glpi_knowbaseitems`.`end_date` > NOW()) ";

               $order   = "ORDER BY `SCORE` DESC";

               // preliminar query to allow alternate search if no result with fulltext
               $query_1   = "SELECT COUNT(`glpi_knowbaseitems`.`id`)
                             FROM `glpi_knowbaseitems`
                             $join
                             WHERE $where_1";
               $result_1  = $DB->query($query_1);
               $numrows_1 = $DB->result($result_1, 0, 0);

               if ($numrows_1 <= 0) {// not result this fulltext try with alternate search
                  $search1 = [/* 1 */   '/\\\"/',
                                   /* 2 */   "/\+/",
                                   /* 3 */   "/\*/",
                                   /* 4 */   "/~/",
                                   /* 5 */   "/</",
                                   /* 6 */   "/>/",
                                   /* 7 */   "/\(/",
                                   /* 8 */   "/\)/",
                                   /* 9 */   "/\-/"];
                  $contains = preg_replace($search1, "", $params["contains"]);
                  $addwhere = '';
                  if (KnowbaseItemTranslation::isKbTranslationActive()
                      && (countElementsInTable('glpi_knowbaseitemtranslations') > 0)) {
                     $addwhere = " OR `glpi_knowbaseitemtranslations`.`name` ".Search::makeTextSearch($contains)."
                                    OR `glpi_knowbaseitemtranslations`.`answer` ".Search::makeTextSearch($contains);
                  }
                  $where   .= " AND (`glpi_knowbaseitems`.`name` ".Search::makeTextSearch($contains)."
                                 OR `glpi_knowbaseitems`.`answer` ".Search::makeTextSearch($contains)."
                                 $addwhere)";
               } else {
                  $where = $where_1;
               }
            }
            break;

         case 'browse' :
            $where .= " AND (`glpi_knowbaseitems`.`knowbaseitemcategories_id`
                           = '".$params["knowbaseitemcategories_id"]."')";
            // Add visibility date
            $where .= " AND (`glpi_knowbaseitems`.`begin_date` IS NULL
                             OR `glpi_knowbaseitems`.`begin_date` < NOW())
                        AND (`glpi_knowbaseitems`.`end_date` IS NULL
                             OR `glpi_knowbaseitems`.`end_date` > NOW()) ";

            $order  = " ORDER BY `glpi_knowbaseitems`.`name` ASC";
            break;
      }

      $query = "SELECT DISTINCT `glpi_knowbaseitems`.*,
                       `glpi_knowbaseitemcategories`.`completename` AS category
                       $addselect
                       $score
                FROM `glpi_knowbaseitems`
                $join
                LEFT JOIN `glpi_knowbaseitemcategories`
                     ON (`glpi_knowbaseitemcategories`.`id`
                           = `glpi_knowbaseitems`.`knowbaseitemcategories_id`)
                WHERE $where
                $order";
      return $query;
   }


   /**
    * Print out list kb item
    *
    * @param $options            $_GET
    * @param $type      string   search type : browse / search (default search)
   **/
   static function showList($options, $type = 'search') {
      global $DB, $CFG_GLPI;

      // Default values of parameters
      $params['faq']                       = !Session::haveRight(self::$rightname, READ);
      $params["start"]                     = "0";
      $params["knowbaseitemcategories_id"] = "0";
      $params["contains"]                  = "";
      $params["target"]                    = $_SERVER['PHP_SELF'];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }
      $ki = new self();
      switch ($type) {
         case 'myunpublished' :
            if (!Session::haveRightsOr(self::$rightname, [UPDATE, self::PUBLISHFAQ])) {
               return false;
            }
            break;

         case 'allunpublished' :
            if (!Session::haveRight(self::$rightname, self::KNOWBASEADMIN)) {
               return false;
            }
            break;

         default :
            break;
      }

      if (!$params["start"]) {
         $params["start"] = 0;
      }

      $query = self::getListRequest($params, $type);
      // Get it from database
      if ($result = $DB->query($query)) {
         $KbCategory = new KnowbaseItemCategory();
         $title      = "";
         if ($KbCategory->getFromDB($params["knowbaseitemcategories_id"])) {
            $title = (empty($KbCategory->fields['name']) ?"(".$params['knowbaseitemcategories_id'].")"
                                                         : $KbCategory->fields['name']);
            $title = sprintf(__('%1$s: %2$s'), __('Category'), $title);
         }

         Session::initNavigateListItems('KnowbaseItem', $title);

         $numrows    = $DB->numrows($result);
         $list_limit = $_SESSION['glpilist_limit'];

         $showwriter = in_array($type, ['myunpublished', 'allunpublished', 'allmy']);

         // Limit the result, if no limit applies, use prior result
         if (($numrows > $list_limit)
             && !isset($_GET['export_all'])) {
            $query_limit   = $query ." LIMIT ".intval($params["start"]).", ".intval($list_limit)." ";
            $result_limit  = $DB->query($query_limit);
            $numrows_limit = $DB->numrows($result_limit);

         } else {
            $numrows_limit = $numrows;
            $result_limit  = $result;
         }

         if ($numrows_limit > 0) {
            // Set display type for export if define
            $output_type = Search::HTML_OUTPUT;

            if (isset($_GET["display_type"])) {
               $output_type = $_GET["display_type"];
            }

            // Pager
            $parameters = "start=".$params["start"]."&amp;knowbaseitemcategories_id=".
                          $params['knowbaseitemcategories_id']."&amp;contains=".
                          $params["contains"]."&amp;is_faq=".$params['faq'];

            if (isset($options['item_itemtype'])
                && isset($options['item_items_id'])) {
               $parameters .= "&amp;item_items_id=".$options['item_items_id']."&amp;item_itemtype=".
                               $options['item_itemtype'];
            }

            if ($output_type == Search::HTML_OUTPUT) {
               Html::printPager($params['start'], $numrows,
                                Toolbox::getItemTypeSearchURL('KnowbaseItem'), $parameters,
                                'KnowbaseItem');
            }

            $nbcols = 1;
            // Display List Header
            echo Search::showHeader($output_type, $numrows_limit+1, $nbcols);

            echo Search::showNewLine($output_type);
            $header_num = 1;
            echo Search::showHeaderItem($output_type, __('Subject'), $header_num);

            if ($output_type != Search::HTML_OUTPUT) {
               echo Search::showHeaderItem($output_type, __('Content'), $header_num);
            }

            if ($showwriter) {
               echo Search::showHeaderItem($output_type, __('Writer'), $header_num);
            }
            echo Search::showHeaderItem($output_type, __('Category'), $header_num);

            if (isset($options['item_itemtype'])
                && isset($options['item_items_id'])
                && ($output_type == Search::HTML_OUTPUT)) {
               echo Search::showHeaderItem($output_type, '&nbsp;', $header_num);
            }

            // Num of the row (1=header_line)
            $row_num = 1;
            for ($i=0; $i<$numrows_limit; $i++) {
               $data = $DB->fetch_assoc($result_limit);

               Session::addToNavigateListItems('KnowbaseItem', $data["id"]);
               // Column num
               $item_num = 1;
               $row_num++;
               echo Search::showNewLine($output_type, $i%2);

               $item = new self;
               $item->getFromDB($data["id"]);
               $name   = $data["name"];
               $answer = $data["answer"];
               // Manage translations
               if (isset($data['transname']) && !empty($data['transname'])) {
                  $name   = $data["transname"];
               }
               if (isset($data['transanswer']) && !empty($data['transanswer'])) {
                  $answer = $data["transanswer"];
               }

               if ($output_type == Search::HTML_OUTPUT) {
                  $toadd = '';
                  if (isset($options['item_itemtype'])
                      && isset($options['item_items_id'])) {
                     $href  = " href='#' onClick=\"".Html::jsGetElementbyID('kbshow'.$data["id"]).".dialog('open'); return false;\"";
                     $toadd = Ajax::createIframeModalWindow('kbshow'.$data["id"],
                                                            KnowbaseItem::getFormURLWithID($data["id"]),
                                                            ['display' => false]);
                  } else {
                     $href = " href=\"".KnowbaseItem::getFormURLWithID($data["id"])."\" ";
                  }

                  echo Search::showItem($output_type,
                                        "<div class='kb'>$toadd<a ".
                                          ($data['is_faq']?" class='pubfaq' title='"
                                                           .__s("This item is part of the FAQ")."' "
                                                           :" class='knowbase' ").
                                          " $href>".Html::resume_text($name, 80)."</a></div>
                                          <div class='kb_resume'>".
                                          Html::resume_text(Html::clean(Toolbox::unclean_cross_side_scripting_deep($answer)),
                                                            600)."</div>",
                                        $item_num, $row_num);
               } else {
                  echo Search::showItem($output_type, $name, $item_num, $row_num);
                  echo Search::showItem($output_type,
                     Html::clean(Toolbox::unclean_cross_side_scripting_deep(html_entity_decode($answer,
                                                                                               ENT_QUOTES,
                                                                                               "UTF-8"))),
                                $item_num, $row_num);
               }

               $showuserlink = 0;
               if (Session::haveRight('user', READ)) {
                  $showuserlink = 1;
               }
               if ($showwriter) {
                  echo Search::showItem($output_type, getUserName($data["users_id"], $showuserlink),
                                           $item_num, $row_num);
               }

               $categ = $data["category"];
               if ($output_type == Search::HTML_OUTPUT) {
                  $cathref = $ki->getSearchURL()."?knowbaseitemcategories_id=".
                              $data["knowbaseitemcategories_id"].'&amp;forcetab=Knowbase$2';
                  $categ   = "<a href='$cathref'>".$categ.'</a>';
               }
               echo Search::showItem($output_type, $categ, $item_num, $row_num);

               if (isset($options['item_itemtype'])
                   && isset($options['item_items_id'])
                   && ($output_type == Search::HTML_OUTPUT)) {

                  $forcetab = $options['item_itemtype'];
                  if (!$_SESSION['glpiticket_timeline'] || $_SESSION['glpiticket_timeline_keep_replaced_tabs']) {
                     $forcetab .= '$2'; //Solution tab
                  } else {
                     $forcetab .= '$1'; //Timeline tab
                  }
                  $content = "<a href='".Toolbox::getItemTypeFormURL($options['item_itemtype']).
                               "?load_kb_sol=".$data['id']."&amp;id=".$options['item_items_id'].
                               "&amp;forcetab=".$forcetab."'>".
                               __('Use as a solution')."</a>";
                  echo Search::showItem($output_type, $content, $item_num, $row_num);
               }

               // End Line
               echo Search::showEndLine($output_type);
            }

            // Display footer
            if (($output_type == Search::PDF_OUTPUT_LANDSCAPE)
                || ($output_type == Search::PDF_OUTPUT_PORTRAIT)) {
               echo Search::showFooter($output_type,
                                       Dropdown::getDropdownName("glpi_knowbaseitemcategories",
                                                                 $params['knowbaseitemcategories_id']),
                                       $numrows_limit);
            } else {
               echo Search::showFooter($output_type, '', $numrows_limit);
            }
            echo "<br>";
            if ($output_type == Search::HTML_OUTPUT) {
               Html::printPager($params['start'], $numrows,
                                Toolbox::getItemTypeSearchURL('KnowbaseItem'), $parameters,
                                'KnowbaseItem');
            }

         } else {
            echo "<div class='center b'>".__('No item found')."</div>";
         }
      }
   }


   /**
    * Print out list recent or popular kb/faq
    *
    * @param $type      type : recent / popular / not published
    *
    * @return nothing (display table)
   **/
   static function showRecentPopular($type) {
      global $DB, $CFG_GLPI;

      $faq = !Session::haveRight(self::$rightname, READ);

      if ($type == "recent") {
         $orderby = "ORDER BY `date` DESC";
         $title   = __('Recent entries');
      } else if ($type == 'lastupdate') {
         $orderby = "ORDER BY `date_mod` DESC";
         $title   = __('Last updated entries');
      } else {
         $orderby = "ORDER BY `view` DESC";
         $title   = __('Most popular questions');
      }

      $faq_limit = "";
      $addselect = "";
      // Force all joins for not published to verify no visibility set
      $join = self::addVisibilityJoins(true);

      if (Session::getLoginUserID()) {
         $faq_limit .= "WHERE ".self::addVisibilityRestrict();
      } else {
         // Anonymous access
         if (Session::isMultiEntitiesMode()) {
            $faq_limit .= " WHERE (`glpi_entities_knowbaseitems`.`entities_id` = 0
                                   AND `glpi_entities_knowbaseitems`.`is_recursive` = 1)";
         } else {
            $faq_limit .= " WHERE 1";
         }
      }

      // Only published
      $faq_limit .= " AND (`glpi_entities_knowbaseitems`.`entities_id` IS NOT NULL
                           OR `glpi_knowbaseitems_profiles`.`profiles_id` IS NOT NULL
                           OR `glpi_groups_knowbaseitems`.`groups_id` IS NOT NULL
                           OR `glpi_knowbaseitems_users`.`users_id` IS NOT NULL)";

      // Add visibility date
      $faq_limit .= " AND (`glpi_knowbaseitems`.`begin_date` IS NULL
                           OR `glpi_knowbaseitems`.`begin_date` < NOW())
                      AND (`glpi_knowbaseitems`.`end_date` IS NULL
                           OR `glpi_knowbaseitems`.`end_date` > NOW()) ";

      if ($faq) { // FAQ
         $faq_limit .= " AND (`glpi_knowbaseitems`.`is_faq` = 1)";
      }

      if (KnowbaseItemTranslation::isKbTranslationActive()
          && (countElementsInTable('glpi_knowbaseitemtranslations') > 0)) {
         $join .= "LEFT JOIN `glpi_knowbaseitemtranslations`
                     ON (`glpi_knowbaseitems`.`id` = `glpi_knowbaseitemtranslations`.`knowbaseitems_id`
                           AND `glpi_knowbaseitemtranslations`.`language` = '".$_SESSION['glpilanguage']."')";
         $addselect .= ", `glpi_knowbaseitemtranslations`.`name` AS transname,
                          `glpi_knowbaseitemtranslations`.`answer` AS transanswer ";
      }

      $query = "SELECT DISTINCT `glpi_knowbaseitems`.* $addselect
                FROM `glpi_knowbaseitems`
                $join
                $faq_limit
                $orderby
                LIMIT 10";
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number > 0) {
         echo "<table class='tab_cadrehov'>";
         echo "<tr class='noHover'><th>".$title."</th></tr>";
         while ($data = $DB->fetch_assoc($result)) {
            $name = $data['name'];

            if (isset($data['transname']) && !empty($data['transname'])) {
               $name = $data['transname'];
            }
            echo "<tr class='tab_bg_2'><td class='left'>";
            echo "<a ".
                  ($data['is_faq']?" class='pubfaq' title='"
                                   .__s("This item is part of the FAQ")."' "
                                   :" class='knowbase' ")." href=\"".KnowbaseItem::getFormURLWithID($data["id"])."\">".
                  Html::resume_text($name, 80)."</a></td></tr>";
         }
         echo "</table>";
      }
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_knowbaseitemcategories',
         'field'              => 'name',
         'name'               => __('Category'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'date',
         'name'               => __('Date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Subject'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'answer',
         'name'               => __('Content'),
         'datatype'           => 'text',
         'htmltext'           => true
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'is_faq',
         'name'               => __('FAQ item'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'view',
         'name'               => _n('View', 'Views', Session::getPluralNumber()),
         'datatype'           => 'integer',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'begin_date',
         'name'               => __('Visibility start date'),
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'end_date',
         'name'               => __('Visibility end date'),
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '70',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('User'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'right'              => 'all'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

      return $tab;
   }

   /**
    * @since 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface = 'central') {

      if ($interface == 'central') {
         $values = parent::getRights();
         $values[self::KNOWBASEADMIN] = __('Knowledge base administration');
         $values[self::PUBLISHFAQ]    = __('Publish in the FAQ');
         $values[self::COMMENTS]      = __('Comment KB entries');
      }
      $values[self::READFAQ]       = __('Read the FAQ');
      return $values;
   }

   function pre_updateInDB() {
      $revision = new KnowbaseItem_Revision();
      $kb = new KnowbaseItem();
      $kb->getFromDB($this->getID());
      $revision->createNew($kb);
   }

   /**
    * Get KB answer, with id on titles to set anchors
    *
    * @return string
    */
   public function getAnswer() {
      if (KnowbaseItemTranslation::canBeTranslated($this)) {
         $answer = KnowbaseItemTranslation::getTranslatedValue($this, 'answer');
      } else {
         $answer = $this->fields["answer"];
      }
      $answer = html_entity_decode($answer);
      $answer = Toolbox::unclean_html_cross_side_scripting_deep($answer);

      $callback = function ($matches) {
         //1 => tag name, 2 => existing attributes, 3 => title contents
         $tpl = '<%tag%attrs id="%slug"><a href="#%slug">%icon</a>%title</%tag>';

         $title = str_replace(
            ['%tag', '%attrs', '%slug', '%title', '%icon'],
            [
               $matches[1],
               $matches[2],
               Toolbox::slugify($matches[3]),
               $matches[3],
               '<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 16 16" width="16"><path d="M4 9h1v1H4c-1.5 0-3-1.69-3-3.5S2.55 3 4 3h4c1.45 0 3 1.69 3 3.5 0 1.41-.91 2.72-2 3.25V8.59c.58-.45 1-1.27 1-2.09C10 5.22 8.98 4 8 4H4c-.98 0-2 1.22-2 2.5S3 9 4 9zm9-3h-1v1h1c1 0 2 1.22 2 2.5S13.98 12 13 12H9c-.98 0-2-1.22-2-2.5 0-.83.42-1.64 1-2.09V6.25c-1.09.53-2 1.84-2 3.25C6 11.31 7.55 13 9 13h4c1.45 0 3-1.69 3-3.5S14.5 6 13 6z"/></svg>'
            ],
            $tpl
         );

         return $title;
      };
      $pattern = '|<(h[1-6]{1})(.?[^>])?>(.+)</h[1-6]{1}>|';
      $answer = preg_replace_callback($pattern, $callback, $answer);

      return $answer;
   }

   /**
    * Get dropdown parameters from showVisibility method
    *
    * @return array
    */
   protected function getShowVisibilityDropdownParams() {
      $params = parent::getShowVisibilityDropdownParams();
      $params['right'] = ($this->getField('is_faq') ? 'faq' : 'knowbase');
      return $params;
   }

   /**
    * Reverts item contents to specified revision
    *
    * @param integer $revid Revision ID
    *
    * @return boolean
    */
   public function revertTo($revid) {
      $revision = new KnowbaseItem_Revision();
      $revision->getFromDB($revid);

      $values = [
         'id'     => $this->getID(),
         'name'   => $revision->fields['name'],
         'answer' => $revision->fields['answer']
      ];

      if ($this->update($values)) {
         Event::log($this->getID(), "knowbaseitem", 5, "tools",
                    //TRANS: %s is the user login, %d the revision number
                    sprintf(__('%s reverts item to revision %id'), $_SESSION["glpiname"], $revid));
         return true;
      } else {
         return false;
      }
   }
}

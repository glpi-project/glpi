<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Event;
use Glpi\RichText\RichText;
use Glpi\Toolbox\Sanitizer;

/**
 * KnowbaseItem Class
 **/
class KnowbaseItem extends CommonDBVisible implements ExtraVisibilityCriteria
{
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

    public static $rightname   = 'knowbase';


    public static function getTypeName($nb = 0)
    {
        return __('Knowledge base');
    }


    /**
     * @see CommonGLPI::getMenuShorcut()
     *
     * @since 0.85
     **/
    public static function getMenuShorcut()
    {
        return 'b';
    }


    public function getName($options = [])
    {
        if (KnowbaseItemTranslation::canBeTranslated($this)) {
            return KnowbaseItemTranslation::getTranslatedValue($this);
        }

        return parent::getName();
    }


    /**
     * @see CommonGLPI::getMenuName()
     *
     * @since 0.85
     **/
    public static function getMenuName()
    {
        if (!Session::haveRight('knowbase', READ)) {
            return __('FAQ');
        } else {
            return static::getTypeName(Session::getPluralNumber());
        }
    }


    public static function getMenuContent()
    {
        $menu = parent::getMenuContent();
        if (isset($menu['links']['lists'])) {
            unset($menu['links']['lists']);
        }

        return $menu;
    }


    public static function canCreate()
    {

        return Session::haveRightsOr(self::$rightname, [CREATE, self::PUBLISHFAQ]);
    }


    /**
     * @since 0.85
     **/
    public static function canUpdate()
    {
        return Session::haveRightsOr(self::$rightname, [UPDATE, self::KNOWBASEADMIN]);
    }


    public static function canView()
    {
        global $CFG_GLPI;

        return (Session::haveRightsOr(self::$rightname, [READ, self::READFAQ])
              || ((Session::getLoginUserID() === false) && $CFG_GLPI["use_public_faq"]));
    }


    public function canViewItem()
    {
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


    public function canUpdateItem()
    {
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
    public function canComment()
    {
        return $this->canViewItem() && Session::haveRight(self::$rightname, self::COMMENTS);
    }

    /**
     * Get the search page URL for the current classe
     *
     * @since 0.84
     *
     * @param boolean $full  path or relative one
     **/
    public static function getSearchURL($full = true)
    {
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');

        if (Session::getCurrentInterface() == "central") {
            return "$dir/front/knowbaseitem.php";
        }
        return "$dir/front/helpdesk.faq.php";
    }

    /**
     * Get the form page URL for the current classe
     *
     * @param boolean $full  path or relative one
     **/
    public static function getFormURL($full = true)
    {
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');

        if (Session::getCurrentInterface() == "central") {
            return "$dir/front/knowbaseitem.form.php";
        }
        return "$dir/front/helpdesk.faq.php";
    }

    /**
     * Get the form page URL for the current classe
     *
     * @param boolean $full  path or relative one
     **/
    public static function getFormURLWithParam($params = [], $full = true)
    {
        $url = self::getFormURL($full) . '?';

        if (isset($params['_sol_to_kb'])) {
            $url .= '&_sol_to_kb=' . $params['_sol_to_kb'];
        }
        if (isset($params['_fup_to_kb'])) {
            $url .= '&_fup_to_kb=' . $params['_fup_to_kb'];
        }
        if (isset($params['_task_to_kb'])) {
            $url .= '&_task_to_kb=' . $params['_task_to_kb'];
        }
        return $url;
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
        $this->addStandardTab('Document_Item', $ong, $options);
        $this->addStandardTab('KnowbaseItem_KnowbaseItemCategory', $ong, $options);
        $this->addStandardTab('KnowbaseItemTranslation', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);
        $this->addStandardTab('KnowbaseItem_Revision', $ong, $options);
        $this->addStandardTab('KnowbaseItem_Comment', $ong, $options);

        return $ong;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case __CLASS__:
                    $ong[1] = $this->getTypeName(1);
                    if ($item->canUpdateItem()) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb = $item->countVisibilities();
                        }
                        $ong[2] = self::createTabEntry(
                            _n('Target', 'Targets', Session::getPluralNumber()),
                            $nb
                        );
                        $ong[3] = __('Edit');
                    }
                    return $ong;
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            switch ($tabnum) {
                case 1:
                    $item->showFull();
                    break;

                case 2:
                    $item->showVisibility();
                    break;

                case 3:
                    $item->showForm($item->getID());
                    break;
            }
        }
        return true;
    }


    /**
     * Actions done at the end of the getEmpty function
     *
     *@return void
     **/
    public function post_getEmpty()
    {

        if (
            Session::haveRight(self::$rightname, self::PUBLISHFAQ)
            && !Session::haveRight("knowbase", UPDATE)
        ) {
            $this->fields["is_faq"] = 1;
        }
    }


    /**
     * @since 0.85
     * @see CommonDBTM::post_addItem()
     **/
    public function post_addItem()
    {

       // add screenshots
        $this->input = $this->addFiles(
            $this->input,
            [
                'force_update'  => true,
                'content_field' => 'answer',
                'name'          => 'answer',
            ]
        );

       // Add documents
        $this->input = $this->addFiles($this->input, ['force_update' => true]);

        if (
            isset($this->input["_visibility"])
            && isset($this->input["_visibility"]['_type'])
            && !empty($this->input["_visibility"]["_type"])
        ) {
            $this->input["_visibility"]['knowbaseitems_id'] = $this->getID();
            $item                                           = null;

            switch ($this->input["_visibility"]['_type']) {
                case 'User':
                    if (
                        isset($this->input["_visibility"]['users_id'])
                        && $this->input["_visibility"]['users_id']
                    ) {
                        $item = new KnowbaseItem_User();
                    }
                    break;

                case 'Group':
                    if (
                        isset($this->input["_visibility"]['groups_id'])
                        && $this->input["_visibility"]['groups_id']
                    ) {
                        $item = new Group_KnowbaseItem();
                    }
                    break;

                case 'Profile':
                    if (
                        isset($this->input["_visibility"]['profiles_id'])
                        && $this->input["_visibility"]['profiles_id']
                    ) {
                        $item = new KnowbaseItem_Profile();
                    }
                    break;

                case 'Entity':
                    $item = new Entity_KnowbaseItem();
                    break;
            }
            if (!is_null($item)) {
                $item->add($this->input["_visibility"]);
                Event::log(
                    $this->getID(),
                    "knowbaseitem",
                    4,
                    "tools",
                    //TRANS: %s is the user login
                    sprintf(__('%s adds a target'), $_SESSION["glpiname"])
                );
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

        // Handle categories
        if (isset($this->input['knowbaseitemcategories_id'])) {
            $kb_cats = is_array($this->input['knowbaseitemcategories_id']) ? $this->input['knowbaseitemcategories_id'] : [$this->input['knowbaseitemcategories_id']];
            foreach ($kb_cats as $kb_cat) {
                $kb_cat_item = new KnowbaseItem_KnowbaseItemCategory();
                $kb_cat_item->add([
                    'knowbaseitems_id' => $this->getID(),
                    'knowbaseitemcategories_id' => $kb_cat,
                ]);
            }
        }
    }


    /**
     * @since 0.83
     **/
    public function post_getFromDB()
    {

       // Users
        $this->users    = KnowbaseItem_User::getUsers($this->fields['id']);

       // Entities
        $this->entities = Entity_KnowbaseItem::getEntities($this->fields['id']);

       // Group / entities
        $this->groups   = Group_KnowbaseItem::getGroups($this->fields['id']);

       // Profile / entities
        $this->profiles = KnowbaseItem_Profile::getProfiles($this->fields['id']);
    }


    /**
     * @see CommonDBTM::cleanDBonPurge()
     *
     * @since 0.83.1
     **/
    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                Entity_KnowbaseItem::class,
                Group_KnowbaseItem::class,
                KnowbaseItem_KnowbaseItemCategory::class,
                KnowbaseItem_Item::class,
                KnowbaseItem_Profile::class,
                KnowbaseItem_User::class,
                KnowbaseItemTranslation::class,
            ]
        );

       /// KnowbaseItem_Comment does not extends CommonDBConnexity
        $kbic = new KnowbaseItem_Comment();
        $kbic->deleteByCriteria(['knowbaseitems_id' => $this->fields['id']]);

       /// KnowbaseItem_Revision does not extends CommonDBConnexity
        $kbir = new KnowbaseItem_Revision();
        $kbir->deleteByCriteria(['knowbaseitems_id' => $this->fields['id']]);
    }

    /**
     * Check is this item if visible to everybody (anonymous users)
     *
     * @since 0.83
     *
     * @return Boolean
     **/
    public function isPubliclyVisible()
    {
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

    public function haveVisibilityAccess()
    {
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
     * @param boolean $forceall  force all joins
     *
     * @return string joins to add
     **/
    public static function addVisibilityJoins($forceall = false)
    {
       //not deprecated because used in self::getListRequest and self::showRecentPopular

        global $DB;

       //get and clean criteria
        $criteria = self::getVisibilityCriteria($forceall);
        unset($criteria['WHERE']);
        $criteria['FROM'] = self::getTable();

        $it = new \DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $sql = $it->getSql();
        $sql = str_replace(
            'SELECT * FROM ' . $DB->quoteName(self::getTable()) . '',
            '',
            $sql
        );
        return $sql;
    }

    /**
     * Return visibility SQL restriction to add
     *
     * @since 0.83
     *
     * @return string restrict to add
     **/
    public static function addVisibilityRestrict()
    {
       //not deprecated because used in self::getListRequest and self::showRecentPopular

        global $DB;

       //get and clean criteria
        $criteria = self::getVisibilityCriteria();
        unset($criteria['LEFT JOIN']);
        $criteria['FROM'] = self::getTable();

        $it = new \DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $sql = $it->getSql();
        $sql = str_replace(
            'SELECT * FROM ' . $DB->quoteName(self::getTable()) . '',
            '',
            $sql
        );
        $sql = preg_replace('/.*WHERE /', '', $sql);

       //No where restrictions. Add a placeholder for compatibility with later restrictions
        if (strlen(trim($sql)) == 0) {
            $sql = "1";
        }
        return $sql;
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
    public static function getVisibilityCriteria(bool $forceall = false): array
    {
        global $CFG_GLPI;

        $is_public_faq_context = !Session::getLoginUserID() && $CFG_GLPI["use_public_faq"];
        $has_session_groups = isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]);
        $has_active_profile = isset($_SESSION["glpiactiveprofile"])
         && isset($_SESSION["glpiactiveprofile"]['id']);
        $has_active_entity = isset($_SESSION["glpiactiveentities"])
         && count($_SESSION["glpiactiveentities"]);

        $where = [];
        $join = [
            'glpi_knowbaseitems_users' => [
                'ON' => [
                    'glpi_knowbaseitems_users' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id'
                ]
            ]
        ];
        if ($forceall || $has_session_groups) {
            $join['glpi_groups_knowbaseitems'] = [
                'ON' => [
                    'glpi_groups_knowbaseitems' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id'
                ]
            ];
        }
        if ($forceall || $has_active_profile) {
            $join['glpi_knowbaseitems_profiles'] = [
                'ON' => [
                    'glpi_knowbaseitems_profiles' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id'
                ]
            ];
        }
        if ($forceall || $has_active_entity || $is_public_faq_context) {
            $join['glpi_entities_knowbaseitems'] = [
                'ON' => [
                    'glpi_entities_knowbaseitems' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id'
                ]
            ];
        }

        if (Session::haveRight(self::$rightname, self::KNOWBASEADMIN)) {
            return [
                'LEFT JOIN' => $join,
                'WHERE' => [],
            ];
        }

       // Users
        if (Session::getLoginUserID()) {
            $where['OR'] = [
                'glpi_knowbaseitems.users_id'       => Session::getLoginUserID(),
                'glpi_knowbaseitems_users.users_id' => Session::getLoginUserID(),
                'glpi_knowbaseitems.is_faq'         => 1,
            ];
        } else if ($is_public_faq_context) {
            $where = [
                "glpi_knowbaseitems.is_faq" => 1,
            ];
            if (Session::isMultiEntitiesMode()) {
                $where += [
                    "glpi_entities_knowbaseitems.entities_id" => 0,
                    "glpi_entities_knowbaseitems.is_recursive" => 1,
                ];
            }
        } else {
            $where = [
                0
            ];
        }
       // Groups
        if ($forceall || $has_session_groups) {
            if (Session::getLoginUserID()) {
                $restrict = getEntitiesRestrictCriteria('glpi_groups_knowbaseitems', '', '', true, true);
                $where['OR'][] = [
                    'glpi_groups_knowbaseitems.groups_id' => count($_SESSION["glpigroups"])
                                                         ? $_SESSION["glpigroups"]
                                                         : [-1],
                    'OR' => [
                        'glpi_groups_knowbaseitems.no_entity_restriction' => 1,
                    ] + $restrict
                ];
            }
        }

       // Profiles
        if ($forceall || $has_active_profile) {
            if (Session::getLoginUserID()) {
                $where['OR'][] = [
                    'glpi_knowbaseitems_profiles.profiles_id' => $_SESSION["glpiactiveprofile"]['id'],
                    'OR' => [
                        'glpi_knowbaseitems_profiles.no_entity_restriction' => 1,
                        getEntitiesRestrictCriteria('glpi_knowbaseitems_profiles', '', '', true, true)
                    ]
                ];
            }
        }

       // Entities
        if ($forceall || $has_active_entity) {
            if (Session::getLoginUserID()) {
                $restrict = getEntitiesRestrictCriteria('glpi_entities_knowbaseitems', '', '', true, true);
                if (count($restrict)) {
                    $where['OR'] = $where['OR'] + $restrict;
                } else {
                    $where['glpi_entities_knowbaseitems.entities_id'] = null;
                }
            }
        }

        $criteria = ['LEFT JOIN' => $join];
        if (count($where)) {
            $criteria['WHERE'] = $where;
        }

        return $criteria;
    }

    public function prepareInputForAdd($input)
    {

       // set title for question if empty
        if (isset($input["name"]) && empty($input["name"])) {
            $input["name"] = __('New item');
        }

        if (
            Session::haveRight(self::$rightname, self::PUBLISHFAQ)
            && !Session::haveRight(self::$rightname, UPDATE)
        ) {
            $input["is_faq"] = 1;
        }
        if (
            !Session::haveRight(self::$rightname, self::PUBLISHFAQ)
            && Session::haveRight(self::$rightname, UPDATE)
        ) {
            $input["is_faq"] = 0;
        }
        return $input;
    }


    public function prepareInputForUpdate($input)
    {
       // set title for question if empty
        if (isset($input["name"]) && empty($input["name"])) {
            $input["name"] = __('New item');
        }
        return $input;
    }

    public function post_updateItem($history = 1)
    {
       // Update screenshots
        $this->input = $this->addFiles(
            $this->input,
            [
                'force_update'  => true,
                'content_field' => 'answer',
                'name'          => 'answer',
            ]
        );

       // add uploaded documents
        $this->input = $this->addFiles(
            $this->input,
            [
                'force_update'  => true,
            ]
        );
    }


    /**
     * Print out an HTML "<form>" for knowbase item
     *
     * @param $ID
     * @param $options array
     *     - target for the Form
     *
     * @return void
     **/
    public function showForm($ID, array $options = [])
    {
        global $CFG_GLPI;

       // show kb item form
        if (
            !Session::haveRightsOr(
                self::$rightname,
                [UPDATE, self::PUBLISHFAQ, self::KNOWBASEADMIN]
            )
        ) {
            return false;
        }

        $canedit = $this->can($ID, UPDATE);

        $item = null;
       // Load ticket solution
        if (
            empty($ID)
            && isset($options['item_itemtype']) && !empty($options['item_itemtype'])
            && isset($options['item_items_id']) && !empty($options['item_items_id'])
        ) {
            if ($item = getItemForItemtype($options['item_itemtype'])) {
                if ($item->getFromDB($options['item_items_id'])) {
                    $this->fields['name']   = $item->getField('name');
                    if (isset($options['_fup_to_kb'])) {
                        $fup = new ITILFollowup();
                        $fup->getFromDBByCrit([
                            'id'           => $options['_fup_to_kb'],
                            'itemtype'     => $item->getType(),
                            'items_id'     => $item->getID()
                        ]);
                        $this->fields['answer'] = $fup->getField('content');
                    } else if (isset($options['_task_to_kb'])) {
                        $tasktype = $item->getType() . 'Task';
                        $task = new $tasktype();
                        $task->getFromDB($options['_task_to_kb']);
                        $this->fields['answer'] = $task->getField('content');
                    } else if (isset($options['_sol_to_kb'])) {
                        $solution = new ITILSolution();
                        $solution->getFromDBByCrit([
                            'itemtype'     => $item->getType(),
                            'items_id'     => $item->getID(),
                            [
                                'NOT' => ['status'       => CommonITILValidation::REFUSED]
                            ]
                        ]);
                        $this->fields['answer'] = $solution->getField('content');
                    }
                    if ($item->isField('itilcategories_id')) {
                          $ic = new ITILCategory();
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
        $options['formoptions'] = "data-track-changes=true";
        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        if ($this->isNewItem()) {
            echo "<td>" . KnowbaseItemCategory::getTypeName(Session::getPluralNumber()) . "</td>";
            echo "<td>";
            KnowbaseItemCategory::dropdown([
                'value' => [],
                'multiple' => true,
            ]);
            echo "</td>";
        } else {
            echo "<td colspan=2></td>";
        }
        echo "<td>";
        echo "<input type='hidden' name='users_id' value=\"" . Session::getLoginUserID() . "\">";
        if ($this->fields["date_creation"]) {
           //TRANS: %s is the datetime of insertion
            printf(__('Created on %s'), Html::convDateTime($this->fields["date_creation"]));
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
            echo "<td>" . __('Put this item in the FAQ') . "</td>";
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
            printf(__('%1$s: %2$s'), __('Writer'), getUserName(
                $this->fields["users_id"],
                $showuserlink
            ));
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
                    echo "<td>" . __('Add link') . "</td>";
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
        echo "<td>" . __('Visible since') . "</td><td>";
        Html::showDateTimeField("begin_date", ['value'       => $this->fields["begin_date"],
            'maybeempty' => true,
            'canedit'    => $canedit
        ]);
        echo "</td>";
        echo "<td>" . __('Visible until') . "</td><td>";
        Html::showDateTimeField("end_date", ['value'       => $this->fields["end_date"],
            'maybeempty' => true,
            'canedit'    => $canedit
        ]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Subject') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea class='form-control' name='name'>" . $this->fields["name"] . "</textarea>";
        echo "</td>";
        echo "</tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Content') . "</td>";
        echo "<td colspan='3'>";

        $cols = 100;
        $rows = 30;
        if (isset($options['_in_modal']) && $options['_in_modal']) {
            $rows = 15;
            echo Html::hidden('_in_modal', ['value' => 1]);
        }
        Html::textarea(['name'              => 'answer',
            'value'             => RichText::getSafeHtml($this->fields['answer'], true),
            'enable_fileupload' => true,
            'enable_richtext'   => true,
            'cols'              => $cols,
            'rows'              => $rows
        ]);
        echo "</td>";
        echo "</tr>";

        if ($this->isNewID($ID)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . _n('Target', 'Targets', 1) . "</td>";
            echo "<td>";
            $types   = ['Entity', 'Group', 'Profile', 'User'];
            $addrand = Dropdown::showItemTypes('_visibility[_type]', $types);
            echo "</td><td colspan='2'>";
            $params  = ['type'     => '__VALUE__',
                'right'    => 'knowbase',
                'prefix'   => '_visibility',
                'nobutton' => 1
            ];

            Ajax::updateItemOnSelectEvent(
                "dropdown__visibility__type_" . $addrand,
                "visibility$rand",
                $CFG_GLPI["root_doc"] . "/ajax/visibility.php",
                $params
            );
            echo "<span id='visibility$rand'></span>";
            echo "</td></tr>\n";
        }

        $this->showFormButtons($options);
        return true;
    }


    /**
     * Add kb item to the public FAQ
     *
     * @return void
     **/
    public function addToFaq()
    {
        global $DB;

        $DB->update(
            $this->getTable(),
            [
                'is_faq' => 1
            ],
            [
                'id' => $this->fields['id']
            ]
        );
    }

    /**
     * Increase the view counter of the current knowbaseitem
     *
     * @since 0.83
     */
    public function updateCounter()
    {
        global $DB;

       //update counter view
        $DB->update(
            'glpi_knowbaseitems',
            [
                'view'   => new \QueryExpression($DB->quoteName('view') . ' + 1')
            ],
            [
                'id' => $this->getID()
            ]
        );
    }


    /**
     * Print out (html) show item : question and answer
     *
     * @param $options      array of options
     *
     * @return void|string
     *    void if option display=true
     *    string if option display=false (HTML code)
     **/
    public function showFull($options = [])
    {
        global $CFG_GLPI, $DB;

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
        if (
            ((Session::getLoginUserID() === false) && $CFG_GLPI["use_public_faq"])
            || (Session::getCurrentInterface() == "helpdesk")
            || !User::canView()
        ) {
            $linkusers_id = false;
        }

        $this->updateCounter();

        $tmp = [];
        $categories = KnowbaseItem_KnowbaseItemCategory::getItems($this);
        foreach ($categories as $category) {
            $knowbaseitemcategories_id = $category['knowbaseitemcategories_id'];
            $fullcategoryname          = getTreeValueCompleteName(
                "glpi_knowbaseitemcategories",
                $knowbaseitemcategories_id
            );

            $tmp[] = "<a href='" . $this->getSearchURL() .
             "?knowbaseitemcategories_id=$knowbaseitemcategories_id&forcetab=Knowbase$2'>" .
             $fullcategoryname . "</a>";
        }
        $tmp = implode(', ', $tmp);
        $out .= "<table class='tab_cadre_fixe'>";
        $out .= "<tr><th colspan='4'>" . sprintf(__('%1$s: %2$s'), _n('Category', 'Categories', 1), $tmp);
        $out .= "</th></tr>";

        $out .= "<tr><td class='left' colspan='4'><h2>" . __('Subject') . "</h2>";
        if (KnowbaseItemTranslation::canBeTranslated($this)) {
            $out .= KnowbaseItemTranslation::getTranslatedValue($this, 'name');
        } else {
            $out .= $this->fields["name"];
        }

        $out .= "</td></tr>";
        $out .= "<tr><td class='left' colspan='4'><h2>" . __('Content') . "</h2>\n";

        $out .= "<div class='rich_text_container' id='kbanswer'>";
        $out .= $this->getAnswer();
        $out .= "</div>";
        $out .= "</td></tr>";

       // Show documents attached to the FAQ Item
        $sort = 'filename';
        $order = 'ASC';
        $criteria = Document_Item::getDocumentForItemRequest($this, ["$sort $order"]);
        $criteria['WHERE'][] = ['is_deleted' => '0'];
        $iterator = $DB->request($criteria);
        if (count($iterator) > 0) {
            $out .= "<tr><td class='left' colspan='4'><h2>" . Document::getTypeName(Session::getPluralNumber()) . "</h2></td></tr>\n";

            $columns = [
                'filename'  => __('File'),
                'headings'  => __('Heading'),
                'assocdate' => _n('Date', 'Dates', 1),
            ];

            $header_begin  = "<tr>";
            $header_top    = '';
            $header_end    = '';

            foreach ($columns as $key => $val) {
                $colspan = $key == 'filename' ? 'colspan="2"' : '';
                $header_end .= "<th $colspan" . ($sort == "$key" ? " class='order_$order'" : '') . ">" .
                           "<a href='javascript:reloadTab(\"sort=$key&amp;order=" .
                              (($order == "ASC") ? "DESC" : "ASC") . "&amp;start=0\");'>$val</a></th>";
            }
            $header_end .= "</tr>";
            $out .= $header_begin . $header_top . $header_end;

            $document = new Document();
            foreach ($iterator as $data) {
                $docID        = $data["id"];
                $downloadlink = NOT_AVAILABLE;

                if ($document->getFromDB($docID)) {
                    $downloadlink = $document->getDownloadLink();
                }

                $used[$docID] = $docID;

                $out .= "<tr class='tab_bg_1" . ($data["is_deleted"] ? "_2" : "") . "'>";
                $out .= "<td colspan='2'>$downloadlink</td>";
                $out .= "<td>" . Dropdown::getDropdownName(
                    "glpi_documentcategories",
                    $data["documentcategories_id"]
                );
                $out .= "</td>";
                $out .= "<td>" . Html::convDateTime($data["assocdate"]) . "</td>";
                $out .= "</tr>";
            }
        }

        $out .= "<tr><th class='tdkb'  colspan='2'>";
        if ($this->fields["users_id"]) {
           // Integer because true may be 2 and getUserName return array
            if ($linkusers_id) {
                $linkusers_id = 1;
            } else {
                $linkusers_id = 0;
            }

            $out .= sprintf(__('%1$s: %2$s'), __('Writer'), getUserName(
                $this->fields["users_id"],
                $linkusers_id
            ));
            $out .= "<br>";
        }

        if ($this->fields["date_creation"]) {
           //TRANS: %s is the datetime of update
            $out .= sprintf(__('Created on %s'), Html::convDateTime($this->fields["date_creation"]));
            $out .= "<br>";
        }
        if ($this->fields["date_mod"]) {
           //TRANS: %s is the datetime of update
            $out .= sprintf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
        }

        $out .= "</th>";
        $out .= "<th class='tdkb' colspan='2'>";
        if ($this->countVisibilities() == 0) {
            $out .= "<span class='red'>" . __('Unpublished') . "</span><br>";
        }

        $out .= sprintf(_n('%d view', '%d views', $this->fields["view"]), $this->fields["view"]);
        $out .= "<br>";
        if ($this->fields["is_faq"]) {
            $out .= __('This item is part of the FAQ');
        } else {
            $out .= __('This item is not part of the FAQ');
        }
        $out .= "</th></tr>";
        $out .= "</table>";

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
     * @return void
     **/
    public function searchForm($options)
    {
        global $CFG_GLPI;

        if (
            !$CFG_GLPI["use_public_faq"]
            && !Session::haveRightsOr(self::$rightname, [READ, self::READFAQ])
        ) {
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

        echo "<form method='get' action='" . $this->getSearchURL() . "' class='d-flex justify-content-center'>";
        echo "<input class='form-control me-1' type='text' size='50' name='contains' value=\"" .
             Html::cleanInputText(stripslashes($params["contains"])) . "\">";
        echo "<input type='submit' value=\"" . _sx('button', 'Search') . "\" class='btn btn-primary'>";
        echo "</table>";
        if (
            isset($options['item_itemtype'])
            && isset($options['item_items_id'])
        ) {
            echo "<input type='hidden' name='item_itemtype' value='" . $options['item_itemtype'] . "'>";
            echo "<input type='hidden' name='item_items_id' value='" . $options['item_items_id'] . "'>";
        }
        Html::closeForm();
    }


    /**
     * Print out an HTML "<form>" for Search knowbase item
     *
     * @since 0.84
     *
     * @param $options   $_GET
     *
     * @return void
     **/
    public function showBrowseForm($options)
    {
        global $CFG_GLPI;

        if (
            !$CFG_GLPI["use_public_faq"]
            && !Session::haveRightsOr(self::$rightname, [READ, self::READFAQ])
        ) {
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
        if (
            Session::getLoginUserID()
            && !$faq
        ) {
            echo "<div>";
            echo "<form method='get' action='" . $this->getSearchURL() . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><td class='right' width='50%'>" . _n('Category', 'Categories', 1) . "&nbsp;";
            KnowbaseItemCategory::dropdown(['value' => $params["knowbaseitemcategories_id"]]);
            echo "</td><td class='left'>";
            echo "<input type='submit' value=\"" . _sx('button', 'Post') . "\" class='btn btn-primary'></td>";
            echo "</tr></table>";
            if (
                isset($options['item_itemtype'])
                && isset($options['item_items_id'])
            ) {
                echo "<input type='hidden' name='item_itemtype' value='" . $options['item_itemtype'] . "'>";
                echo "<input type='hidden' name='item_items_id' value='" . $options['item_items_id'] . "'>";
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
     * @return void
     **/
    public function showManageForm($options)
    {
        if (
            !Session::haveRightsOr(
                self::$rightname,
                [UPDATE, self::PUBLISHFAQ, self::KNOWBASEADMIN]
            )
        ) {
            return false;
        }
        $params['unpublished'] = 'my';
        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        echo "<div>";
        echo "<form method='get' action='" . $this->getSearchURL() . "'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'><td class='right' width='50%'>";
        $values = ['myunpublished' => __('My unpublished articles'),
            'allmy'         => __('All my articles')
        ];
        if (Session::haveRight(self::$rightname, self::KNOWBASEADMIN)) {
            $values['allunpublished'] = __('All unpublished articles');
            $values['allpublished'] = __('All published articles');
        }
        Dropdown::showFromArray('unpublished', $values, ['value' => $params['unpublished']]);
        echo "</td><td class='left'>";
        echo "<input type='submit' value=\"" . _sx('button', 'Post') . "\" class='btn btn-primary'></td>";
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
     * @return array : SQL request
     **/
    public static function getListRequest(array $params, $type = 'search')
    {
        global $DB;

        $criteria = [
            'SELECT' => [
                'glpi_knowbaseitems.*',
                'glpi_knowbaseitems_knowbaseitemcategories.knowbaseitemcategories_id',
                new QueryExpression(
                    'GROUP_CONCAT(DISTINCT ' . $DB->quoteName('glpi_knowbaseitemcategories.completename') . ') AS category'
                ),
                new QueryExpression(
                    'COUNT(' . $DB->quoteName('glpi_knowbaseitems_users.id') . ')' .
                    ' + COUNT(' . $DB->quoteName('glpi_groups_knowbaseitems.id') . ')' .
                    ' + COUNT(' . $DB->quoteName('glpi_knowbaseitems_profiles.id') . ')' .
                    ' + COUNT(' . $DB->quoteName('glpi_entities_knowbaseitems.id') . ') AS ' .
                    $DB->quoteName('visibility_count')
                )
            ],
            'FROM'   => 'glpi_knowbaseitems',
            'WHERE'     => [], //to be filled
            'LEFT JOIN' => [], //to be filled
            'GROUPBY'   => ['glpi_knowbaseitems.id']
        ];

       // Lists kb Items
        $restrict = self::getVisibilityCriteria(true);
        $restrict_where = $restrict['WHERE'];
        unset($restrict['WHERE']);
        unset($restrict['SELECT']);
        $criteria = array_merge_recursive($criteria, $restrict);

        switch ($type) {
            case 'myunpublished':
            case 'allmy':
            case 'allunpublished':
                break;

            default:
               // Build query
                if (Session::getLoginUserID()) {
                    $criteria['WHERE'] = array_merge(
                        $criteria['WHERE'],
                        $restrict_where
                    );
                } else {
                   // Anonymous access
                    if (Session::isMultiEntitiesMode()) {
                        $criteria['WHERE']['glpi_entities_knowbaseitems.entities_id'] = 0;
                        $criteria['WHERE']['glpi_entities_knowbaseitems.is_recursive'] = 1;
                    }
                }
                break;
        }

        if ($params['faq']) { // helpdesk
            $criteria['WHERE'][] = [
                'OR' => [
                    'glpi_knowbaseitems.is_faq' => 1,
                    'glpi_knowbaseitems_users.users_id' => Session::getLoginUserID(),
                ]
            ];
        }

        if ($params['knowbaseitemcategories_id'] > 0) {
            $criteria['LEFT JOIN'][KnowbaseItem_KnowbaseItemCategory::getTable()] = [
                'FKEY' => [
                    KnowbaseItem_KnowbaseItemCategory::getTable() => KnowbaseItem::getForeignKeyField(),
                    KnowbaseItem::getTable() => 'id',
                ],
            ];
            $criteria['WHERE'][KnowbaseItem_KnowbaseItemCategory::getTableField('knowbaseitemcategories_id')] = $params['knowbaseitemcategories_id'];
        }

        if (
            KnowbaseItemTranslation::isKbTranslationActive()
            && (countElementsInTable('glpi_knowbaseitemtranslations') > 0)
        ) {
            $criteria['LEFT JOIN']['glpi_knowbaseitemtranslations'] = [
                'ON'  => [
                    'glpi_knowbaseitems'             => 'id',
                    'glpi_knowbaseitemtranslations'  => 'knowbaseitems_id', [
                        'AND'                            => [
                            'glpi_knowbaseitemtranslations.language' => $_SESSION['glpilanguage']
                        ]
                    ]
                ]
            ];
            $criteria['SELECT'][] = 'glpi_knowbaseitemtranslations.name AS transname';
            $criteria['SELECT'][] = 'glpi_knowbaseitemtranslations.answer AS transanswer';
        }

       // a search with $contains
        switch ($type) {
            case 'allmy':
                $criteria['WHERE']['glpi_knowbaseitems.users_id'] = Session::getLoginUserID();
                break;

            case 'myunpublished':
                $criteria['WHERE']['glpi_knowbaseitems.users_id'] = Session::getLoginUserID();
                $criteria['WHERE']['glpi_entities_knowbaseitems.entities_id'] = null;
                $criteria['WHERE']['glpi_knowbaseitems_profiles.profiles_id'] = null;
                $criteria['WHERE']['glpi_groups_knowbaseitems.groups_id'] = null;
                $criteria['WHERE']['glpi_knowbaseitems_users.users_id'] = null;
                break;

            case 'allunpublished':
               // Only published
                $criteria['WHERE']['glpi_entities_knowbaseitems.entities_id'] = null;
                $criteria['WHERE']['glpi_knowbaseitems_profiles.profiles_id'] = null;
                $criteria['WHERE']['glpi_groups_knowbaseitems.groups_id'] = null;
                $criteria['WHERE']['glpi_knowbaseitems_users.users_id'] = null;
                break;

            case 'search':
                if (strlen($params["contains"]) > 0) {
                    $search  = Sanitizer::unsanitize($params["contains"]);

                   // Replace all non word characters with spaces (see: https://stackoverflow.com/a/26537463)
                    $search_wilcard = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $search);

                   // Remove last space to avoid illegal syntax with " *"
                    $search_wilcard = trim($search_wilcard);

                   // Merge spaces since we are using them to split the string later
                    $search_wilcard = preg_replace('!\s+!', ' ', $search_wilcard);

                    $search_wilcard = explode(' ', $search_wilcard);
                    $search_wilcard = implode('* ', $search_wilcard) . '*';

                    $addscore = [];
                    if (
                        KnowbaseItemTranslation::isKbTranslationActive()
                        && (countElementsInTable('glpi_knowbaseitemtranslations') > 0)
                    ) {
                        $addscore = [
                            'glpi_knowbaseitemtranslations.name',
                            'glpi_knowbaseitemtranslations.answer'
                        ];
                    }

                    $expr = "(MATCH(" . $DB->quoteName('glpi_knowbaseitems.name') . ", " . $DB->quoteName('glpi_knowbaseitems.answer') . ")
                           AGAINST(" . $DB->quote($search_wilcard) . " IN BOOLEAN MODE)";

                    if (!empty($addscore)) {
                        foreach ($addscore as $addscore_field) {
                            $expr .= " + MATCH(" . $DB->quoteName($addscore_field) . ")
                                        AGAINST(" . $DB->quote($search_wilcard) . " IN BOOLEAN MODE)";
                        }
                    }
                    $expr .= " ) AS SCORE ";
                    $criteria['SELECT'][] = new QueryExpression($expr);

                    $ors = [
                        new QueryExpression(
                            "MATCH(" . $DB->quoteName('glpi_knowbaseitems.name') . ",
                        " . $DB->quoteName('glpi_knowbaseitems.answer') . ")
                        AGAINST(" . $DB->quote($search_wilcard) . " IN BOOLEAN MODE)"
                        )
                    ];

                    if (!empty($addscore)) {
                        foreach ($addscore as $addscore_field) {
                            $ors[] = [
                                'NOT' => [$addscore_field => null],
                                new QueryExpression(
                                    "MATCH(" . $DB->quoteName($addscore_field) . ")
                              AGAINST(" . $DB->quote($search_wilcard) . " IN BOOLEAN MODE)"
                                )
                            ];
                        }
                    }

                    $search_where =  $criteria['WHERE']; // Visibility restrict criteria

                    $search_where[] = ['OR' => $ors];

                   // Add visibility date
                    $visibility_crit = [
                        [
                            'OR'  => [
                                ['glpi_knowbaseitems.begin_date'  => null],
                                ['glpi_knowbaseitems.begin_date'  => ['<', new QueryExpression('NOW()')]]
                            ]
                        ], [
                            'OR'  => [
                                ['glpi_knowbaseitems.end_date'    => null],
                                ['glpi_knowbaseitems.end_date'    => ['>', new QueryExpression('NOW()')]]
                            ]
                        ]
                    ];
                    $search_where[] = $visibility_crit;

                    $criteria['ORDERBY'] = ['SCORE DESC'];

                   // preliminar query to allow alternate search if no result with fulltext
                    $search_criteria = [
                        'COUNT'     => 'cpt',
                        'LEFT JOIN' => $criteria['LEFT JOIN'],
                        'FROM'      => 'glpi_knowbaseitems',
                        'WHERE'     => $search_where
                    ];
                    $search_iterator = $DB->request($search_criteria);
                    $numrows_search = $search_iterator->current()['cpt'];

                    if ($numrows_search <= 0) {// not result this fulltext try with alternate search
                        $search1 = [/* 1 */   '/\\\"/',
                            /* 2 */   "/\+/",
                            /* 3 */   "/\*/",
                            /* 4 */   "/~/",
                            /* 5 */   "/</",
                            /* 6 */   "/>/",
                            /* 7 */   "/\(/",
                            /* 8 */   "/\)/",
                            /* 9 */   "/\-/"
                        ];
                        $contains = preg_replace($search1, "", $params["contains"]);
                        $ors = [
                            ["glpi_knowbaseitems.name"     => ['LIKE', Search::makeTextSearchValue($contains)]],
                            ["glpi_knowbaseitems.answer"   => ['LIKE', Search::makeTextSearchValue($contains)]]
                        ];
                        if (
                            KnowbaseItemTranslation::isKbTranslationActive()
                            && (countElementsInTable('glpi_knowbaseitemtranslations') > 0)
                        ) {
                            $ors[] = ["glpi_knowbaseitemtranslations.name"   => ['LIKE', Search::makeTextSearchValue($contains)]];
                            $ors[] = ["glpi_knowbaseitemtranslations.answer" => ['LIKE', Search::makeTextSearchValue($contains)]];
                        }
                        $criteria['WHERE'][] = ['OR' => $ors];
                       // Add visibility date
                        $criteria['WHERE'][] = $visibility_crit;
                    } else {
                        $criteria['WHERE'] = $search_where;
                    }
                }
                break;

            case 'browse':
                if (!Session::haveRight(self::$rightname, self::KNOWBASEADMIN)) {
                   // Add visibility date
                    $criteria['WHERE'][] = [
                        'OR'  => [
                            ['glpi_knowbaseitems.begin_date' => null],
                            ['glpi_knowbaseitems.begin_date' => ['<', new QueryExpression('NOW()')]]
                        ]
                    ];
                    $criteria['WHERE'][] = [
                        'OR'  => [
                            ['glpi_knowbaseitems.end_date' => null],
                            ['glpi_knowbaseitems.end_date' => ['>', new QueryExpression('NOW()')]]
                        ]
                    ];
                }

                $criteria['ORDERBY'] = ['glpi_knowbaseitems.name ASC'];
                break;
        }

        $criteria['LEFT JOIN']['glpi_knowbaseitems_knowbaseitemcategories'] = [
            'ON'  => [
                'glpi_knowbaseitems_knowbaseitemcategories'  => 'knowbaseitems_id',
                'glpi_knowbaseitems'             => 'id'
            ]
        ];

        $criteria['LEFT JOIN']['glpi_knowbaseitemcategories'] = [
            'ON'  => [
                'glpi_knowbaseitemcategories'    => 'id',
                'glpi_knowbaseitems_knowbaseitemcategories'  => 'knowbaseitemcategories_id'
            ]
        ];

        return $criteria;
    }


    /**
     * Print out list kb item
     *
     * @param $options            $_GET
     * @param $type      string   search type : browse / search (default search)
     **/
    public static function showList($options, $type = 'search')
    {
        global $CFG_GLPI;

        $DBread = DBConnection::getReadConnection();

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
            case 'myunpublished':
                if (!Session::haveRightsOr(self::$rightname, [UPDATE, self::PUBLISHFAQ])) {
                    return false;
                }
                break;

            case 'allunpublished':
                if (!Session::haveRight(self::$rightname, self::KNOWBASEADMIN)) {
                    return false;
                }
                break;

            default:
                break;
        }

        if (!$params["start"]) {
            $params["start"] = 0;
        }

        $criteria = self::getListRequest($params, $type);

        $main_iterator = $DBread->request($criteria);
        $rows = count($main_iterator);
        $numrows = $rows;

       // Get it from database
        $KbCategory = new KnowbaseItemCategory();
        $title      = "";
        if ($KbCategory->getFromDB($params["knowbaseitemcategories_id"])) {
            $title = (empty($KbCategory->fields['name']) ? "(" . $params['knowbaseitemcategories_id'] . ")"
                                                      : $KbCategory->fields['name']);
            $title = sprintf(__('%1$s: %2$s'), _n('Category', 'Categories', 1), $title);
        }

        Session::initNavigateListItems('KnowbaseItem', $title);
       // force using getSearchUrl on list icon (when viewing a single article)
        $_SESSION['glpilisturl']['KnowbaseItem'] = '';

        $list_limit = $_SESSION['glpilist_limit'];

        $showwriter = in_array($type, ['myunpublished', 'allunpublished', 'allmy']);

       // Limit the result, if no limit applies, use prior result
        if (
            ($rows > $list_limit)
            && !isset($_GET['export_all'])
        ) {
            $criteria['START'] = (int)$params['start'];
            $criteria['LIMIT'] = (int)$list_limit;
            $main_iterator = $DBread->request($criteria);
            $numrows = count($main_iterator);
        }

        if ($numrows > 0) {
           // Set display type for export if define
            $output_type = Search::HTML_OUTPUT;

            if (isset($_GET["display_type"])) {
                $output_type = $_GET["display_type"];
            }

           // Pager
            $parameters = "start=" . $params["start"] . "&amp;knowbaseitemcategories_id=" .
                        $params['knowbaseitemcategories_id'] . "&amp;contains=" .
                        $params["contains"] . "&amp;is_faq=" . $params['faq'];

            if (
                isset($options['item_itemtype'])
                && isset($options['item_items_id'])
            ) {
                $parameters .= "&amp;item_items_id=" . $options['item_items_id'] . "&amp;item_itemtype=" .
                              $options['item_itemtype'];
            }

            $pager_url = "";
            if ($output_type == Search::HTML_OUTPUT) {
                $pager_url = Toolbox::getItemTypeSearchURL('KnowbaseItem');
                if (!Session::getLoginUserID()) {
                    $pager_url = $CFG_GLPI['root_doc'] . "/front/helpdesk.faq.php";
                }
                Html::printPager($params['start'], $rows, $pager_url, $parameters, 'KnowbaseItem');
            }

            $nbcols = 1;
           // Display List Header
            echo Search::showHeader($output_type, $numrows + 1, $nbcols);

            echo Search::showNewLine($output_type);
            $header_num = 1;
            echo Search::showHeaderItem($output_type, __('Subject'), $header_num);

            if ($output_type != Search::HTML_OUTPUT) {
                echo Search::showHeaderItem($output_type, __('Content'), $header_num);
            }

            if ($showwriter) {
                echo Search::showHeaderItem($output_type, __('Writer'), $header_num);
            }
            echo Search::showHeaderItem($output_type, _n('Category', 'Categories', 1), $header_num);

            if ($output_type == Search::HTML_OUTPUT) {
                echo Search::showHeaderItem($output_type, _n('Associated element', 'Associated elements', Session::getPluralNumber()), $header_num);
            }

            if (
                isset($options['item_itemtype'])
                && isset($options['item_items_id'])
                && ($output_type == Search::HTML_OUTPUT)
            ) {
                echo Search::showHeaderItem($output_type, '&nbsp;', $header_num);
            }

           // Num of the row (1=header_line)
            $row_num = 1;
            foreach ($main_iterator as $data) {
                Session::addToNavigateListItems('KnowbaseItem', $data["id"]);
               // Column num
                $item_num = 1;
                echo Search::showNewLine($output_type, ($row_num - 1) % 2);
                $row_num++;

                $item = new self();
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
                    if (
                        isset($options['item_itemtype'])
                        && isset($options['item_items_id'])
                    ) {
                        $href  = " href='#' data-bs-toggle='modal' data-bs-target='#kbshow{$data["id"]}'";
                        $toadd = Ajax::createIframeModalWindow(
                            'kbshow' . $data["id"],
                            KnowbaseItem::getFormURLWithID($data["id"]),
                            ['display' => false]
                        );
                    } else {
                        $href = " href=\"" . KnowbaseItem::getFormURLWithID($data["id"]) . "\" ";
                    }

                    $fa_class = "";
                    $fa_title = "";
                    if (
                        $data['is_faq']
                        && (!Session::isMultiEntitiesMode()
                        || isset($data['visibility_count'])
                           && $data['visibility_count'] > 0)
                    ) {
                        $fa_class = "fa-question-circle faq";
                        $fa_title = __s("This item is part of the FAQ");
                    } else if (
                        isset($data['visibility_count'])
                        && $data['visibility_count'] <= 0
                    ) {
                        $fa_class = "fa-eye-slash not-published";
                        $fa_title = __s("This item is not published yet");
                    }
                    echo Search::showItem(
                        $output_type,
                        "<div class='kb'>$toadd <i class='fa fa-fw $fa_class' title='$fa_title'></i> <a $href>" . Html::resume_text($name, 80) . "</a></div>
                                       <div class='kb_resume'>" . Html::resume_text(RichText::getTextFromHtml($answer, false, false, true), 600) . "</div>",
                        $item_num,
                        $row_num
                    );
                } else {
                    echo Search::showItem($output_type, $name, $item_num, $row_num);
                    echo Search::showItem($output_type, RichText::getTextFromHtml($answer, true, false, true), $item_num, $row_num);
                }

                $showuserlink = 0;
                if (Session::haveRight('user', READ)) {
                    $showuserlink = 1;
                }
                if ($showwriter) {
                    echo Search::showItem(
                        $output_type,
                        getUserName($data["users_id"], $showuserlink),
                        $item_num,
                        $row_num
                    );
                }

                $categ = $data["category"];
                $inst = new KnowbaseItemCategory();
                if (DropdownTranslation::canBeTranslated($inst)) {
                    $tcateg = DropdownTranslation::getTranslatedValue(
                        $data["knowbaseitemcategories_id"],
                        $inst->getType()
                    );
                    if (!empty($tcateg)) {
                          $categ = $tcateg;
                    }
                }

                if ($output_type == Search::HTML_OUTPUT) {
                    $tmp = [];
                    $ki->getFromDB($data["id"]);
                    $categories = KnowbaseItem_KnowbaseItemCategory::getItems($ki);
                    foreach ($categories as $category) {
                        $knowbaseitemcategories_id = $category['knowbaseitemcategories_id'];
                        $fullcategoryname          = getTreeValueCompleteName(
                            "glpi_knowbaseitemcategories",
                            $knowbaseitemcategories_id
                        );
                        $cathref = $ki->getSearchURL() . "?knowbaseitemcategories_id=" .
                              $knowbaseitemcategories_id . '&amp;forcetab=Knowbase$2';
                        $tmp[] = "<a class='kb-category'"
                         . " href='$cathref'"
                         . " data-category-id='" . $knowbaseitemcategories_id . "'"
                         . ">" . $fullcategoryname . '</a>';
                    }
                    $categ = implode(', ', $tmp);
                }
                echo Search::showItem($output_type, $categ, $item_num, $row_num);

                if ($output_type == Search::HTML_OUTPUT) {
                    echo "<td class='center'>";
                    $j = 0;
                    $iterator = $DBread->request([
                        'FIELDS' => 'documents_id',
                        'FROM'   => 'glpi_documents_items',
                        'WHERE'  => [
                            'items_id'  => $data["id"],
                            'itemtype'  => 'KnowbaseItem'
                        ] + getEntitiesRestrictCriteria('', '', '', true)
                    ]);
                    foreach ($iterator as $docs) {
                          $doc = new Document();
                          $doc->getFromDB($docs["documents_id"]);
                          echo $doc->getDownloadLink();
                          $j++;
                        if ($j > 1) {
                            echo "<br>";
                        }
                    }
                    echo "</td>";
                }

                if (
                    isset($options['item_itemtype'])
                    && isset($options['item_items_id'])
                    && ($output_type == Search::HTML_OUTPUT)
                ) {
                    $forcetab = $options['item_itemtype'] . '$1';
                    $item_itemtype = $options['item_itemtype'];
                    $content = "<a href='" . $item_itemtype::getFormURLWithID($options['item_items_id']) .
                              "&amp;load_kb_sol=" . $data['id'] .
                              "&amp;forcetab=" . $forcetab . "'>" .
                              __('Use as a solution') . "</a>";
                    echo Search::showItem($output_type, $content, $item_num, $row_num);
                }

               // End Line
                echo Search::showEndLine($output_type);
            }

           // Display footer
            if (
                ($output_type == Search::PDF_OUTPUT_LANDSCAPE)
                || ($output_type == Search::PDF_OUTPUT_PORTRAIT)
            ) {
                echo Search::showFooter(
                    $output_type,
                    Dropdown::getDropdownName(
                        "glpi_knowbaseitemcategories",
                        $params['knowbaseitemcategories_id']
                    ),
                    $numrows
                );
            } else {
                echo Search::showFooter($output_type, '', $numrows);
            }
            echo "<br>";
            if ($output_type == Search::HTML_OUTPUT) {
                Html::printPager($params['start'], $rows, $pager_url, $parameters, 'KnowbaseItem');
            }
        } else {
            echo "<div class='center b'>" . __('No item found') . "</div>";
        }
    }


    /**
     * Print out list recent or popular kb/faq
     *
     * @param string $type    type : recent / popular / not published
     * @param bool   $display if false, return html
     *
     * @return void
     **/
    public static function showRecentPopular(string $type = "", bool $display = true)
    {
        global $DB;

        $faq = !Session::haveRight(self::$rightname, READ);

        $criteria = [
            'SELECT'    => ['glpi_knowbaseitems.*'],
            'DISTINCT'  => true,
            'FROM'      => self::getTable(),
            'WHERE'     => [],
            'LIMIT'     => 10
        ];

        if ($type == "recent") {
            $criteria['ORDERBY'] = 'date_creation DESC';
            $title   = __('Recent entries');
        } else if ($type == 'lastupdate') {
            $criteria['ORDERBY'] = 'date_mod DESC';
            $title   = __('Last updated entries');
        } else {
            $criteria['ORDERBY'] = 'view DESC';
            $title   = __('Most popular questions');
        }

       // Force all joins for not published to verify no visibility set
        $restrict = self::getVisibilityCriteria(true);
        unset($restrict['WHERE']);
        unset($restrict['SELECT']);
        $criteria = array_merge($criteria, $restrict);

        if (Session::getLoginUserID()) {
            $restrict = self::getVisibilityCriteria();
            $criteria['WHERE'] = array_merge($criteria['WHERE'], $restrict['WHERE']);
        } else {
           // Anonymous access
            if (Session::isMultiEntitiesMode()) {
                $criteria['WHERE']['glpi_entities_knowbaseitems.entities_id'] = 0;
                $criteria['WHERE']['glpi_entities_knowbaseitems.is_recursive'] = 1;
            }
        }

       // Only published
        $criteria['WHERE'][] = [
            'NOT'  => [
                'glpi_entities_knowbaseitems.entities_id' => null,
                'glpi_knowbaseitems_profiles.profiles_id' => null,
                'glpi_groups_knowbaseitems.groups_id'     => null,
                'glpi_knowbaseitems_users.users_id'       => null
            ]
        ];

       // Add visibility date
        $criteria['WHERE'][] = [
            'OR'  => [
                ['glpi_knowbaseitems.begin_date' => null],
                ['glpi_knowbaseitems.begin_date' => ['<', new QueryExpression('NOW()')]]
            ]
        ];
        $criteria['WHERE'][] = [
            'OR'  => [
                ['glpi_knowbaseitems.end_date'   => null],
                ['glpi_knowbaseitems.end_date'   => ['>', new QueryExpression('NOW()')]]
            ]
        ];

        if ($faq) { // FAQ
            $criteria['WHERE']['glpi_knowbaseitems.is_faq'] = 1;
        }

        if (
            KnowbaseItemTranslation::isKbTranslationActive()
            && (countElementsInTable('glpi_knowbaseitemtranslations') > 0)
        ) {
            $criteria['LEFT JOIN']['glpi_knowbaseitemtranslations'] = [
                'ON'  => [
                    'glpi_knowbaseitems'             => 'id',
                    'glpi_knowbaseitemtranslations'  => 'knowbaseitems_id', [
                        'AND'                            => [
                            'glpi_knowbaseitemtranslations.language' => $_SESSION['glpilanguage']
                        ]
                    ]
                ]
            ];
            $criteria['SELECT'][] = 'glpi_knowbaseitemtranslations.name AS transname';
            $criteria['SELECT'][] = 'glpi_knowbaseitemtranslations.answer AS transanswer';
        }

        $iterator = $DB->request($criteria);

        $output = "";
        if (count($iterator)) {
            $output .= "<table class='tab_cadrehov'>";
            $output .= "<tr class='noHover'><th>" . $title . "</th></tr>";
            foreach ($iterator as $data) {
                $name = $data['name'];

                if (isset($data['transname']) && !empty($data['transname'])) {
                    $name = $data['transname'];
                }
                $output .= "<tr class='tab_bg_2'><td class='left'><div class='kb'>";
                if ($data['is_faq']) {
                    $output .= "<i class='fa fa-fw fa-question-circle faq' title='" . __("This item is part of the FAQ") . "'></i>";
                }
                $output .= Html::link(Html::resume_text($name, 80), KnowbaseItem::getFormURLWithID($data["id"]), [
                    'class' => $data['is_faq'] ? 'faq' : 'knowbase',
                    'title' => $data['is_faq'] ? __s("This item is part of the FAQ") : ''
                ]);
                $output .= "</div></td></tr>";
            }
            $output .= "</table>";
        }

        if ($display) {
            echo $output;
        } else {
            return $output;
        }
    }


    public function rawSearchOptions()
    {
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
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'right'              => 'all'
        ];

       // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        return $tab;
    }

    public function getRights($interface = 'central')
    {

        if ($interface == 'central') {
            $values = parent::getRights();
            $values[self::KNOWBASEADMIN] = __('Knowledge base administration');
            $values[self::PUBLISHFAQ]    = __('Publish in the FAQ');
            $values[self::COMMENTS]      = __('Comment KB entries');
        }
        $values[self::READFAQ]       = __('Read the FAQ');
        return $values;
    }

    public function pre_updateInDB()
    {
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
    public function getAnswer()
    {
        if (KnowbaseItemTranslation::canBeTranslated($this)) {
            $answer = KnowbaseItemTranslation::getTranslatedValue($this, 'answer');
        } else {
            $answer = $this->fields["answer"];
        }
        $answer = RichText::getEnhancedHtml($answer);

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
        $pattern = '|<(h[1-6]{1})(.?[^>])?>(.+?)</h[1-6]{1}>|';
        $answer = preg_replace_callback($pattern, $callback, $answer);

        return $answer;
    }

    /**
     * Get dropdown parameters from showVisibility method
     *
     * @return array
     */
    protected function getShowVisibilityDropdownParams()
    {
        $params = parent::getShowVisibilityDropdownParams();
        $params['right'] = ($this->getField('is_faq') ? 'faq' : 'knowbase');
        $params['allusers'] = 1;
        return $params;
    }

    /**
     * Reverts item contents to specified revision
     *
     * @param integer $revid Revision ID
     *
     * @return boolean
     */
    public function revertTo($revid)
    {
        $revision = new KnowbaseItem_Revision();
        $revision->getFromDB($revid);

        $values = [
            'id'     => $this->getID(),
            'name'   => addslashes($revision->fields['name']),
            'answer' => addslashes($revision->fields['answer'])
        ];

        if ($this->update($values)) {
            Event::log(
                $this->getID(),
                "knowbaseitem",
                5,
                "tools",
                //TRANS: %1$s is the user login, %2$s the revision number
                sprintf(__('%1$s reverts item to revision %2$s'), $_SESSION["glpiname"], $revid)
            );
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get ids of KBI in given category
     *
     * @param int           $category_id   id of the parent category
     * @param KnowbaseItem  $kbi           used only for unit tests
     *
     * @return array        Array of ids
     */
    public static function getForCategory($category_id, $kbi = null)
    {
        global $DB;

        if ($kbi === null) {
            $kbi = new self();
        }

        $ids = $DB->request([
            'SELECT' => self::getTable() . '.id',

            'FROM'   => self::getTable(),
            'LEFT JOIN' => [
                'glpi_knowbaseitems_knowbaseitemcategories' => [
                    'ON'  => [
                        'glpi_knowbaseitems_knowbaseitemcategories'  => 'knowbaseitems_id',
                        'glpi_knowbaseitems'             => 'id'
                    ]
                ]
            ],
            'WHERE'  => ['glpi_knowbaseitems_knowbaseitemcategories.knowbaseitemcategories_id' => $category_id],
        ]);

       // Get array of ids
        $ids = array_map(function ($row) {
            return $row['id'];
        }, iterator_to_array($ids, false));

       // Filter on canViewItem
        $ids = array_filter($ids, function ($id) use ($kbi) {
            $kbi->getFromDB($id);
            return $kbi->canViewItem();
        });

       // Avoid empty IN
        if (count($ids) === 0) {
            $ids[] = -1;
        }

        return $ids;
    }


    public static function getIcon()
    {
        return "ti ti-lifebuoy";
    }
}

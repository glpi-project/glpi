<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Event;
use Glpi\Features\Clonable;
use Glpi\Features\TreeBrowse;
use Glpi\Features\TreeBrowseInterface;
use Glpi\Form\ServiceCatalog\ServiceCatalog;
use Glpi\Form\ServiceCatalog\ServiceCatalogLeafInterface;
use Glpi\RichText\RichText;
use Glpi\Search\Output\HTMLSearchOutput;

use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\preg_replace_callback;

/**
 * KnowbaseItem Class
 **/
class KnowbaseItem extends CommonDBVisible implements ExtraVisibilityCriteria, ServiceCatalogLeafInterface, TreeBrowseInterface
{
    use Clonable;
    use TreeBrowse;

    public static $browse_default = true;

    // From CommonDBTM
    public $dohistory    = true;

    protected $items     = [];

    public const KNOWBASEADMIN = 1024;
    public const READFAQ       = 2048;
    public const PUBLISHFAQ    = 4096;
    public const COMMENTS      = 8192;

    public static $rightname   = 'knowbase';

    public function getCloneRelations(): array
    {
        return [
            Entity_KnowbaseItem::class,
            Group_KnowbaseItem::class,
            KnowbaseItem_Profile::class,
            KnowbaseItem_User::class,
            Document_Item::class,
            Infocom::class,
            KnowbaseItem_Item::class,
            KnowbaseItemTranslation::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return __('Knowledge base');
    }

    public static function getMenuShorcut()
    {
        return 'b';
    }

    public function getName($options = [])
    {
        return KnowbaseItemTranslation::getTranslatedValue($this);
    }

    public static function getMenuName()
    {
        if (!Session::haveRight('knowbase', READ)) {
            return __('FAQ');
        }
        return static::getTypeName(Session::getPluralNumber());
    }

    public static function canCreate(): bool
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, self::PUBLISHFAQ]);
    }

    public static function canUpdate(): bool
    {
        return Session::haveRightsOr(self::$rightname, [UPDATE, self::KNOWBASEADMIN]);
    }

    public static function canView(): bool
    {
        global $CFG_GLPI;

        return (Session::haveRightsOr(self::$rightname, [READ, self::READFAQ])
              || ((Session::getLoginUserID() === false) && $CFG_GLPI["use_public_faq"]));
    }

    public function canViewItem(): bool
    {
        if ($this->fields['users_id'] === Session::getLoginUserID()) {
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

    public function canUpdateItem(): bool
    {
        // Personal knowbase or visibility and write access
        return (Session::haveRight(self::$rightname, self::KNOWBASEADMIN)
              || (Session::getCurrentInterface() === "central"
                  && $this->fields['users_id'] === Session::getLoginUserID())
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

    public static function getSearchURL($full = true)
    {
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');

        if (Session::getCurrentInterface() === "central") {
            return "$dir/front/knowbaseitem.php";
        }
        return "$dir/front/helpdesk.faq.php";
    }

    public static function getFormURL($full = true)
    {
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');

        if (Session::getCurrentInterface() === "central") {
            return "$dir/front/knowbaseitem.form.php";
        }
        return "$dir/front/helpdesk.faq.php";
    }

    /**
     * Get the form page URL for the current classe
     *
     * @param array   $params parameters to add to the URL
     * @param boolean $full  path or relative one
     * @return string
     **/
    public static function getFormURLWithParam($params = [], $full = true): string
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
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(KnowbaseItemTranslation::class, $ong, $options);
        $this->addStandardTab(ServiceCatalog::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Revision::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Comment::class, $ong, $options);

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            $nb = 0;
            switch ($item::class) {
                case self::class:
                    $ong[1] = self::createTabEntry(self::getTypeName(1));
                    if ($item->canUpdateItem()) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb = $item->countVisibilities();
                        }
                        $ong[2] = self::createTabEntry(
                            _n('Target', 'Targets', Session::getPluralNumber()),
                            $nb,
                            $item::getType()
                        );
                        $ong[3] = self::createTabEntry(__('Edit'), 0, $item::class, 'ti ti-pencil');
                    }
                    return $ong;
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof self) {
            return false;
        }
        switch ($tabnum) {
            case 1:
                return (bool) $item->showFull();

            case 2:
                return $item->showVisibility();

            case 3:
                return $item->showForm($item->getID());

            default:
                return false;
        }
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

    public function post_addItem()
    {
        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles(
            $this->input,
            [
                'force_update'  => true,
                'content_field' => 'answer',
            ]
        );

        if (isset($this->input["_visibility"]['_type']) && !empty($this->input["_visibility"]["_type"])) {
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

        if (isset($this->input['_do_item_link']) && (bool) $this->input['_do_item_link']) {
            $params = [
                'knowbaseitems_id' => $this->getID(),
                'itemtype'         => $this->input['_itemtype'],
                'items_id'         => $this->input['_items_id'],
            ];
            $kb_item_item = new KnowbaseItem_Item();
            $kb_item_item->add($params);
        }

        // Support old "knowbaseitemcategories_id" input
        if (isset($this->input['knowbaseitemcategories_id'])) {
            Toolbox::deprecated('knowbaseitemcategories_id input is deprecated. Use _categories instead');
            $categories = $this->input['knowbaseitemcategories_id'];
            $this->input['_categories'] = is_array($categories) ? $categories : [$categories];
            unset($this->input['knowbaseitemcategories_id']);
        }

        // Handle categories
        $this->update1NTableData(KnowbaseItem_KnowbaseItemCategory::class, "_categories");

        NotificationEvent::raiseEvent('new', $this);
    }

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

        // Load categories
        $this->load1NTableData(KnowbaseItem_KnowbaseItemCategory::class, '_categories');
    }

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

        // KnowbaseItem_Comment does not extends CommonDBConnexity
        $kbic = new KnowbaseItem_Comment();
        $kbic->deleteByCriteria(['knowbaseitems_id' => $this->fields['id']]);

        // KnowbaseItem_Revision does not extends CommonDBConnexity
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

        if (!Session::isMultiEntitiesMode()) {
            return true;
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

        // Build common JOIN clause
        $criteria = [
            'LEFT JOIN' => self::getVisibilityCriteriaCommonJoin($forceall),
        ];

        // Handle anonymous users
        if (!Session::getLoginUserID()) {
            // Public FAQ is enabled; show FAQ, otherwise show nothing
            $criteria['WHERE'] = $CFG_GLPI["use_public_faq"] ? self::getVisibilityCriteriaFAQ() : [new QueryExpression('false')];
            return $criteria;
        }

        // Handle logged in users
        // Show FAQ for helpdesk user, knowledge base for central users
        $criteria['WHERE'] = Session::getCurrentInterface() === "helpdesk"
            ? self::getVisibilityCriteriaFAQ()
            : self::getVisibilityCriteriaKB();
        return $criteria;
    }

    /**
     * Common JOIN clause used by getVisibilityCriteria* methods
     *
     * @param bool $forceall Force all join ?
     *
     * @return array LEFT JOIN clause
     */
    private static function getVisibilityCriteriaCommonJoin(bool $forceall = false)
    {
        global $CFG_GLPI;

        $join = [];

        // Context checks - avoid doing unnecessary join if possible
        $is_public_faq_context = !Session::getLoginUserID() && $CFG_GLPI["use_public_faq"];
        $has_session_groups = count(($_SESSION["glpigroups"] ?? []));
        $has_active_profile = isset($_SESSION["glpiactiveprofile"]['id']);
        $has_active_entity = count(($_SESSION["glpiactiveentities"] ?? []));

        // Add user restriction data
        if ($forceall || Session::getLoginUserID()) {
            $join['glpi_knowbaseitems_users'] = [
                'ON' => [
                    'glpi_knowbaseitems_users' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id',
                ],
            ];
        }

        // Add group restriction data
        if ($forceall || $has_session_groups) {
            $join['glpi_groups_knowbaseitems'] = [
                'ON' => [
                    'glpi_groups_knowbaseitems' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id',
                ],
            ];
        }

        // Add profile restriction data
        if ($forceall || $has_active_profile) {
            $join['glpi_knowbaseitems_profiles'] = [
                'ON' => [
                    'glpi_knowbaseitems_profiles' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id',
                ],
            ];
        }

        // Add entity restriction data
        if ($forceall || $has_active_entity || $is_public_faq_context) {
            $join['glpi_entities_knowbaseitems'] = [
                'ON' => [
                    'glpi_entities_knowbaseitems' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id',
                ],
            ];
        }

        return $join;
    }

    /**
     * Get visibility criteria for articles displayed in the FAQ (seen by
     * helpdesk and anonymous users)
     * This mean any KB article tagged as 'is_faq' should be displayed
     *
     * @return array WHERE clause
     */
    private static function getVisibilityCriteriaFAQ(): array
    {
        // Specific case for anonymous users + multi entities
        if (!Session::getLoginUserID()) {
            $where = ['is_faq' => 1];
            if (Session::isMultiEntitiesMode()) {
                $where[Entity_KnowbaseItem::getTableField('entities_id')] = 0;
                $where[Entity_KnowbaseItem::getTableField('is_recursive')] = 1;
            }
        } else {
            $where = self::getVisibilityCriteriaKB();
            $where['is_faq'] = 1;
        }

        return $where;
    }

    /**
     * Get visibility criteria for articles displayed in the knowledge base
     * (seen by central users)
     * This mean any KB article with valid visibility criteria for the current
     * user should be displayed
     *
     * @return array WHERE clause
     */
    private static function getVisibilityCriteriaKB(): array
    {
        // Special case for KB Admins
        if (Session::haveRight(self::$rightname, self::KNOWBASEADMIN)) {
            // See all articles
            return [new QueryExpression('1')];
        }

        // Prepare criteria, which will use an OR statement (the user can read
        // the article if any of the user/group/profile/entity criteria are
        // validated)
        $where = ['OR' => []];

        // Special case: the user may be the article's author
        $user = Session::getLoginUserID();
        $author_check = [self::getTableField('users_id') => $user];
        $where['OR'][] = $author_check;

        // Filter on users
        $where['OR'][] = self::getVisibilityCriteriaKB_User();

        // Filter on groups (if the current user have any)
        $groups = $_SESSION["glpigroups"] ?? [];
        if (count($groups)) {
            $where['OR'][] = self::getVisibilityCriteriaKB_Group();
        }

        // Filter on profiles
        $where['OR'][] = self::getVisibilityCriteriaKB_Profile();

        // Filter on entities
        $where['OR'][] = self::getVisibilityCriteriaKB_Entity();

        return $where;
    }

    /**
     * Get criteria used to filter knowledge base articles on users
     *
     * @return array
     */
    private static function getVisibilityCriteriaKB_User(): array
    {
        $user = Session::getLoginUserID();
        return [
            KnowbaseItem_User::getTableField('users_id') => $user,
        ];
    }

    /**
     * Get criteria used to filter knowledge base articles on groups
     *
     * @return array
     */
    private static function getVisibilityCriteriaKB_Group(): array
    {
        $groups = $_SESSION["glpigroups"] ?? [-1];
        $entity_restriction = getEntitiesRestrictCriteria(
            Group_KnowbaseItem::getTable(),
            '',
            '',
            true,
            true
        );

        return [
            Group_KnowbaseItem::getTableField('groups_id') => $groups,
            'OR' => [
                Group_KnowbaseItem::getTableField('no_entity_restriction') => 1,
            ] + $entity_restriction,
        ];
    }

    /**
     * Get criteria used to filter knowledge base articles on profiles
     *
     * @return array
     */
    private static function getVisibilityCriteriaKB_Profile(): array
    {
        $profile = $_SESSION["glpiactiveprofile"]['id'] ?? -1;
        $entity_restriction = getEntitiesRestrictCriteria(
            KnowbaseItem_Profile::getTable(),
            '',
            '',
            true,
            true
        );

        return [
            KnowbaseItem_Profile::getTableField('profiles_id') => $profile,
            'OR' => [
                KnowbaseItem_Profile::getTableField('no_entity_restriction') => 1,
            ] + $entity_restriction,
        ];
    }

    /**
     * Get criteria used to filter knowledge base articles on entity
     *
     * @return array
     */
    private static function getVisibilityCriteriaKB_Entity(): array
    {
        $entity_restriction = getEntitiesRestrictCriteria(
            Entity_KnowbaseItem::getTable(),
            '',
            '',
            true,
            true
        );

        // All entities
        if (!count($entity_restriction)) {
            $entity_restriction = [
                Entity_KnowbaseItem::getTableField('entities_id') => null,
            ];
        }

        return $entity_restriction;
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

    public function post_updateItem($history = true)
    {
        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles(
            $this->input,
            [
                'force_update'  => true,
                'content_field' => 'answer',
            ]
        );

        // Support old "knowbaseitemcategories_id" input
        if (isset($this->input['knowbaseitemcategories_id'])) {
            Toolbox::deprecated('knowbaseitemcategories_id input is deprecated. Use _categories instead');
            $categories = $this->input['knowbaseitemcategories_id'];
            $this->input['_categories'] = is_array($categories) ? $categories : [$categories];
            unset($this->input['knowbaseitemcategories_id']);
        }

        // Update categories
        $this->update1NTableData(KnowbaseItem_KnowbaseItemCategory::class, '_categories');
        NotificationEvent::raiseEvent('update', $this);
    }

    public function post_purgeItem()
    {
        NotificationEvent::raiseEvent('delete', $this);
    }

    public function showForm($ID, array $options = []): bool
    {
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
        if (empty($ID) && !empty($options['item_itemtype']) && !empty($options['item_items_id'])) {
            /** @var ?CommonITILObject $item */
            if ($item = getItemForItemtype($options['item_itemtype'])) {
                if ($item->getFromDB($options['item_items_id'])) {
                    $this->fields['name']   = $item->getField('name');
                    if (isset($options['_fup_to_kb'])) {
                        $fup = new ITILFollowup();
                        $fup->getFromDBByCrit([
                            'id'           => $options['_fup_to_kb'],
                            'itemtype'     => $item::class,
                            'items_id'     => $item->getID(),
                        ]);
                        $this->fields['answer'] = $fup->getField('content');
                    } elseif (isset($options['_task_to_kb'])) {
                        $task = $item->getTaskClassInstance();
                        $task->getFromDB($options['_task_to_kb']);
                        $this->fields['answer'] = $task->getField('content');
                    } elseif (isset($options['_sol_to_kb'])) {
                        $solution = new ITILSolution();
                        $solution->getFromDBByCrit([
                            'itemtype'     => $item::class,
                            'items_id'     => $item->getID(),
                            [
                                'NOT' => ['status'       => CommonITILValidation::REFUSED],
                            ],
                        ]);
                        $this->fields['answer'] = $solution->getField('content');
                    }
                    if ($item->isField('itilcategories_id')) {
                        $ic = new ITILCategory();
                        if (
                            $ic->getFromDB($item->getField('itilcategories_id'))
                            && $ic->fields['knowbaseitemcategories_id'] > 0
                        ) {
                            $this->fields['knowbaseitemcategories_id'] = $ic->fields['knowbaseitemcategories_id'];
                        }
                    }
                }
            }
        }

        if (($item !== null) && $item = getItemForItemtype($options['item_itemtype'])) {
            $item->getFromDB($options['item_items_id']);
        }

        TemplateRenderer::getInstance()->display('pages/tools/kb/knowbaseitem.html.twig', [
            'item' => $this,
            'linked_item' => $item,
            'no_header' => true,
            'params' => [
                'canedit' => $canedit,
            ] + $options,
        ]);

        return true;
    }

    /**
     * Increase the view counter of the current knowbaseitem
     *
     * @since 0.83
     */
    public function updateCounter()
    {
        global $DB;

        // update counter view
        $DB->update(
            'glpi_knowbaseitems',
            [
                'view'   => new QueryExpression($DB::quoteName('view') . ' + 1'),
            ],
            [
                'id' => $this->getID(),
            ]
        );
    }

    /**
     * Print out (html) show item : question and answer
     *
     * @param array $options Array of options
     *
     * @return boolean|string
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

        $linkusers_id = true;
        if (
            ((Session::getLoginUserID() === false) && $CFG_GLPI["use_public_faq"])
            || (Session::getCurrentInterface() === "helpdesk")
            || !User::canView()
        ) {
            $linkusers_id = false;
        }

        $this->updateCounter();

        $categories = KnowbaseItem_KnowbaseItemCategory::getItems($this);
        $article_categories = [];
        foreach ($categories as $category) {
            $knowbaseitemcategories_id = $category['knowbaseitemcategories_id'];
            $fullcategoryname = getTreeValueCompleteName('glpi_knowbaseitemcategories', $knowbaseitemcategories_id);
            $article_categories[$knowbaseitemcategories_id] = $fullcategoryname;
        }

        // Show documents attached to the FAQ Item
        $sort = 'filename';
        $order = 'ASC';
        $criteria = Document_Item::getDocumentForItemRequest($this, ["$sort $order"]);
        $criteria['WHERE'][] = ['is_deleted' => '0'];
        $iterator = $DB->request($criteria);

        $attachments = [];
        $heading_names = [];
        if (count($iterator) > 0) {
            $document = new Document();
            foreach ($iterator as $data) {
                $docID        = $data["id"];
                $downloadlink = htmlescape(NOT_AVAILABLE);

                if ($document->getFromDB($docID)) {
                    $downloadlink = $document->getDownloadLink();
                }

                if (!isset($heading_names[$data["documentcategories_id"]])) {
                    $heading_names[$data["documentcategories_id"]] = Dropdown::getDropdownName(
                        "glpi_documentcategories",
                        $data["documentcategories_id"]
                    );
                }

                $attachments[] = [
                    'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                    'filename' => $downloadlink,
                    'heading' => $heading_names[$data["documentcategories_id"]],
                    'assocdate' => $data["assocdate"],
                ];
            }
        }

        $writer_link = '';
        if ($this->fields["users_id"]) {
            $writer_link = getUserLink($this->fields["users_id"]);
        }

        $out = TemplateRenderer::getInstance()->render('pages/tools/kb/article.html.twig', [
            'item' => $this,
            'categories' => $article_categories,
            'subject' => KnowbaseItemTranslation::getTranslatedValue($this, 'name'),
            'answer' => $this->getAnswer(),
            'attachments' => $attachments,
            'writer_link' => $writer_link,
        ]);
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
            return;
        }

        // Default values of parameters
        $params["contains"]                  = "";
        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        if (
            isset($options['item_itemtype'], $options['item_items_id'])
            && !is_a($options['item_itemtype'], CommonDBTM::class, true)
        ) {
            unset($options['item_itemtype'], $options['item_items_id']);
        }

        $twig_params = [
            'contains' => $params["contains"],
            'options' => $options,
            'btn_msg' => _x('button', 'Search'),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
            <form method="get" action="{{ 'KnowbaseItem'|itemtype_search_path }}" class="d-flex justify-content-center">
                {{ inputs.text('contains', contains, {additional_attributes: {size: 50}, input_addclass: 'me-1'}) }}
                {{ inputs.submit('search', btn_msg, 1) }}
                {% if options.item_itemtype is defined and options.item_items_id is defined %}
                    {{ inputs.hidden('item_itemtype', options.item_itemtype) }}
                    {{ inputs.hidden('item_items_id', options.item_items_id) }}
                {% endif %}
                {{ inputs.hidden('glpi_csrf_token', csrf_token()) }}
            </form>
TWIG, $twig_params);
    }

    /**
     * Build request for showList
     *
     * @since 0.83
     *
     * @param array $params (contains, knowbaseitemcategories_id, faq)
     * @param string $type search type : browse / search (default search)
     *
     * @return array : SQL request
     **/
    public static function getListRequest(array $params, $type = 'search')
    {
        global $DB;

        $params = array_replace([
            'contains' => '',
            'knowbaseitemcategories_id' => KnowbaseItemCategory::SEEALL,
            'faq' => false,
        ], $params);

        // Mysql's MATCH AGAINST do not accept expressions that contains only spaces
        if (trim($params['contains']) === '') {
            $params['contains'] = '';
        }

        $criteria = [
            'SELECT' => [
                'glpi_knowbaseitems.*',
                new QueryExpression(
                    QueryFunction::count('glpi_knowbaseitems_users.id') . ' + '
                    . QueryFunction::count('glpi_groups_knowbaseitems.id') . ' + '
                    . QueryFunction::count('glpi_knowbaseitems_profiles.id') . ' + '
                    . QueryFunction::count('glpi_entities_knowbaseitems.id') . ' AS '
                    . $DB::quoteName('visibility_count')
                ),
            ],
            'FROM'   => 'glpi_knowbaseitems',
            'WHERE'     => [], //to be filled
            'LEFT JOIN' => [], //to be filled
            'GROUPBY'   => ['glpi_knowbaseitems.id'],
        ];

        // Lists kb Items
        $restrict = self::getVisibilityCriteria(true);
        $restrict_where = $restrict['WHERE'];
        unset($restrict['WHERE'], $restrict['SELECT']);
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
                ],
            ];
        }

        if ($params['knowbaseitemcategories_id'] !== KnowbaseItemCategory::SEEALL) {
            $criteria['LEFT JOIN'][KnowbaseItem_KnowbaseItemCategory::getTable()] = [
                'FKEY' => [
                    KnowbaseItem_KnowbaseItemCategory::getTable() => KnowbaseItem::getForeignKeyField(),
                    KnowbaseItem::getTable() => 'id',
                ],
            ];
            if ($params['knowbaseitemcategories_id'] > 0) {
                $criteria['WHERE'][KnowbaseItem_KnowbaseItemCategory::getTableField('knowbaseitemcategories_id')] = $params['knowbaseitemcategories_id'];
            } elseif ($params['knowbaseitemcategories_id'] === 0) {
                $criteria['WHERE'][KnowbaseItem_KnowbaseItemCategory::getTableField('knowbaseitemcategories_id')] = null;
            }
        }

        if (countElementsInTable('glpi_knowbaseitemtranslations') > 0) {
            $criteria['LEFT JOIN']['glpi_knowbaseitemtranslations'] = [
                'ON'  => [
                    'glpi_knowbaseitems'             => 'id',
                    'glpi_knowbaseitemtranslations'  => 'knowbaseitems_id', [
                        'AND'                            => [
                            'glpi_knowbaseitemtranslations.language' => $_SESSION['glpilanguage'],
                        ],
                    ],
                ],
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

            case 'allpublished':
                $criteria['HAVING']['visibility_count'] = ['>', 0];
                break;

            case 'search':
                if (strlen($params["contains"]) > 0) {
                    $search = $params["contains"];
                    $search_wilcard = self::computeBooleanFullTextSearch($search);

                    if ($search_wilcard === '*') {
                        break;
                    }

                    $addscore = [];
                    if (countElementsInTable('glpi_knowbaseitemtranslations') > 0) {
                        $addscore = [
                            'glpi_knowbaseitemtranslations.name',
                            'glpi_knowbaseitemtranslations.answer',
                        ];
                    }

                    $expr = "(MATCH(" . $DB->quoteName('glpi_knowbaseitems.name') . ", " . $DB->quoteName('glpi_knowbaseitems.answer') . ")
                           AGAINST(" . $DB->quote($search_wilcard) . " IN BOOLEAN MODE)";

                    if ($addscore !== []) {
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
                        ),
                    ];

                    if ($addscore !== []) {
                        foreach ($addscore as $addscore_field) {
                            $ors[] = [
                                'NOT' => [$addscore_field => null],
                                new QueryExpression(
                                    "MATCH(" . $DB->quoteName($addscore_field) . ")
                              AGAINST(" . $DB->quote($search_wilcard) . " IN BOOLEAN MODE)"
                                ),
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
                                ['glpi_knowbaseitems.begin_date'  => ['<', QueryFunction::now()]],
                            ],
                        ], [
                            'OR'  => [
                                ['glpi_knowbaseitems.end_date'    => null],
                                ['glpi_knowbaseitems.end_date'    => ['>', QueryFunction::now()]],
                            ],
                        ],
                    ];
                    $search_where[] = $visibility_crit;

                    $criteria['ORDERBY'] = ['SCORE DESC'];

                    // preliminar query to allow alternate search if no result with fulltext
                    $search_criteria = [
                        'COUNT'     => 'cpt',
                        'LEFT JOIN' => $criteria['LEFT JOIN'],
                        'FROM'      => 'glpi_knowbaseitems',
                        'WHERE'     => $search_where,
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
                            /* 9 */   "/\-/",
                        ];
                        $contains = preg_replace($search1, "", $params["contains"]);
                        $ors = [
                            ["glpi_knowbaseitems.name"     => ['LIKE', Search::makeTextSearchValue($contains)]],
                            ["glpi_knowbaseitems.answer"   => ['LIKE', Search::makeTextSearchValue($contains)]],
                        ];
                        if (countElementsInTable('glpi_knowbaseitemtranslations') > 0) {
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
                            ['glpi_knowbaseitems.begin_date' => ['<', QueryFunction::now()]],
                        ],
                    ];
                    $criteria['WHERE'][] = [
                        'OR'  => [
                            ['glpi_knowbaseitems.end_date' => null],
                            ['glpi_knowbaseitems.end_date' => ['>', QueryFunction::now()]],
                        ],
                    ];
                }

                $criteria['ORDERBY'] = ['glpi_knowbaseitems.name ASC'];
                break;
        }

        return $criteria;
    }

    /**
     * Clean search for Boolean FullText
     *
     * @since 10.0.7
     * @param string $search
     *
     * @return string
     **/
    private static function computeBooleanFullTextSearch(string $search): string
    {
        $word_chars        = '\p{L}\p{N}_';
        $ponderation_chars = '+\-<>~';

        // Remove any whitespace from begin/end
        $search = preg_replace('/^[\p{Z}\h\v\r\n]+|[\p{Z}\h\v\r\n]+$/u', '', $search);

        // Remove all symbols except word chars, ponderation chars, parenthesis, quotes, wildcards and spaces.
        // @distance is not included, since it's unlikely a human will be using it through UI form
        $search = preg_replace("/[^{$word_chars}{$ponderation_chars}()\"* ]/u", '', $search);

        // Remove all ponderation chars, that can only precede a word and that are not preceded by either beginning of string, a space or an opening parenthesis
        $search = preg_replace("/(?<!^| |\()[{$ponderation_chars}]/u", '', $search);
        // Remove all ponderation chars that are not followed by a search term
        // (they are followed by a space, a closing parenthesis or end fo string)
        $search = preg_replace("/[{$ponderation_chars}]+( |\)|$)/u", '', $search);

        // Remove all opening parenthesis that are located inside a searched term
        // (they are preceded by a word char or a quote)
        $search = preg_replace("/(?<=[{$word_chars}\"])\(/u", '', $search);
        // Remove all closing parenthesis that are located inside a searched term
        // (they are followed by a word char or a quote)
        $search = preg_replace("/\)(?=[{$word_chars}\")])/u", '', $search);
        // Remove empty parenthesis
        $search = preg_replace("/\(\)/u", '', $search);
        // Remove all parenthesis if count of closing does not match count of opening ones
        if (mb_substr_count($search, '(') !== mb_substr_count($search, ')')) {
            $search = preg_replace("/[()]/u", '', $search);
        }

        // Remove all asterisks that are not located at the end of a word
        // (can be followed by a space, a closing parenthesis or end of string, and must be preceded by a word char)
        $search = preg_replace("/(?<=[{$word_chars}])\*(?! |\)|$)/u", '', $search);

        // Remove all double quotes
        // - that are not located before a searched term
        //   (can be preceded by beginning of string, an operator, a space or an opening parenthesis, and must be followed by a word char)
        $search = preg_replace("/(?<=^|[{$ponderation_chars} (])\"(?![{$word_chars}])/u", '', $search);
        // - that are not located after a searched term
        //   (can be followed by a space, a closing parenthesis or end of string, and must be preceded by a word char)
        $search = preg_replace("/(?<=[{$word_chars}])\"(?! |\)|$)/u", '', $search);
        // - if the count is not even
        if (mb_substr_count($search, '"') % 2 !== 0) {
            $search = preg_replace("/\"/u", '', $search);
        }

        // Check if the new value is just the set of operators and spaces and if it is - set the value to an empty string
        if (preg_match("/^[{$ponderation_chars}()\"* ]+$/u", $search)) {
            $search = '';
        }

        // Remove extra spaces
        $search = preg_replace('/\s+/u', ' ', trim($search));

        // Add * foreach word when no boolean operator is used
        if (!preg_match('/[^\p{L}\p{N}_ ]/u', $search)) {
            $search = implode('* ', explode(' ', $search)) . '*';
        }

        return $search;
    }

    /**
     * Print out list kb item
     *
     * @param array $options            $_GET
     * @param string $type search type : browse / search (default search)
     **/
    public static function showList($options, $type = 'search')
    {
        global $CFG_GLPI;

        $DBread = DBConnection::getReadConnection();

        // Default values of parameters
        $params = [
            'faq' => !Session::haveRight(self::$rightname, READ),
            'start' => 0,
            'knowbaseitemcategories_id' => null,
            'contains' => '',
        ];

        if (is_array($options)) {
            $params = array_replace($params, $options);
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

        $criteria = self::getListRequest($params, in_array($type, ['search', 'solution'], true) ? 'search' : $type);

        $main_iterator = $DBread->request($criteria);
        $rows = count($main_iterator);
        $numrows = $rows;

        if ($type !== 'solution') {
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
        }

        $list_limit = $_SESSION['glpilist_limit'];

        $showwriter = in_array($type, ['myunpublished', 'allunpublished', 'allmy']);

        // Limit the result, if no limit applies, use prior result
        if (
            ($rows > $list_limit)
            && !isset($_GET['export_all'])
        ) {
            $criteria['START'] = (int) $params['start'];
            $criteria['LIMIT'] = (int) $list_limit;
            $main_iterator = $DBread->request($criteria);
            $numrows = count($main_iterator);
        }

        if ($numrows > 0) {
            $output = new HTMLSearchOutput();

            // Pager
            $parameters = [
                'start' => $params["start"],
                'knowbaseitemcategories_id' => $params['knowbaseitemcategories_id'],
                'contains' => $params["contains"],
                'is_faq' => $params['faq'],
                'type' => $type,
            ];

            if (isset($options['item_itemtype'], $options['item_items_id'])) {
                $parameters += [
                    'item_items_id' => $options['item_items_id'],
                    'item_itemtype' => $options['item_itemtype'],
                ];
            }

            $pager_url = Toolbox::getItemTypeSearchURL('KnowbaseItem');
            if (!Session::getLoginUserID()) {
                $pager_url = $CFG_GLPI['root_doc'] . "/front/helpdesk.faq.php";
            }
            Html::printPager(
                $params['start'],
                $rows,
                $pager_url,
                Toolbox::append_params($parameters),
                'KnowbaseItem'
            );

            $nbcols = 1;
            // Display List Header
            echo $output::showHeader($numrows + 1, $nbcols);

            echo $output::showNewLine();
            $header_num = 1;
            echo $output::showHeaderItem(__s('Subject'), $header_num);

            if ($showwriter) {
                echo $output::showHeaderItem(__s('Writer'), $header_num);
            }
            echo $output::showHeaderItem(_sn('Category', 'Categories', 1), $header_num);

            echo $output::showHeaderItem(_sn('Associated element', 'Associated elements', Session::getPluralNumber()), $header_num);

            if (isset($options['item_itemtype'], $options['item_items_id'])) {
                echo $output::showHeaderItem('&nbsp;', $header_num);
            }

            // Num of the row (1=header_line)
            $row_num = 1;
            foreach ($main_iterator as $data) {
                Session::addToNavigateListItems('KnowbaseItem', $data["id"]);
                // Column num
                $item_num = 1;
                echo $output::showNewLine(($row_num - 1) % 2 === 1);
                $row_num++;

                $item = new self();
                $item->getFromDB($data["id"]);
                $name   = $data["name"];
                $answer = $data["answer"];
                // Manage translations
                if (!empty($data['transname'])) {
                    $name   = $data["transname"];
                }
                if (!empty($data['transanswer'])) {
                    $answer = $data["transanswer"];
                }

                $toadd = '';
                if (isset($options['item_itemtype'], $options['item_items_id'])) {
                    $href  = " href='#' data-bs-toggle='modal' data-bs-target='#kbshow" . htmlescape($data['id']) . "'";
                    $toadd = Ajax::createIframeModalWindow(
                        'kbshow' . $data["id"],
                        self::getFormURLWithID($data["id"]),
                        ['display' => false]
                    );
                } else {
                    $href = " href=\"" . htmlescape(self::getFormURLWithID($data["id"])) . "\" ";
                }

                $icon_class = "";
                $fa_title = "";
                if (
                    $data['is_faq']
                    && (!Session::isMultiEntitiesMode()
                        || (isset($data['visibility_count'])
                            && $data['visibility_count'] > 0))
                ) {
                    $icon_class = "ti-help faq";
                    $fa_title = __s("This item is part of the FAQ");
                } elseif (
                    isset($data['visibility_count'])
                    && $data['visibility_count'] <= 0
                ) {
                    $icon_class = "ti-eye-off not-published";
                    $fa_title = __s("This item is not published yet");
                }
                echo $output::showItem(
                    "<div class='kb'>$toadd <i class='ti $icon_class' title='$fa_title'></i> <a $href>" . Html::resume_text($name, 80) . "</a></div>
                                   <div class='kb_resume'>" . Html::resume_text(RichText::getTextFromHtml($answer, false, false), 600) . "</div>",
                    $item_num,
                    $row_num
                );


                if ($showwriter) {
                    echo $output::showItem(
                        getUserLink($data["users_id"]),
                        $item_num,
                        $row_num
                    );
                }

                $categories_names = [];
                $ki->getFromDB($data["id"]);
                $categories = KnowbaseItem_KnowbaseItemCategory::getItems($ki);
                foreach ($categories as $category) {
                    $knowbaseitemcategories_id = $category['knowbaseitemcategories_id'];
                    $fullcategoryname          = getTreeValueCompleteName(
                        "glpi_knowbaseitemcategories",
                        $knowbaseitemcategories_id
                    );
                    $cathref = self::getSearchURL() . "?knowbaseitemcategories_id="
                        . $knowbaseitemcategories_id . '&amp;forcetab=Knowbase$2';
                    $categories_names[] = "<a class='kb-category'"
                        . " href='" . htmlescape($cathref) . "'"
                        . " data-category-id='" . htmlescape($knowbaseitemcategories_id) . "'"
                        . ">" . htmlescape($fullcategoryname) . '</a>';
                }
                echo $output::showItem(implode(', ', $categories_names), $item_num, $row_num);

                echo "<td class='center'>";
                $j = 0;
                $iterator = $DBread->request([
                    'FIELDS' => 'documents_id',
                    'FROM'   => 'glpi_documents_items',
                    'WHERE'  => [
                        'items_id'  => $data["id"],
                        'itemtype'  => 'KnowbaseItem',
                    ] + getEntitiesRestrictCriteria('', '', '', true),
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

                if (isset($options['item_itemtype'], $options['item_items_id'])) {
                    $content = "<button type='button' class='btn btn-link use_solution' data-solution-id='" . htmlescape($data['id']) . "'>"
                        . __s('Use as a solution') . "</button>";
                    echo $output::showItem($content, $item_num, $row_num);
                }

                // End Line
                echo $output::showEndLine();
            }

            // Display footer
            echo $output::showFooter('', $numrows);
            echo "<br>";
            Html::printPager(
                $params['start'],
                $rows,
                $pager_url,
                Toolbox::append_params($parameters),
                KnowbaseItem::class
            );
        } else {
            echo "<div class='center b'>" . __s('No results found') . "</div>";
        }
    }

    /**
     * Print out list recent or popular kb/faq
     *
     * @param string $type    type : recent / popular / not published
     * @param bool   $display if false, return html
     *
     * @return void|string
     **/
    public static function showRecentPopular(string $type = "", bool $display = true)
    {
        global $DB;

        $faq = !Session::haveRight(self::$rightname, READ);

        $criteria = [
            'SELECT'    => ['glpi_knowbaseitems' => ['id', 'name', 'is_faq']],
            'DISTINCT'  => true,
            'FROM'      => self::getTable(),
            'WHERE'     => [],
            'LIMIT'     => 10,
        ];

        if ($type === "recent") {
            $criteria['ORDERBY'] = self::getTable() . '.date_creation DESC';
            $title   = __('Recent entries');
        } elseif ($type === 'lastupdate') {
            $criteria['ORDERBY'] = self::getTable() . '.date_mod DESC';
            $title   = __('Last updated entries');
        } else {
            $criteria['ORDERBY'] = 'view DESC';
            $title   = __('Most popular questions');
        }

        // Force all joins for not published to verify no visibility set
        $restrict = self::getVisibilityCriteria(true);
        unset($restrict['WHERE'], $restrict['SELECT']);
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
                'glpi_knowbaseitems_users.users_id'       => null,
            ],
        ];

        // Add visibility date
        $criteria['WHERE'][] = [
            'OR'  => [
                ['glpi_knowbaseitems.begin_date' => null],
                ['glpi_knowbaseitems.begin_date' => ['<', QueryFunction::now()]],
            ],
        ];
        $criteria['WHERE'][] = [
            'OR'  => [
                ['glpi_knowbaseitems.end_date'   => null],
                ['glpi_knowbaseitems.end_date'   => ['>', QueryFunction::now()]],
            ],
        ];

        if ($faq) { // FAQ
            $criteria['WHERE']['glpi_knowbaseitems.is_faq'] = 1;
        }

        if (countElementsInTable('glpi_knowbaseitemtranslations') > 0) {
            $criteria['LEFT JOIN']['glpi_knowbaseitemtranslations'] = [
                'ON'  => [
                    'glpi_knowbaseitems'             => 'id',
                    'glpi_knowbaseitemtranslations'  => 'knowbaseitems_id', [
                        'AND'                            => [
                            'glpi_knowbaseitemtranslations.language' => $_SESSION['glpilanguage'],
                        ],
                    ],
                ],
            ];
            $criteria['SELECT'][] = 'glpi_knowbaseitemtranslations.name AS transname';
            $criteria['SELECT'][] = 'glpi_knowbaseitemtranslations.answer AS transanswer';
        }

        $iterator = $DB->request($criteria);

        $output = "";
        if (count($iterator)) {
            $twig_params = [
                'title'    => $title,
                'iterator' => $iterator,
                'faq_tooltip' => __("This item is part of the FAQ"),
            ];
            // language=Twig
            $output .= TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="col-12 col-lg-4 px-2">
                    <table class="table table-sm">
                        <tr><th>{{ title }}</th></tr>
                        {% for data in iterator %}
                            {% set name = (data['transname'] ?? '') is not empty ? data['transname'] : data['name'] %}
                            <tr>
                                <td class="text-start">
                                    <div class="kb">
                                        {% if data['is_faq'] %}
                                            <i class="ti ti-help faq" title="{{ faq_tooltip }}"></i>
                                        {% endif %}
                                        <a href="{{ 'KnowbaseItem'|itemtype_form_path(data['id']) }}" class="{{ data['is_faq'] ? 'faq' : 'knowbase' }}"
                                           title="{{ name }}">{{ name|u.truncate(80, '(...)') }}</a>
                                    </div>
                                </td>
                            </tr>
                        {% endfor %}
                    </table>
                </div>
TWIG, $twig_params);
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
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Subject'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'answer',
            'name'               => __('Content'),
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => static::getTable(),
            'field'              => 'is_faq',
            'name'               => __('FAQ item'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => static::getTable(),
            'field'              => 'view',
            'name'               => _n('View', 'Views', Session::getPluralNumber()),
            'datatype'           => 'integer',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => static::getTable(),
            'field'              => 'begin_date',
            'name'               => __('Visibility start date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'end_date',
            'name'               => __('Visibility end date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => static::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => static::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        $tab[] = [
            'id'                 => '79',
            'table'              => 'glpi_knowbaseitemcategories',
            'field'              => 'completename',
            'name'               => _n('Category', 'Categories', 1),
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => KnowbaseItem_KnowbaseItemCategory::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_knowbaseitems_items',
            'field'              => 'items_id',
            'name'               => _n('Associated element', 'Associated elements', Session::getPluralNumber()),
            'datatype'           => 'specific',
            'comments'           => true,
            'nosort'             => true,
            'nosearch'           => true,
            'additionalfields'   => ['itemtype'],
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '131',
            'table'              => 'glpi_knowbaseitems_items',
            'field'              => 'itemtype',
            'name'               => _n('Associated item type', 'Associated item types', Session::getPluralNumber()),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'kb_types',
            'nosort'             => true,
            'additionalfields'   => ['itemtype'],
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => Entity::getTable(),
            'field'              => 'completename',
            'name'               => _n('Target', 'Targets', 1) . ' - ' . Entity::getTypeName(1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => Entity_KnowbaseItem::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '81',
            'table'              => Profile::getTable(),
            'field'              => 'name',
            'name'               => _n('Target', 'Targets', 1) . ' - ' . Profile::getTypeName(1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => KnowbaseItem_Profile::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '82',
            'table'              => Group::getTable(),
            'field'              => 'name',
            'name'               => _n('Target', 'Targets', 1) . ' - ' . Group::getTypeName(1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => Group_KnowbaseItem::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '83',
            'table'              => User::getTable(),
            'field'              => 'name',
            'name'               => _n('Target', 'Targets', 1) . ' - ' . User::getTypeName(1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => KnowbaseItem_User::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        return $tab;
    }

    public function getRights($interface = 'central')
    {
        if ($interface === 'central') {
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
        $answer = KnowbaseItemTranslation::getTranslatedValue($this, 'answer');
        $answer = RichText::getEnhancedHtml($answer, [
            'text_maxsize' => 0, // Show all text without read more button
        ]);

        $callback = static function ($matches) {
            // 1 => tag name, 2 => existing attributes, 3 => title contents
            $tpl = '<%tag%attrs id="%slug"><a href="#%slug">%icon</a>%title</%tag>';

            $title = str_replace(
                ['%tag', '%attrs', '%slug', '%title', '%icon'],
                [
                    $matches[1],
                    $matches[2],
                    Toolbox::slugify($matches[3]),
                    $matches[3],
                    '<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 16 16" width="16"><path d="M4 9h1v1H4c-1.5 0-3-1.69-3-3.5S2.55 3 4 3h4c1.45 0 3 1.69 3 3.5 0 1.41-.91 2.72-2 3.25V8.59c.58-.45 1-1.27 1-2.09C10 5.22 8.98 4 8 4H4c-.98 0-2 1.22-2 2.5S3 9 4 9zm9-3h-1v1h1c1 0 2 1.22 2 2.5S13.98 12 13 12H9c-.98 0-2-1.22-2-2.5 0-.83.42-1.64 1-2.09V6.25c-1.09.53-2 1.84-2 3.25C6 11.31 7.55 13 9 13h4c1.45 0 3-1.69 3-3.5S14.5 6 13 6z"/></svg>',
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
            'name'   => $revision->fields['name'],
            'answer' => $revision->fields['answer'],
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
        }

        return false;
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
                        'glpi_knowbaseitems'             => 'id',
                    ],
                ],
            ],
            'WHERE'  => ['glpi_knowbaseitems_knowbaseitemcategories.knowbaseitemcategories_id' => $category_id],
        ]);

        // Get array of ids
        $ids = array_map(static fn($row) => $row['id'], iterator_to_array($ids, false));

        // Filter on canViewItem
        $ids = array_filter($ids, static function ($id) use ($kbi) {
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

    public static function getAdditionalSearchCriteria($params)
    {
        if (!self::canView()) {
            $params['criteria'][] = [
                'link'          => "AND",
                'field'         => '8', // is_faq
                'searchtype'    => "equals",
                'virtual'       => true,
                'value'         => 2, // always false, to avoid any result
            ];
        } elseif (!Session::haveRight('knowbase', READ)) {
            $params['criteria'][] = [
                'link'          => "AND",
                'field'         => '8', // is_faq
                'searchtype'    => "equals",
                'virtual'       => true,
                'value'         => 1,
            ];
        }

        $unpublished = [
            '0' => [
                'link'          => "AND",
                'field'         => '80', // entity
                'searchtype'    => "notunder",
                'virtual'       => true,
                'value'         => 0,
            ],
            '1' => [
                'link'          => "AND",
                'field'         => '81', // profile
                'searchtype'    => "equals",
                'virtual'       => true,
                'value'         => "0",
            ],
            '2' => [
                'link'          => "AND",
                'field'         => '82', // group
                'searchtype'    => "equals",
                'virtual'       => true,
                'value'         => "0",
            ],
            '3' => [
                'link'          => "AND",
                'field'         => '83', // user
                'searchtype'    => "equals",
                'virtual'       => true,
                'value'         => "0",
            ],
        ];
        if (!Session::isMultiEntitiesMode()) {
            $unpublished['0'] = [
                'link'          => "AND",
                'field'         => '8', // is_faq
                'searchtype'    => "equals",
                'virtual'       => true,
                'value'         => 0,
            ];
        }
        if (
            !Session::haveRightsOr(self::$rightname, [UPDATE, self::PUBLISHFAQ, self::KNOWBASEADMIN])
            || !isset($params['unpublished'])
            || !$params['unpublished']
        ) {
            $params['criteria'][] = [
                'link'     => "AND NOT",
                'criteria' => $unpublished,
            ];
        }
        return $params;
    }

    #[Override]
    public function getServiceCatalogItemTitle(): string
    {
        return $this->fields['name'] ?? "";
    }

    #[Override]
    public function getServiceCatalogItemDescription(): string
    {
        // Fallback to answer when using the home page search results as the
        // service catalog data may not be specified in this case.
        return $this->fields['description'] ?? $this->fields['answer'] ?? "";
    }

    #[Override]
    public function getServiceCatalogItemIllustration(): string
    {
        // Fallback to a specific icon when using the home page search results
        // as the service catalog data may not be specified in this case.
        return $this->fields['illustration'] ?: "browse-kb";
    }

    #[Override]
    public function isServiceCatalogItemPinned(): bool
    {
        return $this->fields['is_pinned'] ?? false;
    }

    #[Override]
    public function getServiceCatalogLink(): string
    {
        return $this->getLinkURL();
    }
}

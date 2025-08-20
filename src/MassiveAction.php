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

use Glpi\Features\Clonable;
use Glpi\Plugin\Hooks;
use Glpi\Search\SearchOption;
use Symfony\Component\HttpFoundation\Request;

use function Safe\preg_match;

/**
 * Class that manages all the massive actions
 *
 * @todo all documentation !
 *
 * @since 0.85
 **/
class MassiveAction
{
    public const CLASS_ACTION_SEPARATOR  = ':';

    public const NO_ACTION               = 0;
    public const ACTION_OK               = 1;
    public const ACTION_KO               = 2;
    public const ACTION_NORIGHT          = 3;

    /**
     * Massive actions input.
     * @var array
     */
    public $POST = [];

    /**
     * Results of process.
     * @var array
     */
    public $results = [];

    /**
     * Current action key.
     * @var string|null
     */
    private $action;

    /**
     * Current action name.
     * @var string|null
     */
    private $action_name;

    /**
     * Class used to process current action.
     * @var string
     */
    private $processor;

    /**
     * Items to process.
     * @var array
     */
    private $items = [];

    /**
     * Current process identifier.
     * @var int|null
     */
    private $identifier;

    /**
     * Total count of items in current process.
     * @var int
     */
    private $nb_items = 0;

    /**
     * Count of done items in current process.
     * @var int
     */
    private $nb_done = 0;

    /**
     * Items done in current process.
     * @var array
     */
    private $done = [];

    /**
     * Items remaining in current process.
     */
    private ?array $remainings = null;

    /**
     * Fields to remove after reload.
     * @var array
     */
    private $fields_to_remove_when_reload = [];

    /**
     * Current process start time.
     * @var float
     */
    private float $start_time;

    /**
     * Item used to check rights.
     * Variable is used for caching purpose.
     * @var CommonGLPI|null
     */
    private $check_item;

    /**
     * Redirect URL used after actions are processed.
     * @var string
     */
    private $redirect;

    /**
     * Itemtype currently processed.
     * @var class-string<CommonDBTM>
     */
    private $current_itemtype;

    private $from_single_item = false;

    /**
     * Constructor of massive actions.
     * There is three stages and each one have its own objectives:
     * - initial: propose the actions and filter the checkboxes (only once)
     * - specialize: add action specific fields and filter items. There can be as many as needed!
     * - process: process the massive action (only once, but can be reload to avoid timeout)
     *
     * We trust all previous stages: we don't redo the checks
     *
     * @param array     $POST       something like $_POST
     * @param array     $GET        something like $_GET
     * @param string    $stage      the current stage
     * @param int|null  $items_id   Get actions for a single item
     **/
    public function __construct(array $POST, array $GET, $stage, ?int $items_id = null)
    {
        if (isset($GET['_single_item'])) {
            $item = getItemForItemtype($GET['_single_item']['itemtype']);
            if ($item->getFromDB($GET['_single_item']['id'])) {
                $this->from_single_item = true;
                $this->check_item = $item;
            }
        } elseif (($POST['_from_single_item'] ?? false) && isset($POST['item'])) {
            $itemtype = array_keys($POST['item'])[0];
            $item = getItemForItemtype($itemtype);
            if ($item->getFromDB(array_keys($POST['item'][$itemtype])[0])) {
                $this->from_single_item = true;
                $this->check_item = $item;
            }
        }

        if ($POST !== []) {
            if (!isset($POST['is_deleted'])) {
                $POST['is_deleted'] = 0;
            }

            if ((isset($POST['item'])) || (isset($POST['items']))) {
                $remove_from_post = [];

                switch ($stage) {
                    case 'initial':
                        $POST['action_filter'] = [];
                        // 'specific_actions': restrict all possible actions or introduce new ones
                        // thus, don't try to load other actions and don't filter any item
                        if (isset($POST['specific_actions'])) {
                            $POST['actions'] = $POST['specific_actions'];
                            $specific_action = 1;
                            $dont_filter_for = array_keys($POST['actions']);
                        } else {
                            $specific_action = 0;
                            if (isset($POST['add_actions'])) {
                                $POST['actions'] = $POST['add_actions'];
                                $dont_filter_for = array_keys($POST['actions']);
                            } else {
                                $POST['actions'] = [];
                                $dont_filter_for = [];
                            }
                        }
                        if (count($dont_filter_for)) {
                            $POST['dont_filter_for'] = array_combine($dont_filter_for, $dont_filter_for);
                        } else {
                            $POST['dont_filter_for'] = [];
                        }
                        $remove_from_post[] = 'specific_actions';
                        $remove_from_post[] = 'add_actions';
                        $POST['items'] = [];
                        foreach ($POST['item'] as $itemtype => $ids) {
                            // initial are raw checkboxes: 0=unchecked or 1=checked
                            $items = [];
                            foreach ($ids as $id => $checked) {
                                if ($checked == 1) {
                                    $items[$id] = $id;
                                    $this->nb_items++;
                                }
                            }
                            $POST['items'][$itemtype] = $items;
                            if (!$specific_action) {
                                $actions = self::getAllMassiveActions(
                                    $itemtype,
                                    $POST['is_deleted'],
                                    $this->getCheckItem($POST),
                                    $items_id
                                );
                                $POST['actions'] = array_merge($actions, $POST['actions']);
                                foreach ($actions as $action => $label) {
                                    $POST['action_filter'][$action][] = $itemtype;
                                    $POST['actions'][$action]         = $label;
                                }
                            }
                        }
                        if (empty($POST['actions']) && $items_id === null) {
                            throw new Exception(__('No action available'));
                        }
                        // Initial items is used to define $_SESSION['glpimassiveactionselected']
                        $POST['initial_items'] = $POST['items'];
                        $remove_from_post[]    = 'item';
                        break;

                    case 'specialize':
                        if (!isset($POST['action'])) {
                            throw new Exception(__('Implementation error!'));
                        }
                        if ($POST['action'] == -1) {
                            // Case when no action is choosen
                            throw new RuntimeException();
                        }
                        if (isset($POST['actions'])) {
                            // First, get the name of current action !
                            if (!isset($POST['actions'][$POST['action']])) {
                                throw new Exception(__('Implementation error!'));
                            }
                            $POST['action_name'] = $POST['actions'][$POST['action']];
                            $remove_from_post[]  = 'actions';

                            // Then filter the items regarding the action
                            if (!isset($POST['dont_filter_for'][$POST['action']])) {
                                if (isset($POST['action_filter'][$POST['action']])) {
                                    $items = [];
                                    foreach ($POST['action_filter'][$POST['action']] as $itemtype) {
                                        if (isset($POST['items'][$itemtype])) {
                                            $items[$itemtype] = $POST['items'][$itemtype];
                                        }
                                    }
                                    $POST['items'] = $items;
                                }
                            }
                            // Don't affect items that forbid the action
                            $items = [];
                            foreach ($POST['items'] as $itemtype => $ids) {
                                if ($item = getItemForItemtype($itemtype)) {
                                    $forbidden = $item->getForbiddenStandardMassiveAction();
                                    if (in_array($POST['action'], $forbidden)) {
                                        continue;
                                    }
                                    $items[$itemtype] = $ids;
                                }
                            }
                            $POST['items']      = $items;
                            $remove_from_post[] = 'dont_filter_for';
                            $remove_from_post[] = 'action_filter';
                        }
                        // Some action works for only one itemtype. Then, we filter items.
                        if (isset($POST['specialize_itemtype'])) {
                            $itemtype = $POST['specialize_itemtype'];
                            if (isset($POST['items'][$itemtype])) {
                                $POST['items'] = [$itemtype => $POST['items'][$itemtype]];
                            } else {
                                $POST['items'] = [];
                            }
                            $remove_from_post[] = 'specialize_itemtype';
                        }
                        // Extract processor of the action
                        if (!isset($POST['processor'])) {
                            $action = explode(self::CLASS_ACTION_SEPARATOR, $POST['action']);
                            if (count($action) == 2) {
                                $POST['processor'] = $action[0];
                                $POST['action']    = $action[1];
                            } else {
                                $POST['processor'] = 'MassiveAction';
                            }
                        }
                        // Count number of items !
                        foreach ($POST['items'] as $itemtype => $ids) {
                            $this->nb_items += count($ids);
                        }
                        break;

                    case 'process':
                        if (isset($POST['initial_items'])) {
                            $_SESSION['glpimassiveactionselected'] = $POST['initial_items'];
                        } else {
                            $_SESSION['glpimassiveactionselected'] = [];
                        }

                        $remove_from_post = ['items', 'action', 'action_name', 'processor',
                            'massiveaction', 'is_deleted', 'initial_items',
                        ];

                        $this->identifier  = mt_rand();
                        $this->done        = [];
                        $this->nb_done     = 0;
                        $this->action_name = $POST['action_name'];
                        $this->results     = [
                            'ok'       => 0,
                            'noaction' => 0,
                            'ko'       => 0,
                            'noright'  => 0,
                            'messages'  => [],
                        ];
                        foreach ($POST['items'] as $itemtype => $ids) {
                            $this->nb_items += count($ids);
                        }
                        $this->redirect = Html::getBackUrl();
                        break;
                }

                $this->POST = $POST;

                if (isset($this->POST['items']) && is_array($this->POST['items'])) {
                    $this->items = $this->POST['items'];
                }
                if (isset($this->POST['action'])) {
                    $this->action = $this->POST['action'];
                }
                if (isset($this->POST['processor'])) {
                    $this->processor = $this->POST['processor'];
                }

                foreach ($remove_from_post as $field) {
                    if (isset($this->POST[$field])) {
                        unset($this->POST[$field]);
                    }
                }
            }
            if ($this->nb_items == 0 && !isAPI()) {
                throw new Exception(__('No selected items'));
            }
        } else {
            if (
                ($stage != 'process')
                || (!isset($_SESSION['current_massive_action'][$GET['identifier']]))
            ) {
                throw new Exception(__('Implementation error!'));
            }
            $identifier = $GET['identifier'];
            foreach ($_SESSION['current_massive_action'][$identifier] as $attribute => $value) {
                $this->$attribute = $value;
            }
            if ($this->identifier != $identifier) {
                throw new Exception(__('Invalid process'));
            }
            unset($_SESSION['current_massive_action'][$identifier]);
        }

        // Add process elements
        if ($stage == 'process') {
            if (!isset($this->remainings)) {
                $this->remainings = $this->items;
            }

            $this->fields_to_remove_when_reload = ['fields_to_remove_when_reload'];

            $this->start_time = microtime(true);
        }
    }

    /**
     * Get the fields provided by previous stage through $_POST.
     * Beware that the fields that are common (items, action ...) are not provided
     *
     * @return array of the elements
     **/
    public function getInput()
    {
        return $this->POST;
    }


    /**
     * Get current action
     *
     * @return string with the current action or NULL if we are at initial stage
     **/
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get current action name.
     *
     * @return string|null
     */
    public function getActionName(): ?string
    {
        return $this->action_name;
    }

    /**
     * Get current action processor classname.
     *
     * @return string|null
     */
    public function getProcessor(): ?string
    {
        return $this->processor;
    }


    /**
     * Get all items on which this action must work
     *
     * @return array of the items (empty if initial state)
     **/
    public function getItems()
    {
        return $this->items;
    }


    /**
     * Get remaining items
     *
     * @return array of the remaining items (empty if not in process state)
     **/
    public function getRemainings()
    {
        return $this->remainings ?? [];
    }


    /**
     * Destructor of the object
     * It is used when reloading the page during process to store information in $_SESSION.
     **/
    public function __destruct()
    {

        if ($this->identifier !== null) {
            // $this->identifier is unset by self::process() when the massive actions are finished
            foreach ($this->fields_to_remove_when_reload as $field) {
                unset($this->$field);
            }
            $_SESSION['current_massive_action'][$this->identifier] = get_object_vars($this);
        }
    }


    /**
     * @param $POST
     **/
    public function getCheckItem($POST)
    {

        if ($this->check_item === null && isset($POST['check_itemtype'])) {
            if (!($this->check_item = getItemForItemtype($POST['check_itemtype']))) {
                throw new RuntimeException();
            }
            if (isset($POST['check_items_id'])) {
                if (!$this->check_item->getFromDB($POST['check_items_id'])) {
                    throw new RuntimeException();
                } else {
                    $this->check_item->getEmpty();
                }
            }
        }
        return $this->check_item;
    }


    /**
     * Add hidden fields containing all the checked items to the current form
     *
     * @return void
     **/
    public function addHiddenFields()
    {
        $common_fields = ['action', 'processor', 'is_deleted', 'initial_items',
            'item_itemtype', 'item_items_id', 'items', 'action_name',
        ];

        if (!empty($this->POST['massive_action_fields'])) {
            $common_fields = array_merge($common_fields, $this->POST['massive_action_fields']);
        }

        foreach ($common_fields as $field) {
            if (isset($this->POST[$field])) {
                echo Html::hidden($field, ['value' => $this->POST[$field]]);
            }
        }
    }


    /**
     * Extract itemtype from the input (ie.: $input['itemtype'] is defined or $input['item'] only
     * contains one type of item. If none is available and we can display selector (inside the modal
     * window), then display a dropdown to select the itemtype.
     * This is only usefull in case of itemtype specific massive actions (update, ...)
     *
     * @param boolean $display_selector  can we display the itemtype selector ?
     *
     * @return string|boolean  the itemtype, or true if the selector is displayed, or false if we cannot define the itemtype nor display the selector
     **/
    public function getItemtype($display_selector)
    {

        $keys = array_keys($this->items);
        if (count($keys) == 1) {
            return $keys[0];
        }

        if (
            $display_selector
            && (count($keys) > 1)
        ) {
            $itemtypes = [-1 => Dropdown::EMPTY_VALUE];
            foreach ($keys as $itemtype) {
                /** @var class-string $itemtype */
                $itemtypes[$itemtype] = $itemtype::getTypeName(Session::getPluralNumber());
            }
            echo __s('Select the type of the item on which applying this action') . "<br>";

            $rand = Dropdown::showFromArray('specialize_itemtype', $itemtypes);
            echo "<br><br>";

            $params                        = $this->POST;
            $params['specialize_itemtype'] = '__VALUE__';
            Ajax::updateItemOnSelectEvent(
                "dropdown_specialize_itemtype$rand",
                "show_itemtype$rand",
                $_SERVER['REQUEST_URI'],
                $params
            );

            echo "<span id='show_itemtype$rand'>&nbsp;</span>";
            return true;
        }

        return false;
    }


    /**
     * Get 'add to transfer list' action when needed
     *
     * @param $actions   array
     **/
    public static function getAddTransferList(array &$actions)
    {

        if (
            Session::haveRight('transfer', READ)
            && Session::isMultiEntitiesMode()
            && !isAPI()
        ) {
            $actions[self::class . self::CLASS_ACTION_SEPARATOR . 'add_transfer_list']
                  = "<i class='ti ti-corner-right-up'></i>"
                    . _sx('button', 'Add to transfer list');
        }
    }


    /**
     * Get the standard massive actions
     *
     * @param string|CommonDBTM $item        the item for which we want the massive actions
     * @param boolean           $is_deleted  massive action for deleted items ?   (default false)
     * @param CommonDBTM        $checkitem   link item to check right              (default NULL)
     * @param int|null          $items_id    Get actions for a single item
     *
     * @return array|false Array of massive actions or false if $item is not valid
     **/
    public static function getAllMassiveActions($item, $is_deleted = false, ?CommonDBTM $checkitem = null, ?int $items_id = null)
    {
        global $PLUGIN_HOOKS;

        if (is_string($item)) {
            $itemtype = $item;
            if (!($item = getItemForItemtype($itemtype))) {
                return false;
            }
        } elseif ($item instanceof CommonDBTM) {
            $itemtype = $item->getType();
        } else {
            return false;
        }

        if (!is_null($checkitem)) {
            $canupdate = $checkitem->canUpdate();
            $candelete = $checkitem->canDelete();
            $canpurge  = $checkitem->canPurge();
            $cancreate = $checkitem->canCreate();
        } else {
            $canupdate = $itemtype::canUpdate();
            $candelete = $itemtype::canDelete();
            $canpurge  = $itemtype::canPurge();
            $cancreate = $itemtype::canCreate();
        }

        $actions   = [];
        $self_pref = self::class . self::CLASS_ACTION_SEPARATOR;

        if ($is_deleted) {
            if ($canpurge) {
                if (in_array($itemtype, Item_Devices::getConcernedItems())) {
                    $actions[$self_pref . 'purge_item_but_devices']
                                             = _sx('button', 'Delete permanently but keep devices');
                    $actions[$self_pref . 'purge']  = _sx('button', 'Delete permanently and remove devices');
                } else {
                    $actions[$self_pref . 'purge']  = _sx('button', 'Delete permanently');
                }
            }
            if ($candelete) {
                $actions[$self_pref . 'restore'] = _sx('button', 'Restore');
            }
        } else {
            if (
                Session::getCurrentInterface() == 'central'
                && ($canupdate
                 || (Infocom::canApplyOn($itemtype)
                     && Infocom::canUpdate()))
            ) {
                //TRANS: select action 'update' (before doing it)
                $actions[$self_pref . 'update'] = _sx('button', 'Update');

                if ($cancreate && Toolbox::hasTrait($itemtype, Clonable::class)) {
                    $actions[$self_pref . 'clone'] = "<i class='ti ti-copy'></i>" . _sx('button', 'Clone');
                    if ($item->maybeTemplate()) {
                        $actions[$self_pref . 'create_template'] = "<i class='ti ti-copy'></i>" . _sx('button', 'Create template');
                    }
                }
            }

            Line::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
            Infocom::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);

            CommonDBConnexity::getMassiveActionsForItemtype(
                $actions,
                $itemtype,
                $is_deleted,
                $checkitem
            );

            // do not take into account is_deleted if items may be dynamic
            if (
                $item->maybeDeleted()
                && !$item->useDeletedToLockIfDynamic()
            ) {
                if ($candelete) {
                    $actions[$self_pref . 'delete'] = _sx('button', 'Put in trashbin');
                }
            } elseif ($canpurge) {
                if ($item instanceof CommonDBRelation) {
                    $actions[$self_pref . 'purge'] = _sx('button', 'Delete permanently the relation with selected elements');
                } else {
                    $actions[$self_pref . 'purge'] = _sx('button', 'Delete permanently');
                }
                if ($item instanceof CommonDropdown) {
                    $actions[$self_pref . 'purge_but_item_linked']
                     = _sx('button', 'Delete permanently even if linked items');
                }
            }

            // Specific actions
            $actions += $item->getSpecificMassiveActions($checkitem);

            Document::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
            Contract::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
            Reservation::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);

            // Amend comment for objects with a 'comment' field
            $item->getEmpty();
            if ($canupdate && isset($item->fields['comment'])) {
                $actions[$self_pref . 'amend_comment'] = "<i class='ti ti-message-circle'></i>" . __s("Amend comment");
            }

            // Add a note for objects with the UPDATENOTE rights
            if (Session::haveRight($item::$rightname, UPDATENOTE)) {
                $actions[$self_pref . 'add_note'] = "<i class='ti ti-note'></i>" . __s("Add note");
            }

            // Plugin Specific actions
            if (isset($PLUGIN_HOOKS[Hooks::USE_MASSIVE_ACTION])) {
                foreach (array_keys($PLUGIN_HOOKS[Hooks::USE_MASSIVE_ACTION]) as $plugin) {
                    if (!Plugin::isPluginActive($plugin)) {
                        continue;
                    }
                    $plug_actions = Plugin::doOneHook($plugin, Hooks::AUTO_MASSIVE_ACTIONS, $itemtype);

                    if (is_array($plug_actions) && count($plug_actions)) {
                        $actions += $plug_actions;
                    }
                }
            }
        }

        Lock::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
        Consumable::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);

        // Manage forbidden actions : try complete action name or MassiveAction:action_name
        $forbidden_actions = $item->getForbiddenStandardMassiveAction();
        $whitedlisted_actions = [];
        if (
            !isAPI() // Do no filter single actions for API
            && $items_id !== null && $item->getFromDB($items_id)
        ) {
            $forbidden_actions = array_merge(
                $forbidden_actions,
                $item->getForbiddenSingleMassiveActions()
            );
            $whitedlisted_actions = $item->getWhitelistedSingleMassiveActions();
        } elseif ($items_id === null) {
            // Remove forbidden actions for multiple items (actions only allowed from a single item context)
            $forbidden_actions = array_merge(
                $forbidden_actions,
                $item->getForbiddenMultipleMassiveActions()
            );
        }

        if (is_array($forbidden_actions) && count($forbidden_actions)) {
            foreach ($forbidden_actions as $actiontodel) {
                if (isset($actions[$actiontodel])) {
                    unset($actions[$actiontodel]);
                } else {
                    if (str_starts_with($actiontodel, '*:')) {
                        foreach (array_keys($actions) as $action) {
                            if (
                                preg_match('/[^:]+:' . str_replace('*:', '', $actiontodel . '/'), $action)
                                && !in_array($action, $whitedlisted_actions)
                            ) {
                                unset($actions[$action]);
                            }
                        }
                    }
                    if (str_ends_with($actiontodel, ':*')) {
                        foreach (array_keys($actions) as $action) {
                            if (
                                preg_match('/' . str_replace(':*', '', $actiontodel . ':.+/'), $action)
                                && !in_array($action, $whitedlisted_actions)
                            ) {
                                unset($actions[$action]);
                            }
                        }
                    }

                    // Not found search adding MassiveAction prefix
                    $actiontodel = $self_pref . $actiontodel;
                    if (isset($actions[$actiontodel])) {
                        unset($actions[$actiontodel]);
                    }
                }
            }
        }

        // Remove icons for outputs that doesn't expect html
        if ($items_id === null || isAPI()) {
            $actions = array_map(fn($action) => strip_tags($action), $actions);
        }

        return $actions;
    }


    /**
     * Main entry of the modal window for massive actions
     *
     * @return void
     **/
    public function showSubForm()
    {
        $processor = $this->processor;

        if (!$processor::showMassiveActionsSubForm($this)) {
            $this->showDefaultSubForm();
        }

        $this->addHiddenFields();
    }


    /**
     * Class-specific method used to show the fields to specify the massive action
     *
     * @return void
     **/
    public function showDefaultSubForm()
    {
        echo Html::submit(_x('button', 'Post'), [
            'name'  => 'massiveaction',
            'icon'  => 'ti ti-device-floppy',
            'class' => 'btn btn-sm btn-primary',
        ]);
    }


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        global $DB;

        switch ($ma->getAction()) {
            case 'update':
                if (!isset($ma->POST['id_field'])) {
                    $itemtypes        = array_keys($ma->items);
                    $options_per_type = [];
                    $options_count   = [];
                    foreach ($itemtypes as $itemtype) {
                        /** @var class-string $itemtype */
                        $options_per_type[$itemtype] = [];
                        $group                       = '';
                        $show_all                    = true;
                        $show_infocoms               = true;
                        $itemtable                   = getTableForItemType($itemtype);

                        if (
                            Infocom::canApplyOn($itemtype)
                            && (!$itemtype::canUpdate()
                            || !Infocom::canUpdate())
                        ) {
                            $show_all      = false;
                            $show_infocoms = Infocom::canUpdate();
                        }
                        foreach (Search::getCleanedOptions($itemtype, UPDATE) as $index => $option) {
                            if (!is_array($option) || count($option) == 1) {
                                $group                               = !is_array($option) ? $option : $option['name'];
                                $options_per_type[$itemtype][$group] = [];
                            } else {
                                if (
                                    ($option['field'] != 'id')
                                    && ($index != 1)
                                    // Permit entities_id is explicitly activate
                                    && (($option["linkfield"] != 'entities_id')
                                    || (isset($option['massiveaction']) && $option['massiveaction']))
                                ) {
                                    if (!isset($option['massiveaction']) || $option['massiveaction']) {
                                        if (
                                            ($show_all)
                                            || (($show_infocoms
                                            && Search::isInfocomOption($itemtype, $index))
                                            || (!$show_infocoms
                                            && !Search::isInfocomOption($itemtype, $index)))
                                        ) {
                                            $options_per_type[$itemtype][$group][$itemtype . ':' . $index]
                                            = $option['name'];
                                            if ($itemtable == $option['table']) {
                                                $field_key = 'MAIN:' . $option['field'] . ':' . $index;
                                            } else {
                                                $field_key = $option['table'] . ':' . $option['field'] . ':' . $index;
                                            }
                                            if (!isset($options_count[$field_key])) {
                                                $options_count[$field_key] = [];
                                            }
                                            $options_count[$field_key][] = $itemtype . ':' . $index . ':' . $group;
                                            if (isset($option['MA_common_field'])) {
                                                if (!isset($options_count[$option['MA_common_field']])) {
                                                    $options_count[$option['MA_common_field']] = [];
                                                }
                                                $options_count[$option['MA_common_field']][]
                                                 = $itemtype . ':' . $index . ':' . $group;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $options = [];
                    $itemtype_choices = [];
                    if (count($itemtypes) > 1) {
                        $common_options = [];
                        foreach ($options_count as $field => $users) {
                            if (count($users) > 1) {
                                $labels = [];
                                foreach ($users as $user) {
                                    $user      = explode(':', $user);
                                    $itemtype  = $user[0];
                                    $index     = $itemtype . ':' . $user[1];
                                    $group     = implode(':', array_slice($user, 2));
                                    if (isset($options_per_type[$itemtype][$group][$index])) {
                                        if (
                                            !in_array(
                                                $options_per_type[$itemtype][$group][$index],
                                                $labels
                                            )
                                        ) {
                                            $labels[] = $options_per_type[$itemtype][$group][$index];
                                        }
                                    }
                                    $common_options[$field][] = $index;
                                }
                                $options[$group][$field] = implode('/', $labels);
                            }
                        }
                        $choose_itemtype  = true;
                        $itemtype_choices = [-1 => Dropdown::EMPTY_VALUE];
                        foreach ($itemtypes as $itemtype) {
                            /** @var class-string $itemtype */
                            $itemtype_choices[$itemtype] = $itemtype::getTypeName(Session::getPluralNumber());
                        }
                    } else {
                        $options        = $options_per_type[$itemtypes[0]];
                        $common_options  = false;
                        $choose_itemtype = false;
                    }
                    $choose_field = count($options) >= 1;

                    // Beware: "class='tab_cadre_fixe'" induce side effects ...
                    echo "<table width='100%'><tr>";

                    $colspan = 0;
                    if ($choose_field) {
                        $colspan++;
                        echo "<td>";
                        if ($common_options) {
                            echo __s('Select the common field that you want to update');
                        } else {
                            echo __s('Select the field that you want to update');
                        }
                        echo "</td>";
                        if ($choose_itemtype) {
                            $colspan++;
                            echo "<td rowspan='2'>" . __s('or') . "</td>";
                        }
                    }

                    if ($choose_itemtype) {
                        $colspan++;
                        echo "<td>" . __s('Select the type of the item on which applying this action') . "</td>";
                    }

                    echo "</tr><tr>";
                    // Remove empty option groups
                    $options = array_filter($options, static fn($v) => !is_array($v) || count($v) > 0); // @phpstan-ignore function.alreadyNarrowedType (phpstan thinks there are no empty options groups but it is probably safer to keep this check in case the code evolve and it become possible)
                    if ($choose_field) {
                        echo "<td>";
                        $field_rand = Dropdown::showFromArray(
                            'id_field',
                            $options,
                            ['display_emptychoice' => true]
                        );
                        echo "</td>";
                    }
                    if ($choose_itemtype) {
                        echo "<td>";
                        $itemtype_rand = Dropdown::showFromArray(
                            'specialize_itemtype',
                            $itemtype_choices
                        );
                        echo "</td>";
                    }

                    $next_step_rand = mt_rand();

                    echo "</tr></table>";
                    echo "<span id='update_next_step$next_step_rand'>&nbsp;</span>";

                    if ($choose_field) {
                        $params                   = $ma->POST;
                        $params['id_field']       = '__VALUE__';
                        $params['common_options'] = $common_options;
                        Ajax::updateItemOnSelectEvent(
                            "dropdown_id_field$field_rand",
                            "update_next_step$next_step_rand",
                            $_SERVER['REQUEST_URI'],
                            $params
                        );
                    }

                    if ($choose_itemtype) {
                        $params                        = $ma->POST;
                        $params['specialize_itemtype'] = '__VALUE__';
                        $params['common_options']      = $common_options;
                        Ajax::updateItemOnSelectEvent(
                            "dropdown_specialize_itemtype$itemtype_rand",
                            "update_next_step$next_step_rand",
                            $_SERVER['REQUEST_URI'],
                            $params
                        );
                    }
                    // Only display the form for this stage
                    return true;
                }

                if (!isset($ma->POST['common_options'])) {
                    throw new RuntimeException('Implementation error!');
                }

                if ($ma->POST['common_options'] == 'false') {
                    $search_options = [$ma->POST['id_field']];
                } elseif (isset($ma->POST['common_options'][$ma->POST['id_field']])) {
                    $search_options = $ma->POST['common_options'][$ma->POST['id_field']];
                } else {
                    $search_options = [];
                }

                // TODO: ensure that all items are equivalent ...
                $item   = null;
                $search = null;
                foreach ($search_options as $search_option) {
                    $search_option = explode(':', $search_option);
                    $so_itemtype   = $search_option[0];
                    $so_index      = $search_option[1];

                    if (!$so_item = getItemForItemtype($so_itemtype)) {
                        continue;
                    }

                    if (Infocom::canApplyOn($so_itemtype)) {
                        Session::checkSeveralRightsOr([$so_itemtype  => UPDATE,
                            "infocom"  => UPDATE,
                        ]);
                    } else {
                        $so_item->checkGlobal(UPDATE);
                    }

                    $itemtype_search_options = SearchOption::getOptionsForItemtype($so_itemtype);
                    if (!isset($itemtype_search_options[$so_index])) {
                        throw new RuntimeException();
                    }

                    $item   = $so_item;
                    $search = $itemtype_search_options[$so_index];
                    break; // No need to process all items a corresponding item/searchoption has been found
                }

                if ($item === null) {
                    throw new RuntimeException();
                }

                $plugdisplay = false;
                if (
                    ($plug = isPluginItemType($item->getType()))
                    // Specific for plugin which add link to core object
                    || ($plug = isPluginItemType(getItemTypeForTable($search['table'])))
                ) {
                    $plugdisplay = Plugin::doOneHook(
                        $plug['plugin'],
                        Hooks::AUTO_MASSIVE_ACTIONS_FIELDS_DISPLAY,
                        ['itemtype' => $item->getType(),
                            'options'  => $search,
                        ]
                    );
                }

                if (
                    empty($search["linkfield"])
                    || ($search['table'] == 'glpi_infocoms')
                ) {
                    $fieldname = $search["field"];
                } else {
                    $fieldname = $search["linkfield"];
                }

                if (!$plugdisplay) {
                    $options = [];
                    $values  = [];
                    // For ticket template or aditional options of massive actions
                    if (isset($ma->POST['options'])) {
                        $options = $ma->POST['options'];
                    }
                    switch ($item->getType()) {
                        case 'Change':
                            $search['condition'][] = 'is_change';
                            break;
                        case 'Problem':
                            $search['condition'][] = 'is_problem';
                            break;
                        case 'Ticket':
                            if ($DB->fieldExists($search['table'], 'is_incident') || $DB->fieldExists($search['table'], 'is_request')) {
                                $search['condition'][] = [
                                    'OR' => [
                                        'is_incident',
                                        'is_request',
                                    ],
                                ];
                            }
                            break;
                    }
                    if (isset($ma->POST['additionalvalues'])) {
                        $values = $ma->POST['additionalvalues'];
                    }
                    $values[$search["field"]] = '';
                    echo $item->getValueToSelect($search, $fieldname, $values, $options);
                }

                $items_index = [];
                foreach ($search_options as $search_option) {
                    $search_option = explode(':', $search_option);
                    $items_index[$search_option[0]] = $search_option[1];
                }
                echo Html::hidden('search_options', ['value' => $items_index]);
                echo Html::hidden('field', ['value' => $fieldname]);
                echo "<br>";

                $submit_options = [
                    'name'  => 'massiveaction',
                    'class' => 'btn btn-sm btn-primary',
                ];
                if (isset($ma->POST['submitname']) && $ma->POST['submitname']) {
                    $submitname = $ma->POST['submitname'];
                } else {
                    $submitname = _x('button', 'Post');
                    $submit_options['icon'] = 'ti ti-device-floppy';
                }
                echo Html::submit($submitname, $submit_options);

                return true;

            case 'clone':
                $rand = mt_rand();

                echo "<table width='100%'><tr>";
                echo "<td>";
                echo __s('How many copies do you want to create?');
                echo "</td><tr>";
                echo "<td>" . Html::input("nb_copy", [
                    'id'     => "nb_copy$rand",
                    'value'  => 1,
                    'type'   => 'number',
                    'min'    => 1,
                ]);
                echo "</td>";
                echo "</tr></table>";

                echo "<br>";

                $submit_options = [
                    'name'  => 'massiveaction',
                    'class' => 'btn btn-sm btn-primary',
                ];
                if (isset($ma->POST['submitname']) && $ma->POST['submitname']) {
                    $submitname = $ma->POST['submitname'];
                } else {
                    $submitname = _x('button', 'Post');
                    $submit_options['icon'] = 'ti ti-device-floppy';
                }
                echo Html::submit($submitname, $submit_options);

                return true;
            case 'create_template':
                $rand = mt_rand();

                echo "<table class='w-100'><tr>";
                echo "<td>";
                echo __s('Name');
                echo "</td><tr>";
                echo "<td>" . Html::input("template_name", ['id' => "template_name$rand"]);
                echo "</td>";
                echo "</tr></table>";

                echo "<br>";

                $submit_options = [
                    'name'  => 'massiveaction',
                    'class' => 'btn btn-sm btn-primary',
                ];
                if (isset($ma->POST['submitname']) && $ma->POST['submitname']) {
                    $submitname = $ma->POST['submitname'];
                } else {
                    $submitname = _x('button', 'Post');
                    $submit_options['icon'] = 'ti ti-device-floppy';
                }
                echo Html::submit($submitname, $submit_options);

                return true;

            case 'add_transfer_list':
                echo _sn(
                    "Are you sure you want to add this item to transfer list?",
                    "Are you sure you want to add these items to transfer list?",
                    count($ma->items, COUNT_RECURSIVE) - count($ma->items)
                );
                echo "<br><br>";
                echo Html::submit(_x('button', 'Add'), [
                    'name'  => 'massiveaction',
                    'icon'  => 'ti ti-plus',
                    'class' => 'btn btn-sm btn-primary',
                ]);

                return true;

            case 'amend_comment':
                echo __s("Amendment to insert");
                echo "<br><br>";
                Html::textarea([
                    'name' => 'amendment',
                ]);
                echo("<br><br>");
                echo Html::submit(_x('button', 'Update'), [
                    'name'  => 'massiveaction',
                    'icon'  => 'ti ti-device-floppy',
                    'class' => 'btn btn-sm btn-primary',
                ]);

                return true;

            case 'add_note':
                echo __s("New Note");
                echo "<br><br>";
                Html::textarea([
                    'name' => 'add_note',
                ]);
                echo("<br><br>");
                echo Html::submit(_x('button', 'Add'), [
                    'name'  => 'massiveaction',
                    'icon'  => 'ti ti-plus',
                    'class' => 'btn btn-sm btn-primary',
                ]);

                return true;
        }
        return false;
    }


    /**
     * Display the progress bar.
     */
    public function displayProgressBar(): void
    {
        echo Html::getProgressBar(
            $this->nb_done / $this->nb_items * 100,
            $this->action_name
        );
        if (
            count($this->items) > 1
            && $this->current_itemtype !== null
            && array_key_exists($this->current_itemtype, $this->items)
        ) {
            $nb_done = array_key_exists($this->current_itemtype, $this->done)
                ? count($this->done[$this->current_itemtype])
                : 0;
            echo Html::getProgressBar(
                $nb_done / count($this->items[$this->current_itemtype]) * 100,
                $this->current_itemtype::getTypeName(Session::getPluralNumber())
            );
        }
    }


    /**
     * Process the massive actions for all passed items. This a switch between different methods:
     * new system, old one and plugins ...
     *
     * @return array of results (ok, ko, noright counts, redirect ...)
     **/
    public function process()
    {

        if (!empty($this->remainings)) {
            $this->processForSeveralItemtypes();
        }

        $this->results['redirect'] = $this->redirect;

        // unset $this->identifier to ensure the action won't register in $_SESSION
        $this->identifier = null;

        return $this->results;
    }


    /**
     * Process the specific massive actions for severl itemtypes
     * @return void
     **/
    public function processForSeveralItemtypes()
    {

        $processor = $this->processor;
        foreach ($this->remainings as $itemtype => $ids) {
            if ($item = getItemForItemtype($itemtype)) {
                $processor::processMassiveActionsForOneItemtype($this, $item, $ids);
            }
        }
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        global $CFG_GLPI;

        $action = $ma->getAction();

        switch ($action) {
            case 'delete':
                foreach ($ids as $id) {
                    if ($item->can($id, DELETE)) {
                        if ($item->delete(["id" => $id])) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                break;

            case 'restore':
                foreach ($ids as $id) {
                    if ($item->can($id, DELETE)) {
                        if ($item->restore(["id" => $id])) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                break;

            case 'purge_item_but_devices':
            case 'purge_but_item_linked':
            case 'purge':
                foreach ($ids as $id) {
                    if ($item->can($id, PURGE)) {
                        $force = true;
                        // Only mark deletion for
                        if (
                            $item->maybeDeleted()
                            && $item->useDeletedToLockIfDynamic()
                            && $item->isDynamic()
                        ) {
                            $force = false;
                        }
                        $delete_array = ['id' => $id];
                        if ($action == 'purge_item_but_devices') {
                            $delete_array['keep_devices'] = true;
                        }

                        if ($item instanceof CommonDropdown) {
                            if ($item->haveChildren()) {
                                if ($action != 'purge_but_item_linked') {
                                    $force = false;
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                    $ma->addMessage(__s("You can't delete that item by massive actions, because it has sub-items"));
                                    $ma->addMessage(__s("but you can do it by the form of the item"));
                                    continue;
                                }
                            }
                            if ($item->isUsed()) {
                                if ($action != 'purge_but_item_linked') {
                                    $force = false;
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                    $ma->addMessage(__s("You can't delete that item, because it is used for one or more items"));
                                    $ma->addMessage(__s("but you can do it by the form of the item"));
                                    continue;
                                }
                            }
                        }
                        if ($item->delete($delete_array, $force)) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                break;

            case 'update':
                if (
                    (!isset($ma->POST['search_options']))
                    || (!isset($ma->POST['search_options'][$item->getType()]))
                ) {
                    return false;
                }
                $index     = $ma->POST['search_options'][$item->getType()];
                $searchopt = Search::getCleanedOptions($item->getType(), UPDATE);
                $input     = $ma->POST;
                if (isset($searchopt[$index])) {
                    // Infocoms case
                    if (Search::isInfocomOption($item->getType(), $index)) {
                        $ic               = new Infocom();
                        $link_entity_type = -1;
                        $is_recursive     = 0;
                        // Specific entity item
                        if ($searchopt[$index]["table"] == "glpi_suppliers") {
                            $ent = new Supplier();
                            if ($ent->getFromDB($input[$input["field"]])) {
                                $link_entity_type = $ent->fields["entities_id"];
                                $is_recursive     = $ent->fields["is_recursive"];
                            }
                        }
                        foreach ($ids as $key) {
                            if ($item->getFromDB($key)) {
                                if (
                                    ($link_entity_type < 0)
                                    || ($link_entity_type == $item->getEntityID())
                                    || ($is_recursive
                                    && in_array(
                                        $link_entity_type,
                                        getAncestorsOf(
                                            "glpi_entities",
                                            $item->getEntityID()
                                        )
                                    ))
                                ) {
                                    $input2 = [
                                        'items_id'  => $key,
                                        'itemtype'  => $item->getType(),
                                    ];

                                    if ($ic->can(-1, CREATE, $input2)) {
                                        // Add infocom if not exists
                                        if (!$ic->getFromDBforDevice($item->getType(), $key)) {
                                            $input2["items_id"] = $key;
                                            $input2["itemtype"] = $item->getType();
                                            $ic->fields = [];
                                            $ic->add($input2);
                                            $ic->getFromDBforDevice($item->getType(), $key);
                                        }
                                        $id = $ic->fields["id"];
                                        $ic->fields = [];
                                        if (
                                            $ic->update(['id'            => $id,
                                                $input["field"] => $input[$input["field"]],
                                            ])
                                        ) {
                                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                                        } else {
                                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                                        }
                                    } else {
                                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                                    }
                                } else {
                                    $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                    $ma->addMessage($item->getErrorMessage(ERROR_COMPAT));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                            }
                        }
                    } else { // Not infocoms
                        $link_entity_type = [];
                        // Specific entity item
                        $itemtable = getTableForItemType($item->getType());
                        $itemtype2 = getItemTypeForTable($searchopt[$index]["table"]);

                        foreach ($ids as $key) {

                            if ($item2 = getItemForItemtype($itemtype2)) {
                                if (
                                    ($index != 80) // No entities_id fields
                                    && ($searchopt[$index]["table"] != $itemtable)
                                    && $item2->isEntityAssign()
                                    && $item->isEntityAssign()
                                ) {
                                    $related_item = null;
                                    // Case 1: The modified field is a foreign key (ex : locations_id)
                                    if (isForeignKeyField($input["field"])) {
                                        // Attempt to load the related object using its ID (from the input value)
                                        if ($item2->getFromDB($input[$input["field"]])) {
                                            $related_item = $item2;
                                        }
                                        // Case 2: The field is not a foreign key, but the target class supports connexity (relations)
                                        // Use getConnexityItem() to dynamically resolve the related object based on the main itemtype and id (items_id)
                                    } elseif ($item2 instanceof CommonDBConnexity) {
                                        $related_item = $item2->getConnexityItem($item->getType(), $key);
                                    }

                                    if (!is_null($related_item)) {
                                        if (
                                            isset($related_item->fields["entities_id"])
                                            && ($related_item->fields["entities_id"] >= 0)
                                        ) {
                                            if (
                                                isset($related_item->fields["is_recursive"])
                                                && $related_item->fields["is_recursive"]
                                            ) {
                                                $link_entity_type = getSonsOf(
                                                    "glpi_entities",
                                                    $related_item->fields["entities_id"]
                                                );
                                            } else {
                                                $link_entity_type[] = $related_item->fields["entities_id"];
                                            }
                                        }
                                    }
                                }
                            }

                            if (
                                $item->canEdit($key)
                                && $item->canMassiveAction(
                                    $action,
                                    $input['field'],
                                    $input[$input["field"]]
                                )
                            ) {
                                if (
                                    (count($link_entity_type) == 0)
                                    || in_array($item->fields["entities_id"], $link_entity_type)
                                ) {
                                    if (
                                        $item->update(['id'            => $key,
                                            $input["field"] => $input[$input["field"]],
                                        ])
                                    ) {
                                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                                    } else {
                                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                                    }
                                } else {
                                    $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                    $ma->addMessage($item->getErrorMessage(ERROR_COMPAT));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                                $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                            }
                        }
                    }
                }
                break;

            case 'clone':
            case 'create_template':
                $input = $ma->POST;
                $override_input = [];
                if ($action === 'create_template') {
                    $override_input['template_name'] = $input['template_name'];
                }
                foreach ($ids as $id) {
                    // check rights
                    if ($item->can($id, CREATE)) {
                        // recovers the item from DB
                        if ($item->getFromDB($id)) {
                            $clone_as_template = $action === 'create_template' || $item->isTemplate();
                            if (
                                method_exists($item, "cloneMultiple")
                                && $item->cloneMultiple($input["nb_copy"] ?? 1, $override_input, true, $clone_as_template)
                            ) {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                break;

            case 'add_transfer_list':
                $itemtype = $item->getType();
                if (!isset($_SESSION['glpitransfer_list'])) {
                    $_SESSION['glpitransfer_list'] = [];
                }
                if (!isset($_SESSION['glpitransfer_list'][$itemtype])) {
                    $_SESSION['glpitransfer_list'][$itemtype] = [];
                }
                foreach ($ids as $id) {
                    $_SESSION['glpitransfer_list'][$itemtype][$id] = $id;
                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                }
                $ma->setRedirect($CFG_GLPI['root_doc'] . '/front/transfer.action.php');
                break;

            case 'amend_comment':
                $item->getEmpty();

                // Check the itemtype is a valid target
                if (!array_key_exists('comment', $item->fields)) {
                    $ma->addMessage($item->getErrorMessage(ERROR_COMPAT));
                    break;
                }

                // Load input
                $input = $ma->getInput();
                $amendment = $input['amendment'];

                foreach ($ids as $id) {
                    $item->getFromDB($id);

                    // Check rights
                    if (!$item->canUpdateItem()) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        continue;
                    }

                    $comment = $item->fields['comment'];
                    if (is_null($comment) || $comment == "") {
                        // If the comment was empty, use directly the amendment
                        $comment = $amendment;
                    } else {
                        // If there is already a comment, insert some padding then
                        // the amendment
                        $comment .= "\n\n$amendment";
                    }

                    // Update the comment
                    $success = $item->update([
                        'id'      => $id,
                        'comment' => $comment,
                    ]);

                    if (!$success) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    }
                }
                break;

            case 'add_note':
                // Check rights
                if (!Session::haveRight($item::$rightname, UPDATENOTE)) {
                    $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    break;
                }

                // Load input
                $input = $ma->getInput();
                $content = $input['add_note'];

                $em = new Notepad();

                foreach ($ids as $id) {
                    $success = $em->add([
                        'itemtype'             => $item::getType(),
                        'items_id'             => $id,
                        'content'              => $content,
                        'users_id'             => Session::getLoginUserID(),
                        'users_id_lastupdater' => Session::getLoginUserID(),
                    ]);

                    if (!$success) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    }
                }

                break;
        }
    }


    /**
     * Set the page to redirect for specific actions. By default, call previous page.
     * This should be call once for the given action.
     *
     * @param string $redirect link to the page
     *
     * @return void
     **/
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }


    /**
     * add a message to display when action is done.
     *
     * @param string $message  the message to add
     *
     * @return void
     *
     * @psalm-taint-specialize (to report each unsafe usage as a distinct error)
     */
    public function addMessage($message)
    {
        $this->results['messages'][] = $message;
    }


    /**
     * Set an item as done. If the delay is too long, then reload the page to continue the action.
     * Update the progress if necessary.
     *
     * @param string  $itemtype    the type of the item that has been done
     * @param integer|array $id    id or array of ids of the item(s) that have been done.
     * @param integer $result
     *                self::NO_ACTION      in case of no specific action (used internally for older actions)
     *                MassiveAction::ACTION_OK      everything is OK for the action
     *                MassiveAction::ACTION_KO      something went wrong for the action
     *                MassiveAction::ACTION_NORIGHT not anough right for the action
     * @phpstan-param array<integer>|integer $id
     **/
    public function itemDone($itemtype, $id, $result)
    {
        $this->current_itemtype = $itemtype;

        if (!isset($this->done[$itemtype])) {
            $this->done[$itemtype] = [];
        }

        if (is_array($id)) {
            $number = count($id);
            foreach ($id as $single) {
                unset($this->remainings[$itemtype][$single]);
                $this->done[$itemtype][] = $single;
            }
        } else {
            unset($this->remainings[$itemtype][$id]);
            $this->done[$itemtype][] = $id;
            $number = 1;
        }
        if (count($this->remainings[$itemtype]) == 0) {
            unset($this->remainings[$itemtype]);
        }

        switch ($result) {
            case MassiveAction::ACTION_OK:
                $this->results['ok'] += $number;
                break;

            case MassiveAction::NO_ACTION:
                $this->results['noaction'] += $number;
                break;

            case MassiveAction::ACTION_KO:
                $this->results['ko'] += $number;
                break;

            case MassiveAction::ACTION_NORIGHT:
                $this->results['noright'] += $number;
                break;
        }
        $this->nb_done += $number;

        // Reload every X seconds to refresh the progress bar
        $refresh_delay = 5;
        if ((microtime(true) - $this->start_time) > $refresh_delay) {
            $request = Request::createFromGlobals();
            Html::redirect($request->getBasePath() . $request->getPathInfo() . '?identifier=' . $this->identifier);
        }
    }

    /**
     * @return bool True if massive actions are running from a single item context such as the item's form.
     */
    public function isFromSingleItem(): bool
    {
        return $this->from_single_item;
    }
}

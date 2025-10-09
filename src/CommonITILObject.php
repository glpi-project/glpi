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
use Glpi\ContentTemplates\Parameters\CommonITILObjectParameters;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryUnion;
use Glpi\Event;
use Glpi\Features\Clonable;
use Glpi\Features\Kanban;
use Glpi\Features\KanbanInterface;
use Glpi\Features\Teamwork;
use Glpi\Features\TeamworkInterface;
use Glpi\Features\Timeline;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AnswersSet_FormDestinationItem;
use Glpi\Plugin\Hooks;
use Glpi\RichText\RichText;
use Glpi\RichText\UserMention;
use Glpi\Search\Output\HTMLSearchOutput;
use Glpi\Team\Team;
use Safe\Exceptions\DatetimeException;

use function Safe\getimagesize;
use function Safe\ob_get_clean;
use function Safe\ob_start;
use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\strtotime;

/**
 * CommonITILObject Class
 *
 * @property-read array $users
 * @property-read array $groups
 * @property-read array $suppliers
 **/
abstract class CommonITILObject extends CommonDBTM implements KanbanInterface, TeamworkInterface
{
    use Clonable;
    use Timeline;
    use Kanban;
    use Teamwork;

    /// Users by type
    protected $lazy_loaded_users = null;
    /** @var class-string<CommonITILActor>  */
    public $userlinkclass;
    /// Groups by type
    protected $lazy_loaded_groups = null;
    /** @var class-string<CommonITILActor>  */
    public $grouplinkclass;

    /// Suppliers by type
    protected $lazy_loaded_suppliers = null;
    /** @var class-string<CommonITILActor>  */
    public $supplierlinkclass;

    // HELPDESK LINK HARDWARE DEFINITION : CHECKSUM SYSTEM : BOTH=1*2^0+1*2^1=3
    public const HELPDESK_MY_HARDWARE  = 0;
    public const HELPDESK_ALL_HARDWARE = 1;

    protected static $showTitleInNavigationHeader = true;

    public const MATRIX_FIELD         = '';
    public const URGENCY_MASK_FIELD   = '';
    public const IMPACT_MASK_FIELD    = '';
    public const STATUS_MATRIX_FIELD  = '';


    // ITIL Object shared statuses
    public const INCOMING      = 1; // new
    public const ASSIGNED      = 2; // processing (assigned)
    public const PLANNED       = 3; // processing (planned)
    public const WAITING       = 4; // pending
    public const SOLVED        = 5;
    public const CLOSED        = 6;
    public const ACCEPTED      = 7;
    public const OBSERVED      = 8;
    public const APPROVAL      = 10; // approval / validation

    // --- timeline position
    public const NO_TIMELINE       = -1;
    public const TIMELINE_NOTSET   = 0;
    public const TIMELINE_LEFT     = 1;
    public const TIMELINE_MIDLEFT  = 2;
    public const TIMELINE_MIDRIGHT = 3;
    public const TIMELINE_RIGHT    = 4;

    public const TIMELINE_ORDER_NATURAL = 'natural';
    public const TIMELINE_ORDER_REVERSE = 'reverse';

    public const READMY           =      1;
    public const READALL          =   1024;
    public const SURVEY           = 131072;

    /** @return ?class-string<CommonITILTask> */
    public static function getTaskClass()
    {
        $task_class = static::class . 'Task';
        if (class_exists($task_class)) {
            return $task_class;
        }

        return null;
    }

    public static function getTaskClassInstance(): CommonITILTask
    {
        $task_class = static::getTaskClass();
        if (is_a($task_class, CommonITILTask::class, true)) {
            return new $task_class();
        }

        throw new RuntimeException(sprintf(
            'Task class "%s" does not exist or is not a valid CommonITILTask.',
            $task_class
        ));
    }

    public function post_getFromDB()
    {
        // Object may be reused to load multiple tickets thus we must clear all
        // cached data when a new mysql row is loaded
        $this->clearLazyLoadedActors();
    }

    /**
     * Load linked users
     *
     * @return void
     */
    public function loadUsers(): void
    {
        if (!empty($this->userlinkclass) && !$this->isNewItem()) {
            $class = $this->getActorObjectForItem(User::class);
            $this->lazy_loaded_users = $class->getActors((int) $this->fields['id']);
        } else {
            $this->lazy_loaded_users = [];
        }
    }

    /**
     * Load linked groups
     *
     * @return void
     */
    protected function loadGroups(): void
    {
        if (!empty($this->grouplinkclass) && !$this->isNewItem()) {
            $class = $this->getActorObjectForItem(Group::class);
            $this->lazy_loaded_groups = $class->getActors((int) $this->fields['id']);
        } else {
            $this->lazy_loaded_groups = [];
        }
    }

    /**
     * Load linked suppliers
     *
     * @return void
     */
    public function loadSuppliers(): void
    {
        if (!empty($this->supplierlinkclass) && !$this->isNewItem()) {
            $class = $this->getActorObjectForItem(Supplier::class);
            $this->lazy_loaded_suppliers = $class->getActors((int) $this->fields['id']);
        } else {
            $this->lazy_loaded_suppliers = [];
        }
    }

    /**
     * @since 0.84
     **/
    public function loadActors()
    {
        // Might not be 100% needed to clear cache here but let's be safe
        // This way, any direct call to loadActors is assured to return accurate data
        $this->clearLazyLoadedActors();

        // Load each actors type
        $this->loadUsers();
        $this->loadGroups();
        $this->loadSuppliers();
    }

    /**
     * Clear lazy loaded actor data so it can be recomputed again next time its
     * accessed
     *
     * @return void
     */
    protected function clearLazyLoadedActors(): void
    {
        $this->lazy_loaded_users = null;
        $this->lazy_loaded_groups = null;
        $this->lazy_loaded_suppliers = null;
    }

    /**
     * Magic getter for lazy loaded properties
     *
     * @param string $property_name
     */
    public function __get(string $property_name)
    {
        switch ($property_name) {
            case 'users':
                if ($this->lazy_loaded_users === null) {
                    $this->loadUsers();
                }
                return $this->lazy_loaded_users;

            case 'groups':
                if ($this->lazy_loaded_groups === null) {
                    $this->loadGroups();
                }
                return $this->lazy_loaded_groups;

            case 'suppliers':
                if ($this->lazy_loaded_suppliers === null) {
                    $this->loadSuppliers();
                }
                return $this->lazy_loaded_suppliers;

            default:
                throw new RuntimeException(sprintf('Unknown property `%s`.', $property_name));
        }
    }

    /**
     * Magic handler for isset() calls on lazy loaded properties
     *
     * @param string $property_name
     */
    public function __isset(string $property_name)
    {
        switch ($property_name) {
            case 'users':
            case 'groups':
            case 'suppliers':
                return true;

            default:
                throw new RuntimeException(sprintf('Unknown property `%s`.', $property_name));
        }
    }

    /**
     * Magic handler for unset() calls on lazy loaded properties
     *
     * @param string $property_name
     */
    public function __unset(string $property_name)
    {
        switch ($property_name) {
            case 'users':
                $this->lazy_loaded_users = null;
                break;

            case 'groups':
                $this->lazy_loaded_groups = null;
                break;

            case 'suppliers':
                $this->lazy_loaded_suppliers = null;
                break;

            default:
                throw new RuntimeException(sprintf('Unknown property `%s`.', $property_name));
        }
    }

    /**
     * Return the number of actors currently assigned to the object
     *
     * @since 10.0
     *
     * @return int
     */
    public function countActors(): int
    {
        return $this->countGroups() + $this->countUsers() + $this->countSuppliers();
    }


    /**
     * Return the list of actors for a given actor type
     * We try to retrieve them by:
     * - in case new ticket
     *  - from virtual _actor field (present after a reload)
     *  - from template (predefined actor field)
     *  - from default actor if setting is defined in user preference
     * - for existing ticket (with an id > 0), directly from saved actors
     *
     * @since 10.0
     *
     * @param int $actortype 1=requester, 2=assign, 3=observer
     * @param array $params posted data of itil object
     *
     * @return array of actors
     */
    public function getActorsForType(int $actortype = 1, array $params = []): array
    {
        $actors = [];

        $fn_add_actor = static function (string $itemtype, int $items_id, array $params) use (&$actors) {
            $already_added = !empty(array_filter($actors, static function ($actor) use ($itemtype, $items_id, $params) {
                if ($actor['itemtype'] === $itemtype && (int) $actor['items_id'] === 0) {
                    // Anonymous actors unique based on email
                    return ($actor['alternative_email'] ?? null) === ($params['alternative_email'] ?? null);
                }
                return $actor['itemtype'] === $itemtype && (int) $actor['items_id'] === $items_id;
            }));
            if (!$already_added) {
                $actors[] = [
                    'itemtype' => $itemtype,
                    'items_id' => $items_id,
                ] + $params;
            }
        };

        $actortypestring = self::getActorFieldNameType($actortype);

        if ($this->isNewItem()) {
            $entities_id = $params['entities_id'] ?? $_SESSION['glpiactive_entity'];
            $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $entities_id, '', 1);

            // load default user from preference only at the first load of new ticket form
            // we don't want to trigger it on form reload
            // at first load, the key _skip_default_actor is not present (can only be present after a submit)
            if (!isset($params['_skip_default_actor'])) {
                // $params['_users_id_' . $actortypestring] corresponds to value defined by static::getDefaultValues()
                // fallback to static::getDefaultActor() value if empty
                $users_id_default = array_key_exists('_users_id_' . $actortypestring, $params) && $params['_users_id_' . $actortypestring] > 0
                    ? $params['_users_id_' . $actortypestring]
                    : $this->getDefaultActor($actortype);
                if ($users_id_default > 0) {
                    $userobj  = new User();
                    if ($userobj->getFromDB($users_id_default)) {
                        $name = formatUserName(
                            $userobj->fields["id"],
                            $userobj->fields["name"],
                            $userobj->fields["realname"],
                            $userobj->fields["firstname"]
                        );
                        $email = UserEmail::getDefaultForUser($users_id_default);
                        $fn_add_actor(User::class, $users_id_default, [
                            'text'              => $name,
                            'title'             => $name,
                            'use_notification'  => $email === '' ? false : ($default_use_notif && $userobj->isUserNotificationEnable()),
                            'default_email'     => $email,
                            'alternative_email' => '',
                        ]);
                    }
                }

                $groups_id = array_key_exists('_groups_id_' . $actortypestring, $params) && $params['_groups_id_' . $actortypestring] > 0
                    ? $params['_groups_id_' . $actortypestring] : 0;
                if ($groups_id > 0) {
                    $group_obj = new Group();
                    if ($group_obj->getFromDB($groups_id)) {
                        $fn_add_actor('Group', $groups_id, [
                            'text'  => $group_obj->getName(),
                            'title' => $group_obj->getRawCompleteName(),
                        ]);
                    }
                }
            }

            // load default actors from itiltemplate passed from showForm in `params` var
            // we find this key on the first load of template (when opening form)
            // or when the template change (by category loading)
            if (isset($params['_template_changed'])) {
                $users_id = (int) ($params['_predefined_fields']['_users_id_' . $actortypestring] ?? 0);
                if ($users_id > 0) {
                    $userobj  = new User();
                    if ($userobj->getFromDB($users_id)) {
                        $name = formatUserName(
                            $userobj->fields["id"],
                            $userobj->fields["name"],
                            $userobj->fields["realname"],
                            $userobj->fields["firstname"]
                        );
                        $email = UserEmail::getDefaultForUser($users_id);
                        $fn_add_actor(User::class, $users_id, [
                            'text'              => $name,
                            'title'             => $name,
                            'use_notification'  => $email === '' ? false : ($default_use_notif && $userobj->isUserNotificationEnable()),
                            'default_email'     => $email,
                            'alternative_email' => '',
                        ]);
                    }
                }

                $groups_id = (int) ($params['_predefined_fields']['_groups_id_' . $actortypestring] ?? 0);
                if ($groups_id > 0) {
                    $group_obj = new Group();
                    if ($group_obj->getFromDB($groups_id)) {
                        $fn_add_actor(Group::class, $groups_id, [
                            'text'  => $group_obj->getName(),
                            'title' => $group_obj->getRawCompleteName(),
                        ]);
                    }
                }

                $suppliers_id = (int) ($params['_predefined_fields']['_suppliers_id_' . $actortypestring] ?? 0);
                if ($suppliers_id > 0) {
                    $supplier_obj = new Supplier();
                    if ($supplier_obj->getFromDB($suppliers_id)) {
                        $fn_add_actor(Supplier::class, $suppliers_id, [
                            'text'              => $supplier_obj->fields['name'],
                            'title'             => $supplier_obj->fields['name'],
                            'use_notification'  => $supplier_obj->fields['email'] === '' ? false : $default_use_notif,
                            'default_email'     => $supplier_obj->fields['email'],
                            'alternative_email' => '',
                        ]);
                    }
                }
            }

            // if we load any actor from _itemtype_actortype_id, we are loading template,
            // and so we don't want more actors.
            // if any actor exists and was absent in a field from template, it will be loaded by the POST data.
            // we choose to erase existing actors for any defined in the template.
            if (count($actors)) {
                return $actors;
            }

            // existing actors (from a form reload)
            if (isset($params['_actors'])) {
                foreach ($params['_actors'] as $existing_actortype => $existing_actors) {
                    if ($existing_actortype != $actortypestring) {
                        continue;
                    }
                    foreach ($existing_actors as &$existing_actor) {
                        $actor_obj = getItemForItemtype($existing_actor['itemtype']);
                        if ($actor_obj->getFromDB($existing_actor['items_id'])) {
                            if ($actor_obj instanceof User) {
                                $name = formatUserName(
                                    $actor_obj->fields["id"],
                                    $actor_obj->fields["name"],
                                    $actor_obj->fields["realname"],
                                    $actor_obj->fields["firstname"]
                                );
                                $fn_add_actor($existing_actor['itemtype'], $existing_actor['items_id'], $existing_actor + [
                                    'text'          => $name,
                                    'title'         => $name,
                                    'default_email' => UserEmail::getDefaultForUser($actor_obj->fields["id"]),
                                ]);
                            } elseif ($actor_obj instanceof Supplier) {
                                $fn_add_actor($existing_actor['itemtype'], $existing_actor['items_id'], $existing_actor + [
                                    'text'          => $actor_obj->fields['name'],
                                    'title'         => $actor_obj->fields['name'],
                                    'default_email' => $actor_obj->fields['email'],
                                ]);
                            } else {
                                $fn_add_actor($existing_actor['itemtype'], $existing_actor['items_id'], $existing_actor + [
                                    'text'  => $actor_obj->getName(),
                                    'title' => $actor_obj->getRawCompleteName(),
                                ]);
                            }
                        } elseif (
                            $actor_obj instanceof User
                            && $existing_actor['items_id'] == 0
                            && strlen($existing_actor['alternative_email']) > 0
                        ) {
                            // direct mail actor
                            $fn_add_actor($existing_actor['itemtype'], $existing_actor['items_id'], $existing_actor + [
                                'text'  => $existing_actor['alternative_email'],
                                'title' => $existing_actor['alternative_email'],
                            ]);
                        }
                    }
                }
                return $actors;
            }
        }

        // load existing actors (from existing itilobject)
        if (isset($this->users[$actortype])) {
            foreach ($this->users[$actortype] as $user) {
                $name = getUserName(
                    $user['users_id'],
                    disable_anon: in_array($actortype, [CommonITILActor::REQUESTER, CommonITILActor::OBSERVER])
                );
                $fn_add_actor(User::class, $user['users_id'], [
                    'id'                => $user['id'],
                    'text'              => $name,
                    'title'             => $name,
                    'use_notification'  => $user['use_notification'],
                    'default_email'     => UserEmail::getDefaultForUser($user['users_id']),
                    'alternative_email' => $user['alternative_email'],
                ]);
            }
        }
        if (isset($this->groups[$actortype])) {
            foreach ($this->groups[$actortype] as $group) {
                $group_obj = new Group();
                if ($group_obj->getFromDB($group['groups_id'])) {
                    $fn_add_actor(Group::class, $group['groups_id'], [
                        'id'       => $group['id'],
                        'text'     => $group_obj->getName(),
                        'title'    => $group_obj->getRawCompleteName(),
                    ]);
                }
            }
        }
        if (isset($this->suppliers[$actortype])) {
            foreach ($this->suppliers[$actortype] as $supplier) {
                $supplier_obj = new Supplier();
                if ($supplier_obj->getFromDB($supplier['suppliers_id'])) {
                    $fn_add_actor(Supplier::class, $supplier['suppliers_id'], [
                        'id'                => $supplier['id'],
                        'text'              => $supplier_obj->fields['name'],
                        'title'             => $supplier_obj->fields['name'],
                        'use_notification'  => $supplier['use_notification'],
                        'default_email'     => $supplier_obj->fields['email'],
                        'alternative_email' => $supplier['alternative_email'],
                    ]);
                }
            }
        }

        return $actors;
    }

    /**
     * Restores input, restores saved values, then sets the default options for any that are missing.
     * @param integer $ID The item ID
     * @param array $options ITIL Object options array passed to showFormXXXX functions. This is passed by reference and will be modified by this function.
     * @param ?array $overriden_defaults If specified, these values will be used as the defaults instead of the ones from the {@link getDefaultValues()} function.
     * @param bool $force_set_defaults If true, the defaults are set for missing options even if the item is not new.
     * @return void
     * @see getDefaultOptions()
     * @see restoreInput()
     * @see restoreSavedValues()
     */
    protected function restoreInputAndDefaults($ID, array &$options, ?array $overriden_defaults = null, bool $force_set_defaults = false): void
    {
        $default_values = $overriden_defaults ?? static::getDefaultValues();

        // Restore saved value or override with page parameter
        $options['_saved'] = $this->restoreInput();

        // Restore saved values and override $this->fields
        $this->restoreSavedValues($options['_saved']);

        // Set default options
        if ($force_set_defaults || static::isNewID($ID)) {
            foreach ($default_values as $key => $val) {
                if (!isset($options[$key])) {
                    if (isset($options['_saved'][$key])) {
                        $options[$key] = $options['_saved'][$key];
                    } else {
                        $options[$key] = $val;
                    }
                }
            }
        }
    }

    /**
     * @param $ID
     * @param $options   array
     **/
    public function showForm($ID, array $options = [])
    {
        if (!static::canView()) {
            return false;
        }

        $this->restoreInputAndDefaults($ID, $options);

        $canupdate = !$ID || (Session::getCurrentInterface() == "central" && $this->canUpdateItem());

        if ($ID && in_array($this->fields['status'], static::getClosedStatusArray())) {
            $canupdate = false;
            // No update for actors
            $options['_noupdate'] = true;
        }

        if (!$this->isNewItem()) {
            $options['formtitle'] = sprintf(
                __('%1$s - ID %2$d'),
                $this->getTypeName(1),
                $ID
            );
            //set ID as already defined
            $options['noid'] = true;
        }

        $type = null;
        if (is_a($this, Ticket::class)) {
            $type = ($ID ? $this->fields['type'] : $options['type']);
        }
        // Load template if available
        $predefined_template = 0;
        $template_class = static::getTemplateClass();
        if (class_exists($template_class) && (int) $ID > 0 && isset($this->fields[$template_class::getForeignKeyField()])) {
            $predefined_template = $this->fields[$template_class::getForeignKeyField()];
        }
        $tt = $this->getITILTemplateToUse(
            $options['template_preview'] ?? $predefined_template,
            $type,
            ($ID ? $this->fields['itilcategories_id'] : $options['itilcategories_id']),
            ($ID ? $this->fields['entities_id'] : $options['entities_id'])
        );

        $predefined_fields = $this->setPredefinedFields($tt, $options, static::getDefaultValues());
        $this->initForm($this->fields['id'], $options);

        $options['_canupdate'] = Session::haveRight(static::$rightname, UPDATE);
        $item_commonitilobject = null;
        if ($options['_canupdate']) {
            //compute related item object (Ticket has his own showForm)
            $item_link = static::getItemLinkClass();
            $item_commonitilobject = getItemForItemtype($item_link);
        }

        $mention_options = UserMention::getMentionOptions($this);

        TemplateRenderer::getInstance()->display('components/itilobject/layout.html.twig', [
            'item'                    => $this,
            'mention_options'         => $mention_options,
            'timeline_itemtypes'      => $this->getTimelineItemtypes(),
            'legacy_timeline_actions' => $this->getLegacyTimelineActionsHTML(),
            'params'                  => $options,
            'entities_id'             => $ID ? $this->fields['entities_id'] : $options['entities_id'],
            'timeline'                => $this->getTimelineItems(),
            'itiltemplate_key'        => static::getTemplateFormFieldName(),
            'itiltemplate'            => $tt,
            'item_commonitilobject'   => $item_commonitilobject,
            'predefined_fields'       => Toolbox::prepareArrayForInput($predefined_fields),
            'canupdate'               => $canupdate,
            'canpriority'             => $canupdate,
            'canassign'               => $canupdate,
            'can_requester'           => $this->canRequesterUpdateItem(),
            'has_pending_reason'      => PendingReason_Item::getForItem($this) !== false,
        ]);

        return true;
    }

    /**
     * Return an array of predefined fields from provided template
     * Append also data to $options param (passed by reference) :
     *  - if we transform a ticket (form change and problem) or a problem (for change) override with its field
     *  - override form fields from template (ex: if content field is set in template, content field in option will be overriden)
     *  - if template changed (provided template doesn't match the one found in options), append a key _template_changed in $options
     *  - finally, append templates_id in options
     *
     * @param ITILTemplate $tt The ticket template to use
     * @param array $options The current options array (PASSED BY REFERENCE)
     * @param array $default_values The default values to use in case they are not predefined
     * @return array An array of the predefined values
     */
    protected function setPredefinedFields(ITILTemplate $tt, array &$options, array $default_values): array
    {
        // Predefined fields from template: reset them
        if (isset($options['_predefined_fields'])) {
            $options['_predefined_fields'] = Toolbox::decodeArrayFromInput($options['_predefined_fields']);
        } else {
            $options['_predefined_fields'] = [];
        }
        if (!isset($options['_hidden_fields'])) {
            $options['_hidden_fields'] = [];
        }

        // check original ticket for change and problem
        $tickets_id = $options['tickets_id'] ?? $options['_tickets_id'] ?? null;
        $ticket = new Ticket();
        $ticket->getEmpty();
        if (in_array($this->getType(), ['Change', 'Problem']) && $tickets_id) {
            $ticket->getFromDB($tickets_id);

            // copy fields from original ticket, only when fields are not already set by the user (contained in _saved array)
            $fields = [
                'content',
                'name',
                'impact',
                'urgency',
                'priority',
                'time_to_resolve',
                'entities_id',
            ];
            foreach ($fields as $field) {
                if (!isset($options['_saved'][$field])) {
                    $options[$field] = $ticket->fields[$field];
                }
            }

            if (!isset($options['_saved']['itilcategories_id'])) {
                //page is reloaded on category change, we only want category on the very first load
                $category = new ITILCategory();
                $options['itilcategories_id'] = 0;
                if (
                    $category->getFromDB($ticket->fields['itilcategories_id'])
                    && (
                        ($this->getType() === Change::class && $category->fields['is_change'])
                        || ($this->getType() === Problem::class && $category->fields['is_problem'])
                    )
                ) {
                    $options['itilcategories_id'] = $ticket->fields['itilcategories_id'];
                }
            }
        }

        // check original problem for change
        $problems_id = $options['problems_id'] ?? $options['_problems_id'] ?? null;
        $problem = new Problem();
        $problem->getEmpty();
        if ($this->getType() == "Change" && $problems_id) {
            $problem->getFromDB($problems_id);

            $options['content']             = $problem->fields['content'];
            $options['name']                = $problem->fields['name'];
            $options['impact']              = $problem->fields['impact'];
            $options['urgency']             = $problem->fields['urgency'];
            $options['priority']            = $problem->fields['priority'];
            if (isset($options['problems_id'])) {
                //page is reloaded on category change, we only want category on the very first load
                $options['itilcategories_id'] = $problem->fields['itilcategories_id'];
            }
            $options['time_to_resolve']     = $problem->fields['time_to_resolve'];
            $options['entities_id']         = $problem->fields['entities_id'];
        }

        // Store predefined fields to be able not to take into account on change template
        $predefined_fields = [];
        $tpl_key = static::getTemplateFormFieldName();

        if ($this->isNewItem()) {
            if (isset($tt->predefined) && count($tt->predefined)) {
                foreach ($tt->predefined as $predeffield => $predefvalue) {
                    if (isset($options[$predeffield]) && isset($default_values[$predeffield])) {
                        // Is always default value : not set
                        // Set if already predefined field
                        // Set if ticket template change
                        if (
                            ((count($options['_predefined_fields']) == 0)
                                && ($options[$predeffield] == $default_values[$predeffield]))
                            || (isset($options['_predefined_fields'][$predeffield])
                                && ($options[$predeffield] == $options['_predefined_fields'][$predeffield]))
                            || (isset($options[$tpl_key])
                                && ($options[$tpl_key] != $tt->getID()))

                            // user pref for requestype can't overwrite requestype from template
                            // when change category
                            || ($predeffield == 'requesttypes_id'
                                && empty(($options['_saved'] ?? [])))

                            // tests specificic for change & problem
                            || ($tickets_id != null
                                && $options[$predeffield] == $ticket->fields[$predeffield])
                            || ($problems_id != null
                                && $options[$predeffield] == $problem->fields[$predeffield])
                        ) {
                            $options[$predeffield]           = $predefvalue;
                            $predefined_fields[$predeffield] = $predefvalue;
                            $this->fields[$predeffield]      = $predefvalue;
                        }
                    } else { // Not defined options set as hidden field
                        $options['_hidden_fields'][$predeffield] = $predefvalue;
                    }
                }
                // All predefined override : add option to say predifined exists
                if (count($predefined_fields) == 0) {
                    $predefined_fields['_all_predefined_override'] = 1;
                }
            } else { // No template load : reset predefined values
                if (count($options['_predefined_fields'])) {
                    foreach ($options['_predefined_fields'] as $predeffield => $predefvalue) {
                        if ($options[$predeffield] == $predefvalue) {
                            $options[$predeffield] = $default_values[$predeffield];
                        }
                    }
                }
            }
        }
        // append to options to know later we added predefined values
        // we may need this especially for actors
        if (count($predefined_fields)) {
            $options['_predefined_fields'] = $predefined_fields;
        }

        // check if we load the default template (when openning form for example) or the template changed
        if (!isset($options[$tpl_key]) || $options[$tpl_key] != $tt->getId()) {
            $options['_template_changed'] = true;
        }

        // Put ticket template id on $options for actors
        $options[str_replace('s_id', '', $tpl_key)] = $tt->getId();

        // Add all values to fields of tickets for template preview
        if (($options['template_preview'] ?? false)) {
            foreach ($options as $key => $val) {
                if (!isset($this->fields[$key])) {
                    $this->fields[$key] = $val;
                }
            }
        }

        // Recompute priority if not predefined and impact/urgency was changed
        if (
            !isset($predefined_fields['priority'])
            && (
                isset($predefined_fields['urgency'])
                || isset($predefined_fields['impact'])
            )
        ) {
            $this->fields['priority'] = self::computePriority(
                $this->fields['urgency'] ?? 3,
                $this->fields['impact'] ?? 3
            );
        }

        return $predefined_fields;
    }


    /**
     * Retrieve all possible entities for an itilobject posted data.
     * We try to retrieve requesters in the data:
     * - from `_users_id_requester` (data from template or default actor)
     * - from `_actors` (virtual field when the form is reloaded)
     * By default, if none of these fields are present, entities are get from current active entity.
     *
     * @since 10.0
     *
     * @param array $params posted data by an itil object
     * @return array of possible entities_id
     */
    public function getEntitiesForRequesters(array $params = [])
    {
        $requesters = [];
        if (array_key_exists('_users_id_requester', $params) && !empty($params["_users_id_requester"])) {
            $requesters = !is_array($params["_users_id_requester"])
                ? [$params["_users_id_requester"]]
                : $params["_users_id_requester"];
        }
        if (isset($params['_actors']['requester'])) {
            foreach ($params['_actors']['requester'] as $actor) {
                if (
                    $actor['itemtype'] == "User"
                    && (int) $actor['items_id'] > 0 // ignore actor that is added by only its email
                ) {
                    $requesters[] = $actor['items_id'];
                }
            }
        }

        $entities = $_SESSION['glpiactiveentities'] ?? [];
        foreach ($requesters as $users_id) {
            $user_entities = Profile_User::getUserEntities($users_id, true, true);
            $entities = array_intersect($user_entities, $entities);
        }

        $entities = array_values($entities); // Ensure keys are starting at 0

        return $entities;
    }


    /**
     * Retrieve an item from the database with datas associated (hardwares)
     *
     * @param integer $ID ID of the item to get
     *
     * @return boolean true if succeed else false
     **/
    public function getFromDBwithData($ID)
    {

        if ($this->getFromDB($ID)) {
            $this->getAdditionalDatas();
            return true;
        }
        return false;
    }


    public function getAdditionalDatas() {}


    /**
     * Can manage actors
     *
     * @return boolean
     */
    public function canAdminActors()
    {
        if (isset($this->fields['is_deleted']) && $this->fields['is_deleted'] == 1) {
            return false;
        }
        return Session::haveRight(static::$rightname, UPDATE);
    }


    /**
     * Can assign object
     *
     * @return boolean
     */
    public function canAssign()
    {
        if ($this->isDeleted() || (!$this->isNewItem() && $this->isClosed())) {
            return false;
        }

        return Session::haveRight(static::$rightname, UPDATE);
    }


    /**
     * Can be assigned to me
     *
     * @return boolean
     */
    public function canAssignToMe()
    {
        if (
            isset($this->fields['is_deleted']) && $this->fields['is_deleted'] == 1
            || isset($this->fields['status']) && in_array($this->fields['status'], static::getClosedStatusArray())
        ) {
            return false;
        }
        return Session::haveRight(static::$rightname, UPDATE);
    }


    /**
     * Is the current user have right to approve solution of the current ITIL object.
     *
     * @since 9.4.0
     *
     * @return boolean
     */
    public function canApprove()
    {

        return (($this->fields["users_id_recipient"] === Session::getLoginUserID())
              || $this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION["glpigroups"])));
    }

    /**
     * Is the current user have right to add followups to the current ITIL Object ?
     *
     * @since 9.4.0
     *
     * @return boolean
     */
    public function canAddFollowups()
    {
        return (
            (
                Session::haveRight(ITILFollowup::$rightname, ITILFollowup::ADDMY)
                && (
                    $this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                    || (
                        isset($this->fields["users_id_recipient"])
                        && ($this->fields["users_id_recipient"] == Session::getLoginUserID())
                    )
                )
            )
            || (
                Session::haveRight(ITILFollowup::$rightname, ITILFollowup::ADD_AS_OBSERVER)
                && $this->isUser(CommonITILActor::OBSERVER, Session::getLoginUserID())
            )
            || Session::haveRight(ITILFollowup::$rightname, ITILFollowup::ADDALLITEM)
            || (
                Session::haveRight(ITILFollowup::$rightname, ITILFollowup::ADD_AS_GROUP)
                && isset($_SESSION["glpigroups"])
                && (
                    (
                        $this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION['glpigroups'])
                        && Session::haveRight(ITILFollowup::$rightname, ITILFollowup::ADDMY)
                    )
                    || (
                        $this->haveAGroup(CommonITILActor::OBSERVER, $_SESSION['glpigroups'])
                        && Session::haveRight(ITILFollowup::$rightname, ITILFollowup::ADD_AS_OBSERVER)
                    )
                )
            )
            || (
                Session::haveRight(ITILFollowup::$rightname, ITILFollowup::ADD_AS_TECHNICIAN)
                && (
                    $this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                    || (
                        isset($_SESSION["glpigroups"])
                        && $this->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])
                    )
                )
            )
            || $this->isUserValidationRequested(Session::getLoginUserID(), true)
        );
    }

    public function canAddTasks()
    {
        return (
            (
                Session::haveRight(CommonITILTask::$rightname, CommonITILTask::ADDMY)
                && (
                    $this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                    || (
                        isset($this->fields["users_id_recipient"])
                        && ($this->fields["users_id_recipient"] == Session::getLoginUserID())
                    )
                )
            )
            || (
                Session::haveRight(CommonITILTask::$rightname, CommonITILTask::ADD_AS_OBSERVER)
                && $this->isUser(CommonITILActor::OBSERVER, Session::getLoginUserID())
            )
            || Session::haveRight(CommonITILTask::$rightname, CommonITILTask::ADDALLITEM)
            || (
                Session::haveRight(CommonITILTask::$rightname, CommonITILTask::ADD_AS_GROUP)
                && isset($_SESSION["glpigroups"])
                && (
                    (
                        $this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION['glpigroups'])
                        && Session::haveRight(CommonITILTask::$rightname, CommonITILTask::ADDMY)
                    )
                    || (
                        $this->haveAGroup(CommonITILActor::OBSERVER, $_SESSION['glpigroups'])
                        && Session::haveRight(CommonITILTask::$rightname, CommonITILTask::ADD_AS_OBSERVER)
                    )
                )
            )
            || (
                Session::haveRight(CommonITILTask::$rightname, CommonITILTask::ADD_AS_TECHNICIAN)
                && (
                    $this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                    || (
                        isset($_SESSION["glpigroups"])
                        && $this->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])
                    )
                )
            )
        );
    }


    public function canMassiveAction($action, $field, $value)
    {

        switch ($action) {
            case 'update':
                switch ($field) {
                    case 'status':
                        if (!static::isAllowedStatus($this->fields['status'], (int) $value)) {
                            return false;
                        }
                        break;
                }
                break;
        }
        return true;
    }


    /**
     * Do the current ItilObject need to be reopened by a requester answer
     *
     * @since 10.0.1
     *
     * @return boolean
     */
    public function needReopen(): bool
    {
        $my_id    = Session::getLoginUserID();
        $my_groups = $_SESSION["glpigroups"] ?? [];

        // Compute requester groups
        $requester_groups = array_filter(
            $my_groups,
            fn($group) => $this->isGroup(CommonITILActor::REQUESTER, $group)
        );

        // Compute assigned groups
        $assigned_groups = array_filter(
            $my_groups,
            fn($group) => $this->isGroup(CommonITILActor::ASSIGN, $group)
        );

        $ami_requester       = $this->isUser(CommonITILActor::REQUESTER, $my_id);
        $ami_requester_group = count($requester_groups) > 0;

        $ami_assignee        = $this->isUser(CommonITILActor::ASSIGN, $my_id);
        $ami_assignee_group  = count($assigned_groups) > 0;

        return in_array($this->fields["status"], static::getReopenableStatusArray())
            && ($ami_requester || $ami_requester_group)
            && !($ami_assignee || $ami_assignee_group);
    }

    /**
     * Check if user validation is requested.
     *
     * @param int $users_id
     *
     * @return bool
     */
    final protected function isUserValidationRequested(int $users_id, bool $search_in_groups): bool
    {
        $validation = static::getValidationClassInstance();
        if ($validation === null) {
            // Object cannot be validated
            return false;
        }

        $validation_requests = $validation->find(
            [
                static::getForeignKeyField() => $this->getID(),
                $validation->getTargetCriteriaForUser($users_id, $search_in_groups),
            ]
        );

        return count($validation_requests) > 0;
    }


    /**
     * Does current user have right to solve the current item?
     *
     * @return boolean
     **/
    public function canSolve()
    {

        return ((Session::haveRight(static::$rightname, UPDATE)
               || $this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $this->haveAGroup(CommonITILActor::ASSIGN, $_SESSION["glpigroups"])))
              && static::isAllowedStatus($this->fields['status'], self::SOLVED)
              // No edition on closed status
              && !in_array($this->fields['status'], static::getClosedStatusArray()));
    }

    /**
     * Does current user have right to solve the current item; if it was not closed?
     *
     * @return boolean
     **/
    public function maySolve()
    {

        return ((Session::haveRight(static::$rightname, UPDATE)
               || $this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $this->haveAGroup(CommonITILActor::ASSIGN, $_SESSION["glpigroups"])))
              && static::isAllowedStatus($this->fields['status'], self::SOLVED));
    }


    /**
     * Get the ITIL object closed, solved or waiting status list
     *
     * @since 9.4.0
     *
     * @return array
     */
    public static function getReopenableStatusArray()
    {
        return [self::CLOSED, self::SOLVED, self::WAITING, self::OBSERVED];
    }


    /**
     * Is a user linked to the object ?
     *
     * @param integer $type     type to search (see constants)
     * @param integer $users_id user ID
     *
     * @return boolean
     **/
    public function isUser($type, $users_id)
    {

        if (isset($this->users[$type])) {
            foreach ($this->users[$type] as $data) {
                if ($data['users_id'] == $users_id) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Is a group linked to the object ?
     *
     * @param int $type      type to search (see constants)
     * @param int $groups_id group ID
     *
     * @return bool
     **/
    // FIXME add params typehint in GLPI 11.0
    public function isGroup($type, $groups_id): bool
    {
        if (isset($this->groups[$type])) {
            foreach ($this->groups[$type] as $data) {
                if ($data['groups_id'] == $groups_id) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Is a supplier linked to the object?
     *
     * @since 0.84
     *
     * @param integer $type         type to search (see constants)
     * @param integer $suppliers_id supplier ID
     *
     * @return boolean
     **/
    public function isSupplier($type, $suppliers_id)
    {

        if (isset($this->suppliers[$type])) {
            foreach ($this->suppliers[$type] as $data) {
                if ($data['suppliers_id'] == $suppliers_id) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * get users linked to a object
     *
     * @param integer $type type to search (see constants)
     *
     * @return array
     **/
    public function getUsers($type)
    {

        return $this->users[$type] ?? [];
    }


    /**
     * get groups linked to a object
     *
     * @param integer $type type to search (see constants)
     *
     * @return array
     **/
    public function getGroups($type)
    {

        return $this->groups[$type] ?? [];
    }


    /**
     * get users linked to an object including groups ones
     *
     * @since 0.85
     *
     * @param integer $type type to search (see constants)
     *
     * @return array
     **/
    public function getAllUsers($type)
    {

        $users = [];
        foreach ($this->getUsers($type) as $link) {
            $users[$link['users_id']] = $link['users_id'];
        }

        foreach ($this->getGroups($type) as $link) {
            $gusers = Group_User::getGroupUsers($link['groups_id']);
            foreach ($gusers as $user) {
                $users[$user['id']] = $user['id'];
            }
        }

        return $users;
    }


    /**
     * get suppliers linked to a object
     *
     * @since 0.84
     *
     * @param integer $type type to search (see constants)
     *
     * @return array
     **/
    public function getSuppliers($type)
    {

        return $this->suppliers[$type] ?? [];
    }


    /**
     * count users linked to object by type or global
     *
     * @param integer $type type to search (see constants) / 0 for all (default 0)
     *
     * @return integer
     **/
    public function countUsers($type = 0)
    {

        if ($type > 0) {
            if (isset($this->users[$type])) {
                return count($this->users[$type]);
            }
        } else {
            if (count($this->users)) {
                $count = 0;
                foreach ($this->users as $u) {
                    $count += count($u);
                }
                return $count;
            }
        }
        return 0;
    }


    /**
     * count groups linked to object by type or global
     *
     * @param integer $type type to search (see constants) / 0 for all (default 0)
     *
     * @return integer
     **/
    public function countGroups($type = 0)
    {

        if ($type > 0) {
            if (isset($this->groups[$type])) {
                return count($this->groups[$type]);
            }
        } else {
            if (count($this->groups)) {
                $count = 0;
                foreach ($this->groups as $u) {
                    $count += count($u);
                }
                return $count;
            }
        }
        return 0;
    }


    /**
     * count suppliers linked to object by type or global
     *
     * @since 0.84
     *
     * @param integer $type type to search (see constants) / 0 for all (default 0)
     *
     * @return integer
     **/
    public function countSuppliers($type = 0)
    {

        if ($type > 0) {
            if (isset($this->suppliers[$type])) {
                return count($this->suppliers[$type]);
            }
        } else {
            if (count($this->suppliers)) {
                $count = 0;
                foreach ($this->suppliers as $u) {
                    $count += count($u);
                }
                return $count;
            }
        }
        return 0;
    }


    /**
     * Is one of groups linked to the object ?
     *
     * @param integer $type   type to search (see constants)
     * @param array   $groups groups IDs
     *
     * @return boolean
     **/
    public function haveAGroup($type, array $groups)
    {

        if (count($groups) && isset($this->groups[$type])) {
            foreach ($groups as $groups_id) {
                foreach ($this->groups[$type] as $data) {
                    if ($data['groups_id'] == $groups_id) {
                        return true;
                    }
                }
            }
        }
        return false;
    }


    /**
     * Get Default actor when creating the object
     *
     * @param integer $type type to search (see constants)
     *
     * @return int
     **/
    public function getDefaultActor($type)
    {

        /// TODO own_ticket -> own_itilobject
        if ($type == CommonITILActor::ASSIGN) {
            if (
                Session::haveRight("ticket", Ticket::OWN)
                && $_SESSION['glpiset_default_tech']
            ) {
                return Session::getLoginUserID();
            }
        }
        if ($type == CommonITILActor::REQUESTER) {
            if (
                Session::haveRight(static::$rightname, CREATE)
                && $_SESSION['glpiset_default_requester']
            ) {
                return Session::getLoginUserID();
            }
        }
        return 0;
    }


    /**
     * Get Default actor when creating the object
     *
     * @param integer $type type to search (see constants)
     *
     * @return string
     **/
    public function getDefaultActorRightSearch($type)
    {

        if ($type == CommonITILActor::ASSIGN) {
            return "own_ticket";
        }
        return "all";
    }


    /**
     * Count active ITIL Objects
     *
     * @since 9.3.1
     *
     * @param CommonITILActor $linkclass Link class instance
     * @param integer         $id        Item ID
     * @param integer         $role      ITIL role
     *
     * @return integer
     **/
    private function countActiveObjectsFor(CommonITILActor $linkclass, $id, $role)
    {

        $itemtable = $this->getTable();
        $itemfk    = $this->getForeignKeyField();
        $linktable = $linkclass->getTable();
        $field     = $linkclass::$items_id_2;

        return countElementsInTable(
            [$itemtable, $linktable],
            [
                "$linktable.$itemfk"    => new QueryExpression(DBmysql::quoteName("$itemtable.id")),
                "$linktable.$field"     => $id,
                "$linktable.type"       => $role,
                "$itemtable.is_deleted" => 0,
                "NOT"                   => [
                    "$itemtable.status" => array_merge(
                        static::getSolvedStatusArray(),
                        static::getClosedStatusArray()
                    ),
                ],
            ] + getEntitiesRestrictCriteria($itemtable)
        );
    }




    /**
     * Count active ITIL Objects requested by a user
     *
     * @since 0.83
     *
     * @param integer $users_id ID of the User
     *
     * @return integer
     **/
    public function countActiveObjectsForUser($users_id)
    {
        $linkclass = $this->getActorObjectForItem(User::class);
        return $this->countActiveObjectsFor(
            $linkclass,
            $users_id,
            CommonITILActor::REQUESTER
        );
    }


    /**
     * Count active ITIL Objects having given user as observer.
     *
     * @param int $user_id
     *
     * @return int
     */
    final public function countActiveObjectsForObserverUser(int $user_id): int
    {
        $linkclass = $this->getActorObjectForItem(User::class);
        return $this->countActiveObjectsFor(
            $linkclass,
            $user_id,
            CommonITILActor::OBSERVER
        );
    }


    /**
     * Count active ITIL Objects assigned to a user
     *
     * @since 0.83
     *
     * @param integer $users_id ID of the User
     *
     * @return integer
     **/
    public function countActiveObjectsForTech($users_id)
    {
        $linkclass = $this->getActorObjectForItem(User::class);
        return $this->countActiveObjectsFor(
            $linkclass,
            $users_id,
            CommonITILActor::ASSIGN
        );
    }


    /**
     * Count active ITIL Objects having given group as requester.
     *
     * @param int $group_id
     *
     * @return int
     */
    final public function countActiveObjectsForRequesterGroup(int $group_id): int
    {
        $linkclass = $this->getActorObjectForItem(Group::class);
        return $this->countActiveObjectsFor(
            $linkclass,
            $group_id,
            CommonITILActor::REQUESTER
        );
    }


    /**
     * Count active ITIL Objects having given group as observer.
     *
     * @param int $group_id
     *
     * @return int
     */
    final public function countActiveObjectsForObserverGroup(int $group_id): int
    {
        $linkclass = $this->getActorObjectForItem(Group::class);
        return $this->countActiveObjectsFor(
            $linkclass,
            $group_id,
            CommonITILActor::OBSERVER
        );
    }


    /**
     * Count active ITIL Objects assigned to a group
     *
     * @since 0.84
     *
     * @param integer $groups_id ID of the User
     *
     * @return integer
     **/
    public function countActiveObjectsForTechGroup($groups_id)
    {
        $linkclass = $this->getActorObjectForItem(Group::class);
        return $this->countActiveObjectsFor(
            $linkclass,
            $groups_id,
            CommonITILActor::ASSIGN
        );
    }


    /**
     * Count active ITIL Objects assigned to a supplier
     *
     * @since 0.85
     *
     * @param integer $suppliers_id ID of the Supplier
     *
     * @return integer
     **/
    public function countActiveObjectsForSupplier($suppliers_id)
    {
        $linkclass = $this->getActorObjectForItem(Supplier::class);
        return $this->countActiveObjectsFor(
            $linkclass,
            $suppliers_id,
            CommonITILActor::ASSIGN
        );
    }

    /**
     * Get active or solved tickets for an hardware last X days
     *
     * @since 0.83
     *
     * @param $itemtype  string   Item type
     * @param $items_id  integer  ID of the Item
     * @param $days      integer  day number
     *
     * @return array
     **/
    public function getActiveOrSolvedLastDaysForItem($itemtype, $items_id, $days)
    {
        global $DB;

        $result = [];

        $class_l_pl = getPlural(strtolower(static::class));

        $iterator = $DB->request([
            'FROM'      => $this->getTable(),
            'LEFT JOIN' => [
                "glpi_items_{$class_l_pl}" => [
                    'ON' => [
                        "glpi_items_{$class_l_pl}" => "{$class_l_pl}_id",
                        $this->getTable()    => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_items_tickets.items_id' => $items_id,
                'glpi_items_tickets.itemtype' => $itemtype,
                'OR'                          => [
                    [
                        'NOT' => [
                            $this->getTable() . '.status' => array_merge(
                                static::getClosedStatusArray(),
                                static::getSolvedStatusArray()
                            ),
                        ],
                    ],
                    [
                        'NOT' => [$this->getTable() . '.solvedate' => null],
                        new QueryExpression(
                            QueryFunction::dateAdd(
                                date: static::getTable() . '.solvedate',
                                interval: $days,
                                interval_unit: 'DAY'
                            ) . ' > ' . QueryFunction::now()
                        ),
                    ],
                ],
            ],
        ]);

        foreach ($iterator as $tick) {
            $result[$tick['id']] = $tick['name'];
        }

        return $result;
    }

    public function cleanDBonPurge()
    {

        $link_classes = [
            Itil_Project::class,
            ITILFollowup::class,
            ITILSolution::class,
        ];

        if (is_a($this->grouplinkclass, CommonDBConnexity::class, true)) {
            $link_classes[] = $this->grouplinkclass;
        }

        if (is_a($this->userlinkclass, CommonDBConnexity::class, true)) {
            $link_classes[] = $this->userlinkclass;
        }

        if (is_a($this->supplierlinkclass, CommonDBConnexity::class, true)) {
            $link_classes[] = $this->supplierlinkclass;
        }

        $this->deleteChildrenAndRelationsFromDb($link_classes);
    }

    /**
     * Handle template mandatory fields on update
     *
     * @param array $input Input
     * @param bool  $show_error_message Show error message
     *
     * @return false|array
     */
    protected function handleTemplateFields(array $input, bool $show_error_message = true)
    {
        //// check mandatory fields
        // First get ticket template associated: entity and type/category
        $entid = $input['entities_id'] ?? $this->fields['entities_id'];

        $type = null;
        if (isset($input['type'])) {
            $type = $input['type'];
        } elseif (isset($this->fields['type'])) {
            $type = $this->fields['type'];
        }

        $categid = $input['itilcategories_id'] ?? $this->fields['itilcategories_id'];

        $check_allowed_fields_for_template = false;
        $allowed_fields                    = [];
        if (
            !Session::isCron()
            && (!Session::haveRight(static::$rightname, UPDATE)
            // Closed tickets
            || in_array($this->fields['status'], static::getClosedStatusArray()))
        ) {
            $allowed_fields                    = ['id'];
            $check_allowed_fields_for_template = true;

            if (in_array($this->fields['status'], static::getClosedStatusArray())) {
                $allowed_fields[] = 'status';

                // probably transfer
                $allowed_fields[] = 'entities_id';
                $allowed_fields[] = 'itilcategories_id';
                $allowed_fields[] = 'locations_id';
            } else {
                if (
                    $this->canApprove()
                    || $this->canAssign()
                    || $this->canAssignToMe()
                    || isset($input['_from_assignment'])
                ) {
                    $allowed_fields[] = 'status';
                    $allowed_fields[] = '_accepted';
                }
                $validation_class = static::getType() . 'Validation';
                if (
                    class_exists($validation_class)
                    && (
                        // for validation created by rules
                        // FIXME Use a more precise input name to ensure that 'global_validation' has been defined by rules
                        // e.g. $input['_validation_from_rule']
                        isset($input["_rule_process"])
                        // for validation status updated after CommonITILValidation add/update/delete
                        || (array_key_exists('_from_itilvalidation', $input) && $input['_from_itilvalidation'])
                    )
                ) {
                    $allowed_fields[] = 'global_validation';
                }
                // Manage assign and steal right
                if (static::getType() === Ticket::getType() && Session::haveRightsOr(static::$rightname, [Ticket::ASSIGN, Ticket::STEAL])) {
                    $allowed_fields[] = '_itil_assign';
                    $allowed_fields[] = '_users_id_assign';
                    $allowed_fields[] = '_groups_id_assign';
                    $allowed_fields[] = '_suppliers_id_assign';
                }

                // Can only update initial fields if no followup or task already added
                if ($this->canUpdateItem()) {
                    $allowed_fields[] = 'content';
                    $allowed_fields[] = 'urgency';
                    $allowed_fields[] = 'priority'; // automatic recalculate if user changes urgence
                    $allowed_fields[] = 'itilcategories_id';
                    $allowed_fields[] = 'name';
                    $allowed_fields[] = 'items_id';
                    $allowed_fields[] = '_filename';
                    $allowed_fields[] = '_tag_filename';
                    $allowed_fields[] = '_prefix_filename';
                    $allowed_fields[] = '_content';
                    $allowed_fields[] = '_tag_content';
                    $allowed_fields[] = '_prefix_content';
                    $allowed_fields[] = 'takeintoaccount_delay_stat';
                    $allowed_fields[] = 'takeintoaccountdate';
                }
            }

            $ret = [];

            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    $ret[$field] = $input[$field];
                }
            }

            $input = $ret;

            // Only ID return false
            if (count($input) == 1) {
                return false;
            }
        }

        $tt = $this->getITILTemplateToUse(0, $type, $categid, $entid);

        if (count($tt->mandatory)) {
            $mandatory_missing = [];
            $fieldsname        = $tt->getAllowedFieldsNames(true);
            foreach ($tt->mandatory as $key => $val) {
                if (
                    (!$check_allowed_fields_for_template || in_array($key, $allowed_fields))
                    && (
                        isset($input[$key])
                    && (empty($input[$key]) || ($input[$key] == 'NULL'))
                    )
                ) {
                    $mandatory_missing[$key] = $fieldsname[$val];
                }
            }
            if (count($mandatory_missing)) {
                //TRANS: %s are the fields concerned
                if ($show_error_message) {
                    $message = sprintf(
                        __('Mandatory fields are not filled. Please correct: %s'),
                        implode(", ", $mandatory_missing)
                    );
                    Session::addMessageAfterRedirect(htmlescape($message), false, ERROR);
                }
                return false;
            }
        }

        return $input;
    }

    /**
     * Checks if all required fields are filled
     *
     * @return bool
     */
    public function checkRequiredFieldsFilled(): bool
    {
        if (empty($this->fields) && $this->input === false) {
            return false;
        }
        $result = $this->handleTemplateFields($this->fields, false);
        if ($result === false) {
            return false;
        }
        return true;
    }

    protected function manageITILObjectLinkInput($input)
    {
        if (isset($input['_link'])) {
            $link = $input['_link'];

            if (isset($link['tickets_id_2'])) {
                Toolbox::deprecated();
                $link = [
                    'itemtype_1' => Ticket::class,
                    'items_id_1' => $link['tickets_id_1'] ?? 0,
                    'itemtype_2' => Ticket::class,
                    'items_id_2' => $link['tickets_id_2'],
                    'link'       => $link['link'] ?? CommonITILObject_CommonITILObject::LINK_TO,
                ];
            }

            if (!isset($link['itemtype_1'], $link['items_id_1'], $link['itemtype_2'], $link['items_id_2'], $link['link'])) {
                // Not enough data, ignore link silently
                return;
            }

            if ($link['itemtype_1'] == $this->getType() && $link['items_id_1'] == 0) {
                // Link was added in creation form, ID was not available yet
                $link['items_id_1'] = $this->getID();
            }

            if ((int) $link['items_id_1'] === 0 || (int) $link['items_id_2'] === 0) {
                // Not enough data, ignore link silently
                return;
            }

            $link_class = !empty($link['itemtype_1']) && !empty($link['itemtype_2'])
                ? CommonITILObject_CommonITILObject::getLinkClass($link['itemtype_1'], $link['itemtype_2'])
                : null;

            if ($link_class === null || !is_a($link_class, CommonITILObject_CommonITILObject::class, true)) {
                trigger_error(
                    sprintf('Invalid itemtypes "%s"/"%s" on ITIL objects link.', $link['itemtype_1'], $link['itemtype_2']),
                    E_USER_WARNING
                );
                return;
            }

            $itil_itil = new $link_class();

            $link = $itil_itil->normalizeInput($link);

            if ($itil_itil->can(-1, CREATE, $link) && $itil_itil->add($link)) {
                $input['_forcenotif'] = true;
            } else {
                Session::addMessageAfterRedirect(__s('Unknown ITIL Object'), false, ERROR);
            }
        }
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->handleInputDeprecations($input);

        if (!$this->checkFieldsConsistency($input)) {
            return false;
        }

        // Add document if needed
        $this->getFromDB($input["id"]); // entities_id field required

        // Map unique template field to template foreign key
        // Leave original field. The new field is stored in the DB, while the original is used for everything else (left for BC)
        if (isset($input[static::getTemplateFormFieldName()]) && (int) $input[static::getTemplateFormFieldName()] > 0) {
            $tpl_class = static::getTemplateClass();
            $input[$tpl_class::getForeignKeyField()] = (int) $input[static::getTemplateFormFieldName()];
        }

        if ($this->getType() !== Ticket::getType()) {
            //cannot be handled here for tickets. @see Ticket::prepareInputForUpdate()
            $input = $this->handleTemplateFields($input);
            if ($input === false) {
                return false;
            }
        }

        $location_id = $input['locations_id'] ?? 0;
        $location_id = (int) $location_id;
        if ($location_id > 0 && ($location = Location::getById($location_id)) !== false) {
            $input['_locations_code'] = $location->fields['code'];
        }

        if (isset($input["document"]) && ($input["document"] > 0)) {
            $doc = new Document();
            if ($doc->getFromDB($input["document"])) {
                $docitem = new Document_Item();
                if (
                    $docitem->add(['documents_id' => $input["document"],
                        'itemtype'     => $this->getType(),
                        'items_id'     => $input["id"],
                    ])
                ) {
                    // Force date_mod of tracking
                    $input["date_mod"]     = $_SESSION["glpi_currenttime"];
                    $input['_doc_added'][] = $doc->fields["name"];
                }
            }
            unset($input["document"]);
        }

        if (isset($input["date"]) && empty($input["date"])) {
            unset($input["date"]);
        }

        if (isset($input["closedate"]) && empty($input["closedate"])) {
            unset($input["closedate"]);
        }

        if (isset($input["solvedate"]) && empty($input["solvedate"])) {
            unset($input["solvedate"]);
        }

        // "do not compute" flag set by business rules for "takeintoaccount_delay_stat" field
        $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($input);

        if (isset($input['_itil_requester'])) {
            // FIXME Deprecate this input key in GLPI 11.0.
            if (isset($input['_itil_requester']['_type'])) {
                $input['_itil_requester'] = [
                    'type'                            => CommonITILActor::REQUESTER,
                    $this->getForeignKeyField()       => $input['id'],
                    '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                    '_from_object'                    => true,
                ] + $input['_itil_requester'];

                switch ($input['_itil_requester']['_type']) {
                    case "user":
                        if (
                            isset($input['_itil_requester']['use_notification'])
                            && is_array($input['_itil_requester']['use_notification'])
                        ) {
                            $input['_itil_requester']['use_notification'] = $input['_itil_requester']['use_notification'][0];
                        }
                        if (
                            isset($input['_itil_requester']['alternative_email'])
                            && is_array($input['_itil_requester']['alternative_email'])
                        ) {
                            $input['_itil_requester']['alternative_email'] = $input['_itil_requester']['alternative_email'][0];
                        }

                        if (!empty($this->userlinkclass)) {
                            if (
                                isset($input['_itil_requester']['alternative_email'])
                                && $input['_itil_requester']['alternative_email']
                                && !NotificationMailing::isUserAddressValid($input['_itil_requester']['alternative_email'])
                            ) {
                                $input['_itil_requester']['alternative_email'] = '';
                                Session::addMessageAfterRedirect(__s('Invalid email address'), false, ERROR);
                            }

                            if (
                                (isset($input['_itil_requester']['alternative_email'])
                                && $input['_itil_requester']['alternative_email'])
                                || ($input['_itil_requester']['users_id'] > 0)
                            ) {
                                $useractors = $this->getActorObjectForItem(User::class);
                                if (
                                    isset($input['_auto_update'])
                                    || $useractors->can(-1, CREATE, $input['_itil_requester'])
                                ) {
                                    $useractors->add($input['_itil_requester']);
                                    $input['_forcenotif']                     = true;
                                }
                            }
                        }
                        break;

                    case "group":
                        if (
                            !empty($this->grouplinkclass)
                            && ($input['_itil_requester']['groups_id'] > 0)
                        ) {
                            $groupactors = $this->getActorObjectForItem(Group::class);
                            if (
                                isset($input['_auto_update'])
                                || $groupactors->can(-1, CREATE, $input['_itil_requester'])
                            ) {
                                $groupactors->add($input['_itil_requester']);
                                $input['_forcenotif']                     = true;
                            }
                        }
                        break;
                }
            }
        }

        if (isset($input['_itil_observer'])) {
            // FIXME Deprecate this input key in GLPI 11.0.
            if (isset($input['_itil_observer']['_type'])) {
                $input['_itil_observer'] = [
                    'type'                            => CommonITILActor::OBSERVER,
                    $this->getForeignKeyField()       => $input['id'],
                    '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                    '_from_object'                    => true,
                ] + $input['_itil_observer'];

                switch ($input['_itil_observer']['_type']) {
                    case "user":
                        if (
                            isset($input['_itil_observer']['use_notification'])
                            && is_array($input['_itil_observer']['use_notification'])
                        ) {
                            $input['_itil_observer']['use_notification'] = $input['_itil_observer']['use_notification'][0];
                        }
                        if (
                            isset($input['_itil_observer']['alternative_email'])
                            && is_array($input['_itil_observer']['alternative_email'])
                        ) {
                            $input['_itil_observer']['alternative_email'] = $input['_itil_observer']['alternative_email'][0];
                        }

                        if (!empty($this->userlinkclass)) {
                            if (
                                isset($input['_itil_observer']['alternative_email'])
                                && $input['_itil_observer']['alternative_email']
                                && !NotificationMailing::isUserAddressValid($input['_itil_observer']['alternative_email'])
                            ) {
                                $input['_itil_observer']['alternative_email'] = '';
                                Session::addMessageAfterRedirect(__s('Invalid email address'), false, ERROR);
                            }
                            if (
                                (isset($input['_itil_observer']['alternative_email'])
                                && $input['_itil_observer']['alternative_email'])
                                || ($input['_itil_observer']['users_id'] > 0)
                            ) {
                                $useractors = $this->getActorObjectForItem(User::class);
                                if (
                                    isset($input['_auto_update'])
                                    || $useractors->can(-1, CREATE, $input['_itil_observer'])
                                ) {
                                    $useractors->add($input['_itil_observer']);
                                    $input['_forcenotif']                    = true;
                                }
                            }
                        }
                        break;

                    case "group":
                        if (
                            !empty($this->grouplinkclass)
                            && ($input['_itil_observer']['groups_id'] > 0)
                        ) {
                            $groupactors = $this->getActorObjectForItem(Group::class);
                            if (
                                isset($input['_auto_update'])
                                || $groupactors->can(-1, CREATE, $input['_itil_observer'])
                            ) {
                                $groupactors->add($input['_itil_observer']);
                                $input['_forcenotif']                    = true;
                            }
                        }
                        break;
                }
            }
        }

        if (isset($input['_itil_assign'])) {
            // FIXME Deprecate this input key in GLPI 11.0.
            if (isset($input['_itil_assign']['_type'])) {
                $input['_itil_assign'] = [
                    'type'                            => CommonITILActor::ASSIGN,
                    $this->getForeignKeyField()       => $input['id'],
                    '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                    '_from_object'                    => true,
                ] + $input['_itil_assign'];

                if (
                    isset($input['_itil_assign']['use_notification'])
                    && is_array($input['_itil_assign']['use_notification'])
                ) {
                    $input['_itil_assign']['use_notification'] = $input['_itil_assign']['use_notification'][0];
                }
                if (
                    isset($input['_itil_assign']['alternative_email'])
                    && is_array($input['_itil_assign']['alternative_email'])
                ) {
                    $input['_itil_assign']['alternative_email'] = $input['_itil_assign']['alternative_email'][0];
                }

                switch ($input['_itil_assign']['_type']) {
                    case "user":
                        if (
                            !empty($this->userlinkclass)
                            && ((isset($input['_itil_assign']['alternative_email'])
                            && $input['_itil_assign']['alternative_email'])
                            || $input['_itil_assign']['users_id'] > 0)
                        ) {
                            $useractors = $this->getActorObjectForItem(User::class);
                            if (
                                isset($input['_auto_update'])
                                || $useractors->can(-1, CREATE, $input['_itil_assign'])
                            ) {
                                $useractors->add($input['_itil_assign']);
                                $input['_forcenotif']                  = true;
                                if (
                                    ((!isset($input['status'])
                                    && in_array($this->fields['status'], static::getNewStatusArray()))
                                    || (isset($input['status'])
                                    && in_array($input['status'], static::getNewStatusArray())))
                                    && !$this->isStatusComputationBlocked($input)
                                ) {
                                    if (in_array(self::ASSIGNED, array_keys(static::getAllStatusArray()))) {
                                        $input['status'] = self::ASSIGNED;
                                    }
                                }
                            }
                        }
                        break;

                    case "group":
                        if (
                            !empty($this->grouplinkclass)
                            && ($input['_itil_assign']['groups_id'] > 0)
                        ) {
                            $groupactors = $this->getActorObjectForItem(Group::class);

                            if (
                                isset($input['_auto_update'])
                                || $groupactors->can(-1, CREATE, $input['_itil_assign'])
                            ) {
                                $groupactors->add($input['_itil_assign']);
                                $input['_forcenotif']                  = true;
                                if (
                                    ((!isset($input['status'])
                                    && (in_array($this->fields['status'], static::getNewStatusArray())))
                                    || (isset($input['status'])
                                    && (in_array($input['status'], static::getNewStatusArray()))))
                                    && !$this->isStatusComputationBlocked($input)
                                ) {
                                    if (in_array(self::ASSIGNED, array_keys(static::getAllStatusArray()))) {
                                        $input['status'] = self::ASSIGNED;
                                    }
                                }
                            }
                        }
                        break;

                    case "supplier":
                        if (
                            !empty($this->supplierlinkclass)
                            && ((isset($input['_itil_assign']['alternative_email'])
                            && $input['_itil_assign']['alternative_email'])
                            || $input['_itil_assign']['suppliers_id'] > 0)
                        ) {
                            $supplieractors = $this->getActorObjectForItem(Supplier::class);
                            if (
                                isset($input['_auto_update'])
                                || $supplieractors->can(-1, CREATE, $input['_itil_assign'])
                            ) {
                                $supplieractors->add($input['_itil_assign']);
                                $input['_forcenotif']                  = true;
                                if (
                                    ((!isset($input['status'])
                                    && (in_array($this->fields['status'], static::getNewStatusArray())))
                                    || (isset($input['status'])
                                    && (in_array($input['status'], static::getNewStatusArray()))))
                                    && !$this->isStatusComputationBlocked($input)
                                ) {
                                    if (in_array(self::ASSIGNED, array_keys(static::getAllStatusArray()))) {
                                        $input['status'] = self::ASSIGNED;
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }

        // set last updater if interactive user
        if (!Session::isCron()) {
            $input['users_id_lastupdater'] = Session::getLoginUserID();
        }

        $solvedclosed = array_merge(
            static::getSolvedStatusArray(),
            static::getClosedStatusArray()
        );

        if (
            isset($input["status"])
            && !in_array($input["status"], $solvedclosed)
        ) {
            $input['solvedate'] = 'NULL';
        }

        if (isset($input["status"]) && !in_array($input["status"], static::getClosedStatusArray())) {
            $input['closedate'] = 'NULL';
        }

        // Setting a solution type means the ticket is solved
        if (
            isset($input["solutiontypes_id"])
            && (!isset($input['status']) || !in_array($input["status"], $solvedclosed))
        ) {
            $solution = new ITILSolution();
            $soltype = new SolutionType();
            $soltype->getFromDB($input['solutiontypes_id']);
            $solution->add([
                'itemtype'           => $this->getType(),
                'items_id'           => $this->getID(),
                'solutiontypes_id'   => $input['solutiontypes_id'],
                'content'            => 'Solved using type ' . $soltype->getName(),
            ]);
        }

        // If status changed from pending to anything else, remove pending reason
        if (
            (
                isset($this->input["status"])
                && $this->input["status"] != self::WAITING
            ) || (
                isset($input["status"])
                && $input["status"] != self::WAITING
            )
        ) {
            PendingReason_Item::deleteForItem($this);
        }

        return $input;
    }

    public function post_updateItem($history = true)
    {
        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, ['force_update' => true]);

        // handle actors changes
        $this->updateActors();

        // handle items linking
        $this->handleItemsIdInput();

        // Handle "_tasktemplates_id" special input
        $this->handleTaskTemplateInput();

        // Handle "_itilfollowuptemplates_id" special input
        $this->handleITILFollowupTemplateInput();

        // Handle "_solutiontemplates_id" special input
        $this->handleSolutionTemplateInput();

        // Handle validation requests
        $this->handleValidationStepThresholdInput($this->input);
        $this->manageValidationAdd($this->input);

        $this->manageITILObjectLinkInput($this->input);

        parent::post_updateItem();
    }


    public function pre_updateInDB()
    {
        global $DB;

        // get again object to reload actors
        $this->loadActors();

        // Check dates change interval because seconds are not displayed in form
        if (
            (($key = array_search('date', $this->updates)) !== false)
            && (substr($this->fields["date"], 0, 16) === substr($this->oldvalues['date'], 0, 16))
        ) {
            unset($this->updates[$key]);
            unset($this->oldvalues['date']);
        }

        if (
            (($key = array_search('closedate', $this->updates)) !== false)
            && isset($this->oldvalues['closedate'])
            && (substr($this->fields["closedate"], 0, 16) === substr($this->oldvalues['closedate'], 0, 16))
        ) {
            unset($this->updates[$key]);
            unset($this->oldvalues['closedate']);
        }

        if (
            (($key = array_search('time_to_resolve', $this->updates)) !== false)
            && isset($this->oldvalues['time_to_resolve'])
            && (substr($this->fields["time_to_resolve"], 0, 16) === substr($this->oldvalues['time_to_resolve'], 0, 16))
        ) {
            unset($this->updates[$key]);
            unset($this->oldvalues['time_to_resolve']);
        }

        if (
            (($key = array_search('solvedate', $this->updates)) !== false)
            && isset($this->oldvalues['solvedate'])
            && (substr($this->fields["solvedate"], 0, 16) === substr($this->oldvalues['solvedate'], 0, 16))
        ) {
            unset($this->updates[$key]);
            unset($this->oldvalues['solvedate']);
        }

        if (isset($this->input["status"])) {
            // status changed to solved
            if (
                in_array("status", $this->updates)
                && in_array($this->input["status"], static::getSolvedStatusArray())
            ) {
                $this->updates[]              = "solvedate";
                $this->oldvalues['solvedate'] = $this->fields["solvedate"];
                $this->fields["solvedate"]    = $_SESSION["glpi_currenttime"];
                // If invalid date : set open date
                if ($this->fields["solvedate"] < $this->fields["date"]) {
                    $this->fields["solvedate"] = $this->fields["date"];
                }
            }

            // status changed to closed
            if (
                in_array("status", $this->updates)
                && in_array($this->input["status"], static::getClosedStatusArray())
            ) {
                $this->updates[]              = "closedate";
                $this->oldvalues['closedate'] = $this->fields["closedate"];
                $this->fields["closedate"]    = $_SESSION["glpi_currenttime"];
                // If invalid date : set open date
                if ($this->fields["closedate"] < $this->fields["date"]) {
                    $this->fields["closedate"] = $this->fields["date"];
                }
                // Set solvedate to closedate
                if (empty($this->fields["solvedate"])) {
                    $this->updates[]              = "solvedate";
                    $this->oldvalues['solvedate'] = $this->fields["solvedate"];
                    $this->fields["solvedate"]    = $this->fields["closedate"];
                }
            }
        }

        // check dates

        // check time_to_resolve (SLA)
        if (
            (in_array("date", $this->updates) || in_array("time_to_resolve", $this->updates))
            && !is_null($this->fields["time_to_resolve"])
        ) { // Date set
            if ($this->fields["time_to_resolve"] < $this->fields["date"]) {
                Session::addMessageAfterRedirect(__s('Invalid dates. Update cancelled.'), false, ERROR);

                if (($key = array_search('date', $this->updates)) !== false) {
                    unset($this->updates[$key]);
                    unset($this->oldvalues['date']);
                }
                if (($key = array_search('time_to_resolve', $this->updates)) !== false) {
                    unset($this->updates[$key]);
                    unset($this->oldvalues['time_to_resolve']);
                }
            }
        }

        // check internal_time_to_resolve (OLA)
        if (
            (in_array("date", $this->updates) || in_array("internal_time_to_resolve", $this->updates))
            && !is_null($this->fields["internal_time_to_resolve"])
        ) { // Date set
            if ($this->fields["internal_time_to_resolve"] < $this->fields["date"]) {
                Session::addMessageAfterRedirect(__s('Invalid dates. Update cancelled.'), false, ERROR);

                if (($key = array_search('date', $this->updates)) !== false) {
                    unset($this->updates[$key]);
                    unset($this->oldvalues['date']);
                }
                if (($key = array_search('internal_time_to_resolve', $this->updates)) !== false) {
                    unset($this->updates[$key]);
                    unset($this->oldvalues['internal_time_to_resolve']);
                }
            }
        }

        // Status close: check dates
        if (
            in_array($this->fields["status"], static::getClosedStatusArray())
            && (in_array("date", $this->updates) || in_array("closedate", $this->updates))
        ) {
            // Invalid dates : no change
            // closedate must be > solvedate
            if ($this->fields["closedate"] < $this->fields["solvedate"]) {
                Session::addMessageAfterRedirect(__s('Invalid dates. Update cancelled.'), false, ERROR);

                if (($key = array_search('closedate', $this->updates)) !== false) {
                    unset($this->updates[$key]);
                    unset($this->oldvalues['closedate']);
                }
            }

            // closedate must be > create date
            if ($this->fields["closedate"] < $this->fields["date"]) {
                Session::addMessageAfterRedirect(__s('Invalid dates. Update cancelled.'), false, ERROR);
                if (($key = array_search('date', $this->updates)) !== false) {
                    unset($this->updates[$key]);
                    unset($this->oldvalues['date']);
                }
                if (($key = array_search('closedate', $this->updates)) !== false) {
                    unset($this->updates[$key]);
                    unset($this->oldvalues['closedate']);
                }
            }
        }

        if (
            (($key = array_search('status', $this->updates)) !== false)
            && $this->oldvalues['status'] == $this->fields['status']
        ) {
            unset($this->updates[$key]);
            unset($this->oldvalues['status']);
        }

        // Status solved: check dates
        if (
            in_array($this->fields["status"], static::getSolvedStatusArray())
            && (in_array("date", $this->updates) || in_array("solvedate", $this->updates))
        ) {
            // Invalid dates : no change
            // solvedate must be > create date
            if ($this->fields["solvedate"] < $this->fields["date"]) {
                Session::addMessageAfterRedirect(__s('Invalid dates. Update cancelled.'), false, ERROR);

                if (($key = array_search('date', $this->updates)) !== false) {
                    unset($this->updates[$key]);
                    unset($this->oldvalues['date']);
                }
                if (($key = array_search('solvedate', $this->updates)) !== false) {
                    unset($this->updates[$key]);
                    unset($this->oldvalues['solvedate']);
                }
            }
        }

        // Manage come back to waiting state
        if (
            !is_null($this->fields['begin_waiting_date'])
            && ($key = array_search('status', $this->updates)) !== false
            && (
                $this->oldvalues['status'] == self::WAITING
            // From solved to another state than closed
            || (
                in_array($this->oldvalues["status"], static::getSolvedStatusArray())
               && !in_array($this->fields["status"], static::getClosedStatusArray())
            )
            // From closed to any open state
            || (
                in_array($this->oldvalues["status"], static::getClosedStatusArray())
               && in_array($this->fields["status"], static::getNotSolvedStatusArray())
            )
            )
        ) {
            // Compute ticket waiting time use calendar if exists
            $calendar     = new Calendar();
            $calendars_id = $this->getCalendar();
            $delay_time   = 0;

            // Compute ticket waiting time use calendar if exists
            // Using calendar
            if (
                ($calendars_id > 0)
                && $calendar->getFromDB($calendars_id)
            ) {
                $delay_time = $calendar->getActiveTimeBetween(
                    $this->fields['begin_waiting_date'],
                    $_SESSION["glpi_currenttime"]
                );
            } else { // Not calendar defined
                $delay_time = strtotime($_SESSION["glpi_currenttime"])
                           - strtotime($this->fields['begin_waiting_date']);
            }

            // SLA case: compute sla_ttr duration
            if (isset($this->fields['slas_id_ttr']) && ($this->fields['slas_id_ttr'] > 0)) {
                $sla = new SLA();
                if ($sla->getFromDB($this->fields['slas_id_ttr'])) {
                    $sla->setTicketCalendar($calendars_id);
                    $delay_time_sla  = $sla->getActiveTimeBetween(
                        $this->fields['begin_waiting_date'],
                        $_SESSION["glpi_currenttime"]
                    );
                    $this->updates[] = "sla_waiting_duration";
                    $this->fields["sla_waiting_duration"] += $delay_time_sla;
                }

                // Compute new time_to_resolve
                $this->updates[]                 = "time_to_resolve";
                $this->fields['time_to_resolve'] = $sla->computeDate(
                    $this->fields['date'],
                    $this->fields["sla_waiting_duration"]
                );
                // Add current level to do
                if ($this instanceof Ticket) { // TODO: rewrite with polymorphism...
                    $sla->addLevelToDo($this);
                }
            } else {
                // Using calendar
                if (
                    ($calendars_id > 0)
                    && $calendar->getFromDB($calendars_id)
                    && $calendar->hasAWorkingDay()
                ) {
                    if ((int) $this->fields['time_to_resolve'] > 0) {
                        // compute new due date using calendar
                        $this->updates[]                 = "time_to_resolve";
                        $this->fields['time_to_resolve'] = $calendar->computeEndDate(
                            $this->fields['time_to_resolve'],
                            $delay_time
                        );
                    }
                } else { // Not calendar defined
                    if ((int) $this->fields['time_to_resolve'] > 0) {
                        // compute new due date: no calendar so add computed delay_time
                        $this->updates[]                 = "time_to_resolve";
                        $this->fields['time_to_resolve'] = date(
                            'Y-m-d H:i:s',
                            $delay_time + strtotime($this->fields['time_to_resolve'])
                        );
                    }
                }
            }

            // OLA case: compute ola_ttr duration
            if (isset($this->fields['olas_id_ttr']) && ($this->fields['olas_id_ttr'] > 0)) {
                $ola = new OLA();
                if ($ola->getFromDB($this->fields['olas_id_ttr'])) {
                    $ola->setTicketCalendar($calendars_id);
                    $delay_time_ola  = $ola->getActiveTimeBetween(
                        $this->fields['begin_waiting_date'],
                        $_SESSION["glpi_currenttime"]
                    );
                    $this->updates[]                      = "ola_waiting_duration";
                    $this->fields["ola_waiting_duration"] += $delay_time_ola;
                }

                // Compute new internal_time_to_resolve
                $this->updates[]                          = "internal_time_to_resolve";
                $this->fields['internal_time_to_resolve'] = $ola->computeDate(
                    $this->fields['ola_ttr_begin_date'],
                    $this->fields["ola_waiting_duration"]
                );
                // Add current level to do
                if ($this instanceof Ticket) { // TODO: rewrite with polymorphism...
                    $ola->addLevelToDo($this, $this->fields["olalevels_id_ttr"]);
                }
            } elseif (array_key_exists("internal_time_to_resolve", $this->fields)) {
                // Change doesn't have internal_time_to_resolve
                // Using calendar
                if (
                    ($calendars_id > 0)
                    && $calendar->getFromDB($calendars_id)
                    && $calendar->hasAWorkingDay()
                ) {
                    if ((int) $this->fields['internal_time_to_resolve'] > 0) {
                        // compute new internal_time_to_resolve using calendar
                        $this->updates[]                          = "internal_time_to_resolve";
                        $this->fields['internal_time_to_resolve'] = $calendar->computeEndDate(
                            $this->fields['internal_time_to_resolve'],
                            $delay_time
                        );
                    }
                } else { // Not calendar defined
                    if ((int) $this->fields['internal_time_to_resolve'] > 0) {
                        // compute new internal_time_to_resolve: no calendar so add computed delay_time
                        $this->updates[]                          = "internal_time_to_resolve";
                        $this->fields['internal_time_to_resolve'] = date(
                            'Y-m-d H:i:s',
                            $delay_time
                            + strtotime($this->fields['internal_time_to_resolve'])
                        );
                    }
                }
            }

            $this->updates[]                   = "waiting_duration";
            $this->fields["waiting_duration"] += $delay_time;

            // Reset begin_waiting_date
            $this->updates[]                    = "begin_waiting_date";
            $this->fields["begin_waiting_date"] = 'NULL';
        }

        // Set begin waiting date if needed
        if (
            (($key = array_search('status', $this->updates)) !== false)
            && (($this->fields['status'] == self::WAITING)
              || in_array($this->fields["status"], static::getSolvedStatusArray()))
        ) {
            $this->updates[]                    = "begin_waiting_date";
            $this->fields["begin_waiting_date"] = $_SESSION["glpi_currenttime"];

            // Specific for tickets
            if ($this instanceof Ticket) { // TODO: rewrite with polymorphism...
                if (isset($this->fields['slas_id_ttr']) && ($this->fields['slas_id_ttr'] > 0)) {
                    SLA::deleteLevelsToDo($this);
                }

                if (isset($this->fields['olas_id_ttr']) && ($this->fields['olas_id_ttr'] > 0)) {
                    OLA::deleteLevelsToDo($this);
                }
            }
        }

        // solve_delay_stat: use delay between opendate and solvedate
        if (in_array("solvedate", $this->updates)) {
            $this->updates[]                  = "solve_delay_stat";
            $this->fields['solve_delay_stat'] = $this->computeSolveDelayStat();
        }
        // close_delay_stat : use delay between opendate and closedate
        if (in_array("closedate", $this->updates)) {
            $this->updates[]                  = "close_delay_stat";
            $this->fields['close_delay_stat'] = $this->computeCloseDelayStat();
        }

        //Look for reopening
        $statuses = array_merge(
            static::getSolvedStatusArray(),
            static::getClosedStatusArray()
        );
        if (
            ($key = array_search('status', $this->updates)) !== false
            && in_array($this->oldvalues['status'], $statuses)
            && !in_array($this->fields['status'], $statuses)
        ) {
            $users_id_reject = 0;
            // set last updater if interactive user
            if (!Session::isCron()) {
                $users_id_reject = Session::getLoginUserID();
            }

            //Mark existing solutions as refused
            $DB->update(
                ITILSolution::getTable(),
                [
                    'status'             => CommonITILValidation::REFUSED,
                    'users_id_approval'  => $users_id_reject,
                    'date_approval'      => date('Y-m-d H:i:s'),
                ],
                [
                    'WHERE'  => [
                        'itemtype'  => static::getType(),
                        'items_id'  => $this->getID(),
                    ],
                    'ORDER'  => [
                        'date_creation DESC',
                        'id DESC',
                    ],
                    'LIMIT'  => 1,
                ]
            );

            //Delete existing survey
            $inquest = new TicketSatisfaction();
            $inquest->delete(['tickets_id' => $this->getID()]);
        }

        if (isset($this->input['_accepted'])) {
            //Mark last solution as approved
            $DB->update(
                ITILSolution::getTable(),
                [
                    'status'             => CommonITILValidation::ACCEPTED,
                    'users_id_approval'  => Session::getLoginUserID(),
                    'date_approval'      => date('Y-m-d H:i:s'),
                ],
                [
                    'WHERE'  => [
                        'itemtype'  => static::getType(),
                        'items_id'  => $this->getID(),
                    ],
                    'ORDER'  => [
                        'date_creation DESC',
                        'id DESC',
                    ],
                    'LIMIT'  => 1,
                ]
            );
        }

        // Do not take into account date_mod if no update is done
        if (
            (count($this->updates) == 1)
            && (($key = array_search('date_mod', $this->updates)) !== false)
        ) {
            unset($this->updates[$key]);
        }
    }


    public function prepareInputForAdd($input)
    {
        global $CFG_GLPI;

        $input = $this->handleInputDeprecations($input);

        if (!$this->checkFieldsConsistency($input)) {
            return false;
        }

        $input = $this->transformActorsInput($input);

        // Map unique template field to template foreign key
        // Leave original field. The new field is stored in the DB, while the original is used for everything else (left for BC)
        if (isset($input[static::getTemplateFormFieldName()]) && (int) $input[static::getTemplateFormFieldName()] > 0) {
            $tpl_class = static::getTemplateClass();
            $input[$tpl_class::getForeignKeyField()] = (int) $input[static::getTemplateFormFieldName()];
        }

        // save value before clean;
        $title = ltrim($input['name']);

        // Set default status to avoid notice
        if (!isset($input["status"])) {
            $input["status"] = self::INCOMING;
        }

        if (
            !isset($input["urgency"])
            || !($CFG_GLPI['urgency_mask'] & (1 << $input["urgency"]))
        ) {
            $input["urgency"] = 3;
        }
        if (
            !isset($input["impact"])
            || !($CFG_GLPI['impact_mask'] & (1 << $input["impact"]))
        ) {
            $input["impact"] = 3;
        }

        $cat_id = $input['itilcategories_id'] ?? 0;
        if ($cat_id) {
            $input['itilcategories_id_code'] = ITILCategory::getById($cat_id)->fields['code'];
        }

        $location_id = $input['locations_id'] ?? 0;
        $location_id = (int) $location_id;
        if ($location_id > 0 && ($location = Location::getById($location_id)) !== false) {
            $input['_locations_code'] = $location->fields['code'];
        }

        $canpriority = true;
        if ($this->getType() == 'Ticket') {
            $canpriority = Session::haveRight(Ticket::$rightname, Ticket::CHANGEPRIORITY);
        }

        if ($canpriority && !isset($input["priority"]) || !$canpriority) {
            $input["priority"] = static::computePriority($input["urgency"], $input["impact"]);
        }

        // set last updater if interactive user
        if (!Session::isCron() && ($last_updater = Session::getLoginUserID(true))) {
            $input['users_id_lastupdater'] = $last_updater;
        }

        if (!isset($input['_skip_auto_assign']) || $input['_skip_auto_assign'] === false) {
            // No Auto set Import for external source
            if (
                ($uid = Session::getLoginUserID())
                && !isset($input['_auto_import'])
            ) {
                $input["users_id_recipient"] = $uid;
            } elseif (
                !empty($input["_users_id_requester"])
                && !is_array($input['_users_id_requester'])
                && !isset($input["users_id_recipient"])
            ) {
                $input["users_id_recipient"] = $input["_users_id_requester"];
            }
        }

        // No name set name
        $input["name"]    = ltrim($input["name"]);
        $input['content'] = ltrim($input['content']);
        if (empty($input["name"])) {
            // Build name based on content

            // Get unformatted text
            $name = RichText::getTextFromHtml($input['content'], false);

            // Shorten result
            $name = Toolbox::substr(preg_replace('/\s{2,}/', ' ', $name), 0, 70);

            $input['name'] = $name;
        }

        // Set default dropdown
        $dropdown_fields = ['entities_id', 'itilcategories_id'];
        foreach ($dropdown_fields as $field) {
            if (!isset($input[$field])) {
                $input[$field] = 0;
            }
        }

        $input = $this->computeDefaultValuesForAdd($input);

        // Do not check mandatory on auto import (mailgates)
        $key = static::getTemplateFormFieldName();
        if (!isset($input['_auto_import'])) {
            if (isset($input[$key]) && $input[$key]) {
                $tt_class = static::getTemplateClass();
                $tt = getItemForItemtype($tt_class);
                if ($tt->getFromDBWithData($input[$key])) {
                    if (count($tt->mandatory)) {
                        $mandatory_missing = [];
                        $fieldsname        = $tt->getAllowedFieldsNames(true);
                        foreach ($tt->mandatory as $key => $val) {
                            // for title if mandatory (restore initial value)
                            if ($key == 'name') {
                                $input['name']                     = $title;
                            }
                            // Check only defined values: Not defined not in form
                            if (isset($input[$key])) {
                                // If content is also predefined need to be different from predefined value
                                if (
                                    ($key == 'content')
                                    && isset($tt->predefined['content'])
                                ) {
                                    $predefined_content = $tt->predefined['content'];
                                    // Clean new lines to be fix encoding
                                    if (
                                        strcmp(
                                            preg_replace(
                                                "/\r?\n/",
                                                "",
                                                $input[$key]
                                            ),
                                            preg_replace(
                                                "/\r?\n/",
                                                "",
                                                $predefined_content
                                            )
                                        ) == 0
                                    ) {
                                        Session::addMessageAfterRedirect(
                                            __s('You cannot use predefined description verbatim'),
                                            false,
                                            ERROR
                                        );
                                        $mandatory_missing[$key] = $fieldsname[$val];
                                    }
                                }

                                if (
                                    empty($input[$key]) || ($input[$key] == 'NULL')
                                    || (is_array($input[$key])
                                    && ($input[$key] === [0 => "0"]))
                                ) {
                                    $mandatory_missing[$key] = $fieldsname[$val];
                                }
                            }

                            if (
                                ($key == '_add_validation')
                                && !empty($input['_validation_targets'])
                                && isset($input['_validation_targets'][0]['itemtype_target'], $input['_validation_targets'][0]['items_id_target'])
                                && class_exists($input['_validation_targets'][0]['itemtype_target'])
                                && ($input['_validation_targets'][0]['items_id_target'] > 0)
                            ) {
                                unset($mandatory_missing['_add_validation']);
                            }

                            if (static::getType() === Ticket::getType()) {
                                // For time_to_resolve and time_to_own : check also slas
                                // For internal_time_to_resolve and internal_time_to_own : check also olas
                                foreach ([SLM::TTR, SLM::TTO] as $slmType) {
                                    [$dateField, $slaField] = SLA::getFieldNames($slmType);
                                    if (
                                        ($key == $dateField)
                                        && isset($input[$slaField]) && ($input[$slaField] > 0)
                                        && isset($mandatory_missing[$dateField])
                                    ) {
                                        unset($mandatory_missing[$dateField]);
                                    }
                                    [$dateField, $olaField] = OLA::getFieldNames($slmType);
                                    if (
                                        ($key == $dateField)
                                        && isset($input[$olaField]) && ($input[$olaField] > 0)
                                        && isset($mandatory_missing[$dateField])
                                    ) {
                                        unset($mandatory_missing[$dateField]);
                                    }
                                }
                            }

                            // For document mandatory
                            if (
                                ($key == '_documents_id')
                                && !isset($input['_filename'])
                                && !isset($input['_tag_filename'])
                                && !isset($input['_content'])
                                && !isset($input['_tag_content'])
                                && !isset($input['_stock_image'])
                                && !isset($input['_tag_stock_image'])
                            ) {
                                $mandatory_missing[$key] = $fieldsname[$val];
                            }
                        }

                        if (count($mandatory_missing)) {
                            //TRANS: %s are the fields concerned
                            $message = sprintf(
                                __('Mandatory fields are not filled. Please correct: %s'),
                                implode(", ", $mandatory_missing)
                            );
                            Session::addMessageAfterRedirect(htmlescape($message), false, ERROR);
                            return false;
                        }
                    }
                }
            }
        }

        return $input;
    }

    /**
     * Handle input deprecations by transferring old supported input keys to new input keys.
     *
     * @param array $input
     *
     * @return array
     */
    private function handleInputDeprecations(array $input): array
    {
        if (array_key_exists('users_id_validate', $input)) {
            Toolbox::deprecated('Usage of "users_id_validate" in input is deprecated. Use "_validation_targets" instead.');

            if (!array_key_exists('_validation_targets', $input)) {
                $input['_validation_targets'] = [];
            }
            $users_ids = !is_array($input['users_id_validate']) ? [$input['users_id_validate']] : $input['users_id_validate'];
            foreach ($users_ids as $user_id) {
                $input['_validation_targets'][] = [
                    'itemtype_target' => User::class,
                    'items_id_target' => $user_id,
                ];
            }
        }

        return $input;
    }

    /**
     * Check input fields consistency.
     *
     * @param array $input
     *
     * @return bool
     */
    private function checkFieldsConsistency(array $input): bool
    {
        if (
            array_key_exists('date', $input) && !empty($input['date']) && $input['date'] != 'NULL'
            && (!is_string($input['date']) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $input['date']))
        ) {
            Session::addMessageAfterRedirect(__s('Incorrect value for date field.'), false, ERROR);
            return false;
        }

        return true;
    }

    /**
     * Compute default values for Add
     * (to be passed in prepareInputForAdd before and after rules if needed)
     *
     * @since 0.84
     *
     * @param $input
     *
     * @return array
     **/
    public function computeDefaultValuesForAdd($input)
    {

        if (!isset($input["status"])) {
            $input["status"] = self::INCOMING;
        }

        if (!isset($input["date"]) || empty($input["date"]) || $input["date"] == 'NULL') {
            $input["date"] = $_SESSION["glpi_currenttime"];
        }

        if (in_array($input["status"], static::getSolvedStatusArray())) {
            if (
                !isset($input["solvedate"])
                || $input["solvedate"] < $input["date"]
            ) {
                $input["solvedate"] = $input["date"];
            }
        }

        if (in_array($input["status"], static::getClosedStatusArray())) {
            if (
                !isset($input["closedate"])
                || $input["closedate"] < $input["date"]
            ) {
                $input["closedate"] = $input["date"];
            }
            if (
                !isset($input["solvedate"])
                || $input["solvedate"] < $input["date"]
                || $input["solvedate"] > $input["closedate"]
            ) {
                $input['solvedate'] = $input["closedate"];
            }
        }

        // Set begin waiting time if status is waiting
        if ($input["status"] == self::WAITING) {
            $input['begin_waiting_date'] = $input['date'];
        }

        return $input;
    }


    public function post_addItem()
    {
        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, ['force_update' => true]);

        // Add default document if set in template
        if (
            isset($this->input['_documents_id'])
            && is_array($this->input['_documents_id'])
            && count($this->input['_documents_id'])
        ) {
            $docitem = new Document_Item();
            foreach ($this->input['_documents_id'] as $docID) {
                $docitem->add(['documents_id' => $docID,
                    '_do_notif'    => false,
                    'itemtype'     => $this->getType(),
                    'items_id'     => $this->fields['id'],
                ]);
            }
        }

        // handle actors changes
        $this->updateActors(true);

        // handle items linking
        $this->handleItemsIdInput();

        // Handle "_tasktemplates_id" special input
        $this->handleTaskTemplateInput();

        // Handle "_itilfollowuptemplates_id" special input
        $this->handleITILFollowupTemplateInput();

        // Handle "_solutiontemplates_id" special input
        $this->handleSolutionTemplateInput();

        // Handle validation requests
        $this->handleValidationStepThresholdInput($this->input);
        $this->manageValidationAdd($this->input);

        $this->manageITILObjectLinkInput($this->input);

        parent::post_addItem();
    }

    /**
     * @see Clonable::post_clone
     */
    public function post_clone($source, $history)
    {
        global $DB;
        $update = [];
        if (isset($source->fields['users_id_lastupdater'])) {
            $update['users_id_lastupdater'] = $source->fields['users_id_lastupdater'];
        }
        if (isset($source->fields['status'])) {
            $update['status'] = $source->fields['status'];
        }
        $DB->update(
            $this->getTable(),
            $update,
            ['id' => $this->getID()]
        );
    }

    public function getCloneRelations(): array
    {
        $relations = [
            KnowbaseItem_Item::class,
        ];

        if (is_a($this->userlinkclass, CommonITILActor::class, true)) {
            $relations[] = $this->userlinkclass;
        }
        if (is_a($this->grouplinkclass, CommonITILActor::class, true)) {
            $relations[] = $this->grouplinkclass;
        }
        if (is_a($this->supplierlinkclass, CommonITILActor::class, true)) {
            $relations[] = $this->supplierlinkclass;
        }

        return $relations;
    }


    /**
     * Compute Priority
     *
     * @since 0.84
     *
     * @param $urgency   integer from 1 to 5
     * @param $impact    integer from 1 to 5
     *
     * @return integer from 1 to 5 (priority)
     **/
    public static function computePriority($urgency, $impact)
    {
        global $CFG_GLPI;
        // Failback to trivial
        return $CFG_GLPI[static::MATRIX_FIELD][$urgency][$impact] ?? (int) round(($urgency + $impact) / 2);
    }


    /**
     * Dropdown of ITIL object priority
     *
     * @since  version 0.84 new proto
     *
     * @param $options array of options
     *       - name     : select name (default is urgency)
     *       - value    : default value (default 0)
     *       - showtype : list proposed : normal, search (default normal)
     *       - wthmajor : boolean with major priority ?
     *       - display  : boolean if false get string
     *
     * @return string id of the select
     **/
    public static function dropdownPriority(array $options = [])
    {
        global $CFG_GLPI;

        $p = [
            'name'      => 'priority',
            'value'     => 0,
            'showtype'  => 'normal',
            'display'   => true,
            'withmajor' => false,
            'enable_filtering' => true,
            'templateResult'    => "templateItilPriority",
            'templateSelection' => "templateItilPriority",
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $values = [];

        if ($p['showtype'] == 'search') {
            $values[0]  = static::getPriorityName(0);
            $values[-5] = static::getPriorityName(-5);
            $values[-4] = static::getPriorityName(-4);
            $values[-3] = static::getPriorityName(-3);
            $values[-2] = static::getPriorityName(-2);
            $values[-1] = static::getPriorityName(-1);
        }

        if (
            ($p['showtype'] == 'search')
            || $p['withmajor']
        ) {
            $values[6] = static::getPriorityName(6);
        }

        $values[5] = static::getPriorityName(5);
        $values[4] = static::getPriorityName(4);
        $values[3] = static::getPriorityName(3);
        $values[2] = static::getPriorityName(2);
        $values[1] = static::getPriorityName(1);

        if ($p['enable_filtering']) {
            $urgencies = [];
            if (isset($CFG_GLPI[static::URGENCY_MASK_FIELD])) {
                if (
                    ($p['showtype'] == 'search')
                    || $CFG_GLPI[static::URGENCY_MASK_FIELD] & (1 << 5)
                ) {
                    $urgencies[] = 5;
                }
                if (
                    ($p['showtype'] == 'search')
                    || $CFG_GLPI[static::URGENCY_MASK_FIELD] & (1 << 4)
                ) {
                    $urgencies[] = 4;
                }
                $urgencies[] = 3;
                if (
                    ($p['showtype'] == 'search')
                    || $CFG_GLPI[static::URGENCY_MASK_FIELD] & (1 << 2)
                ) {
                    $urgencies[] = 2;
                }
                if (
                    ($p['showtype'] == 'search')
                    || $CFG_GLPI[static::URGENCY_MASK_FIELD] & (1 << 1)
                ) {
                    $urgencies[] = 1;
                }
            }
            $impacts = [];
            if (isset($CFG_GLPI[static::IMPACT_MASK_FIELD])) {
                if (
                    ($p['showtype'] == 'search')
                    || $CFG_GLPI[static::IMPACT_MASK_FIELD] & (1 << 5)
                ) {
                    $impacts[] = 5;
                }
                if (
                    ($p['showtype'] == 'search')
                    || $CFG_GLPI[static::IMPACT_MASK_FIELD] & (1 << 4)
                ) {
                    $impacts[] = 4;
                }
                $impacts[] = 3;
                if (
                    ($p['showtype'] == 'search')
                    || $CFG_GLPI[static::IMPACT_MASK_FIELD] & (1 << 2)
                ) {
                    $impacts[] = 2;
                }
                if (
                    ($p['showtype'] == 'search')
                    || $CFG_GLPI[static::IMPACT_MASK_FIELD] & (1 << 1)
                ) {
                    $impacts[] = 1;
                }
            }

            $active_priorities = [];
            foreach ($urgencies as $urgency) {
                foreach ($impacts as $impact) {
                    if (isset($CFG_GLPI["_matrix_{$urgency}_{$impact}"])) {
                        $active_priorities[] = $CFG_GLPI["_matrix_{$urgency}_{$impact}"];
                    }
                }
            }
            $active_priorities = array_unique($active_priorities);
            if (count($active_priorities) > 0) {
                foreach (array_keys($values) as $priority) {
                    if (!in_array($priority, $active_priorities)) {
                        if ($p['withmajor'] && $priority == 6) {
                            continue;
                        }

                        // don't unset current value (to avoid selecting major priority on existing item)
                        if ($priority != $p['value']) {
                            unset($values[$priority]);
                        }
                    }
                }
            }
        }

        return Dropdown::showFromArray($p['name'], $values, $p);
    }


    /**
     * Get ITIL object priority Name
     *
     * @param integer $value priority ID
     **/
    public static function getPriorityName($value)
    {

        switch ($value) {
            case 6:
                return _x('priority', 'Major');

            case 5:
                return _x('priority', 'Very high');

            case 4:
                return _x('priority', 'High');

            case 3:
                return _x('priority', 'Medium');

            case 2:
                return _x('priority', 'Low');

            case 1:
                return _x('priority', 'Very low');

                // No standard one :
            case 0:
                return _x('priority', 'All');
            case -1:
                return _x('priority', 'At least very low');
            case -2:
                return _x('priority', 'At least low');
            case -3:
                return _x('priority', 'At least medium');
            case -4:
                return _x('priority', 'At least high');
            case -5:
                return _x('priority', 'At least very high');

            default:
                // Return $value if not define
                return $value;
        }
    }


    /**
     * Dropdown of ITIL object Urgency
     *
     * @since 0.84 new proto
     *
     * @param $options array of options
     *       - name     : select name (default is urgency)
     *       - value    : default value (default 0)
     *       - showtype : list proposed: normal, search (default normal)
     *       - display  : boolean if false get string
     *
     * @return string id of the select
     **/
    public static function dropdownUrgency(array $options = [])
    {
        global $CFG_GLPI;

        if (self::class === static::class) {
            // Needed because this method depends on static variables that will
            // be defined by child classes.
            throw new RuntimeException("Do not call this method on CommonITILObject, use the target child class instead");
        }

        $p = [
            'name'     => 'urgency',
            'value'    => 0,
            'showtype' => 'normal',
            'display'  => true,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $values = [];

        if ($p['showtype'] == 'search') {
            $values[0]  = static::getUrgencyName(0);
            $values[-5] = static::getUrgencyName(-5);
            $values[-4] = static::getUrgencyName(-4);
            $values[-3] = static::getUrgencyName(-3);
            $values[-2] = static::getUrgencyName(-2);
            $values[-1] = static::getUrgencyName(-1);
        }

        if (isset($CFG_GLPI[static::URGENCY_MASK_FIELD])) {
            if (
                ($p['showtype'] == 'search')
                || ($CFG_GLPI[static::URGENCY_MASK_FIELD] & (1 << 5))
            ) {
                $values[5]  = static::getUrgencyName(5);
            }

            if (
                ($p['showtype'] == 'search')
                || ($CFG_GLPI[static::URGENCY_MASK_FIELD] & (1 << 4))
            ) {
                $values[4]  = static::getUrgencyName(4);
            }

            $values[3]  = static::getUrgencyName(3);

            if (
                ($p['showtype'] == 'search')
                || ($CFG_GLPI[static::URGENCY_MASK_FIELD] & (1 << 2))
            ) {
                $values[2]  = static::getUrgencyName(2);
            }

            if (
                ($p['showtype'] == 'search')
                || ($CFG_GLPI[static::URGENCY_MASK_FIELD] & (1 << 1))
            ) {
                $values[1]  = static::getUrgencyName(1);
            }
        }

        return Dropdown::showFromArray($p['name'], $values, $p);
    }


    /**
     * Get ITIL object Urgency Name
     *
     * @param integer $value urgency ID
     **/
    public static function getUrgencyName($value)
    {

        switch ($value) {
            case 5:
                return _x('urgency', 'Very high');

            case 4:
                return _x('urgency', 'High');

            case 3:
                return _x('urgency', 'Medium');

            case 2:
                return _x('urgency', 'Low');

            case 1:
                return _x('urgency', 'Very low');

                // No standard one :
            case 0:
                return _x('urgency', 'All');
            case -1:
                return _x('urgency', 'At least very low');
            case -2:
                return _x('urgency', 'At least low');
            case -3:
                return _x('urgency', 'At least medium');
            case -4:
                return _x('urgency', 'At least high');
            case -5:
                return _x('urgency', 'At least very high');

            default:
                // Return $value if not define
                return $value;
        }
    }


    /**
     * Dropdown of ITIL object Impact
     *
     * @since 0.84 new proto
     *
     * @param $options   array of options
     *  - name     : select name (default is impact)
     *  - value    : default value (default 0)
     *  - showtype : list proposed : normal, search (default normal)
     *  - display  : boolean if false get string
     *
     * \
     * @return string id of the select
     **/
    public static function dropdownImpact(array $options = [])
    {
        global $CFG_GLPI;

        $p = [
            'name'     => 'impact',
            'value'    => 0,
            'showtype' => 'normal',
            'display'  => true,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }
        $values = [];

        if ($p['showtype'] == 'search') {
            $values[0]  = static::getImpactName(0);
            $values[-5] = static::getImpactName(-5);
            $values[-4] = static::getImpactName(-4);
            $values[-3] = static::getImpactName(-3);
            $values[-2] = static::getImpactName(-2);
            $values[-1] = static::getImpactName(-1);
        }

        if (isset($CFG_GLPI[static::IMPACT_MASK_FIELD])) {
            if (
                ($p['showtype'] == 'search')
                || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1 << 5))
            ) {
                $values[5]  = static::getImpactName(5);
            }

            if (
                ($p['showtype'] == 'search')
                || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1 << 4))
            ) {
                $values[4]  = static::getImpactName(4);
            }

            $values[3]  = static::getImpactName(3);

            if (
                ($p['showtype'] == 'search')
                || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1 << 2))
            ) {
                $values[2]  = static::getImpactName(2);
            }

            if (
                ($p['showtype'] == 'search')
                || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1 << 1))
            ) {
                $values[1]  = static::getImpactName(1);
            }
        }

        return Dropdown::showFromArray($p['name'], $values, $p);
    }


    /**
     * Get ITIL object Impact Name
     *
     * @param integer $value impact ID
     **/
    public static function getImpactName($value)
    {

        switch ($value) {
            case 5:
                return _x('impact', 'Very high');

            case 4:
                return _x('impact', 'High');

            case 3:
                return _x('impact', 'Medium');

            case 2:
                return _x('impact', 'Low');

            case 1:
                return _x('impact', 'Very low');

                // No standard one :
            case 0:
                return _x('impact', 'All');
            case -1:
                return _x('impact', 'At least very low');
            case -2:
                return _x('impact', 'At least low');
            case -3:
                return _x('impact', 'At least medium');
            case -4:
                return _x('impact', 'At least high');
            case -5:
                return _x('impact', 'At least very high');

            default:
                // Return $value if not define
                return $value;
        }
    }


    /**
     * Get the ITIL object status list
     *
     * @param $withmetaforsearch boolean (false by default)
     *
     * @return array
     **/
    public static function getAllStatusArray($withmetaforsearch = false)
    {

        // To be overridden by class
        return [];
    }


    /**
     * Get the ITIL object closed status list
     *
     * @since 0.83
     *
     * @return array
     **/
    public static function getClosedStatusArray()
    {

        // To be overridden by class
        return [];
    }


    /**
     * Get the ITIL object solved status list
     *
     * @since 0.83
     *
     * @return array
     **/
    public static function getSolvedStatusArray()
    {

        // To be overridden by class
        return [];
    }

    /**
     * Get the ITIL object all status list without solved and closed status
     *
     * @since 9.2.1
     *
     * @return array
     **/
    public static function getNotSolvedStatusArray()
    {
        $all = static::getAllStatusArray();
        foreach (static::getSolvedStatusArray() as $status) {
            if (isset($all[$status])) {
                unset($all[$status]);
            }
        }
        foreach (static::getClosedStatusArray() as $status) {
            if (isset($all[$status])) {
                unset($all[$status]);
            }
        }
        $nosolved = array_keys($all);

        return $nosolved;
    }


    /**
     * Get the ITIL object new status list
     *
     * @since 0.83.8
     *
     * @return array
     **/
    public static function getNewStatusArray()
    {

        // To be overridden by class
        return [];
    }

    public static function getProcessStatusArray()
    {
        // To be overridden by class
        return [];
    }

    /**
     * Get the ITIL object process status list
     *
     * @since 0.83
     *
     * @return array
     **/
    public static function getProcessStatus()
    {

        // To be overridden by class
        return [];
    }


    /**
     * check is the user can change from / to a status
     *
     * @since 0.84
     *
     * @param integer $old value of old/current status
     * @param integer $new value of target status
     *
     * @return boolean
     **/
    public static function isAllowedStatus($old, $new)
    {

        if (
            isset($_SESSION['glpiactiveprofile'][static::STATUS_MATRIX_FIELD][$old][$new])
            && !$_SESSION['glpiactiveprofile'][static::STATUS_MATRIX_FIELD][$old][$new]
        ) {
            return false;
        }

        if (
            array_key_exists(
                static::STATUS_MATRIX_FIELD,
                $_SESSION['glpiactiveprofile'] ?? []
            )
            && static::isStatusExists($new)
        ) { // maybe not set for post-only
            return true;
        }

        return false;
    }


    /**
     * Check if an ITIL object is still in an open status
     *
     * @since 10.0
     *
     * @return bool
     */
    public function isNotSolved()
    {
        return !in_array(
            $this->fields['status'],
            array_merge(
                static::getSolvedStatusArray(),
                static::getClosedStatusArray()
            )
        );
    }

    /**
     * Check if an ITIL object has a solved status
     *
     * @since 10.0
     *
     * @param bool $include_closed do we want ticket with closed status also?
     *
     * @return bool
     */
    public function isSolved(bool $include_closed = false)
    {
        $status = static::getSolvedStatusArray();
        if ($include_closed) {
            $status = array_merge($status, static::getClosedStatusArray());
        }

        return in_array(
            $this->fields['status'] ?? null,
            $status
        );
    }

    /**
     * Check if an ITIL object has a closed status
     *
     * @since 10.0
     *
     * @return bool
     */
    public function isClosed()
    {
        return in_array(
            $this->fields['status'] ?? null,
            static::getClosedStatusArray()
        );
    }


    /**
     * Get the ITIL object status allowed for a current status
     *
     * @since 0.84 new proto
     *
     * @param int|null $current   status
     *
     * @return array
     **/
    public static function getAllowedStatusArray($current)
    {

        $tab = static::getAllStatusArray();
        if (!static::isStatusExists($current)) {
            $current = self::INCOMING;
        }

        foreach (array_keys($tab) as $status) {
            if (
                ($status != $current)
                && !static::isAllowedStatus($current, $status)
            ) {
                unset($tab[$status]);
            }
        }
        return $tab;
    }

    /**
     * Is the ITIL object status exist for the object
     *
     * @since 0.85
     *
     * @param integer $status   status
     *
     * @return boolean
     **/
    public static function isStatusExists($status)
    {

        $tab = static::getAllStatusArray();

        return isset($tab[$status]);
    }

    /**
     * Dropdown of object status
     *
     * @since 0.84 new proto
     *
     * @param $options   array of options
     *  - name     : select name (default is status)
     *  - value    : default value (default self::INCOMING)
     *  - showtype : list proposed : normal, search or allowed (default normal)
     *  - display  : boolean if false get string
     *  - use_template_limits: Integer ID of the template to use when considering the available statuses (false disables this limitation).
     *
     * @return string|integer Output string if display option is set to false,
     *                        otherwise random part of dropdown id
     **/
    public static function dropdownStatus(array $options = [])
    {

        $p = [
            'name'                  => 'status',
            'showtype'              => 'normal',
            'display'               => true,
            'templateResult'        => "templateItilStatus",
            'templateSelection'     => "templateItilStatus",
            'use_template_limits'   => false,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        if (empty($p['values']) && (!isset($p['value']) || empty($p['value']))) {
            $p['value']     = self::INCOMING;
        }

        switch ($p['showtype']) {
            case 'allowed':
                $current = isset($p['value_calculation']) && $p['value_calculation'] !== ''
                    ? $p['value_calculation']
                    : $p['value'];
                $tab = static::getAllowedStatusArray($current);
                break;

            case 'search':
                $tab = static::getAllStatusArray(true);
                break;

            default:
                $tab = static::getAllStatusArray(false);
                break;
        }

        if ($p['use_template_limits'] !== false && (int) $p['use_template_limits'] > 0) {
            $template_class = static::getTemplateClass();
            $template = getItemForItemtype($template_class);
            if ($template->getFromDB($p['use_template_limits'])) {
                $allowed_statuses = $template->fields['allowed_statuses'];
                // Allow current value if set
                if (isset($p['value']) && !empty($p['value'])) {
                    $allowed_statuses[] = $p['value'];
                }
                $tab = array_filter($tab, static fn($status) => in_array($status, $allowed_statuses, false), ARRAY_FILTER_USE_KEY);
            }
        }

        return Dropdown::showFromArray($p['name'], $tab, $p);
    }


    /**
     * Get ITIL object status Name
     *
     * @since 0.84
     *
     * @param integer $value status ID
     **/
    public static function getStatus($value)
    {
        $tab  = static::getAllStatusArray(true);
        // Return $value if not defined
        return ($tab[$value] ?? $value);
    }


    /**
     * get field part name corresponding to actor type
     *
     * @param integer $type user type
     *
     * @since 0.84.6
     *
     * @return string|boolean Field part or false if not applicable
     **/
    public static function getActorFieldNameType($type)
    {

        return match ((int) $type) {
            CommonITILActor::REQUESTER => 'requester',
            CommonITILActor::OBSERVER => 'observer',
            CommonITILActor::ASSIGN => 'assign',
            default => false,
        };
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        return match ($field) {
            'status' => htmlescape(static::getStatus($values[$field])),
            'urgency' => htmlescape(static::getUrgencyName($values[$field])),
            'impact' => htmlescape(static::getImpactName($values[$field])),
            'priority' => htmlescape(static::getPriorityName($values[$field])),
            'global_validation' => htmlescape(CommonITILValidation::getStatus($values[$field])),
            default => parent::getSpecificValueToDisplay($field, $values, $options),
        };
    }


    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'status':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return static::dropdownStatus($options);

            case 'impact':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return static::dropdownImpact($options);

            case 'urgency':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return static::dropdownUrgency($options);

            case 'priority':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                $options['enable_filtering'] = false;
                return static::dropdownPriority($options);

            case 'global_validation':
                $options['global'] = true;
                $options['value']  = $values[$field];
                return CommonITILValidation::dropdownStatus($name, $options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = [];

        if (Session::getCurrentInterface() === 'central') {
            $can_update_itilobject = Session::haveRight(Ticket::$rightname, UPDATE)
                || Session::haveRight(Change::$rightname, UPDATE)
                || Session::haveRight(Problem::$rightname, UPDATE);
            if ($can_update_itilobject) {
                $actions['CommonITILObject_CommonITILObject' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add']
                    = "<i class='ti ti-link'></i>"
                    . _sx('button', 'Link ITIL Object');
                $actions['CommonITILObject_CommonITILObject' . MassiveAction::CLASS_ACTION_SEPARATOR . 'delete']
                    = "<i class='ti ti-unlink'></i>"
                    . _sx('button', 'Unlink ITIL Object');
            }
        }

        $actions += parent::getSpecificMassiveActions($checkitem);

        return $actions;
    }

    /**
     * @since 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     **/
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        global $CFG_GLPI;

        switch ($ma->getAction()) {
            case 'add_task':
                $itemtype_or_selector = $ma->getItemtype(true);

                if (is_bool($itemtype_or_selector)) {
                    // MassiveAction::getItemtype() will return a boolean if the itemtype selector needs to be displayed.
                    return $itemtype_or_selector;
                }

                // MassiveAction::getItemtype() will return a classname if the selector does not need to be displayed.
                $itemtype = $itemtype_or_selector;

                $tasktype = $itemtype . 'Task';
                if ($ttype = getItemForItemtype($tasktype)) {
                    /** @var CommonITILTask $ttype */
                    $ttype->showMassiveActionAddTaskForm();
                    return true;
                }
                return false;

            case 'add_actor':
                $types            = [0                          => Dropdown::EMPTY_VALUE,
                    CommonITILActor::REQUESTER => _n('Requester', 'Requesters', 1),
                    CommonITILActor::OBSERVER  => _n('Observer', 'Observers', 1),
                    CommonITILActor::ASSIGN    => __('Assigned to'),
                ];
                $rand             = Dropdown::showFromArray('actortype', $types);

                $paramsmassaction = ['actortype' => '__VALUE__'];

                Ajax::updateItemOnSelectEvent(
                    "dropdown_actortype$rand",
                    "show_massiveaction_field",
                    $CFG_GLPI["root_doc"]
                                             . "/ajax/dropdownMassiveActionAddActor.php",
                    $paramsmassaction
                );
                echo "<span id='show_massiveaction_field'>&nbsp;</span>\n";
                return true;
            case 'update_notif':
                Dropdown::showYesNo('use_notification');
                echo "<br><br>";
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }


    /**
     * @since 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     **/
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        /** @var CommonITILObject $item */
        switch ($ma->getAction()) {
            case 'add_actor':
                $input = $ma->getInput();
                foreach ($ids as $id) {
                    $input2 = ['id' => $id];
                    if (isset($input['_itil_requester'])) {
                        $input2['_itil_requester'] = $input['_itil_requester'];
                    }
                    if (isset($input['_itil_observer'])) {
                        $input2['_itil_observer'] = $input['_itil_observer'];
                    }
                    if (isset($input['_itil_assign'])) {
                        $input2['_itil_assign'] = $input['_itil_assign'];
                    }
                    if ($item->can($id, UPDATE)) {
                        if ($item->update($input2)) {
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
                return;

            case 'update_notif':
                $input = $ma->getInput();
                foreach ($ids as $id) {
                    if ($item->can($id, UPDATE)) {
                        $linkclass = $item->getActorObjectForItem(User::class);
                        foreach ($linkclass->getActors($id) as $users) {
                            foreach ($users as $data) {
                                $data['use_notification'] = $input['use_notification'];
                                $linkclass->update($data);
                            }
                        }
                        $linkclass = $item->getActorObjectForItem(Supplier::class);
                        foreach ($linkclass->getActors((int) $id) as $users) {
                            foreach ($users as $data) {
                                $data['use_notification'] = $input['use_notification'];
                                $linkclass->update($data);
                            }
                        }

                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;

            case 'add_task':
                if (!($task = $item->getTaskClassInstance())) {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                    break;
                }

                foreach ($ids as $id) {
                    if ($item->getFromDB($id)) {
                        $input = $ma->getInput();
                        unset($input['itemtype']);
                        unset($input['_glpi_csrf_token']);
                        $input[$item->getForeignKeyField()] = $id;
                        if (count($ids) > 1) {
                            // Avoid the "The user xxx is busy at the selected timeframe"
                            // warning if planning multiple tasks
                            $input['_do_not_check_already_planned'] = true;
                        }

                        if (
                            $task->can(-1, CREATE, $input)
                            && !in_array(
                                $item->fields['status'],
                                array_merge(
                                    $item->getSolvedStatusArray(),
                                    $item->getClosedStatusArray()
                                )
                            )
                        ) {
                            if ($task->add($input)) {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    /**
     * @since 0.85
     **/
    public function getSearchOptionsMain()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Title'),
            'datatype'           => 'itemlink',
            'searchtype'         => 'contains',
            'massiveaction'      => false,
            'additionalfields'   => ['id', 'status'],
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => $this->getTable(),
            'field'              => 'content',
            'name'               => __('Description'),
            'massiveaction'      => false,
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'status',
            'name'               => __('Status'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'urgency',
            'name'               => __('Urgency'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'impact',
            'name'               => __('Impact'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'priority',
            'name'               => __('Priority'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => $this->getTable(),
            'field'              => 'date',
            'name'               => __('Opening date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'closedate',
            'name'               => __('Closing date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => $this->getTable(),
            'field'              => 'time_to_resolve',
            'name'               => __('Time to resolve'),
            'datatype'           => 'datetime',
            'maybefuture'        => true,
            'massiveaction'      => false,
            'additionalfields'   => ['solvedate', 'status'],
        ];

        $tab[] = [
            'id'                 => '151',
            'table'              => $this->getTable(),
            'field'              => 'time_to_resolve',
            'name'               => __('Time to resolve + Progress'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'additionalfields'   => ['status'],
        ];

        $tab[] = [
            'id'                 => '82',
            'table'              => $this->getTable(),
            'field'              => 'is_late',
            'name'               => __('Time to resolve exceeded'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
            'computation'        => self::generateSLAOLAComputation('time_to_resolve'),
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => $this->getTable(),
            'field'              => 'solvedate',
            'name'               => __('Resolution date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $newtab = [
            'id'                 => '7',
            'table'              => 'glpi_itilcategories',
            'field'              => 'completename',
            'name'               => _n('Category', 'Categories', 1),
            'datatype'           => 'dropdown',
        ];

        if (
            !Session::isCron() // no filter for cron
            && Session::getCurrentInterface() == 'helpdesk'
        ) {
            $newtab['condition']         = ['is_helpdeskvisible' => 1];
        }
        $tab[] = $newtab;

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '45',
            'table'              => $this->getTable(),
            'field'              => 'actiontime',
            'name'               => __('Total duration'),
            'datatype'           => 'timestamp',
            'massiveaction'      => false,
            'nosearch'           => true,
        ];

        $newtab = [
            'id'                 => '64',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_lastupdater',
            'name'               => __('Last edit by'),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        // Filter search fields for helpdesk
        if (
            !Session::isCron() // no filter for cron
            && Session::getCurrentInterface() != 'central'
        ) {
            // last updater no search
            $newtab['nosearch'] = true;
        }
        $tab[] = $newtab;

        // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        // For ITIL template
        $tab[] = [
            'id'                 => '142',
            'table'              => 'glpi_documents',
            'field'              => 'name',
            'name'               => Document::getTypeName(Session::getPluralNumber()),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'nosearch'           => true,
            'nodisplay'          => true,
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'items_id',
                'beforejoin'         => [
                    'table'              => 'glpi_documents_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '400',
            'table'              => PendingReason::getTable(),
            'field'              => 'name',
            'name'               => PendingReason::getTypeName(1),
            'massiveaction'      => false,
            'searchtype'         => ['equals', 'notequals'],
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'           => 'items_id',
                'beforejoin'         => [
                    'table'              => PendingReason_Item::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '401',
            'table'              => static::getTemplateClass()::getTable(),
            'field'              => 'name',
            'name'               => _n('Template', 'Templates', 1),
            'massiveaction'      => false,
            'searchtype'         => ['equals', 'notequals'],
            'datatype'           => 'dropdown',
            'linkfield'          => static::getTemplateClass()::getForeignKeyField(),
        ];

        $location_so = Location::rawSearchOptionsToAdd();
        foreach ($location_so as &$so) {
            //duplicated search options :(
            switch ($so['id']) {
                case 3:
                    $so['id'] = 83;
                    break;
                case 91:
                    $so['id'] = 84;
                    break;
                case 92:
                    $so['id'] = 85;
                    break;
                case 93:
                    $so['id'] = 86;
                    break;
            }
        }

        $tab = array_merge($tab, $location_so);

        $tab = array_merge($tab, Project::rawSearchOptionsToAdd(static::class));

        // Search by form answer
        $tab[] = [
            'id'                 => '120',
            'table'              => AnswersSet::getTable(),
            'field'              => 'name',
            'name'               => AnswersSet::getTypeName(1),
            'massiveaction'      => false,
            'searchtype'         => 'equals',
            'datatype'           => 'itemlink',
            'usehaving'          => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => AnswersSet_FormDestinationItem::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'linkfield'          => 'items_id',
                        'condition'          => ['NEWTABLE.itemtype' => self::getType()],
                    ],
                ],
            ],
            'forcegroupby'       => true,
        ];

        return $tab;
    }


    /**
     * @since 0.85
     **/
    public function getSearchOptionsSolution()
    {
        global $DB;
        $tab = [];

        $tab[] = [
            'id'                 => 'solution',
            'name'               => ITILSolution::getTypeName(1),
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_solutiontypes',
            'field'              => 'name',
            'name'               => SolutionType::getTypeName(1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => ITILSolution::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => ITILSolution::getTable(),
            'field'              => 'content',
            'name'               => ITILSolution::getTypeName(1),
            'datatype'           => 'text',
            'htmltext'           => true,
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                  => '38',
            'table'               => ITILSolution::getTable(),
            'field'               => 'status',
            'name'                => __('Any solution status'),
            'datatype'            => 'specific',
            'searchtype'          => ['equals', 'notequals'],
            'searchequalsonfield' => true,
            'massiveaction'       => false,
            'forcegroupby'        => true,
            'joinparams'          => [
                'jointype' => 'itemtype_item',
            ],
        ];

        $last_solution_condition = new QuerySubQuery([
            'SELECT' => 'id',
            'FROM'   => ITILSolution::getTable(),
            'WHERE'  => [
                ITILSolution::getTable() . '.items_id' => new QueryExpression($DB::quoteName('REFTABLE.id')),
                ITILSolution::getTable() . '.itemtype' => static::getType(),
            ],
            'ORDER'  => ITILSolution::getTable() . '.id DESC',
            'LIMIT'  => 1,
        ]);
        $tab[] = [
            'id'                  => '39',
            'table'               => ITILSolution::getTable(),
            'field'               => 'status',
            'name'                => __('Last solution status'),
            'datatype'            => 'specific',
            'searchtype'          => ['equals', 'notequals'],
            'searchequalsonfield' => true,
            'massiveaction'       => false,
            'forcegroupby'        => true,
            'joinparams'          => [
                'jointype'  => 'itemtype_item',
                // Get only last created solution
                'condition' => [
                    'NEWTABLE.id'  => new QueryExpression($last_solution_condition->getQuery()),
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '74',
            'table'              => ITILSolution::getTable(),
            'field'              => 'date_creation',
            'name'               => _n('Latest date', 'Latest dates', 1),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
            'computation'        => QueryFunction::max('TABLE.date_creation'),
            'nometa'             => true, // cannot GROUP_CONCAT a MAX
        ];

        return $tab;
    }


    public function getSearchOptionsStats()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'stats',
            'name'               => __('Statistics'),
        ];

        $tab[] = [
            'id'                 => '154',
            'table'              => $this->getTable(),
            'field'              => 'solve_delay_stat',
            'name'               => __('Resolution time'),
            'datatype'           => 'timestamp',
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '152',
            'table'              => $this->getTable(),
            'field'              => 'close_delay_stat',
            'name'               => __('Closing time'),
            'datatype'           => 'timestamp',
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '153',
            'table'              => $this->getTable(),
            'field'              => 'waiting_duration',
            'name'               => __('Waiting time'),
            'datatype'           => 'timestamp',
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        return $tab;
    }


    public function getSearchOptionsActors()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'requester',
            'name'               => _n('Requester', 'Requesters', 1),
        ];

        $newtab = [
            'id'                 => '4', // Also in Ticket_User::post_addItem() and Log::getHistoryData()
            'table'              => 'glpi_users',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'right'              => 'all',
            'name'               => _n('Requester', 'Requesters', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'use_subquery'       => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => getTableForItemType($this->userlinkclass),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => ['NEWTABLE.type' => CommonITILActor::REQUESTER],
                    ],
                ],
            ],
        ];

        if (
            !Session::isCron() // no filter for cron
            && Session::getCurrentInterface() == 'helpdesk'
        ) {
            $newtab['right']       = 'id';
        }
        $tab[] = $newtab;

        $tab[] = [
            'id'                 => '500',
            'table'              => 'glpi_usercategories',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => _n('Requester category', 'Requester categories', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_users',
                    'joinparams'         => [
                        'beforejoin'         => [
                            'table'              => getTableForItemType($this->userlinkclass),
                            'joinparams'         => [
                                'jointype'           => 'child',
                                'condition'          => ['NEWTABLE.type' => CommonITILActor::REQUESTER],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $newtab = [
            'id'                 => '71',  // Also in Group_Ticket::post_addItem() and Log::getHistoryData()
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'datatype'           => 'dropdown',
            'name'               => _n('Requester group', 'Requester groups', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'condition'          => ['is_requester' => 1],
            'use_subquery'       => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => getTableForItemType($this->grouplinkclass),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => ['NEWTABLE.type' => CommonITILActor::REQUESTER],
                    ],
                ],
            ],
        ];

        if (
            !Session::isCron() // no filter for cron
            && Session::getCurrentInterface() == 'helpdesk'
        ) {
            $newtab['condition'] = array_merge(
                $newtab['condition'],
                ['id' => $_SESSION['glpigroups']]
            );
        }
        $tab[] = $newtab;

        $newtab = [
            'id'                 => '22',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'right'              => 'all',
            'linkfield'          => 'users_id_recipient',
            'name'               => __('Writer'),
        ];

        if (
            !Session::isCron() // no filter for cron
            && Session::getCurrentInterface() == 'helpdesk'
        ) {
            $newtab['right']       = 'id';
        }
        $tab[] = $newtab;

        $tab[] = [
            'id'                 => 'observer',
            'name'               => _n('Observer', 'Observers', 1),
        ];

        $tab[] = [
            'id'                 => '66', // Also in Ticket_User::post_addItem() and Log::getHistoryData()
            'table'              => 'glpi_users',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'right'              => 'all',
            'name'               => _n('Observer', 'Observers', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'use_subquery'       => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => getTableForItemType($this->userlinkclass),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => ['NEWTABLE.type' => CommonITILActor::OBSERVER],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '501',
            'table'              => 'glpi_usercategories',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => _n('Observer category', 'Observer categories', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_users',
                    'joinparams'         => [
                        'beforejoin'         => [
                            'table'              => getTableForItemType($this->userlinkclass),
                            'joinparams'         => [
                                'jointype'           => 'child',
                                'condition'          => ['NEWTABLE.type' => CommonITILActor::OBSERVER],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '65', // Also in Group_Ticket::post_addItem() and Log::getHistoryData()
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'datatype'           => 'dropdown',
            'name'               => _n('Observer group', 'Observer groups', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'condition'          => ['is_watcher' => 1],
            'use_subquery'       => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => getTableForItemType($this->grouplinkclass),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => ['NEWTABLE.type' => CommonITILActor::OBSERVER],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => 'assign',
            'name'               => __('Assigned to'),
        ];

        $tab[] = [
            'id'                 => '5', // Also in Ticket_User::post_addItem() and Log::getHistoryData()
            'table'              => 'glpi_users',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket',
            'name'               => __('Technician'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'use_subquery'       => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => getTableForItemType($this->userlinkclass),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => ['NEWTABLE.type' => CommonITILActor::ASSIGN],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '6', // Also in Supplier_Ticket::post_addItem() and Log::getHistoryData()
            'table'              => 'glpi_suppliers',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => __('Assigned to a supplier'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'use_subquery'       => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => getTableForItemType($this->supplierlinkclass),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => ['NEWTABLE.type' => CommonITILActor::ASSIGN],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '502',
            'table'              => 'glpi_usercategories',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => _n('Technician category', 'Technician categories', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_users',
                    'joinparams'         => [
                        'beforejoin'         => [
                            'table'              => getTableForItemType($this->userlinkclass),
                            'joinparams'         => [
                                'jointype'           => 'child',
                                'condition'          => ['NEWTABLE.type' => CommonITILActor::ASSIGN],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '8', // Also in Group_Ticket::post_addItem() and Log::getHistoryData()
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'datatype'           => 'dropdown',
            'name'               => __('Technician group'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'condition'          => ['is_assign' => 1],
            'use_subquery'       => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => getTableForItemType($this->grouplinkclass),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => ['NEWTABLE.type' => CommonITILActor::ASSIGN],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => 'notification',
            'name'               => _n('Notification', 'Notifications', Session::getPluralNumber()),
        ];

        $tab[] = [
            'id'                 => '35',
            'table'              => getTableForItemType($this->userlinkclass),
            'field'              => 'use_notification',
            'name'               => __('Email followup'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.type' => CommonITILActor::REQUESTER],
            ],
        ];

        $tab[] = [
            'id'                 => '34',
            'table'              => getTableForItemType($this->userlinkclass),
            'field'              => 'alternative_email',
            'name'               => __('Email for followup'),
            'datatype'           => 'email',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.type' => CommonITILActor::REQUESTER],
            ],
        ];

        return $tab;
    }

    /**
     * @param string $type
     * @param string $table
     * @return QueryExpression|void
     */
    public static function generateSLAOLAComputation($type, $table = "TABLE")
    {
        global $DB;

        switch ($type) {
            case 'internal_time_to_own':
            case 'time_to_own':
                return QueryFunction::if(
                    condition: [
                        'NOT' => ["{$table}.{$type}" => null],
                        "$table.status" => ['<>', self::WAITING],
                        'OR' => [
                            [
                                'AND' => [
                                    'NOT' => ["$table.takeintoaccountdate" => null],
                                    "$table.takeintoaccountdate" => ['>', new QueryExpression($DB::quoteName("{$table}.{$type}"))],
                                ],
                            ],
                            [
                                'AND' => [
                                    "$table.takeintoaccountdate" => null,
                                    "$table.takeintoaccount_delay_stat" => ['>',
                                        QueryFunction::timestampdiff(
                                            unit: 'SECOND',
                                            expression1: "$table.date",
                                            expression2: "{$table}.{$type}"
                                        ),
                                    ],
                                ],
                            ],
                            [
                                'AND' => [
                                    "$table.takeintoaccount_delay_stat" => 0,
                                    "$table.$type" => ['<', QueryFunction::now()],
                                ],
                            ],
                        ],
                    ],
                    true_expression: new QueryExpression('1'),
                    false_expression: new QueryExpression('0')
                );

            case 'internal_time_to_resolve':
            case 'time_to_resolve':
                return QueryFunction::if(
                    condition: [
                        'NOT' => ["{$table}.{$type}" => null],
                        "$table.status" => ['<>', self::WAITING],
                        'OR' => [
                            "$table.solvedate" => ['>', new QueryExpression($DB::quoteName("$table.$type"))],
                            'AND' => [
                                "$table.solvedate" => null,
                                "$table.$type" => ['<', QueryFunction::now()],
                            ],
                        ],
                    ],
                    true_expression: new QueryExpression('1'),
                    false_expression: new QueryExpression('0')
                );
        }
    }

    /**
     * Get status icon
     *
     * @since 9.3
     *
     * @return string
     */
    public static function getStatusIcon($status)
    {
        $class = htmlescape(static::getStatusClass($status));
        $label = htmlescape(static::getStatus($status));
        return "<i class='$class me-1' title='$label' data-bs-toggle='tooltip'></i>";
    }

    /**
     * Get CSS status class
     *
     * @since 9.3
     *
     * @return string
     */
    public static function getStatusClass($status)
    {
        $class = match ($status) {
            self::INCOMING, self::WAITING, self::CLOSED => 'circle-filled',
            self::ASSIGNED, self::SOLVED, Change::EVALUATION => 'circle',
            self::PLANNED => 'calendar',
            self::ACCEPTED => 'check-circle-filled',
            self::OBSERVED => 'eye',
            self::APPROVAL, Change::TEST => 'help',
            Change::QUALIFICATION => 'circle',
            Change::REFUSED => 'circle-x',
            Change::CANCELED => 'ban',
            default => null
        };

        return $class === null ? '' : 'itilstatus ti ti-' . $class . " " . static::getStatusKey($status);
    }

    /**
     * Get status key
     *
     * @since 9.3
     *
     * @return string
     */
    public static function getStatusKey($status)
    {
        $key = '';
        switch ($status) {
            case self::INCOMING:
                $key = 'new';
                break;
            case self::ASSIGNED:
                $key = 'assigned';
                break;
            case self::PLANNED:
                $key = 'planned';
                break;
            case self::WAITING:
                $key = 'waiting';
                break;
            case self::SOLVED:
                $key = 'solved';
                break;
            case self::CLOSED:
                $key = 'closed';
                break;
            case self::ACCEPTED:
                $key = 'accepted';
                break;
            case self::OBSERVED:
                $key = 'observe';
                break;
            case Change::EVALUATION:
                $key = 'eval';
                break;
            case self::APPROVAL:
                $key = 'approval';
                break;
            case Change::TEST:
                $key = 'test';
                break;
            case Change::QUALIFICATION:
                $key = 'qualif';
                break;
        }
        return $key;
    }


    /**
     * show actor add div
     *
     * @param $type         string   actor type
     * @param $rand_type    integer  rand value of div to use
     * @param $entities_id  integer  entity ID
     * @param $is_hidden    array    of hidden fields (if empty consider as not hidden)
     * @param $withgroup    boolean  allow adding a group (true by default)
     * @param $withsupplier boolean  allow adding a supplier (only one possible in ASSIGN case)
     *                               (false by default)
     * @param $inobject     boolean  display in ITIL object ? (true by default)
     *
     * @return void|boolean Nothing if displayed, false if not applicable
     **/
    public function showActorAddForm(
        $type,
        $rand_type,
        $entities_id,
        $is_hidden = [],
        $withgroup = true,
        $withsupplier = false,
        $inobject = true
    ) {
        global $CFG_GLPI;

        $types = ['user'  => User::getTypeName(1)];

        if ($withgroup) {
            $types['group'] = Group::getTypeName(1);
        }

        if (
            $withsupplier
            && ($type == CommonITILActor::ASSIGN)
        ) {
            $types['supplier'] = Supplier::getTypeName(1);
        }

        $typename = static::getActorFieldNameType($type);
        switch ($type) {
            case CommonITILActor::REQUESTER:
                if (isset($is_hidden['_users_id_requester']) && $is_hidden['_users_id_requester']) {
                    unset($types['user']);
                }
                if (isset($is_hidden['_groups_id_requester']) && $is_hidden['_groups_id_requester']) {
                    unset($types['group']);
                }
                break;

            case CommonITILActor::OBSERVER:
                if (isset($is_hidden['_users_id_observer']) && $is_hidden['_users_id_observer']) {
                    unset($types['user']);
                }
                if (isset($is_hidden['_groups_id_observer']) && $is_hidden['_groups_id_observer']) {
                    unset($types['group']);
                }
                break;

            case CommonITILActor::ASSIGN:
                if (isset($is_hidden['_users_id_assign']) && $is_hidden['_users_id_assign']) {
                    unset($types['user']);
                }
                if (isset($is_hidden['_groups_id_assign']) && $is_hidden['_groups_id_assign']) {
                    unset($types['group']);
                }
                if (
                    isset($types['supplier'])
                    && isset($is_hidden['_suppliers_id_assign']) && $is_hidden['_suppliers_id_assign']
                ) {
                    unset($types['supplier']);
                }
                break;

            default:
                return false;
        }

        echo "<div " . ($inobject ? "style='display:none'" : '') . " id='itilactor" . htmlescape($rand_type) . "' class='actor-dropdown'>";
        $rand   = Dropdown::showFromArray(
            "_itil_" . $typename . "[_type]",
            $types,
            ['display_emptychoice' => true]
        );
        $params = ['type'            => '__VALUE__',
            'actortype'       => $typename,
            'itemtype'        => $this->getType(),
            'allow_email'     => (($type == CommonITILActor::OBSERVER)
                                            || $type == CommonITILActor::REQUESTER),
            'entity_restrict' => $entities_id,
            'use_notif'       => Entity::getUsedConfig('is_notif_enable_default', $entities_id, '', 1),
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown__itil_" . $typename . "[_type]$rand",
            "showitilactor" . $typename . "_$rand",
            $CFG_GLPI["root_doc"] . "/ajax/dropdownItilActors.php",
            $params
        );
        echo "<span id='showitilactor" . htmlescape($typename) . "_$rand' class='actor-dropdown'>&nbsp;</span>";
        if ($inobject) {
            echo "<hr>";
        }
        echo "</div>";
    }


    /**
     * @param int|float $actiontime
     **/
    public static function getActionTime($actiontime)
    {
        return Html::timestampToString($actiontime, false);
    }

    /**
     * Form to add a solution to an ITIL object
     *
     * @since 0.84
     * @since 9.2 Signature has changed
     *
     * @param CommonITILObject $item item instance
     *
     * @param $entities_id
     **/
    public static function showMassiveSolutionForm(CommonITILObject $item)
    {
        $solution = new ITILSolution();
        $solution->showForm(
            0,
            [
                'parent' => $item,
                'entity' => $item->getEntityID(),
                'noform' => true,
                'nokb'   => true,
            ]
        );
    }


    /**
     * Update date mod of the ITIL object
     *
     * @param $ID                    integer  ID of the ITIL object
     * @param $no_stat_computation   boolean  do not compute take into account stat (false by default)
     * @param $users_id_lastupdater  integer  to force last_update id (default 0 = not used)
     **/
    public function updateDateMod($ID, $no_stat_computation = false, $users_id_lastupdater = 0)
    {
        global $DB;

        if ($this->getFromDB($ID)) {
            // Force date mod and lastupdater
            $update = ['date_mod' => $_SESSION['glpi_currenttime']];

            // set last updater if interactive user
            if (!Session::isCron()) {
                $update['users_id_lastupdater'] = Session::getLoginUserID();
            } elseif ($users_id_lastupdater > 0) {
                $update['users_id_lastupdater'] = $users_id_lastupdater;
            }

            $DB->update(
                $this->getTable(),
                $update,
                ['id' => $ID]
            );
        }
    }


    /**
     * Update actiontime of the object based on actiontime of the tasks
     *
     * @param integer $ID ID of the object
     *
     * @return boolean : success
     **/
    public function updateActionTime($ID)
    {
        global $DB;

        $tot       = 0;
        $tasktable = getTableForItemType(static::getTaskClass());

        $result = $DB->request([
            'SELECT' => ['SUM' => 'actiontime as sumtime'],
            'FROM'   => $tasktable,
            'WHERE'  => [$this->getForeignKeyField() => $ID],
        ])->current();
        $sum = $result['sumtime'];
        if (!is_null($sum)) {
            $tot += $sum;
        }

        $result = $DB->update(
            $this->getTable(),
            [
                'actiontime' => $tot,
            ],
            [
                'id' => $ID,
            ]
        );
        return $result;
    }


    /**
     * Get all available types to which an ITIL object can be assigned
     **/
    public static function getAllTypesForHelpdesk()
    {
        global $CFG_GLPI, $PLUGIN_HOOKS;

        /// TODO ticket_types -> itil_types

        $types = [];
        $ptypes = [];
        // Types of the plugins (keep the plugin hook for right check)
        if (isset($PLUGIN_HOOKS[Hooks::ASSIGN_TO_TICKET])) {
            foreach (array_keys($PLUGIN_HOOKS[Hooks::ASSIGN_TO_TICKET]) as $plugin) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                $ptypes = Plugin::doOneHook($plugin, Hooks::AUTO_ASSIGN_TO_TICKET, $ptypes);
            }
        }
        asort($ptypes);
        //Types of the core (after the plugin for robustness)
        foreach ($CFG_GLPI["ticket_types"] as $itemtype) {
            if ($item = getItemForItemtype($itemtype)) {
                if (
                    !isPluginItemType($itemtype) // No plugin here
                    && isset($_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                    && in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                ) {
                    $types[$itemtype] = $item->getTypeName(1);
                }
            }
        }
        asort($types); // core type first... asort could be better ?

        // Drop not available plugins
        foreach (array_keys($ptypes) as $itemtype) {
            if (
                !isset($_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                || !in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
            ) {
                unset($ptypes[$itemtype]);
            }
        }

        $types = array_merge($types, $ptypes);
        return $types;
    }


    /**
     * Check if it's possible to assign ITIL object to a type (core or plugin)
     *
     * @param string $itemtype the object's type
     *
     * @return boolean true if ticket can be assigned to this type, false if not
     **/
    public static function isPossibleToAssignType($itemtype)
    {

        if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
            return true;
        }
        return false;
    }


    /**
     * Compute solve delay stat of the current ticket
     **/
    public function computeSolveDelayStat()
    {

        if (
            isset($this->fields['id'])
            && !empty($this->fields['date'])
            && !empty($this->fields['solvedate'])
            && $this->fields['solvedate'] !== 'NULL'
        ) {
            $calendars_id = $this->getCalendar();
            $calendar     = new Calendar();

            // Using calendar
            if (
                ($calendars_id > 0)
                && $calendar->getFromDB($calendars_id)
            ) {
                return max(0, $calendar->getActiveTimeBetween(
                    $this->fields['date'],
                    $this->fields['solvedate']
                )
                                                            - $this->fields["waiting_duration"]);
            }
            // Not calendar defined
            try {
                return max(0, strtotime($this->fields['solvedate']) - strtotime($this->fields['date'])
                    - $this->fields["waiting_duration"]);
            } catch (DatetimeException $e) {
                return 0;
            }
        }
        return 0;
    }


    /**
     * Closing delay in seconds.
     *
     * Taking working time into account if calendar is defined.
     * Removes waiting duration from the total time.
     *
     * @return int
     **/
    public function computeCloseDelayStat()
    {

        if (
            isset($this->fields['id'])
            && !empty($this->fields['date'])
            && !empty($this->fields['closedate'])
            && $this->fields['closedate'] !== 'NULL'
        ) {
            $calendars_id = $this->getCalendar();
            $calendar     = new Calendar();

            // Using calendar
            if (
                ($calendars_id > 0)
                && $calendar->getFromDB($calendars_id)
            ) {
                return max(0, $calendar->getActiveTimeBetween(
                    $this->fields['date'],
                    $this->fields['closedate']
                )
                                                             - $this->fields["waiting_duration"]);
            }
            // Not calendar defined
            try {
                return max(0, strtotime($this->fields['closedate']) - strtotime($this->fields['date'])
                    - $this->fields["waiting_duration"]);
            } catch (DatetimeException $e) {
                return 0;
            }
        }
        return 0;
    }


    public function showStats()
    {

        if (
            !$this->canView()
            || !isset($this->fields['id'])
        ) {
            return false;
        }

        $this->showStatsDates();
        Plugin::doHook(Hooks::SHOW_ITEM_STATS, $this);
        $this->showStatsTimes();
    }

    public function showStatsDates()
    {
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>" . _sn('Date', 'Dates', Session::getPluralNumber()) . "</th></tr>";

        echo "<tr class='tab_bg_2'><td>" . __s('Opening date') . "</td>";
        echo "<td>" . htmlescape(Html::convDateTime($this->fields['date'])) . "</td></tr>";

        echo "<tr class='tab_bg_2'><td>" . __s('Time to resolve') . "</td>";
        echo "<td>" . htmlescape(Html::convDateTime($this->fields['time_to_resolve'])) . "</td></tr>";

        if (!$this->isNotSolved()) {
            echo "<tr class='tab_bg_2'><td>" . __s('Resolution date') . "</td>";
            echo "<td>" . htmlescape(Html::convDateTime($this->fields['solvedate'])) . "</td></tr>";
        }

        if (in_array($this->fields['status'], static::getClosedStatusArray())) {
            echo "<tr class='tab_bg_2'><td>" . __s('Closing date') . "</td>";
            echo "<td>" . htmlescape(Html::convDateTime($this->fields['closedate'])) . "</td></tr>";
        }
        echo "</table>";
    }

    public function showStatsTimes()
    {
        echo "<div class='dates_timelines'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>" . _sn('Time', 'Times', Session::getPluralNumber()) . "</th></tr>";

        if (isset($this->fields['takeintoaccount_delay_stat'])) {
            echo "<tr class='tab_bg_2'><td>" . __s('Take into account') . "</td><td>";
            if ($this->fields['takeintoaccount_delay_stat'] > 0) {
                echo htmlescape(Html::timestampToString($this->fields['takeintoaccount_delay_stat'], false, false));
            } else {
                echo '&nbsp;';
            }
            echo "</td></tr>";
        }

        if (!$this->isNotSolved()) {
            echo "<tr class='tab_bg_2'><td>" . __s('Resolution') . "</td><td>";

            if ($this->fields['solve_delay_stat'] > 0) {
                echo htmlescape(Html::timestampToString($this->fields['solve_delay_stat'], false, false));
            } else {
                echo '&nbsp;';
            }
            echo "</td></tr>";
        }

        if (in_array($this->fields['status'], static::getClosedStatusArray())) {
            echo "<tr class='tab_bg_2'><td>" . __s('Closure') . "</td><td>";
            if ($this->fields['close_delay_stat'] > 0) {
                echo htmlescape(Html::timestampToString($this->fields['close_delay_stat'], true, false));
            } else {
                echo '&nbsp;';
            }
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_2'><td>" . __s('Pending') . "</td><td>";
        if ($this->fields['waiting_duration'] > 0) {
            echo htmlescape(Html::timestampToString($this->fields['waiting_duration'], false, false));
        } else {
            echo '&nbsp;';
        }
        echo "</td></tr>";

        echo "</table>";
        echo "</div>";
    }


    /** Get users_ids of itil object between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct users_ids which have itil object
     **/
    public function getUsedAuthorBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $linktable = $this->userlinkclass::getTable();

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => [
                'glpi_users.id AS users_id',
                'glpi_users.name AS name',
                'glpi_users.realname AS realname',
                'glpi_users.firstname AS firstname',
            ],
            'DISTINCT' => true,
            'FROM'            => $ctable,
            'LEFT JOIN'       => [
                $linktable  => [
                    'ON' => [
                        $linktable  => $this->getForeignKeyField(),
                        $ctable     => 'id', [
                            'AND' => [
                                "$linktable.type"    => CommonITILActor::REQUESTER,
                            ],
                        ],
                    ],
                ],
            ],
            'INNER JOIN'      => [
                'glpi_users'   => [
                    'ON' => [
                        $linktable     => 'users_id',
                        'glpi_users'   => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => [
                'realname',
                'firstname',
                'name',
            ],
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];
        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['users_id'],
                'link' => formatUserLink(
                    $line['users_id'],
                    $line['name'],
                    $line['realname'],
                    $line['firstname']
                ),
            ];
        }
        return $tab;
    }


    /** Get recipient of ITIL object between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct recipients which have ITIL object
     **/
    public function getUsedRecipientBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => [
                'glpi_users.id AS user_id',
                'glpi_users.name AS name',
                'glpi_users.realname AS realname',
                'glpi_users.firstname AS firstname',
            ],
            'DISTINCT'        => true,
            'FROM'            => $ctable,
            'LEFT JOIN'       => [
                'glpi_users'   => [
                    'ON' => [
                        $ctable        => 'users_id_recipient',
                        'glpi_users'   => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => [
                'realname',
                'firstname',
                'name',
            ],
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];

        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['user_id'],
                'link' => formatUserLink(
                    $line['user_id'],
                    $line['name'],
                    $line['realname'],
                    $line['firstname'],
                ),
            ];
        }
        return $tab;
    }


    /** Get groups which have itil object between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct groups of tickets
     **/
    public function getUsedGroupBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $linktable = $this->grouplinkclass::getTable();

        $ctable = $this->getTable();
        $criteria = [
            'SELECT' => [
                'glpi_groups.id',
                'glpi_groups.completename',
            ],
            'DISTINCT'        => true,
            'FROM'            => $ctable,
            'LEFT JOIN'       => [
                $linktable  => [
                    'ON' => [
                        $linktable  => $this->getForeignKeyField(),
                        $ctable     => 'id', [
                            'AND' => [
                                "$linktable.type"    => CommonITILActor::REQUESTER,
                            ],
                        ],
                    ],
                ],
            ],
            'INNER JOIN'      => [
                'glpi_groups'   => [
                    'ON' => [
                        $linktable     => 'groups_id',
                        'glpi_groups'   => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => [
                'glpi_groups.completename',
            ],
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];

        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['id'],
                'link' => htmlescape($line['completename']),
            ];
        }
        return $tab;
    }


    /** Get recipient of itil object between 2 dates
     *
     * @param string  $date1 begin date
     * @param string  $date2 end date
     * @param boolean $title indicates if stat if by title (true) or type (false)
     *
     * @return array contains the distinct recipents which have tickets
     **/
    public function getUsedUserTitleOrTypeBetween($date1 = '', $date2 = '', $title = true)
    {
        global $DB;

        $linktable = $this->userlinkclass::getTable();

        if ($title) {
            $table = "glpi_usertitles";
            $field = "usertitles_id";
        } else {
            $table = "glpi_usercategories";
            $field = "usercategories_id";
        }

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => "glpi_users.$field",
            'DISTINCT'        => true,
            'FROM'            => $ctable,
            'INNER JOIN'      => [
                $linktable  => [
                    'ON' => [
                        $linktable  => $this->getForeignKeyField(),
                        $ctable     => 'id',
                    ],
                ],
                'glpi_users'   => [
                    'ON' => [
                        $linktable     => 'users_id',
                        'glpi_users'   => 'id',
                    ],
                ],
            ],
            'LEFT JOIN'       => [
                $table         => [
                    'ON' => [
                        'glpi_users'   => $field,
                        $table         => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => [
                "glpi_users.$field",
            ],
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];
        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line[$field],
                'link' => htmlescape(Dropdown::getDropdownName($table, $line[$field])),
            ];
        }
        return $tab;
    }


    /**
     * Get priorities of ITIL object between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct priorities of tickets
     **/
    public function getUsedPriorityBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => 'priority',
            'DISTINCT'        => true,
            'FROM'            => $ctable,
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => 'priority',
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];
        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['priority'],
                'link' => htmlescape(static::getPriorityName($line['priority'])),
            ];
        }
        return $tab;
    }


    /**
     * Get urgencies of ITIL object between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct priorities of tickets
     **/
    public function getUsedUrgencyBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => 'urgency',
            'DISTINCT'        => true,
            'FROM'            => $ctable,
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => 'urgency',
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];

        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['urgency'],
                'link' => htmlescape(static::getUrgencyName($line['urgency'])),
            ];
        }
        return $tab;
    }


    /**
     * Get impacts of ITIL object between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct priorities of tickets
     **/
    public function getUsedImpactBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => 'impact',
            'DISTINCT'        => true,
            'FROM'            => $ctable,
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => 'impact',
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];

        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['impact'],
                'link' => htmlescape(static::getImpactName($line['impact'])),
            ];
        }
        return $tab;
    }


    /**
     * Get request types of ITIL object between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct request types of tickets
     **/
    public function getUsedRequestTypeBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => 'requesttypes_id',
            'DISTINCT'        => true,
            'FROM'            => $ctable,
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => 'requesttypes_id',
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];
        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['requesttypes_id'],
                'link' => htmlescape(Dropdown::getDropdownName('glpi_requesttypes', $line['requesttypes_id'])),
            ];
        }
        return $tab;
    }


    /**
     * Get solution types of ITIL object between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct request types of tickets
     **/
    public function getUsedSolutionTypeBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => 'solutiontypes_id',
            'DISTINCT'        => true,
            'FROM'            => ITILSolution::getTable(),
            'INNER JOIN'      => [
                $ctable   => [
                    'ON' => [
                        ITILSolution::getTable()   => 'items_id',
                        $ctable                    => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                ITILSolution::getTable() . ".itemtype" => $this->getType(),
                "$ctable.is_deleted"                   => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => 'solutiontypes_id',
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];
        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['solutiontypes_id'],
                'link' => htmlescape(Dropdown::getDropdownName('glpi_solutiontypes', $line['solutiontypes_id'])),
            ];
        }
        return $tab;
    }


    /** Get users which have intervention assigned to  between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct users which have any intervention assigned to.
     **/
    public function getUsedTechBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $linktable = $this->userlinkclass::getTable();

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => [
                'glpi_users.id AS users_id',
                'glpi_users.name AS name',
                'glpi_users.realname AS realname',
                'glpi_users.firstname AS firstname',
            ],
            'DISTINCT'        => true,
            'FROM'            => $ctable,
            'LEFT JOIN'       => [
                $linktable  => [
                    'ON' => [
                        $linktable  => $this->getForeignKeyField(),
                        $ctable     => 'id', [
                            'AND' => [
                                "$linktable.type"    => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
                'glpi_users'   => [
                    'ON' => [
                        $linktable     => 'users_id',
                        'glpi_users'   => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => [
                'realname',
                'firstname',
                'name',
            ],
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];

        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['users_id'],
                'link' => formatUserLink($line['users_id'], $line['name'], $line['realname'], $line['firstname']),
            ];
        }
        return $tab;
    }


    /** Get users which have followup assigned to  between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct users which have any followup assigned to.
     **/
    public function getUsedTechTaskBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $linktable = getTableForItemType(static::getTaskClass());

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => [
                'glpi_users.id AS users_id',
                'glpi_users.name AS name',
                'glpi_users.realname AS realname',
                'glpi_users.firstname AS firstname',
            ],
            'DISTINCT' => true,
            'FROM'            => $ctable,
            'LEFT JOIN'       => [
                $linktable  => [
                    'ON' => [
                        $linktable  => $this->getForeignKeyField(),
                        $ctable     => 'id',
                    ],
                ],
                'glpi_users'   => [
                    'ON' => [
                        $linktable     => 'users_id',
                        'glpi_users'   => 'id',
                    ],
                ],
                'glpi_profiles_users'   => [
                    'ON' => [
                        'glpi_users'            => 'id',
                        'glpi_profiles_users'   => 'users_id',
                    ],
                ],
                'glpi_profiles'         => [
                    'ON' => [
                        'glpi_profiles'         => 'id',
                        'glpi_profiles_users'   => 'profiles_id',
                    ],
                ],
                'glpi_profilerights'    => [
                    'ON' => [
                        'glpi_profiles'      => 'id',
                        'glpi_profilerights' => 'profiles_id',
                    ],
                ],
            ],
            'WHERE'           => [
                "$ctable.is_deleted"          => 0,
                'glpi_profilerights.name'     => 'ticket',
                'glpi_profilerights.rights'   => ['&', Ticket::OWN],
                "$linktable.users_id"         => ['<>', 0],
                ['NOT'                        => ["$linktable.users_id" => null]],
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => [
                'realname',
                'firstname',
                'name',
            ],
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];

        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['users_id'],
                'link' => formatUserLink($line['users_id'], $line['name'], $line['realname'], $line['firstname']),
            ];
        }
        return $tab;
    }


    /** Get enterprises which have itil object assigned to between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct enterprises which have any tickets assigned to.
     **/
    public function getUsedSupplierBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $linktable = $this->supplierlinkclass::getTable();

        $ctable = $this->getTable();
        $criteria = [
            'SELECT'          => [
                'glpi_suppliers.id AS suppliers_id_assign',
                'glpi_suppliers.name AS name',
            ],
            'DISTINCT'        => true,
            'FROM'            => $ctable,
            'LEFT JOIN'       => [
                $linktable        => [
                    'ON' => [
                        $linktable  => $this->getForeignKeyField(),
                        $ctable     => 'id', [
                            'AND' => [
                                "$linktable.type"    => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
                'glpi_suppliers'  => [
                    'ON' => [
                        $linktable        => 'suppliers_id',
                        'glpi_suppliers'  => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => [
                'name',
            ],
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];
        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['suppliers_id_assign'],
                'link' => '<a href="' . htmlescape(Supplier::getFormURLWithID($line['suppliers_id_assign'])) . '">' . htmlescape($line['name']) . '</a>',
            ];
        }
        return $tab;
    }


    /** Get groups assigned to itil object between 2 dates
     *
     * @param string $date1 begin date
     * @param string $date2 end date
     *
     * @return array contains the distinct groups assigned to a tickets
     **/
    public function getUsedAssignGroupBetween($date1 = '', $date2 = '')
    {
        global $DB;

        $linktable = $this->grouplinkclass::getTable();

        $ctable = $this->getTable();
        $criteria = [
            'SELECT' => [
                'glpi_groups.id',
                'glpi_groups.completename',
            ],
            'DISTINCT'        => true,
            'FROM'            => $ctable,
            'LEFT JOIN'       => [
                $linktable  => [
                    'ON' => [
                        $linktable  => $this->getForeignKeyField(),
                        $ctable     => 'id', [
                            'AND' => [
                                "$linktable.type"    => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
                'glpi_groups'   => [
                    'ON' => [
                        $linktable     => 'groups_id',
                        'glpi_groups'   => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "$ctable.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($ctable),
            'ORDERBY'         => [
                'glpi_groups.completename',
            ],
        ];

        if (!empty($date1) || !empty($date2)) {
            $criteria['WHERE'][] = [
                'OR' => [
                    getDateCriteria("$ctable.date", $date1, $date2),
                    getDateCriteria("$ctable.closedate", $date1, $date2),
                ],
            ];
        }

        $iterator = $DB->request($criteria);
        $tab    = [];
        foreach ($iterator as $line) {
            $tab[] = [
                'id'   => $line['id'],
                'link' => htmlescape($line['completename']),
            ];
        }
        return $tab;
    }


    /**
     * Display a line for an object
     *
     * @since 0.85 (before in each object with different parameters)
     *
     * @param $id                 Integer  ID of the object
     * @param $options            array of options
     *      row_num                : row num used for display
     *      type_for_massiveaction : itemtype for massive action
     *      id_for_massaction      : default 0 means no massive action
     *
     * @since 10.0.0 "followups" option has been dropped
     */
    public static function showShort($id, $options = [])
    {
        global $DB;

        //Toolbox::deprecated('Use CommonITILObject::getDatatableEntries() instead');
        $p = [
            'row_num'                => 0,
            'type_for_massiveaction' => 0,
            'id_for_massiveaction'   => 0,
            'followups'              => false,
            'ticket_stats'           => false,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $rand = mt_rand();

        /// TODO to be cleaned. Get datas and clean display links

        // Prints a job in short form
        // Should be called in a <table>-segment
        // Print links or not in case of user view
        // Make new job object and fill it from database, if success, print it
        $item         = new static();

        $candelete   = static::canDelete();
        $canupdate   = Session::haveRight(static::$rightname, UPDATE);
        $showprivate = Session::haveRight('followup', ITILFollowup::SEEPRIVATE);
        $align       = "class='left'";
        $align_desc  = "class='left'";

        $output = new HTMLSearchOutput();
        if ($item->getFromDB($id)) {
            $item_num = 1;
            $bgcolor  = htmlescape($_SESSION["glpipriority_" . $item->fields["priority"]]);
            echo $output::showNewLine($p['row_num'] % 2 === 1, $item->isDeleted());

            $check_col = '';
            if (
                ($candelete || $canupdate)
                && $p['id_for_massiveaction']
            ) {
                $check_col = Html::getMassiveActionCheckBox($p['type_for_massiveaction'], $p['id_for_massiveaction']);
            }
            echo $output::showItem($check_col, $item_num, $p['row_num'], $align);

            // First column ID
            echo $output::showItem((string) $item->getID(), $item_num, $p['row_num'], $align);

            // Second column TITLE
            $second_column = "<span class='b'>" . htmlescape($item->getName()) . "</span>&nbsp;";
            if ($item->canViewItem()) {
                $second_column  = sprintf(
                    __s('%1$s (%2$s)'),
                    "<a id='" . htmlescape($item::class . $item->getID() . $rand) . "' href=\"" . htmlescape($item->getLinkURL()) . "\">$second_column</a>",
                    sprintf(
                        __s('%1$s - %2$s'),
                        $item->numberOfFollowups($showprivate),
                        $item->numberOfTasks($showprivate)
                    )
                );
                $second_column = sprintf(
                    __s('%1$s %2$s'),
                    $second_column,
                    Html::showToolTip(
                        RichText::getEnhancedHtml($item->fields['content']),
                        [
                            'display' => false,
                            'applyto' => $item->getType() . $item->fields["id"] . $rand,
                        ]
                    )
                );
            }
            echo $output::showItem($second_column, $item_num, $p['row_num'], $align);

            // third column
            $third_col = static::getStatusIcon($item->fields["status"]);
            echo $output::showItem($third_col, $item_num, $p['row_num'], $align);

            // fourth column
            if ($item->fields['status'] == static::CLOSED) {
                $fourth_col = sprintf(
                    __('Closed on %s'),
                    Html::convDateTime($item->fields['closedate'])
                );
            } elseif ($item->fields['status'] == static::SOLVED) {
                $fourth_col = sprintf(
                    __('Solved on %s'),
                    Html::convDateTime($item->fields['solvedate'])
                );
            } elseif ($item->fields['begin_waiting_date']) {
                $fourth_col = sprintf(
                    __('Put on hold on %s'),
                    Html::convDateTime($item->fields['begin_waiting_date'])
                );
            } elseif ($item->fields['time_to_resolve']) {
                $fourth_col = sprintf(
                    __('%1$s: %2$s'),
                    __('Time to resolve'),
                    Html::convDateTime($item->fields['time_to_resolve'])
                );
            } else {
                $fourth_col = sprintf(
                    __('Opened on %s'),
                    Html::convDateTime($item->fields['date'])
                );
            }
            $fourth_col = htmlescape($fourth_col);

            echo $output::showItem($fourth_col, $item_num, $p['row_num'], $align . " width=130");

            // fifth column
            $fifth_col = htmlescape(Html::convDateTime($item->fields["date_mod"]));
            echo $output::showItem($fifth_col, $item_num, $p['row_num'], $align . " width=90");

            // sixth column
            if (count($_SESSION["glpiactiveentities"]) > 1) {
                $sixth_col = htmlescape(Dropdown::getDropdownName('glpi_entities', $item->fields['entities_id']));
                echo $output::showItem(
                    $sixth_col,
                    $item_num,
                    $p['row_num'],
                    $align . " width=100"
                );
            }

            // seventh Column
            echo $output::showItem(
                "<span class='b'>" . htmlescape(static::getPriorityName($item->fields["priority"])) . "</span>",
                $item_num,
                $p['row_num'],
                "$align bgcolor='$bgcolor'"
            );

            // eighth Column
            $eighth_col = "";
            foreach ($item->getUsers(CommonITILActor::REQUESTER) as $d) {
                $user = new User();
                if ($user->getFromDB($d["users_id"])) {
                    $eighth_col .= sprintf(
                        __s('%1$s %2$s'),
                        "<span class='b'>" . htmlescape($user->getName()) . "</span>",
                        Html::showToolTip(
                            $user->getInfoCard(),
                            [
                                'link'    => $user->getLinkURL(),
                                'display' => false,
                            ]
                        )
                    );
                    $eighth_col .= "<br>";
                }
            }

            foreach ($item->getGroups(CommonITILActor::REQUESTER) as $d) {
                $eighth_col .= htmlescape(Dropdown::getDropdownName("glpi_groups", $d["groups_id"]));
                $eighth_col .= "<br>";
            }

            echo $output::showItem($eighth_col, $item_num, $p['row_num'], $align);

            // ninth column
            $ninth_col = "";
            foreach ($item->getUsers(CommonITILActor::ASSIGN) as $d) {
                $user = new User();
                if (
                    Session::getCurrentInterface() == 'helpdesk'
                    && !empty($anon_name = User::getAnonymizedNameForUser(
                        $d['users_id'],
                        $item->getEntityID()
                    ))
                ) {
                    $ninth_col .= htmlescape($anon_name);
                } elseif ($user->getFromDB($d["users_id"])) {
                    $ninth_col .= sprintf(
                        __s('%1$s %2$s'),
                        "<span class='b'>" . htmlescape($user->getName()) . "</span>",
                        Html::showToolTip(
                            $user->getInfoCard(),
                            [
                                'link'    => $user->getLinkURL(),
                                'display' => false,
                            ]
                        )
                    );
                }
                $ninth_col .= "<br>";
            }

            foreach ($item->getGroups(CommonITILActor::ASSIGN) as $d) {
                if (
                    Session::getCurrentInterface() == 'helpdesk'
                    && !empty($anon_name = Group::getAnonymizedName($item->getEntityID()))
                ) {
                    $ninth_col .= htmlescape($anon_name);
                } else {
                    $ninth_col .= htmlescape(Dropdown::getDropdownName("glpi_groups", $d["groups_id"]));
                }
                $ninth_col .= "<br>";
            }

            foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $d) {
                $ninth_col .= htmlescape(Dropdown::getDropdownName("glpi_suppliers", $d["suppliers_id"]));
                $ninth_col .= "<br>";
            }
            echo $output::showItem($ninth_col, $item_num, $p['row_num'], $align);

            if (!$p['ticket_stats']) {
                // tenth Colum
                // Ticket : simple link to item
                $tenth_col  = "";
                $is_deleted = false;
                $item_ticket = new Item_Ticket();
                $data = $item_ticket->find(['tickets_id' => $item->fields['id']]);

                if ($item->getType() == 'Ticket') {
                    if (!empty($data)) {
                        foreach ($data as $val) {
                            if (!empty($val["itemtype"]) && ($val["items_id"] > 0)) {
                                if ($object = getItemForItemtype($val["itemtype"])) {
                                    if ($object->getFromDB($val["items_id"])) {
                                        $is_deleted = $object->isDeleted();

                                        $tenth_col .= htmlescape($object->getTypeName());
                                        $tenth_col .= " - <span class='b'>";
                                        if ($item->canView()) {
                                            $tenth_col .= $object->getLink();
                                        } else {
                                            $tenth_col .= htmlescape($object->getNameID());
                                        }
                                        $tenth_col .= "</span><br>";
                                    }
                                }
                            }
                        }
                    } else {
                        $tenth_col = __s('General');
                    }

                    echo $output::showItem($tenth_col, $item_num, $p['row_num'], ($is_deleted ? " class='center deleted' " : $align));
                }

                // Seventh column
                echo $output::showItem(
                    "<span class='b'>"
                        . htmlescape(Dropdown::getDropdownName('glpi_itilcategories', $item->fields["itilcategories_id"]))
                        . "</span>",
                    $item_num,
                    $p['row_num'],
                    $align
                );

                $plan          = $item->getTaskClassInstance();
                $items         = [];

                $result = $DB->request(
                    [
                        'FROM'  => $plan->getTable(),
                        'WHERE' => [
                            $item->getForeignKeyField() => $item->fields['id'],
                        ],
                    ]
                );

                $planned_info = [];

                foreach ($result as $plan) {
                    if (isset($plan['begin']) && $plan['begin']) {
                        $items[$plan['id']] = $plan['id'];

                        $planned_info[] = htmlescape(sprintf(__('From %s'), Html::convDateTime($plan['begin'])));
                        $planned_info[] = htmlescape(sprintf(__('To %s'), Html::convDateTime($plan['end'])));
                        if ($plan['users_id_tech']) {
                            $planned_info[] = htmlescape(sprintf(__('By %s'), getUserName($plan['users_id_tech'])));
                        }
                    }
                }

                $eleventh_column = '';
                if (count($items)) {
                    $eleventh_column = "<span class='pointer'
                                 id='" . htmlescape($item::class . $item->fields["id"]) . "planning$rand'>"
                                 . $eleventh_column . '</span>';
                    $eleventh_column = sprintf(
                        __s('%1$s %2$s'),
                        $eleventh_column,
                        Html::showToolTip(
                            implode('<br>', $planned_info),
                            [
                                'display' => false,
                                'applyto' => $item->getType() . $item->fields["id"] . "planning" . $rand,
                            ]
                        )
                    );
                }

                echo $output::showItem(
                    $eleventh_column,
                    $item_num,
                    $p['row_num'],
                    $align_desc . " width='150'"
                );
            } else {
                echo $output::showItem($second_column, $item_num, $p['row_num'], $align_desc . " width='200'");

                $takeintoaccountdelay_column = "";
                // Show only for tickets taken into account
                if ($item->fields['takeintoaccount_delay_stat'] > 0) {
                    $takeintoaccountdelay_column = htmlescape(Html::timestampToString($item->fields['takeintoaccount_delay_stat']));
                }
                echo $output::showItem($takeintoaccountdelay_column, $item_num, $p['row_num'], $align_desc . " width='150'");

                $solvedelay_column = "";
                // Show only for solved tickets
                if ($item->fields['solve_delay_stat'] > 0) {
                    $solvedelay_column = htmlescape(Html::timestampToString($item->fields['solve_delay_stat']));
                }
                echo $output::showItem($solvedelay_column, $item_num, $p['row_num'], $align_desc . " width='150'");

                $waiting_duration_column = htmlescape(Html::timestampToString($item->fields['waiting_duration']));
                echo $output::showItem($waiting_duration_column, $item_num, $p['row_num'], $align_desc . " width='150'");
            }

            // Finish Line
            echo $output::showEndLine();
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='6' ><i>" . __s('No item in progress.') . "</i></td></tr>";
        }
    }

    /**
     * @param integer $output_type Output type
     * @param string  $mass_id     id of the form to check all
     */
    public static function commonListHeader(
        $output_type = Search::HTML_OUTPUT,
        $mass_id = '',
        array $params = []
    ) {
        if ($output_type !== Search::HTML_OUTPUT) {
            Toolbox::deprecated('Only HTML output is allowed');
        }
        //Toolbox::deprecated('Use CommonITILObject::getCommonDatatableColumns() instead');
        $ticket_stats = $params['ticket_stats'] ?? false;

        // New Line for Header Items Line
        $output = new HTMLSearchOutput();
        echo $output::showNewLine();
        // $show_sort if
        $header_num                      = 1;

        $items                           = [];

        echo $output::showHeaderItem((empty($mass_id) ? '' : Html::getCheckAllAsCheckbox($mass_id)), $header_num);

        $items[__('ID')]           = "id";
        $items[__('Title')]        = "name";
        $items[__('Status')]             = "status";
        $items[_n('Date', 'Dates', 1)]               = "date";
        $items[__('Last update')]        = "date_mod";

        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $items[Entity::getTypeName(Session::getPluralNumber())] = "glpi_entities.completename";
        }

        $items[__('Priority')]           = "priority";
        $items[_n('Requester', 'Requesters', 1)]          = "users_id";
        $items[__('Assigned')]           = "users_id_assign";

        if (!$ticket_stats) {
            if (static::getType() == 'Ticket') {
                $items[_n('Associated element', 'Associated elements', Session::getPluralNumber())] = "";
            }
            $items[_n('Category', 'Categories', 1)]           = "glpi_itilcategories.completename";
            $items[__('Planification')]      = "glpi_tickettasks.begin";
        } else {
            $items[__('Take into account')] = "takeintoaccount_delay_stat";
            $items[__('Resolution')]        = "solve_delay_stat";
            $items[__('Pending')]           = "waiting_duration";
        }

        foreach (array_keys($items) as $key) {
            echo $output::showHeaderItem(htmlescape($key), $header_num);
        }

        // End Line for column headers
        echo $output::showEndLine();
    }

    /**
     * @param array{ticket_stats?: bool} $params
     * @return array{columns: array, formatters: array} Array of columns and formatters to be used in datatables (templates/components/datatable.html.twig) that are common to all ITIL objects.
     * @see CommonITILObject::getDatatableEntries()
     * @note If the columns are changed, you must also update the `getDatatableEntries` method to match the new columns.
     */
    final public static function getCommonDatatableColumns(array $params = []): array
    {
        $params = array_replace([
            'ticket_stats' => false,
        ], $params);

        $columns = [
            'item_id' => __('ID'), // Not using 'id' as it is used internally by the datatable to signify the ID of the item listed (which is probably a link)
            'name'    => __('Title'),
            'status'  => __('Status'),
            'date'    => _n('Date', 'Dates', 1),
            'date_mod' => __('Last update'),
        ];
        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['priority'] = __('Priority');
        $columns['requester'] = _n('Requester', 'Requesters', 1);
        $columns['assigned'] = __('Assigned');
        if (!$params['ticket_stats']) {
            $columns['associated_elements'] = _n('Associated element', 'Associated elements', Session::getPluralNumber());
            $columns['category'] = _n('Category', 'Categories', 1);
            $columns['planification'] = __('Planification');
        } else {
            $columns['take_into_account'] = __('Take into account');
            $columns['resolution'] = __('Resolution');
            $columns['pending'] = __('Pending');
        }
        return [
            'columns' => $columns,
            'formatters' => [
                'name' => 'raw_html',
                'status' => 'raw_html',
                'date_mod' => 'datetime',
                'priority' => 'badge',
                'requester' => 'raw_html',
                'assigned' => 'raw_html',
                'planification' => 'raw_html',
                'associated_elements' => 'raw_html',
                'take_into_account' => 'duration',
                'resolution' => 'duration',
                'pending' => 'duration',
            ],
        ];
    }

    /**
     * @param array{item_id: int, id: int, itemtype: class-string<CommonITILObject>}[] $data
     *        - item_id: The ID of the ITIL object
     *        - id: The ID of the entry in the datatable (probably the ID of the link between the ITIL item and another item)
     *       - itemtype: The class name of the ITIL object
     * @param array{ticket_stats?: bool} $params
     * @return array The data with the other required fields added
     * @see CommonITILObject::getCommonDatatableColumns()
     */
    public static function getDatatableEntries(array $data, $params = []): array
    {
        $params = array_replace([
            'ticket_stats' => false,
        ], $params);

        $showprivate_fup = Session::haveRight('followup', ITILFollowup::SEEPRIVATE);
        $showprivate_task = [];
        $rand = mt_rand();
        // Cache of entity names
        $entity_cache = [];
        // Cache of user names
        $user_cache = [];
        // Cache of group names
        $group_cache = [];
        // Cache of supplier names
        $supplier_cache = [];
        // Cache of asset links
        $asset_cache = [];
        // Cache of category names
        $category_cache = [];

        foreach ($data as &$entry) {
            $itemtype = $entry['itemtype'];
            /** @var CommonITILObject $item */
            $item = getItemForItemtype($itemtype);
            if (!$item->getFromDB($entry['item_id'])) {
                unset($entry);
                continue;
            }
            $name_link_id = $entry['id'] . $rand;

            if (!isset($showprivate_task[$itemtype])) {
                /** @var CommonITILTask $taskclass */
                $taskclass = $item->getTaskClass();
                $showprivate_task[$itemtype] = Session::haveRight($taskclass::$rightname, CommonITILTask::SEEPRIVATE);
            }

            $name = '<span class="fw-bold">' . htmlescape($item->getName()) . '</span>';
            if ($item->canViewItem()) {
                $name  = sprintf(
                    __s('%1$s (%2$s)'),
                    '<a id="' . htmlescape($name_link_id) . '" href="' . htmlescape($item->getLinkURL()) . '">' . $name . '</a><br>',
                    sprintf(
                        __s('%1$s - %2$s'),
                        $item->numberOfFollowups($showprivate_fup),
                        $item->numberOfTasks($showprivate_task[$itemtype])
                    )
                );
                $name = sprintf(
                    __s('%1$s %2$s'),
                    $name,
                    Html::showToolTip(
                        RichText::getEnhancedHtml($item->fields['content']),
                        [
                            'display' => false,
                            'applyto' => $name_link_id,
                        ]
                    )
                );
            }
            $entry['name'] = $name;
            $entry['status'] = static::getStatusIcon($item->fields["status"]);
            $entry['date'] = match (true) {
                $item->fields['status'] === static::CLOSED => sprintf(
                    __s('Closed on %s'),
                    Html::convDateTime($item->fields['closedate'])
                ),
                $item->fields['status'] === static::SOLVED => sprintf(
                    __s('Solved on %s'),
                    Html::convDateTime($item->fields['solvedate'])
                ),
                !empty($item->fields['begin_waiting_date']) => sprintf(
                    __s('Put on hold on %s'),
                    Html::convDateTime($item->fields['begin_waiting_date'])
                ),
                !empty($item->fields['time_to_resolve']) => sprintf(
                    __s('%1$s: %2$s'),
                    __s('Time to resolve'),
                    Html::convDateTime($item->fields['time_to_resolve'])
                ),
                default => sprintf(
                    __s('Opened on %s'),
                    Html::convDateTime($item->fields['date'])
                ),
            };
            $entry['date_mod'] = $item->fields["date_mod"];

            if (count($_SESSION["glpiactiveentities"]) > 1) {
                if (!isset($entity_cache[$item->fields['entities_id']])) {
                    $entity_cache[$item->fields['entities_id']] = Dropdown::getDropdownName(
                        table: 'glpi_entities',
                        id: $item->fields['entities_id'],
                        default: ''
                    );
                }
                $entry['entity'] = $entity_cache[$item->fields['entities_id']];
            }

            $priority_name = static::getPriorityName($item->fields["priority"]);
            $priority_color = $_SESSION["glpipriority_" . $item->fields["priority"]];
            $entry['priority'] = [
                'content' => $priority_name,
                'color' => $priority_color,
            ];

            $entry['requester'] = '';
            foreach ($item->getUsers(CommonITILActor::REQUESTER) as $d) {
                $user = new User();
                if (!isset($user_cache[$d["users_id"]]) && $user->getFromDB($d["users_id"])) {
                    $user_value = sprintf(
                        __s('%1$s %2$s'),
                        htmlescape($user->getName()),
                        Html::showToolTip(
                            $user->getInfoCard(),
                            [
                                'link'    => $user->getLinkURL(),
                                'display' => false,
                            ]
                        )
                    );
                    $user_cache[$d['users_id']] = $user_value;
                }
                $entry['requester'] .= isset($user_cache[$d["users_id"]]) ? $user_cache[$d['users_id']] . '<br>' : '';
            }
            foreach ($item->getGroups(CommonITILActor::REQUESTER) as $d) {
                if (!isset($group_cache[$d['groups_id']])) {
                    $group_cache[$d['groups_id']] = Dropdown::getDropdownName(table: 'glpi_groups', id: $d['groups_id'], default: '');
                }
                $entry['requester'] .= htmlescape($group_cache[$d['groups_id']]) . '<br>';
            }

            $entry['assigned'] = '';
            foreach ($item->getUsers(CommonITILActor::ASSIGN) as $d) {
                if (Session::getCurrentInterface() === 'helpdesk' && !empty($anon_name = User::getAnonymizedNameForUser($d['users_id'], $item->getEntityID()))) {
                    $entry['assigned'] .= htmlescape($anon_name) . '<br>';
                } else {
                    $user = new User();
                    if (!isset($user_cache[$d["users_id"]]) && $user->getFromDB($d["users_id"])) {
                        $user_value = sprintf(
                            __s('%1$s %2$s'),
                            htmlescape($user->getName()),
                            Html::showToolTip(
                                $user->getInfoCard(),
                                [
                                    'link'    => $user->getLinkURL(),
                                    'display' => false,
                                ]
                            )
                        );
                        $user_cache[$d['users_id']] = $user_value;
                    }
                    $entry['assigned'] .= isset($user_cache[$d["users_id"]]) ? $user_cache[$d['users_id']] . '<br>' : '';
                }
            }
            foreach ($item->getGroups(CommonITILActor::ASSIGN) as $d) {
                if (Session::getCurrentInterface() === 'helpdesk' && !empty($anon_name = Group::getAnonymizedName($item->getEntityID()))) {
                    $entry['assigned'] .= htmlescape($anon_name) . '<br>';
                } else {
                    if (!isset($group_cache[$d['groups_id']])) {
                        $group_cache[$d['groups_id']] = Dropdown::getDropdownName(table: 'glpi_groups', id: $d['groups_id'], default: '');
                    }
                    $entry['assigned'] .= htmlescape($group_cache[$d['groups_id']]) . '<br>';
                }
            }
            foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $d) {
                if (!isset($supplier_cache[$d['suppliers_id']])) {
                    $supplier_cache[$d['suppliers_id']] = Dropdown::getDropdownName(table: 'glpi_suppliers', id: $d['suppliers_id'], default: '');
                }
                $entry['assigned'] .= htmlescape($supplier_cache[$d['suppliers_id']]) . '<br>';
            }

            if (!$params['ticket_stats']) {
                $item_itil = getItemForItemtype(static::getItemLinkClass());
                $linked_items = $item_itil->find([
                    $itemtype::getForeignKeyField() => $item->getID(),
                ]);
                $linked_items = array_filter($linked_items, static fn($val) => !empty($val["itemtype"]) && $val["items_id"] > 0);
                $entry['associated_elements'] = '';
                foreach ($linked_items as $val) {
                    if (!isset($asset_cache[$val['itemtype']][$val['items_id']])) {
                        $object = getItemForItemtype($val["itemtype"]);
                        if ($object && $object->getFromDB($val["items_id"])) {
                            $asset_cache[$val['itemtype']][$val['items_id']] = $object::canView() ? $object->getLink() : htmlescape($object->getNameID());
                        }
                    }
                    if (isset($asset_cache[$val['itemtype']][$val['items_id']])) {
                        $entry['associated_elements'] .= $asset_cache[$val['itemtype']][$val['items_id']] . '<br>';
                    }
                }

                if (!isset($category_cache[$item->fields['itilcategories_id']])) {
                    $category_cache[$item->fields['itilcategories_id']] = Dropdown::getDropdownName(
                        table: 'glpi_itilcategories',
                        id: $item->fields['itilcategories_id'],
                        default: ''
                    );
                }
                $entry['category'] = $category_cache[$item->fields['itilcategories_id']];

                $planned_info = '';

                $plan          = $itemtype::getTaskClassInstance();
                $items         = [];

                global $DB;
                $result = $DB->request(
                    [
                        'FROM'  => $plan->getTable(),
                        'WHERE' => [
                            $itemtype::getForeignKeyField() => $item->getID(),
                        ],
                    ]
                );
                foreach ($result as $plan) {
                    if (isset($plan['begin']) && $plan['begin']) {
                        $items[$plan['id']] = $plan['id'];
                        $planned_info .= htmlescape(sprintf(__('From %s'), Html::convDateTime($plan['begin']))) . '<br>';
                        $planned_info .= htmlescape(sprintf(__('To %s'), Html::convDateTime($plan['end']))) . '<br>';
                        if ($plan['users_id_tech']) {
                            $user = new User();
                            if (!isset($user_cache[$plan["users_id_tech"]]) && $user->getFromDB($plan["users_id_tech"])) {
                                $user_value = sprintf(
                                    __s('%1$s %2$s'),
                                    htmlescape($user->getName()),
                                    Html::showToolTip(
                                        $user->getInfoCard(),
                                        [
                                            'link'    => $user->getLinkURL(),
                                            'display' => false,
                                        ]
                                    )
                                );
                                $user_cache[$plan['users_id']] = $user_value;
                            }
                            $planned_info .= sprintf(__s('By %s'), $user_cache[$plan['users_id_tech']]);
                        }
                        $planned_info .= "<br>";
                    }
                }

                if (count($items)) {
                    $planification_id = "{$itemtype}{$item->fields['id']}planning{$rand}";
                    $entry['planification'] = sprintf(
                        __s('%1$s %2$s'),
                        "<span class='pointer' id='" . htmlescape($planification_id) . "'>" . count($items) . '</span>',
                        Html::showToolTip(
                            $planned_info,
                            [
                                'display' => false,
                                'applyto' => $planification_id,
                            ]
                        )
                    );
                }
            } else {
                $entry['take_into_account'] = $item->fields['takeintoaccount_delay_stat'];
                $entry['resolution'] = $item->fields['solve_delay_stat'];
                $entry['pending'] = $item->fields['waiting_duration'];
            }
        }
        unset($entry);
        return $data;
    }

    /**
     * Get correct Calendar: Entity or Sla
     *
     * @since 0.90.4
     *
     **/
    public function getCalendar()
    {
        return Entity::getUsedConfig(
            'calendars_strategy',
            $this->fields['entities_id'],
            'calendars_id',
            0
        );
    }


    /**
     * Summary of getTimelinePosition
     * Returns the position of the $sub_type for the $user_id in the timeline
     *
     * @param int $items_id is the id of the ITIL object
     * @param string $sub_type is ITILFollowup, Document_Item, TicketTask, TicketValidation or Solution
     * @param int $users_id
     * @since 9.2
     */
    public static function getTimelinePosition($items_id, $sub_type, $users_id)
    {
        $itilobject = new static();
        $itilobject->fields['id'] = $items_id;
        $actors = $itilobject->getITILActors();

        // 1) rule for followups, documents, tasks and validations:
        //    Matrix for position of timeline objects
        //    R O A (R=Requester, O=Observer, A=AssignedTo)
        //    0 0 0 -> depending on the interface: central -> right, helpdesk -> left
        //    0 0 1 -> Right
        //    0 1 0 -> Left
        //    0 1 1 -> R
        //    1 0 0 -> L
        //    1 0 1 -> L
        //    1 1 0 -> L
        //    1 1 1 -> L
        //    if users_id is not in the actor list, then pos is left
        // 2) rule for solutions: always on the right side

        // default position is left
        $pos = self::TIMELINE_LEFT;

        $pos_matrix = [];
        $pos_matrix[0][0][0] = Session::getCurrentInterface() == "central"
            ? self::TIMELINE_RIGHT
            : self::TIMELINE_LEFT;
        $pos_matrix[0][0][1] = self::TIMELINE_RIGHT;
        $pos_matrix[0][1][1] = self::TIMELINE_RIGHT;

        switch ($sub_type) {
            case ITILFollowup::class:
            case Document_Item::class:
            case static::getTaskClassInstance()::class:
            case static::getValidationClassInstance()::class:
                if (isset($actors[$users_id])) {
                    $r = in_array(CommonITILActor::REQUESTER, $actors[$users_id]) ? 1 : 0;
                    $o = in_array(CommonITILActor::OBSERVER, $actors[$users_id]) ? 1 : 0;
                    $a = in_array(CommonITILActor::ASSIGN, $actors[$users_id]) ? 1 : 0;
                    if (isset($pos_matrix[$r][$o][$a])) {
                        $pos = $pos_matrix[$r][$o][$a];
                    }
                }
                break;
            case ITILSolution::class:
                $pos = self::TIMELINE_RIGHT;
                break;
        }

        return $pos;
    }


    public function getTimelineItemtypes(): array
    {
        global $PLUGIN_HOOKS;

        $obj_type = static::getType();
        $foreign_key = static::getForeignKeyField();

        //check sub-items rights
        $tmp = [$foreign_key => $this->getID()];
        $fup = new ITILFollowup();
        $fup->getEmpty();
        $fup->fields['itemtype'] = $obj_type;
        $fup->fields['items_id'] = $this->getID();

        $task_class = static::getTaskClass();
        $task = static::getTaskClassInstance();

        $solved_statuses = static::getSolvedStatusArray();
        $closed_statuses = static::getClosedStatusArray();
        $solved_closed_statuses = array_merge($solved_statuses, $closed_statuses);

        $canadd_fup = $fup->can(-1, CREATE, $tmp) && !in_array($this->fields["status"], $solved_closed_statuses, true) || isset($_GET['_openfollowup']);
        $canadd_task = $task->can(-1, CREATE, $tmp) && !in_array($this->fields["status"], $solved_closed_statuses, true);
        $canadd_document = $canadd_fup || ($this->canAddItem('Document') && !in_array($this->fields["status"], $solved_closed_statuses, true));
        $canadd_solution = $obj_type::canUpdate() && $this->canSolve() && !in_array($this->fields["status"], $solved_statuses, true) && $this->checkRequiredFieldsFilled();

        $validation = static::getValidationClassInstance();
        $canadd_validation = $validation !== null
            && $validation->can(-1, CREATE, $tmp)
            && !in_array($this->fields["status"], $solved_closed_statuses, true);

        $itemtypes = [];

        $itemtypes['answer'] = [
            'type'          => 'ITILFollowup',
            'class'         => 'ITILFollowup',
            'icon'          => ITILFollowup::getIcon(),
            'label'         => _x('button', 'Answer'),
            'short_label'   => _x('button', 'Answer'),
            'template'      => 'components/itilobject/timeline/form_followup.html.twig',
            'item'          => $fup,
            'hide_in_menu'  => !$canadd_fup,
        ];
        $itemtypes['task'] = [
            'type'          => 'ITILTask',
            'class'         => $task_class,
            'icon'          => CommonITILTask::getIcon(),
            'label'         => _x('button', 'Create a task'),
            'short_label'   => _x('button', 'Task'),
            'template'      => 'components/itilobject/timeline/form_task.html.twig',
            'item'          => $task,
            'hide_in_menu'  => !$canadd_task,
        ];
        $itemtypes['solution'] = [
            'type'          => 'ITILSolution',
            'class'         => 'ITILSolution',
            'icon'          => ITILSolution::getIcon(),
            'label'         => _x('button', 'Add a solution'),
            'short_label'   => _x('button', 'Solution'),
            'template'      => 'components/itilobject/timeline/form_solution.html.twig',
            'item'          => new ITILSolution(),
            'hide_in_menu'  => !$canadd_solution,
        ];
        $itemtypes['document'] = [
            'type'          => 'Document_Item',
            'class'         => Document_Item::class,
            'icon'          => Document_Item::getIcon(),
            'label'         => _x('button', 'Add a document'),
            'short_label'   => _x('button', 'Document'),
            'template'      => 'components/itilobject/timeline/form_document_item.html.twig',
            'item'          => new Document_Item(),
            'hide_in_menu'  => !$canadd_document,
        ];
        if ($validation !== null) {
            $itemtypes['validation'] = [
                'type'          => 'ITILValidation',
                'class'         => $validation::getType(),
                'icon'          => CommonITILValidation::getIcon(),
                'label'         => _x('button', 'Ask for approval'),
                'short_label'   => CommonITILValidation::getTypeName(1),
                'template'      => 'components/itilobject/timeline/form_validation.html.twig',
                'item'          => $validation,
                'hide_in_menu'  => !$canadd_validation,
            ];
        }

        if (isset($PLUGIN_HOOKS[Hooks::TIMELINE_ANSWER_ACTIONS])) {
            /**
             * @var string $plugin
             * @var array|callable $hook_callable
             */
            foreach ($PLUGIN_HOOKS[Hooks::TIMELINE_ANSWER_ACTIONS] as $plugin => $hook_callable) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if (is_callable($hook_callable)) {
                    $hook_itemtypes = $hook_callable(['item' => $this]);
                } else {
                    $hook_itemtypes = $hook_callable;
                }
                if (is_array($hook_itemtypes)) {
                    $itemtypes = array_merge($itemtypes, $hook_itemtypes);
                } else {
                    trigger_error(
                        sprintf('"%s" hook callback result should be an array, "%s" returned.', Hooks::TIMELINE_ANSWER_ACTIONS, gettype($hook_itemtypes)),
                        E_USER_WARNING
                    );
                }
            }
        }

        return $itemtypes;
    }

    /**
     * Get an HTML string of all timeline actions/buttons provided by plugins via the {@link Hooks::TIMELINE_ACTIONS}  hook.
     * @return string
     * @since 10.0.0
     */
    public function getLegacyTimelineActionsHTML(): string
    {
        global $PLUGIN_HOOKS;

        $legacy_actions = '';

        ob_start();
        Plugin::doHook(Hooks::TIMELINE_ACTIONS, [
            'rand'   => mt_rand(),
            'item'   => $this,
        ]);
        $legacy_actions .= ob_get_clean();

        return $legacy_actions;
    }

    /**
     * Retrieves all timeline items for this ITILObject
     *
     * @param array $options Possible options:
     * - with_documents     : include documents elements
     * - with_logs          : include log entries
     * - with_validations   : include validation elements
     * - sort_by_date_desc  : sort timeline items by date
     * - check_view_rights  : indicates whether current session rights should be checked for view rights
     * - hide_private_items : force hiding private items (followup/tasks), even if session allow it
     * @since 9.4.0
     *
     * @return mixed[] Timeline items
     */
    public function getTimelineItems(array $options = [])
    {

        $params = [
            'with_documents'     => true,
            'with_logs'          => true,
            'with_validations'   => true,
            'sort_by_date_desc'  => $_SESSION['glpitimeline_order'] == CommonITILObject::TIMELINE_ORDER_REVERSE,

            // params used by notifications process (as session cannot be used there)
            'check_view_rights'  => true,
            'hide_private_items' => false,
        ];

        if (array_key_exists('bypass_rights', $options) && $options['bypass_rights']) {
            Toolbox::deprecated('Using `bypass_rights` parameter is deprecated.');
            $params['check_view_rights'] = false;
        }
        if (array_key_exists('expose_private', $options) && $options['expose_private']) {
            Toolbox::deprecated('Using `expose_private` parameter is deprecated.');
            $params['hide_private_items'] = false;
        }
        if (array_key_exists('is_self_service', $options) && $options['is_self_service']) {
            Toolbox::deprecated('Using `is_self_service` parameter is deprecated.');
            $params['hide_private_items'] = false;
        }

        if (count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        if ($this->isNewItem()) {
            return [];
        }

        if ($params['check_view_rights'] && !$this->canViewItem()) {
            return [];
        }

        /** @var CommonITILObject $objType */
        $objType    = static::getType();
        $foreignKey = static::getForeignKeyField();
        $timeline = [];

        $canupdate_parent = $this->canUpdateItem() && !in_array($this->fields['status'], static::getClosedStatusArray());

        //checks rights
        $restrict_fup = $restrict_task = [];
        if (
            $params['hide_private_items']
            || ($params['check_view_rights'] && !Session::haveRight("followup", ITILFollowup::SEEPRIVATE))
        ) {
            if (!$params['check_view_rights']) {
                // notification case, we cannot rely on session
                $restrict_fup = [
                    'is_private' => 0,
                ];
            } else {
                $restrict_fup = [
                    'OR' => [
                        'is_private' => 0,
                        'users_id'   => Session::getCurrentInterface() === "central" ? (int) Session::getLoginUserID() : 0,
                    ],
                ];
            }
        }

        $restrict_fup['itemtype'] = static::getType();
        $restrict_fup['items_id'] = $this->getID();

        $taskClass = static::getTaskClass();
        $task_obj  = static::getTaskClassInstance();
        if (
            $task_obj->maybePrivate()
            && (
                $params['hide_private_items']
                || ($params['check_view_rights'] && !Session::haveRight($task_obj::$rightname, CommonITILTask::SEEPRIVATE))
            )
        ) {
            if (!$params['check_view_rights']) {
                // notification case, we cannot rely on session
                $restrict_task = [
                    'is_private' => 0,
                ];
            } else {
                $current_user_id = (Session::getCurrentInterface() === "central") ? (int) Session::getLoginUserID() : 0;

                $restrict_task = [
                    'OR' => [
                        'is_private' => 0,
                        'users_id'   => $current_user_id,
                        'users_id_tech' => $current_user_id,
                    ],
                ];

                $groupsuser = Group_User::getUserGroups($current_user_id);
                if ($groupsuser !== []) {
                    foreach ($groupsuser as $groupuser) {
                        $groups_ids[] = $groupuser['id'];
                    }
                    if (Session::haveRight($task_obj::$rightname, CommonITILTask::SEEPRIVATEGROUPS)) {
                        $restrict_task['OR']['groups_id_tech'] = $groups_ids;
                    }
                }
            }
        }

        // Add followups to timeline
        $followup_obj = new ITILFollowup();
        if (!$params['check_view_rights'] || $followup_obj->canview()) {
            $followups = $followup_obj->find(
                ['items_id'  => $this->getID()] + $restrict_fup,
                ['date_creation DESC', 'id DESC']
            );

            foreach ($followups as $followups_id => $followup_row) {
                // Safer to use a clean object to load our data
                $followup = new ITILFollowup();
                $followup->setParentItem($this);
                $followup->fields = $followup_row;
                $followup->post_getFromDB();

                if (!$params['check_view_rights'] || $followup->canViewItem()) {
                    $followup_row['can_edit'] = $followup->canUpdateItem();
                    $followup_row['can_promote']
                        = Session::getCurrentInterface() === 'central'
                        && $this instanceof Ticket
                        && Ticket::canCreate()
                    ;
                    $timeline["ITILFollowup_" . $followups_id] = [
                        'type'     => ITILFollowup::class,
                        'item'     => $followup_row,
                        'object'   => $followup,
                        'itiltype' => 'Followup',
                    ];
                }
            }
        }

        // Add tasks to timeline
        if (!$params['check_view_rights'] || $task_obj->canview()) {
            $tasks = $task_obj->find(
                [$foreignKey => $this->getID()] + $restrict_task,
                'date_creation DESC'
            );

            foreach ($tasks as $tasks_id => $task_row) {
                // Safer to use a clean object to load our data
                $tltask = getItemForItemtype($taskClass);
                $tltask->fields = $task_row;
                $tltask->post_getFromDB();

                if (!$params['check_view_rights'] || $tltask->canViewItem()) {
                    $task_row['can_edit'] = $tltask->canUpdateItem();
                    $task_row['can_promote']
                        = Session::getCurrentInterface() === 'central'
                        && $this instanceof Ticket
                        && Ticket::canCreate()
                    ;
                    $timeline[$tltask::getType() . "_" . $tasks_id] = [
                        'type'     => $taskClass,
                        'item'     => $task_row,
                        'object'   => $tltask,
                        'itiltype' => 'Task',
                    ];
                }
            }
        }

        // Add solutions to timeline
        $solution_obj   = new ITILSolution();
        $solution_items = $solution_obj->find([
            'itemtype'  => static::getType(),
            'items_id'  => $this->getID(),
        ]);

        foreach ($solution_items as $solution_item) {
            // Safer to use a clean object to load our data
            $solution = new ITILSolution();
            $solution->setParentItem($this);
            $solution->fields = $solution_item;
            $solution->post_getFromDB();

            $timeline["ITILSolution_" . $solution_item['id'] ] = [
                'type'     => ITILSolution::class,
                'itiltype' => 'Solution',
                'item'     => [
                    'id'                 => $solution_item['id'],
                    'content'            => $solution_item['content'],
                    'date'               => $solution_item['date_creation'],
                    'users_id'           => $solution_item['users_id'],
                    'solutiontypes_id'   => $solution_item['solutiontypes_id'],
                    'can_edit'           => $objType::canUpdate() && $this->canSolve(),
                    'timeline_position'  => self::TIMELINE_RIGHT,
                    'users_id_editor'    => $solution_item['users_id_editor'],
                    'date_creation'      => $solution_item['date_creation'],
                    'date_mod'           => $solution_item['date_mod'],
                    'users_id_approval'  => $solution_item['users_id_approval'],
                    'date_approval'      => $solution_item['date_approval'],
                    'status'             => $solution_item['status'],
                ],
                'object' => $solution,
            ];
        }

        // Add validation to timeline
        $validation_class = $objType::getValidationClassName();
        if (
            class_exists($validation_class ?? '') && $params['with_validations']
            && (!$params['check_view_rights'] || $validation_class::canView())
        ) {
            /** @var CommonITILValidation $validation_obj */
            $validation_obj = getItemForItemtype($validation_class);
            $validations = $validation_obj->find([
                $foreignKey => $this->getID(),
            ]);

            foreach ($validations as $validations_id => $validation_row) {
                // Safer to use a clean object to load our data
                /** @var CommonITILValidation $validation */
                $validation = getItemForItemtype($validation_class);
                $validation->fields = $validation_row;
                $validation->post_getFromDB();

                $canedit = $validation_obj->can($validations_id, UPDATE);
                $cananswer = $validation_obj->canAnswer()
                    && $validation_row['status'] == CommonITILValidation::WAITING
                    && !$this->isSolved(true);
                $user = new User();
                $user->getFromDB($validation_row['users_id_validate']);

                $request_key = $validation_obj::getType() . '_' . $validations_id
                    . (empty($validation_row['validation_date']) ? '' : '_request'); // If no answer, no suffix to see attached documents on request

                $content = __s('Approval request');
                if (is_a($validation_row['itemtype_target'], CommonDBTM::class, true)) {
                    $validation_target = new $validation_row['itemtype_target']();
                    if ($validation_target->getFromDB($validation_row['items_id_target'])) {
                        $content .= " <i class='ti ti-arrow-right'></i><i class='" . htmlescape($validation_target->getIcon()) . " text-muted me-1'></i>"
                            . $validation_target->getlink();
                    }
                }
                $timeline[$request_key] = [
                    'type' => $validation_class,
                    'item' => [
                        'id'        => $validations_id,
                        'date'      => $validation_row['submission_date'],
                        'content'   => $content,
                        'comment_submission' => $validation_row['comment_submission'],
                        'users_id'  => $validation_row['users_id'],
                        'can_edit'  => $canedit,
                        'can_answer'   => $cananswer,
                        'users_id_validate' => ((int) $validation_row['users_id_validate'] > 0)
                            ? $validation_row['users_id_validate']
                            // 'users_id_validate' will be set to current user id once answer will be submitted
                            : ($cananswer ? Session::getLoginUserID() : 0),
                        'timeline_position' => $validation_row['timeline_position'],
                    ],
                    'itiltype' => 'Validation',
                    'class'    => 'validation-request '
                    . ($validation_row['status'] == CommonITILValidation::WAITING ? "validation-waiting" : "")
                    . ($validation_row['status'] == CommonITILValidation::ACCEPTED ? "validation-accepted" : "")
                    . ($validation_row['status'] == CommonITILValidation::REFUSED ? "validation-refused" : ""),
                    'item_action' => 'validation-request',
                    'object'      => $validation,
                ];

                if (!empty($validation_row['validation_date'])) {
                    $timeline[$validation_obj::getType() . "_" . $validations_id] = [
                        'type' => $validation_class,
                        'item' => [
                            'id'        => $validations_id,
                            'date'      => $validation_row['validation_date'],
                            'content'   => __s('Approval request answer') . " : " . $validation_class::getStatus($validation_row['status']),
                            'comment_validation' => $validation_row['comment_validation'],
                            'users_id'  => $validation_row['users_id_validate'],
                            'status'    => "status_" . $validation_row['status'],
                            'can_edit'  => $canedit,
                            'timeline_position' => $validation_row['timeline_position'],
                            'itemtype_target' => $validation_row['itemtype_target'],
                            'items_id_target' => $validation_row['items_id_target'],
                        ],
                        'class'       => 'validation-answer',
                        'itiltype'    => 'Validation',
                        'item_action' => 'validation-answer',
                        'object'      => $validation,
                    ];
                }
            }
        }

        // Add documents to timeline
        if ($params['with_documents']) {
            $document_item_obj = new Document_Item();
            $document_obj      = new Document();
            $document_items    = $document_item_obj->find([
                $this->getAssociatedDocumentsCriteria(!$params['check_view_rights']),
                'timeline_position'  => ['>', self::NO_TIMELINE],
            ]);
            foreach ($document_items as $document_item) {
                if (!$document_obj->getFromDB($document_item['documents_id'])) {
                    // Orphan `Document_Item`
                    continue;
                }

                $item = $document_obj->fields;
                $item['date'] = $document_item['date'] ?? $document_item['date_creation'];
                // #1476 - set date_creation, date_mod and owner to attachment ones
                $item['date_creation'] = $document_item['date_creation'];
                $item['date_mod'] = $document_item['date_mod'];
                $item['users_id'] = $document_item['users_id'];
                $item['documents_item_id'] = $document_item['id'];

                $item['timeline_position'] = $document_item['timeline_position'];
                $item['_can_edit'] = Document::canUpdate() && $document_obj->canUpdateItem();
                $item['_can_delete'] = Document::canDelete() && $document_obj->canDeleteItem() && $canupdate_parent;

                $timeline_key = $document_item['itemtype'] . "_" . $document_item['items_id'];
                $doc_entry = [
                    'type' => Document_Item::class,
                    'item' => $item,
                    '_is_image' => false,
                ];
                $docpath = GLPI_DOC_DIR . "/" . $item['filepath'];
                $is_image = Document::isImage($docpath);
                if ($is_image) {
                    $doc_entry['_is_image'] = true;
                    $doc_entry['_size'] = getimagesize($docpath);
                }
                if ($document_item['itemtype'] == static::getType()) {
                    // document associated directly to itilobject
                    $doc_entry['object'] = $document_obj;
                    $timeline["Document_" . $document_item['documents_id']] = $doc_entry;
                } elseif (isset($timeline[$timeline_key])) {
                    // document associated to a sub item of itilobject
                    if (!isset($timeline[$timeline_key]['documents'])) {
                        $timeline[$timeline_key]['documents'] = [];
                    }

                    $timeline[$timeline_key]['documents'][] = $doc_entry;
                }
            }
        }

        // Add logs to timeline
        if ($params['with_logs'] && Session::getCurrentInterface() == "central") {
            //add logs to timeline
            $log_items = Log::getHistoryData($this, 0, 0, [
                'OR' => [
                    'id_search_option' => ['>', 0],
                    'itemtype_link'    => [User::class, Group::class, Supplier::class],
                ],
            ]);

            foreach ($log_items as $log_row) {
                // Safer to use a clean object to load our data
                $log = new Log();
                $log->fields = $log_row;
                $log->post_getFromDB();

                $content = $log_row['change'];
                if (strlen($log_row['field']) > 0) {
                    $content = sprintf(__s("%s: %s"), htmlescape($log_row['field']), $content);
                }
                $content = "<i class='ti ti-history me-1' title='" . __s("Log entry") . "' data-bs-toggle='tooltip'></i>" . $content;
                $user_id = 0;
                // try to extract ID from "user_name" (which was created using User::getNameForLog)
                if (preg_match('/ \((\d+)\)$/', $log_row["user_name"], $m)) {
                    $user_id = $m[1];
                }
                $timeline["Log_" . $log_row['id'] ] = [
                    'type'     => Log::class,
                    'class'    => 'text-muted d-none',
                    'item'     => [
                        'id'                 => $log_row['id'],
                        'content'            => $content,
                        'is_content_safe'    => true,
                        'date'               => $log_row['date_mod'],
                        'users_id'           => $user_id,
                        'can_edit'           => false,
                        'timeline_position'  => self::TIMELINE_LEFT,
                    ],
                    'object' => $log,
                ];
            }
        }

        // add autoreminders to timeline
        $autoreminder_obj = new ITILReminder();
        $autoreminders = $autoreminder_obj->find(['items_id'  => $this->getID()]);
        foreach ($autoreminders as $autoreminder_id => $autoreminder) {
            $autoreminder_obj = ITILReminder::getByID($autoreminder_id);
            if (!$autoreminder_obj instanceof ITILReminder) {
                continue;
            }
            $pending_reason = $autoreminder_obj->getPendingReason();
            $content = sprintf(
                '<span>%1$s%2$s (<span data-bs-toggle="popover" data-bs-html="true" data-bs-sanitize="true" data-bs-content="%3$s"><u>%4$s</u></span>)</span>',
                '<i class="ti ti-refresh-alert text-warning me-1"></i>',
                htmlescape(ITILReminder::getTypeName(1)),
                $autoreminder_obj->fields['content'] ?? '',
                htmlescape($autoreminder_obj->fields['name'])
            );

            $timeline["ITILReminder_" . $autoreminder_id] = [
                'type' => ITILReminder::class,
                'item' => [
                    'id' => $autoreminder_id,
                    'content' => $content,
                    'is_content_safe'    => true,
                    'date' => $autoreminder['date_creation'],
                    'users_id' => 0,
                    'can_edit' => false,
                    'timeline_position' => self::TIMELINE_LEFT,
                ],
                'object' => $autoreminder_obj,
            ];
        }

        Plugin::doHook(Hooks::SHOW_IN_TIMELINE, ['item' => $this, 'timeline' => &$timeline]); // @phpstan-ignore classConstant.deprecated
        Plugin::doHook(Hooks::TIMELINE_ITEMS, ['item' => $this, 'timeline' => &$timeline]);

        //sort timeline items by date. If items have the same date, sort by id
        $reverse = $params['sort_by_date_desc'];
        usort($timeline, function ($a, $b) use ($reverse) {
            $date_a = $a['item']['date_creation'] ?? $a['item']['date'];
            $date_b = $b['item']['date_creation'] ?? $b['item']['date'];
            $diff = strtotime($date_a) - strtotime($date_b);
            if ($diff === 0) {
                $diff = $a['item']['id'] - $b['item']['id'];
            }
            return $reverse ? 0 - $diff : $diff;
        });

        return $timeline;
    }


    /**
     * @since 9.4.0
     *
     * @param CommonDBTM $item The item whose form should be shown
     * @param integer $id ID of the item
     * @param mixed[] $params Array of extra parameters
     *
     * @return void
     */
    public static function showSubForm(CommonDBTM $item, $id, $params)
    {

        if ($item instanceof Document_Item) {
            Document_Item::showAddFormForItem($params['parent'], '');
        } elseif ($item->getType() == $params['parent']->getType()) {
            self::showEditDescriptionForm($params['parent']);
        } elseif ($item->can(-1, CREATE, $params)) {
            $item->showForm($id, $params);
        }
    }

    public static function showEditDescriptionForm(CommonITILObject $item)
    {
        $can_requester = $item->canRequesterUpdateItem();
        TemplateRenderer::getInstance()->display('components/itilobject/timeline/simple_form.html.twig', [
            'item'          => $item,
            'canupdate'     => (Session::getCurrentInterface() == "central" && $item->canUpdateItem()),
            'can_requester' => $can_requester,
        ]);
    }

    /**
     * Summary of getITILActors
     * Get the list of actors for the current Change
     * will return an assoc array of users_id => array of roles.
     *
     * @since 9.4.0
     *
     * @return array[] of array[] of users and roles
     */
    public function getITILActors()
    {
        global $DB;

        $users_table = $this->getTable() . '_users';
        switch ($this::class) {
            case Ticket::class:
                $groups_table = 'glpi_groups_tickets';
                break;
            case Problem::class:
                $groups_table = 'glpi_groups_problems';
                break;
            default:
                $groups_table = $this->getTable() . '_groups';
                break;
        }
        $fk = $this->getForeignKeyField();

        $subquery1 = new QuerySubQuery([
            'SELECT'    => [
                'usr.id AS users_id',
                'tu.type AS type',
            ],
            'FROM'      => "$users_table AS tu",
            'INNER JOIN' => [
                User::getTable() . ' AS usr' => [
                    'ON' => [
                        'tu'  => 'users_id',
                        'usr' => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                "tu.$fk" => $this->getID(),
            ],
        ]);

        $subquery2 = new QuerySubQuery([
            'SELECT'    => [
                'usr.id AS users_id',
                'gt.type AS type',
            ],
            'FROM'      => "$groups_table AS gt",
            'INNER JOIN' => [
                Group_User::getTable() . ' AS gu'   => [
                    'ON' => [
                        'gu'  => 'groups_id',
                        'gt'  => 'groups_id',
                    ],
                ],
                User::getTable() . ' AS usr'        => [
                    'ON' => [
                        'gu'  => 'users_id',
                        'usr' => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                "gt.$fk" => $this->getID(),
            ],
        ]);

        $union = new QueryUnion([$subquery1, $subquery2], false, 'allactors');
        $iterator = $DB->request([
            'SELECT'          => [
                'users_id',
                'type',
            ],
            'DISTINCT'        => true,
            'FROM'            => $union,
        ]);

        $users_keys = [];
        foreach ($iterator as $current_tu) {
            $users_keys[$current_tu['users_id']][] = $current_tu['type'];
        }

        return $users_keys;
    }


    /**
     * Number of followups of the object
     *
     * @param boolean $with_private true: all followups / false: only public ones (default 1)
     *
     * @return integer followup count
     **/
    public function numberOfFollowups($with_private = true)
    {
        global $DB;

        $RESTRICT = [];
        if ($with_private !== true) {
            $RESTRICT['is_private'] = 0;
        }

        // Set number of followups
        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_itilfollowups',
            'WHERE'  => [
                'itemtype'  => $this->getType(),
                'items_id'  => $this->fields['id'],
            ] + $RESTRICT,
        ])->current();

        return $result['cpt'];
    }

    /**
     * Number of tasks of the object
     *
     * @param boolean $with_private true: all followups / false: only public ones (default 1)
     *
     * @return integer
     **/
    public function numberOfTasks($with_private = true)
    {
        global $DB;

        $table = 'glpi_' . strtolower($this->getType()) . 'tasks';

        $RESTRICT = [];
        if ($with_private !== true && $this->getType() == 'Ticket') {
            //No private tasks for Problems and Changes
            $RESTRICT['is_private'] = 0;
        }

        // Set number of tasks
        $row = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => $table,
            'WHERE'  => [
                $this->getForeignKeyField()   => $this->fields['id'],
            ] + $RESTRICT,
        ])->current();
        return (int) $row['cpt'];
    }

    /**
     * Check if input contains a flag set to prevent 'takeintoaccount' delay computation.
     *
     * @param array $input
     *
     * @return boolean
     */
    public function isTakeIntoAccountComputationBlocked($input)
    {
        return array_key_exists('_do_not_compute_takeintoaccount', $input)
         && $input['_do_not_compute_takeintoaccount'];
    }


    /**
     * Check if input contains a flag set to prevent status computation.
     *
     * @param array $input
     *
     * @return boolean
     */
    public function isStatusComputationBlocked(array $input)
    {
        return array_key_exists('_do_not_compute_status', $input)
         && $input['_do_not_compute_status'];
    }


    public function addDefaultFormTab(array &$ong)
    {

        $timeline    = $this->getTimelineItems(['with_logs' => false]);
        $nb_elements = count($timeline);
        $label = static::getTypeName(1);

        $ong[static::getType() . '$main'] = static::createTabEntry($label, $nb_elements, static::getType());
        return $this;
    }

    public static function getAdditionalMenuOptions()
    {
        $tplclass = self::getTemplateClass();
        if ($tplclass::canView()) {
            $menu = [
                $tplclass => [
                    'title' => $tplclass::getTypeName(Session::getPluralNumber()),
                    'page'  => $tplclass::getSearchURL(false),
                    'icon'  => $tplclass::getIcon(),
                    'links' => [
                        'search' => $tplclass::getSearchURL(false),
                    ],
                ],
            ];

            if ($tplclass::canCreate()) {
                $menu[$tplclass]['links']['add'] = $tplclass::getFormURL(false);
            }
            return $menu;
        }
        return false;
    }

    public static function getAdditionalMenuLinks()
    {
        $links = [];
        $tplclass = self::getTemplateClass();
        if ($tplclass::canView()) {
            $links['template'] = $tplclass::getSearchURL(false);
        }
        $links['summary_kanban'] = static::getFormURL(false) . '?showglobalkanban=1';

        return $links;
    }


    /**
     * Get template to use
     * Use force_template first, then try on template define for type and category
     * then use default template of active profile of connected user and then use default entity one
     *
     * @param integer      $force_template     itiltemplate_id to use (case of preview for example)
     * @param integer|null $type               type of the ticket
     *                                         (use Ticket::INCIDENT_TYPE or Ticket::DEMAND_TYPE constants value)
     * @param integer      $itilcategories_id  ticket category
     * @param integer      $entities_id
     *
     * @return ITILTemplate
     *
     * @since 9.5.0
     **/
    public function getITILTemplateToUse(
        $force_template = 0,
        $type = null,
        $itilcategories_id = 0,
        $entities_id = -1
    ) {
        if (!$type && $this->getType() != Ticket::getType()) {
            $type = true;
        }
        // Load template if available :
        $tplclass = static::getTemplateClass();
        $tt              = getItemForItemtype($tplclass);
        $template_loaded = false;

        if ($force_template) {
            // with type and categ
            if ($tt->getFromDBWithData($force_template, true)) {
                $template_loaded = true;
            }
        }

        if (
            !$template_loaded
            && $type
            && $itilcategories_id
        ) {
            $categ = new ITILCategory();
            if ($categ->getFromDB($itilcategories_id)) {
                $field = $this->getTemplateFieldName($type);

                if ($categ->fields[$field]) {
                    // without type and categ
                    if ($tt->getFromDBWithData($categ->fields[$field], false)) {
                        $template_loaded = true;
                    }
                }
            }
        }

        // If template loaded from type and category do not check after
        if ($template_loaded) {
            return $tt;
        }

        //Get template from profile
        if ($type) {
            $field = $this->getTemplateFieldName($type);
            $field = str_replace(['_incident', '_demand'], ['', ''], $field);
            // load default profile one if not already loaded
            if (
                isset($_SESSION['glpiactiveprofile'][$field])
                && $_SESSION['glpiactiveprofile'][$field]
            ) {
                // with type and categ
                if (
                    $tt->getFromDBWithData(
                        $_SESSION['glpiactiveprofile'][$field],
                        true
                    )
                ) {
                    $template_loaded = true;
                }
            }
        }

        //Get template from entity
        if (
            !$template_loaded
            && ($entities_id >= 0)
        ) {
            // load default entity one if not already loaded
            $template_id = Entity::getUsedConfig(
                strtolower($this->getType()) . 'templates_strategy',
                $entities_id,
                strtolower($this->getType()) . 'templates_id',
                0
            );
            if ($template_id > 0 && $tt->getFromDBWithData($template_id, true)) {
                $template_loaded = true;
            }
        }

        // Check if profile / entity set type and category and try to load template for these values
        if ($template_loaded) { // template loaded for profile or entity
            $newtype              = $type;
            $newitilcategories_id = $itilcategories_id;
            // Get predefined values for ticket template
            if (isset($tt->predefined['itilcategories_id']) && $tt->predefined['itilcategories_id']) {
                $newitilcategories_id = $tt->predefined['itilcategories_id'];
            }
            if (isset($tt->predefined['type']) && $tt->predefined['type']) {
                $newtype = $tt->predefined['type'];
            }
            if (
                $newtype != $type
                && $newitilcategories_id != $itilcategories_id
            ) {
                $categ = new ITILCategory();
                if ($categ->getFromDB($newitilcategories_id)) {
                    $field = $this->getTemplateFieldName($newtype);

                    if (isset($categ->fields[$field]) && $categ->fields[$field]) {
                        // without type and categ
                        if ($tt->getFromDBWithData($categ->fields[$field], false)) {
                            $template_loaded = true;
                        }
                    }
                }
            }
        }
        return $tt;
    }

    /**
     * Get template field name
     *
     * @param int $type Type, if any
     *
     * @return string
     */
    public function getTemplateFieldName($type = null): string
    {
        $field = strtolower(static::getType()) . 'templates_id';
        if (static::getType() === Ticket::getType()) {
            switch ($type) {
                case Ticket::INCIDENT_TYPE:
                    $field .= '_incident';
                    break;

                case Ticket::DEMAND_TYPE:
                    $field .= '_demand';
                    break;

                case true:
                    //for changes and problem, or from profiles
                    break;

                default:
                    $field = '';
                    trigger_error('Missing type for Ticket template!', E_USER_WARNING);
                    break;
            }
        }

        return $field;
    }

    /**
     * @since 9.5.0
     *
     * @param integer $entity entities_id usefull if function called by cron (default 0)
     **/
    abstract public static function getDefaultValues($entity = 0);

    /**
     * Get template class name.
     *
     * @since 9.5.0
     *
     * @return class-string<ITILTemplate>
     */
    public static function getTemplateClass()
    {
        return static::getType() . 'Template';
    }

    /**
     * Get template form field name
     *
     * @since 9.5.0
     *
     * @return string
     */
    public static function getTemplateFormFieldName()
    {
        return '_' . strtolower(static::getType()) . 'template';
    }

    /**
     * Get common request criteria
     *
     * @since 9.5.0
     *
     * @return array
     */
    public static function getCommonCriteria()
    {
        $fk = self::getForeignKeyField();
        $gtable = str_replace('glpi_', 'glpi_groups_', static::getTable());
        $itable = str_replace('glpi_', 'glpi_items_', static::getTable());
        if (self::getType() == 'Change') {
            $gtable = 'glpi_changes_groups';
            $itable = 'glpi_changes_items';
        }
        $utable = static::getTable() . '_users';
        $stable = static::getTable() . '_suppliers';
        if (self::getType() == 'Ticket') {
            $stable = 'glpi_suppliers_tickets';
        }
        $table = static::getTable();
        $criteria = [
            'SELECT'          => [
                "$table.*",
                'glpi_itilcategories.completename AS catname',
            ],
            'DISTINCT'        => true,
            'FROM'            => $table,
            'LEFT JOIN'       => [
                $gtable  => [
                    'ON' => [
                        $table   => 'id',
                        $gtable  => $fk,
                    ],
                ],
                $utable  => [
                    'ON' => [
                        $table   => 'id',
                        $utable  => $fk,
                    ],
                ],
                $stable  => [
                    'ON' => [
                        $table   => 'id',
                        $stable  => $fk,
                    ],
                ],
                'glpi_itilcategories'      => [
                    'ON' => [
                        $table                  => 'itilcategories_id',
                        'glpi_itilcategories'   => 'id',
                    ],
                ],
                $itable  => [
                    'ON' => [
                        $table   => 'id',
                        $itable  => $fk,
                    ],
                ],
            ],
            'ORDERBY'            => "$table.date_mod DESC",
        ];
        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $criteria['LEFT JOIN']['glpi_entities'] = [
                'ON' => [
                    'glpi_entities'   => 'id',
                    $table            => 'entities_id',
                ],
            ];
            $criteria['SELECT'] = array_merge(
                $criteria['SELECT'],
                [
                    'glpi_entities.completename AS entityname',
                    "$table.entities_id AS entityID",
                ]
            );
        }
        return $criteria;
    }

    public function getForbiddenSingleMassiveActions()
    {
        $excluded = parent::getForbiddenSingleMassiveActions();

        if (isset($this->fields['global_validation']) && $this->fields['global_validation'] != CommonITILValidation::NONE) {
            //a validation has already been requested/done
            $excluded[] = 'TicketValidation:submit_validation';
        }

        $excluded[] = '*:add_actor';
        $excluded[] = '*:add_task';
        $excluded[] = '*:add_followup';
        $excluded[] = 'CommonITILObject_CommonITILObject:add';
        $excluded[] = 'CommonITILObject_CommonITILObject:delete';

        return $excluded;
    }

    /**
     * Returns criteria that can be used to get documents related to current instance.
     *
     * @return array
     */
    public function getAssociatedDocumentsCriteria($bypass_rights = false): array
    {
        $task_class = static::getTaskClass();

        global $DB; // Used to get subquery results - better performance

        $or_crits = [
            // documents associated to ITIL item directly
            [
                Document_Item::getTableField('itemtype') => $this->getType(),
                Document_Item::getTableField('items_id') => $this->getID(),
            ],
        ];

        // documents associated to followups
        if ($bypass_rights || ITILFollowup::canView()) {
            $fup_crits = [
                ITILFollowup::getTableField('itemtype') => $this->getType(),
                ITILFollowup::getTableField('items_id') => $this->getID(),
            ];
            if (!$bypass_rights && !Session::haveRight(ITILFollowup::$rightname, ITILFollowup::SEEPRIVATE)) {
                $fup_crits[] = [
                    'OR' => ['is_private' => 0, 'users_id' => Session::getLoginUserID()],
                ];
            }
            // Run the subquery separately. It's better for huge databases
            $iterator_tmp = $DB->request([
                'SELECT' => 'id',
                'FROM'   => ITILFollowup::getTable(),
                'WHERE'  => $fup_crits,
            ]);
            $arr_values = array_column(iterator_to_array($iterator_tmp, false), 'id');
            if (count($arr_values) > 0) {
                $or_crits[] = [
                    Document_Item::getTableField('itemtype') => ITILFollowup::getType(),
                    Document_Item::getTableField('items_id') => $arr_values,
                ];
            }
        }

        // documents associated to solutions
        if ($bypass_rights || ITILSolution::canView()) {
            // Run the subquery separately. It's better for huge databases
            $iterator_tmp = $DB->request([
                'SELECT' => 'id',
                'FROM'   => ITILSolution::getTable(),
                'WHERE'  => [
                    ITILSolution::getTableField('itemtype') => $this->getType(),
                    ITILSolution::getTableField('items_id') => $this->getID(),
                ],
            ]);
            $arr_values = array_column(iterator_to_array($iterator_tmp, false), 'id');
            if (count($arr_values) > 0) {
                $or_crits[] = [
                    Document_Item::getTableField('itemtype') => ITILSolution::getType(),
                    Document_Item::getTableField('items_id') => $arr_values,
                ];
            }
        }

        // documents associated to ticketvalidation
        $validation_class = static::getType() . 'Validation';
        if (class_exists($validation_class) && ($bypass_rights ||  $validation_class::canView())) {
            // Run the subquery separately. It's better for huge databases
            $iterator_tmp = $DB->request([
                'SELECT' => 'id',
                'FROM'   => $validation_class::getTable(),
                'WHERE'  => [
                    $validation_class::getTableField($validation_class::$items_id) => $this->getID(),
                ],
            ]);
            $arr_values = array_column(iterator_to_array($iterator_tmp, false), 'id');
            if (count($arr_values) > 0) {
                $or_crits[] = [
                    Document_Item::getTableField('itemtype') => $validation_class::getType(),
                    Document_Item::getTableField('items_id') => $arr_values,
                ];
            }
        }

        // documents associated to tasks
        if ($bypass_rights || $task_class::canView()) {
            $tasks_crit = [
                $this->getForeignKeyField() => $this->getID(),
            ];

            if (!$bypass_rights) {
                $private_task_crit = [];
                if (!Session::haveRight($task_class::$rightname, CommonITILTask::SEEPRIVATE)) {
                    $private_task_crit = [
                        'is_private' => 0,
                        'users_id' => Session::getLoginUserID(),
                        'users_id_tech' => Session::getLoginUserID(),
                    ];
                }
                if (Session::haveRight($task_class::$rightname, CommonITILTask::SEEPRIVATEGROUPS) && !empty($_SESSION["glpigroups"])) {
                    $private_task_crit['groups_id_tech'] = $_SESSION["glpigroups"];
                }
                if (!empty($private_task_crit)) {
                    $tasks_crit[] = ['OR' => $private_task_crit];
                }
            }

            // Run the subquery separately. It's better for huge databases
            $iterator_tmp = $DB->request([
                'SELECT' => 'id',
                'FROM'   => $task_class::getTable(),
                'WHERE'  => $tasks_crit,
            ]);
            $arr_values = array_column(iterator_to_array($iterator_tmp, false), 'id');
            if (count($arr_values) > 0) {
                $or_crits[] = [
                    'glpi_documents_items.itemtype' => $task_class::getType(),
                    'glpi_documents_items.items_id' => $arr_values,
                ];
            }
        }
        return ['OR' => $or_crits];
    }

    /**
     * Check if this item is new
     *
     * @return bool
     */
    protected function isNew()
    {
        if (isset($this->input['status'])) {
            $status = $this->input['status'];
        } elseif (isset($this->fields['status'])) {
            $status = $this->fields['status'];
        } else {
            throw new LogicException("Can't get status value: no object loaded");
        }

        return $status == CommonITILObject::INCOMING;
    }

    /**
     * Retrieve linked items table name
     *
     * @since 9.5.0
     *
     * @return string
     */
    public static function getItemsTable()
    {
        switch (static::getType()) {
            case 'Change':
                return 'glpi_changes_items';
            case 'Problem':
                return 'glpi_items_problems';
            case 'Ticket':
                return 'glpi_items_tickets';
            default:
                throw new RuntimeException('Unknown ITIL type ' . static::getType());
        }
    }


    public function getLinkedItems(): array
    {
        global $DB;

        $assets = $DB->request([
            'SELECT' => ["itemtype", "items_id"],
            'FROM'   => static::getItemsTable(),
            'WHERE'  => [$this->getForeignKeyField() => $this->getID()],
        ]);

        $assets = iterator_to_array($assets);

        $tab = [];
        foreach ($assets as $asset) {
            if (!class_exists($asset['itemtype'])) {
                //ignore if class does not exist (maybe a plugin)
                continue;
            }
            $tab[$asset['itemtype']][$asset['items_id']] = $asset['items_id'];
        }

        return $tab;
    }

    /**
     * Should impact tab be displayed? Check if there is a valid linked item
     *
     * @return boolean
     */
    protected function hasImpactTab()
    {
        foreach (array_keys($this->getLinkedItems()) as $itemtype) {
            $class = $itemtype;
            if (Impact::isEnabled($class) && Session::getCurrentInterface() === "central") {
                return true;
            }
        }
        return false;
    }

    /**
     * Get criteria needed to match objets with an "open" status (= not resolved
     * or closed)
     *
     * @return array
     */
    public static function getOpenCriteria(): array
    {
        $table = static::getTable();

        return [
            'NOT' => [
                "$table.status" => array_merge(
                    static::getSolvedStatusArray(),
                    static::getClosedStatusArray()
                ),
            ],
        ];
    }

    public function handleItemsIdInput(): void
    {
        if (!empty($this->input['items_id'])) {
            $item_link_class = static::getItemLinkClass();
            $item_link = getItemForItemtype($item_link_class);
            foreach ($this->input['items_id'] as $itemtype => $items) {
                foreach ($items as $items_id) {
                    $item_link->add([
                        'items_id'                    => $items_id,
                        'itemtype'                    => $itemtype,
                        static::getForeignKeyField()  => $this->fields['id'],
                        '_disablenotif'               => true,
                    ]);
                }
            }
        }
    }

    /**
     * @return class-string<CommonDBTM>
     */
    abstract public static function getItemLinkClass(): string;

    /**
     * Handle "_tasktemplates_id" special input
     */
    public function handleTaskTemplateInput()
    {
        // Check input is valid
        if (
            !isset($this->input['_tasktemplates_id'])
            || !is_array($this->input['_tasktemplates_id'])
            || !count($this->input['_tasktemplates_id'])
        ) {
            return;
        }

        // Add tasks in tasktemplates if defined in itiltemplate
        $itiltask   = static::getTaskClassInstance();
        foreach ($this->input['_tasktemplates_id'] as $tasktemplates_id) {
            $itiltask->add([
                '_tasktemplates_id'           => $tasktemplates_id,
                $this->getForeignKeyField()   => $this->fields['id'],
                'date'                        => $this->fields['date'],
                '_do_not_compute_status'      => $this->input['_do_not_compute_status'] ?? false,
            ]);
        }
    }

    /**
     * Handle "_itilfollowuptemplates_id" special input
     */
    public function handleITILFollowupTemplateInput(): void
    {
        // Check input is valid
        if (
            !isset($this->input['_itilfollowuptemplates_id'])
            || !is_array($this->input['_itilfollowuptemplates_id'])
            || !count($this->input['_itilfollowuptemplates_id'])
        ) {
            return;
        }

        // Add tasks in itilfollowup template if defined in itiltemplate
        foreach ($this->input['_itilfollowuptemplates_id'] as $fup_templates_id) {
            $values = [
                '_itilfollowuptemplates_id' => $fup_templates_id,
                'itemtype'                  => $this->getType(),
                'items_id'                  => $this->getID(),
                '_do_not_compute_status'    => $this->input['_do_not_compute_status'] ?? 0,
                '_do_not_compute_takeintoaccount' => $this->input['_do_not_compute_takeintoaccount'] ?? 0,
            ];
            // Insert new followup from template
            $fup = new ITILFollowup();
            $fup->add($values);
        }
    }

    /**
     * Handle "_solutiontemplates_id" special input
     */
    public function handleSolutionTemplateInput(): void
    {
        // Check input is valid
        if (!isset($this->input['_solutiontemplates_id'])) {
            return;
        }

        $solution = new ITILSolution();
        $input = [
            '_solutiontemplates_id' => $this->input['_solutiontemplates_id'],
            'itemtype'              => $this->getType(),
            'items_id'              => $this->getID(),
        ];
        if (isset($this->input['_do_not_compute_status'])) {
            $input['_do_not_compute_status'] = $this->input['_do_not_compute_status'];
        }
        $solution->add($input);
    }

    /**
     * Handle notifications to be sent after item creation.
     *
     * @return void
     */
    public function handleNewItemNotifications(): void
    {
        global $CFG_GLPI;

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
            $this->getFromDB($this->fields['id']); // Reload item to get actual status

            NotificationEvent::raiseEvent('new', $this);

            $status = $this->fields['status'] ?? null;

            //Check if a waiting ITIL solution has been posted to avoid sending duplicate notifications.
            $has_waiting_solution = countElementsInTable(
                ITILSolution::getTable(),
                [
                    'itemtype' => Ticket::class,
                    'items_id' => $this->getID(),
                    'status'   => CommonITILValidation::WAITING,
                ]
            ) > 0;

            if (in_array($status, static::getSolvedStatusArray()) && !$has_waiting_solution) {
                NotificationEvent::raiseEvent('solved', $this);
            }
            if (in_array($status, static::getClosedStatusArray())) {
                NotificationEvent::raiseEvent('closed', $this);
            }
        }
    }

    /**
     * Handle the input related to validation step threshold update.
     */
    private function handleValidationStepThresholdInput(array $input): void
    {
        // Action for `validationsteps_threshold` rule action
        if (!array_key_exists('_validationsteps_threshold', $input)) {
            return;
        }

        $relation_fields = [
            'itemtype' => static::class,
            'items_id' => $this->getID(),
            'validationsteps_id' => $input['_validationsteps_id'] ?? ValidationStep::getDefault()->getID(),
        ];

        $success = false;

        $itil_validationstep = static::getValidationStepInstance();
        if ($itil_validationstep->getFromDBByCrit($relation_fields)) {
            $success = $itil_validationstep->update([
                'id' => $itil_validationstep->getID(),
                'minimal_required_validation_percent' => $input['_validationsteps_threshold'],
            ]);
        } else {
            $success = $itil_validationstep->add($relation_fields + [
                'minimal_required_validation_percent' => $input['_validationsteps_threshold'],
            ]);
        }

        if ($success === false) {
            throw new RuntimeException('Unable to update the validation step threshold.');
        }
    }

    /**
     * Manage Validation add from input (form and rules)
     *
     * @param array $input
     *
     * @return boolean
     */
    public function manageValidationAdd($input)
    {
        $validation = static::getValidationClassInstance();

        if ($validation === null) {
            return true;
        }

        $self_fk = $this->getForeignKeyField();

        //Action for send_validation rule
        if (isset($input["_add_validation"])) {
            if (isset($input['entities_id'])) {
                $entid = $input['entities_id'];
            } elseif (isset($this->fields['entities_id'])) {
                $entid = $this->fields['entities_id'];
            } else {
                return false;
            }

            $validations_to_send = [];
            if (!is_array($input["_add_validation"])) {
                $input["_add_validation"] = [$input["_add_validation"]];
            }

            // user/groups assignements
            foreach ($input["_add_validation"] as $key => $value) {
                switch ($value) {
                    case 'requester_supervisor':
                        if (
                            isset($input['_groups_id_requester'])
                            && $input['_groups_id_requester']
                        ) {
                            $users = Group_User::getGroupUsers(
                                $input['_groups_id_requester'],
                                ['is_manager' => 1]
                            );
                            foreach ($users as $data) {
                                $validations_to_send[] = [
                                    'itemtype_target' => User::class,
                                    'items_id_target' => $data['id'],
                                ];
                            }
                        }
                        // Add to already set groups
                        foreach ($this->getGroups(CommonITILActor::REQUESTER) as $d) {
                            $users = Group_User::getGroupUsers(
                                $d['groups_id'],
                                ['is_manager' => 1]
                            );
                            foreach ($users as $data) {
                                $validations_to_send[] = [
                                    'itemtype_target' => User::class,
                                    'items_id_target' => $data['id'],
                                ];
                            }
                        }
                        break;

                    case 'assign_supervisor':
                        if (
                            isset($input['_groups_id_assign'])
                            && $input['_groups_id_assign']
                        ) {
                            $users = Group_User::getGroupUsers(
                                $input['_groups_id_assign'],
                                ['is_manager' => 1]
                            );
                            foreach ($users as $data) {
                                $validations_to_send[] = [
                                    'itemtype_target' => User::class,
                                    'items_id_target' => $data['id'],
                                ];
                            }
                        }
                        foreach ($this->getGroups(CommonITILActor::ASSIGN) as $d) {
                            $users = Group_User::getGroupUsers(
                                $d['groups_id'],
                                ['is_manager' => 1]
                            );
                            foreach ($users as $data) {
                                $validations_to_send[] = [
                                    'itemtype_target' => User::class,
                                    'items_id_target' => $data['id'],
                                ];
                            }
                        }
                        break;

                    case 'requester_responsible':
                        if (isset($input['_users_id_requester'])) {
                            $requesters = is_array($input['_users_id_requester'])
                                ? $input['_users_id_requester']
                                : [$input['_users_id_requester']];
                            foreach ($requesters as $users_id) {
                                $user = new User();
                                if ($user->getFromDB($users_id)) {
                                    $validations_to_send[] = [
                                        'itemtype_target' => User::class,
                                        'items_id_target' => $user->fields['users_id_supervisor'],
                                    ];
                                }
                            }
                        }
                        break;

                    default:
                        // Group case from rules
                        if ($key === 'group') {
                            foreach ($value as $groups_id) {
                                $validation_right = 'validate';
                                if ($this->getType() === Ticket::class) {
                                    $validation_right = isset($input['type']) && $input['type'] == Ticket::DEMAND_TYPE
                                        ? 'validate_request'
                                        : 'validate_incident';
                                }
                                $opt = [
                                    'groups_id' => $groups_id,
                                    'right'     => $validation_right,
                                    'entity'    => $entid,
                                ];

                                $data_users = $validation->getGroupUserHaveRights($opt);

                                foreach ($data_users as $user) {
                                    $validations_to_send[] = [
                                        'itemtype_target' => User::class,
                                        'items_id_target' => $user['id'],
                                    ];
                                }
                            }
                        } elseif ($key === 'group_any') {
                            foreach ($value as $groups_id) {
                                $validations_to_send[] = [
                                    'itemtype_target' => Group::class,
                                    'items_id_target' => $groups_id,
                                ];
                            }
                        } elseif ((int) $value > 0) {
                            $validations_to_send[] = [
                                'itemtype_target' => User::class,
                                'items_id_target' => $value,
                            ];
                        }
                }
            }

            // Validation user added on ticket form
            if (array_key_exists('_validation_targets', $input)) {
                foreach ($input['_validation_targets'] as $validation_target) {
                    if (
                        !array_key_exists('itemtype_target', $validation_target)
                        || !array_key_exists('items_id_target', $validation_target)
                    ) {
                        continue; // User may have not selected both fields
                    }
                    if (!is_array($validation_target['items_id_target'])) {
                        $validation_target['items_id_target'] = [$validation_target['items_id_target']];
                    }
                    foreach ($validation_target['items_id_target'] as $items_id_target) {
                        $validations_to_send[] = [
                            'itemtype_target'    => $validation_target['itemtype_target'],
                            'items_id_target'    => $items_id_target,
                            'validationsteps_id' => $validation_target['validationsteps_id'] ?? null,
                        ];
                    }
                }
            }

            // Keep only one
            $validations_to_send = array_unique($validations_to_send, SORT_REGULAR);

            if (count($validations_to_send)) {
                $values            = [];
                $values[$self_fk]  = $this->fields['id'];
                if (isset($input['id']) && $input['id'] != $this->fields['id']) {
                    $values['_itilobject_add'] = true;
                }

                // to know update by rules
                if (isset($input["_rule_process"])) {
                    $values['_rule_process'] = $input["_rule_process"];
                }
                // if auto_import, tranfert it for validation
                if (isset($input['_auto_import'])) {
                    $values['_auto_import'] = $input['_auto_import'];
                }

                // Cron or rule process of hability to do
                if (
                    Session::isCron()
                    || isset($input["_auto_import"])
                    || isset($input["_rule_process"])
                    || $validation->can(-1, CREATE, $values)
                ) { // cron or allowed user
                    $add_done = false;
                    foreach ($validations_to_send as $validation_to_send) {
                        // Do not auto add twice same validation
                        if (
                            $validation_to_send['itemtype_target'] === User::class
                            && $this->isUserValidationRequested($validation_to_send['items_id_target'], false)
                        ) {
                            continue;
                        }

                        $values['itemtype_target']     = $validation_to_send['itemtype_target'];
                        $values['items_id_target']     = $validation_to_send['items_id_target'];
                        $values['_validationsteps_id'] = $validation_to_send['validationsteps_id'] ?? null;

                        // add validation step
                        if (isset($input['_validationsteps_id']) && $values['_validationsteps_id'] === null) {
                            $values['_validationsteps_id'] = $input['_validationsteps_id'];
                        }

                        if ($validation->add($values)) {
                            $add_done = true;
                        }
                    }
                    if ($add_done) {
                        Event::log(
                            $this->fields['id'],
                            strtolower($this->getType()),
                            4,
                            "tracking",
                            sprintf(
                                __('%1$s updates the item %2$s'),
                                $_SESSION["glpiname"],
                                $this->fields['id']
                            )
                        );
                    }
                }
            }
        }
        return true;
    }

    /**
     * Manage actors posted by itil form
     * New way to do it with a general array containing all item actors.
     * We compare to old actors (in case of items's update) to know which we need to remove/add/update
     *
     * @param bool $disable_notifications
     *
     * @since 10.0.0
     *
     * @return void
     */
    protected function updateActors(bool $disable_notifications = false)
    {
        // Reload actors to be able to categorize users as added/updated/deleted.
        $this->loadActors();

        $common_actor_input = [
            '_do_not_compute_takeintoaccount' => $this->isTakeIntoAccountComputationBlocked($this->input),
            '_from_object'                    => true,
        ];
        if ($disable_notifications) {
            $common_actor_input['_disablenotif'] = true;
        }

        $actor_itemtypes = [
            User::class,
            Group::class,
            Supplier::class,
        ];
        $actor_types = [
            'requester',
            'assign',
            'observer',
        ];

        foreach ($actor_types as $actor_type) {
            $actor_type_value = constant(CommonITILActor::class . '::' . strtoupper($actor_type));

            // List actors from all input keys
            $actors = [];
            foreach ($actor_itemtypes as $actor_itemtype) {
                $actor_fkey = getForeignKeyFieldForItemType($actor_itemtype);

                $actors_id_input_key      = sprintf('_%s_%s', $actor_fkey, $actor_type);
                $actors_notif_input_key   = sprintf('%s_notif', $actors_id_input_key);
                $actors_id_add_input_key  = $actor_itemtype === User::class
                    ? sprintf('_additional_%ss', $actor_type)
                    : sprintf('_additional_%ss_%ss', strtolower($actor_itemtype), $actor_type);

                $get_unique_key = (fn(array $actor): string
                    // Use alternative_email in value key for "email" actors
                    => sprintf('%s_%s', $actors_id_input_key, $actor['items_id'] ?: $actor['alternative_email'] ?? ''));

                if (array_key_exists($actors_id_input_key, $this->input)) {
                    if (is_array($this->input[$actors_id_input_key])) {
                        foreach ($this->input[$actors_id_input_key] as $actor_key => $actor_id) {
                            if (!is_numeric($actor_id) && $actor_id !== 'requester_manager') {
                                trigger_error(
                                    sprintf(
                                        'Invalid value "%s" found for actor in "%s".',
                                        $actor_id,
                                        $actors_id_input_key
                                    ),
                                    E_USER_WARNING
                                );
                            }
                            $actor_id = (int) $actor_id;
                            $actor = [
                                'itemtype' => $actor_itemtype,
                                'items_id' => $actor_id,
                                'type'     => $actor_type_value,
                            ];
                            if ($actor_itemtype !== Group::class && array_key_exists($actors_notif_input_key, $this->input)) {
                                // Expected format
                                // '_users_id_requester_notif' => [
                                //     'use_notification'  => [1, 0],
                                //     'alternative_email' => ['user1@example.com', 'user2@example.com'],
                                // ]
                                $notification_params = $this->input[$actors_notif_input_key];
                                $unexpected_format = false;
                                if (
                                    !is_array($notification_params)
                                    || (
                                        !array_key_exists('use_notification', $notification_params)
                                        && !array_key_exists('alternative_email', $notification_params)
                                    )
                                ) {
                                    $unexpected_format = true;
                                    $notification_params = [];
                                }
                                foreach ($notification_params as $key => $values) {
                                    if (!is_array($values)) {
                                        $unexpected_format = true;
                                        continue;
                                    }
                                    if (array_key_exists($actor_key, $values)) {
                                        $actor[$key] = $values[$actor_key];
                                    }
                                }
                                if ($unexpected_format) {
                                    trigger_error(
                                        sprintf('Unexpected format found in "%s".', $actors_notif_input_key),
                                        E_USER_WARNING
                                    );
                                }
                            }
                            $actors[$get_unique_key($actor)] = $actor;
                        }
                    } elseif (is_numeric($this->input[$actors_id_input_key])) {
                        $actor_id = (int) $this->input[$actors_id_input_key];
                        $actor = [
                            'itemtype' => $actor_itemtype,
                            'items_id' => $actor_id,
                            'type'     => $actor_type_value,
                        ];
                        if (array_key_exists($actors_notif_input_key, $this->input)) {
                            // Expected formats
                            //
                            // Value provided by Change::getDefaultValues()
                            // '_users_id_requester_notif' => [
                            //     'use_notification'  => 1,
                            //     'alternative_email' => 'user1@example.com',
                            // ]
                            //
                            // OR
                            //
                            // Value provided by Ticket::getDefaultValues()
                            // '_users_id_requester_notif' => [
                            //     'use_notification'  => [1, 0],
                            //     'alternative_email' => ['user1@example.com', 'user2@example.com'],
                            // ]
                            $notification_params = $this->input[$actors_notif_input_key];
                            if (
                                !is_array($notification_params)
                                || (
                                    !array_key_exists('use_notification', $notification_params)
                                    && !array_key_exists('alternative_email', $notification_params)
                                )
                            ) {
                                trigger_error(
                                    sprintf('Unexpected format found in "%s".', $actors_notif_input_key),
                                    E_USER_WARNING
                                );
                                $notification_params = [];
                            }
                            foreach ($notification_params as $key => $values) {
                                if (is_array($values) && array_key_exists(0, $values)) {
                                    $actor[$key] = $values[0];
                                } elseif (!is_array($values)) {
                                    $actor[$key] = $values;
                                }
                            }
                        }
                        $actors[$get_unique_key($actor)] = $actor;
                    } elseif ($this->input[$actors_id_input_key] !== '') {
                        trigger_error(
                            sprintf(
                                'Invalid value "%s" found for actor in "%s".',
                                $this->input[$actors_id_input_key],
                                $actors_id_input_key
                            ),
                            E_USER_WARNING
                        );
                    }
                }
                if (array_key_exists($actors_id_add_input_key, $this->input)) {
                    foreach ($this->input[$actors_id_add_input_key] as $actor) {
                        $actor_id = null;
                        if (is_array($actor) && array_key_exists($actor_fkey, $actor)) {
                            $actor_id = $actor[$actor_fkey];
                        } else {
                            $actor_id = $actor;
                        }
                        if (!is_numeric($actor_id)) {
                            trigger_error(
                                sprintf(
                                    'Invalid value "%s" found for additional actor in "%s".',
                                    var_export($actor_id, true),
                                    $actors_id_add_input_key
                                ),
                                E_USER_WARNING
                            );
                            continue;
                        }
                        $actor_id = (int) $actor_id;
                        $actor = [
                            'itemtype' => $actor_itemtype,
                            'items_id' => $actor_id,
                            'type'     => $actor_type_value,
                        ];
                        $unique_key = $get_unique_key($actor);
                        if (!array_key_exists($unique_key, $actors)) {
                            $actors[$unique_key] = $actor;
                        }
                    }
                }
            }

            // Search for added/updated actors
            $existings = $this->getActorsForType($actor_type_value);
            $added     = [];
            $updated   = [];

            foreach ($actors as $actor) {
                if (
                    $actor['items_id'] === 0
                    && (
                        ($actor['itemtype'] === User::class && empty($actor['alternative_email'] ?? ''))
                        || $actor['itemtype'] !== User::class
                    )
                ) {
                    // Empty values, probably provided by static::getDefaultValues()
                    continue;
                }

                $found = false;
                foreach ($existings as $existing) {
                    if (
                        $actor['itemtype'] === User::class
                        && $actor['items_id'] == 0
                        && $actor['itemtype'] == $existing['itemtype']
                    ) {
                        // "email" actor found
                        if ($actor['alternative_email'] == $existing['alternative_email']) {
                            // The anonymous actor matches an existing one, update it
                            $updated[] = $actor + ['id' => $existing['id']];
                            $found = true;
                            break;
                        } else {
                            // Do not check for modifications on "email" actors (they should be deleted then re-added on email change)
                            continue;
                        }
                    }

                    if ($actor['itemtype'] != $existing['itemtype'] || $actor['items_id'] != $existing['items_id']) {
                        continue;
                    }
                    $found = true;

                    if ($actor['itemtype'] === Group::class) {
                        // Do not check for modifications on "group" actors (they do not have notification settings to update)
                        continue;
                    }

                    // check if modifications exists
                    if (
                        (
                            array_key_exists('use_notification', $actor)
                            && $actor['use_notification'] != $existing['use_notification']
                        )
                        || (
                            array_key_exists('alternative_email', $actor)
                            && $actor['alternative_email'] != $existing['alternative_email']
                        )
                    ) {
                        $updated[] = $actor + ['id' => $existing['id']];
                    }

                    break; // As actor is found, do not continue to list existings
                }

                if ($found === false) {
                    $added[] = $actor;
                }
            }

            // Add new actors
            foreach ($added as $actor) {
                $actor_obj = $this->getActorObjectForItem($actor['itemtype']);
                $actor_obj->add($common_actor_input + $actor + [
                    $actor_obj->getItilObjectForeignKey() => $this->fields['id'],
                    $actor_obj->getActorForeignKey()      => $actor['items_id'],
                ]);
                if (
                    $actor['type'] === CommonITILActor::ASSIGN
                    && (
                        (!isset($this->input['status']) && in_array($this->fields['status'], static::getNewStatusArray()))
                        || (isset($this->input['status']) && in_array($this->input['status'], static::getNewStatusArray()))
                    )
                    && in_array(self::ASSIGNED, array_keys(static::getAllStatusArray()))
                    && !$this->isStatusComputationBlocked($this->input)
                ) {
                    $self = new static();
                    $self->update(
                        [
                            'id'                              => $this->getID(),
                            'status'                          => self::ASSIGNED,
                            '_do_not_compute_takeintoaccount' => $this->isTakeIntoAccountComputationBlocked($this->input),
                            '_from_assignment'                => true,
                        ]
                    );
                    $this->fields['status'] = $self->fields['status'];
                }
            }
            // Update existing actors
            foreach ($updated as $actor) {
                $actor_obj = $this->getActorObjectForItem($actor['itemtype']);
                $actor_obj->update($common_actor_input + $actor);
            }
        }

        // Process deleted actors
        foreach ($actor_types as $actor_type) {
            foreach ($actor_itemtypes as $actor_itemtype) {
                $actor_fkey = getForeignKeyFieldForItemType($actor_itemtype);
                $actors_deleted_input_key = sprintf('_%s_%s_deleted', $actor_fkey, $actor_type);

                $deleted = array_key_exists($actors_deleted_input_key, $this->input)
                    ? $this->input[$actors_deleted_input_key]
                    : [];
                foreach ($deleted as $actor) {
                    $actor_obj = $this->getActorObjectForItem($actor['itemtype']);
                    $actor_obj->delete(['id' => $actor['id']]);
                }
            }
        }

        // We just updated actors, clear any cached data
        $this->clearLazyLoadedActors();
    }


    final public function getActorObjectForItem(string $itemtype): CommonITILActor
    {
        $actor_class = match ($itemtype) {
            User::class => $this->userlinkclass,
            Group::class => $this->grouplinkclass,
            Supplier::class => $this->supplierlinkclass,
            default => throw new RuntimeException('Unexpected actor type.'),
        };

        $actor = getItemForItemtype($actor_class);
        if (!($actor instanceof CommonITILActor)) {
            throw new RuntimeException(
                'The actor class for item type ' . $itemtype . ' must extend CommonITILActor.'
            );
        }
        return $actor;
    }


    /**
     * Fill the tech and the group from the category
     * @param array $input
     * @return array
     */
    protected function setTechAndGroupFromItilCategory($input)
    {
        $cat = new ITILCategory();
        $has_user_assigned  = $this->hasValidActorInInput($input, User::class, CommonITILActor::ASSIGN);
        $has_group_assigned = $this->hasValidActorInInput($input, Group::class, CommonITILActor::ASSIGN);
        if (
            $input['itilcategories_id'] > 0
            && (!$has_user_assigned || !$has_group_assigned)
            && $cat->getFromDB($input['itilcategories_id'])
        ) {
            if (!$has_user_assigned && $cat->fields['users_id'] > 0) {
                $input['_users_id_assign'] = $cat->fields['users_id'];
            }
            if (!$has_group_assigned && $cat->fields['groups_id'] > 0) {
                $input['_groups_id_assign'] = $cat->fields['groups_id'];
            }
        }

        return $input;
    }


    /**
     * Fill the tech and the group from the hardware
     * @param array $input
     * @return array
     */
    protected function setTechAndGroupFromHardware($input, $item)
    {
        global $CFG_GLPI;

        if ($item != null) {
            // Auto assign tech from item
            $has_user_assigned  = $this->hasValidActorInInput($input, User::class, CommonITILActor::ASSIGN);
            if (
                !$has_user_assigned
                && in_array($item::class, $CFG_GLPI['assignable_types'], true)
                && $item->fields['users_id_tech'] > 0
            ) {
                $input['_users_id_assign'] = $item->fields['users_id_tech'];
            }

            // Auto assign group from item
            $has_group_assigned = $this->hasValidActorInInput($input, Group::class, CommonITILActor::ASSIGN);
            if (
                !$has_group_assigned
                && in_array($item::class, $CFG_GLPI['assignable_types'], true)
                && $item->fields['groups_id_tech'] > 0
            ) {
                $input['_groups_id_assign'] = $item->fields['groups_id_tech'];
            }
        }

        return $input;
    }

    /**
     * Replay setting auto assign if set in rules engine or by auto_assign_mode
     * Do not force status if status has been set by rules
     *
     * @param array $input
     *
     * @return array
     */
    protected function assign(array $input)
    {
        // FIXME Deprecate this method in GLPI 11.0.
        if (!in_array(self::ASSIGNED, array_keys(static::getAllStatusArray()))) {
            return $input;
        }

        if (
            (
                $this->hasValidActorInInput($input, User::class, CommonITILActor::ASSIGN)
                || $this->hasValidActorInInput($input, Group::class, CommonITILActor::ASSIGN)
                || $this->hasValidActorInInput($input, Supplier::class, CommonITILActor::ASSIGN)
            )
            && (in_array($input['status'], static::getNewStatusArray()))
            && !$this->isStatusComputationBlocked($input)
        ) {
            $input["status"] = self::ASSIGNED;
        }

        return $input;
    }

    /**
     * Check if input contains a valid actor for given itemtype / actortype.
     */
    private function hasValidActorInInput(array $input, string $itemtype, int $actortype): bool
    {
        $input_id_key = sprintf(
            '_%s_%s',
            getForeignKeyFieldForItemType($itemtype),
            self::getActorFieldNameType($actortype)
        );
        $input_notif_key = sprintf(
            '%s_notif',
            $input_id_key
        );

        $has_valid_actor = false;
        if (array_key_exists($input_id_key, $input)) {
            if (is_array($input[$input_id_key]) && !empty($input[$input_id_key])) {
                foreach ($input[$input_id_key] as $key => $actor_id) {
                    if (
                        // actor with valid ID
                        (int) $actor_id > 0
                        // or "email" actor
                        || (
                            $itemtype === User::class
                            && (int) $actor_id === 0
                            && array_key_exists($input_notif_key, $input)
                            && (bool) ($input[$input_notif_key]['use_notification'][$key] ?? false) === true
                            && !empty($input[$input_notif_key]['alternative_email'][$key])
                        )
                    ) {
                        $has_valid_actor = true;
                        break;
                    }
                }
            } elseif (is_numeric($input[$input_id_key])) {
                $actor_id = (int) $input[$input_id_key];
                if (
                    // actor with valid ID
                    $actor_id > 0
                    // or "email" actor
                    || (
                        $itemtype === User::class
                        && $actor_id === 0
                        // Expected formats
                        //
                        // Value provided by Change::getDefaultValues()
                        // '_users_id_requester_notif' => [
                        //     'use_notification'  => 1,
                        //     'alternative_email' => 'user1@example.com',
                        // ]
                        //
                        // OR
                        //
                        // Value provided by Ticket::getDefaultValues()
                        // '_users_id_requester_notif' => [
                        //     'use_notification'  => [1, 0],
                        //     'alternative_email' => ['user1@example.com', 'user2@example.com'],
                        // ]
                        && array_key_exists($input_notif_key, $input)
                        && (
                            array_key_exists('use_notification', $input[$input_notif_key])
                            && (
                                (
                                    is_array($input[$input_notif_key]['use_notification'])
                                    && (bool) ($input[$input_notif_key]['use_notification'][0] ?? false) === true
                                )
                                || (bool) ($input[$input_notif_key]['use_notification'] ?? false) === true
                            )
                        )
                        && (
                            array_key_exists('alternative_email', $input[$input_notif_key])
                            && (
                                (
                                    is_array($input[$input_notif_key]['alternative_email'])
                                    && !empty($input[$input_notif_key]['alternative_email'][0])
                                )
                                || !empty($input[$input_notif_key]['alternative_email'])
                            )
                        )
                    )
                ) {
                    $has_valid_actor = true;
                }
            }
        }

        return $has_valid_actor;
    }

    /**
     * Parameter class instance to be used for this item (user templates)
     */
    abstract public static function getContentTemplatesParametersClassInstance(): CommonITILObjectParameters;

    public static function getDataToDisplayOnKanban($ID, $criteria = [])
    {
        global $DB;

        // List of items to return
        $items = [];

        // Common variables
        $self_item = new static();
        $can_update = static::canUpdate();
        $itemtype = static::class;
        $self_fk_field = static::getForeignKeyField();
        $linked_actors = [];

        // Build base query
        $WHERE = ['is_deleted' => 0];
        $WHERE += $criteria;
        $WHERE += getEntitiesRestrictCriteria();
        // visibility check hack so we don't have to load the complete DB info for every item
        $visiblity_criteria = Search::addDefaultWhere(static::class);
        if (!empty($visiblity_criteria)) {
            $WHERE[] = new QueryExpression(Search::addDefaultWhere(static::class));
        }
        $base_common_itil_query = [
            'SELECT' => [static::getTableField('id')],
            'FROM'   => static::getTable(),
            'WHERE'  => $WHERE,
        ];

        // Add JOIN
        $linked_tables = [];
        $default_joint = Search::addDefaultJoin(
            $itemtype,
            getTableForItemType($itemtype),
            $linked_tables, // Passed by reference, must be a defined variable even if empty
        );
        if (!empty($default_joint)) {
            $base_common_itil_query['LEFT JOIN'] = [new QueryExpression($default_joint)];
        }

        // Load common_itil
        $common_itil_query = $base_common_itil_query;
        $common_itil_query['SELECT'][] = static::getTableField('name');
        $common_itil_query['SELECT'][] = static::getTableField('status');
        $common_itil_query['SELECT'][] = static::getTableField('itilcategories_id');
        $common_itil_query['SELECT'][] = static::getTableField('content');
        $common_itil_iterator = $DB->request($common_itil_query);

        // Load actors (users)
        $user_link_class = $self_item->userlinkclass;
        if (is_a($user_link_class, CommonITILActor::class, true)) {
            $user_link_table = getTableForItemType($user_link_class);
            $linked_user_iterator = $DB->request([
                'SELECT' => [
                    $user_link_class::getTableField($self_fk_field),
                    $user_link_class::getTableField('users_id'),
                    User::getTableField('firstname'),
                    User::getTableField('realname'),
                    User::getTableField('name'),
                ],
                'FROM'   => $user_link_table,
                'INNER JOIN' => [
                    User::getTable() => [
                        'ON'  => [
                            $user_link_table => 'users_id',
                            User::getTable() => 'id',
                        ],
                    ],
                ],
                'WHERE'  => [
                    'type' => CommonITILActor::ASSIGN,
                    $self_fk_field => new QuerySubQuery($base_common_itil_query),
                ],
            ]);
            foreach ($linked_user_iterator as $linked_user_row) {
                $common_itil_id = $linked_user_row[$self_fk_field];

                // Init array
                if (!isset($linked_actors[$common_itil_id])) {
                    $linked_actors[$common_itil_id] = [];
                }

                // Push users
                $linked_actors[$common_itil_id][] = [
                    'itemtype'  => User::getType(),
                    'id'        => $linked_user_row['users_id'],
                    'firstname' => $linked_user_row['firstname'],
                    'realname'  => $linked_user_row['realname'],
                    'name'      => formatUserName(
                        $linked_user_row['users_id'],
                        $linked_user_row['name'],
                        $linked_user_row['realname'],
                        $linked_user_row['firstname']
                    ),
                ];
            }
        }

        // Load actors (groups)
        $group_link_class = $self_item->grouplinkclass;
        if (is_a($group_link_class, CommonITILActor::class, true)) {
            $group_link_table = getTableForItemType($group_link_class);
            $linked_group_iterator = $DB->request([
                'SELECT' => [
                    $group_link_class::getTableField($self_fk_field),
                    $group_link_class::getTableField('groups_id'),
                    Group::getTableField('name'),
                ],
                'FROM'   => $group_link_table,
                'INNER JOIN' => [
                    Group::getTable() => [
                        'ON'  => [
                            $group_link_table => 'groups_id',
                            Group::getTable() => 'id',
                        ],
                    ],
                ],
                'WHERE'  => [
                    'type' => CommonITILActor::ASSIGN,
                    $self_fk_field => new QuerySubQuery($base_common_itil_query),
                ],
            ]);
            foreach ($linked_group_iterator as $linked_group_row) {
                $common_itil_id = $linked_group_row[$self_fk_field];

                // Init array
                if (!isset($linked_actors[$common_itil_id])) {
                    $linked_actors[$common_itil_id] = [];
                }

                // Push groups
                $linked_actors[$common_itil_id][] = [
                    'itemtype' => Group::getType(),
                    'id'       => $linked_group_row['groups_id'],
                    'name'     => $linked_group_row['name'],
                ];
            }
        }

        // Load actors (supplier)
        $supplier_link_class = $self_item->supplierlinkclass;
        if (is_a($supplier_link_class, CommonITILActor::class, true)) {
            $suplier_link_table = getTableForItemType($supplier_link_class);
            $linked_supplier_iterator = $DB->request([
                'SELECT' => [
                    $supplier_link_class::getTableField($self_fk_field),
                    $supplier_link_class::getTableField('suppliers_id'),
                    Supplier::getTableField('name'),
                ],
                'FROM'   => $suplier_link_table,
                'INNER JOIN' => [
                    Supplier::getTable() => [
                        'ON'  => [
                            $suplier_link_table => 'suppliers_id',
                            Supplier::getTable() => 'id',
                        ],
                    ],
                ],
                'WHERE'  => [
                    'type' => CommonITILActor::ASSIGN,
                    $self_fk_field => new QuerySubQuery($base_common_itil_query),
                ],
            ]);
            foreach ($linked_supplier_iterator as $linked_supplier_row) {
                $common_itil_id = $linked_supplier_row[$self_fk_field];

                // Init array
                if (!isset($linked_actors[$common_itil_id])) {
                    $linked_actors[$common_itil_id] = [];
                }

                // Push groups
                $linked_actors[$common_itil_id][] = [
                    'itemtype' => Supplier::getType(),
                    'id'       => $linked_supplier_row['suppliers_id'],
                    'name'     => $linked_supplier_row['name'],
                ];
            }
        }

        // Load linked tickets (only for tickets)
        if (static::class === Ticket::class) {
            $linked_tickets = [];
            $linked_tickets_iterator = $DB->request([
                'FROM' => new QueryUnion([
                    // Get parents tickets
                    [
                        'SELECT' => [
                            Ticket_Ticket::getTableField('tickets_id_1 AS tickets_id_parent'),
                            Ticket_Ticket::getTableField('tickets_id_2 AS tickets_id_child'),
                            Ticket::getTableField('status'),
                        ],
                        'FROM' => Ticket_Ticket::getTable(),
                        'LEFT JOIN' => [
                            Ticket::getTable() => [
                                'ON'  => [
                                    Ticket_Ticket::getTable() => 'tickets_id_2',
                                    Ticket::getTable() => 'id',
                                ],
                            ],
                        ],
                        'WHERE'  => [
                            'link' => Ticket_Ticket::PARENT_OF,
                            'tickets_id_1' => new QuerySubQuery($base_common_itil_query),
                        ],
                    ],
                    // Get children tickets
                    [
                        'SELECT' => [
                            Ticket_Ticket::getTableField('tickets_id_1 AS tickets_id_child'),
                            Ticket_Ticket::getTableField('tickets_id_2 AS tickets_id_parent'),
                            Ticket::getTableField('status'),
                        ],
                        'FROM' => Ticket_Ticket::getTable(),
                        'LEFT JOIN' => [
                            Ticket::getTable() => [
                                'ON'  => [
                                    Ticket_Ticket::getTable() => 'tickets_id_1',
                                    Ticket::getTable() => 'id',
                                ],
                            ],
                        ],
                        'WHERE'  => [
                            'link' => Ticket_Ticket::SON_OF,
                            'tickets_id_2' => new QuerySubQuery($base_common_itil_query),
                        ],
                    ],
                ]),
            ]);

            foreach ($linked_tickets_iterator as $linked_ticket_row) {
                $tickets_id_parent = $linked_ticket_row['tickets_id_parent'];

                // Init array
                if (!isset($linked_tickets[$tickets_id_parent])) {
                    $linked_tickets[$tickets_id_parent] = [];
                }

                // Push links
                $linked_tickets[$tickets_id_parent][] = [
                    'tickets_id' => $linked_ticket_row['tickets_id_child'],
                    'status'     => $linked_ticket_row['status'],
                ];
            }
        }

        foreach ($common_itil_iterator as $data) {
            $data = [
                'id'        => $data['id'],
                'name'      => $data['name'],
                'category'  => $data['itilcategories_id'],
                'content'   => $data['content'],
                'status'    => $data['status'],
                '_itemtype' => $itemtype,
                '_team'     => $linked_actors[$data['id']] ?? [],
                // Only use global update right here because checking item right
                // is too expensive (need to load full item just to check right)
                '_readonly' => !$can_update,
            ];

            if (static::class === Ticket::class) {
                $data['_steps'] = $linked_tickets[$data['id']] ?? [];
            }

            $items[$data['id']] = $data;
        }

        return $items;
    }

    public static function getKanbanColumns($ID, $column_field = null, $column_ids = [], $get_default = false)
    {
        // TODO Make this function only return the card data and leave rendering to Vue components. This will deduplicate the data between display and filters.
        if (!in_array($column_field, ['status'])) {
            return [];
        }

        $columns = [];
        $criteria = [];
        if (empty($column_ids)) {
            return [];
        }
        // Fill columns with empty arrays for each column id to avoid missing columns in the kanban
        foreach ($column_ids as $column_id) {
            $columns[$column_id] = [];
        }
        // Never try getting cards in drop-only columns
        $columns_defined = self::getAllKanbanColumns('status');
        $statuses_from_db = array_filter($column_ids, static function ($id) use ($columns_defined) {
            $id = (int) $id;
            return isset($columns_defined[$id]) && (!isset($columns_defined[$id]['drop_only']) || $columns_defined[$id]['drop_only'] === false);
        });
        if (count($statuses_from_db)) {
            $criteria = [
                static::getTableField('status') => $statuses_from_db,
            ];
        }

        // Avoid fetching everything when nothing is needed
        if (isset($criteria[static::getTableField('status')])) {
            $items = self::getDataToDisplayOnKanban($ID, $criteria);
        } else {
            $items = [];
        }


        $extracolumns = self::getAllKanbanColumns($column_field, $column_ids, $get_default);
        foreach ($extracolumns as $column_id => $column) {
            $columns[$column_id] = $column;
        }

        foreach ($items as $item) {
            if (!array_key_exists($item[$column_field], $columns)) {
                continue;
            }
            $itemtype = $item['_itemtype'];
            $card = [
                'id'              => "{$itemtype}-{$item['id']}",
                'title'           => $item['name'],
                'is_deleted'      => $item['is_deleted'] ?? false,
            ];

            $content = "<div class='kanban-plugin-content'>";
            $plugin_content_pre = Plugin::doHookFunction(Hooks::PRE_KANBAN_CONTENT, [
                'itemtype' => $itemtype,
                'items_id' => $item['id'],
            ]);
            if (!empty($plugin_content_pre['content'])) {
                $content .= $plugin_content_pre['content'];
            }
            $content .= "</div>";
            // Core content
            $content .= "<div class='kanban-core-content'>";
            if (isset($item['_steps']) && count($item['_steps'])) {
                $done = count(array_filter($item['_steps'], static fn($l) => in_array($l['status'], static::getClosedStatusArray())));
                $total = count($item['_steps']);
                $content .= "<div class='flex-break'></div>";
                $content .= sprintf(__s('%s / %s tasks complete'), $done, $total);
            }
            $content .= "<div class='flex-break'></div>";

            $content .= "</div>";
            $content .= "<div class='kanban-plugin-content'>";
            $plugin_content_post = Plugin::doHookFunction(Hooks::POST_KANBAN_CONTENT, [
                'itemtype' => $itemtype,
                'items_id' => $item['id'],
            ]);
            if (!empty($plugin_content_post['content'])) {
                $content .= $plugin_content_post['content'];
            }
            $content .= "</div>";

            $card['content'] = $content;
            $card['_team'] = $item['_team'];
            $card['_readonly'] = $item['_readonly'];
            $card['_form_link'] = $itemtype::getFormUrlWithID($item['id']);
            $card['_metadata'] = [];
            $metadata_values = ['name', 'content'];
            foreach ($metadata_values as $metadata_value) {
                if (isset($item[$metadata_value])) {
                    $card['_metadata'][$metadata_value] = $item[$metadata_value];
                }
            }
            if (isset($card['_metadata']['content']) && is_string($card['_metadata']['content'])) {
                $card['_metadata']['content'] = RichText::getTextFromHtml(content: $card['_metadata']['content'], preserve_line_breaks: true);
            } else {
                $card['_metadata']['content'] = '';
            }
            $card['_metadata']['category'] = $item['category'];
            $card['_metadata'] = Plugin::doHookFunction(Hooks::KANBAN_ITEM_METADATA, [
                'itemtype' => $itemtype,
                'items_id' => $item['id'],
                'metadata' => $card['_metadata'],
            ])['metadata'];
            $columns[$item[$column_field]]['items'][] = $card;
        }

        $category_ids = [];
        foreach ($columns as $column_id => $column) {
            if ($column_id !== 0 && !in_array($column_id, $column_ids)) {
                unset($columns[$column_id]);
            } elseif (isset($column['items'])) {
                foreach ($column['items'] as $item) {
                    if (isset($item['_metadata']['category'])) {
                        $category_ids[] = $item['_metadata']['category'];
                    }
                }
            }
        }
        $category_ids = array_filter(array_unique($category_ids), static fn($id) => $id > 0);

        $categories = [];
        if ($category_ids !== []) {
            global $DB;

            $cat_table = ITILCategory::getTable();
            $trans_table = DropdownTranslation::getTable();
            $name_select = new QueryExpression('IFNULL(' . $DB::quoteName("$trans_table.value") . ',' . $DB::quoteName("$cat_table.name") . ') AS ' . $DB::quoteName('name'));
            $it = $DB->request([
                'SELECT' => ["$cat_table.id", $name_select],
                'FROM' => $cat_table,
                'LEFT JOIN' => [
                    $trans_table => [
                        'ON' => [
                            $trans_table => 'items_id',
                            $cat_table => 'id',
                            [
                                'AND' => [
                                    $trans_table . '.itemtype' => ITILCategory::getType(),
                                    $trans_table . '.field' => 'name',
                                    $trans_table . '.language' => $_SESSION['glpilanguage'],
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE' => ["$cat_table.id" => $category_ids],
            ]);
            foreach ($it as $row) {
                $categories[$row['id']] = $row['name'];
            }
            // Add uncategorized category
            $categories[0] = '';
        }

        // Replace category ids with category names in items metadata
        foreach ($columns as &$column) {
            if (!isset($column['items'])) {
                continue;
            }
            foreach ($column['items'] as &$item) {
                $item['_metadata']['category'] = $categories[$item['_metadata']['category']] ?? '';
            }
        }

        return $columns;
    }

    public static function showKanban($ID)
    {
        $itilitem = new static();

        if (($ID > 0 && !$itilitem->getFromDB($ID)) || !$itilitem::canView()) {
            return false;
        }

        $team_role_ids = static::getTeamRoles();
        $team_roles = [];

        foreach ($team_role_ids as $role_id) {
            $team_roles[$role_id] = static::getTeamRoleName($role_id);
        }

        $supported_itemtypes = [];
        $supported_itemtypes[static::class] = [
            'name' => static::getTypeName(1),
            'icon' => static::getIcon(),
            'fields' => [
                'name'   => [
                    'placeholder'  => __('Name'),
                ],
                'content'   => [
                    'placeholder'  => __('Content'),
                    'type'         => 'textarea',
                ],
                'users_id'  => [
                    'type'         => 'hidden',
                    'value'        => $_SESSION['glpiID'],
                ],
            ],
            'team_itemtypes'  => static::getTeamItemtypes(),
            'team_roles'      => $team_roles,
            'allow_create'    => static::canCreate(),
        ];
        $column_field = [
            'id' => 'status',
            'extra_fields' => [],
        ];

        $itemtype = static::class;
        $rights = [
            'create_item'                    => self::canCreate(),
            'delete_item'                    => self::canDelete(),
            'create_column'                  => false,
            'modify_view'                    => true,
            'order_card'                     => true,
            'create_card_limited_columns'    => [],
        ];

        TemplateRenderer::getInstance()->display('components/kanban/kanban.html.twig', [
            'kanban_id'                   => 'kanban',
            'rights'                      => $rights,
            'supported_itemtypes'         => $supported_itemtypes,
            'max_team_images'             => 3,
            'column_field'                => $column_field,
            'item'                        => [
                'itemtype'  => $itemtype,
                'items_id'  => $ID,
            ],
            'supported_filters'           => [
                'title' => [
                    'description' => _x('filters', 'The title of the item'),
                    'supported_prefixes' => ['!', '#'], // Support exclusions and regex
                ],
                'type' => [
                    'description' => _x('filters', 'The type of the item'),
                    'supported_prefixes' => ['!'],
                ],
                'category' => [
                    'description' => _x('filters', 'The category of the item'),
                    'supported_prefixes' => ['!', '#'],
                ],
                'content' => [
                    'description' => _x('filters', 'The content of the item'),
                    'supported_prefixes' => ['!', '#'], // Support exclusions and regex
                ],
                'team' => [
                    'description' => _x('filters', 'A team member for the item'),
                    'supported_prefixes' => ['!'],
                ],
                'user' => [
                    'description' => _x('filters', 'A user in the team of the item'),
                    'supported_prefixes' => ['!'],
                ],
                'group' => [
                    'description' => _x('filters', 'A group in the team of the item'),
                    'supported_prefixes' => ['!'],
                ],
                'supplier' => [
                    'description' => _x('filters', 'A supplier in the team of the item'),
                    'supported_prefixes' => ['!'],
                ],
            ] + self::getKanbanPluginFilters(static::getType()),
        ]);
    }

    public static function getAllForKanban($active = true, $current_id = -1)
    {
        // ITIL items only have a global view
        $items = [
            -1 => __('Global'),
        ];
        return $items;
    }

    public static function getAllKanbanColumns($column_field = null, $column_ids = [], $get_default = false)
    {

        if ($column_field === null) {
            $column_field = 'status';
        }
        $columns = [];
        if ($column_field === 'status') {
            $all_statuses = static::getAllStatusArray();
            foreach ($all_statuses as $status_id => $status) {
                $columns['status'][$status_id] = [
                    'id'           => $status_id,
                    'name'         => $status,
                    'color_class'  => 'itilstatus ' . static::getStatusKey($status_id),
                    'header_color' => 'var(--status-color)',
                    'drop_only'    => (int) $status_id === self::CLOSED,
                ];
            }
        } else {
            return [];
        }
        return $columns[$column_field];
    }

    public static function getTeamRoles(): array
    {
        return [
            Team::ROLE_REQUESTER,
            Team::ROLE_OBSERVER,
            Team::ROLE_ASSIGNED,
        ];
    }

    public static function getTeamRoleName(int $role, int $nb = 1): string
    {
        return match ($role) {
            Team::ROLE_REQUESTER => _n('Requester', 'Requesters', $nb),
            Team::ROLE_OBSERVER => _n('Observer', 'Observers', $nb),
            Team::ROLE_ASSIGNED => _n('Assignee', 'Assignees', $nb),
            default => '',
        };
    }

    /**
     * Returns the itemtypes that can be used as team members.
     *
     * @return array<class-string<CommonDBTM>>
     */
    public static function getTeamItemtypes(): array
    {
        return [User::class, Group::class, Supplier::class];
    }

    public function addTeamMember(string $itemtype, int $items_id, array $params = []): bool
    {
        if (
            array_key_exists('role', $params)
            && is_string($params['role'])
            && defined(CommonITILActor::class . '::' . strtoupper($params['role']))
        ) {
            $params['role'] = constant(CommonITILActor::class . '::' . strtoupper($params['role']));
        }
        $role = $params['role'] ?? CommonITILActor::ASSIGN;

        $link_item = $this->getActorObjectForItem($itemtype);
        $result = $link_item->add([
            static::getForeignKeyField()     => $this->getID(),
            $itemtype::getForeignKeyField()  => $items_id,
            'type'                           => $role,
        ]);
        return (bool) $result;
    }

    public function deleteTeamMember(string $itemtype, int $items_id, array $params = []): bool
    {
        $role = $params['role'] ?? CommonITILActor::ASSIGN;

        $link_item = $this->getActorObjectForItem($itemtype);
        $result = $link_item->deleteByCriteria([
            static::getForeignKeyField()     => $this->getID(),
            $itemtype::getForeignKeyField()  => $items_id,
            'type'                           => $role,
        ]);
        return (bool) $result;
    }

    public function getTeam(): array
    {
        global $DB;

        $team = [];

        $team_itemtypes = static::getTeamItemtypes();

        foreach ($team_itemtypes as $itemtype) {
            $link_item = $this->getActorObjectForItem($itemtype);
            $link_class = $link_item::class;

            $select = [];
            if ($itemtype === User::class) {
                $select = [$link_class::getTable() . '.' . $itemtype::getForeignKeyField(), 'type', 'name', 'realname', 'firstname'];
            } else {
                $select = [
                    $link_class::getTable() . '.' . $itemtype::getForeignKeyField(), 'type', 'name',
                    new QueryExpression('NULL as realname'),
                    new QueryExpression('NULL as firstname'),
                ];
            }

            $it = $DB->request([
                'SELECT' => $select,
                'FROM'   => $link_class::getTable(),
                'WHERE'  => [static::getForeignKeyField() => $this->getID()],
                'INNER JOIN' => [
                    $itemtype::getTable() => [
                        'ON'  => [
                            $itemtype::getTable()   => 'id',
                            $link_class::getTable() => $itemtype::getForeignKeyField(),
                        ],
                    ],
                ],
            ]);
            foreach ($it as $data) {
                $items_id = $data[$itemtype::getForeignKeyField()];
                $member = [
                    'itemtype'     => $itemtype,
                    'items_id'     => $items_id,
                    'role'         => $data['type'],
                    'name'         => $data['name'],
                    'realname'     => $data['realname'],
                    'firstname'    => $data['firstname'],
                    'display_name' => formatUserName($items_id, $data['name'], $data['realname'], $data['firstname']),
                ];
                $team[] = $member;
            }
        }

        usort(
            $team,
            fn(array $member_1, array $member_2) => strcasecmp($member_1['display_name'], $member_2['display_name'])
        );

        return $team;
    }

    public function getTimelineStats(): array
    {
        global $DB;

        $stats = [
            'total_duration' => 0,
            'percent_done'   => 0,
        ];

        // compute itilobject duration
        $task_class  = $this->getType() . "Task";
        $task_table  = getTableForItemType($task_class);
        $foreign_key = $this->getForeignKeyField();

        $criteria = [
            'SELECT' => ['SUM' => 'actiontime AS actiontime'],
            'FROM'   => $task_table,
            'WHERE'  => [$foreign_key => $this->fields['id']],
        ];

        $req = $DB->request($criteria);
        if ($row = $req->current()) {
            $stats['total_duration'] = $row['actiontime'];
        }

        // compute itilobject percent done
        $criteria    = [
            $foreign_key => $this->fields['id'],
            'state'     => [Planning::TODO, Planning::DONE],
        ];
        $total_tasks = countElementsInTable($task_table, $criteria);
        $criteria    = [
            $foreign_key => $this->fields['id'],
            'state'      => Planning::DONE,
        ];
        $done_tasks = countElementsInTable($task_table, $criteria);
        if ($total_tasks != 0) {
            $stats['percent_done'] = floor(100 * $done_tasks / $total_tasks);
        }

        return $stats;
    }

    /**
     * Returns an instance of validation class, if it exists.
     *
     * @return CommonITILValidation|null
     */
    public static function getValidationClassInstance(): ?CommonITILValidation
    {
        $validation_class_name = self::getValidationClassName();

        return $validation_class_name ? getItemForItemtype($validation_class_name) : null;
    }

    /**
     * @return class-string<CommonITILValidation>|null
     */
    public static function getValidationClassName(): ?string
    {
        $validation_class = static::class . 'Validation';
        if (class_exists($validation_class)) {
            return $validation_class;
        }

        return null;
    }


    /**
     * @return class-string<ITIL_ValidationStep>|null
     */
    public static function getValidationStepClassName(): ?string
    {
        $validation_class = static::class . 'ValidationStep';
        if (class_exists($validation_class)) {
            return $validation_class;
        }

        return null;
    }

    public static function getValidationStepInstance(): ?ITIL_ValidationStep
    {
        $class = self::getValidationStepClassName();

        return $class ? getItemForItemtype($class) : null;
    }

    /**
     * Instead of "{itemtype} - {name}" we will use {itemtype} ({id}) - {name}
     * as the ID of a Ticket/Change/Problem is an important information
     *
     * @return string
     */
    public function getBrowserTabName(): string
    {
        return sprintf(
            __('%1$s - %2$s'),
            static::getTypeName(1),
            $this->getHeaderName()
        );
    }


    /**
     * Count number of open children having same type as current item.
     *
     * @return integer
     */
    public function countOpenChildrenOfSameType()
    {
        $itemtype = $this->getType();
        $link_class = CommonITILObject_CommonITILObject::getLinkClass($itemtype, $itemtype);

        if ($link_class === null || $this->isNewItem()) {
            return 0;
        }

        $not_open_statuses = array_merge(
            static::getSolvedStatusArray(),
            static::getClosedStatusArray()
        );
        $open_statuses = array_diff(
            array_keys(static::getAllStatusArray()),
            $not_open_statuses
        );

        return $link_class::countLinksByStatus(
            $this->getType(),
            $this->getID(),
            $open_statuses,
            [CommonITILObject_CommonITILObject::SON_OF]
        );
    }

    /**
     * @param $output
     **/
    public static function showPreviewAssignAction($output)
    {
        //If ticket is assign to an object, display this information first
        if (isset($output["entities_id"], $output["items_id"], $output["itemtype"])) {
            if ($item = getItemForItemtype($output["itemtype"])) {
                if ($item->getFromDB($output["items_id"])) {
                    echo "<tr class='tab_bg_2'>";
                    echo "<td>" . __s('Assign equipment') . "</td>";

                    echo "<td>" . $item->getLink(['comments' => true]) . "</td>";
                    echo "</tr>";
                }
            }

            unset($output["items_id"], $output["itemtype"]);
        }
        unset($output["entities_id"]);
        return $output;
    }

    /**
     * Change $input values before applying business rules
     *
     * @param array $input
     *
     * @return void
     */
    protected function fillInputForBusinessRules(array &$input)
    {
        global $DB;

        $entities_id = $input['entities_id']
            ?? $this->fields['entities_id'];

        // If creation date is not set, then we're called during ticket creation
        $creation_date = !empty($this->fields['date_creation'])
            ? strtotime($this->fields['date_creation'])
            : time();

        // add calendars matching date creation (for business rules)
        $calendars = [];
        $ite_calendar = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => Calendar::getTable(),
            'WHERE'  => getEntitiesRestrictCriteria('', '', $entities_id, true),
        ]);
        foreach ($ite_calendar as $calendar_data) {
            $calendar = new Calendar();
            $calendar->getFromDB($calendar_data['id']);
            if ($calendar->isAWorkingHour($creation_date)) {
                $calendars[] = $calendar_data['id'];
            }
        }
        if (count($calendars)) {
            $input['_date_creation_calendars_id'] = $calendars;
        }

        $user = new User();
        if (isset($input["_users_id_requester"])) {
            if (
                !is_array($input["_users_id_requester"])
                && $user->getFromDB($input["_users_id_requester"])
            ) {
                $input['_locations_id_of_requester'] = $user->fields['locations_id'];
                $input['users_default_groups'] = $user->fields['groups_id'];
                $input['profiles_id'] = $user->fields['profiles_id']; //default profile
            } elseif (is_array($input["_users_id_requester"]) && ($user_id = reset($input["_users_id_requester"])) !== false) {
                if ($user->getFromDB($user_id)) {
                    $input['_locations_id_of_requester'] = $user->fields['locations_id'];
                    $input['users_default_groups'] = $user->fields['groups_id'];
                    $input['profiles_id'] = $user->fields['profiles_id']; //default profile
                }
            }
        }

        // Clean new lines before passing to rules
        if (isset($input["content"])) {
            $input["content"] = str_replace("\r\n", "\n", $input['content']);
        }

        // Set itil category code
        $cat_id = $input['itilcategories_id'] ?? 0;
        if ($cat_id) {
            $input['itilcategories_id_code'] = ITILCategory::getById($cat_id)->fields['code'];
        }

        // Set previous category code, this is needed to let the rule engine
        // decide if the code was changed
        $existing_cat_id = $this->fields['itilcategories_id'] ?? 0;
        if ($existing_cat_id > 0 && $category = ITILCategory::getById($existing_cat_id)) {
            $this->fields['itilcategories_id_code'] = $category->fields['code'];
        }

        // Add global validation
        if (!$this->isNewItem() && $this->isField('global_validation') && !isset($input['global_validation'])) {
            $input['global_validation'] = $this->fields['global_validation'];
        }

        if (!$this->isNewItem() && !isset($input['priority'])) {
            $input['priority'] = $this->fields['priority'];
        }

        if (!$this->isNewItem() && !isset($input['entities_id'])) {
            $input['entities_id'] = $this->fields['entities_id'];
        }
    }

    public static function cronInfo($name)
    {
        return match ($name) {
            'createinquest' => ['description' => __('Generation of satisfaction surveys')],
            default => [],
        };
    }

    /**
     * Cron for automatically creating surveys for ITIL Objects
     *
     * @param CronTask $task
     *
     * @return integer (0 : nothing done - 1 : done)
     **/
    public static function cronCreateInquest($task)
    {
        global $DB;

        $inquest_class = static::getType() . 'Satisfaction';

        if (!class_exists($inquest_class) || !is_a($inquest_class, CommonITILSatisfaction::class, true)) {
            return 0;
        }

        $inquest     = new $inquest_class();
        $tot         = 0;
        $maxentity   = [];
        $tabentities = [];

        // Get suffix for entity config fields. For backwards compatibility, ticket values have no suffix.
        $config_suffix = static::getType() === 'Ticket' ? '' : ('_' . strtolower(static::getType()));

        $rate = Entity::getUsedConfig('inquest_config' . $config_suffix, 0, 'inquest_rate' . $config_suffix);
        if ($rate > 0) {
            $tabentities[0] = $rate;
        }

        $dbentities = $DB->request(['FROM' => Entity::getTable()]);
        foreach ($dbentities as $entity) {
            $rate = Entity::getUsedConfig('inquest_config' . $config_suffix, $entity['id'], 'inquest_rate' . $config_suffix);
            if ($rate > 0) {
                $tabentities[$entity['id']] = $rate;
            }
        }

        foreach ($tabentities as $entity_id => $rate) {
            $parent        = Entity::getUsedConfig('inquest_config' . $config_suffix, $entity_id, 'entities_id');
            $delay         = Entity::getUsedConfig('inquest_config' . $config_suffix, $entity_id, 'inquest_delay' . $config_suffix);
            $duration      = Entity::getUsedConfig('inquest_config' . $config_suffix, $entity_id, 'inquest_duration' . $config_suffix);
            $type          = Entity::getUsedConfig('inquest_config' . $config_suffix, $entity_id);
            $max_closedate = Entity::getUsedConfig('inquest_config' . $config_suffix, $entity_id, 'max_closedate' . $config_suffix);

            $table = static::getTable();
            $survey_table = $inquest::getTable();
            $fk = static::getForeignKeyField();

            $iterator = $DB->request([
                'SELECT'    => [
                    "$table.id",
                    "$table.closedate",
                    "$table.entities_id",
                ],
                'FROM'      => $table,
                'LEFT JOIN' => [
                    $survey_table => [
                        'ON' => [
                            $survey_table   => $fk,
                            $table          => 'id',
                        ],
                    ],
                    'glpi_entities'            => [
                        'ON' => [
                            $table          => 'entities_id',
                            'glpi_entities' => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    "$table.entities_id"          => $entity_id,
                    "$table.is_deleted"           => 0,
                    "$table.status"               => self::CLOSED,
                    "$table.closedate"            => ['>', $max_closedate],
                    new QueryExpression(
                        QueryFunction::dateAdd(
                            date: "$table.closedate",
                            interval: $delay,
                            interval_unit: 'DAY'
                        ) . ' <= ' . QueryFunction::now()
                    ),
                    new QueryExpression(
                        QueryFunction::dateAdd(
                            date: "glpi_entities.max_closedate$config_suffix",
                            interval: $duration,
                            interval_unit: 'DAY'
                        ) . ' <= ' . QueryFunction::now()
                    ),
                    "$survey_table.id" => null,
                ],
                'ORDERBY'   => 'closedate ASC',
            ]);

            $nb            = 0;
            $max_closedate = '';
            foreach ($iterator as $itil_item) {
                $max_closedate = $itil_item['closedate'];
                if (mt_rand(1, 100) <= $rate) {
                    if (
                        $inquest->add([
                            $fk             => $itil_item['id'],
                            'date_begin'    => $_SESSION["glpi_currenttime"],
                            'entities_id'   => $itil_item['entities_id'],
                            'type'          => $type,
                        ])
                    ) {
                        $nb++;
                    }
                }
            }

            // keep max_closedate
            if (!empty($max_closedate)) {
                $entity = new Entity();
                $entity->getFromDB($entity_id);
                // If the inquest configuration is inherited, then the `max_closedate` value should be updated
                // on the entity that hosts the configuration; otherwise it has to be stored on current entity.
                // It is necessary to ensure that `Entity::getUsedConfig('inquest_config', $entity_id, 'max_closedate')`
                // will return the expected value.
                $target_entity_id = $entity->fields['inquest_config' . $config_suffix] === Entity::CONFIG_PARENT
                    ? Entity::getUsedConfig('inquest_config' . $config_suffix, $entity_id, 'entities_id', 0)
                    : $entity_id;
                if (!array_key_exists($target_entity_id, $maxentity) || $max_closedate > $maxentity[$target_entity_id]) {
                    $maxentity[$target_entity_id] = $max_closedate;
                }
            }

            if ($nb) {
                $tot += $nb;
                $task->addVolume($nb);
                $task->log(sprintf(
                    __('%1$s: %2$s'),
                    Dropdown::getDropdownName('glpi_entities', $entity_id),
                    $nb
                ));
            }
        }

        // Save max_closedate to avoid testing the same tickets twice
        foreach ($maxentity as $entity_id => $maxdate) {
            $entity = new Entity();
            $entity->update([
                'id'                             => $entity_id,
                'max_closedate' . $config_suffix => $maxdate,
            ]);
        }

        return ($tot > 0 ? 1 : 0);
    }

    /**
     * Returns the {@link CommonITILSatisfaction} class instance for the current itemtype
     * @return CommonITILSatisfaction|null
     */
    public static function getSatisfactionClassInstance(): ?CommonITILSatisfaction
    {
        $classname = static::class . 'Satisfaction';
        if (class_exists($classname) && is_a($classname, CommonITILSatisfaction::class, true)) {
            return new $classname();
        }
        return null;
    }

    /**
     * Displays the current satisfaction survey for the given item or a message stating there is no survey.
     *
     * @param CommonITILObject $item The ITIL Object
     * @return void
     * @since 11.0.0
     */
    final protected static function showSatisfactionTabContent(CommonITILObject $item): void
    {
        $satisfaction = static::getSatisfactionClassInstance();

        if ($satisfaction === null) {
            return;
        }

        if (
            in_array($item->fields['status'], static::getClosedStatusArray())
            && $satisfaction->getFromDB($item->getID())
        ) {
            $satisfaction->showSatisactionForm($item);
        } else {
            echo "<p class='center b'>" . __s('No generated survey') . "</p>";
        }
    }

    /**
     * Handle the potential creation of a satisfaction survey for the current item when the item is updated.
     *
     * Must be called from {@link static::post_updateItem()}
     *
     * @return void
     * @since 11.0.0
     */
    final protected function handleSatisfactionSurveyOnUpdate(): void
    {
        $satisfaction = static::getSatisfactionClassInstance();

        if ($satisfaction === null) {
            return;
        }

        // Get suffix for entity config fields. For backwards compatibility, ticket values have no suffix.
        $config_suffix = $this->getType() === 'Ticket' ? '' : ('_' . strtolower($this->getType()));
        $rate          = Entity::getUsedConfig(
            'inquest_config' . $config_suffix,
            $this->fields['entities_id'],
            'inquest_rate' . $config_suffix
        );
        $delay         = Entity::getUsedConfig(
            'inquest_config' . $config_suffix,
            $this->fields['entities_id'],
            'inquest_delay' . $config_suffix
        );
        $type          = Entity::getUsedConfig('inquest_config' . $config_suffix, $this->fields['entities_id']);
        $max_closedate = $this->fields['closedate'];

        if (
            in_array("status", $this->updates)
            && in_array($this->input["status"], static::getClosedStatusArray())
            && ($delay == 0)
            && ($rate > 0)
            && (mt_rand(1, 100) <= $rate)
        ) {
            $fkey = $this->getForeignKeyField();

            // For reopened ITIL object
            $satisfaction->delete([$fkey => $this->fields['id']]);

            $satisfaction->add(
                [
                    $fkey           => $this->fields['id'],
                    'date_begin'    => $_SESSION["glpi_currenttime"],
                    'entities_id'   => $this->fields['entities_id'],
                    'type'          => $type,
                    'max_closedate' => $max_closedate,
                ]
            );
        }
    }


    /**
     * Transfer "_actors" input (introduced in 10.0.0) into historical input keys.
     *
     * @param array $input
     *
     * @return array
     */
    protected function transformActorsInput(array $input): array
    {
        // Reload actors to be able to identify deleted users.
        if (!$this->isNewItem()) {
            $this->loadActors();
        }

        if (
            array_key_exists('_actors', $input)
            && is_array($input['_actors'])
            && count($input['_actors'])
        ) {
            foreach (['requester', 'observer', 'assign'] as $actor_type) {
                $actor_type_value = constant(CommonITILActor::class . '::' . strtoupper($actor_type));
                if ($actor_type_value === CommonITILActor::ASSIGN && !$this->canAssign()) {
                    continue;
                }
                if ($actor_type_value !== CommonITILActor::ASSIGN && !$this->isNewItem() && !$this->canUpdateItem()) {
                    continue;
                }

                $get_input_key = (fn(string $actor_itemtype, string $actor_type): string => sprintf(
                    '_%s_%s',
                    getForeignKeyFieldForItemType($actor_itemtype),
                    $actor_type
                ));

                // Normalize all keys.
                foreach ([User::class, Group::class, Supplier::class] as $actor_itemtype) {
                    $input_key = $get_input_key($actor_itemtype, $actor_type);
                    $notif_key = sprintf('%s_notif', $input_key);

                    if (!array_key_exists($input_key, $input) || !is_array($input[$input_key])) {
                        $input[$input_key] = !empty($input[$input_key]) ? [$input[$input_key]] : [];
                    }

                    if ($actor_itemtype !== Group::class) {
                        if (!array_key_exists($notif_key, $input) || !is_array($input[$notif_key])) {
                            $input[$notif_key] = [
                                'use_notification'  => [],
                                'alternative_email' => [],
                            ];
                        }
                        foreach (['use_notification', 'alternative_email'] as $param_key) {
                            if (
                                !array_key_exists($param_key, $input[$notif_key])
                                || $input[$notif_key][$param_key] === ''
                            ) {
                                $input[$notif_key][$param_key] = [];
                            } elseif (!is_array($input[$notif_key][$param_key])) {
                                $input[$notif_key][$param_key] = [$input[$notif_key][$param_key]];
                            }
                        }
                    }
                    $input[sprintf('%s_deleted', $input_key)] = [];
                }

                $actors = array_key_exists($actor_type, $input['_actors']) && is_array($input['_actors'][$actor_type])
                    ? $input['_actors'][$actor_type]
                    : [];

                // Extract actors from new actors list
                foreach ($actors as $actor) {
                    $input_key = $get_input_key($actor['itemtype'], $actor_type);
                    $notif_key = sprintf('%s_notif', $input_key);

                    // Use alternative_email in value key for "email" actors
                    $value_key = sprintf('_actors_%s', $actor['items_id'] ?: $actor['alternative_email'] ?? '');

                    if (array_key_exists($value_key, $input[$input_key])) {
                        continue;
                    }

                    $input[$input_key][$value_key] = $actor['items_id'];

                    if ($actor_itemtype !== Group::class && array_key_exists('use_notification', $actor)) {
                        $input[$notif_key]['use_notification'][$value_key]  = $actor['use_notification'];
                        $input[$notif_key]['alternative_email'][$value_key] = $actor['alternative_email'] ?? '';
                    }
                }

                // Identify deleted actors
                if (!$this->isNewItem()) {
                    $existings = $this->getActorsForType($actor_type_value);
                    foreach ($existings as $existing) {
                        $found = false;
                        foreach ($actors as $actor) {
                            if (
                                (
                                    // "email" actor match
                                    $actor['itemtype'] === User::class
                                    && $actor['items_id'] == 0
                                    && $actor['itemtype'] == $existing['itemtype']
                                    && $actor['alternative_email'] == $existing['alternative_email']
                                )
                                || (
                                    // other actor match
                                    $actor['items_id'] != 0
                                    && $actor['itemtype'] == $existing['itemtype']
                                    && $actor['items_id'] == $existing['items_id']
                                )
                            ) {
                                $found = true;
                                break;
                            }
                        }
                        if ($found === false) {
                            $input_key = $get_input_key($existing['itemtype'], $actor_type);
                            $input[sprintf('%s_deleted', $input_key)][] = $existing;
                        }
                    }
                }
            }
            unset($input['_actors']);
        }

        return $input;
    }

    /**
     * Get the first requester user
     *
     * @return null|User the first user added as a requester or 0 if no requester found
     */
    final public function getPrimaryRequesterUser(): ?User
    {
        if (!isset($this->fields['id']) || $this->isNewID($this->fields['id'])) {
            return null;
        }

        $user_link = $this->getActorObjectForItem(User::class);
        ;
        $rows = $user_link->find(
            [
                static::getForeignKeyField() => $this->fields['id'],
                'type' => CommonITILActor::REQUESTER,
            ],
            [
                'id ASC',
            ],
            1
        );
        $row = array_pop($rows);
        if ($row === null) {
            return null;
        }
        $user = User::getById($row['users_id']);
        if (!($user instanceof User)) {
            return null;
        }
        return $user;
    }

    public function prepareInputForClone($input)
    {
        unset($input['actiontime']);
        return $input;
    }

    public static function getMessageReferenceEvent(string $event): ?string
    {
        // All actions should be attached to thread instanciated by `new` event
        return 'new';
    }

    public static function getRuleCollectionClassInstance(int $entity_id): RuleCommonITILObjectCollection
    {
        $expected = 'Rule' . static::getType() . 'Collection';
        if (is_a($expected, RuleCommonITILObjectCollection::class, true)) {
            return new $expected($entity_id);
        }
        throw new RuntimeException(
            sprintf(
                'Collection class %s does not exists for rule type %s',
                $expected,
                static::getType()
            )
        );
    }

    private function loadActorsForRules(array &$input, array &$unchanged, array &$toclean_postrules): void
    {
        $usertypes           = [
            CommonITILActor::ASSIGN    => 'assign',
            CommonITILActor::REQUESTER => 'requester',
            CommonITILActor::OBSERVER  => 'observer',
        ];
        foreach ($usertypes as $k => $t) {
            //handle new input
            if (isset($input['_itil_' . $t]) && isset($input['_itil_' . $t]['_type'])) {
                // FIXME Deprecate these keys in GLPI 11.0.
                $field = $input['_itil_' . $t]['_type'] . 's_id';
                if (
                    isset($input['_itil_' . $t][$field])
                    && !isset($input[$field . '_' . $t])
                ) {
                    $input['_' . $field . '_' . $t][]             = $input['_itil_' . $t][$field];
                    $toclean_postrules['_' . $field . '_' . $t][] = $input['_itil_' . $t][$field];
                }
            }

            //handle existing actors: load all existing actors from ticket
            //to make sure business rules will receive all information, and not just
            //what have been entered in the html form.
            $existing_actors = [
                User::class     => $this->getUsers($k),
                Group::class    => $this->getGroups($k),
                Supplier::class => $this->getSuppliers($k),
            ];
            foreach ($existing_actors as $actor_itemtype => $actors) {
                $field = getForeignKeyFieldForItemType($actor_itemtype);
                $input_key = '_' . $field . '_' . $t;
                $deleted_key = $input_key . '_deleted';
                $deleted_actors = array_key_exists($deleted_key, $input) && is_array($input[$deleted_key]) ? array_column($input[$deleted_key], 'items_id') : [];
                $tmp_input = $input[$input_key] ?? [];
                if (!is_array($tmp_input)) {
                    $tmp_input = [$tmp_input];
                }
                $added_actors = array_diff($tmp_input, array_column($actors, $field));
                if ($added_actors === [] && $deleted_actors === []) {
                    $unchanged[] = $input_key;
                }
                foreach ($actors as $actor) {
                    if (
                        !isset($input[$input_key])
                        || (is_array($input[$input_key]) && !in_array($actor[$field], $input[$input_key]))
                        || (is_numeric($input[$input_key]) && $actor[$field] !== $input[$input_key])
                    ) {
                        if (
                            !array_key_exists($input_key, $input)
                            || (!is_array($input[$input_key]) && !is_numeric($input[$input_key]) && empty($input[$input_key]))
                        ) {
                            $input[$input_key] = [];
                        } elseif (!is_array($input[$input_key])) {
                            $input[$input_key] = [$input[$input_key]];
                        }
                        if (!in_array($actor[$field], $deleted_actors)) {
                            $input[$input_key][]             = $actor[$field];
                            $toclean_postrules[$input_key][] = $actor[$field];
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int $condition
     * @phpstan-param RuleCommonITILObject::ON* $condition
     * @param array $input
     * @param int $entid
     * @return void
     */
    protected function processRules(int $condition, array &$input, int $entid = -1): void
    {
        if (isset($input['_skip_rules']) && $input['_skip_rules'] !== false) {
            return;
        }

        if ($entid < 0) {
            $entid = $input['entities_id'];
        }

        $this->fillInputForBusinessRules($input);

        $rules = static::getRuleCollectionClassInstance((int) $entid);
        $rule = $rules->getRuleClass();

        $unchanged = [];
        $tocleanafterrules   = [];
        if ($condition === RuleCommonITILObject::ONUPDATE) {
            $this->loadActorsForRules($input, $unchanged, $tocleanafterrules);
        }

        $rules_params = ['recursive' => true];
        $rules_options = ['condition' => $condition];

        if ($condition === RuleCommonITILObject::ONUPDATE) {
            $rules_params['entities_id'] = $entid;
            $changes = [];
            foreach ($rule->getCriterias() as $key => $val) {
                if (array_key_exists($key, $input)) {
                    if (
                        (!isset($this->fields[$key]) || ($this->fields[$key] != $input[$key]))
                        && !in_array($key, $unchanged)
                    ) {
                        $changes[] = $key;
                    }
                }
            }
            if (count($changes)) {
                $rules_options['only_criteria'] = $changes;
            }
        }

        if ($condition === RuleCommonITILObject::ONADD || isset($rules_options['only_criteria'])) {
            if ($condition === RuleCommonITILObject::ONUPDATE) {
                $user = new User();
                $user_id = null;
                //try to find user from changes if exist (defined as _itil_requester)
                if (isset($input["_itil_requester"]["users_id"])) {
                    $user_id = $input["_itil_requester"]["users_id"];
                } elseif (isset($input["_users_id_requester"])) {  //else try to find user from input
                    $user_id = is_array($input["_users_id_requester"]) ? reset($input["_users_id_requester"]) : $input["_users_id_requester"];
                }

                if ($user_id !== null && $user->getFromDB($user_id)) {
                    $input['_locations_id_of_requester']   = $user->fields['locations_id'];
                    $input['users_default_groups']         = $user->fields['groups_id'];
                    $input['profiles_id']                  = $user->fields['profiles_id']; //default profile
                    $rules_options['only_criteria'][] = '_locations_id_of_requester';
                    $rules_options['only_criteria'][] = '_groups_id_of_requester';
                    $rules_options['only_criteria'][] = 'profiles_id';
                }
            }
            $input = $rules->processAllRules(
                $input,
                $input,
                $rules_params,
                $rules_options
            );
        }

        // Clean actors fields added for rules
        foreach ($tocleanafterrules as $key => $values_to_drop) {
            if (!array_key_exists($key, $input) || !is_array($input[$key])) {
                // Assign rules may remove input key or replace array by a single value.
                // In such case, as values were completely redefined by rules, there is no need to filter them.
                continue;
            }

            $input[$key] = array_filter($input[$key], static fn($value) => !in_array($value, $values_to_drop));
            if (empty($input[$key])) {
                unset($input[$key]);
            }
        }

        if ($condition === RuleCommonITILObject::ONADD) {
            // Recompute default values based on values computed by rules
            $input = $this->computeDefaultValuesForAdd($input);
        }
    }

    /**
     * Is the current user have right to update the current ITIL object?
     *
     * @return boolean
     **/
    public function canUpdateItem(): bool
    {
        if (!$this->checkEntity()) {
            return false;
        }

        return self::canUpdate();
    }

    public function canDeleteItem(): bool
    {

        if (!$this->checkEntity()) {
            return false;
        }
        return self::canDelete();
    }

    public function canAddItem(string $type): bool
    {
        if ($type == Document::class) {
            return $this->canAddDocuments();
        }

        return parent::canAddItem($type);
    }


    /**
     * Check whether the current user can add documents.
     */
    final protected function canAddDocuments(): bool
    {
        if (in_array($this->fields['status'], static::getClosedStatusArray())) {
            return false;
        }

        if ($this->canAddFollowups()) {
            return true;
        }

        return false;
    }

    public static function getTeamMemberForm(CommonDBTM $item): string
    {
        if (!($item instanceof CommonITILObject)) {
            throw new RuntimeException();
        }

        $itiltemplate = $item->getITILTemplateToUse(
            0,
            $item instanceof Ticket ? $item->fields['type'] : null,
            $item->fields['itilcategories_id'],
            $item->fields['entities_id']
        );
        $field_options = [
            'full_width' => true,
            'fields_template' => $itiltemplate,
            'add_field_class' => 'col-sm-12',
        ];
        return TemplateRenderer::getInstance()->render('components/itilobject/actors/main.html.twig', [
            'item' => $item,
            'entities_id' => 0,
            'canupdate' => true,
            'canassign' => true,
            'params' => $item->fields + ['load_actors' => false],
            'itiltemplate' => $itiltemplate,
            'canassigntome' => false,
            'field_options' => $field_options,
            'allow_auto_submit' => false,
            'main_rand' => mt_rand(),
        ]);
    }

    /**
     * Is the current user a requester of the current itil item and does he have
     * the right to update it?
     *
     * @return bool
     */
    public function canRequesterUpdateItem()
    {
        return true;
    }
}

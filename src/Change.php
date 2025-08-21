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
use Glpi\ContentTemplates\Parameters\ChangeParameters;
use Glpi\ContentTemplates\Parameters\CommonITILObjectParameters;
use Glpi\DBAL\QueryExpression;
use Glpi\RichText\RichText;
use Glpi\Search\DefaultSearchRequestInterface;

/**
 * Change Class
 **/
class Change extends CommonITILObject implements DefaultSearchRequestInterface
{
    // From CommonDBTM
    public $dohistory                   = true;
    protected static $forward_entity_to = ['ChangeValidation', 'ChangeCost'];

    // From CommonITIL
    public $userlinkclass               = 'Change_User';
    public $grouplinkclass              = 'Change_Group';
    public $supplierlinkclass           = 'Change_Supplier';

    public static $rightname            = 'change';
    protected $usenotepad               = true;

    public const MATRIX_FIELD                  = 'priority_matrix';
    public const URGENCY_MASK_FIELD            = 'urgency_mask';
    public const IMPACT_MASK_FIELD             = 'impact_mask';
    public const STATUS_MATRIX_FIELD           = 'change_status';

    // Specific status for changes
    public const EVALUATION             = 9;
    public const TEST                   = 11;
    public const QUALIFICATION          = 12;
    public const REFUSED                = 13;
    public const CANCELED               = 14;

    public static function getTypeName($nb = 0)
    {
        return _n('Change', 'Changes', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['helpdesk', self::class];
    }

    public function canSolve()
    {

        return (self::isAllowedStatus($this->fields['status'], self::SOLVED)
              // No edition on closed status
              && !in_array($this->fields['status'], static::getClosedStatusArray())
              && (Session::haveRight(self::$rightname, UPDATE)
                  || (Session::haveRight(self::$rightname, self::READMY)
                      && ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(
                                  CommonITILActor::ASSIGN,
                                  $_SESSION["glpigroups"]
                              ))))));
    }


    public static function canView(): bool
    {
        return Session::haveRightsOr(self::$rightname, [self::READALL, self::READMY]);
    }


    /**
     * Is the current user have right to show the current change ?
     *
     * @return boolean
     **/
    public function canViewItem(): bool
    {

        if (!$this->checkEntity(true)) {
            return false;
        }
        return (Session::haveRight(self::$rightname, self::READALL)
              || (Session::haveRight(self::$rightname, self::READMY)
                  && ($this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                      || $this->isUser(CommonITILActor::OBSERVER, Session::getLoginUserID())
                      || (isset($_SESSION["glpigroups"])
                          && ($this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION["glpigroups"])
                              || $this->haveAGroup(
                                  CommonITILActor::OBSERVER,
                                  $_SESSION["glpigroups"]
                              )))
                      || ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(
                                  CommonITILActor::ASSIGN,
                                  $_SESSION["glpigroups"]
                              ))))));
    }

    /**
     * Is the current user have right to create the current change ?
     *
     * @return boolean
     **/
    public function canCreateItem(): bool
    {

        if (!Session::haveAccessToEntity($this->getEntityID())) {
            return false;
        }
        return Session::haveRight(self::$rightname, CREATE);
    }


    /**
     * is the current user could reopen the current change
     *
     * @since 9.4.0
     *
     * @return boolean
     */
    public function canReopen()
    {
        return Session::haveRight('followup', CREATE)
             && in_array($this->fields["status"], static::getClosedStatusArray())
             && ($this->isAllowedStatus($this->fields['status'], self::INCOMING)
                 || $this->isAllowedStatus($this->fields['status'], self::EVALUATION));
    }


    public function prepareInputForAdd($input)
    {
        $input =  parent::prepareInputForAdd($input);
        if ($input === false) {
            return false;
        }

        $this->processRules(RuleCommonITILObject::ONADD, $input);

        if (!isset($input['_skip_auto_assign']) || $input['_skip_auto_assign'] === false) {
            // Manage auto assign
            $auto_assign_mode = Entity::getUsedConfig('auto_assign_mode', $input['entities_id']);

            switch ($auto_assign_mode) {
                case Entity::CONFIG_NEVER:
                    break;

                case Entity::AUTO_ASSIGN_HARDWARE_CATEGORY:
                case Entity::AUTO_ASSIGN_CATEGORY_HARDWARE:
                    // Auto assign tech/group from Category
                    // Changes are not associated to a hardware then both settings behave the same way
                    $input = $this->setTechAndGroupFromItilCategory($input);
                    break;
            }
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->transformActorsInput($input);

        $entid = $input['entities_id'] ?? $this->fields['entities_id'];
        $this->processRules(RuleCommonITILObject::ONUPDATE, $input, $entid);

        $input = parent::prepareInputForUpdate($input);
        return $input;
    }


    public function pre_deleteItem()
    {
        global $CFG_GLPI;

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
            NotificationEvent::raiseEvent('delete', $this);
        }
        return true;
    }


    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($this->canAdminActors()) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'add_actor'] = __s('Add an actor');
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'update_notif']
               = __s('Set notifications for all actors');
        }

        return $actions;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (static::canView()) {
            switch ($item::class) {
                case self::class:
                    $ong = [];
                    if ($item->canUpdate()) {
                        $ong[1] = static::createTabEntry(__('Statistics'), 0, null, 'ti ti-chart-pie');
                    }
                    $satisfaction = new ChangeSatisfaction();
                    if (
                        $satisfaction->getFromDB($item->getID())
                        && in_array($item->fields['status'], self::getClosedStatusArray())
                    ) {
                        $ong[3] = ChangeSatisfaction::createTabEntry(__('Satisfaction'), 0, static::getType());
                    }

                    return $ong;

                case User::class:
                    $nb = 0;
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            ['glpi_changes', 'glpi_changes_users'],
                            [
                                'glpi_changes_users.changes_id'  => new QueryExpression(DBmysql::quoteName('glpi_changes.id')),
                                'glpi_changes_users.users_id'    => $item->getID(),
                                'glpi_changes_users.type'        => CommonITILActor::REQUESTER,
                                'glpi_changes.is_deleted'        => 0,
                            ] + getEntitiesRestrictCriteria(self::getTable())
                        );
                    }
                    return self::createTabEntry(__('Created changes'), $nb, $item::getType());

                case Group::class:
                    $nb = 0;
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            ['glpi_changes', 'glpi_changes_groups'],
                            [
                                'glpi_changes_groups.changes_id' => new QueryExpression(DBmysql::quoteName('glpi_changes.id')),
                                'glpi_changes_groups.groups_id'  => $item->getID(),
                                'glpi_changes_groups.type'       => CommonITILActor::REQUESTER,
                                'glpi_changes.is_deleted'        => 0,
                            ] + getEntitiesRestrictCriteria(self::getTable())
                        );
                    }
                    return self::createTabEntry(__('Created changes'), $nb, $item::getType());
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch (get_class($item)) {
            case self::class:
                switch ($tabnum) {
                    case 1:
                        $item->showStats();
                        break;
                    case 3:
                        self::showSatisfactionTabContent($item);
                        break;
                }
                break;

            case User::class:
            case Group::class:
                return self::showListForItem($item, $withtemplate);
        }
        return true;
    }


    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(ChangeValidation::class, $ong, $options);
        $this->addStandardTab(ChangeCost::class, $ong, $options);
        $this->addStandardTab(Itil_Project::class, $ong, $options);
        $this->addStandardTab(Change_Problem::class, $ong, $options);
        $this->addStandardTab(Change_Ticket::class, $ong, $options);
        $this->addStandardTab(Change_Item::class, $ong, $options);
        if ($this->hasImpactTab()) {
            $this->addStandardTab(Impact::class, $ong, $options);
        }
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }


    public function cleanDBonPurge()
    {

        // CommonITILTask does not extends CommonDBConnexity
        $ct = new ChangeTask();
        $ct->deleteByCriteria(['changes_id' => $this->fields['id']]);

        // ChangeSatisfaction does not extends CommonDBConnexity
        $cs = new ChangeSatisfaction();
        $cs->deleteByCriteria(['changes_id' => $this->fields['id']]);

        $this->deleteChildrenAndRelationsFromDb(
            [
                // Done by parent: Change_Group::class,
                Change_Item::class,
                Change_Problem::class,
                // Done by parent: Change_Supplier::class,
                Change_Ticket::class,
                // Done by parent: Change_User::class,
                ChangeCost::class,
                ChangeValidationStep::class,
                ChangeValidation::class,
                // Done by parent: ITILSolution::class,
                Change_Change::class,
            ]
        );

        parent::cleanDBonPurge();
    }


    public function post_updateItem($history = true)
    {
        global $CFG_GLPI;

        parent::post_updateItem($history);

        $donotif = count($this->updates);

        if (isset($this->input['_forcenotif'])) {
            $donotif = true;
        }

        if (isset($this->input['_disablenotif'])) {
            $donotif = false;
        }

        if ($donotif && $CFG_GLPI["use_notifications"]) {
            $mailtype = "update";
            if (
                isset($this->input["status"]) && $this->input["status"]
                && in_array("status", $this->updates)
                && in_array($this->input["status"], static::getSolvedStatusArray())
            ) {
                $mailtype = "solved";
            }

            if (
                isset($this->input["status"])
                && $this->input["status"]
                && in_array("status", $this->updates)
                && in_array($this->input["status"], static::getClosedStatusArray())
            ) {
                $mailtype = "closed";
            }

            // Read again change to be sure that all data are up to date
            $this->getFromDB($this->fields['id']);
            NotificationEvent::raiseEvent($mailtype, $this);
        }

        $this->handleSatisfactionSurveyOnUpdate();
    }


    public function post_addItem()
    {
        global $DB;

        parent::post_addItem();

        if (isset($this->input['_tickets_id'])) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->input['_tickets_id'])) {
                $pt = new Change_Ticket();
                $pt->add(['tickets_id' => $this->input['_tickets_id'],
                    'changes_id' => $this->fields['id'],
                ]);

                if (!empty($ticket->fields['itemtype']) && $ticket->fields['items_id'] > 0) {
                    $it = new Change_Item();
                    $it->add(['changes_id' => $this->fields['id'],
                        'itemtype'   => $ticket->fields['itemtype'],
                        'items_id'   => $ticket->fields['items_id'],
                    ]);
                }

                //Copy associated elements
                $iterator = $DB->request([
                    'FROM'   => Item_Ticket::getTable(),
                    'WHERE'  => [
                        'tickets_id'   => $this->input['_tickets_id'],
                    ],
                ]);
                $assoc = new Change_Item();
                foreach ($iterator as $row) {
                    unset($row['tickets_id']);
                    unset($row['id']);
                    $row['changes_id'] = $this->fields['id'];
                    $assoc->add($row);
                }
            }
        }

        if (isset($this->input['_problems_id'])) {
            $problem = new Problem();
            if ($problem->getFromDB($this->input['_problems_id'])) {
                $cp = new Change_Problem();
                $cp->add(['problems_id' => $this->input['_problems_id'],
                    'changes_id'  => $this->fields['id'],
                ]);

                //Copy associated elements
                $iterator = $DB->request([
                    'FROM'   => Item_Problem::getTable(),
                    'WHERE'  => [
                        'problems_id'   => $this->input['_problems_id'],
                    ],
                ]);
                $assoc = new Change_Item();
                foreach ($iterator as $row) {
                    unset($row['problems_id']);
                    unset($row['id']);
                    $row['changes_id'] = $this->fields['id'];
                    $assoc->add($row);
                }
            }
        }

        $this->handleNewItemNotifications();

        if (
            isset($this->input['_from_items_id'])
            && isset($this->input['_from_itemtype'])
        ) {
            $change_item = new Change_Item();
            $change_item->add([
                'items_id'      => (int) $this->input['_from_items_id'],
                'itemtype'      => $this->input['_from_itemtype'],
                'changes_id'    => $this->fields['id'],
                '_disablenotif' => true,
            ]);
        }
    }

    #[Override]
    public static function getDefaultSearchRequest(): array
    {

        $search = ['criteria' => [ 0 => ['field'      => 12,
            'searchtype' => 'equals',
            'value'      => 'notold',
        ],
        ],
            'sort'     => 19,
            'order'    => 'DESC',
        ];

        return $search;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab = array_merge($tab, $this->getSearchOptionsMain());

        $tab[] = [
            'id'                 => '68',
            'table'              => 'glpi_changes_items',
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of items'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_changes_items',
            'field'              => 'items_id',
            'name'               => _n('Associated element', 'Associated elements', Session::getPluralNumber()),
            'datatype'           => 'specific',
            'comments'           => true,
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
            'table'              => 'glpi_changes_items',
            'field'              => 'itemtype',
            'name'               => _n('Associated item type', 'Associated item types', Session::getPluralNumber()),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'ticket_types',
            'nosort'             => true,
            'additionalfields'   => ['itemtype'],
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab = array_merge($tab, $this->getSearchOptionsActors());

        $tab[] = [
            'id'                 => 'analysis',
            'name'               => __('Control list'),
        ];

        $tab[] = [
            'id'                 => '60',
            'table'              => $this->getTable(),
            'field'              => 'impactcontent',
            'name'               => __('Analysis impact'),
            'massiveaction'      => false,
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => $this->getTable(),
            'field'              => 'controlistcontent',
            'name'               => __('Control list'),
            'massiveaction'      => false,
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '62',
            'table'              => $this->getTable(),
            'field'              => 'rolloutplancontent',
            'name'               => __('Deployment plan'),
            'massiveaction'      => false,
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '63',
            'table'              => $this->getTable(),
            'field'              => 'backoutplancontent',
            'name'               => __('Backup plan'),
            'massiveaction'      => false,
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '67',
            'table'              => $this->getTable(),
            'field'              => 'checklistcontent',
            'name'               => __('Checklist'),
            'massiveaction'      => false,
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab = array_merge($tab, ChangeValidation::rawSearchOptionsToAdd());

        $tab = array_merge($tab, ChangeSatisfaction::rawSearchOptionsToAdd());

        $tab = array_merge($tab, ITILFollowup::rawSearchOptionsToAdd());

        $tab = array_merge($tab, ChangeTask::rawSearchOptionsToAdd());

        $tab = array_merge($tab, $this->getSearchOptionsSolution());

        $tab = array_merge($tab, ChangeCost::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => 'ticket',
            'name'               => Ticket::getTypeName(Session::getPluralNumber()),
        ];

        $tab[] = [
            'id'                 => '164',
            'table'              => 'glpi_changes_tickets',
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of tickets'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => 'problem',
            'name'               => Problem::getTypeName(Session::getPluralNumber()),
        ];

        $tab[] = [
            'id'                 => '165',
            'table'              => 'glpi_changes_problems',
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of problems'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd(string $itemtype)
    {
        global $CFG_GLPI;

        $tab = [];

        if (in_array($itemtype, $CFG_GLPI["ticket_types"])) {
            $tab[] = [
                'id'            => 141,
                'table'         => self::getTable(),
                'field'         => "id",
                'datatype'      => "count",
                'name'          => _x('quantity', 'Number of changes'),
                'forcegroupby'  => true,
                'usehaving'     => true,
                'massiveaction' => false,
                'joinparams'    => [
                    'beforejoin' => [
                        'table' => self::getItemLinkClass()::getTable(),
                        'joinparams' => [
                            'jointype' => 'itemtype_item',
                        ],
                    ],
                    'condition' => getEntitiesRestrictCriteria('NEWTABLE'),
                ],
            ];
        }

        $tab[] = [
            'id'                 => 'change',
            'name'               => self::getTypeName(Session::getPluralNumber()),
        ];

        if ($itemtype == "Ticket") {
            $tab[] = [
                'id'                 => '210',
                'table'              => 'glpi_changes_tickets',
                'field'              => 'id',
                'name'               => _x('quantity', 'Number of changes'),
                'forcegroupby'       => true,
                'usehaving'          => true,
                'datatype'           => 'count',
                'massiveaction'      => false,
                'joinparams'         => [
                    'jointype'           => 'child',
                ],
            ];
        }

        if ($itemtype == "Problem") {
            $tab[] = [
                'id'                 => '211',
                'table'              => 'glpi_changes_problems',
                'field'              => 'id',
                'name'               => _x('quantity', 'Number of changes'),
                'forcegroupby'       => true,
                'usehaving'          => true,
                'datatype'           => 'count',
                'massiveaction'      => false,
                'joinparams'         => [
                    'jointype'           => 'child',
                ],
            ];
        }

        return $tab;
    }

    public static function getAllStatusArray($withmetaforsearch = false)
    {

        $tab = [
            self::INCOMING      => _x('status', 'New'),
            self::EVALUATION    => __('Evaluation'),
            self::APPROVAL      => _n('Approval', 'Approvals', 1),
            self::ACCEPTED      => _x('status', 'Accepted'),
            self::WAITING       => __('Pending'),
            self::TEST          => _x('change', 'Testing'),
            self::QUALIFICATION => __('Qualification'),
            self::SOLVED        => __('Applied'),
            self::OBSERVED      => __('Review'),
            self::CLOSED        => _x('status', 'Closed'),
            self::CANCELED      => _x('status', 'Cancelled'),
            self::REFUSED       => _x('status', 'Refused'),
        ];

        if ($withmetaforsearch) {
            $tab['notold']    = _x('status', 'Not solved');
            $tab['notclosed'] = _x('status', 'Not closed');
            $tab['process']   = __('Processing');
            $tab['old']       = _x('status', 'Solved + Closed');
            $tab['all']       = __('All');
        }
        return $tab;
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
        $tab = [
            self::CLOSED,
            self::CANCELED,
            self::REFUSED,
        ];
        return $tab;
    }


    /**
     * Get the ITIL object solved or observe status list
     *
     * @since 0.83
     *
     * @return array
     **/
    public static function getSolvedStatusArray()
    {
        // To be overridden by class
        $tab = [self::OBSERVED, self::SOLVED];
        return $tab;
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
        return [self::INCOMING, self::ACCEPTED, self::EVALUATION, self::APPROVAL];
    }

    /**
     * Get the ITIL object test, qualification or accepted status list
     * To be overridden by class
     *
     * @since 0.83
     *
     * @return array
     **/
    public static function getProcessStatusArray()
    {

        // To be overridden by class
        $tab = [self::ACCEPTED, self::QUALIFICATION, self::TEST];
        return $tab;
    }

    public static function getReopenableStatusArray()
    {
        return array_merge(self::getClosedStatusArray(), [self::SOLVED]);
    }

    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        unset($values[READ]);

        $values[self::READALL] = __('See all');
        $values[self::READMY]  = __('See (author)');
        $values[self::SURVEY]  = [
            'short' => __('Reply to survey (my change)'),
            'long'  => __('Reply to survey for ticket created by me'),
        ];

        return $values;
    }

    /**
     * Display changes for an item
     *
     * Will also display changes of linked items
     *
     * @param CommonDBTM $item
     * @param integer    $withtemplate
     *
     * @return boolean|void
     **/
    public static function showListForItem(CommonDBTM $item, $withtemplate = 0)
    {
        if (!Session::haveRightsOr(self::$rightname, [self::READALL])) {
            return false;
        }

        if ($item->isNewID($item->getID())) {
            return false;
        }

        $options = [
            'metacriteria' => [],
        ];

        switch (get_class($item)) {
            case Group::class:
                // Mini search engine
                /** @var Group $item */
                if ($item->haveChildren()) {
                    $tree = (int) Session::getSavedOption(self::class, 'tree', 0);
                    TemplateRenderer::getInstance()->display('components/form/item_itilobject_group.html.twig', [
                        'tree' => $tree,
                    ]);
                } else {
                    $tree = 0;
                }
                break;
        }
        Change_Item::showListForItem($item, $withtemplate, $options);
    }

    public static function getListForItemRestrict(CommonDBTM $item)
    {
        $restrict = [];

        switch (true) {
            case $item instanceof User:
                $restrict['glpi_changes_users.users_id'] = $item->getID();
                $restrict['glpi_changes_users.type'] = CommonITILActor::REQUESTER;
                break;

            case $item instanceof Supplier:
                $restrict['glpi_changes_suppliers.suppliers_id'] = $item->getID();
                $restrict['glpi_changes_suppliers.type'] = CommonITILActor::ASSIGN;
                break;

            case $item instanceof Group:
                if ($item->haveChildren()) {
                    $tree = Session::getSavedOption(self::class, 'tree', 0);
                } else {
                    $tree = 0;
                }
                $restrict['glpi_changes_groups.groups_id'] = ($tree ? getSonsOf('glpi_groups', $item->getID()) : $item->getID());
                $restrict['glpi_changes_groups.type'] = CommonITILActor::REQUESTER;
                break;

            default:
                $restrict['glpi_changes_items.items_id'] = $item->getID();
                $restrict['glpi_changes_items.itemtype'] = $item->getType();
                // you can only see your tickets
                if (!Session::haveRight(self::$rightname, self::READALL)) {
                    $or = [
                        'glpi_changes.users_id_recipient'   => Session::getLoginUserID(),
                        [
                            'AND' => [
                                'glpi_changes_users.changes_id'  => 'glpi_changes.id',
                                'glpi_changes_users.users_id'    => Session::getLoginUserID(),
                            ],
                        ],
                    ];
                    if (count($_SESSION['glpigroups'])) {
                        $or['glpi_changes_groups.groups_id'] = $_SESSION['glpigroups'];
                    }
                    $restrict[] = ['OR' => $or];
                }
        }

        return $restrict;
    }

    public static function getDefaultValues($entity = 0)
    {
        if (is_numeric(Session::getLoginUserID(false))) {
            $users_id_requester = Session::getLoginUserID();
        } else {
            $users_id_requester = 0;
        }

        $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $_SESSION['glpiactive_entity'], '', 1);
        return [
            '_users_id_requester'        => $users_id_requester,
            '_users_id_requester_notif'  => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => '',
            ],
            '_groups_id_requester'       => 0,
            '_users_id_assign'           => 0,
            '_users_id_assign_notif'     => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => '',
            ],
            '_groups_id_assign'          => 0,
            '_users_id_observer'         => 0,
            '_users_id_observer_notif'   => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => '',
            ],
            '_suppliers_id_assign_notif' => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => '',
            ],
            '_groups_id_observer'        => 0,
            '_suppliers_id_assign'       => 0,
            'priority'                   => 3,
            'urgency'                    => 3,
            'impact'                     => 3,
            'content'                    => '',
            'entities_id'                => $_SESSION['glpiactive_entity'],
            'name'                       => '',
            'itilcategories_id'          => 0,
            'actiontime'                 => 0,
            'date'                       => 'NULL',
            '_add_validation'            => 0,
            '_validation_targets'        => [],
            '_tasktemplates_id'          => [],
            'controlistcontent'          => '',
            'impactcontent'              => '',
            'rolloutplancontent'         => '',
            'backoutplancontent'         => '',
            'checklistcontent'           => '',
            'items_id'                   => 0,
            '_actors'                    => [],
            'status'                     => self::INCOMING,
            'time_to_resolve'            => 'NULL',
            'itemtype'                   => '',
            'locations_id'               => 0,
        ];
    }

    /**
     * Get active changes for an item
     *
     * @since 9.5
     *
     * @param string $itemtype     Item type
     * @param integer $items_id    ID of the Item
     *
     * @return DBmysqlIterator
     */
    public function getActiveChangesForItem($itemtype, $items_id)
    {
        global $DB;

        return $DB->request([
            'SELECT'    => [
                $this->getTable() . '.id',
                $this->getTable() . '.name',
                $this->getTable() . '.priority',
            ],
            'FROM'      => $this->getTable(),
            'LEFT JOIN' => [
                'glpi_changes_items' => [
                    'ON' => [
                        'glpi_changes_items' => 'changes_id',
                        $this->getTable()    => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_changes_items.itemtype' => $itemtype,
                'glpi_changes_items.items_id'    => $items_id,
                $this->getTable() . '.is_deleted' => 0,
                'NOT'                         => [
                    $this->getTable() . '.status' => array_merge(
                        static::getSolvedStatusArray(),
                        static::getClosedStatusArray()
                    ),
                ],
            ],
        ]);
    }


    public static function getIcon()
    {
        return "ti ti-clipboard-check";
    }

    public static function getItemLinkClass(): string
    {
        return Change_Item::class;
    }

    public static function getStatusKey($status)
    {
        switch ($status) {
            case self::REFUSED:
                return 'refused';
            case self::CANCELED:
                return 'canceled';
            default:
                return parent::getStatusKey($status);
        }
    }

    public static function getContentTemplatesParametersClassInstance(): CommonITILObjectParameters
    {
        return new ChangeParameters();
    }

    /**
     * @param $start
     * @param $status             (default 'process')
     * @param $showgroupchanges  (true by default)
     * @since 10.0.0
     *
     */
    public static function showCentralList($start, $status = "process", $showgroupchanges = true)
    {
        global $CFG_GLPI, $DB;

        if (!static::canView()) {
            return false;
        }

        $JOINS = [];
        $WHERE = [
            'is_deleted' => 0,
        ];
        $search_users_id = [
            'glpi_changes_users.users_id'   => Session::getLoginUserID(),
            'glpi_changes_users.type'       => CommonITILActor::REQUESTER,
        ];
        $search_assign = [
            'glpi_changes_users.users_id'   => Session::getLoginUserID(),
            'glpi_changes_users.type'       => CommonITILActor::ASSIGN,
        ];

        if ($showgroupchanges) {
            $search_users_id  = [0];
            $search_assign = [0];

            if (count($_SESSION['glpigroups'])) {
                $search_users_id = [
                    'glpi_changes_groups.groups_id' => $_SESSION['glpigroups'],
                    'glpi_changes_groups.type'      => CommonITILActor::REQUESTER,
                ];
                $search_assign = [
                    'glpi_changes_groups.groups_id' => $_SESSION['glpigroups'],
                    'glpi_changes_groups.type'      => CommonITILActor::ASSIGN,
                ];
            }
        }

        switch ($status) {
            case "waiting":
                $WHERE = array_merge(
                    $WHERE,
                    $search_assign,
                    ['status' => self::WAITING]
                );
                break;

            case "process":
                $WHERE = array_merge(
                    $WHERE,
                    $search_assign,
                    ['status' => [self::ACCEPTED, self::TEST, self::QUALIFICATION]]
                );
                break;

            case "tovalidate": // changes waiting for validation
                $JOINS['LEFT JOIN'] = [
                    'glpi_changevalidations' => [
                        'ON' => [
                            'glpi_changevalidations'   => 'changes_id',
                            'glpi_changes'             => 'id',
                        ],
                    ],
                ];
                $WHERE = array_merge(
                    $WHERE,
                    [
                        ChangeValidation::getTargetCriteriaForUser(Session::getLoginUserID()),
                        'glpi_changevalidations.status'  => CommonITILValidation::WAITING,
                        'glpi_changes.global_validation' => CommonITILValidation::WAITING,
                        'NOT'                            => [
                            'glpi_changevalidations.status'   => [self::SOLVED, self::CLOSED],
                        ],
                    ]
                );
                break;

            default:
                $WHERE = array_merge(
                    $WHERE,
                    $search_users_id,
                    [
                        'status' => array_diff(self::getAllStatusArray(), self::getClosedStatusArray()),
                    ]
                );
                $WHERE['NOT'] = $search_assign;
        }

        $criteria = [
            'SELECT'          => ['glpi_changes.id'],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_changes',
            'LEFT JOIN'       => [
                'glpi_changes_users'   => [
                    'ON' => [
                        'glpi_changes_users'   => 'changes_id',
                        'glpi_changes'         => 'id',
                    ],
                ],
                'glpi_changes_groups'  => [
                    'ON' => [
                        'glpi_changes_groups'  => 'changes_id',
                        'glpi_changes'         => 'id',
                    ],
                ],
            ],
            'WHERE'           => $WHERE + getEntitiesRestrictCriteria('glpi_changes'),
            'ORDERBY'         => 'date_mod DESC',
        ];

        if (count($JOINS)) {
            $criteria = array_merge_recursive($criteria, $JOINS);
        }

        $iterator = $DB->request($criteria);

        $total_row_count = count($iterator);
        $displayed_row_count = min((int) $_SESSION['glpidisplay_count_on_home'], $total_row_count);

        if ($total_row_count > 0) {
            $options  = [
                'criteria' => [],
                'reset'    => 'reset',
            ];
            $forcetab         = '';
            if ($showgroupchanges) {
                switch ($status) {
                    case "waiting":
                        $options['criteria'][0]['field']      = 12; // status
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = self::WAITING;
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 8; // groups_id_assign
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = 'mygroups';
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape(Change::getSearchURL() . '?' . Toolbox::append_params($options)) . "\">"
                            . Html::makeTitle(__('Changes on pending status'), $displayed_row_count, $total_row_count)
                            . "</a>";
                        break;

                    case "process":
                        $options['criteria'][0]['field']      = 12; // status
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = self::EVALUATION;
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 8; // groups_id_assign
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = 'mygroups';
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape(Change::getSearchURL() . '?' . Toolbox::append_params($options)) . "\">"
                            . Html::makeTitle(__('Changes to be processed'), $displayed_row_count, $total_row_count)
                            . "</a>";
                        break;

                    default:
                        $options['criteria'][0]['field']      = 12; // status
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = 'notold';
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 71; // groups_id
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = 'mygroups';
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape(Change::getSearchURL() . '?' . Toolbox::append_params($options)) . "\">"
                            . Html::makeTitle(__('Your changes in progress'), $displayed_row_count, $total_row_count)
                            . "</a>";
                }
            } else {
                switch ($status) {
                    case "waiting":
                        $options['criteria'][0]['field']      = 12; // status
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = self::WAITING;
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 5; // users_id_assign
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = Session::getLoginUserID();
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape(Change::getSearchURL() . '?' . Toolbox::append_params($options)) . "\">"
                            . Html::makeTitle(__('Changes on pending status'), $displayed_row_count, $total_row_count)
                            . "</a>";
                        break;

                    case "tovalidate":
                        $options['criteria'][0]['field']      = 55; // validation status
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = CommonITILValidation::WAITING;
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['criteria'][0]['field']      = 59; // validation aprobator user
                        $options['criteria'][1]['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][1]['criteria'][0]['value']      = 'myself'; // Resolved as current user's ID
                        $options['criteria'][1]['criteria'][1]['field']      = 195; // validation aprobator substitute user
                        $options['criteria'][1]['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['criteria'][1]['value']      = 'myself'; // Resolved as current user's ID
                        $options['criteria'][1]['criteria'][1]['link']       = 'OR';
                        $options['criteria'][1]['criteria'][2]['field']      = 196; // validation aprobator group
                        $options['criteria'][1]['criteria'][2]['searchtype'] = 'equals';
                        $options['criteria'][1]['criteria'][2]['value']      = 'mygroups'; // Resolved as groups the current user belongs to
                        $options['criteria'][1]['criteria'][2]['link']       = 'OR';
                        $options['criteria'][1]['criteria'][3]['field']      = 197; // validation aprobator group
                        $options['criteria'][1]['criteria'][3]['searchtype'] = 'equals';
                        $options['criteria'][1]['criteria'][3]['value']      = 'myself'; // Resolved as groups the current user belongs to
                        $options['criteria'][1]['criteria'][3]['link']       = 'OR';
                        $options['criteria'][1]['link']       = 'AND';

                        $options['criteria'][2]['field']      = 12; // validation aprobator
                        $options['criteria'][2]['searchtype'] = 'equals';
                        $options['criteria'][2]['value']      = 'notold';
                        $options['criteria'][2]['link']       = 'AND';

                        $options['criteria'][3]['field']      = 52; // global validation status
                        $options['criteria'][3]['searchtype'] = 'equals';
                        $options['criteria'][3]['value']      = CommonITILValidation::WAITING;
                        $options['criteria'][3]['link']       = 'AND';
                        $forcetab                         = 'ChangeValidation$1';

                        $main_header = "<a href=\"" . htmlescape(Change::getSearchURL() . '?' . Toolbox::append_params($options)) . "\">"
                            . Html::makeTitle(__('Your changes to approve'), $displayed_row_count, $total_row_count)
                            . "</a>";

                        break;

                    case "process":
                        $options['criteria'][0]['field']      = 5; // users_id_assign
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = Session::getLoginUserID();
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 12; // status
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = 'process';
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape(Change::getSearchURL() . '?' . Toolbox::append_params($options)) . "\">"
                            . Html::makeTitle(__('Changes to be processed'), $displayed_row_count, $total_row_count)
                            . "</a>";
                        break;

                    default:
                        $options['criteria'][0]['field']      = 4; // users_id
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = Session::getLoginUserID();
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 12; // status
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = 'notold';
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape(Change::getSearchURL() . '?' . Toolbox::append_params($options)) . "\">"
                            . Html::makeTitle(__('Your changes in progress'), $displayed_row_count, $total_row_count)
                            . "</a>";
                }
            }

            $twig_params = [
                'class'        => 'table table-borderless table-striped table-hover card-table',
                'header_rows'  => [
                    [
                        [
                            'colspan'   => 3,
                            'content'   => $main_header,
                        ],
                    ],
                ],
                'rows'         => [],
            ];
            $i = 0;
            if ($displayed_row_count > 0) {
                $twig_params['header_rows'][] = [
                    [
                        'content'   => __('ID'),
                        'style'     => 'width: 75px',
                    ],
                    [
                        'content'   => _n('Requester', 'Requesters', 1),
                        'style'     => 'width: 20%',
                    ],
                    __('Description'),
                ];
                foreach ($iterator as $data) {
                    $change = new self();
                    $rand = mt_rand();
                    $row = [
                        'values' => [],
                    ];

                    if ($change->getFromDBwithData($data['id'])) {
                        $bgcolor = htmlescape($_SESSION["glpipriority_" . $change->fields["priority"]]);
                        $name = htmlescape(sprintf(__('%1$s: %2$s'), __('ID'), $change->fields["id"]));
                        $row['values'][] = [
                            'class' => 'badge_block',
                            'content' => "<span style='background: $bgcolor'></span>&nbsp;$name",
                        ];

                        $requesters = [];
                        if (
                            isset($change->users[CommonITILActor::REQUESTER])
                            && count($change->users[CommonITILActor::REQUESTER])
                        ) {
                            foreach ($change->users[CommonITILActor::REQUESTER] as $d) {
                                if ($d["users_id"] > 0) {
                                    $name = '<i class="fs-4 ti ti-user text-muted me-1"></i>'
                                        . htmlescape(getUserName($d["users_id"]));
                                    $requesters[] = $name;
                                } else {
                                    $requesters[] = '<i class="fs-4 ti ti-mail text-muted me-1"></i>'
                                        . htmlescape($d['alternative_email']);
                                }
                            }
                        }

                        if (
                            isset($change->groups[CommonITILActor::REQUESTER])
                            && count($change->groups[CommonITILActor::REQUESTER])
                        ) {
                            foreach ($change->groups[CommonITILActor::REQUESTER] as $d) {
                                $requesters[] = '<i class="fs-4 ti ti-users text-muted me-1"></i>'
                                    . htmlescape(Dropdown::getDropdownName("glpi_groups", $d["groups_id"]));
                            }
                        }
                        $row['values'][] = implode('<br>', $requesters);

                        $link = "<a id='change" . $change->getID() . $rand . "' href='"
                            . htmlescape(Change::getFormURLWithID($change->fields["id"]));
                        if ($forcetab != '') {
                            $link .= "&amp;forcetab=" . htmlescape($forcetab);
                        }
                        $link .= "'>";
                        $link .= "<span class='b'>" . htmlescape($change->fields["name"]) . "</span></a>";
                        $link = sprintf(
                            __s('%1$s %2$s'),
                            $link,
                            Html::showToolTip(
                                RichText::getEnhancedHtml($change->fields['content']),
                                ['applyto' => 'change' . $change->fields["id"] . $rand,
                                    'display' => false,
                                ]
                            )
                        );

                        $row['values'][] = $link;
                    } else {
                        $row['class'] = 'tab_bg_2';
                        $row['values'] = [
                            [
                                'colspan' => 6,
                                'content' => "<i>" . __s('No ticket in progress.') . "</i>",
                            ],
                        ];
                    }
                    $twig_params['rows'][] = $row;

                    $i++;
                    if ($i == $displayed_row_count) {
                        break;
                    }
                }
            }
            TemplateRenderer::getInstance()->display('components/table.html.twig', $twig_params);
        }
    }


    /**
     * Get changes count
     *
     * @since 10.0.0
     *
     * @param bool $foruser only for current login user as requester
     * @param bool $display if false, return html
     **/
    public static function showCentralCount(bool $foruser = false, bool $display = true)
    {
        global $CFG_GLPI, $DB;

        // show a tab with count of jobs in the central and give link
        if (!static::canView()) {
            return false;
        }
        if (!Session::haveRight(self::$rightname, self::READALL)) {
            $foruser = true;
        }

        $table = self::getTable();
        $criteria = [
            'SELECT' => [
                'status',
                'COUNT'  => '* AS COUNT',
            ],
            'FROM'   => $table,
            'WHERE'  => getEntitiesRestrictCriteria($table),
            'GROUP'  => 'status',
        ];

        if ($foruser) {
            $criteria['LEFT JOIN'] = [
                'glpi_changes_users' => [
                    'ON' => [
                        'glpi_changes_users'   => 'changes_id',
                        $table                  => 'id', [
                            'AND' => [
                                'glpi_changes_users.type' => CommonITILActor::REQUESTER,
                            ],
                        ],
                    ],
                ],
            ];
            $WHERE = ['glpi_changes_users.users_id' => Session::getLoginUserID()];

            if (
                isset($_SESSION["glpigroups"])
                && count($_SESSION["glpigroups"])
            ) {
                $criteria['LEFT JOIN']['glpi_changes_groups'] = [
                    'ON' => [
                        'glpi_changes_groups'  => 'changes_id',
                        $table                  => 'id', [
                            'AND' => [
                                'glpi_changes_groups.type' => CommonITILActor::REQUESTER,
                            ],
                        ],
                    ],
                ];
                $WHERE['glpi_changes_groups.groups_id'] = $_SESSION['glpigroups'];
            }
            $criteria['WHERE'][] = ['OR' => $WHERE];
        }

        $deleted_criteria = $criteria;
        $criteria['WHERE']['glpi_changes.is_deleted'] = 0;
        $deleted_criteria['WHERE']['glpi_changes.is_deleted'] = 1;
        $iterator = $DB->request($criteria);
        $deleted_iterator = $DB->request($deleted_criteria);

        $status = [];
        foreach (self::getAllStatusArray() as $key => $val) {
            $status[$key] = 0;
        }

        foreach ($iterator as $data) {
            $status[$data["status"]] = $data["COUNT"];
        }

        $number_deleted = 0;
        foreach ($deleted_iterator as $data) {
            $number_deleted += $data["COUNT"];
        }

        $options = [];
        $options['criteria'][0]['field']      = 12;
        $options['criteria'][0]['searchtype'] = 'equals';
        $options['criteria'][0]['value']      = 'new';
        $options['criteria'][0]['link']       = 'AND';
        $options['reset']                     = 'reset';

        $twig_params = [
            'title'     => [
                'link'   => $CFG_GLPI["root_doc"] . "/front/change.php?" . Toolbox::append_params($options),
                'text'   => self::getTypeName(Session::getPluralNumber()),
                'icon'   => self::getIcon(),
            ],
            'items'     => [],
        ];

        foreach ($status as $key => $val) {
            $options['criteria'][0]['value'] = $key;
            $twig_params['items'][] = [
                'link'   => $CFG_GLPI["root_doc"] . "/front/change.php?" . Toolbox::append_params($options),
                'text'   => self::getStatus($key),
                'icon'   => self::getStatusClass($key),
                'count'  => $val,
            ];
        }

        $options['criteria'][0]['value'] = 'all';
        $options['is_deleted']  = 1;
        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/change.php?" . Toolbox::append_params($options),
            'text'   => __('Deleted'),
            'icon'   => 'ti ti-trash bg-red-lt',
            'count'  => $number_deleted,
        ];

        $output = TemplateRenderer::getInstance()->render('central/lists/itemtype_count.html.twig', $twig_params);
        if ($display) {
            echo $output;
        } else {
            return $output;
        }
    }

    /**
     * @since 10.0.0
     *
     * @param $ID
     * @param $forcetab  string   name of the tab to force at the display (default '')
     **/
    public static function showVeryShort($ID, $forcetab = '')
    {
        // Prints a job in short form
        // Should be called in a <table>-segment
        // Print links or not in case of user view
        // Make new job object and fill it from database, if success, print it
        $viewusers = User::canView();

        $change   = new self();
        $rand      = mt_rand();
        if ($change->getFromDBwithData($ID)) {
            $bgcolor = htmlescape($_SESSION["glpipriority_" . $change->fields["priority"]]);
            $name    = htmlescape(sprintf(__('%1$s: %2$s'), __('ID'), $change->fields["id"]));
            echo "<tr class='tab_bg_2'>";
            echo "<td>
            <div class='badge_block' style='border-color: $bgcolor'>
               <span style='background: $bgcolor'></span>&nbsp;$name
            </div>
         </td>";
            echo "<td class='center'>";

            if (
                isset($change->users[CommonITILActor::REQUESTER])
                && count($change->users[CommonITILActor::REQUESTER])
            ) {
                foreach ($change->users[CommonITILActor::REQUESTER] as $d) {
                    $user = new User();
                    if ($d["users_id"] > 0 && $user->getFromDB($d["users_id"])) {
                        $name     = "<span class='b'>" . htmlescape($user->getName()) . "</span>";
                        if ($viewusers) {
                            $name = sprintf(
                                __s('%1$s %2$s'),
                                $name,
                                Html::showToolTip(
                                    $user->getInfoCard(),
                                    [
                                        'link'    => $user->getLinkURL(),
                                        'display' => false,
                                    ]
                                )
                            );
                        }
                        echo $name;
                    } else {
                        echo htmlescape($d['alternative_email']) . "&nbsp;";
                    }
                    echo "<br>";
                }
            }

            if (
                isset($change->groups[CommonITILActor::REQUESTER])
                && count($change->groups[CommonITILActor::REQUESTER])
            ) {
                foreach ($change->groups[CommonITILActor::REQUESTER] as $d) {
                    echo htmlescape(Dropdown::getDropdownName("glpi_groups", $d["groups_id"]));
                    echo "<br>";
                }
            }

            echo "</td>";

            echo "<td>";
            $link = "<a id='change" . htmlescape($change->fields["id"] . $rand) . "' href='"
                . htmlescape(Change::getFormURLWithID($change->fields["id"]));
            if ($forcetab != '') {
                $link .= "&amp;forcetab=" . htmlescape($forcetab);
            }
            $link .= "'>";
            $link .= "<span class='b'>" . htmlescape($change->fields["name"]) . "</span></a>";
            $link = sprintf(
                __s('%1$s %2$s'),
                $link,
                Html::showToolTip(
                    RichText::getEnhancedHtml($change->fields['content']),
                    [
                        'applyto' => 'change' . $change->fields["id"] . $rand,
                        'display' => false,
                    ]
                )
            );
            echo $link;

            echo "</td>";

            // Finish Line
            echo "</tr>";
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='6' ><i>" . __s('No change found.') . "</i></td></tr>";
        }
    }
}

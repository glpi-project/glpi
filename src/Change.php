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

use Glpi\Application\View\TemplateRenderer;
use Glpi\ContentTemplates\Parameters\ChangeParameters;
use Glpi\RichText\RichText;

/**
 * Change Class
 **/
class Change extends CommonITILObject
{
   // From CommonDBTM
    public $dohistory                   = true;
    protected static $forward_entity_to = ['ChangeValidation', 'ChangeCost'];

   // From CommonITIL
    public $userlinkclass               = 'Change_User';
    public $grouplinkclass              = 'Change_Group';
    public $supplierlinkclass           = 'Change_Supplier';

    public static $rightname                   = 'change';
    protected $usenotepad               = true;

    const MATRIX_FIELD                  = 'priority_matrix';
    const URGENCY_MASK_FIELD            = 'urgency_mask';
    const IMPACT_MASK_FIELD             = 'impact_mask';
    const STATUS_MATRIX_FIELD           = 'change_status';


    const READMY                        = 1;
    const READALL                       = 1024;

   // Specific status for changes
    const REFUSED                       = 13;
    const CANCELED                      = 14;

    public static function getTypeName($nb = 0)
    {
        return _n('Change', 'Changes', $nb);
    }


    public function canSolve()
    {

        return (self::isAllowedStatus($this->fields['status'], self::SOLVED)
              // No edition on closed status
              && !in_array($this->fields['status'], $this->getClosedStatusArray())
              && (Session::haveRight(self::$rightname, UPDATE)
                  || (Session::haveRight(self::$rightname, self::READMY)
                      && ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(
                                  CommonITILActor::ASSIGN,
                                  $_SESSION["glpigroups"]
                              ))))));
    }


    public static function canView()
    {
        return Session::haveRightsOr(self::$rightname, [self::READALL, self::READMY]);
    }


    /**
     * Is the current user have right to show the current change ?
     *
     * @return boolean
     **/
    public function canViewItem()
    {

        if (!Session::haveAccessToEntity($this->getEntityID())) {
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
    public function canCreateItem()
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
             && in_array($this->fields["status"], $this->getClosedStatusArray())
             && ($this->isAllowedStatus($this->fields['status'], self::INCOMING)
                 || $this->isAllowedStatus($this->fields['status'], self::EVALUATION));
    }


    public function prepareInputForAdd($input)
    {
        $input =  parent::prepareInputForAdd($input);
        if ($input === false) {
            return false;
        }

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

            $input = $this->assign($input);
        }

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
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'add_actor'] = __('Add an actor');
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'update_notif']
               = __('Set notifications for all actors');
        }

        return $actions;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (static::canView()) {
            switch ($item->getType()) {
                case __CLASS__:
                    if ($item->canUpdate()) {
                         $ong[1] = __('Statistics');
                    }

                    return $ong;
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case __CLASS__:
                switch ($tabnum) {
                    case 1:
                        $item->showStats();
                        break;
                }
                break;
        }
        return true;
    }


    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('ChangeValidation', $ong, $options);
        $this->addStandardTab('ChangeCost', $ong, $options);
        $this->addStandardTab('Itil_Project', $ong, $options);
        $this->addStandardTab('Change_Problem', $ong, $options);
        $this->addStandardTab('Change_Ticket', $ong, $options);
        $this->addStandardTab('Change_Item', $ong, $options);
        if ($this->hasImpactTab()) {
            $this->addStandardTab('Impact', $ong, $options);
        }
        $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
        $this->addStandardTab('Notepad', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function cleanDBonPurge()
    {

       // CommonITILTask does not extends CommonDBConnexity
        $ct = new ChangeTask();
        $ct->deleteByCriteria(['changes_id' => $this->fields['id']]);

        $this->deleteChildrenAndRelationsFromDb(
            [
            // Done by parent: Change_Group::class,
                Change_Item::class,
                Change_Problem::class,
            // Done by parent: Change_Supplier::class,
                Change_Ticket::class,
            // Done by parent: Change_User::class,
                ChangeCost::class,
                ChangeValidation::class,
            // Done by parent: ITILSolution::class,
            ]
        );

        parent::cleanDBonPurge();
    }


    public function post_updateItem($history = 1)
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
                && in_array($this->input["status"], $this->getSolvedStatusArray())
            ) {
                $mailtype = "solved";
            }

            if (
                isset($this->input["status"])
                && $this->input["status"]
                && in_array("status", $this->updates)
                && in_array($this->input["status"], $this->getClosedStatusArray())
            ) {
                $mailtype = "closed";
            }

           // Read again change to be sure that all data are up to date
            $this->getFromDB($this->fields['id']);
            NotificationEvent::raiseEvent($mailtype, $this);
        }
    }


    public function post_addItem()
    {
        global $CFG_GLPI, $DB;

        parent::post_addItem();

        if (isset($this->input['_tickets_id'])) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->input['_tickets_id'])) {
                $pt = new Change_Ticket();
                $pt->add(['tickets_id' => $this->input['_tickets_id'],
                    'changes_id' => $this->fields['id']
                ]);

                if (!empty($ticket->fields['itemtype']) && $ticket->fields['items_id'] > 0) {
                     $it = new Change_Item();
                     $it->add(['changes_id' => $this->fields['id'],
                         'itemtype'   => $ticket->fields['itemtype'],
                         'items_id'   => $ticket->fields['items_id']
                     ]);
                }

                //Copy associated elements
                $iterator = $DB->request([
                    'FROM'   => Item_Ticket::getTable(),
                    'WHERE'  => [
                        'tickets_id'   => $this->input['_tickets_id']
                    ]
                ]);
                $assoc = new Change_Item();
                foreach ($iterator as $row) {
                     unset($row['tickets_id']);
                     unset($row['id']);
                     $row['changes_id'] = $this->fields['id'];
                     $assoc->add(Toolbox::addslashes_deep($row));
                }
            }
        }

        if (isset($this->input['_problems_id'])) {
            $problem = new Problem();
            if ($problem->getFromDB($this->input['_problems_id'])) {
                $cp = new Change_Problem();
                $cp->add(['problems_id' => $this->input['_problems_id'],
                    'changes_id'  => $this->fields['id']
                ]);

               //Copy associated elements
                $iterator = $DB->request([
                    'FROM'   => Item_Problem::getTable(),
                    'WHERE'  => [
                        'problems_id'   => $this->input['_problems_id']
                    ]
                ]);
                $assoc = new Change_Item();
                foreach ($iterator as $row) {
                     unset($row['problems_id']);
                     unset($row['id']);
                     $row['changes_id'] = $this->fields['id'];
                     $assoc->add(Toolbox::addslashes_deep($row));
                }
            }
        }

       // Processing notifications
        if ($CFG_GLPI["use_notifications"]) {
           // Clean reload of the change
            $this->getFromDB($this->fields['id']);

            $type = "new";
            if (
                isset($this->fields["status"])
                && in_array($this->input["status"], $this->getSolvedStatusArray())
            ) {
                $type = "solved";
            }
            NotificationEvent::raiseEvent($type, $this);
        }

        if (
            isset($this->input['_from_items_id'])
            && isset($this->input['_from_itemtype'])
        ) {
            $change_item = new Change_Item();
            $change_item->add([
                'items_id'      => (int)$this->input['_from_items_id'],
                'itemtype'      => $this->input['_from_itemtype'],
                'changes_id'    => $this->fields['id'],
                '_disablenotif' => true
            ]);
        }

        $this->handleItemsIdInput();
    }


    /**
     * Get default values to search engine to override
     **/
    public static function getDefaultSearchRequest()
    {

        $search = ['criteria' => [ 0 => ['field'      => 12,
            'searchtype' => 'equals',
            'value'      => 'notold'
        ]
        ],
            'sort'     => 19,
            'order'    => 'DESC'
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
                'jointype'           => 'child'
            ]
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
                'jointype'           => 'child'
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false
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
                'jointype'           => 'child'
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false
        ];

        $tab = array_merge($tab, $this->getSearchOptionsActors());

        $tab[] = [
            'id'                 => 'analysis',
            'name'               => __('Control list')
        ];

        $tab[] = [
            'id'                 => '60',
            'table'              => $this->getTable(),
            'field'              => 'impactcontent',
            'name'               => __('Analysis impact'),
            'massiveaction'      => false,
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => $this->getTable(),
            'field'              => 'controlistcontent',
            'name'               => __('Control list'),
            'massiveaction'      => false,
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '62',
            'table'              => $this->getTable(),
            'field'              => 'rolloutplancontent',
            'name'               => __('Deployment plan'),
            'massiveaction'      => false,
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '63',
            'table'              => $this->getTable(),
            'field'              => 'backoutplancontent',
            'name'               => __('Backup plan'),
            'massiveaction'      => false,
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '67',
            'table'              => $this->getTable(),
            'field'              => 'checklistcontent',
            'name'               => __('Checklist'),
            'massiveaction'      => false,
            'datatype'           => 'text'
        ];

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab = array_merge($tab, ChangeValidation::rawSearchOptionsToAdd());

        $tab = array_merge($tab, ITILFollowup::rawSearchOptionsToAdd());

        $tab = array_merge($tab, ChangeTask::rawSearchOptionsToAdd());

        $tab = array_merge($tab, $this->getSearchOptionsSolution());

        $tab = array_merge($tab, ChangeCost::rawSearchOptionsToAdd());

        return $tab;
    }


    /**
     * get the change status list
     * To be overridden by class
     *
     * @param $withmetaforsearch boolean (default false)
     *
     * @return array
     **/
    public static function getAllStatusArray($withmetaforsearch = false)
    {

        $tab = [self::INCOMING      => _x('status', 'New'),
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


    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        unset($values[READ]);

        $values[self::READALL] = __('See all');
        $values[self::READMY]  = __('See (author)');

        return $values;
    }

    /**
     * Display changes for an item
     *
     * Will also display changes of linked items
     *
     * @param CommonDBTM      $item
     * @param boolean|integer $withtemplate
     *
     * @return boolean|void
     **/
    public static function showListForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $DB;

        if (!Session::haveRight(self::$rightname, self::READALL)) {
            return false;
        }

        if ($item->isNewID($item->getID())) {
            return false;
        }

        $restrict = [];
        $options  = [
            'criteria' => [],
            'reset'    => 'reset',
        ];

        switch ($item->getType()) {
            case 'User':
                $restrict['glpi_changes_users.users_id'] = $item->getID();

                $options['criteria'][0]['field']      = 4; // status
                $options['criteria'][0]['searchtype'] = 'equals';
                $options['criteria'][0]['value']      = $item->getID();
                $options['criteria'][0]['link']       = 'OR';

                $options['criteria'][1]['field']      = 66; // status
                $options['criteria'][1]['searchtype'] = 'equals';
                $options['criteria'][1]['value']      = $item->getID();
                $options['criteria'][1]['link']       = 'OR';

                $options['criteria'][5]['field']      = 5; // status
                $options['criteria'][5]['searchtype'] = 'equals';
                $options['criteria'][5]['value']      = $item->getID();
                $options['criteria'][5]['link']       = 'OR';

                break;

            case 'Supplier':
                $restrict['glpi_changes_suppliers.suppliers_id'] = $item->getID();

                $options['criteria'][0]['field']      = 6;
                $options['criteria'][0]['searchtype'] = 'equals';
                $options['criteria'][0]['value']      = $item->getID();
                $options['criteria'][0]['link']       = 'AND';
                break;

            case 'Group':
               // Mini search engine
                if ($item->haveChildren()) {
                    $tree = Session::getSavedOption(__CLASS__, 'tree', 0);
                    echo "<table class='tab_cadre_fixe'>";
                    echo "<tr class='tab_bg_1'><th>" . __('Last changes') . "</th></tr>";
                    echo "<tr class='tab_bg_1'><td class='center'>";
                    echo __('Child groups');
                    Dropdown::showYesNo(
                        'tree',
                        $tree,
                        -1,
                        ['on_change' => 'reloadTab("start=0&tree="+this.value)']
                    );
                } else {
                    $tree = 0;
                }
                echo "</td></tr></table>";

                $restrict['glpi_changes_groups.groups_id'] = ($tree ? getSonsOf('glpi_groups', $item->getID()) : $item->getID());

                $options['criteria'][0]['field']      = 71;
                $options['criteria'][0]['searchtype'] = ($tree ? 'under' : 'equals');
                $options['criteria'][0]['value']      = $item->getID();
                $options['criteria'][0]['link']       = 'AND';
                break;

            default:
                $restrict['items_id'] = $item->getID();
                $restrict['itemtype'] = $item->getType();
                break;
        }

       // Link to open a new change
        if (
            $item->getID()
            && Change::isPossibleToAssignType($item->getType())
            && self::canCreate()
            && !(!empty($withtemplate) && $withtemplate == 2)
            && (!isset($item->fields['is_template']) || $item->fields['is_template'] == 0)
        ) {
            echo "<div class='firstbloc'>";
            Html::showSimpleForm(
                Change::getFormURL(),
                '_add_fromitem',
                __('New change for this item...'),
                [
                    '_from_itemtype' => $item->getType(),
                    '_from_items_id' => $item->getID(),
                    'entities_id'    => $item->fields['entities_id']
                ]
            );
            echo "</div>";
        }

        $criteria = self::getCommonCriteria();
        $criteria['WHERE'] = $restrict + getEntitiesRestrictCriteria(self::getTable());
        $criteria['LIMIT'] = (int)$_SESSION['glpilist_limit'];
        $iterator = $DB->request($criteria);
        $number = count($iterator);

       // Ticket for the item
        echo "<div><table class='tab_cadre_fixe'>";

        $colspan = 11;
        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $colspan++;
        }
        if ($number > 0) {
            Session::initNavigateListItems(
                'Change',
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(
                                            __('%1$s = %2$s'),
                                            $item->getTypeName(1),
                                            $item->getName()
                                        )
            );

            echo "<tr><th colspan='$colspan'>";

           //TRANS : %d is the number of problems
            echo sprintf(_n('Last %d change', 'Last %d changes', $number), $number);

            echo "</th></tr>";
        } else {
            echo "<tr><th>" . __('No change found.') . "</th></tr>";
        }
       // Ticket list
        if ($number > 0) {
            self::commonListHeader(Search::HTML_OUTPUT);

            foreach ($iterator as $data) {
                Session::addToNavigateListItems('Problem', $data["id"]);
                self::showShort($data["id"]);
            }
            self::commonListHeader(Search::HTML_OUTPUT);
        }

        echo "</table></div>";

       // Tickets for linked items
        $linkeditems = $item->getLinkedItems();
        $restrict = [];
        if (count($linkeditems)) {
            foreach ($linkeditems as $ltype => $tab) {
                foreach ($tab as $lID) {
                    $restrict[] = ['AND' => ['itemtype' => $ltype, 'items_id' => $lID]];
                }
            }
        }

        if (count($restrict)) {
            $criteria         = self::getCommonCriteria();
            $criteria['WHERE'] = ['OR' => $restrict]
            + getEntitiesRestrictCriteria(self::getTable());
            $iterator = $DB->request($criteria);
            $number = count($iterator);

            echo "<div class='spaced'><table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='$colspan'>";
            echo __('Changes on linked items');

            echo "</th></tr>";
            if ($number > 0) {
                self::commonListHeader(Search::HTML_OUTPUT);

                foreach ($iterator as $data) {
                    // Session::addToNavigateListItems(TRACKING_TYPE,$data["id"]);
                    self::showShort($data["id"]);
                }
                self::commonListHeader(Search::HTML_OUTPUT);
            } else {
                echo "<tr><th>" . __('No change found.') . "</th></tr>";
            }
            echo "</table></div>";
        }
    }


    /**
     * Display debug information for current object
     *
     * @since 0.90.2
     **/
    public function showDebug()
    {
        NotificationEvent::debugEvent($this);
    }

    public static function getDefaultValues($entity = 0)
    {
        $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $_SESSION['glpiactive_entity'], '', 1);
        return [
            '_users_id_requester'        => Session::getLoginUserID(),
            '_users_id_requester_notif'  => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => ''
            ],
            '_groups_id_requester'       => 0,
            '_users_id_assign'           => 0,
            '_users_id_assign_notif'     => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => ''
            ],
            '_groups_id_assign'          => 0,
            '_users_id_observer'         => 0,
            '_users_id_observer_notif'   => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => ''
            ],
            '_suppliers_id_assign_notif' => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => ''
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
            '_add_validation'            => 0,
            'users_id_validate'          => [],
            '_tasktemplates_id'          => [],
            'controlistcontent'          => '',
            'impactcontent'              => '',
            'rolloutplancontent'         => '',
            'backoutplancontent'         => '',
            'checklistcontent'           => '',
            'items_id'                   => 0,
            '_actors'                     => [],
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
                        $this->getTable()    => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_changes_items.itemtype' => $itemtype,
                'glpi_changes_items.items_id'    => $items_id,
                $this->getTable() . '.is_deleted' => 0,
                'NOT'                         => [
                    $this->getTable() . '.status' => array_merge(
                        $this->getSolvedStatusArray(),
                        $this->getClosedStatusArray()
                    )
                ]
            ]
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

    public static function getStatusClass($status)
    {
        $class = null;
        $solid = true;

        switch ($status) {
            case self::REFUSED:
            case self::CANCELED:
                $class = 'circle';
                break;
            default:
                return parent::getStatusClass($status);
        }

        return $class == null
         ? ''
         : 'itilstatus ' . ($solid ? 'fas fa-' : 'far fa-') . $class .
         " " . static::getStatusKey($status);
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

    public static function getTaskClass()
    {
        return ChangeTask::class;
    }

    public static function getContentTemplatesParametersClass(): string
    {
        return ChangeParameters::class;
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
        global $DB, $CFG_GLPI;

        if (!static::canView()) {
            return false;
        }

        $WHERE = [
            'is_deleted' => 0
        ];
        $search_users_id = [
            'glpi_changes_users.users_id'   => Session::getLoginUserID(),
            'glpi_changes_users.type'       => CommonITILActor::REQUESTER
        ];
        $search_assign = [
            'glpi_changes_users.users_id'   => Session::getLoginUserID(),
            'glpi_changes_users.type'       => CommonITILActor::ASSIGN
        ];

        if ($showgroupchanges) {
            $search_users_id  = [0];
            $search_assign = [0];

            if (count($_SESSION['glpigroups'])) {
                $search_users_id = [
                    'glpi_changes_groups.groups_id' => $_SESSION['glpigroups'],
                    'glpi_changes_groups.type'      => CommonITILActor::REQUESTER
                ];
                $search_assign = [
                    'glpi_changes_groups.groups_id' => $_SESSION['glpigroups'],
                    'glpi_changes_groups.type'      => CommonITILActor::ASSIGN
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

            default:
                $WHERE = array_merge(
                    $WHERE,
                    $search_users_id,
                    [
                        'status' => array_diff(self::getAllStatusArray(), self::getClosedStatusArray())
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
                        'glpi_changes'         => 'id'
                    ]
                ],
                'glpi_changes_groups'  => [
                    'ON' => [
                        'glpi_changes_groups'  => 'changes_id',
                        'glpi_changes'         => 'id'
                    ]
                ]
            ],
            'WHERE'           => $WHERE + getEntitiesRestrictCriteria('glpi_changes'),
            'ORDERBY'         => 'date_mod DESC'
        ];
        $iterator = $DB->request($criteria);

        $total_row_count = count($iterator);
        $displayed_row_count = (int)$_SESSION['glpidisplay_count_on_home'] > 0
         ? min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count)
         : $total_row_count;

        if ($displayed_row_count > 0) {
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

                        $main_header = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                         Toolbox::append_params($options, '&amp;') . "\">" .
                         Html::makeTitle(__('Changes on pending status'), $displayed_row_count, $total_row_count) . "</a>";
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

                        $main_header = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                        Toolbox::append_params($options, '&amp;') . "\">" .
                        Html::makeTitle(__('Changes to be processed'), $displayed_row_count, $total_row_count) . "</a>";
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

                        $main_header = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                        Toolbox::append_params($options, '&amp;') . "\">" .
                        Html::makeTitle(__('Your changes in progress'), $displayed_row_count, $total_row_count) . "</a>";
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

                        $main_header = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                         Toolbox::append_params($options, '&amp;') . "\">" .
                         Html::makeTitle(__('Changes on pending status'), $displayed_row_count, $total_row_count) . "</a>";
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

                        $main_header = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                        Toolbox::append_params($options, '&amp;') . "\">" .
                        Html::makeTitle(__('Changes to be processed'), $displayed_row_count, $total_row_count) . "</a>";
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

                        $main_header = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                        Toolbox::append_params($options, '&amp;') . "\">" .
                        Html::makeTitle(__('Your changes in progress'), $displayed_row_count, $total_row_count) . "</a>";
                }
            }

            $twig_params = [
                'class'        => 'table table-borderless table-striped table-hover card-table',
                'header_rows'  => [
                    [
                        [
                            'colspan'   => 3,
                            'content'   => $main_header
                        ]
                    ],
                    [
                        [
                            'content'   => __('ID'),
                            'style'     => 'width: 75px'
                        ],
                        [
                            'content'   => _n('Requester', 'Requesters', 1),
                            'style'     => 'width: 20%'
                        ],
                        __('Description')
                    ]
                ],
                'rows'         => []
            ];
            $i = 0;
            foreach ($iterator as $data) {
                $change   = new self();
                $rand      = mt_rand();
                $row = [
                    'values' => []
                ];

                if ($change->getFromDBwithData($data['id'], 0)) {
                    $bgcolor = $_SESSION["glpipriority_" . $change->fields["priority"]];
                    $name    = sprintf(__('%1$s: %2$s'), __('ID'), $change->fields["id"]);
                    $row['values'][] = [
                        'class'   => 'priority_block',
                        'content' => "<span style='background: $bgcolor'></span>&nbsp;$name"
                    ];

                    $requesters = [];
                    if (
                        isset($change->users[CommonITILActor::REQUESTER])
                        && count($change->users[CommonITILActor::REQUESTER])
                    ) {
                        foreach ($change->users[CommonITILActor::REQUESTER] as $d) {
                            if ($d["users_id"] > 0) {
                                $userdata = getUserName($d["users_id"], 2);
                                $name     = '<i class="fas fa-sm fa-fw fa-user text-muted me-1"></i>' .
                                    $userdata['name'];
                                $requesters[] = $name;
                            } else {
                                $requesters[] = '<i class="fas fa-sm fa-fw fa-envelope text-muted me-1"></i>' .
                                       $d['alternative_email'];
                            }
                        }
                    }

                    if (
                        isset($change->groups[CommonITILActor::REQUESTER])
                        && count($change->groups[CommonITILActor::REQUESTER])
                    ) {
                        foreach ($change->groups[CommonITILActor::REQUESTER] as $d) {
                            $requesters[] = '<i class="fas fa-sm fa-fw fa-users text-muted me-1"></i>' .
                                     Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
                        }
                    }
                    $row['values'][] = implode('<br>', $requesters);

                    $link = "<a id='change" . $change->fields["id"] . $rand . "' href='" .
                    Change::getFormURLWithID($change->fields["id"]);
                    if ($forcetab != '') {
                        $link .= "&amp;forcetab=" . $forcetab;
                    }
                    $link .= "'>";
                    $link .= "<span class='b'>" . $change->fields["name"] . "</span></a>";
                    $link = sprintf(
                        __('%1$s %2$s'),
                        $link,
                        Html::showToolTip(
                            RichText::getEnhancedHtml($change->fields['content']),
                            ['applyto' => 'change' . $change->fields["id"] . $rand,
                                'display' => false
                            ]
                        )
                    );

                    $row['values'][] = $link;
                } else {
                    $row['class'] = 'tab_bg_2';
                    $row['values'] = [
                        [
                            'colspan'   => 6,
                            'content'   => "<i>" . __('No ticket in progress.') . "</i>"
                        ]
                    ];
                }
                $twig_params['rows'][] = $row;

                $i++;
                if ($i == $displayed_row_count) {
                    break;
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
        global $DB, $CFG_GLPI;

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
            'GROUP'  => 'status'
        ];

        if ($foruser) {
            $criteria['LEFT JOIN'] = [
                'glpi_changes_users' => [
                    'ON' => [
                        'glpi_changes_users'   => 'changes_id',
                        $table                  => 'id', [
                            'AND' => [
                                'glpi_changes_users.type' => CommonITILActor::REQUESTER
                            ]
                        ]
                    ]
                ]
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
                                'glpi_changes_groups.type' => CommonITILActor::REQUESTER
                            ]
                        ]
                    ]
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
            'items'     => []
        ];

        foreach ($status as $key => $val) {
            $options['criteria'][0]['value'] = $key;
            $twig_params['items'][] = [
                'link'   => $CFG_GLPI["root_doc"] . "/front/change.php?" . Toolbox::append_params($options),
                'text'   => self::getStatus($key),
                'icon'   => self::getStatusClass($key),
                'count'  => $val
            ];
        }

        $options['criteria'][0]['value'] = 'all';
        $options['is_deleted']  = 1;
        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/change.php?" . Toolbox::append_params($options),
            'text'   => __('Deleted'),
            'icon'   => 'fas fa-trash bg-red-lt',
            'count'  => $number_deleted
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
        if ($change->getFromDBwithData($ID, 0)) {
            $bgcolor = $_SESSION["glpipriority_" . $change->fields["priority"]];
            $name    = sprintf(__('%1$s: %2$s'), __('ID'), $change->fields["id"]);
            echo "<tr class='tab_bg_2'>";
            echo "<td>
            <div class='priority_block' style='border-color: $bgcolor'>
               <span style='background: $bgcolor'></span>&nbsp;$name
            </div>
         </td>";
            echo "<td class='center'>";

            if (
                isset($change->users[CommonITILActor::REQUESTER])
                && count($change->users[CommonITILActor::REQUESTER])
            ) {
                foreach ($change->users[CommonITILActor::REQUESTER] as $d) {
                    if ($d["users_id"] > 0) {
                         $userdata = getUserName($d["users_id"], 2);
                         $name     = "<span class='b'>" . $userdata['name'] . "</span>";
                        if ($viewusers) {
                            $name = sprintf(
                                __('%1$s %2$s'),
                                $name,
                                Html::showToolTip(
                                    $userdata["comment"],
                                    ['link'    => $userdata["link"],
                                        'display' => false
                                    ]
                                )
                            );
                        }
                         echo $name;
                    } else {
                        echo $d['alternative_email'] . "&nbsp;";
                    }
                    echo "<br>";
                }
            }

            if (
                isset($change->groups[CommonITILActor::REQUESTER])
                && count($change->groups[CommonITILActor::REQUESTER])
            ) {
                foreach ($change->groups[CommonITILActor::REQUESTER] as $d) {
                    echo Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
                    echo "<br>";
                }
            }

            echo "</td>";

            echo "<td>";
            $link = "<a id='change" . $change->fields["id"] . $rand . "' href='" .
            Change::getFormURLWithID($change->fields["id"]);
            if ($forcetab != '') {
                $link .= "&amp;forcetab=" . $forcetab;
            }
            $link .= "'>";
            $link .= "<span class='b'>" . $change->fields["name"] . "</span></a>";
            $link = printf(
                __('%1$s %2$s'),
                $link,
                Html::showToolTip(
                    $change->fields['content'],
                    ['applyto' => 'change' . $change->fields["id"] . $rand,
                        'display' => false
                    ]
                )
            );

            echo "</td>";

           // Finish Line
            echo "</tr>";
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='6' ><i>" . __('No change found.') . "</i></td></tr>";
        }
    }
}

<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
use Glpi\Toolbox\Sanitizer;

/**
 * @since 9.4.0
 */
class ITILFollowup extends CommonDBChild
{
    use Glpi\Features\ParentStatus;

   // From CommonDBTM
    public $auto_message_on_action = false;
    public static $rightname              = 'followup';
    private $item                  = null;

    public static $log_history_add    = Log::HISTORY_LOG_SIMPLE_MESSAGE;
    public static $log_history_update = Log::HISTORY_LOG_SIMPLE_MESSAGE;
    public static $log_history_delete = Log::HISTORY_LOG_SIMPLE_MESSAGE;

    const SEEPUBLIC       =    1;
    const UPDATEMY        =    2;
    const ADDMYTICKET     =    4;
    const UPDATEALL       = 1024;
    const ADDGROUPTICKET  = 2048;
    const ADDALLTICKET    = 4096;
    const SEEPRIVATE      = 8192;

    /**
     * Right allowing the user to add a follow-up as soon as he is an observer of an ITIL object.
     * @var integer
     */
    const ADD_AS_OBSERVER = 16384;

    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';


    public function getItilObjectItemType()
    {
        return str_replace('Followup', '', $this->getType());
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Followup', 'Followups', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-message-circle';
    }

    /**
     * can read the parent ITIL Object ?
     *
     * @return boolean
     */
    public function canReadITILItem()
    {
        if ($this->isParentAlreadyLoaded()) {
            $item = $this->item;
        } else {
            $itemtype = $this->getItilObjectItemType();
            $item     = new $itemtype();
        }
        if (!$item->can($this->getField($item->getForeignKeyField()), READ)) {
            return false;
        }
        return true;
    }


    public static function canView()
    {
        return (Session::haveRightsOr(self::$rightname, [self::SEEPUBLIC, self::SEEPRIVATE])
              || Session::haveRight('ticket', Ticket::OWN))
              || Session::haveRight('ticket', READ)
              || Session::haveRight('change', READ)
              || Session::haveRight('problem', READ);
    }


    public static function canCreate()
    {
        return Session::haveRight('change', UPDATE)
             || Session::haveRight('problem', UPDATE)
             || (Session::haveRightsOr(
                 self::$rightname,
                 [self::ADDALLTICKET, self::ADDMYTICKET, self::ADDGROUPTICKET]
             )
             || Session::haveRight('ticket', Ticket::OWN));
    }


    public function canViewItem()
    {

        if ($this->isParentAlreadyLoaded()) {
            $itilobject = $this->item;
        } else {
            $itilobject = new $this->fields['itemtype']();
        }
        if (!$itilobject->can($this->getField('items_id'), READ)) {
            return false;
        }
        if (Session::haveRight(self::$rightname, self::SEEPRIVATE)) {
            return true;
        }
        if (
            !$this->fields['is_private']
            && Session::haveRight(self::$rightname, self::SEEPUBLIC)
        ) {
            return true;
        }
        if ($itilobject instanceof Ticket) {
            if ($this->fields["users_id"] === Session::getLoginUserID()) {
                return true;
            }
        } else {
            return Session::haveRight($itilobject::$rightname, READ);
        }
        return false;
    }


    public function canCreateItem()
    {
        if (
            !isset($this->fields['itemtype'])
            || strlen($this->fields['itemtype']) == 0
        ) {
            return false;
        }

        if ($this->isParentAlreadyLoaded()) {
            $itilobject = $this->item;
        } else {
            $itilobject = new $this->fields['itemtype']();
        }

        if (
            !$itilobject->can($this->getField('items_id'), READ)
            // No validation for closed tickets
            || in_array($itilobject->fields['status'], $itilobject->getClosedStatusArray())
            && !$itilobject->canReopen()
        ) {
            return false;
        }
        return $itilobject->canAddFollowups();
    }


    public function canPurgeItem()
    {
        if ($this->isParentAlreadyLoaded()) {
            $itilobject = $this->item;
        } else {
            $itilobject = new $this->fields['itemtype']();
        }
        if (!$itilobject->can($this->getField('items_id'), READ)) {
            return false;
        }

        if (Session::haveRight(self::$rightname, PURGE)) {
            return true;
        }

        return false;
    }


    public function canUpdateItem()
    {

        if (
            ($this->fields["users_id"] != Session::getLoginUserID())
            && !Session::haveRight(self::$rightname, self::UPDATEALL)
        ) {
            return false;
        }

        if ($this->isParentAlreadyLoaded()) {
            $itilobject = $this->item;
        } else {
            $itilobject = new $this->fields['itemtype']();
        }
        if (!$itilobject->can($this->getField('items_id'), READ)) {
            return false;
        }

        if ($this->fields["users_id"] === Session::getLoginUserID()) {
            if (!Session::haveRight(self::$rightname, self::UPDATEMY)) {
                return false;
            }
            return true;
        }

       // Only the technician
        return (Session::haveRight(self::$rightname, self::UPDATEALL)
              || $itilobject->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $itilobject->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));
    }


    public function post_getEmpty()
    {

        if (isset($_SESSION['glpifollowup_private']) && $_SESSION['glpifollowup_private']) {
            $this->fields['is_private'] = 1;
        }

        if (isset($_SESSION["glpiname"])) {
            $this->fields['requesttypes_id'] = RequestType::getDefault('followup');
        }
    }


    public function post_addItem()
    {

        global $CFG_GLPI;

        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, [
            'force_update' => true,
            'date' => $this->fields['date'],
        ]);

       // Check if stats should be computed after this change
        $no_stat = isset($this->input['_do_not_compute_takeintoaccount']);

        $parentitem = $this->input['_job'];
        $parentitem->updateDateMod(
            $this->input["items_id"],
            $no_stat,
            $this->input["users_id"]
        );

        $this->updateParentStatus($this->input['_job'], $this->input);

        $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

        if ($donotif) {
            $options = ['followup_id' => $this->fields["id"],
                'is_private'  => $this->fields['is_private']
            ];
            NotificationEvent::raiseEvent("add_followup", $parentitem, $options);
        }

       // Add log entry in the ITILObject
        $changes = [
            0,
            '',
            $this->fields['id'],
        ];
        Log::history(
            $this->getField('items_id'),
            get_class($parentitem),
            $changes,
            $this->getType(),
            Log::HISTORY_ADD_SUBITEM
        );

        parent::post_addItem();
    }


    public function post_deleteFromDB()
    {
        global $CFG_GLPI;

        $donotif = $CFG_GLPI["use_notifications"];
        if (isset($this->input['_disablenotif'])) {
            $donotif = false;
        }

        $job = new $this->fields['itemtype']();
        $job->getFromDB($this->fields[self::$items_id]);
        $job->updateDateMod($this->fields[self::$items_id]);

       // Add log entry in the ITIL Object
        $changes = [
            0,
            '',
            $this->fields['id'],
        ];
        Log::history(
            $this->getField(self::$items_id),
            $this->fields['itemtype'],
            $changes,
            $this->getType(),
            Log::HISTORY_DELETE_SUBITEM
        );

        if ($donotif) {
            $options = ['followup_id' => $this->fields["id"],
                           // Force is_private with data / not available
                'is_private'  => $this->fields['is_private']
            ];
            NotificationEvent::raiseEvent('delete_followup', $job, $options);
        }
    }


    public function prepareInputForAdd($input)
    {
        //Handle template
        if (isset($input['_itilfollowuptemplates_id'])) {
            $template = new ITILFollowupTemplate();
            $parent_item = new $input['itemtype']();
            if (
                !$template->getFromDB($input['_itilfollowuptemplates_id'])
                || !$parent_item->getFromDB($input['items_id'])
            ) {
                return false;
            }
            $input = array_replace(
                [
                    'content'         => Sanitizer::sanitize($template->getRenderedContent($parent_item)),
                    'is_private'      => $template->fields['is_private'],
                    'requesttypes_id' => $template->fields['requesttypes_id'],
                ],
                $input
            );
        }

        $input["_job"] = new $input['itemtype']();

        if (
            empty($input['content'])
            && !isset($input['add_close'])
            && !isset($input['add_reopen'])
        ) {
            Session::addMessageAfterRedirect(
                __("You can't add a followup without description"),
                false,
                ERROR
            );
            return false;
        }
        if (!$input["_job"]->getFromDB($input["items_id"])) {
            return false;
        }

        $input['_close'] = 0;

        if (!isset($input["users_id"])) {
            $input["users_id"] = 0;
            if ($uid = Session::getLoginUserID()) {
                $input["users_id"] = $uid;
            }
        }
       // if ($input["_isadmin"] && $input["_type"]!="update") {
        if (isset($input["add_close"])) {
            $input['_close'] = 1;
            $input['_no_reopen'] = 1;
            if (empty($input['content'])) {
                $input['content'] = __('Solution approved');
            }
        }

        unset($input["add_close"]);

        if (!isset($input["is_private"])) {
            $input['is_private'] = 0;
        }

        if (isset($input["add_reopen"])) {
            if ($input["content"] == '') {
                if (isset($input["_add"])) {
                    // Reopen using add form
                    Session::addMessageAfterRedirect(
                        __('If you want to reopen this item, you must specify a reason'),
                        false,
                        ERROR
                    );
                } else {
                   // Refuse solution
                    Session::addMessageAfterRedirect(
                        __('If you reject the solution, you must specify a reason'),
                        false,
                        ERROR
                    );
                }
                return false;
            }
            $input['_reopen'] = 1;
        }
        unset($input["add_reopen"]);
       // }

        $itemtype = $input['itemtype'];

       // Only calculate timeline_position if not already specified in the input
        if (!isset($input['timeline_position'])) {
            $input['timeline_position'] = $itemtype::getTimelinePosition($input["items_id"], $this->getType(), $input["users_id"]);
        }

        if (!isset($input['date'])) {
            $input["date"] = $_SESSION["glpi_currenttime"];
        }
        return $input;
    }


    public function prepareInputForUpdate($input)
    {
        if (!isset($this->fields['itemtype'])) {
            return false;
        }
        $input["_job"] = new $this->fields['itemtype']();
        if (!$input["_job"]->getFromDB($this->fields["items_id"])) {
            return false;
        }

       // update last editor if content change
        if (
            ($uid = Session::getLoginUserID())
            && isset($input['content']) && ($input['content'] != $this->fields['content'])
        ) {
            $input["users_id_editor"] = $uid;
        }

        return $input;
    }


    public function post_updateItem($history = 1)
    {
        global $CFG_GLPI;

        $job      = new $this->fields['itemtype']();

        if (!$job->getFromDB($this->fields['items_id'])) {
            return;
        }

        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, [
            'force_update' => true,
            'date' => $this->fields['date'],
        ]);

       //Get user_id when not logged (from mailgate)
        $uid = Session::getLoginUserID();
        if ($uid === false) {
            if (isset($this->fields['users_id_editor'])) {
                $uid = $this->fields['users_id_editor'];
            } else {
                $uid = $this->fields['users_id'];
            }
        }
        $job->updateDateMod($this->fields['items_id'], false, $uid);

        if (count($this->updates)) {
            if (
                !isset($this->input['_disablenotif'])
                && $CFG_GLPI["use_notifications"]
                && (in_array("content", $this->updates)
                 || isset($this->input['_need_send_mail']))
            ) {
                //FIXME: _need_send_mail does not seems to be used

                $options = ['followup_id' => $this->fields["id"],
                    'is_private'  => $this->fields['is_private']
                ];

                NotificationEvent::raiseEvent("update_followup", $job, $options);
            }
        }

        $this->input = PendingReason_Item::handleTimelineEdits($this);

       // change ITIL Object status (from splitted button)
        if (
            isset($this->input['_status'])
            && ($this->input['_status'] != $this->input['_job']->fields['status'])
        ) {
            $update = [
                'status'        => $this->input['_status'],
                'id'            => $this->input['_job']->fields['id'],
                '_disablenotif' => true,
            ];
            $this->input['_job']->update($update);
        }

       // Add log entry in the ITIL Object
        $changes = [
            0,
            '',
            $this->fields['id'],
        ];
        Log::history(
            $this->getField('items_id'),
            $this->fields['itemtype'],
            $changes,
            $this->getType(),
            Log::HISTORY_UPDATE_SUBITEM
        );

        parent::post_updateItem($history);
    }

    /**
     * Check if $this->item already contains the correct parent item and thus
     * help us to avoid reloading it for no reason
     *
     * @return bool
     */
    protected function isParentAlreadyLoaded(): bool
    {
        // If current item fields are not loaded, we can't know what its parent should be
        if (!isset($this->fields['id']) || empty($this->fields['id'])) {
            return false;
        }

        // Fail if no item are loaded un $this->item
        if ($this->item === null) {
            return false;
        }

        // Fail if loaded item's type doesn't match our expected parent itemtype
        if ($this->item->getType() !== $this->fields['itemtype']) {
            return false;
        }

        // Fail if loaded item's id is not what we expect
        if ($this->item->getID() !== $this->fields['items_id']) {
            return false;
        }

        return true;
    }

    public function post_getFromDB()
    {
        // Bandaid to avoid loading parent item if not needed
        // TODO: replace by proper lazy loading in GLPI 10.1
        if (!$this->isParentAlreadyLoaded()) {
            $this->item = new $this->fields['itemtype']();
            $this->item->getFromDB($this->fields['items_id']);
        }
    }


    protected function computeFriendlyName()
    {

        if (isset($this->fields['requesttypes_id'])) {
            if ($this->fields['requesttypes_id']) {
                return Dropdown::getDropdownName('glpi_requesttypes', $this->fields['requesttypes_id']);
            }
            return $this->getTypeName();
        }
        return '';
    }


    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'content',
            'name'               => __('Description'),
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => 'glpi_requesttypes',
            'field'              => 'name',
            'name'               => RequestType::getTypeName(1),
            'forcegroupby'       => true,
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'date',
            'name'               => _n('Date', 'Dates', 1),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'is_private',
            'name'               => __('Private'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => RequestType::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }


    public static function rawSearchOptionsToAdd($itemtype = null)
    {

        $tab = [];

        $tab[] = [
            'id'                 => 'followup',
            'name'               => _n('Followup', 'Followups', Session::getPluralNumber())
        ];

        $followup_condition = '';
        if (!Session::haveRight('followup', self::SEEPRIVATE)) {
            $followup_condition = [
                'OR' => [
                    'NEWTABLE.is_private'   => 0,
                    'NEWTABLE.users_id'     => Session::getLoginUserID()
                ]
            ];
        }

        $tab[] = [
            'id'                 => '25',
            'table'              => static::getTable(),
            'field'              => 'content',
            'name'               => __('Description'),
            'forcegroupby'       => true,
            'splititems'         => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
                'condition'          => $followup_condition
            ],
            'datatype'           => 'text',
            'htmltext'           => true
        ];

        $tab[] = [
            'id'                 => '36',
            'table'              => static::getTable(),
            'field'              => 'date',
            'name'               => _n('Date', 'Dates', 1),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
                'condition'          => $followup_condition
            ]
        ];

        $tab[] = [
            'id'                 => '27',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of followups'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
                'condition'          => $followup_condition
            ]
        ];

        $tab[] = [
            'id'                 => '29',
            'table'              => 'glpi_requesttypes',
            'field'              => 'name',
            'name'               => RequestType::getTypeName(1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => static::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => $followup_condition
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '91',
            'table'              => static::getTable(),
            'field'              => 'is_private',
            'name'               => __('Private followup'),
            'datatype'           => 'bool',
            'forcegroupby'       => true,
            'splititems'         => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
                'condition'          => $followup_condition
            ]
        ];

        $tab[] = [
            'id'                 => '93',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => __('Writer'),
            'datatype'           => 'itemlink',
            'right'              => 'all',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => static::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => $followup_condition
                    ]
                ]
            ]
        ];

        return $tab;
    }


    public static function getFormURL($full = true)
    {
        return Toolbox::getItemTypeFormURL("ITILFollowup", $full);
    }


    /** form for Followup
     *
     *@param $ID      integer : Id of the followup
     *@param $options array of possible options:
     *     - item Object : the ITILObject parent
     **/
    public function showForm($ID, array $options = [])
    {
        if ($this->isNewItem()) {
            $this->getEmpty();
        }

        TemplateRenderer::getInstance()->display('components/itilobject/timeline/form_followup.html.twig', [
            'item'               => $options['parent'],
            'subitem'            => $this,
            'has_pending_reason' => PendingReason_Item::getForItem($options['parent']) !== false,
        ]);

        return true;
    }


    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        unset($values[UPDATE], $values[CREATE], $values[READ]);

        if ($interface == 'central') {
            $values[self::UPDATEALL]      = __('Update all');
            $values[self::ADDALLTICKET]   = __('Add to all tickets');
            $values[self::SEEPRIVATE]     = __('See private ones');
        }

        $values[self::ADDGROUPTICKET]
                                 = ['short' => __('Add followup (associated groups)'),
                                     'long'  => __('Add a followup to tickets of associated groups')
                                 ];
        $values[self::UPDATEMY]    = __('Update followups (author)');
        $values[self::ADDMYTICKET] = ['short' => __('Add followup (requester)'),
            'long'  => __('Add a followup to tickets (requester)')
        ];
        $values[self::ADD_AS_OBSERVER] = ['short' => __('Add followup (watcher)'),
            'long'  => __('Add a followup to tickets (watcher)')
        ];
        $values[self::SEEPUBLIC]   = __('See public ones');

        if ($interface == 'helpdesk') {
            unset($values[PURGE]);
        }

        return $values;
    }

    public static function showMassiveActionAddFollowupForm()
    {
        echo "<table class='tab_cadre_fixe'>";
        echo '<tr><th colspan=4>' . __('Add a new followup') . '</th></tr>';

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Source of followup') . "</td>";
        echo "<td>";
        RequestType::dropdown(
            [
                'value' => RequestType::getDefault('followup'),
                'condition' => ['is_active' => 1, 'is_itilfollowup' => 1]
            ]
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Description') . "</td>";
        echo "<td><textarea name='content' cols='50' rows='6'></textarea></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center' colspan='2'>";
        echo "<input type='hidden' name='is_private' value='" . $_SESSION['glpifollowup_private'] . "'>";
        echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
        echo "</td>";
        echo "</tr>";

        echo "</table>";
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'add_followup':
                static::showMassiveActionAddFollowupForm();
                return true;
        }

        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'add_followup':
                $input = $ma->getInput();
                $fup   = new self();
                foreach ($ids as $id) {
                    if ($item->getFromDB($id)) {
                        if (in_array($item->fields['status'], array_merge($item->getSolvedStatusArray(), $item->getClosedStatusArray()))) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        } else {
                            $input2 = [
                                'items_id'        => $id,
                                'itemtype'        => $item->getType(),
                                'is_private'      => $input['is_private'],
                                'requesttypes_id' => $input['requesttypes_id'],
                                'content'         => $input['content']
                            ];
                            if ($fup->can(-1, CREATE, $input2)) {
                                if ($fup->add($input2)) {
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
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                    }
                }
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    /**
     * Build parent condition for ITILFollowup, used in addDefaultWhere
     *
     * @param string $itemtype
     * @param string $target
     * @param string $user_table
     * @param string $group_table keys
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function buildParentCondition(
        $itemtype,
        $target = "",
        $user_table = "",
        $group_table = ""
    ) {
        $itilfup_table = static::getTable();

       // An ITILFollowup parent can only by a CommonItilObject
        if (!is_a($itemtype, "CommonITILObject", true)) {
            throw new \InvalidArgumentException(
                "'$itemtype' is not a CommonITILObject"
            );
        }

        $rightname = $itemtype::$rightname;
       // Can see all items, no need to go further
        if (Session::haveRight($rightname, $itemtype::READALL)) {
            return "(`$itilfup_table`.`itemtype` = '$itemtype') ";
        }

        $user   = Session::getLoginUserID();
        $groups = "'" . implode("','", $_SESSION['glpigroups']) . "'";
        $table = getTableNameForForeignKeyField(
            getForeignKeyFieldForItemType($itemtype)
        );

       // Avoid empty IN ()
        if ($groups == "''") {
            $groups = '-1';
        }

       // We need to do some specific checks for tickets
        if ($itemtype == "Ticket") {
           // Default condition
            $condition = "(`itemtype` = '$itemtype' AND (0 = 1 ";
            return $condition . Ticket::buildCanViewCondition("items_id") . ")) ";
        } else {
            if (Session::haveRight($rightname, $itemtype::READMY)) {
               // Subquery for affected/assigned/observer user
                $user_query = "SELECT `$target`
               FROM `$user_table`
               WHERE `users_id` = '$user'";

               // Subquery for affected/assigned/observer group
                $group_query = "SELECT `$target`
               FROM `$group_table`
               WHERE `groups_id` IN ($groups)";

               // Subquery for recipient
                $recipient_query = "SELECT `id`
               FROM `$table`
               WHERE `users_id_recipient` = '$user'";

                return "(
               `$itilfup_table`.`itemtype` = '$itemtype' AND (
                  `$itilfup_table`.`items_id` IN ($user_query) OR
                  `$itilfup_table`.`items_id` IN ($group_query) OR
                  `$itilfup_table`.`items_id` IN ($recipient_query)
               )
            ) ";
            } else {
               // Can't see any items
                return "(`$itilfup_table`.`itemtype` = '$itemtype' AND 0 = 1) ";
            }
        }
    }

    public static function getNameField()
    {
        return 'id';
    }

    /**
     * Check if this item author is a support agent
     *
     * @return bool
     */
    public function isFromSupportAgent()
    {
        global $DB;

       // Get parent item
        $commonITILObject = new $this->fields['itemtype']();
        $commonITILObject->getFromDB($this->fields['items_id']);

        $actors = $commonITILObject->getITILActors();
        $user_id = $this->fields['users_id'];
        $roles = $actors[$user_id] ?? [];

        if (in_array(CommonITILActor::ASSIGN, $roles)) {
           // The author is assigned -> support agent
            return true;
        } else if (in_array(CommonITILActor::OBSERVER, $roles)) {
           // The author is an observer or a requester -> can be support agent OR
           // requester depending on how GLPI is used so we must check the user's
           // profiles
            $central_profiles = $DB->request([
                'COUNT' => 'total',
                'FROM' => Profile::getTable(),
                'WHERE' => [
                    'interface' => 'central',
                    'id' => new QuerySubQuery([
                        'SELECT' => ['profiles_id'],
                        'FROM' => Profile_User::getTable(),
                        'WHERE' => [
                            'users_id' => $user_id
                        ]
                    ])
                ]
            ]);

           // No profiles, let's assume it is a support agent to be safe
            if (!count($central_profiles)) {
                return false;
            }

            return $central_profiles->current()['total'] > 0;
        } else if (in_array(CommonITILActor::REQUESTER, $roles)) {
           // The author is a requester -> not from support agent
            return false;
        } else {
           // The author is not an actor of the ticket -> he was most likely a
           // support agent that is no longer assigned to the ticket
            return true;
        }
    }

    /**
     * Allow to set the parent item
     * Some subclasses will load their parent item in their `post_getFromDB` function
     * If the parent is already loaded, it might be useful to set it with this method
     * before loading the item, thus avoiding one useless DB query (or many more queries
     * when looping on children items)
     *
     * TODO 10.1 move method and `item` property into parent class
     *
     * @param CommonITILObject Parent item
     *
     * @return void
     */
    final public function setParentItem(CommonITILObject $parent): void
    {
        $this->item = $parent;
    }
}

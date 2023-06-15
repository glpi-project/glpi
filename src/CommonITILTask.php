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
use Glpi\CalDAV\Contracts\CalDAVCompatibleItemInterface;
use Glpi\CalDAV\Traits\VobjectConverterTrait;
use Glpi\RichText\RichText;
use Glpi\Toolbox\Sanitizer;
use Sabre\VObject\Component\VCalendar;

/// TODO extends it from CommonDBChild
abstract class CommonITILTask extends CommonDBTM implements CalDAVCompatibleItemInterface
{
    use Glpi\Features\ParentStatus;
    use Glpi\Features\PlanningEvent;
    use VobjectConverterTrait;

   // From CommonDBTM
    public $auto_message_on_action = false;

    const SEEPUBLIC       =    1;
    const UPDATEMY        =    2;
    const UPDATEALL       = 1024;
   //   const NOTUSED      = 2048;
    const ADDALLITEM      = 4096;
    const SEEPRIVATE      = 8192;



    public function getItilObjectItemType()
    {
        return str_replace('Task', '', $this->getType());
    }

    public static function getNameField()
    {
        return 'id';
    }

    public static function getIcon()
    {
        return 'ti ti-checkbox';
    }

    public function canViewPrivates()
    {
        return false;
    }


    public function canEditAll()
    {
        return false;
    }


    /**
     * Get the item associated with the current object.
     *
     * @since 0.84
     *
     * @return object of the concerned item or false on error
     **/
    public function getItem()
    {

        if ($item = getItemForItemtype($this->getItilObjectItemType())) {
            if ($item->getFromDB($this->fields[$item->getForeignKeyField()])) {
                return $item;
            }
        }
        return false;
    }


    /**
     * can read the parent ITIL Object ?
     *
     * @return boolean
     **/
    public function canReadITILItem()
    {

        $itemtype = $this->getItilObjectItemType();
        $item     = new $itemtype();
        if (!$item->can($this->getField($item->getForeignKeyField()), READ)) {
            return false;
        }
        return true;
    }


    /**
     * can update the parent ITIL Object ?
     *
     * @since 0.85
     *
     * @return boolean
     **/
    public function canUpdateITILItem()
    {

        $itemtype = $this->getItilObjectItemType();
        $item     = new $itemtype();
        if (!$item->can($this->getField($item->getForeignKeyField()), UPDATE)) {
            return false;
        }
        return true;
    }


    /**
     * Name of the type
     *
     * @param $nb : number of item in the type (default 0)
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('Task', 'Tasks', $nb);
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $values
     * @param $options   array
     **/
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'state':
                return Planning::getState($values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $name            (default '')
     * @param $values          (default '')
     * @param $options   array
     *
     * @return string
     **/
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'state':
                return Planning::dropdownState($name, $values[$field], false);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (
            ($item->getType() == $this->getItilObjectItemType())
            && $this->canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $restrict = [$item->getForeignKeyField() => $item->getID()];

                if (
                    $this->maybePrivate()
                    && !$this->canViewPrivates()
                ) {
                    $restrict['OR'] = [
                        'is_private'   => 0,
                        'users_id'     => Session::getLoginUserID()
                    ];
                }
                $nb = countElementsInTable($this->getTable(), $restrict);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    public function post_deleteFromDB()
    {
        global $CFG_GLPI;

        $itemtype = $this->getItilObjectItemType();
        $item     = new $itemtype();
        $item->getFromDB($this->fields[$item->getForeignKeyField()]);
        $item->updateActiontime($this->fields[$item->getForeignKeyField()]);
        $item->updateDateMod($this->fields[$item->getForeignKeyField()]);

       // Add log entry in the ITIL object
        $changes = [
            0,
            '',
            $this->fields['id'],
        ];
        Log::history(
            $this->getField($item->getForeignKeyField()),
            $this->getItilObjectItemType(),
            $changes,
            $this->getType(),
            Log::HISTORY_DELETE_SUBITEM
        );

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            $options = ['task_id'             => $this->fields["id"],
                           // Force is_private with data / not available
                'is_private'          => $this->isPrivate(),
                          // Pass users values
                'task_users_id'       => $this->fields['users_id'],
                'task_users_id_tech'  => $this->fields['users_id_tech'],
                'task_groups_id_tech' => $this->fields['groups_id_tech']
            ];
            NotificationEvent::raiseEvent('delete_task', $item, $options);
        }
    }


    public function prepareInputForUpdate($input)
    {

        if (array_key_exists('content', $input) && empty($input['content'])) {
            Session::addMessageAfterRedirect(
                __("You can't remove description of a task."),
                false,
                ERROR
            );
            return false;
        }

        Toolbox::manageBeginAndEndPlanDates($input['plan']);

        if (isset($input['_planningrecall'])) {
            PlanningRecall::manageDatas($input['_planningrecall']);
        }

       // update last editor if content change
        if (
            isset($input['_update'])
            && ($uid = Session::getLoginUserID())
        ) { // Change from task form
            $input["users_id_editor"] = $uid;
        }

        $itemtype      = $this->getItilObjectItemType();
        $input["_job"] = new $itemtype();

        if (
            isset($input[$input["_job"]->getForeignKeyField()])
            && !$input["_job"]->getFromDB($input[$input["_job"]->getForeignKeyField()])
        ) {
            return false;
        }

        if (isset($input["plan"])) {
            $input["begin"]         = $input['plan']["begin"];
            $input["end"]           = $input['plan']["end"];

            $timestart              = strtotime($input["begin"]);
            $timeend                = strtotime($input["end"]);
            $input["actiontime"]    = $timeend - $timestart;

            unset($input["plan"]);

            if (!$this->test_valid_date($input)) {
                Session::addMessageAfterRedirect(
                    __('Error in entering dates. The starting date is later than the ending date'),
                    false,
                    ERROR
                );
                return false;
            }
            Planning::checkAlreadyPlanned(
                $input["users_id_tech"],
                $input["begin"],
                $input["end"],
                [$this->getType() => [$input["id"]]]
            );

            $calendars_id = Entity::getUsedConfig(
                'calendars_strategy',
                $input["_job"]->fields['entities_id'],
                'calendars_id',
                0
            );
            $calendar     = new Calendar();

           // Using calendar
            if (
                ($calendars_id > 0)
                && $calendar->getFromDB($calendars_id)
            ) {
                if (!$calendar->isAWorkingHour(strtotime($input["begin"]))) {
                    Session::addMessageAfterRedirect(
                        __('Start of the selected timeframe is not a working hour.'),
                        false,
                        ERROR
                    );
                }
                if (!$calendar->isAWorkingHour(strtotime($input["end"]))) {
                    Session::addMessageAfterRedirect(
                        __('End of the selected timeframe is not a working hour.'),
                        false,
                        ERROR
                    );
                }
            }
        }

        return $input;
    }


    public function post_updateItem($history = 1)
    {
        global $CFG_GLPI;

        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, ['force_update' => true]);

        if (in_array("begin", $this->updates)) {
            PlanningRecall::managePlanningUpdates(
                $this->getType(),
                $this->getID(),
                $this->fields["begin"]
            );
        }

        if (isset($this->input['_planningrecall'])) {
            $this->input['_planningrecall']['items_id'] = $this->fields['id'];
            PlanningRecall::manageDatas($this->input['_planningrecall']);
        }

        $update_done = false;
        $itemtype    = $this->getItilObjectItemType();
        $item        = new $itemtype();

        $this->input = PendingReason_Item::handleTimelineEdits($this);

        if ($item->getFromDB($this->fields[$item->getForeignKeyField()])) {
            $item->updateDateMod($this->fields[$item->getForeignKeyField()]);

            $proceed = count($this->updates);

           //Also check if item status has changed
            if (!$proceed) {
                if (
                    isset($this->input['_status'])
                    && $this->input['_status'] != $item->getField('status')
                ) {
                    $proceed = true;
                }
            }

            if ($proceed) {
                $update_done = true;

                if (in_array("actiontime", $this->updates)) {
                    $item->updateActionTime($this->fields[$item->getForeignKeyField()]);
                }

               // change ticket status (from splitted button)
                $itemtype = $this->getItilObjectItemType();
                $this->input['_job'] = new $itemtype();
                if (!$this->input['_job']->getFromDB($this->fields[$this->input['_job']->getForeignKeyField()])) {
                    return false;
                }
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

                if (
                    !empty($this->fields['begin'])
                    && $item->isStatusExists(CommonITILObject::PLANNED)
                    && (($item->fields["status"] == CommonITILObject::INCOMING)
                     || ($item->fields["status"] == CommonITILObject::ASSIGNED))
                ) {
                    $input2 = [
                        'id'            => $item->getID(),
                        'status'        => CommonITILObject::PLANNED,
                        '_disablenotif' => true,
                    ];
                    $item->update($input2);
                }

                if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
                    $options = ['task_id'    => $this->fields["id"],
                        'is_private' => $this->isPrivate()
                    ];
                    NotificationEvent::raiseEvent('update_task', $item, $options);
                }
            }
        }

        if ($update_done) {
           // Add log entry in the ITIL object
            $changes = [
                0,
                '',
                $this->fields['id'],
            ];
            Log::history(
                $this->getField($item->getForeignKeyField()),
                $itemtype,
                $changes,
                $this->getType(),
                Log::HISTORY_UPDATE_SUBITEM
            );
        }

        parent::post_updateItem($history);
    }


    public function prepareInputForAdd($input)
    {

        $itemtype = $this->getItilObjectItemType();

        // Handle template
        if (isset($input['_tasktemplates_id'])) {
            $template = new TaskTemplate();
            $parent_item = new $itemtype();
            if (
                !$template->getFromDB($input['_tasktemplates_id'])
                || !$parent_item->getFromDB($input[$parent_item->getForeignKeyField()])
            ) {
                return false;
            }
            $input['tasktemplates_id']  = $input['_tasktemplates_id'];
            $input = array_replace(
                [
                    'content'           => Sanitizer::sanitize($template->getRenderedContent($parent_item)),
                    'taskcategories_id' => $template->fields['taskcategories_id'],
                    'actiontime'        => $template->fields['actiontime'],
                    'state'             => $template->fields['state'],
                    'is_private'        => $template->fields['is_private'],
                    'users_id_tech'     => $template->fields['users_id_tech'],
                    'groups_id_tech'    => $template->fields['groups_id_tech'],
                ],
                $input
            );
        }

        if (empty($input['content'])) {
            Session::addMessageAfterRedirect(
                __("You can't add a task without description."),
                false,
                ERROR
            );
            return false;
        }

        if (!isset($input['uuid'])) {
            $input['uuid'] = \Ramsey\Uuid\Uuid::uuid4();
        }

        Toolbox::manageBeginAndEndPlanDates($input['plan']);

        if (isset($input["plan"])) {
            $input["begin"]         = $input['plan']["begin"];
            $input["end"]           = $input['plan']["end"];

            $timestart              = strtotime($input["begin"]);
            $timeend                = strtotime($input["end"]);
            $input["actiontime"]    = $timeend - $timestart;

            unset($input["plan"]);
            if (!$this->test_valid_date($input)) {
                Session::addMessageAfterRedirect(
                    __('Error in entering dates. The starting date is later than the ending date'),
                    false,
                    ERROR
                );
                return false;
            }
        }

        $input["_job"] = new $itemtype();
        if (!$input["_job"]->getFromDB($input[$input["_job"]->getForeignKeyField()])) {
            return false;
        }

       // Pass old assign From object in case of assign change
        if (isset($input["_old_assign"])) {
            $input["_job"]->fields["_old_assign"] = $input["_old_assign"];
        }

        if (
            !isset($input["users_id"])
            && ($uid = Session::getLoginUserID())
        ) {
            $input["users_id"] = $uid;
        }

        if (!isset($input["date"]) || empty($input["date"])) {
            $input["date"] = $_SESSION["glpi_currenttime"];
        }
        if (!isset($input["is_private"])) {
            $input['is_private'] = 0;
        }

        $input['timeline_position'] = CommonITILObject::TIMELINE_LEFT;
        if (isset($input["users_id"])) {
            $input['timeline_position'] = $itemtype::getTimelinePosition($input["_job"]->getID(), $this->getType(), $input["users_id"]);
        }

        return $input;
    }


    public function post_addItem()
    {
        global $CFG_GLPI;

        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, ['force_update' => true]);

        if (isset($this->input['_planningrecall'])) {
            $this->input['_planningrecall']['items_id'] = $this->fields['id'];
            PlanningRecall::manageDatas($this->input['_planningrecall']);
        }

        $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

        if (isset($this->fields["begin"]) && !empty($this->fields["begin"])) {
            Planning::checkAlreadyPlanned(
                $this->fields["users_id_tech"],
                $this->fields["begin"],
                $this->fields["end"],
                [$this->getType() => [$this->fields["id"]]]
            );

            $calendars_id = Entity::getUsedConfig(
                'calendars_strategy',
                $this->input["_job"]->fields['entities_id'],
                'calendars_id',
                0
            );
            $calendar     = new Calendar();

           // Using calendar
            if (
                ($calendars_id > 0)
                && $calendar->getFromDB($calendars_id)
            ) {
                if (!$calendar->isAWorkingHour(strtotime($this->fields["begin"]))) {
                    Session::addMessageAfterRedirect(
                        __('Start of the selected timeframe is not a working hour.'),
                        false,
                        ERROR
                    );
                }
                if (!$calendar->isAWorkingHour(strtotime($this->fields["end"]))) {
                    Session::addMessageAfterRedirect(
                        __('End of the selected timeframe is not a working hour.'),
                        false,
                        ERROR
                    );
                }
            }
        }

        $this->input["_job"]->updateDateMod($this->input[$this->input["_job"]->getForeignKeyField()]);

        if (isset($this->input["actiontime"]) && ($this->input["actiontime"] > 0)) {
            $this->input["_job"]->updateActionTime($this->input[$this->input["_job"]->getForeignKeyField()]);
        }

        $this->updateParentStatus($this->input['_job'], $this->input);

        if ($donotif) {
            $options = ['task_id'             => $this->fields["id"],
                'is_private'          => $this->isPrivate()
            ];
            NotificationEvent::raiseEvent('add_task', $this->input["_job"], $options);
        }

       // Add log entry in the ITIL object
        $changes = [
            0,
            '',
            $this->fields['id'],
        ];
        Log::history(
            $this->getField($this->input["_job"]->getForeignKeyField()),
            $this->input["_job"]->getTYpe(),
            $changes,
            $this->getType(),
            Log::HISTORY_ADD_SUBITEM
        );

        parent::post_addItem();
    }


    public function post_getEmpty()
    {

        if (
            $this->maybePrivate()
            && isset($_SESSION['glpitask_private']) && $_SESSION['glpitask_private']
        ) {
            $this->fields['is_private'] = 1;
        }
       // Default is todo
        $this->fields['state'] = Planning::TODO;
        if (isset($_SESSION['glpitask_state'])) {
            $this->fields['state'] = $_SESSION['glpitask_state'];
        }
    }


    /**
     * @see CommonDBTM::cleanDBonPurge()
     *
     * @since 0.84
     **/
    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                PlanningRecall::class,
            ]
        );
    }


   // SPECIFIC FUNCTIONS
    protected function computeFriendlyName()
    {

        if (isset($this->fields['taskcategories_id'])) {
            if ($this->fields['taskcategories_id']) {
                return Dropdown::getDropdownName(
                    'glpi_taskcategories',
                    $this->fields['taskcategories_id']
                );
            }
            return $this->getTypeName(1);
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
            'table'              => 'glpi_taskcategories',
            'field'              => 'name',
            'name'               => _n('Task category', 'Task categories', 1),
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

        if ($this->maybePrivate()) {
            $tab[] = [
                'id'                 => '4',
                'table'              => $this->getTable(),
                'field'              => 'is_private',
                'name'               => __('Public followup'),
                'datatype'           => 'bool'
            ];
        }

        $tab[] = [
            'id'                 => '5',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => __('Technician'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'actiontime',
            'name'               => __('Total duration'),
            'datatype'           => 'actiontime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'state',
            'name'               => __('Status'),
            'datatype'           => 'specific'
        ];

        return $tab;
    }


    /**
     * @since 0.85
     **/
    public static function rawSearchOptionsToAdd($itemtype = null)
    {

        $task = new static();
        $tab = [];
        $name = _n('Task', 'Tasks', Session::getPluralNumber());

        $task_condition = '';
        if ($task->maybePrivate() && !Session::haveRight("task", CommonITILTask::SEEPRIVATE)) {
            $task_condition = [
                'OR' => [
                    'NEWTABLE.is_private'   => 0,
                    'NEWTABLE.users_id'     => Session::getLoginUserID()
                ]
            ];
        }

        $tab[] = [
            'id'                 => 'task',
            'name'               => $name
        ];

        $tab[] = [
            'id'                 => '26',
            'table'              => static::getTable(),
            'field'              => 'content',
            'name'               => __('Description'),
            'datatype'           => 'text',
            'forcegroupby'       => true,
            'splititems'         => true,
            'massiveaction'      => false,
            'htmltext'           => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => $task_condition,
            ]
        ];

        $tab[] = [
            'id'                 => '28',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of tasks'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => $task_condition,
            ]
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => 'glpi_taskcategories',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => _n('Category', 'Categories', 1),
            'forcegroupby'       => true,
            'splititems'         => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => static::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => $task_condition,
                    ]
                ]
            ]
        ];

        if ($task->maybePrivate()) {
            $tab[] = [
                'id'                 => '92',
                'table'              => static::getTable(),
                'field'              => 'is_private',
                'name'               => __('Private task'),
                'datatype'           => 'bool',
                'forcegroupby'       => true,
                'splititems'         => true,
                'massiveaction'      => false,
                'joinparams'         => [
                    'jointype'           => 'child',
                    'condition'          => $task_condition,
                ]
            ];
        }

        $tab[] = [
            'id'                 => '94',
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
                        'jointype'           => 'child',
                        'condition'          => $task_condition,
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '95',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
            'datatype'           => 'itemlink',
            'right'              => 'own_ticket',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => static::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => $task_condition,
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '112',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id_tech',
            'name'               => __('Group in charge'),
            'datatype'           => 'itemlink',
            'condition'          => ['is_task' => 1],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => static::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => $task_condition,
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '96',
            'table'              => static::getTable(),
            'field'              => 'actiontime',
            'name'               => __('Duration'),
            'datatype'           => 'timestamp',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => $task_condition,
            ]
        ];

        $tab[] = [
            'id'                 => '97',
            'table'              => static::getTable(),
            'field'              => 'date',
            'name'               => _n('Date', 'Dates', 1),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => $task_condition,
            ]
        ];

        $tab[] = [
            'id'                 => '33',
            'table'              => static::getTable(),
            'field'              => 'state',
            'name'               => __('Status'),
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
            'searchequalsonfield' => true,
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => $task_condition,
            ]
        ];

        $tab[] = [
            'id'                 => '173',
            'table'              => static::getTable(),
            'field'              => 'begin',
            'name'               => __('Begin date'),
            'datatype'           => 'datetime',
            'maybefuture'        => true,
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => $task_condition,
            ]
        ];

        $tab[] = [
            'id'                 => '174',
            'table'              => static::getTable(),
            'field'              => 'end',
            'name'               => __('End date'),
            'datatype'           => 'datetime',
            'maybefuture'        => true,
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => $task_condition,
            ]
        ];

        $tab[] = [
            'id'                 => '175',
            'table'              => TaskTemplate::getTable(),
            'field'              => 'name',
            'linkfield'          => 'tasktemplates_id',
            'name'               => TaskTemplate::getTypeName(1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => static::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => $task_condition,
                    ]
                ]
            ]
        ];

        return $tab;
    }


    /**
     * Current dates are valid ? begin before end
     *
     * @param $input
     *
     *@return boolean
     **/
    public function test_valid_date($input)
    {

        return (!empty($input["begin"])
              && !empty($input["end"])
              && (strtotime($input["begin"]) < strtotime($input["end"])));
    }


    /**
     * Populate the planning with planned tasks
     *
     * @param string $itemtype itemtype
     * @param array $options   options must contains :
     *    - who                ID of the user (0 = undefined)
     *    - whogroup           ID of the group of users (0 = undefined)
     *    - begin              Date
     *    - end                Date
     *    - color
     *    - event_type_color
     *    - display_done_events (boolean)
     *
     * @return array of planning item
     **/
    public static function genericPopulatePlanning($itemtype, $options = [])
    {
        global $DB, $CFG_GLPI;

        $interv = [];

        if (
            !isset($options['begin']) || ($options['begin'] == 'NULL')
            || !isset($options['end']) || ($options['end'] == 'NULL')
        ) {
            return $interv;
        }

        if (!$item = getItemForItemtype($itemtype)) {
            return;
        }
        $parentitemtype = $item->getItilObjectItemType();
        if (!$parentitem = getItemForItemtype($parentitemtype)) {
            return;
        }

        $default_options = [
            'genical'             => false,
            'color'               => '',
            'event_type_color'    => '',
            'display_done_events' => true,
        ];
        $options = array_merge($default_options, $options);

        $who      = $options['who'];
        $whogroup = $options['whogroup']; // direct group
        $begin    = $options['begin'];
        $end      = $options['end'];

        $SELECT = [$item->getTable() . '.*'];

       // Get items to print
        if (isset($options['not_planned'])) {
           //not planned case
           // as we consider that people often create tasks after their execution
           // begin date is task date minus duration
           // and end date is task date
            $bdate = "DATE_SUB(" . $DB->quoteName($item->getTable() . '.date') .
            ", INTERVAL " . $DB->quoteName($item->getTable() . '.actiontime') . " SECOND)";
            $SELECT[] = new QueryExpression($bdate . ' AS ' . $DB->quoteName('notp_date'));
            $edate = $DB->quoteName($item->getTable() . '.date');
            $SELECT[] = new QueryExpression($edate . ' AS ' . $DB->quoteName('notp_edate'));
            $WHERE = [
                $item->getTable() . '.end'     => null,
                $item->getTable() . '.begin'   => null,
                $item->getTable() . '.actiontime' => ['>', 0],
            //begin is replaced with creation tim minus duration
                new QueryExpression($edate . " >= '" . $begin . "'"),
                new QueryExpression($bdate . " <= '" . $end . "'")
            ];
        } else {
           //std case: get tasks for current view dates
            $WHERE = [
                $item->getTable() . '.end'     => ['>=', $begin],
                $item->getTable() . '.begin'   => ['<=', $end]
            ];
        }
        $ADDWHERE = [];

        if ($whogroup === "mine") {
            if (isset($_SESSION['glpigroups'])) {
                $whogroup = $_SESSION['glpigroups'];
            } else if ($who > 0) {
                $whogroup = array_column(Group_User::getUserGroups($who), 'id');
            }
        }

        if ($who > 0) {
            $ADDWHERE[$item->getTable() . '.users_id_tech'] = $who;
        }

       //This means we can pass 2 groups here, not sure this is expected. Not documented :/
        if ($whogroup > 0) {
            $ADDWHERE[$item->getTable() . '.groups_id_tech'] = $whogroup;
        }

        if (!count($ADDWHERE)) {
            $ADDWHERE = [
                $item->getTable() . '.users_id_tech' => new \QuerySubQuery([
                    'SELECT'          => 'glpi_profiles_users.users_id',
                    'DISTINCT'        => true,
                    'FROM'            => 'glpi_profiles',
                    'LEFT JOIN'       => [
                        'glpi_profiles_users'   => [
                            'ON' => [
                                'glpi_profiles_users' => 'profiles_id',
                                'glpi_profiles'       => 'id'
                            ]
                        ]
                    ],
                    'WHERE'           => [
                        'glpi_profiles.interface'  => 'central'
                    ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $_SESSION['glpiactive_entity'], 1)
                ])
            ];
        }

        if (count($ADDWHERE) > 0) {
            $WHERE[] = ['OR' => $ADDWHERE];
        }

        if (!$options['display_done_events']) {
            $WHERE[] = ['OR' => [
                $item->getTable() . ".state"  => Planning::TODO,
                [
                    'AND' => [
                        $item->getTable() . '.state'  => Planning::INFO,
                        $item->getTable() . '.end'    => ['>', new \QueryExpression('NOW()')]
                    ]
                ]
            ]
            ];
        }

        if ($parentitem->maybeDeleted()) {
            $WHERE[$parentitem->getTable() . '.is_deleted'] = 0;
        }

        if (!$options['display_done_events']) {
            $WHERE[] = ['NOT' => [
                $parentitem->getTable() . '.status' => array_merge(
                    $parentitem->getSolvedStatusArray(),
                    $parentitem->getClosedStatusArray()
                )
            ]
            ];
        }

        $iterator = $DB->request([
            'SELECT'       => $SELECT,
            'FROM'         => $item->getTable(),
            'INNER JOIN'   => [
                $parentitem->getTable() => [
                    'ON' => [
                        $parentitem->getTable() => 'id',
                        $item->getTable()       => $parentitem->getForeignKeyField()
                    ]
                ]
            ],
            'WHERE'        => $WHERE,
            'ORDERBY'      => $item->getTable() . '.begin'
        ]);

        $interv = [];

        if (count($iterator)) {
            foreach ($iterator as $data) {
                if (
                    $item->getFromDB($data["id"])
                    && $item->canViewItem()
                ) {
                    if ($parentitem->getFromDBwithData($item->fields[$parentitem->getForeignKeyField()])) {
                      //not planned
                        if (isset($data['notp_date'])) {
                              $data['begin'] = $data['notp_date'];
                              $data['end'] = $data['notp_edate'];
                        }
                        $key = $data["begin"] .
                         "$$$" . $itemtype .
                         "$$$" . $data["id"] .
                         "$$$" . $who . "$$$" . $whogroup;

                        if (isset($options['from_group_users'])) {
                             $key .= "_gu";
                        }

                        $interv[$key]['color']            = $options['color'];
                        $interv[$key]['event_type_color'] = $options['event_type_color'];
                        $interv[$key]['itemtype']         = $itemtype;
                        $url_id = $item->fields[$parentitem->getForeignKeyField()];
                        if (!$options['genical']) {
                            $interv[$key]["url"] = $parentitemtype::getFormURLWithID($url_id);
                        } else {
                            $interv[$key]["url"] = $CFG_GLPI["url_base"] .
                                            $parentitemtype::getFormURLWithID($url_id, false);
                        }
                        $interv[$key]["ajaxurl"] = $CFG_GLPI["root_doc"] . "/ajax/planning.php" .
                                             "?action=edit_event_form" .
                                             "&itemtype=" . $itemtype .
                                             "&parentitemtype=" . $parentitemtype .
                                             "&parentid=" . $item->fields[$parentitem->getForeignKeyField()] .
                                             "&id=" . $data['id'] .
                                             "&url=" . $interv[$key]["url"];

                        $interv[$key][$item->getForeignKeyField()] = $data["id"];
                        $interv[$key]["id"]                        = $data["id"];
                        if (isset($data["state"])) {
                            $interv[$key]["state"]                  = $data["state"];
                        }
                        $interv[$key][$parentitem->getForeignKeyField()]
                                                  = $item->fields[$parentitem->getForeignKeyField()];
                        $interv[$key]["users_id"]       = $data["users_id"];
                        $interv[$key]["users_id_tech"]  = $data["users_id_tech"];
                        $interv[$key]["groups_id_tech"]  = $data["groups_id_tech"];

                        if (strcmp($begin, $data["begin"]) > 0) {
                            $interv[$key]["begin"] = $begin;
                        } else {
                            $interv[$key]["begin"] = $data["begin"];
                        }

                        if (strcmp($end, $data["end"]) < 0) {
                            $interv[$key]["end"] = $end;
                        } else {
                            $interv[$key]["end"] = $data["end"];
                        }

                        $interv[$key]["name"]     = Sanitizer::unsanitize($parentitem->fields['name']); // name is re-encoded on JS side
                        $interv[$key]["content"]  = RichText::getSafeHtml($item->fields['content']);
                        $interv[$key]["status"]   = $parentitem->fields["status"];
                        $interv[$key]["priority"] = $parentitem->fields["priority"];

                        $interv[$key]["editable"] = $item->canUpdateITILItem();

                      /// Specific for tickets
                        $interv[$key]["device"] = [];
                        if (isset($parentitem->hardwaredatas) && !empty($parentitem->hardwaredatas)) {
                            foreach ($parentitem->hardwaredatas as $hardwaredata) {
                                $interv[$key]["device"][$hardwaredata->fields['id']] = ($hardwaredata
                                                   ? $hardwaredata->getName() : '');
                            }
                            if (is_array($interv[$key]["device"])) {
                                $interv[$key]["device"] = implode("<br>", $interv[$key]["device"]);
                            }
                        }
                    }
                }
            }
        }
        return $interv;
    }

    /**
     * Populate the planning with not planned tasks
     *
     * @param string $itemtype itemtype
     * @param array $options   options must contains :
     *    - who                ID of the user (0 = undefined)
     *    - whogroup           ID of the group of users (0 = undefined)
     *    - begin              Date
     *    - end                Date
     *    - color
     *    - event_type_color
     *    - display_done_events (boolean)
     *
     * @return array of planning item
     **/
    public static function genericPopulateNotPlanned($itemtype, $options = [])
    {
        $options['not_planned'] = true;
        return self::genericPopulatePlanning($itemtype, $options);
    }

    /**
     * Display a Planning Item
     *
     * @param string          $itemtype  itemtype
     * @param array           $val       the item to display
     * @param integer         $who       ID of the user (0 if all)
     * @param string          $type      position of the item in the time block (in, through, begin or end)
     * @param integer|boolean $complete  complete display (more details) (default 0)
     *
     * @return string Output
     **/
    public static function genericDisplayPlanningItem($itemtype, array $val, $who, $type = "", $complete = 0)
    {
        global $CFG_GLPI;

        $html = "";
        $rand      = mt_rand();
        $styleText = "";
        if (isset($val["state"])) {
            switch ($val["state"]) {
                case 2: // Done
                    $styleText = "color:#747474;";
                    break;
            }
        }

        $parenttype = str_replace('Task', '', $itemtype);
        if ($parent = getItemForItemtype($parenttype)) {
            $parenttype_fk = $parent->getForeignKeyField();
        } else {
            return;
        }

        $html .= "<img src='" . $CFG_GLPI["root_doc"] . "/pics/rdv_interv.png' alt='' title=\"" .
             Html::entities_deep($parent->getTypeName(1)) . "\">&nbsp;&nbsp;";
        $html .= $parent->getStatusIcon($val['status']);
        $html .= "&nbsp;<a id='content_tracking_" . $val["id"] . $rand . "'
                   href='" . $parenttype::getFormURLWithID($val[$parenttype_fk]) . "'
                   style='$styleText'>";

        if (!empty($val["device"])) {
            $html .= "<br>" . $val["device"];
        }

        if ($who <= 0) { // show tech for "show all and show group"
            $html .= "<br>";
           //TRANS: %s is user name
            $html .= sprintf(__('By %s'), getUserName($val["users_id_tech"]));
        }

        $html .= "</a>";

        $recall = '';
        if (
            isset($val[getForeignKeyFieldForItemType($itemtype)])
            && PlanningRecall::isAvailable()
        ) {
            $pr = new PlanningRecall();
            if (
                $pr->getFromDBForItemAndUser(
                    $val['itemtype'],
                    $val[getForeignKeyFieldForItemType($itemtype)],
                    Session::getLoginUserID()
                )
            ) {
                $recall = "<span class='b'>" . sprintf(
                    __('Recall on %s'),
                    Html::convDateTime($pr->fields['when'])
                ) .
                      "<span>";
            }
        }

        if (isset($val["state"])) {
            $html .= "<span>";
            $html .= Planning::getState($val["state"]);
            $html .= "</span>";
        }
        $html .= "<div>";
        $html .= sprintf(__('%1$s: %2$s'), __('Priority'), $parent->getPriorityName($val["priority"]));
        $html .= "</div>";

       // $val['content'] has already been sanitized and decoded by self::populatePlanning()
        $content = $val['content'];
        $html .= "<div class='event-description rich_text_container'>" . $content . "</div>";
        $html .= $recall;

        return $html;
    }


    /** form for Task
     *
     * @param $ID        Integer : Id of the task
     * @param $options   array
     *     -  parent Object : the object
     **/
    public function showForm($ID, array $options = [])
    {
        TemplateRenderer::getInstance()->display('components/itilobject/timeline/form_task.html.twig', [
            'item'               => $options['parent'],
            'subitem'            => $this,
            'has_pending_reason' => PendingReason_Item::getForItem($options['parent']) !== false,
            'params'             => $options,
        ]);

        return true;
    }


    /**
     * Form for Ticket or Problem Task on Massive action
     */
    public function showMassiveActionAddTaskForm()
    {
        echo "<table class='tab_cadre_fixe'>";
        echo '<tr><th colspan=4>' . __('Add a new task') . '</th></tr>';

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . _n('Category', 'Categories', 1) . "</td>";
        echo "<td>";
        TaskCategory::dropdown(['condition' => ['is_active' => 1]]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Description') . "</td>";
        echo "<td><textarea name='content' cols='50' rows='6'></textarea></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Duration') . "</td>";
        echo "<td>";
        $toadd = [];
        for ($i = 9; $i <= 100; $i++) {
            $toadd[] = $i * HOUR_TIMESTAMP;
        }
        Dropdown::showTimeStamp("actiontime", ['min'             => 0,
            'max'             => 8 * HOUR_TIMESTAMP,
            'addfirstminutes' => true,
            'inhours'         => true,
            'toadd'           => $toadd
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Status') . "</td>";
        echo "<td>";
        Planning::dropdownState("state", $_SESSION['glpitask_state']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center' colspan='2'>";
        if ($this->maybePrivate()) {
            echo "<input type='hidden' name='is_private' value='" . $_SESSION['glpitask_private'] . "'>";
        }
        echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
        echo "</td>";
        echo "</tr>";

        echo "</table>";
    }

    /**
     * Get tasks list
     *
     * @since 9.2
     *
     * @return DBmysqlIterator
     */
    public static function getTaskList($status, $showgrouptickets, $start = null, $limit = null)
    {
        global $DB;

        $prep_req = ['SELECT' => self::getTable() . '.id', 'FROM' => self::getTable()];

        $itemtype = str_replace('Task', '', self::getType());
        $fk_table = getTableForItemType($itemtype);
        $fk_field = Toolbox::strtolower(getPlural($itemtype)) . '_id';

        $prep_req['INNER JOIN'] = [
            $fk_table => [
                'FKEY' => [
                    self::getTable()  => $fk_field,
                    $fk_table         => 'id'
                ]
            ]
        ];

        $prep_req['WHERE'] = [$fk_table . ".status" => $itemtype::getNotSolvedStatusArray()];
        switch ($status) {
            case "todo": // we display the task with the status `todo`
                $prep_req['WHERE'][self::getTable() . '.state'] = Planning::TODO;
                break;
        }
        if ($showgrouptickets) {
            if (isset($_SESSION['glpigroups']) && count($_SESSION['glpigroups'])) {
                $prep_req['WHERE'][self::getTable() . '.groups_id_tech'] = $_SESSION['glpigroups'];
            } else {
               // Return empty iterator result
                $prep_req['WHERE'][] = 0;
            }
        } else {
            $prep_req['WHERE'][self::getTable() . '.users_id_tech'] = $_SESSION['glpiID'];
        }

        $prep_req['WHERE'] += getEntitiesRestrictCriteria($fk_table);
        $prep_req['WHERE'][$fk_table . '.is_deleted'] = 0;

        $prep_req['ORDER'] = [self::getTable() . '.date_mod DESC'];

        if ($start !== null) {
            $prep_req['START'] = $start;
        }
        if ($limit !== null) {
            $prep_req['LIMIT'] = $limit;
        }

        $req = $DB->request($prep_req);
        return $req;
    }


    /**
     * Display tasks in homepage
     *
     * @since 9.2
     *
     * @param integer $start            Start number to display
     * @param string  $status           The task status to filter
     * @param boolean $showgrouptickets As we display for group defined in task or not?
     *
     * @return void
     */
    public static function showCentralList($start, $status = 'todo', $showgrouptickets = true)
    {
        global $DB;

        $iterator = self::getTaskList($status, $showgrouptickets);

        $total_row_count = count($iterator);
        $displayed_row_count = min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count);

        if ($total_row_count > 0) {
            $itemtype = get_called_class();
            switch ($status) {
                case "todo":
                    $options  = [
                        'reset'    => 'reset',
                        'criteria' => [
                            [
                                'field'      => 12, // status
                                'searchtype' => 'equals',
                                'value'      => 'notold',
                                'link'       => 'AND',
                            ]
                        ],
                    ];
                    if ($showgrouptickets) {
                        $options['criteria'][] = [
                            'field'      => 112, // tech in charge of task
                            'searchtype' => 'equals',
                            'value'      => 'mygroups',
                            'link'       => 'AND',
                        ];
                    } else {
                        $options['criteria'][] = [
                            'field'      => 95, // tech in charge of task
                            'searchtype' => 'equals',
                            'value'      => $_SESSION['glpiID'],
                            'link'       => 'AND',
                        ];
                    }
                    $options['criteria'][] = [
                        'field'      => 33, // task status
                        'searchtype' => 'equals',
                        'value'      =>  Planning::TODO,
                        'link'       => 'AND',
                    ];

                    $title = '';
                    if ($itemtype == "TicketTask") {
                        $title = __("Ticket tasks to do");
                        $type = Ticket::getTypeName();
                        $parent_itemtype = Ticket::class;
                    } else if ($itemtype == "ProblemTask") {
                        $title = __("Problem tasks to do");
                        $type = Problem::getTypeName();
                        $parent_itemtype = Problem::class;
                    } else if ($itemtype == "ChangeTask") {
                        $title = __("Change tasks to do");
                        $type = Change::getTypeName();
                        $parent_itemtype = Change::class;
                    } else {
                        // Invalid itemtype
                        return;
                    }
                    $linked_itemtype = str_replace("Task", "", $itemtype);
                    $main_header = "<a href=\"" . $linked_itemtype::getSearchURL() . "?" .
                      Toolbox::append_params($options, '&amp;') . "\">" .
                      Html::makeTitle($title, $displayed_row_count, $total_row_count) . "</a>";
                    break;

                default:
                    // Invalid status
                    return;
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
                ],
                'rows'         => []
            ];

            $i = 0;
            if ($displayed_row_count > 0) {
                $twig_params['header_rows'][] = [
                    [
                        'content'   => __('ID'),
                        'style'     => 'width: 75px'
                    ],
                    [
                        'content'   => __('Title') . " (" . strtolower($type) . ")",
                        'style'     => 'width: 20%'
                    ],
                    __('Description')
                ];
                foreach ($iterator as $data) {
                    $row = [
                        'values' => []
                    ];

                    $task = $itemtype::getById($data['id']);
                    $parent_item = $parent_itemtype::getById($task->fields[getForeignKeyFieldForItemType($parent_itemtype)]);


                    if (!$task || !$parent_item) {
                        // Invalid data; skip
                        continue;
                    }

                    // Parent item id with priority hint
                    $bgcolor = $_SESSION["glpipriority_" . $parent_item->fields["priority"]];
                    $name = sprintf(__('%1$s: %2$s'), __('ID'), $parent_item->fields["id"]);
                    $row['values'][] = [
                        'content' => "<div class='priority_block' style='border-color: $bgcolor'><span style='background: $bgcolor'></span>&nbsp;$name</div>"
                    ];

                    // Parent item name
                    $row['values'][] = [
                        'content' => $parent_item->fields['name']
                    ];

                    // Task description
                    $href = $parent_item::getFormURLWithID($parent_item->fields['id']);
                    $link_title = Html::resume_text(RichText::getTextFromHtml($task->fields['content'], false, true, true), 50);
                    $row['values'][] = [
                        'content' => "<a href='$href'>$link_title</a>"
                    ];

                    $twig_params['rows'][] = $row;

                    $i++;
                    if ($i == $displayed_row_count) {
                        break;
                    }
                }
            }
            echo TemplateRenderer::getInstance()->render('components/table.html.twig', $twig_params);
        }
    }



    /**
     * Very short table to display the task
     *
     * @since 9.2
     *
     * @param integer $ID       The ID of the task
     * @param string  $itemtype The itemtype (TicketTask, ProblemTask)
     *
     * @return void
     */
    public static function showVeryShort($ID, $itemtype)
    {
        global $DB;

        $job  = new $itemtype();
        $rand = mt_rand();
        if ($job->getFromDB($ID)) {
            if ($DB->fieldExists($job->getTable(), 'tickets_id')) {
                $item_link = new Ticket();
                $item_link->getFromDB($job->fields['tickets_id']);
                $tab_name = "Ticket";
            } else if ($DB->fieldExists($job->getTable(), 'problems_id')) {
                $item_link = new Problem();
                $item_link->getFromDB($job->fields['problems_id']);
                $tab_name = "ProblemTask";
            } else if ($DB->fieldExists($job->getTable(), 'changes_id')) {
                $item_link = new Change();
                $item_link->getFromDB($job->fields['changes_id']);
                $tab_name = "ChangeTask";
            } else {
                throw new \RuntimeException(sprintf('Unexpected `%s` itemtype.', $itemtype));
            }

            $bgcolor = $_SESSION["glpipriority_" . $item_link->fields["priority"]];
            $name    = sprintf(__('%1$s: %2$s'), __('ID'), $job->fields["id"]);
            echo "<tr class='tab_bg_2'>";
            echo "<td>
            <div class='priority_block' style='border-color: $bgcolor'>
               <span style='background: $bgcolor'></span>&nbsp;$name
            </div>
         </td>";

            echo "<td>";
            echo $item_link->fields['name'];
            echo "</td>";

            echo "<td>";
            $link = "<a id='" . strtolower($item_link->getType()) . "ticket" . $item_link->fields["id"] . $rand . "' href='" .
                   $item_link->getFormURLWithID($item_link->fields["id"]);
            $link .= "&amp;forcetab=" . $tab_name . "$1";
            $link   .= "'>";
            $link    = sprintf(__('%1$s'), $link);
            printf(
                __('%1$s %2$s'),
                $link,
                Html::resume_text(RichText::getTextFromHtml($job->fields['content'], false, true, true), 50)
            );

            echo "</a>";
            echo "</td>";

           // Finish Line
            echo "</tr>";
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='6' ><i>" . __('No tasks do to.') . "</i></td></tr>";
        }
    }

    public static function getGroupItemsAsVCalendars($groups_id)
    {

        return self::getItemsAsVCalendars([static::getTableField('groups_id_tech') => $groups_id]);
    }

    public static function getUserItemsAsVCalendars($users_id)
    {

        return self::getItemsAsVCalendars([static::getTableField('users_id_tech') => $users_id]);
    }

    /**
     * Returns items as VCalendar objects.
     *
     * @param array $criteria
     *
     * @return \Sabre\VObject\Component\VCalendar[]
     */
    private static function getItemsAsVCalendars(array $criteria)
    {

        global $DB;

        $item = new static();
        $parent_item = getItemForItemtype($item->getItilObjectItemType());
        if (!$parent_item) {
            return;
        }

        $query = [
            'SELECT'     => [$item->getTableField('*')],
            'FROM'       => $item->getTable(),
            'INNER JOIN' => [],
            'WHERE'      => $criteria,
        ];
        if ($parent_item->maybeDeleted()) {
            $query['INNER JOIN'][$parent_item->getTable()] = [
                'ON' => [
                    $parent_item->getTable() => 'id',
                    $item->getTable()        => $parent_item->getForeignKeyField(),
                ]
            ];
            $query['WHERE'][$parent_item->getTableField('is_deleted')] = 0;
        }

        $tasks_iterator = $DB->request($query);

        $vcalendars = [];
        foreach ($tasks_iterator as $task) {
            $item->getFromResultSet($task);
            $vcalendar = $item->getAsVCalendar();
            if (null !== $vcalendar) {
                $vcalendars[] = $vcalendar;
            }
        }

        return $vcalendars;
    }

    public function getAsVCalendar()
    {

        global $CFG_GLPI;

        if (!$this->canViewItem()) {
            return null;
        }

        $parent_item = getItemForItemtype($this->getItilObjectItemType());
        if (!$parent_item) {
            return null;
        }
        $parent_id = $this->fields[$parent_item->getForeignKeyField()];
        if (!$parent_item->getFromDB($parent_id)) {
            return null;
        }

        $is_task = true;
        $is_planned = !empty($this->fields['begin']) && !empty($this->fields['end']);
        $target_component = $this->getTargetCaldavComponent($is_planned, $is_task);
        if (null === $target_component) {
            return null;
        }

        $vcalendar = $this->getVCalendarForItem($this, $target_component);

        $parent_fields = Html::entity_decode_deep($parent_item->fields);
        $utc_tz = new \DateTimeZone('UTC');

        $vcomp = $vcalendar->getBaseComponent();
        $vcomp->SUMMARY           = $parent_fields['name'];
        $vcomp->DTSTAMP           = (new \DateTime($parent_fields['date_mod']))->setTimeZone($utc_tz);
        $vcomp->{'LAST-MODIFIED'} = (new \DateTime($parent_fields['date_mod']))->setTimeZone($utc_tz);
        $vcomp->URL               = $CFG_GLPI['url_base'] . $parent_item->getFormURLWithID($parent_id, false);

        return $vcalendar;
    }

    public function getInputFromVCalendar(VCalendar $vcalendar)
    {

        $vtodo = $vcalendar->getBaseComponent();

        if (null !== $vtodo->RRULE) {
            throw new \UnexpectedValueException('RRULE not yet implemented for ITIL tasks');
        }

        $input = $this->getCommonInputFromVcomponent($vtodo, $this->isNewItem());

        if (!$this->isNewItem()) {
           // self::prepareInputForUpdate() expect these fields to be set in input.
           // We should be able to not pass these fields in input
           // but fixing self::prepareInputForUpdate() seems complex right now.
            $itil_fkey = getForeignKeyFieldForItemType($this->getItilObjectItemType());
            $input[$itil_fkey] = $this->fields[$itil_fkey];
            $input['users_id_tech'] = $this->fields['users_id_tech'];
        }

        return $input;
    }

    final public function unplan(): bool
    {
        return $this->update([
            'id'         => $this->fields['id'],
            'begin'      => 'NULL',
            'end'        => 'NULL',
            'actiontime' => 0,
        ]);
    }
}

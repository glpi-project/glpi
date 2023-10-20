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

use Glpi\DBAL\QueryExpression;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\RichText\RichText;

/**
 * CommonITILValidation Class
 *
 * @since 0.85
 **/
abstract class CommonITILValidation extends CommonDBChild
{
   // From CommonDBTM
    public $auto_message_on_action    = false;

    public static $log_history_add    = Log::HISTORY_LOG_SIMPLE_MESSAGE;
    public static $log_history_update = Log::HISTORY_LOG_SIMPLE_MESSAGE;
    public static $log_history_delete = Log::HISTORY_LOG_SIMPLE_MESSAGE;

    const VALIDATE               = 1024;


   // STATUS
    const NONE      = 1; // none
    const WAITING   = 2; // waiting
    const ACCEPTED  = 3; // accepted
    const REFUSED   = 4; // rejected

    public static function getIcon()
    {
        return 'ti ti-thumb-up';
    }

    public function getItilObjectItemType()
    {
        return str_replace('Validation', '', $this->getType());
    }

    public static function getCreateRights()
    {
        return [CREATE];
    }


    public static function getPurgeRights()
    {
        return [PURGE];
    }


    public static function getValidateRights()
    {
        return [static::VALIDATE];
    }


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Approval', 'Approvals', $nb);
    }


    public static function canCreate()
    {
        return Session::haveRightsOr(static::$rightname, static::getCreateRights());
    }


    /**
     * Is the current user have right to delete the current validation ?
     *
     * @return boolean
     **/
    public function canCreateItem()
    {

        if (
            ($this->fields["users_id"] == Session::getLoginUserID())
            || Session::haveRightsOr(static::$rightname, static::getCreateRights())
        ) {
            return true;
        }
        return false;
    }


    public static function canView()
    {

        return Session::haveRightsOr(
            static::$rightname,
            array_merge(
                static::getCreateRights(),
                static::getValidateRights(),
                static::getPurgeRights()
            )
        );
    }


    public static function canUpdate()
    {

        return Session::haveRightsOr(
            static::$rightname,
            array_merge(
                static::getCreateRights(),
                static::getValidateRights()
            )
        );
    }


    /**
     * Is the current user have right to delete the current validation ?
     *
     * @return boolean
     **/
    public function canDeleteItem()
    {

        if (
            ($this->fields["users_id"] == Session::getLoginUserID())
            || Session::haveRight(static::$rightname, DELETE)
        ) {
            return true;
        }
        return false;
    }


    /**
     * Does the current user have the rights needed to update the current validation?
     *
     * @return boolean
     */
    public function canUpdateItem()
    {
        $is_target = static::canValidate($this->fields[static::$items_id]);
        if (
            !$is_target
            && !Session::haveRightsOr(static::$rightname, static::getCreateRights())
        ) {
            return false;
        }
        return (int) $this->fields['status'] === self::WAITING
            || (int) $this->fields['users_id_validate'] === Session::getLoginUserID();
    }


    /**
     * @param integer $items_id ID of the item
     **/
    public static function canValidate($items_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [static::getTable() . '.id'],
            'FROM'   => static::getTable(),
            'WHERE'  => [
                static::$items_id => $items_id,
                static::getTargetCriteriaForUser(Session::getLoginUserID()),
            ],
            'START'  => 0,
            'LIMIT'  => 1
        ]);

        if (count($iterator) > 0) {
            return true;
        }
        return false;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $hidetab = false;
       // Hide if no rights on validations
        if (!static::canView()) {
            $hidetab = true;
        }
       // No right to create and no validation for current object
        if (
            !$hidetab
            && !Session::haveRightsOr(static::$rightname, static::getCreateRights())
            && !static::canValidate($item->getID())
        ) {
            $hidetab = true;
        }

        if (!$hidetab) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $restrict = [static::$items_id => $item->getID()];
               // No rights for create only count asign ones
                if (!Session::haveRightsOr(static::$rightname, static::getCreateRights())) {
                    $restrict[] = static::getTargetCriteriaForUser(Session::getLoginUserID());
                }
                $nb = countElementsInTable(static::getTable(), $restrict);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        $validation = new static();
        $validation->showSummary($item);
        return true;
    }


    public function post_getEmpty()
    {

        $this->fields["users_id"] = Session::getLoginUserID();
        $this->fields["status"]   = self::WAITING;
    }


    public function prepareInputForAdd($input)
    {

        $input["users_id"] = 0;
       // Only set requester on manual action
        if (
            !isset($input['_auto_import'])
            && !isset($input['_auto_update'])
            && !Session::isCron()
        ) {
            $input["users_id"] = Session::getLoginUserID();
        }

        $input["submission_date"] = $_SESSION["glpi_currenttime"];
        $input["status"]          = self::WAITING;

        if (
            (!isset($input['itemtype_target']) || empty($input['itemtype_target']))
            && (isset($input['users_id_validate']) && !empty($input['users_id_validate']))
        ) {
            Toolbox::deprecated('Defining "users_id_validate" field during creation is deprecated in "CommonITILValidation".');
            $input['itemtype_target'] = User::class;
            $input['items_id_target'] = $input['users_id_validate'];
            unset($input['users_id_validate']);
        }

        if (
            !isset($input['itemtype_target']) || empty($input['itemtype_target'])
            || !isset($input["items_id_target"]) || $input["items_id_target"] <= 0
        ) {
            return false;
        }

        $itemtype = static::$itemtype;
        $input['timeline_position'] = $itemtype::getTimelinePosition($input[static::$items_id], $this->getType(), $input["users_id"]);

        return parent::prepareInputForAdd($input);
    }


    public function post_addItem()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // Handle rich-text images
        foreach (['comment_submission', 'comment_validation'] as $content_field) {
            $this->input = $this->addFiles($this->input, [
                'force_update'  => true,
                'name'          => $content_field,
                'content_field' => $content_field,
            ]);
        }

        // Handle uploaded documents
        $this->input = $this->addFiles($this->input);

        $item     = new static::$itemtype();
        $mailsend = false;
        if ($item->getFromDB($this->fields[static::$items_id])) {
            // Set global validation to waiting
            if (
                ($item->fields['global_validation'] == self::ACCEPTED)
                || ($item->fields['global_validation'] == self::NONE)
            ) {
                $input = [
                    'id'                    => $this->fields[static::$items_id],
                    'global_validation'     => self::WAITING,
                    '_from_itilvalidation'  => true,
                ];

               // to fix lastupdater
                if (isset($this->input['_auto_update'])) {
                    $input['_auto_update'] = $this->input['_auto_update'];
                }
             // to know update by rules
                if (isset($this->input["_rule_process"])) {
                      $input['_rule_process'] = $this->input["_rule_process"];
                }
             // No update ticket notif on ticket add
                if (isset($this->input["_ticket_add"])) {
                    $input['_disablenotif'] = true;
                }
                $item->update($input);
            }

            if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
                $options = ['validation_id'     => $this->fields["id"],
                    'validation_status' => $this->fields["status"]
                ];
                $mailsend = NotificationEvent::raiseEvent('validation', $item, $options, $this);
            }
            if ($mailsend) {
                if ($this->fields['itemtype_target'] === 'User') {
                    $user    = new User();
                    $user->getFromDB($this->fields["items_id_target"]);
                    $email   = $user->getDefaultEmail();
                    if (!empty($email)) {
                        Session::addMessageAfterRedirect(sprintf(__('Approval request sent to %s'), $user->getName()));
                    } else {
                        Session::addMessageAfterRedirect(
                            sprintf(
                                __('The selected user (%s) has no valid email address. The request has been created, without email confirmation.'),
                                $user->getName()
                            ),
                            false,
                            ERROR
                        );
                    }
                } elseif (is_a($this->fields["itemtype_target"], CommonDBTM::class, true)) {
                    $target = new $this->fields["itemtype_target"]();
                    if ($target->getFromDB($this->fields["items_id_target"])) {
                        Session::addMessageAfterRedirect(sprintf(__('Approval request sent to %s'), $target->getName()));
                    }
                }
            }
        }
        parent::post_addItem();
    }


    public function prepareInputForUpdate($input)
    {

        $forbid_fields = ['entities_id', static::$items_id, 'is_recursive'];
        if (isset($input["status"]) && static::canValidate($this->fields[static::$items_id])) {
            if (
                ($input["status"] == self::REFUSED)
                && (!isset($input["comment_validation"])
                 || ($input["comment_validation"] == ''))
            ) {
                Session::addMessageAfterRedirect(
                    __('If approval is denied, specify a reason.'),
                    false,
                    ERROR
                );
                return false;
            }
            if ($input["status"] == self::WAITING) {
               // $input["comment_validation"] = '';
                $input["validation_date"] = 'NULL';
            } else {
                $input["validation_date"] = $_SESSION["glpi_currenttime"];
            }

            array_push(
                $forbid_fields,
                'users_id',
                'itemtype_target',
                'items_id_target',
                'comment_submission',
                'submission_date'
            );
        } else if (Session::haveRightsOr(static::$rightname, $this->getCreateRights())) { // Update validation request
            array_push($forbid_fields, 'status', 'comment_validation', 'validation_date');
        }

        if (count($forbid_fields)) {
            foreach (array_keys($forbid_fields) as $key) {
                if (isset($input[$key])) {
                    unset($input[$key]);
                }
            }
        }

        return parent::prepareInputForUpdate($input);
    }


    public function post_updateItem($history = 1)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $item    = new static::$itemtype();
        $donotif = $CFG_GLPI["use_notifications"];
        if (isset($this->input['_disablenotif'])) {
            $donotif = false;
        }

        // Handle rich-text images
        foreach (['comment_submission', 'comment_validation'] as $content_field) {
            $this->input = $this->addFiles($this->input, [
                'force_update'  => true,
                'name'          => $content_field,
                'content_field' => $content_field,
            ]);
        }

        // Handle uploaded documents
        $this->input = $this->addFiles($this->input);

        if ($item->getFromDB($this->fields[static::$items_id])) {
            if (
                count($this->updates)
                && $donotif
            ) {
                $options  = ['validation_id'     => $this->fields["id"],
                    'validation_status' => $this->fields["status"]
                ];
                NotificationEvent::raiseEvent('validation_answer', $item, $options, $this);
            }

            //if status is updated, update global approval status
            if (in_array("status", $this->updates)) {
                $input = [
                    'id'                    => $this->fields[static::$items_id],
                    'global_validation'     => self::computeValidationStatus($item),
                    '_from_itilvalidation'  => true,
                ];
                $item->update($input);
            }
        }
        parent::post_updateItem($history);
    }

    public function pre_deleteItem()
    {

        $item    = new static::$itemtype();
        if ($item->getFromDB($this->fields[static::$items_id])) {
            if (($item->fields['global_validation'] == self::WAITING)) {
                $input = [
                    'id'                    => $this->fields[static::$items_id],
                    'global_validation'     => self::NONE,
                    '_from_itilvalidation'  => true,
                ];
                $item->update($input);
            }
        }
        return true;
    }


    /**
     * @see CommonDBConnexity::getHistoryChangeWhenUpdateField
     **/
    public function getHistoryChangeWhenUpdateField($field)
    {

        if ($field == 'status') {
            $result   = ['0', '', ''];
            if ($this->fields["status"] == self::ACCEPTED) {
                //TRANS: %s is the username
                $result[2] = sprintf(__('Approval granted by %s'), getUserName($this->fields["users_id_validate"]));
            } else {
                //TRANS: %s is the username
                $result[2] = sprintf(__('Update the approval request to %s'), $this->getTargetName());
            }
            return $result;
        }
        return false;
    }


    /**
     * @see CommonDBChild::getHistoryNameForItem
     **/
    public function getHistoryNameForItem(CommonDBTM $item, $case)
    {
        $target_name = $this->getTargetName();

        switch ($case) {
            case 'add':
                return sprintf(__('Approval request sent to %s'), $target_name);

            case 'delete':
                return sprintf(__('Cancel the approval request to %s'), $target_name);
        }
        return '';
    }

    /**
     * Returns the target name.
     *
     * @return string
     */
    final protected function getTargetName(): string
    {
        $target_name = '';
        switch ($this->fields['itemtype_target']) {
            case User::class:
                $target_name = getUserName($this->fields['items_id_target']);
                break;
            default:
                if (!is_a($this->fields['itemtype_target'], CommonDBTM::class, true)) {
                    break;
                }
                $target_item = new $this->fields['itemtype_target']();
                if ($target_item->getFromDB($this->fields['items_id_target'])) {
                    $target_name = $target_item->getNameID();
                }
                break;
        }
        return $target_name;
    }


    /**
     * get the Ticket validation status list
     *
     * @param $withmetaforsearch  boolean (false by default)
     * @param $global             boolean (true for global status, with "no validation" option)
     *                                    (false by default)
     *
     * @return array
     **/
    public static function getAllStatusArray($withmetaforsearch = false, $global = false)
    {

        $tab = [
            self::WAITING  => __('Waiting for approval'),
            self::REFUSED  => _x('validation', 'Refused'),
            self::ACCEPTED => __('Granted')
        ];
        if ($global) {
            $tab[self::NONE] = __('Not subject to approval');

            if ($withmetaforsearch) {
                $tab['can'] = __('Granted + Not subject to approval');
            }
        }

        if ($withmetaforsearch) {
            $tab['all'] = __('All');
        }
        return $tab;
    }


    /**
     * Dropdown of validation status
     *
     * @param string $name    select name
     * @param array  $options possible options:
     *      - value    : default value (default waiting)
     *      - all      : boolean display all (default false)
     *      - global   : for global validation (default false)
     *      - display  : boolean display or get string ? (default true)
     *
     * @return string|integer Output string if display option is set to false,
     *                        otherwise random part of dropdown id
     **/
    public static function dropdownStatus($name, $options = [])
    {

        $p = [
            'value'             => self::WAITING,
            'global'            => false,
            'all'               => false,
            'display'           => true,
            'disabled'          => false,
            'templateResult'    => "templateValidation",
            'templateSelection' => "templateValidation",
            'width'             => '100%',
            'required'          => false,
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $tab = self::getAllStatusArray($p['all'], $p['global']);
        unset($p['all']);
        unset($p['global']);

        return Dropdown::showFromArray($name, $tab, $p);
    }


    /**
     * Get Ticket validation status Name
     *
     * @param integer   $value
     * @param bool      $decorated
     **/
    public static function getStatus($value, bool $decorated = false)
    {
        $statuses = self::getAllStatusArray(true, true);

        $label = $statuses[$value] ?? $value;

        if ($decorated) {
            $color   = self::getStatusColor($value);
            $classes = null;
            switch ($value) {
                case self::WAITING:
                    $classes = 'waiting far fa-clock';
                    break;
                case self::ACCEPTED:
                    $classes = 'accepted fas fa-check';
                    break;
                case self::REFUSED:
                    $classes = 'refused fas fa-times';
                    break;
            }

            return sprintf('<span><i class="validationstatus %s"></i> %s</span>', $classes, $label);
        }

        return $label;
    }


    /**
     * Get Ticket validation status Color
     *
     * @param integer $value status ID
     **/
    public static function getStatusColor($value)
    {

        switch ($value) {
            case self::WAITING:
                $style = "#FFC65D";
                break;

            case self::REFUSED:
                $style = "#cf9b9b";
                break;

            case self::ACCEPTED:
                $style = "#9BA563";
                break;

            default:
                $style = "#cf9b9b";
        }
        return $style;
    }


    /**
     * Get item validation demands count for a user
     *
     * @param $users_id  integer  User ID
     **/
    public static function getNumberToValidate($users_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $row = $DB->request([
            'FROM'   => static::getTable(),
            'COUNT'  => 'cpt',
            'WHERE'  => [
                'status' => self::WAITING,
                static::getTargetCriteriaForUser($users_id)
            ]
        ])->current();

        return $row['cpt'];
    }

    /**
     * Return criteria to apply to get only validations on which given user is targetted.
     *
     * @see self::getNumberToValidate()
     *
     * @param int $users_id
     * @param bool $search_in_groups
     *
     * @return array
     */
    final public static function getTargetCriteriaForUser(int $users_id, bool $search_in_groups = true): array
    {
        $substitute_subQuery = new QuerySubQuery([
            'SELECT'     => 'validator_users.id',
            'FROM'       => User::getTable() . ' as validator_users',
            'INNER JOIN' => [
                ValidatorSubstitute::getTable() => [
                    'ON' => [
                        ValidatorSubstitute::getTable() => User::getForeignKeyField(),
                        'validator_users' => 'id',
                        [
                            'AND' => [
                                [
                                    'OR' => [
                                        [
                                            'validator_users.substitution_start_date' => null,
                                        ],
                                        [
                                            'validator_users.substitution_start_date' => ['<=', QueryFunction::now()],
                                        ],
                                    ],
                                ],
                                [
                                    'OR' => [
                                        [
                                            'validator_users.substitution_end_date' => null,
                                        ],
                                        [
                                            'validator_users.substitution_end_date' => ['>=', QueryFunction::now()],
                                        ],
                                    ],
                                ],
                            ]
                        ]
                    ],
                ],
            ],
            'WHERE'  => [
                ValidatorSubstitute::getTable() . '.users_id_substitute' => $users_id,
            ],
        ]);

        $target_criteria = [
            'OR' => [
                [
                    static::getTableField('itemtype_target') => User::class,
                    static::getTableField('items_id_target') => $users_id,
                ],
                [
                    static::getTableField('itemtype_target') => User::class,
                    static::getTableField('items_id_target') => $substitute_subQuery,
                ],
            ],
        ];
        if ($search_in_groups) {
            $target_criteria = [
                'OR' => [
                    $target_criteria,
                    [
                        static::getTableField('itemtype_target') => Group::class,
                        static::getTableField('items_id_target') => new QuerySubQuery([
                            'SELECT' => Group_User::getTableField('groups_id'),
                            'FROM'   => Group_User::getTable(),
                            'WHERE'  => [
                                'OR' => [
                                    [
                                        Group_User::getTableField('users_id') => $users_id,
                                    ],
                                    [
                                        Group_User::getTableField('users_id') => $substitute_subQuery,
                                    ],
                                ],
                            ],
                        ])
                    ],
                ],
            ];
        }

        return $target_criteria;
    }


    /**
     * Get the number of validations attached to an item having a specified status
     *
     * @param integer $items_id item ID
     * @param integer $status   status
     *
     * @deprecated 10.1.0
     **/
    public static function getTicketStatusNumber($items_id, $status)
    {
        Toolbox::deprecated();

        /** @var \DBmysql $DB */
        global $DB;

        $row = $DB->request([
            'FROM'   => static::getTable(),
            'COUNT'  => 'cpt',
            'WHERE'  => [
                static::$items_id => $items_id,
                'status'          => $status
            ]
        ])->current();

        return $row['cpt'];
    }


    /**
     * Check if validation already exists
     *
     * @param $items_id   integer  item ID
     * @param $users_id   integer  user ID
     *
     * @since 0.85
     *
     * @return boolean
     *
     * @deprecated 10.1.0
     **/
    public static function alreadyExists($items_id, $users_id)
    {
        Toolbox::deprecated();

        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => [
                static::$items_id    => $items_id,
                'users_id_validate'  => $users_id
            ],
            'START'  => 0,
            'LIMIT'  => 1
        ]);

        if (count($iterator) > 0) {
            return true;
        }
        return false;
    }


    /**
     * Form for Followup on Massive action
     **/
    public static function showFormMassiveAction()
    {

        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $types = [
            'User'       => User::getTypeName(1),
            'Group_User' => __('Group user(s)'),
            'Group'      => Group::getTypeName(1),
        ];

        $rand = Dropdown::showFromArray(
            "validatortype",
            $types,
            ['display_emptychoice' => true]
        );

        $paramsmassaction = [
            'validation_class' => static::class,
            'validatortype'    => '__VALUE__',
            'entity'           => $_SESSION['glpiactive_entity'],
            'right'            => static::$itemtype == 'Ticket' ? ['validate_request', 'validate_incident'] : 'validate'
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown_validatortype$rand",
            "show_massiveaction_field",
            $CFG_GLPI["root_doc"] .
                                       "/ajax/dropdownMassiveActionAddValidator.php",
            $paramsmassaction
        );

        echo "<br><span id='show_massiveaction_field'>&nbsp;</span>\n";
    }


    /**
     * @since 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     **/
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'submit_validation':
                static::showFormMassiveAction();
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

        switch ($ma->getAction()) {
            case 'submit_validation':
                $input = $ma->getInput();
                $valid = new static();
                foreach ($ids as $id) {
                    if ($item->getFromDB($id)) {
                        $input2 = [static::$items_id      => $id,
                            'comment_submission'   => $input['comment_submission']
                        ];
                        if ($valid->can(-1, CREATE, $input2)) {
                            if (array_key_exists('users_id_validate', $input)) {
                                Toolbox::deprecated('Usage of "users_id_validate" in input is deprecated. Use "itemtype_target"/"items_id_target" instead.');
                                $input['itemtype_target'] = User::class;
                                $input['items_id_target'] = $input['users_id_validate'];
                                unset($input['users_id_validate']);
                            }

                            $itemtype  = $input['itemtype_target'];
                            $items_ids = $input['items_id_target'];

                            if (!is_array($items_ids)) {
                                $items_ids = [$items_ids];
                            }
                            $ok = true;
                            foreach ($items_ids as $item_id) {
                                $input2["itemtype_target"] = $itemtype;
                                $input2["items_id_target"] = $item_id;
                                if (!$valid->add($input2)) {
                                     $ok = false;
                                }
                            }
                            if ($ok) {
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
     * Print the validation list into item
     *
     * @param CommonDBTM $item
     **/
    public function showSummary(CommonDBTM $item)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        if (
            !Session::haveRightsOr(
                static::$rightname,
                array_merge(
                    static::getCreateRights(),
                    static::getValidateRights(),
                    static::getPurgeRights()
                )
            )
        ) {
            return false;
        }

        /** @var CommonITILObject $item */
        $tID    = $item->fields['id'];

        $tmp    = [static::$items_id => $tID];
        $canadd = $this->can(-1, CREATE, $tmp);
        $rand   = mt_rand();

        if ($canadd) {
            $itemtype = static::$itemtype;
            echo "<form method='post' name=form action='" . $itemtype::getFormURL() . "'>";
        }
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr>";
        echo "<th colspan='3'>" . self::getTypeName(Session::getPluralNumber()) . "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Global approval status') . "</td>";
        echo "<td colspan='2'>";
        echo TicketValidation::getStatus($item->fields["global_validation"], true);
        echo "</td></tr>";

        echo "<tr>";
        echo "<th colspan='2'>" . _x('item', 'State') . "</th>";
        echo "<th colspan='2'>";
        echo self::getValidationStats($tID);
        echo "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Minimum validation required') . "</td>";
        if ($canadd) {
            echo "<td>";
            echo $item->getValueToSelect(
                'validation_percent',
                'validation_percent',
                $item->fields["validation_percent"]
            );
            echo "</td>";
            echo "<td><input type='submit' name='update' class='btn btn-outline-secondary' value='" .
                    _sx('button', 'Save') . "'>";
            if (!empty($tID)) {
                echo "<input type='hidden' name='id' value='$tID'>";
            }
            echo "</td>";
        } else {
            echo "<td colspan='2'>";
            echo Dropdown::getValueWithUnit($item->fields["validation_percent"], "%");
            echo "</td>";
        }
        echo "</tr>";
        echo "</table>";
        if ($canadd) {
            Html::closeForm();
        }

        $iterator = $DB->Request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [static::$items_id => $item->getField('id')],
            'ORDER'  => 'submission_date DESC'
        ]);

        $colonnes = ['', _x('item', 'State'), __('Request date'), __('Approval requester'),
            __('Request comments'), __('Approval status'),
            __('Requested approver type'), __('Requested approver'),
        ];
        $nb_colonnes = count($colonnes);

        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'><th colspan='" . $nb_colonnes . "'>" . __('Approvals for the ticket') .
           "</th></tr>";

        if ($canadd) {
            if (
                !in_array($item->fields['status'], array_merge(
                    $item->getSolvedStatusArray(),
                    $item->getClosedStatusArray()
                ))
            ) {
                echo "<tr class='tab_bg_1 noHover'><td class='center' colspan='" . $nb_colonnes . "'>";
                echo "<a class='btn btn-outline-secondary' href='javascript:viewAddValidation" . $tID . "$rand();'>";
                echo __('Send an approval request') . "</a></td></tr>\n";
            }
        }
        if (count($iterator)) {
            $header = "<tr>";
            foreach ($colonnes as $colonne) {
                $header .= "<th>" . $colonne . "</th>";
            }
            $header .= "</tr>";
            echo $header;

            Session::initNavigateListItems(
                $this->getType(),
                //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(
                                            __('%1$s = %2$s'),
                                            $item->getTypeName(1),
                                            $item->fields["name"]
                                        )
            );

            foreach ($iterator as $row) {
                $canedit = $this->canEdit($row["id"]);
                Session::addToNavigateListItems($this->getType(), $row["id"]);
                $bgcolor = self::getStatusColor($row['status']);
                $status  = self::getStatus($row['status']);

                echo "<tr class='tab_bg_1'>";

                echo "<td>";
                if ($canedit) {
                    echo "<span class='far fa-edit' style='cursor:pointer' title='" . __('Edit') . "' ";
                    echo "onClick=\"viewEditValidation" . $item->fields['id'] . $row["id"] . "$rand();\"";
                    echo " id='viewvalidation" . $this->fields[static::$items_id] . $row["id"] . "$rand'";
                    echo "></span>";
                    echo "\n<script type='text/javascript' >\n";
                    echo "function viewEditValidation" . $item->fields['id'] . $row["id"] . "$rand() {\n";
                    $params = ['type'             => $this->getType(),
                        'parenttype'       => static::$itemtype,
                        static::$items_id  => $this->fields[static::$items_id],
                        'id'               => $row["id"]
                    ];
                    Ajax::updateItemJsCode(
                        "viewvalidation" . $item->fields['id'] . "$rand",
                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        $params
                    );
                    echo "};";
                    echo "</script>\n";
                }
                echo "</td>";

                echo "<td><div style='background-color:" . $bgcolor . ";'>" . $status . "</div></td>";
                echo "<td>" . Html::convDateTime($row["submission_date"]) . "</td>";
                echo "<td>" . getUserName($row["users_id"]) . "</td>";
                $comment_submission = RichText::getEnhancedHtml($this->fields['comment_submission'], ['images_gallery' => true]);
                echo "<td><div class='rich_text_container'>" . $comment_submission . "</div></td>";
                echo "<td>" . Html::convDateTime($row["validation_date"]) . "</td>";
                $type_name   = null;
                $target_name = null;
                if ($row["itemtype_target"] === 'User') {
                    $type_name   = User::getTypeName();
                    $target_name = getUserName($row["items_id_target"]);
                } elseif (is_a($row["itemtype_target"], CommonDBTM::class, true)) {
                    $target = new $row["itemtype_target"]();
                    $type_name = $target->getTypeName();
                    if ($target->getFromDB($row["items_id_target"])) {
                        $target_name = $target->getName();
                    }
                }
                echo "<td>" . $type_name . "</td>";
                echo "<td>" . $target_name . "</td>";
                $is_answered = $row['status'] !== self::WAITING && $row['users_id_validate'] > 0;
                echo "<td>" . ($is_answered ? getUserName($row["users_id_validate"]) : '') . "</td>";
                $comment_validation = RichText::getEnhancedHtml($this->fields['comment_validation'] ?? '', ['images_gallery' => true]);
                echo "<td><div class='rich_text_container'>" . $comment_validation . "</div></td>";

                $doc_item = new Document_Item();
                $docs = $doc_item->find(["itemtype"          => $this->getType(),
                    "items_id"           => $this->getID(),
                    "timeline_position"  => ['>', CommonITILObject::NO_TIMELINE]
                ]);
                 $out = "";
                foreach ($docs as $docs_values) {
                     $doc = new Document();
                     $doc->getFromDB($docs_values['documents_id']);
                     $out  .= "<a ";
                     $out .= "href=\"" . Document::getFormURLWithID($docs_values['documents_id']) . "\">";
                     $out .= $doc->getField('name') . "</a><br>";
                }
                 echo "<td>" . $out . "</td>";

                 echo "</tr>";
            }
            echo $header;
        } else {
           //echo "<div class='center b'>".__('No item found')."</div>";
            echo "<tr class='tab_bg_1 noHover'><th colspan='" . $nb_colonnes . "'>";
            echo __('No item found') . "</th></tr>\n";
        }
        echo "</table>";

        echo "<div id='viewvalidation" . $tID . "$rand'></div>\n";

        if ($canadd) {
            echo "<script type='text/javascript' >\n";
            echo "function viewAddValidation" . $tID . "$rand() {\n";
            $params = ['type'             => $this->getType(),
                'parenttype'       => static::$itemtype,
                static::$items_id  => $tID,
                'id'               => -1
            ];
            Ajax::updateItemJsCode(
                "viewvalidation" . $tID . "$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>";
        }
    }


    /**
     * Print the validation form
     *
     * @param $ID        integer  ID of the item
     * @param $options   array    options used
     **/
    public function showForm($ID, array $options = [])
    {
        if ($ID > 0) {
            $this->canEdit($ID);
        } else {
            $options[static::$items_id] = $options['parent']->fields["id"];
            $this->check(-1, CREATE, $options);
        }

        TemplateRenderer::getInstance()->display('components/itilobject/timeline/form_validation.html.twig', [
            'item'      => $options['parent'],
            'subitem'   => $this,
            'scroll'    => true,
        ]);

        return true;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $table = static::getTable();

        $tab[] = [
            'id'                 => 'common',
            'name'               => CommonITILValidation::getTypeName(1)
        ];

        $tab[] = [
            'id'                 => 9,
            'table'              => $table,
            'field'              => 'id',
            'name'               => __('ID'),
            'datatype'           => 'number',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $table,
            'field'              => 'comment_submission',
            'name'               => __('Request comments'),
            'datatype'           => 'text',
            'htmltext'           => true
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $table,
            'field'              => 'comment_validation',
            'name'               => __('Approval comments'),
            'datatype'           => 'text',
            'htmltext'           => true
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $table,
            'field'              => 'status',
            'name'               => __('Status'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $table,
            'field'              => 'submission_date',
            'name'               => __('Request date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $table,
            'field'              => 'validation_date',
            'name'               => __('Approval date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => __('Approval requester'),
            'datatype'           => 'itemlink',
            'right'              => static::$itemtype == 'Ticket' ? 'create_ticket_validate' : 'create_validate',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_validate',
            'name'               => __('Approver'),
            'datatype'           => 'itemlink',
            'right'              => static::$itemtype == 'Ticket' ? ['validate_request', 'validate_incident'] : 'validate',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $table,
            'field'              => 'itemtype_target',
            'name'               => __('Requested approver type'),
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }


    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'validation',
            'name'               => CommonITILValidation::getTypeName(1)
        ];

        $tab[] = [
            'id'                 => '51',
            'table'              => getTableForItemType(static::$itemtype),
            'field'              => 'validation_percent',
            'name'               => __('Minimum validation required'),
            'datatype'           => 'number',
            'unit'               => '%',
            'min'                => 0,
            'max'                => 100,
            'step'               => 50
        ];

        $tab[] = [
            'id'                 => '52',
            'table'              => getTableForItemType(static::$itemtype),
            'field'              => 'global_validation',
            'name'               => CommonITILValidation::getTypeName(1),
            'searchtype'         => 'equals',
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '53',
            'table'              => static::getTable(),
            'field'              => 'comment_submission',
            'name'               => __('Request comments'),
            'datatype'           => 'text',
            'htmltext'           => true,
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '54',
            'table'              => static::getTable(),
            'field'              => 'comment_validation',
            'name'               => __('Approval comments'),
            'datatype'           => 'text',
            'htmltext'           => true,
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '55',
            'table'              => static::getTable(),
            'field'              => 'status',
            'datatype'           => 'specific',
            'name'               => __('Approval status'),
            'searchtype'         => 'equals',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '56',
            'table'              => static::getTable(),
            'field'              => 'submission_date',
            'name'               => __('Request date'),
            'datatype'           => 'datetime',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '57',
            'table'              => static::getTable(),
            'field'              => 'validation_date',
            'name'               => __('Approval date'),
            'datatype'           => 'datetime',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '58',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => _n('Requester', 'Requesters', 1),
            'datatype'           => 'itemlink',
            'right'              => (static::$itemtype == 'Ticket' ? 'create_ticket_validate' : 'create_validate'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => static::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '59',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'items_id_target',
            'name'               => __('Approver'),
            'datatype'           => 'itemlink',
            'right'              => static::$itemtype == 'Ticket' ? ['validate_request', 'validate_incident'] : 'validate',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'condition'          => [
                    'REFTABLE.itemtype_target' => User::class,
                ],
                'beforejoin'         => [
                    'table'              => static::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '195',
            'table'              => User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id_substitute',
            'name'               => __('Approver substitute'),
            'datatype'           => 'itemlink',
            'right'              => (static::$itemtype == 'Ticket' ?
                ['validate_request', 'validate_incident'] :
                'validate'
            ),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams' => [
                'beforejoin'         => [
                    'table'          => ValidatorSubstitute::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => [
                            // same condition on search option 197, but with swapped expression
                            // This workarounds identical complex join ID if a search ise both search options 195 and 197
                            [
                                'OR' => [
                                    [
                                        'REFTABLE.substitution_start_date' => null,
                                    ], [
                                        'REFTABLE.substitution_start_date' => ['<=', QueryFunction::now()],
                                    ],
                                ],
                            ], [
                                'OR' => [
                                    [
                                        'REFTABLE.substitution_end_date' => null,
                                    ], [
                                        'REFTABLE.substitution_end_date' => ['>=', QueryFunction::now()],
                                    ],
                                ],
                            ]
                        ],
                        'beforejoin'         => [
                            'table'              => User::getTable(),
                            'linkfield'          => 'items_id_target',
                            'joinparams'             => [
                                'condition'                  => [
                                    'REFTABLE.itemtype_target' => User::class,
                                ],
                                'beforejoin'             => [
                                    'table'                  => static::getTable(),
                                    'joinparams'                 => [
                                        'jointype'                   => 'child',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ];

        $tab[] = [
            'id'                 => '196',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'items_id_target',
            'name'               => __('Approver group'),
            'datatype'           => 'itemlink',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'condition'          => [
                    'REFTABLE.itemtype_target' => Group::class,
                ],
                'beforejoin'         => [
                    'table'              => static::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '197',
            'table'              => User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id_substitute',
            'name'               => __('Substitute of a member of approver group'),
            'datatype'           => 'itemlink',
            'right'              => (static::$itemtype == 'Ticket' ?
                ['validate_request', 'validate_incident'] :
                'validate'
            ),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'          => ValidatorSubstitute::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => [
                            // same condition on search option 195, but with swapped expression
                            // This workarounds identical complex join ID if a search ise both search options 195 and 197
                            [
                                'OR' => [
                                    [
                                        'REFTABLE.substitution_end_date' => null,
                                    ], [
                                        'REFTABLE.substitution_end_date' => ['>=', QueryFunction::now()],
                                    ],
                                ],
                            ], [
                                'OR' => [
                                    [
                                        'REFTABLE.substitution_start_date' => null,
                                    ], [
                                        'REFTABLE.substitution_start_date' => ['<=', QueryFunction::now()],
                                    ],
                                ],
                            ]
                        ],
                        'beforejoin'         => [
                            'table'          => User::getTable(),
                            'joinparams'         => [
                                'beforejoin'         => [
                                    'table'          => Group_User::getTable(),
                                    'joinparams'         => [
                                        'jointype'           => 'child',
                                        'beforejoin'         => [
                                            'table'              => Group::getTable(),
                                            'linkfield'          => 'items_id_target',
                                            'joinparams'         => [
                                                'condition'          => [
                                                    'REFTABLE.itemtype_target' => Group::class,
                                                ],
                                                'beforejoin'         => [
                                                    'table'              => static::getTable(),
                                                    'joinparams'         => [
                                                        'jointype'           => 'child',
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $tab;
    }


    /**
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
            case 'status':
                return self::getStatus($values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @param $field
     * @param $name              (default '')
     * @param $values            (default '')
     * @param $options   array
     **/
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'status':
                $options['value'] = $values[$field];
                return self::dropdownStatus($name, $options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    /**
     * @see commonDBTM::getRights()
     **/
    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        unset($values[UPDATE], $values[READ]);

        $values[self::VALIDATE]  = __('Validate');

        return $values;
    }

    /**
     * Dropdown of validator
     *
     * @param $options   array of options
     *  - prefix                  : inputs prefix
     *                              - an empty prefix will result in having `itemtype` and `items_id` inputs
     *                              - a `_validator` prefix will result in having `_validator[itemtype]` and `_validator[items_id]` inputs
     *  - id                      : ID of object > 0 Update, < 0 New
     *  - entity                  : ID of entity
     *  - right                   : validation rights
     *  - groups_id               : ID of preselected group when validator are users of a same group
     *  - itemtype_target         : Validator itemtype (User or Group)
     *  - items_id_target         : Validator id (can be an array)
     *  - applyto
     *
     * @return string|int Output if $options['display'] is false, else return rand
     *
     * @since 10.1.0 Deprecated usage of 'name' option
     * @since 10.1.0 Deprecated usage of 'users_id_validate' option
     **/
    public static function dropdownValidator(array $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (array_key_exists('name', $options)) {
            Toolbox::deprecated('Usage of "name" option is deprecated in "CommonITILValidation::dropdownValidator()". Use "prefix" instead.');
        }

        if (array_key_exists('users_id_validate', $options)) {
            Toolbox::deprecated('Usage of "users_id_validate" option is deprecated in "CommonITILValidation::dropdownValidator()". Use "itemtype_target"/"items_id_target" instead.');
            if (isset($options['users_id_validate']['groups_id'])) {
                $options['itemtype_target'] = Group::class;
                $options['items_id_target'] = $options['users_id_validate']['groups_id'];
            } else {
                $options['itemtype_target'] = User::class;
                $options['items_id_target'] = is_array($options['users_id_validate'])
                    ? current($options['users_id_validate'])
                    : $options['users_id_validate'];
            }
            unset($options['users_id_validate']);
        }

        $params = [
            'prefix'             => null,
            'id'                 => 0,
            'parents_id'         => null,
            'entity'             => $_SESSION['glpiactive_entity'],
            'right'              => static::$itemtype == 'Ticket' ? ['validate_request', 'validate_incident'] : 'validate',
            'groups_id'          => 0,
            'itemtype_target'    => '',
            'items_id_target'    => 0,
            'users_id_requester' => [],
            'display'            => true,
            'disabled'           => false,
            'readonly'           => false,
            'width'              => '100%',
            'required'           => false,
            'rand'               => mt_rand(),
        ];
        $params['applyto'] = 'show_validator_field' . $params['rand'];

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }
        if (!is_array($params['users_id_requester'])) {
            $params['users_id_requester'] = [$params['users_id_requester']];
        }

        $params['validation_class'] = static::class;

        $validatortype = array_key_exists('groups_id', $options) && !empty($options['groups_id'])
            ? 'Group_User'
            : $options['itemtype_target'];

        $validatortype_name = array_key_exists('name', $params)
            ? 'validatortype' // legacy behaviour, remove it in GLPI 11.0
            : $params['prefix'] . '[validatortype]';

        // Build list of available dropdown items
        $validators = [
            'User'       => User::getTypeName(1),
            'Group_User' => __('Group user(s)'),
            'Group'      => Group::getTypeName(1),
        ];

        $out = Dropdown::showFromArray($validatortype_name, $validators, [
            'value'               => $validatortype,
            'display_emptychoice' => true,
            'display'             => false,
            'disabled'            => $params['disabled'],
            'readonly'            => $params['readonly'],
            'rand'                => $params['rand'],
            'width'               => $params['width'],
            'required'            => $params['required'],
        ]);

        if ($validatortype) {
            $out .= Ajax::updateItem(
                $params['applyto'],
                $CFG_GLPI["root_doc"] . "/ajax/dropdownValidator.php",
                array_merge($params, ['validatortype' => $validatortype]),
                "",
                false
            );
        }
        $out .= Ajax::updateItemOnSelectEvent(
            "dropdown_{$validatortype_name}{$params['rand']}",
            $params['applyto'],
            $CFG_GLPI["root_doc"] . "/ajax/dropdownValidator.php",
            array_merge($params, ['validatortype' => '__VALUE__']),
            false
        );

        if (!isset($options['applyto'])) {
            $out .= "<br><span id='" . $params['applyto'] . "'>&nbsp;</span>\n";
        }

        if ($params['display']) {
            echo $out;
            return $params['rand'];
        } else {
            return $out;
        }
    }


    /**
     * Get list of users from a group which have validation rights
     *
     * @param $options   array   possible:
     *       groups_id
     *       right
     *       entity
     *
     * @return array
     **/
    public static function getGroupUserHaveRights(array $options = [])
    {
        $params = [
            'entity' => $_SESSION['glpiactive_entity'],
        ];
        if (static::$itemtype == 'Ticket') {
            $params['right']  = ['validate_request', 'validate_incident'];
        } else {
            $params['right']  = ['validate'];
        }
        $params['groups_id'] = 0;

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }

        $list       = [];
        $restrict   = [];

        $res = User::getSqlSearchResult(false, $params['right'], $params['entity']);
        foreach ($res as $data) {
            $list[] = $data['id'];
        }
        if (count($list) > 0) {
            $restrict = ['glpi_users.id' => $list];
        }
        $users = Group_User::getGroupUsers($params['groups_id'], $restrict);

        return $users;
    }


    /**
     * Compute the validation status
     *
     * @param $item CommonITILObject
     *
     * @return integer
     **/
    public static function computeValidationStatus(CommonITILObject $item)
    {

       // Percent of validation
        $validation_percent = $item->fields['validation_percent'];

        $statuses           = [self::ACCEPTED => 0,
            self::WAITING  => 0,
            self::REFUSED  => 0
        ];
        $validations        = getAllDataFromTable(
            static::getTable(),
            [
                static::$items_id => $item->getID()
            ]
        );

        if ($total = count($validations)) {
            foreach ($validations as $validation) {
                $statuses[$validation['status']] ++;
            }
        }

        return self::computeValidation(
            round($statuses[self::ACCEPTED] * 100 / $total),
            round($statuses[self::REFUSED]  * 100 / $total),
            $validation_percent
        );
    }

    /**
     * Compute the validation status from the percentage of acceptation, the
     * percentage of refusals and the target acceptation threshold
     *
     * @param int $accepted             0-100 (percentage of acceptation)
     * @param int $refused              0-100 (percentage of refusals)
     * @param int $validation_percent   0-100 (target accepation threshold)
     *
     * @return int the validation status : ACCEPTED|REFUSED|WAITING
     */
    public static function computeValidation(
        int $accepted,
        int $refused,
        int $validation_percent
    ): int {
        if ($validation_percent > 0) {
            if ($accepted >= $validation_percent) {
                // We have reached the acceptation threshold
                return self::ACCEPTED;
            } else if ($refused + $validation_percent > 100) {
               // We can no longer reach the acceptation threshold
                return self::REFUSED;
            }
        } else {
           // No validation threshold set, one approval or denial is enough
            if ($accepted > 0) {
                return self::ACCEPTED;
            } else if ($refused > 0) {
                return self::REFUSED;
            }
        }

        return self::WAITING;
    }


    /**
     * Get the validation statistics
     *
     * @param integer $tID tickets id
     *
     * @return string
     **/
    public static function getValidationStats($tID)
    {

        $tab = self::getAllStatusArray();

        $nb  = countElementsInTable(static::getTable(), [static::$items_id => $tID]);

        $stats = [];
        foreach (array_keys($tab) as $status) {
            $validations = countElementsInTable(static::getTable(), [static::$items_id => $tID,
                'status'          => $status
            ]);
            if ($validations > 0) {
                if (!isset($stats[$status])) {
                    $stats[$status] = 0;
                }
                 $stats[$status] = $validations;
            }
        }

        $list = "";
        foreach ($stats as $stat => $val) {
            $list .= $tab[$stat];
            $list .= sprintf(__('%1$s (%2$d%%) '), " ", Html::formatNumber($val * 100 / $nb));
        }

        return $list;
    }


    /**
     * @param $item       CommonITILObject
     * @param $type
     */
    public static function alertValidation(CommonITILObject $item, $type)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       // No alert for new item
        if ($item->isNewID($item->getID())) {
            return;
        }
        $status  = array_merge($item->getClosedStatusArray(), $item->getSolvedStatusArray());

        $message = __s("This item is waiting for approval, do you really want to resolve or close it?");

        switch ($type) {
            case 'status':
                $jsScript = "
               $(document).ready(
                  function() {
                     $('[name=\"status\"]').change(function() {
                        var status_ko = 0;
                        var input_status = $(this).val();
                        if (input_status != undefined) {
                           if ((";
                $first = true;
                foreach ($status as $val) {
                    if (!$first) {
                        $jsScript .= "||";
                    }
                    $jsScript .= "input_status == $val";
                    $first = false;
                }
                $jsScript .= "           )
                                 && input_status != " . $item->fields['status'] . "){
                              status_ko = 1;
                           }
                        }
                        if ((status_ko == 1)
                            && ('" . ($item->fields['global_validation'] ?? '') . "' == '" . self::WAITING . "')) {
                           alert('" . $message . "');
                        }
                     });
                  }
               );";
                echo Html::scriptBlock($jsScript);
                break;

            case 'solution':
                if (
                    !in_array($item->fields['status'], $status)
                    && isset($item->fields['global_validation'])
                    && $item->fields['global_validation'] == self::WAITING
                ) {
                    $title   = __s("This item is waiting for approval.");
                    $message = __s("Do you really want to resolve or close it?");
                    ;
                    $html = <<<HTML
                  <div class="alert alert-warning" role="alert">
                     <div class="d-flex">
                        <div class="me-2">
                           <i class="fas fa-2x fa-exclamation-triangle"></i>
                        </div>
                        <div>
                           <h4 class="alert-title">$title</h4>
                           <div class="text-muted">$message</div>
                        </div>
                     </div>
                  </div>
HTML;
                    echo $html;
                }
                break;
        }
    }


    /**
     * Get the ITIL object can validation status list
     *
     * @since 0.85
     *
     * @return array
     **/
    public static function getCanValidationStatusArray()
    {
        return [self::NONE, self::ACCEPTED];
    }


    /**
     * Get the ITIL object all validation status list
     *
     * @since 0.85
     *
     * @return array
     **/
    public static function getAllValidationStatusArray()
    {
        return [self::NONE, self::WAITING, self::REFUSED, self::ACCEPTED];
    }
}

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
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\RichText\RichText;
use Glpi\RichText\UserMention;

use function Safe\json_encode;

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

    public const VALIDATE               = 1024;


    // STATUSES
    public const NONE      = 1; // used for ticket.global_validation
    public const WAITING   = 2;
    public const ACCEPTED  = 3;
    public const REFUSED   = 4;

    public static function getIcon()
    {
        return 'ti ti-thumb-up';
    }

    public static function getItilObjectItemType()
    {
        return str_replace('Validation', '', static::class);
    }

    public static function getItilObjectItemInstance(): CommonITILObject
    {
        $class = static::getItilObjectItemType();

        if (!is_a($class, CommonITILObject::class, true)) {
            throw new LogicException();
        }

        return new $class();
    }

    /**
     * @return class-string<ITIL_ValidationStep>|null
     */
    public static function getValidationStepClassName(): ?string
    {
        $validation_class = static::class . 'Step';
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


    public static function canCreate(): bool
    {
        return Session::haveRightsOr(static::$rightname, static::getCreateRights());
    }


    /**
     * Is the current user have right to delete the current validation ?
     *
     * @return boolean
     **/
    public function canCreateItem(): bool
    {

        if (
            ($this->fields["users_id"] == Session::getLoginUserID())
            || Session::haveRightsOr(static::$rightname, static::getCreateRights())
        ) {
            return true;
        }
        return false;
    }


    public static function canView(): bool
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


    public static function canUpdate(): bool
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
    public function canDeleteItem(): bool
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
    public function canUpdateItem(): bool
    {
        if (
            !$this->canAnswer()
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
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [static::getTable() . '.id'],
            'FROM'   => static::getTable(),
            'WHERE'  => [
                static::$items_id => $items_id,
                static::getTargetCriteriaForUser(Session::getLoginUserID()),
            ],
            'START'  => 0,
            'LIMIT'  => 1,
        ]);
        return count($iterator) > 0;
    }

    /**
     * Indicates whether the current connected user can answer the validation.
     */
    final public function canAnswer(): bool
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [static::getTable() . '.id'],
            'FROM'   => static::getTable(),
            'WHERE'  => [
                'id' => $this->getID(),
                static::getTargetCriteriaForUser(Session::getLoginUserID()),
            ],
            'START'  => 0,
            'LIMIT'  => 1,
        ]);
        return count($iterator) > 0;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
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
        if (!$item instanceof CommonITILObject) {
            return false;
        }

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
        // validation step is mandatory : add default value is not set
        if (!isset($input['itils_validationsteps_id']) && !isset($input['_validationsteps_id'])) {
            $input['_validationsteps_id'] = ValidationStep::getDefault()->getID();
        }

        $input = $this->addITILValidationStepFromInput($input);

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

        $itil_class = static::getItilObjectItemType();
        $itil_fkey  = $itil_class::getForeignKeyField();
        $input['timeline_position'] = $itil_class::getTimelinePosition($input[$itil_fkey], static::class, $input["users_id"]);

        return parent::prepareInputForAdd($input);
    }

    public function post_addItem()
    {
        global $CFG_GLPI;

        $itilobject = $this->getItem();
        $this->checkIsAnItilObject($itilobject);

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

        // --- update item (ITILObject) handling the validation
        // always recompute global validation status on ticket
        $input = [
            'id' => $itilobject->getID(),
            'global_validation' => static::computeValidationStatus($itilobject),
            '_from_itilvalidation' => true,
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
        $itilobject->update($input);

        // -- send email notification
        $mailsend = false;
        if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            $options = ['validation_id' => $this->fields["id"],
                'validation_status' => $this->fields["status"],
            ];
            $mailsend = NotificationEvent::raiseEvent('validation', $itilobject, $options, $this);
        }
        if ($mailsend) {
            if ($this->fields['itemtype_target'] === 'User') {
                $user = new User();
                $user->getFromDB($this->fields["items_id_target"]);
                $email = $user->getDefaultEmail();
                if (!empty($email)) {
                    Session::addMessageAfterRedirect(htmlescape(sprintf(__('Approval request sent to %s'), $user->getName())));
                } else {
                    Session::addMessageAfterRedirect(
                        htmlescape(sprintf(
                            __('The selected user (%s) has no valid email address. The request has been created, without email confirmation.'),
                            $user->getName()
                        )),
                        false,
                        ERROR
                    );
                }
            } elseif (is_a($this->fields["itemtype_target"], CommonDBTM::class, true)) {
                $target = new $this->fields["itemtype_target"]();
                if ($target->getFromDB($this->fields["items_id_target"])) {
                    Session::addMessageAfterRedirect(htmlescape(sprintf(__('Approval request sent to %s'), $target->getName())));
                }
            }
        }
        parent::post_addItem();
    }


    public function prepareInputForUpdate($input)
    {
        $can_answer = $this->canAnswer();
        // Don't allow changing internal entity fields or change the item it is attached to
        $forbid_fields = ['entities_id', static::$items_id, 'is_recursive'];
        // The following fields shouldn't be changed by anyone after the approval is created
        $forbid_fields[] = 'itils_validationsteps_id';
        $forbid_fields[] = 'users_id';
        $forbid_fields[] = 'itemtype_target';
        $forbid_fields[] = 'items_id_target';
        $forbid_fields[] = 'submission_date';

        if (!$can_answer) {
            $forbid_fields[] = 'status';
            $forbid_fields[] = 'comment_validation';
            $forbid_fields[] = 'validation_date';
        }

        if ($this->fields["status"] !== self::WAITING) {
            // Cannot change the approval request comment after it has been answered
            $forbid_fields[] = 'comment_submission';
        }

        foreach ($forbid_fields as $key) {
            unset($input[$key]);
        }

        if (isset($input["status"])) {
            if (
                ($input["status"] == self::REFUSED)
                && (!isset($input["comment_validation"])
                 || ($input["comment_validation"] == ''))
            ) {
                Session::addMessageAfterRedirect(
                    __s('If approval is denied, specify a reason.'),
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
        }

        return parent::prepareInputForUpdate($input);
    }

    public function post_purgeItem()
    {
        $this->recomputeItilStatus();
        $this->removeUnsedITILValidationStep();

        parent::post_purgeItem();
    }

    public function post_updateItem($history = true)
    {
        global $CFG_GLPI;

        $this->recomputeItilStatus();

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

        // -- notifications
        if (
            count($this->updates)
            && $donotif
        ) {
            $options  = ['validation_id'     => $this->fields["id"],
                'validation_status' => $this->fields["status"],
            ];
            NotificationEvent::raiseEvent('validation_answer', $this->getItem(), $options, $this);
        }

        parent::post_updateItem($history);
    }

    public function post_deleteItem()
    {
        $item = $this->getItem();
        if ($item instanceof CommonITILObject) {
            $input = [
                'id'                    => $item->getID(),
                'global_validation'     => static::computeValidationStatus($item),
                '_from_itilvalidation'  => true,
            ];

            if (!$item->update($input)) {
                throw new RuntimeException(sprintf('Failed to update related `%s` approval status.', $item::class));
            }
        }
    }


    /**
     * @see CommonDBConnexity::getHistoryChangeWhenUpdateField
     **/
    public function getHistoryChangeWhenUpdateField($field)
    {
        $result = [];
        if ($field == 'status') {
            $result   = ['0', '', ''];
            if ($this->fields["status"] == self::ACCEPTED) {
                //TRANS: %s is the username
                $result[2] = sprintf(__('Approval granted by %s'), getUserName($this->fields["users_id_validate"]));
            } else {
                //TRANS: %s is the username
                $result[2] = sprintf(__('Update the approval request to %s'), $this->getTargetName());
            }
        }
        return $result;
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
            self::ACCEPTED => __('Granted'),
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
            $classes = null;
            switch ($value) {
                case self::WAITING:
                    $classes = 'waiting ti ti-clock';
                    break;
                case self::ACCEPTED:
                    $classes = 'accepted ti ti-check';
                    break;
                case self::REFUSED:
                    $classes = 'refused ti ti-x';
                    break;
            }

            return sprintf('<span><i class="validationstatus %s"></i> %s</span>', $classes, htmlescape($label));
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
                $style = "#ff0000";
                break;

            case self::ACCEPTED:
                $style = "#43e900";
                break;

            default:
                $style = "#ff0000";
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
        global $DB;

        $itil_class = static::getItilObjectItemType();

        $it = $DB->request([
            'FROM'   => $itil_class::getTable(),
            'COUNT'  => 'cpt',
            'WHERE'  => [
                [
                    'id' => new QuerySubQuery([
                        'SELECT' => $itil_class::getForeignKeyField(),
                        'FROM'   => static::getTable(),
                        'WHERE'  => [
                            'status' => self::WAITING,
                            static::getTargetCriteriaForUser($users_id),
                        ],
                    ]),
                ],
                'NOT' => [
                    'status' => [...$itil_class::getSolvedStatusArray(), ...$itil_class::getClosedStatusArray()],
                ],
            ],
        ]);

        return $it->current()['cpt'];
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
                            ],
                        ],
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
                        ]),
                    ],
                ],
            ];
        }

        return $target_criteria;
    }

    /**
     * Form for Followup on Massive action
     **/
    public static function showFormMassiveAction()
    {

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
            'right'            => static::$itemtype == 'Ticket' ? ['validate_request', 'validate_incident'] : 'validate',
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown_validatortype$rand",
            "show_massiveaction_field",
            $CFG_GLPI["root_doc"]
                                       . "/ajax/dropdownMassiveActionAddValidator.php",
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
                            'comment_submission'   => $input['comment_submission'],
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
     * Print validations summary (list of validations of the ITIL object)
     */
    private function showSummary(CommonITILObject $itil): void
    {
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
            return;
        }

        $rand = mt_rand();
        $validation_steps_classname = static::getValidationStepClassName();

        $values = [];
        $validation_steps_iterator = $DB->request(
            [
                'FROM'  => $validation_steps_classname::getTable(),
                'WHERE' => [
                    'itemtype' => $itil::class,
                    'items_id' => $itil->getID(),
                ],
            ]
        );

        foreach ($validation_steps_iterator as $validation_step_data) {
            $validation_step_id = $validation_step_data['id'];

            $validation_step = static::getValidationStepInstance();
            $validation_step->getFromDB($validation_step_id);

            $step_name          = Dropdown::getDropdownName(ValidationStep::getTable(), $validation_step_data['validationsteps_id']);
            $step_status        = $validation_step->getStatus();
            $step_achievements  = $validation_step->getAchievements();
            $step_threshold     = $validation_step->fields['minimal_required_validation_percent'];
            $edit_dialog_params = [
                "url"    => $CFG_GLPI['root_doc'] . '/ajax/viewsubitem.php',
                "params" => [
                    'type'                      => $validation_steps_classname,
                    'parenttype'                => $itil::class,
                    $itil::getForeignKeyField() => $itil->getID(),
                    'id'                        => $validation_step_id,
                ],
            ];

            $step_row_html = TemplateRenderer::getInstance()->renderFromStringTemplate(
                <<<TWIG
                    {% macro stacked_progressbar(achieved, bg_color_class, stripped = false) %}
                        <div class="progress" style="width: {{ achieved }}%">
                            <div
                                    class="progress-bar {% if stripped %}progress-bar-striped progress-bar-animated{% endif %} {{ bg_color_class }}"
                                    role="progressbar"
                                    aria-valuenow="{{ achieved }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-label="{{ achieved|formatted_number }}%"
                            >
                                <span class="visually-hidden">{{ achieved|formatted_number }}%</span>
                            </div>
                        </div>
                    {% endmacro %}
                    <div class="d-flex align-items-center gap-2 mx-auto" style="max-width: 650px;">
                        <div class="flex-shrink-0"><strong>{{ step_name }}</strong></div>
                        <div class="flex-shrink-0">
                            {% if step_status == constant('CommonITILValidation::ACCEPTED') %}
                                <span class="text-green" data-bs-toogle="tooltip" title="{{ accepted_label }}">
                                    <i class="ti ti-check"></i>
                                </span>
                            {% elseif step_status == constant('CommonITILValidation::REFUSED') %}
                                <span class="text-red" data-bs-toggle="tooltip" title="{{ refused_label }}">
                                    <i class="ti ti-ban"></i>
                                </span>
                            {% elseif step_status == constant('CommonITILValidation::WAITING') %}
                                <span class="text-yellow" data-bs-toggle="tooltip" title="{{ pending_label }}">
                                    <i class="ti ti-clock"></i>
                                </span>
                            {% endif %}
                        </div>
                        <div class="flex-grow-1">
                            <div class="progress-stacked position-relative" data-bs-toggle="tooltip"
                                 title="{{ progress_label|format(accepted_percent|formatted_number, step_threshold|formatted_number) }}">
                                {{ _self.stacked_progressbar(accepted_percent, 'bg-green') }}
                                {{ _self.stacked_progressbar(waiting_percent, 'bg-yellow', true) }}
                                {{ _self.stacked_progressbar(refused_percent, 'bg-red') }}
                                {# threshold  #}
                                {# sligly move the indicator on edge case (0|100) to be visible #}
                                {% if step_threshold == 0 %}
                                    <div class="threshold-indicator" style="position: absolute; width: 5px; height: 100%; background-color: black; left: 0; top: 0; z-index: 10;"></div>
                                {% elseif step_threshold == 100 %}
                                    <div class="threshold-indicator" style="position: absolute; width: 5px; height: 100%; background-color: black; right: 0; top: 0; z-index: 10;"></div>
                                {% else %}
                                    <div class="threshold-indicator" style="position: absolute; width: 3px; height: 100%; background-color: black; left: {{ step_threshold }}%; top: 0; z-index: 10;"></div>
                                {% endif %}
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="ti ti-edit"
                               role="button"
                               title="{{ edit_button_label }}"
                               onclick="glpi_ajax_dialog({{ edit_dialog_params|json_encode }});"
                            >
                                <span class="sr-only">{{ edit_button_label }}</span>
                            </span>
                        </div>
                    </div>
                TWIG,
                [
                    'step_id'            => $validation_step_id,
                    'step_name'          => $step_name,
                    'step_status'        => $step_status,
                    'accepted_percent'   => $step_achievements[self::ACCEPTED],
                    'refused_percent'    => $step_achievements[self::REFUSED],
                    'waiting_percent'    => $step_achievements[self::WAITING],
                    'step_threshold'     => $step_threshold,
                    'edit_dialog_params' => $edit_dialog_params,
                    'edit_button_label'  => __('Edit approval step'),
                    'progress_label'     => __('Progress: %1$s%% of %2$s%% required'),
                    'accepted_label'     => __('Approval step accepted'),
                    'refused_label'      => __('Approval step refused'),
                    'pending_label'      => __('Approval step pending'),
                ]
            );

            $values[] = [
                'row_class'          => 'table-light',
                'showmassiveactions' => false,
                'edit_colspan'       => 10,
                'edit'               => $step_row_html,
            ];

            $validation_iterator = $DB->request([
                'FROM'  => $this->getTable(),
                'WHERE' => ['itils_validationsteps_id' => $validation_step_id],
                'ORDER' => ['submission_date DESC'],
            ]);

            foreach ($validation_iterator as $row) {
                $canedit = $this->canEdit($row["id"]);
                $status  = sprintf(
                    '<div class="badge fw-normal fs-4 text-wrap" style="border-color: %s;border-width: 2px;">%s</div>',
                    htmlescape(self::getStatusColor($row['status'])),
                    htmlescape(self::getStatus($row['status']))
                );

                $comment_submission = RichText::getEnhancedHtml($this->fields['comment_submission'], ['images_gallery' => true]);
                $type_name   = null;
                $target_name = null;
                if ($row["itemtype_target"] === User::class) {
                    $type_name   = User::getTypeName();
                    $target_name = getUserName($row["items_id_target"]);
                } elseif (is_a($row["itemtype_target"], CommonDBTM::class, true)) {
                    $target = new $row["itemtype_target"]();
                    $type_name = $target::getTypeName();
                    if ($target->getFromDB($row["items_id_target"])) {
                        $target_name = $target->getName();
                    }
                }
                $is_answered = $row['status'] !== self::WAITING && $row['users_id_validate'] > 0;
                $comment_validation = RichText::getEnhancedHtml($this->fields['comment_validation'] ?? '', ['images_gallery' => true]);

                $doc_item = new Document_Item();
                $docs = $doc_item->find([
                    "itemtype"          => static::class,
                    "items_id"           => $this->getID(),
                    "timeline_position"  => ['>', CommonITILObject::NO_TIMELINE],
                ]);

                $document = "";
                foreach ($docs as $docs_values) {
                    $doc = new Document();
                    if ($doc->getFromDB($docs_values['documents_id'])) {
                        $document .= sprintf(
                            '<a href="%s">%s</a><br />',
                            htmlescape($doc->getLinkURL()),
                            htmlescape($doc->getName())
                        );
                    }
                }

                $script = "";
                if ($canedit) {
                    $edit_title = __s('Edit');
                    $item_id = (int) $itil->fields['id'];
                    $row_id = (int) $row["id"];
                    $params_json = json_encode([
                        'type'             => static::class,
                        'parenttype'       => static::$itemtype,
                        static::$items_id  => $this->fields[static::$items_id],
                        'id'               => $row["id"],
                    ]);

                    $rand_id = htmlescape($item_id . $row_id . $rand);

                    $script = <<<HTML
                        <span class="ti ti-edit" style="cursor:pointer" title="{$edit_title}"
                              onclick="viewEditValidation{$rand_id}();"
                              id="viewvalidation{$rand_id}">
                        </span>
                        <script>
                            function viewEditValidation{$rand_id}() {
                                glpi_ajax_dialog({
                                    url: CFG_GLPI.root_doc + "/ajax/viewsubitem.php",
                                    modalclass: 'modal-xl',
                                    params: $params_json,
                                });
                            };
                        </script>
HTML;
                }

                $values[] = [
                    'edit'                  => $script,
                    'status'                => $status,
                    'type_name'             => $type_name,
                    'target_name'           => $target_name,
                    'is_answered'           => $is_answered,
                    'comment_submission'    => $comment_submission,
                    'comment_validation'    => $comment_validation,
                    'document'              => $document,
                    'submission_date'       => $row["submission_date"],
                    'validation_date'       => $row["validation_date"],
                    'user'                  => getUserName($row["users_id"]),
                ];

            }
        }

        $can_input = [static::$items_id => $itil->getID()];
        TemplateRenderer::getInstance()->display('components/itilobject/validation.html.twig', [
            'canadd' => $this->can(-1, CREATE, $can_input),
            'item' => $itil,
            'itemtype' => static::$itemtype,
            'tID' => $itil->getID(),
            'donestatus' => array_merge($itil->getSolvedStatusArray(), $itil->getClosedStatusArray()),
            'validation' => $this,
            'rand' => $rand,
            'items_id' => static::$items_id,
        ]);

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'edit' => '',
                'status' => _x('item', 'State'),
                'submission_date' => __('Request date'),
                'user' => __('Approval requester'),
                'comment_submission' => __('Request comments'),
                'validation_date' => __('Approval date'),
                'type_name' => __('Requested approver type'),
                'target_name' => __('Requested approver'),
                'comment_validation' => __('Approval Comment'),
                'document' => __('Documents'),
            ],
            'formatters' => [
                'edit' => 'raw_html',
                'status' => 'raw_html',
                'submission_date' => 'date',
                'comment_submission' => 'raw_html',
                'validation_date' => 'date',
                'comment_validation' => 'raw_html',
                'document' => 'raw_html',
            ],
            'entries' => $values,
            'total_number' => count($values),
            'showmassiveactions' => false,
        ]);
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

        /** @var CommonITILObject $itil */
        $itil = $this->getItem();

        $ivs = $itil::getValidationStepInstance();
        $ivs->getFromDB($this->fields['itils_validationsteps_id']);
        $validationsteps_id = $ivs->fields['validationsteps_id'] ?? ValidationStep::getDefault()->getID();

        $mention_options = UserMention::getMentionOptions($itil);

        TemplateRenderer::getInstance()->display('components/itilobject/timeline/form_validation.html.twig', [
            'item'                => $itil, // ItilObject
            'subitem'             => $this, // Validation
            'scroll'              => true,
            'mention_options'     => $mention_options,
            '_validationsteps_id' => $validationsteps_id,
        ]);

        return true;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $table = static::getTable();

        $tab[] = [
            'id'                 => 'common',
            'name'               => CommonITILValidation::getTypeName(1),
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
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $table,
            'field'              => 'comment_validation',
            'name'               => __('Approval comments'),
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $table,
            'field'              => 'status',
            'name'               => __('Status'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $table,
            'field'              => 'submission_date',
            'name'               => __('Request date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $table,
            'field'              => 'validation_date',
            'name'               => __('Approval date'),
            'datatype'           => 'datetime',
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
            'name'               => CommonITILValidation::getTypeName(1),
        ];

        $tab[] = [
            'id'                 => '52',
            'table'              => getTableForItemType(static::$itemtype),
            'field'              => 'global_validation',
            'name'               => CommonITILValidation::getTypeName(1),
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
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
                'jointype'           => 'child',
            ],
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
                'jointype'           => 'child',
            ],
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
                'jointype'           => 'child',
            ],
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
                'jointype'           => 'child',
            ],
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
                'jointype'           => 'child',
            ],
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
                        'jointype'           => 'child',
                    ],
                ],
            ],
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
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '195',
            'table'              => User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id_substitute',
            'name'               => __('Approver substitute'),
            'datatype'           => 'itemlink',
            'right'              => (
                static::$itemtype == 'Ticket'
                ? ['validate_request', 'validate_incident']
                : 'validate'
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
                            ],
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
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
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
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '197',
            'table'              => User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id_substitute',
            'name'               => __('Substitute of a member of approver group'),
            'datatype'           => 'itemlink',
            'right'              => (
                static::$itemtype == 'Ticket'
                ? ['validate_request', 'validate_incident']
                : 'validate'
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
                            ],
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
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '198',
            'table'              => static::getTable(),
            'field'              => 'status',
            'datatype'           => 'specific',
            'name'               => __('Approval status by users'),
            'searchtype'         => 'equals',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'additionalfields'   => ['itemtype_target', 'items_id_target'],
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        if ($field === 'status') {
            $out = '';
            $targets = $values;
            if (array_key_exists('status', $targets)) {
                // single value
                $targets = [$values];
            }
            foreach ($targets as $target) {
                if (!empty($target['status'])) {
                    $status  = \htmlescape(static::getStatus($target['status']));
                    $bgcolor = \htmlescape(static::getStatusColor($target['status']));
                    $content = "<div class='badge_block' style='border-color: $bgcolor'><span style='background: $bgcolor'></span>&nbsp;" . $status . "</div>";
                    if (isset($target['itemtype_target']) && is_a($target['itemtype_target'], CommonDBTM::class, true) && isset($target['items_id_target'])) {
                        $user = '';
                        if (($approver = $target['itemtype_target']::getById((int) $target['items_id_target'])) !== null) {
                            $user = $approver->getLink();
                        }
                        $text = "<i class='" . \htmlescape($target['itemtype_target']::getIcon()) . " me-1'></i>" . $user . '<span class="mx-1">-</span>' . $status;
                        $content = "<div class='badge_block' style='border-color: $bgcolor'><span style='background: $bgcolor'></span>&nbsp;" . $text . "</div>";
                    }
                    $out .= (empty($out) ? '' : Search::LBBR) . $content;
                }
            }
            return $out;
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
     * Display (or return) html fragment with a select element plus the javascript that will trigger an ajax request to populate the options.
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
     **/
    public static function dropdownValidator(array $options = [])
    {
        global $CFG_GLPI;

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

        $validatortype_name = $params['prefix'] . '[validatortype]';

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
            'aria_label'          => __('Approver type'),
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
            $out .= "<br><span id='" . htmlescape($params['applyto']) . "'>&nbsp;</span>\n";
        }

        if ($params['display']) {
            echo $out;
            return (int) $params['rand'];
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
     * Reduced all the Validations of an item to a single status
     *
     * @param $itil CommonITILObject
     **@return int CommonITILValidation::VALIDATE|CommonITILValidation::REFUSED|CommonITILValidation::WAITING|CommonITILValidation::NONE
     */
    public static function computeValidationStatus(CommonITILObject $itil): int
    {
        $vs = $itil->getValidationStepInstance();
        return $vs::getValidationStatusForITIL($itil);
    }

    /**
     * @param $item       CommonITILObject
     * @param $type
     *
     * Used in twig template
     */
    public static function alertValidation(CommonITILObject $item, $type)
    {
        global $CFG_GLPI;

        // No alert for new item
        if ($item->isNewID($item->getID())) {
            return;
        }
        $status  = array_merge($item->getClosedStatusArray(), $item->getSolvedStatusArray());

        switch ($type) {
            case 'status':
                $message = __("This item is waiting for approval, do you really want to resolve or close it?");
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
                           alert('" . jsescape($message) . "');
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
                           <i class="ti ti-alert-triangle fs-2x"></i>
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

    /**
     * Associate the validation with an "itil validation step" created from an exiting "validation step"
     *
     * If no itils_validationsteps is defined for the itilobject, create it
     * else, refererence it.
     */
    private function addITILValidationStepFromInput(array $input): array
    {
        $itil_class = static::getItilObjectItemType(); // Change | Ticket
        $itil_fkey  = $itil_class::getForeignKeyField(); // changes_id | tickets_id

        if (!array_key_exists('_validationsteps_id', $input) || !array_key_exists($itil_fkey, $input)) {
            return $input;
        }

        $relation_fields = [
            'itemtype'           => $itil_class,
            'items_id'           => $input[$itil_fkey],
            'validationsteps_id' => $input['_validationsteps_id'],
        ];

        $itil_validationstep = $itil_class::getValidationStepInstance();
        if (!$itil_validationstep->getFromDBByCrit($relation_fields)) {
            $validationstep = new ValidationStep();
            if (!$validationstep->getFromDB($input['_validationsteps_id'])) {
                throw new RuntimeException('Failed to get validation step with id #' . $input['_validationsteps_id']);
            };

            $step_input = $relation_fields + [
                'minimal_required_validation_percent' => $validationstep->fields['minimal_required_validation_percent'],
            ];

            if (!$itil_validationstep->add($step_input)) {
                throw new RuntimeException('Failed to create approval step of type ' . get_class($itil_validationstep));
            }
        }

        $input['itils_validationsteps_id'] = $itil_validationstep->getID();
        unset($input['_validationsteps_id']);

        return $input;
    }

    /**
     * Delete, only if the itils_validationstep is not used anymore
     *
     * @return void
     */
    private function removeUnsedITILValidationStep(): void
    {
        $itils_validationsteps_id = $this->fields['itils_validationsteps_id'];

        $validations = (new static())->find(['itils_validationsteps_id' => $itils_validationsteps_id]);
        if (!empty($validations)) {
            // itils_validation is still used, do not delete
            return;
        }

        $itil_validationstep = static::getItilObjectItemType()::getValidationStepInstance();
        if (!$itil_validationstep->delete(['id' => $itils_validationsteps_id])) {
            throw new RuntimeException('Failed to delete unused approval step.');
        };
    }

    public function recomputeItilStatus(): void
    {
        $itil_object = $this->getItem();
        $this->checkIsAnItilObject($itil_object);

        $update = $itil_object->update([
            'id' => $itil_object->getID(),
            'global_validation' => self::computeValidationStatus($itil_object),
            '_from_itilvalidation' => true,
        ]);
        if (!$update) {
            throw new RuntimeException('Failed to update Itil global approval status.');
        }
    }

    /**
     * @throws RuntimeException
     * @phpstan-assert CommonITILObject $itilobject
     */
    private function checkIsAnItilObject(false|CommonDBTM $itilobject): void
    {
        if (!($itilobject instanceof CommonITILObject)) {
            throw new RuntimeException('Validation must be linked to an ITIL object. ' . ($itilobject === false ? 'false' : get_class($itilobject)) . ' given.');
        }
    }
}

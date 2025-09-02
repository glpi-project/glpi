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

/**
 * UserEmail class
 **/
class UserEmail extends CommonDBChild
{
    // From CommonDBTM
    public $auto_message_on_action = false;

    // From CommonDBChild
    public static $itemtype        = 'User';
    public static $items_id        = 'users_id';
    public $dohistory              = true;


    public static function getTypeName($nb = 0)
    {
        return _n('Email', 'Emails', $nb);
    }

    public function canChildItem($methodItem, $methodNotItem)
    {
        $users_id = $this->input['users_id'] ?? $this->fields['users_id'] ?? null;
        if ($users_id !== null && !$this->canAlterUserEmails((int) $users_id)) {
            return false;
        }

        return parent::canChildItem($methodItem, $methodNotItem);
    }

    /**
     * Indicates whether the current user can alter the email addresses from the target user.
     *
     * @param int $target_user_id
     * @return bool
     */
    private function canAlterUserEmails(int $target_user_id): bool
    {
        $session_user_id = Session::getLoginUserID();

        if ($session_user_id === false) {
            // No active user session, action is made by a cron or a system routine, no need to check.
            return true;
        }

        if ($target_user_id === $session_user_id) {
            // Email is attached to the current user, no need to check.
            return true;
        }

        // Current user can alter target user's emails only if he has more rights.
        $user = new User();
        return $user->currentUserHaveMoreRightThan($target_user_id);
    }

    /**
     * Get default email for user. If no default email get first one
     *
     * @param int $users_id user ID
     *
     * @return string default email, empty if no email set
     **/
    public static function getDefaultForUser($users_id)
    {
        global $DB;

        // Get default one
        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'users_id'     => $users_id,
            ],
            'ORDER'  => 'is_default DESC',
            'LIMIT'  => 1,
        ]);

        foreach ($iterator as $row) {
            return $row['email'];
        }

        return '';
    }


    /**
     * Get all emails for user.
     *
     * @param $users_id user ID
     *
     * @return array of emails
     **/
    public static function getAllForUser($users_id)
    {
        global $DB;

        $emails = [];

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'users_id'     => $users_id,
            ],
        ]);

        foreach ($iterator as $row) {
            $emails[] = $row['email'];
        }

        return $emails;
    }


    /**
     * is an email of the user
     *
     * @param $users_id           user ID
     * @param $email     string   email to check user ID
     *
     * @return boolean is this email set for the user ?
     **/
    public static function isEmailForUser($users_id, $email)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'users_id'  => $users_id,
                'email'     => $email,
            ],
            'LIMIT'  => 1,
        ]);

        if (count($iterator)) {
            return true;
        }

        return false;
    }

    #[Override()]
    public static function getJSCodeToAddForItemChild($field_name, $child_count_js_var)
    {
        $html = "<div class='d-flex'>"
            . "<input title='" . __s('Default email') . "' type='radio' name='_default_email' value='-__JS_PLACEHOLDER__' aria-label='" . __s('Set as default email') . "'>"
            . "&nbsp;"
            . "<input type='text' size='30' class='form-control' " . "name='" . htmlescape($field_name) . "[-__JS_PLACEHOLDER__]'  aria-label='" . __s('Email address') . "'>"
            . "</div>";

        return str_replace(
            '__JS_PLACEHOLDER__',
            "'+{$child_count_js_var}+'", // string closing, + operator, JS variable name, + operator, string reopening
            jsescape($html)
        );
    }


    /**
     * @since 0.85 (since 0.85 but param $id since 0.85)
     *
     * @param $canedit
     * @param $field_name
     * @param $id
     **/
    public function showChildForItemForm($canedit, $field_name, $id, bool $display = true)
    {

        if ($this->isNewID($this->getID())) {
            $value = '';
        } else {
            $value = htmlescape($this->fields['email']);
        }
        $result = "";
        $field_name = htmlescape($field_name . "[$id]");
        $result .= "<div class='d-flex align-items-center'>";
        $result .= "<input title='" . __s('Default email') . "' type='radio' name='_default_email'
             value='" . htmlescape($this->getID()) . "'";
        if (!$canedit) {
            $result .= " disabled aria-disabled='true'";
        }
        if ($this->fields['is_default']) {
            $result .= " checked";
        }
        $result .= " aria-label='" . __s('Set as default email') . "'>&nbsp;";
        if (!$canedit || $this->fields['is_dynamic']) {
            $result .= "<input type='hidden' name='$field_name' value='$value'>";
            $result .= sprintf('%s <span class="b">(%s)</span>', $value, __s('D'));
        } else {
            $result .= "<input type='text' size=30 class='form-control' name='$field_name' value='$value' aria-label='" . __s('Email address') . "'>";
        }
        $result .= "</div>";

        if ($display) {
            echo $result;
        } else {
            return $result;
        }
    }


    /**
     * Show emails of a user
     *
     * @param $user User object
     *
     * @return void
     **/
    public static function showForUser(User $user)
    {

        $users_id = $user->getID();

        if (
            !$user->can($users_id, READ)
            && ($users_id != Session::getLoginUserID())
        ) {
            return;
        }

        $canedit = $users_id == Session::getLoginUserID();
        if (!$canedit) {
            if ($user->isNewID($users_id)) {
                $canedit = $user->can($users_id, CREATE);
            } else {
                $canedit = $user->can($users_id, UPDATE)
                && $user->currentUserHaveMoreRightThan($users_id);
            }
        }

        parent::showChildsForItemForm($user, '_useremails', $canedit);
    }


    /**
     * @param $user
     **/
    public static function showAddEmailButton(User $user)
    {

        $users_id = $user->getID();
        if (!$user->can($users_id, READ) && ($users_id != Session::getLoginUserID())) {
            return false;
        }

        $canedit = $users_id == Session::getLoginUserID();
        if (!$canedit) {
            if ($user->isNewID($users_id)) {
                $canedit = $user->can($users_id, CREATE);
            } else {
                $canedit = $user->can($users_id, UPDATE)
                    && $user->currentUserHaveMoreRightThan($users_id);
            }
        }

        parent::showAddChildButtonForItemForm($user, '_useremails', $canedit);
    }


    public function prepareInputForAdd($input)
    {
        if (!$this->checkInputEmailValidity($input)) {
            return false;
        }

        // First email is default
        if (countElementsInTable($this->getTable(), ['users_id' => $input['users_id']]) == 0) {
            $input['is_default'] = 1;
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (!$this->checkInputEmailValidity($input)) {
            return false;
        }

        return parent::prepareInputForUpdate($input);
    }

    /**
     * Check validity of email passed in input.
     *
     * @param array $input
     *
     * @return bool
     */
    private function checkInputEmailValidity(array $input): bool
    {
        return isset($input['email']) && !empty($input['email']) && GLPIMailer::validateAddress($input['email']);
    }


    /**
     * @since 0.84
     *
     * @see CommonDBTM::getNameField
     *
     * @return string
     **/
    public static function getNameField()
    {
        return 'email';
    }


    public function post_updateItem($history = true)
    {
        global $DB;

        // if default is set : unsed others for the users
        if (
            in_array('is_default', $this->updates)
            && ($this->input["is_default"] == 1)
        ) {
            $DB->update(
                $this->getTable(),
                [
                    'is_default' => 0,
                ],
                [
                    'id'        => ['<>', $this->input['id']],
                    'users_id'  => $this->fields['users_id'],
                ]
            );
        }

        parent::post_updateItem($history);
    }


    public function post_addItem()
    {
        global $DB;

        // if default is set : unset others for the users
        if (isset($this->fields['is_default']) && ($this->fields["is_default"] == 1)) {
            $DB->update(
                $this->getTable(),
                [
                    'is_default' => 0,
                ],
                [
                    'id'        => ['<>', $this->fields['id']],
                    'users_id'  => $this->fields['users_id'],
                ]
            );
        }

        parent::post_addItem();
    }


    public function post_deleteFromDB()
    {
        global $DB;

        // if default is set : set default to another one
        if ($this->fields["is_default"] == 1) {
            $DB->update(
                $this->getTable(),
                [
                    'is_default'   => 1,
                ],
                [
                    'WHERE'  => [
                        'id'        => ['<>', $this->fields['id']],
                        'users_id'  => $this->fields['users_id'],
                    ],
                    'LIMIT'  => 1,
                ]
            );
        }

        parent::post_deleteFromDB();
    }
}

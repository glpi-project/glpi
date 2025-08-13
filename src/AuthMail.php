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

/**
 *  Class used to manage Auth mail config
 */
class AuthMail extends CommonDBTM
{
    // From CommonDBTM
    public $dohistory = true;

    public static $rightname = 'config';

    public static function getTypeName($nb = 0)
    {
        return _n('Email server', 'Email servers', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', Auth::class, self::class];
    }

    public function prepareInputForUpdate($input)
    {
        if (array_key_exists('name', $input) && (string) $input['name'] === '') {
            Session::addMessageAfterRedirect(sprintf(__s('The %s field is mandatory'), 'name'), false, ERROR);

            return false;
        }

        if (!empty($input['mail_server'])) {
            $input["connect_string"] = Toolbox::constructMailServerConfig($input);
        }
        return $input;
    }

    public static function canCreate(): bool
    {
        return static::canUpdate();
    }

    public static function canPurge(): bool
    {
        return static::canUpdate();
    }

    public function prepareInputForAdd($input)
    {
        if (empty($input['name'])) {
            Session::addMessageAfterRedirect(sprintf(__s('The %s field is mandatory'), 'name'), false, ERROR);

            return false;
        }

        if (!empty($input['mail_server'])) {
            $input["connect_string"] = Toolbox::constructMailServerConfig($input);
        }
        return $input;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => _n('Email server', 'Email servers', 1),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'datatype'           => 'number',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'host',
            'name'               => __('Server'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'connect_string',
            'name'               => __('Connection string'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'is_default',
            'name'               => __('Default server'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
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
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        return $tab;
    }

    /**
     * Print the auth mail form
     *
     * @param integer $ID      ID of the item
     * @param array   $options Options
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     */
    public function showForm($ID, array $options = [])
    {
        if (!$this->can($ID, UPDATE)) {
            return false;
        }

        $protocol_choices = [];
        foreach (Toolbox::getMailServerProtocols(allow_plugins_protocols: false) as $key => $protocol) {
            $protocol_choices['/' . $key] = $protocol['label'];
        }

        TemplateRenderer::getInstance()->display('pages/setup/authentication/mail.html.twig', [
            'item'             => $this,
            'params'           => $options,
            'protocol_choices' => $protocol_choices,
        ]);
    }

    /**
     * @return void
     */
    public function post_updateItem($history = true)
    {
        if ($this->fields["is_default"] === 1) {
            $this->removeDefaultFromOtherItems();
        }

        parent::post_updateItem($history);
    }

    /**
     * @return void
     */
    public function post_addItem()
    {
        if ($this->fields["is_default"] === 1) {
            $this->removeDefaultFromOtherItems();
        }

        parent::post_addItem();
    }

    /**
     * Show test mail form
     *
     * @return void
     */
    public function showFormTestMail()
    {
        $ID = $this->getField('id');

        if ($this->getFromDB($ID)) {
            $twig_params = [
                'title'          => __('Test connection to email server'),
                'login'          => __('Login'),
                'password'       => __('Password'),
                'test'           => _x('button', 'Test'),
                'connect_string' => $this->fields['connect_string'] ?? '',
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <form method="post" action="{{ 'AuthMail'|itemtype_form_path }}" data-submit-once>
                    <div class="text-center d-flex flex-column">
                        <div>
                            <h1 class="fs-2">{{ title }}</h1>
                        </div>
                        {{ fields.textField('imap_login', '', login, {
                            full_width: true,
                            additional_attributes: {
                                autocomplete: 'username'
                            }
                        }) }}
                        {{ fields.passwordField('imap_password', '', password, {
                            full_width: true,
                            clearable: false,
                            additional_attributes: {
                                autocomplete: 'password'
                            }
                        }) }}
                        {{ fields.hiddenField('imap_string', connect_string) }}
                        <div>
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                            <button type="submit" name="test" class="btn btn-primary">{{ test }}</button>
                        </div>
                    </div>
                </form>
TWIG, $twig_params);
        }
    }

    /**
     * Is the Mail authentication used?
     *
     * @return boolean
     */
    public static function useAuthMail()
    {
        return (countElementsInTable('glpi_authmails', ['is_active' => 1]) > 0);
    }

    /**
     * Test a connexion to the IMAP/POP server
     *
     * @param string $connect_string mail server
     * @param string $login          user login
     * @param string $password       user password
     *
     * @return boolean Authentication succeeded?
     */
    public static function testAuth($connect_string, $login, $password)
    {
        $auth = new Auth();
        return $auth->connection_imap(
            $connect_string,
            Toolbox::decodeFromUtf8($login),
            Toolbox::decodeFromUtf8($password)
        );
    }

    /**
     * Authenticate a user by checking a specific mail server
     *
     * @param object $auth        identification object
     * @param string $login       user login
     * @param string $password    user password
     * @param array  $mail_method mail_method array to use
     *
     * @return object identification object
     */
    public static function mailAuth($auth, $login, $password, $mail_method)
    {
        if (!empty($mail_method["connect_string"])) {
            $auth->auth_succeded = $auth->connection_imap(
                $mail_method["connect_string"],
                $login,
                $password
            );
            if ($auth->auth_succeded) {
                $auth->extauth      = 1;
                $auth->user_present = $auth->user->getFromDBbyName($login);
                $auth->user->getFromIMAP($mail_method, Toolbox::decodeFromUtf8($login));
                // Update the authentication method for the current user
                $auth->user->fields["authtype"] = Auth::MAIL;
                $auth->user->fields["auths_id"] = $mail_method["id"];
            }
        }
        return $auth;
    }

    /**
     * Try to authenticate a user by checking all the mail server
     *
     * @param object  $auth     identification object
     * @param string  $login    user login
     * @param string  $password user password
     * @param integer $auths_id auths_id already used for the user (default 0)
     * @param boolean $break    if user is not found in the first directory,
     *                          stop searching or try the following ones (true by default)
     *
     * @return object identification object
     */
    public static function tryMailAuth($auth, $login, $password, $auths_id = 0, $break = true)
    {
        if ($auths_id <= 0) {
            foreach ($auth->authtypes["mail"] as $mail_method) {
                if (!$auth->auth_succeded && $mail_method['is_active']) {
                    $auth = self::mailAuth($auth, $login, $password, $mail_method);
                } else {
                    if ($break) {
                        break;
                    }
                }
            }
        } elseif (array_key_exists($auths_id, $auth->authtypes["mail"])) {
            // Check if the mail server indicated as the last good one still exists !
            $auth = self::mailAuth($auth, $login, $password, $auth->authtypes["mail"][$auths_id]);
        }
        return $auth;
    }

    public function cleanDBonPurge()
    {
        Rule::cleanForItemCriteria($this, 'MAIL_SERVER');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        if (!$withtemplate && $item->can($item->getField('id'), READ)) {
            $ong = [];
            $ong[1] = self::createTabEntry(_x('button', 'Test'), icon: 'ti ti-stethoscope');

            return $ong;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var AuthMail $item */
        switch ($tabnum) {
            case 1:
                $item->showFormTestMail();
                break;
        }
        return true;
    }

    public static function getIcon()
    {
        return "ti ti-mail";
    }

    /**
     * Remove the `is_default` flag from authentication methods that does not match the current item.
     */
    private function removeDefaultFromOtherItems(): void
    {
        if (isset($this->fields['is_default']) && (int) $this->fields["is_default"] === 1) {
            // if current default Auth is an AuthMail, remvove it
            $auth = new self();
            $defaults = $auth->find(['is_default' => 1, ['NOT' => ['id' => $this->getID()]]]);
            foreach ($defaults as $default) {
                $auth = new self();
                $auth->update([
                    'id' => $default['id'],
                    'is_default' => 0,
                ]);
            }

            // if current default Auth is an AuthLDAP, remvove it
            $auth = new AuthLDAP();
            $defaults = $auth->find(['is_default' => 1]);
            foreach ($defaults as $default) {
                $auth = new AuthLDAP();
                $auth->update([
                    'id' => $default['id'],
                    'is_default' => 0,
                ]);
            }
        }
    }
}

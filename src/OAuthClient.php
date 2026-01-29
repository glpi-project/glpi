<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Glpi\OAuth\Server;

use function Safe\json_decode;
use function Safe\json_encode;

final class OAuthClient extends CommonDBTM
{
    public static $rightname = 'oauth_client';

    public static $undisclosedFields = [
        'secret',
    ];

    public static function getTypeName($nb = 0)
    {
        return _n('OAuth client', 'OAuth clients', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', self::class];
    }

    public static function getIcon()
    {
        return 'ti ti-key';
    }

    /**
     * @return array<string, string>
     */
    public function defineTabs($options = [])
    {
        $tabs = parent::defineTabs($options);
        $this->addStandardTab(self::class, $tabs, $options);

        return $tabs;
    }

    /**
     * @return string[]
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return [
            1 => self::createTabEntry(text: _n('Session', 'Sessions', Session::getPluralNumber()), icon: 'ti ti-cloud-lock'),
        ];
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof self && $tabnum == 1) {
            $item->showSessions();
            return true;
        }
        return false;
    }

    public function showForm($ID, array $options = [])
    {
        TemplateRenderer::getInstance()->display('pages/setup/oauthclient.html.twig', [
            'item' => $this,
            'params' => $options,
            'allowed_scopes' => Server::getAllowedScopes(),
        ]);
        return true;
    }

    public function showSessions(): void
    {
        global $DB;

        $access_tokens = $DB->request([
            'SELECT' => [
                'glpi_oauth_access_tokens.user_identifier', 'glpi_oauth_access_tokens.date_expiration',
                'glpi_oauth_access_tokens.scopes', 'glpi_oauth_access_tokens.identifier',
                'glpi_oauth_refresh_tokens.date_expiration AS refresh_token_expiration',
            ],
            'FROM'   => 'glpi_oauth_access_tokens',
            'LEFT JOIN' => [
                'glpi_oauth_refresh_tokens' => [
                    'ON' => [
                        'glpi_oauth_access_tokens' => 'identifier',
                        'glpi_oauth_refresh_tokens' => 'access_token',
                    ],
                ],
            ],
            'WHERE'  => ['client' => $this->fields['identifier']],
        ]);

        $show_actions = self::canUpdate();

        $entries = [];
        foreach ($access_tokens as $token) {
            $user = new User();
            $user_display = $token['user_identifier'];
            if ($user->getFromDB($token['user_identifier'])) {
                $user_display = $user->getLink();
            }
            $entry = [
                'id' => $token['identifier'],
                'user'       => $user_display,
                'expiration' => $token['date_expiration'],
                'refresh_token_expiration' => $token['refresh_token_expiration'],
                'scopes'     => implode(', ', json_decode($token['scopes'], true) ?? []),
            ];
            if ($show_actions) {
                $entry['actions'] = '<button type="button" name="revoke_oauth_token" class="btn btn-danger btn-sm">' . __('Revoke') . '</button>';
            }
            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'user'       => User::getTypeName(1),
                'expiration' => __('Expiration date'),
                'refresh_token_expiration' => __('Refresh token expiration date'),
                'scopes'     => __('Scopes'),
                'actions'    => __('Actions'),
            ],
            'formatters' => [
                'user' => 'raw_html',
                'expiration' => 'datetime',
                'refresh_token_expiration' => 'datetime',
                'actions' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
        ]);
        if ($show_actions) {
            echo Html::scriptBlock(<<<JS
                document.querySelectorAll('button[name="revoke_oauth_token"]').forEach(function(button) {
                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        glpi_confirm({
                            message: __("Are you sure you want to revoke this access token?"),
                            confirm_callback: () => {
                                const token_identifier = button.closest('tr').getAttribute('data-id');
                                $.ajax({
                                    method: "POST",
                                    url: "/OAuth/AccessToken/" + token_identifier + "/Revoke"
                                }).then(() => {
                                    // Remove the row from the table
                                    const row = button.closest('tr');
                                    row.parentNode.removeChild(row);
                                    glpi_toast_success(__("The access token has been successfully revoked."));
                                }, () => {
                                    glpi_toast_error(__("An error occurred while revoking the access token."));
                                }).catch(() => {
                                    glpi_toast_error(__("An error occurred while revoking the access token."));
                                });
                            }
                        });
                    });
                });
JS);
        }
    }

    public function rawSearchOptions()
    {
        $opts = [];

        $opts[] = [
            'id' => 'common',
            'name' => self::getTypeName(1),
        ];
        $opts[] = [
            'id' => 1,
            'table' => self::getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'datatype' => 'itemlink',
        ];
        $opts[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number',
        ];

        $opts[] = [
            'id' => '3',
            'table' => self::getTable(),
            'field' => 'identifier',
            'name' => __('Client ID'),
            'datatype' => 'itemlink',
        ];

        return $opts;
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function getNewIDOrSecret()
    {
        return bin2hex(random_bytes(Server::ID_SECRET_LENGTH_BYTES));
    }

    public function prepareInputForAdd($input)
    {
        if (array_key_exists('allowed_ips', $input) && !$this->validateAllowedIPs($input['allowed_ips'])) {
            Session::addMessageAfterRedirect(
                msg: __s('Invalid IP address or CIDR range'),
                message_type: ERROR
            );
            return false;
        }
        $key = new GLPIKey();
        $input['identifier'] = self::getNewIDOrSecret();
        $input['secret'] = $key->encrypt(self::getNewIDOrSecret());

        $input['grants'] = json_encode($input['grants'] ?? []);
        $input['scopes'] = json_encode(empty($input['scopes']) ? [] : $input['scopes']);

        if (empty($input['redirect_uri'])) {
            $input['redirect_uri'] = ['/api.php/oauth2/redirection'];
        }
        $input['redirect_uri'] = json_encode($input['redirect_uri']);

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (array_key_exists('allowed_ips', $input) && !$this->validateAllowedIPs($input['allowed_ips'])) {
            Session::addMessageAfterRedirect(
                msg: __s('Invalid IP address or CIDR range'),
                message_type: ERROR
            );
            return false;
        }
        $key = new GLPIKey();
        if (isset($input['secret'])) {
            $input['secret'] = $key->encrypt($input['secret']);
        }
        if (isset($input['grants'])) {
            $input['grants'] = json_encode($input['grants']);
        }
        if (isset($input['scopes'])) {
            $input['scopes'] = json_encode(empty($input['scopes']) ? [] : $input['scopes']);
        }
        $input['redirect_uri'] = json_encode($input['redirect_uri'] ?? []);

        return $input;
    }

    /**
     * Ensure the allowed IPs input is a comma-separated list of valid IP addresses or CIDR ranges.
     * @param string|null $allowed_ips
     * @return bool
     */
    private function validateAllowedIPs(?string $allowed_ips)
    {
        if (empty($allowed_ips)) {
            return true;
        }
        $allowed_ip_array = array_map('trim', explode(',', $allowed_ips));
        foreach ($allowed_ip_array as $allowed_ip) {
            $ipv6 = str_contains($allowed_ip, ':');
            $max_mask = $ipv6 ? 128 : 32;
            if (str_contains($allowed_ip, '/')) {
                [$ip, $mask] = explode('/', $allowed_ip);
                if (filter_var($mask, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $max_mask]]) === false) {
                    return false;
                }
            } else {
                $ip = $allowed_ip;
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
                return false;
            }
        }
        return true;
    }

    public function post_getFromDB()
    {
        $key = new GLPIKey();
        if (isset($this->fields['secret'])) {
            $this->fields['secret'] = $key->decrypt($this->fields['secret']);
        }
        if (isset($this->fields['grants'])) {
            $this->fields['grants'] = json_decode($this->fields['grants'], true);
        }
        if (isset($this->fields['scopes'])) {
            $this->fields['scopes'] = json_decode($this->fields['scopes'], true);
        }
        $this->fields['redirect_uri'] = json_decode($this->fields['redirect_uri'], true);
    }

    public function post_getEmpty()
    {
        $this->fields['grants'] = [];
        $this->fields['scopes'] = [];
    }
}

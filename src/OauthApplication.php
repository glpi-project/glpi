<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

class OAuthApplication extends CommonDBTM
{
    public static string $rightname = 'config';

    public static array $undisclosedFields = [
        'client_secret',
    ];

    public static function getTypeName($nb = 0): string
    {
        return _n('OAuth Application', 'OAuth Applications', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', self::class];
    }

    public static function getIcon(): string
    {
        return 'ti ti-lock-access';
    }

    public static function canCreate(): bool
    {
        return Session::haveRight(static::$rightname, CREATE);
    }

    public static function canPurge(): bool
    {
        return Session::haveRight(static::$rightname, PURGE);
    }

    public function defineTabs($options = []): array
    {
        $tabs = parent::defineTabs($options);
        $this->addStandardTab(self::class, $tabs, $options);
        return $tabs;
    }

    /**
     * @return array<int, string>|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!($item instanceof self)) {
            return '';
        }

        $nb = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = $this->countLinkedMailCollectors($item->getID());
        }

        return [
            1 => self::createTabEntry(
                text: MailCollector::getTypeName(Session::getPluralNumber()),
                nb: $nb,
                icon: MailCollector::getIcon(),
            ),
        ];
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof self && $tabnum === 1) {
            $item->showLinkedMailCollectors();
            return true;
        }
        return false;
    }

    /**
     * Returns the number of MailCollectors whose host references this application.
     */
    private function countLinkedMailCollectors(int $id): int
    {
        global $DB;

        $app_key  = 'oauth_imap_' . $id;
        $iterator = $DB->request([
            'COUNT' => 'cpt',
            'FROM'  => MailCollector::getTable(),
            'WHERE' => [
                'OR' => [
                    ['host' => ['LIKE', '%/' . $app_key . '/%']],
                    ['host' => ['LIKE', '%/' . $app_key . '}%']],
                ],
            ],
        ]);
        return (int) $iterator->current()['cpt'];
    }

    /**
     * Displays the tab content listing MailCollectors linked to this application.
     */
    public function showLinkedMailCollectors(): void
    {
        global $DB;

        $app_key  = 'oauth_imap_' . $this->getID();
        $iterator = $DB->request([
            'FROM'  => MailCollector::getTable(),
            'WHERE' => [
                'OR' => [
                    ['host' => ['LIKE', '%/' . $app_key . '/%']],
                    ['host' => ['LIKE', '%/' . $app_key . '}%']],
                ],
            ],
        ]);

        $entries = [];
        foreach ($iterator as $row) {
            $collector = new MailCollector();
            $collector->getFromResultSet($row);
            $entries[] = [
                'id'                => $collector->getID(),
                'name'              => $collector->getLink(),
                'is_active'         => $row['is_active'] ? __('Yes') : __('No'),
                'last_collect_date' => $row['last_collect_date'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab'          => true,
            'nofilter'        => true,
            'nosort'          => true,
            'columns'         => [
                'name'              => __('Name'),
                'is_active'         => __('Active'),
                'last_collect_date' => __('Last collection date'),
            ],
            'formatters'      => [
                'name'              => 'raw_html',
                'last_collect_date' => 'datetime',
            ],
            'entries'         => $entries,
            'total_number'    => count($entries),
            'filtered_number' => count($entries),
        ]);
    }

    public function showForm($ID, array $options = []): bool
    {
        TemplateRenderer::getInstance()->display('pages/setup/oauthapplication.html.twig', [
            'item'      => $this,
            'params'    => $options,
            'providers' => self::getProviders(),
        ]);
        return true;
    }

    public function prepareInputForAdd($input)
    {
        if (empty($input['client_id'])) {
            Session::addMessageAfterRedirect(
                msg: __s('Client ID is required'),
                message_type: ERROR
            );
            return false;
        }
        if (empty($input['client_secret'])) {
            Session::addMessageAfterRedirect(
                msg: __s('Client secret is required'),
                message_type: ERROR
            );
            return false;
        }

        $input['client_secret'] = (new GLPIKey())->encrypt($input['client_secret']);

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['client_secret'])) {
            if (!empty($input['client_secret'])) {
                $input['client_secret'] = (new GLPIKey())->encrypt($input['client_secret']);
            } else {
                unset($input['client_secret']);
            }
        }

        return $input;
    }

    /**
     * Returns all active OauthApplication instances.
     *
     * @return self[]
     */
    public static function getActiveApplications(): array
    {
        $instance = new self();
        $rows     = $instance->find(['is_active' => 1]);

        $result = [];
        foreach ($rows as $row) {
            $app = new self();
            $app->getFromResultSet($row);
            $result[] = $app;
        }
        return $result;
    }

    /**
     * Returns available OAuth provider options.
     *
     * @return array<string, string>
     */
    public static function getProviders(): array
    {
        return [
            'azure'  => __('Microsoft Azure'),
            'google' => __('Google'),
        ];
    }

    public function rawSearchOptions(): array
    {
        $opts = [];

        $opts[] = [
            'id'   => 'common',
            'name' => self::getTypeName(1),
        ];
        $opts[] = [
            'id'       => 1,
            'table'    => self::getTable(),
            'field'    => 'name',
            'name'     => __('Name'),
            'datatype' => 'itemlink',
        ];
        $opts[] = [
            'id'            => 2,
            'table'         => self::getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'massiveaction' => false,
            'datatype'      => 'number',
        ];
        $opts[] = [
            'id'       => 3,
            'table'    => self::getTable(),
            'field'    => 'is_active',
            'name'     => __('Active'),
            'datatype' => 'bool',
        ];
        $opts[] = [
            'id'       => 4,
            'table'    => self::getTable(),
            'field'    => 'provider',
            'name'     => __('Provider'),
            'datatype' => 'string',
        ];
        $opts[] = [
            'id'       => 5,
            'table'    => self::getTable(),
            'field'    => 'comment',
            'name'     => _n('Comment', 'Comments', 1),
            'datatype' => 'text',
        ];
        $opts[] = [
            'id'            => 19,
            'table'         => self::getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];
        $opts[] = [
            'id'            => 121,
            'table'         => self::getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        return $opts;
    }
}

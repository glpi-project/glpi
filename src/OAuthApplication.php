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
use Glpi\Mail\OauthProvider\ProviderInterface as ImapOauthProviderInterface;
use Glpi\Mail\SMTP\OauthProvider\Azure as SmtpAzure;
use Glpi\Mail\SMTP\OauthProvider\Google as SmtpGoogle;

final class OAuthApplication extends CommonDBTM
{
    public static string $rightname = 'oauth_application';

    public bool $dohistory = true;

    public const AZURE = 'azure';
    public const GOOGLE = 'google';

    public const PROTOCOL_PREFIX = 'oauth_imap_';

    public static array $undisclosedFields = [
        'client_secret',
    ];

    public static function getTypeName($nb = 0): string
    {
        return _n('OAuth application', 'OAuth applications', $nb);
    }

    /**
     * An application carries the OAuth client credentials used to obtain mail
     * access tokens, so reading or altering one is a sensitive action and
     * requires the user to re-authenticate ("sudo mode").
     */
    #[Override]
    protected static function itemTypeRequiresReauthentication(): bool
    {
        return true;
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', self::class];
    }

    public static function getIcon(): string
    {
        return 'ti ti-lock-access';
    }


    public function defineTabs($options = []): array
    {
        $tabs = parent::defineTabs($options);
        $this->addStandardTab(self::class, $tabs, $options);
        $this->addStandardTab(OAuthAuthorization::class, $tabs, $options);
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
            $nb = $this->countLinkedMailCollectors();
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
            $item->displayLinkedMailCollectors();
            return true;
        }
        return false;
    }

    /** @return array<string, mixed> */
    private function linkedMailCollectorsWhere(): array
    {
        $app_key = self::PROTOCOL_PREFIX . $this->getID();
        return [
            'OR' => [
                ['host' => ['LIKE', '%/' . $app_key . '/%']],
                ['host' => ['LIKE', '%/' . $app_key . '}%']],
            ],
        ];
    }

    private function countLinkedMailCollectors(): int
    {
        global $DB;

        $iterator = $DB->request([
            'COUNT' => 'cpt',
            'FROM'  => MailCollector::getTable(),
            'WHERE' => $this->linkedMailCollectorsWhere(),
        ]);
        return (int) $iterator->current()['cpt'];
    }

    /**
     * @return list<array{id: int, name: string, plain_name: string, is_active: string, last_collect_date: mixed}>
     */
    private function getLinkedMailCollectors(): array
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => MailCollector::getTable(),
            'WHERE' => $this->linkedMailCollectorsWhere(),
        ]);

        $entries = [];
        foreach ($iterator as $row) {
            $collector = new MailCollector();
            $collector->getFromResultSet($row);
            $entries[] = [
                'id'                => $collector->getID(),
                'name'              => $collector->getLink(),
                'plain_name'        => $row['name'],
                'is_active'         => $row['is_active'] ? __('Yes') : __('No'),
                'last_collect_date' => $row['last_collect_date'],
            ];
        }

        return $entries;
    }

    private function displayLinkedMailCollectors(): void
    {
        $entries = $this->getLinkedMailCollectors();

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
        if (!$this->checkRequiredFields($input)) {
            return false;
        }

        if (($input['provider'] ?? null) === self::GOOGLE && !empty($input['tenant_id'])) {
            unset($input['tenant_id']);
        }

        $input['client_secret'] = (new GLPIKey())->encrypt($input['client_secret']);

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (!empty($input['client_secret'])) {
            $input['client_secret'] = (new GLPIKey())->encrypt($input['client_secret']);
        } else {
            unset($input['client_secret']);
        }

        // Merge current DB fields so validation always has all required data
        $full = array_merge($this->fields, $input);

        if (!$this->checkRequiredFields($full)) {
            return false;
        }

        if (($full['provider'] ?? null) !== self::AZURE) {
            $input['tenant_id'] = '';
        }

        return $input;
    }

    /** @param array<string, mixed> $input */
    private function checkRequiredFields(array $input): bool
    {
        $errors = [];

        if (empty($input['provider'])) {
            $errors[] = __s('A valid provider is required');
        } elseif (!array_key_exists($input['provider'], self::getProviders())) {
            $errors[] = __s('Invalid provider');
        }

        if (empty($input['client_id'])) {
            $errors[] = __s('Client ID is required');
        }

        if (empty($input['client_secret'])) {
            $errors[] = __s('Client secret is required');
        }

        if (($input['provider'] ?? null) === self::AZURE && empty($input['tenant_id'])) {
            $errors[] = __s('Tenant ID is required');
        }

        foreach ($errors as $error) {
            Session::addMessageAfterRedirect($error, message_type: ERROR);
        }

        return empty($errors);
    }

    public function pre_deleteItem()
    {
        $lkd_collectors = $this->getLinkedMailCollectors();
        if (count($lkd_collectors) > 0) {
            Session::addMessageAfterRedirect(__s('The app could not be deleted, it is linked with the following receiver(s): '), message_type: ERROR);
            foreach ($lkd_collectors as $collector) {
                Session::addMessageAfterRedirect('- ' . $collector['name'], message_type: ERROR);
            }

            return false;
        }
        return true;
    }

    /**
     * Returns all active OAuthApplication instances.
     *
     * @return self[]
     */
    public static function getActiveApplications(): array
    {
        $rows = (new self())->find(['is_active' => 1]);

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
            self::AZURE  => __('Microsoft Azure'),
            self::GOOGLE => __('Google'),
        ];
    }

    /**
     * Builds the OAuth provider instance configured for this application,
     * requesting the scopes relevant to the given authorization type.
     *
     * @param string $type One of `OAuthAuthorization::TYPE_IMAP`/`TYPE_SMTP`.
     */
    public function getOauthProvider(string $type): ImapOauthProviderInterface
    {
        $provider_class = match ($this->fields['provider']) {
            self::AZURE  => SmtpAzure::class,
            self::GOOGLE => SmtpGoogle::class,
            default      => throw new RuntimeException(sprintf('Unknown provider %s.', $this->fields['provider'])),
        };

        $options = [
            'clientId'     => $this->fields['client_id'],
            'clientSecret' => (new GLPIKey())->decrypt($this->fields['client_secret']),
            'redirectUri'  => self::getCallbackUrl(),
            'type'         => $type,
        ];

        if ($this->fields['provider'] === self::AZURE && !empty($this->fields['tenant_id'])) {
            $options['tenant'] = $this->fields['tenant_id'];
        }

        return new $provider_class($options);
    }

    /**
     * Redirects the current user to the provider's consent screen, in order
     * to authorize a mailbox for the given authorization type.
     *
     * @param string $type One of `OAuthAuthorization::TYPE_IMAP`/`TYPE_SMTP`.
     */
    public function redirectToAuthorizationUrl(string $type): void
    {
        if ($this->isNewItem()) {
            throw new RuntimeException('Invalid application.');
        }

        $provider = $this->getOauthProvider($type);

        $auth_url = $provider->getAuthorizationUrl();

        $_SESSION['oauth2_provider_id'] = $this->fields['id'];
        $_SESSION['oauth2_type']        = $type;
        $_SESSION['oauth2_state']       = $provider->getState();

        Html::redirect($auth_url);
    }

    /**
     * Returns the absolute URL of the OAuth callback route.
     */
    public static function getCallbackUrl(): string
    {
        global $CFG_GLPI;

        return $CFG_GLPI['url_base'] . '/oauth/callback';
    }


    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        if ($field === 'provider') {
            return htmlescape(static::getProviders()[$values[$field]] ?? $values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        if ($field === 'provider') {
            $options['value'] = $values[$field] ?? '';
            return (string) Dropdown::showFromArray($name, static::getProviders(), $options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public function rawSearchOptions(): array
    {
        $opts = [];

        $opts[] = [
            'id'   => 'common',
            'name' => static::getTypeName(1),
        ];
        $opts[] = [
            'id'       => 1,
            'table'    => static::getTable(),
            'field'    => 'name',
            'name'     => __('Name'),
            'datatype' => 'itemlink',
        ];
        $opts[] = [
            'id'            => 2,
            'table'         => static::getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'massiveaction' => false,
            'datatype'      => 'number',
        ];
        $opts[] = [
            'id'       => 3,
            'table'    => static::getTable(),
            'field'    => 'is_active',
            'name'     => __('Active'),
            'datatype' => 'bool',
        ];
        $opts[] = [
            'id'         => 4,
            'table'      => static::getTable(),
            'field'      => 'provider',
            'name'       => __('Provider'),
            'datatype'   => 'specific',
            'searchtype' => 'equals',
        ];
        $opts[] = [
            'id'       => 5,
            'table'    => static::getTable(),
            'field'    => 'comment',
            'name'     => _n('Comment', 'Comments', 1),
            'datatype' => 'text',
        ];
        $opts[] = [
            'id'            => 19,
            'table'         => static::getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];
        $opts[] = [
            'id'            => 121,
            'table'         => static::getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        return $opts;
    }
}

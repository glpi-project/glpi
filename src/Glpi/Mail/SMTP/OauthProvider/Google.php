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

namespace Glpi\Mail\SMTP\OauthProvider;

use Glpi\Mail\OauthProvider\ImapOauthProviderTrait;
use Glpi\Mail\OauthProvider\OwnerDetails;
use Glpi\Mail\OauthProvider\ProviderInterface as ImapProviderInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use OAuthAuthorization;

final class Google extends \League\OAuth2\Client\Provider\Google implements ProviderInterface, ImapProviderInterface
{
    use ImapOauthProviderTrait;

    /**
     * Scopes requested for this instance.
     */
    private array $requestedScopes;

    public function __construct(array $options = [])
    {
        $type = $options['type'] ?? OAuthAuthorization::TYPE_SMTP;
        unset($options['type']);

        $this->requestedScopes = $options['scopes'] ?? $this->getScopesForType($type);
        unset($options['scopes']);
        $options['accessType'] = 'offline';

        parent::__construct($options);
    }

    public function getAuthorizationUrl(array $options = [])
    {
        $options = [
            'prompt' => 'consent select_account', // ensure user will have to specify the account to use
            'scope'  => $this->requestedScopes,
        ];

        return parent::getAuthorizationUrl($options);
    }

    public static function getName(): string
    {
        return 'Google';
    }

    public static function getAdditionalParameters(): array
    {
        return [
        ];
    }

    /**
     * Default (SMTP-sending) scopes requested by this provider.
     *
     * @return array
     */
    public static function getSmtpDefaultScopes(): array
    {
        return [
            'https://mail.google.com/',
        ];
    }

    public static function getImapDefaults(): array
    {
        return [
            'host' => 'imap.gmail.com',
            'port' => 993,
            'ssl'  => 'SSL',
        ];
    }

    public function getOwnerDetails(AccessToken $token): ?OwnerDetails
    {
        $owner = $this->getResourceOwner($token);
        if (!$owner instanceof GoogleUser) {
            return null;
        }

        $owner_details = new OwnerDetails();
        $owner_details->email     = $owner->getEmail() ?? '';
        $owner_details->firstname = $owner->getFirstName() ?? '';
        $owner_details->lastname  = $owner->getLastName() ?? '';

        return $owner_details;
    }

    protected function getImapScopes(): array
    {
        // The broad Gmail scope already covers both IMAP and SMTP usage.
        return [
            'https://mail.google.com/',
        ];
    }

    protected function getSmtpScopes(): array
    {
        return self::getSmtpDefaultScopes();
    }
}

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

namespace Glpi\Mail\OauthProvider;

use GLPIKey;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use OAuthApplication;
use OAuthAuthorization;
use Throwable;

use function Safe\preg_match;

/**
 * Runs a step-by-step diagnostic of an `OAuthApplication` configuration.
 *
 * The diagnostic is exposed as a tree of steps (each step declaring its
 * parent) so that a caller can run them one by one and report progress
 * live, skipping the descendants of any step that failed.
 */
class ApplicationDiagnostic
{
    public const STATUS_OK      = 'ok';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_WARNING = 'warning';
    public const STATUS_INFO    = 'info';

    public const STEP_CONFIG       = 'config';
    public const STEP_SECRET       = 'secret';
    public const STEP_ENDPOINT     = 'endpoint';
    public const STEP_CREDENTIALS  = 'credentials';
    public const STEP_REDIRECT_URI = 'redirect_uri';

    public function __construct(
        protected readonly OAuthApplication $application
    ) {}

    /**
     * Returns the ordered tree of steps to run.
     *
     * Steps are returned in execution order (a parent always comes before
     * its children). A step whose parent did not succeed must be skipped.
     *
     * @return list<array{key: string, label: string, parent: ?string}>
     */
    public function getPlan(): array
    {
        $steps = [
            [
                'key'    => self::STEP_CONFIG,
                'label'  => __('Configuration'),
                'parent' => null,
            ],
            [
                'key'    => self::STEP_SECRET,
                'label'  => __('Client secret readable'),
                'parent' => self::STEP_CONFIG,
            ],
            [
                'key'    => self::STEP_ENDPOINT,
                'label'  => __('Provider endpoint'),
                'parent' => self::STEP_SECRET,
            ],
            [
                'key'    => self::STEP_CREDENTIALS,
                'label'  => __('Client credentials'),
                'parent' => self::STEP_ENDPOINT,
            ],
            [
                'key'    => self::STEP_REDIRECT_URI,
                'label'  => __('Redirect URI'),
                'parent' => self::STEP_CREDENTIALS,
            ],
        ];

        foreach ($this->getAuthorizations() as $authorization) {
            $id = (int) $authorization->fields['id'];
            $name = sprintf('%s · %s', $authorization->fields['type'], $authorization->fields['email']);

            $steps[] = [
                'key'    => sprintf('auth:%d:refresh', $id),
                'label'  => sprintf(__('%s: token refresh'), $name),
                'parent' => self::STEP_CREDENTIALS,
            ];
            $steps[] = [
                'key'    => sprintf('auth:%d:connect', $id),
                'label'  => sprintf(__('%s: connection'), $name),
                'parent' => sprintf('auth:%d:refresh', $id),
            ];
        }

        return $steps;
    }

    /**
     * Runs a single step of the diagnostic.
     *
     * @return array{status: string, message: string}
     */
    public function runStep(string $key): array
    {
        $matches = [];
        if (preg_match('/^auth:(\d+):(refresh|connect)$/', $key, $matches) === 1) {
            return $this->runAuthorizationStep((int) $matches[1], $matches[2]);
        }

        return match ($key) {
            self::STEP_CONFIG       => $this->checkConfiguration(),
            self::STEP_SECRET       => $this->checkSecret(),
            self::STEP_ENDPOINT     => $this->checkEndpoint(),
            self::STEP_CREDENTIALS  => $this->checkCredentials(),
            self::STEP_REDIRECT_URI => $this->describeRedirectUri(),
            default                 => ['status' => self::STATUS_FAILED, 'message' => __('Unknown diagnostic step')],
        };
    }

    /**
     * Checks that all fields required by the selected provider are filled in.
     */
    private function checkConfiguration(): array
    {
        if ($this->application->isNewItem()) {
            return ['status' => self::STATUS_FAILED, 'message' => __('This application has not been saved yet.')];
        }

        if (!array_key_exists($this->application->fields['provider'], OAuthApplication::getProviders())) {
            return [
                'status'  => self::STATUS_FAILED,
                'message' => sprintf(__('Unknown provider "%s".'), $this->application->fields['provider']),
            ];
        }

        $missing = [];
        if (empty($this->application->fields['client_id'])) {
            $missing[] = __('Client ID');
        }
        if (empty($this->application->fields['client_secret'])) {
            $missing[] = __('Client secret');
        }
        if (
            $this->application->fields['provider'] === OAuthApplication::AZURE
            && empty($this->application->fields['tenant_id'])
        ) {
            $missing[] = __('Tenant ID');
        }

        if ($missing !== []) {
            return [
                'status'  => self::STATUS_FAILED,
                'message' => sprintf(__('Missing required settings: %s.'), implode(', ', $missing)),
            ];
        }

        if (!$this->application->fields['is_active']) {
            return [
                'status'  => self::STATUS_WARNING,
                'message' => __('All settings are filled in, but this application is not active.'),
            ];
        }

        return [
            'status'  => self::STATUS_OK,
            'message' => sprintf(
                __('Provider: %s'),
                OAuthApplication::getProviders()[$this->application->fields['provider']]
            ),
        ];
    }

    /**
     * Checks that the stored client secret can be decrypted with the current
     * GLPI encryption key.
     */
    private function checkSecret(): array
    {
        $decrypted = (new GLPIKey())->decrypt($this->application->fields['client_secret']);

        if (empty($decrypted)) {
            return [
                'status'  => self::STATUS_FAILED,
                'message' => __('The client secret cannot be decrypted. It was probably encrypted with a different GLPI encryption key: re-enter it to fix this.'),
            ];
        }

        return ['status' => self::STATUS_OK, 'message' => __('The client secret was decrypted successfully.')];
    }

    /**
     * Resolves the provider's authorization endpoint.
     *
     * For Microsoft Azure this performs a real call to the tenant's OpenID
     * configuration (which also validates the tenant ID and the client ID
     * format); for Google the endpoint is a well-known constant.
     */
    private function checkEndpoint(): array
    {
        try {
            $this->getProvider(OAuthAuthorization::TYPE_IMAP)->getAuthorizationUrl();
        } catch (IdentityProviderException $e) {
            return ['status' => self::STATUS_FAILED, 'message' => $this->explainProviderError($e)];
        } catch (Throwable $e) {
            return [
                'status'  => self::STATUS_FAILED,
                'message' => sprintf(__('Unable to reach the provider: %s'), $e->getMessage()),
            ];
        }

        return [
            'status'  => self::STATUS_OK,
            'message' => __('The authorization endpoint was resolved successfully.'),
        ];
    }

    /**
     * Checks that the provider accepts our client ID / client secret.
     *
     * This is done by requesting a token refresh with a deliberately invalid
     * refresh token: providers authenticate the client before validating the
     * grant, so a "bad grant" error proves the credentials themselves were
     * accepted, while a "bad client" error pinpoints a credential problem.
     */
    private function checkCredentials(): array
    {
        try {
            $this->getProvider(OAuthAuthorization::TYPE_IMAP)->getAccessToken('refresh_token', [
                'refresh_token' => 'glpi-diagnostic-' . bin2hex(random_bytes(16)),
            ]);

            // Getting a token out of a random string is unexpected, but it
            // does prove the client credentials were accepted.
            return ['status' => self::STATUS_OK, 'message' => __('The provider accepted the client credentials.')];
        } catch (IdentityProviderException $e) {
            if ($this->isGrantOnlyError($e->getMessage())) {
                return [
                    'status'  => self::STATUS_OK,
                    'message' => __('The provider accepted the client ID and client secret.'),
                ];
            }

            return ['status' => self::STATUS_FAILED, 'message' => $this->explainProviderError($e)];
        } catch (Throwable $e) {
            return [
                'status'  => self::STATUS_FAILED,
                'message' => sprintf(__('Unable to reach the provider: %s'), $e->getMessage()),
            ];
        }
    }

    /**
     * The redirect URI can only be validated by the provider during a real
     * authorization request, so it is reported for the administrator to
     * check against the provider's application registration.
     */
    private function describeRedirectUri(): array
    {
        return [
            'status'  => self::STATUS_INFO,
            'message' => sprintf(
                __('This exact URL must be registered as a redirect URI in the provider\'s application settings: %s'),
                OAuthApplication::getCallbackUrl()
            ),
        ];
    }

    private function runAuthorizationStep(int $authorizations_id, string $action): array
    {
        $authorization = $this->loadAuthorization($authorizations_id);
        if ($authorization === null) {
            return ['status' => self::STATUS_FAILED, 'message' => __('This authorization no longer exists.')];
        }

        if ($action === 'refresh') {
            if ($authorization->refreshToken()) {
                return ['status' => self::STATUS_OK, 'message' => __('A fresh access token was obtained.')];
            }

            return [
                'status'  => self::STATUS_FAILED,
                'message' => $authorization->getLastError()
                    ?? __('The access token could not be refreshed. The authorization may have been revoked by the user or the provider.'),
            ];
        }

        $result = $authorization->testConnection();

        return [
            'status'  => $result['success'] ? self::STATUS_OK : self::STATUS_FAILED,
            'message' => $result['message'],
        ];
    }

    /**
     * Whether the provider error only relates to the (deliberately invalid)
     * grant, meaning the client credentials themselves were accepted.
     */
    private function isGrantOnlyError(string $message): bool
    {
        return preg_match('/\binvalid_grant\b/i', $message) === 1
            || preg_match('/\bAADSTS(700082|70008|54005|9002313)\b/i', $message) === 1;
    }

    /**
     * Turns a provider error into an actionable explanation.
     */
    private function explainProviderError(IdentityProviderException $e): string
    {
        $message = $e->getMessage();

        $matches = [];
        if (preg_match('/\bAADSTS(\d+)\b/', $message, $matches) === 1) {
            return $this->explainAzureError($matches[1], $message);
        }

        $body = $e->getResponseBody();
        $description = is_array($body) ? (string) ($body['error_description'] ?? '') : '';

        return match (true) {
            str_contains($message, 'invalid_client') => __('The client ID or the client secret is invalid.'),
            str_contains($message, 'unauthorized_client') => __('This application is not allowed to use this authentication flow. Check its configuration with the provider.'),
            str_contains($message, 'access_denied') => __('The provider denied access to this application.'),
            default => trim(sprintf(__('The provider returned an error: %s %s'), $message, $description)),
        };
    }

    /**
     * Maps well-known Microsoft Entra ID (Azure AD) error codes to an
     * actionable explanation.
     */
    private function explainAzureError(string $code, string $raw): string
    {
        return match ($code) {
            '90002'  => __('This tenant does not exist. Check the Tenant ID.'),
            '90112'  => __('The Client ID is not a valid GUID. Copy the "Application (client) ID" from the Azure portal.'),
            '700016' => __('This application was not found in the tenant\'s directory. Check the Client ID and the Tenant ID, and make sure the application is registered in that tenant.'),
            '7000215', '7000216', '7000222' => __('The client secret is invalid or has expired. Generate a new secret in the Azure portal.'),
            '650056', '65001' => __('This application is missing permissions, or an administrator has not granted consent yet. Add the required API permissions in the Azure portal and grant admin consent.'),
            '50011'  => sprintf(
                __('The redirect URI is not registered for this application. Add this exact URL in the Azure portal: %s'),
                OAuthApplication::getCallbackUrl()
            ),
            '900023' => __('The Tenant ID is malformed. Use the directory (tenant) ID, a verified domain name, or "common".'),
            default  => sprintf(__('Microsoft returned the error AADSTS%s: %s'), $code, $raw),
        };
    }

    /**
     * Builds the OAuth provider used to probe the configuration.
     *
     * Isolated in its own (protected) method so tests can substitute a
     * fake provider that does not perform any network access.
     */
    protected function getProvider(string $type): ProviderInterface
    {
        return $this->application->getOauthProvider($type);
    }

    /**
     * Returns the authorizations attached to this application.
     *
     * @return list<OAuthAuthorization>
     */
    protected function getAuthorizations(): array
    {
        $authorizations = [];
        $rows = (new OAuthAuthorization())->find(
            ['oauth_applications_id' => $this->application->getID()],
            ['type', 'email']
        );

        foreach ($rows as $row) {
            $authorization = new OAuthAuthorization();
            $authorization->getFromResultSet($row);
            $authorizations[] = $authorization;
        }

        return $authorizations;
    }

    /**
     * Loads a single authorization belonging to this application.
     *
     * Isolated in its own (protected) method so tests can substitute a
     * test double that does not perform any network access.
     */
    protected function loadAuthorization(int $authorizations_id): ?OAuthAuthorization
    {
        $authorization = new OAuthAuthorization();
        if (
            !$authorization->getFromDB($authorizations_id)
            || (int) $authorization->fields['oauth_applications_id'] !== $this->application->getID()
        ) {
            return null;
        }

        return $authorization;
    }
}

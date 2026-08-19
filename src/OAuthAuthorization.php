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
use Glpi\Mail\OauthProvider\ProviderInterface;
use League\OAuth2\Client\Token\AccessToken;
use Safe\Exceptions\JsonException;
use Safe\Exceptions\UrlException;

use function Safe\base64_decode;
use function Safe\fclose;
use function Safe\fwrite;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;
use function Safe\stream_set_timeout;

class OAuthAuthorization extends CommonDBChild
{
    public static string $itemtype = OAuthApplication::class;

    public static string $items_id = 'oauth_applications_id';

    public bool $dohistory = true;

    public const TYPE_IMAP = 'IMAP';
    public const TYPE_SMTP = 'SMTP';

    /**
     * Local states of the stored access token, as reported by
     * getTokenStatus().
     */
    public const TOKEN_MISSING    = 'missing';
    public const TOKEN_UNREADABLE = 'unreadable';
    public const TOKEN_VALID      = 'valid';
    public const TOKEN_RENEWABLE  = 'renewable';
    public const TOKEN_EXPIRED    = 'expired';

    /**
     * Timeout, in seconds, used when probing a mail server.
     */
    private const PROBE_TIMEOUT = 10;

    public static array $undisclosedFields = [
        'code',
        'token',
        'refresh_token',
    ];

    private ?string $error = null;

    public static function getTable($classname = null)
    {
        return 'glpi_oauth_authorizations';
    }

    public static function getTypeName($nb = 0): string
    {
        return _n('OAuth authorization', 'OAuth authorizations', $nb);
    }

    public static function getIcon(): string
    {
        return 'ti ti-key';
    }

    /**
     * @return array<string, string>
     */
    public static function getEnumTypes(): array
    {
        return [
            self::TYPE_IMAP => __('IMAP'),
            self::TYPE_SMTP => __('SMTP'),
        ];
    }

    public function getLastError(): ?string
    {
        return $this->error;
    }

    /**
     * @return array<int, string>|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!($item instanceof OAuthApplication)) {
            return '';
        }

        $nb_imap = 0;
        $nb_smtp = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $nb_imap = self::countForApplicationAndType($item->getID(), self::TYPE_IMAP);
            $nb_smtp = self::countForApplicationAndType($item->getID(), self::TYPE_SMTP);
        }

        return [
            1 => self::createTabEntry(text: __('IMAP Authorizations'), nb: $nb_imap, icon: self::getIcon()),
            2 => self::createTabEntry(text: __('SMTP Authorizations'), nb: $nb_smtp, icon: self::getIcon()),
        ];
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if (!($item instanceof OAuthApplication)) {
            return false;
        }

        $type = $tabnum === 2 ? self::TYPE_SMTP : self::TYPE_IMAP;
        (new self())->displayAuthorizationsList($item, $type);
        return true;
    }

    private static function countForApplicationAndType(int $oauth_applications_id, string $type): int
    {
        global $DB;

        $iterator = $DB->request([
            'COUNT' => 'cpt',
            'FROM'  => self::getTable(),
            'WHERE' => [
                'oauth_applications_id' => $oauth_applications_id,
                'type'                  => $type,
            ],
        ]);
        return (int) $iterator->current()['cpt'];
    }

    public function displayAuthorizationsList(OAuthApplication $item, string $type): void
    {
        global $DB;

        $entries = [];
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'oauth_applications_id' => $item->getID(),
                'type'                  => $type,
            ],
            'ORDER' => 'email',
        ]);

        foreach ($iterator as $row) {
            $authorization = new self();
            $authorization->getFromResultSet($row);

            $status = $authorization->getTokenStatus();

            $entries[] = [
                'email'      => $row['email'],
                'status'     => $this->getStatusHtml($status),
                'expiration' => $status['expiration'],
                'date_mod'   => $row['date_mod'],
                'actions'    => $this->getRowActionsHtml($authorization),
            ];
        }

        TemplateRenderer::getInstance()->display('pages/setup/oauthauthorization_tab.html.twig', [
            'item'    => $item,
            'type'    => $type,
            'entries' => $entries,
            'total_number'    => count($entries),
            'filtered_number' => count($entries),
        ]);
    }

    /**
     * Renders a token status as a badge prefixed with a colored round icon, so
     * a healthy authorization can be told from a broken one at a glance.
     *
     * The icon only repeats what the label already says, hence aria-hidden.
     *
     * @param array{code: string, label: string, color: string, icon: string, expiration: ?string} $status
     */
    private function getStatusHtml(array $status): string
    {
        return sprintf(
            '<span class="badge bg-%1$s-lt text-nowrap"><i class="%2$s me-1" aria-hidden="true"></i>%3$s</span>',
            htmlescape($status['color']),
            htmlescape($status['icon']),
            htmlescape($status['label'])
        );
    }

    private function getRowActionsHtml(self $authorization): string
    {
        $id = (int) $authorization->fields['id'];

        $btn = static fn(string $action, string $label, string $class) => sprintf(
            '<form method="post" action="%1$s/ajax/oauthauthorization_action.php" class="d-inline">'
            . '<input type="hidden" name="id" value="%2$d">'
            . '<button type="submit" name="%3$s" value="1" class="btn btn-sm %4$s" title="%5$s">%5$s</button>'
            . '</form>',
            $GLOBALS['CFG_GLPI']['root_doc'],
            $id,
            $action,
            $class,
            htmlescape($label)
        );

        return $btn('refresh', __('Refresh'), 'btn-outline-secondary')
            . ' ' . $btn('test', __('Troubleshoot'), 'btn-outline-secondary')
            . ' ' . $btn('delete', __('Delete'), 'btn-outline-danger');
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInput($input);

        // Avoid spurious history entries: encryption is non-deterministic,
        // so only keep a field in the update payload if its decrypted value
        // actually changed.
        foreach (['code', 'token', 'refresh_token'] as $field) {
            if (
                array_key_exists($field, $input)
                && array_key_exists($field, $this->fields)
                && (new GLPIKey())->decrypt($this->fields[$field]) === (new GLPIKey())->decrypt($input[$field])
            ) {
                unset($input[$field]);
            }
        }

        return $input;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function prepareInput(array $input): array
    {
        foreach (['code', 'token', 'refresh_token'] as $field) {
            if (!empty($input[$field])) {
                $input[$field] = (new GLPIKey())->encrypt($input[$field]);
            }
        }

        return $input;
    }

    /**
     * Resolves the OAuth provider instance to use for a given application
     * and authorization type.
     *
     * Isolated in its own (protected) method so tests can stub it out with
     * a fake provider, without performing any real HTTP/OAuth2 calls.
     */
    protected function resolveProvider(OAuthApplication $application, string $type): ProviderInterface
    {
        return $application->getOauthProvider($type);
    }

    /**
     * Exchanges an authorization code for an access token, and creates or
     * updates the authorization row for the resulting mailbox.
     */
    public function createFromCode(int $oauth_applications_id, string $type, string $code): bool
    {
        $this->error = null;

        $application = new OAuthApplication();
        if (!$application->getFromDB($oauth_applications_id)) {
            $this->error = __('Invalid OAuth application');
            return false;
        }

        try {
            $provider = $this->resolveProvider($application, $type);
            $token = $provider->getAccessToken('authorization_code', ['code' => $code]);
        } catch (Throwable $e) {
            global $PHPLOGGER;
            $PHPLOGGER->warning(
                sprintf('Error during authorization code fetching: %s', $e->getMessage()),
                ['exception' => $e]
            );
            $this->error = sprintf(__('Unable to obtain access token from provider: %s'), $e->getMessage());
            return false;
        }

        $owner_details = $provider->getOwnerDetails($token);
        $email = $owner_details?->email;
        if (empty($email)) {
            global $PHPLOGGER;
            $PHPLOGGER->warning('Unable to get user email');
            $this->error = __('The authenticated account does not expose an email address');
            return false;
        }

        $input = [
            'oauth_applications_id' => $oauth_applications_id,
            'type'                  => $type,
            'code'                  => $code,
            'token'                 => json_encode($token->jsonSerialize()),
            'refresh_token'         => $token->getRefreshToken() ?? '',
            'email'                 => $email,
        ];

        $exists = $this->getFromDBByCrit([
            'oauth_applications_id' => $oauth_applications_id,
            'email'                 => $email,
            'type'                  => $type,
        ]);

        return $exists
            ? $this->update(['id' => $this->fields['id']] + $input)
            : (bool) $this->add($input);
    }

    /**
     * Computes the state of the stored access token.
     *
     * This is a purely local check: it reads the stored token and the
     * expiration date the provider advertised when it was issued, without
     * contacting the provider. A token can therefore be reported as valid here
     * and still be refused by the mail server (revoked application, missing
     * API permission, ...); testConnection() is what answers that question.
     *
     * The color is a Tabler color name, and the icon a round Tabler icon, both
     * meant to be rendered by getStatusHtml().
     *
     * @return array{code: string, label: string, color: string, icon: string, expiration: ?string}
     */
    public function getTokenStatus(): array
    {
        if (empty($this->fields['token'])) {
            return self::buildTokenStatus(self::TOKEN_MISSING, null);
        }

        $token = $this->getAccessToken();
        if ($token === null) {
            return self::buildTokenStatus(self::TOKEN_UNREADABLE, null);
        }

        $expires = (int) $token->getExpires();
        if ($expires <= 0) {
            return self::buildTokenStatus(self::TOKEN_VALID, null);
        }

        $expiration = date('Y-m-d H:i:s', $expires);

        if (!self::isTokenExpired($token)) {
            return self::buildTokenStatus(self::TOKEN_VALID, $expiration);
        }

        return !empty($this->fields['refresh_token'])
            ? self::buildTokenStatus(self::TOKEN_RENEWABLE, $expiration)
            : self::buildTokenStatus(self::TOKEN_EXPIRED, $expiration);
    }

    /**
     * Label, color and icon used to present each token status.
     *
     * @return array<string, array{label: string, color: string, icon: string}>
     */
    private static function getTokenStatusPresentations(): array
    {
        return [
            self::TOKEN_VALID => [
                'label' => __('Authorized'),
                'color' => 'green',
                'icon'  => 'ti ti-circle-check',
            ],
            self::TOKEN_RENEWABLE => [
                'label' => __('Renewable'),
                'color' => 'orange',
                'icon'  => 'ti ti-alert-circle',
            ],
            self::TOKEN_EXPIRED => [
                'label' => __('Expired'),
                'color' => 'red',
                'icon'  => 'ti ti-circle-x',
            ],
            self::TOKEN_UNREADABLE => [
                'label' => __('Unreadable token'),
                'color' => 'red',
                'icon'  => 'ti ti-circle-x',
            ],
            self::TOKEN_MISSING => [
                'label' => __('Not authorized'),
                'color' => 'secondary',
                'icon'  => 'ti ti-info-circle',
            ],
        ];
    }

    /**
     * @return array{code: string, label: string, color: string, icon: string, expiration: ?string}
     */
    private static function buildTokenStatus(string $code, ?string $expiration): array
    {
        return [
            'code'       => $code,
            'expiration' => $expiration,
        ] + self::getTokenStatusPresentations()[$code];
    }

    /**
     * Tells whether a token is past its expiration date.
     *
     * AccessToken::hasExpired() throws when the provider did not advertise any
     * expiration date, which cannot be treated as an error here: such a token
     * is simply of unknown lifetime, so it is considered still usable and left
     * to the mail server to accept or refuse.
     */
    private static function isTokenExpired(AccessToken $token): bool
    {
        $expires = (int) $token->getExpires();

        return $expires > 0 && $expires < time();
    }

    /**
     * Returns the currently stored access token, if any.
     */
    public function getAccessToken(): ?AccessToken
    {
        if (empty($this->fields['token'])) {
            return null;
        }

        try {
            $decrypted = (new GLPIKey())->decrypt($this->fields['token']);
            $data = json_decode($decrypted ?? '', true, flags: JSON_THROW_ON_ERROR);
            return new AccessToken($data);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Manually refreshes the access token using the stored refresh token.
     */
    public function refreshToken(): bool
    {
        $this->error = null;

        if (empty($this->fields['refresh_token'])) {
            $this->error = __('No refresh token is available for this authorization');
            return false;
        }

        $application = new OAuthApplication();
        if (!$application->getFromDB($this->fields['oauth_applications_id'])) {
            $this->error = __('Invalid OAuth application');
            return false;
        }

        $refresh_token = (new GLPIKey())->decrypt($this->fields['refresh_token']);

        try {
            $provider = $this->resolveProvider($application, $this->fields['type']);
            $token = $provider->getAccessToken('refresh_token', ['refresh_token' => $refresh_token]);
        } catch (Throwable $e) {
            global $PHPLOGGER;
            $PHPLOGGER->warning(
                sprintf('Error during token refresh: %s', $e->getMessage()),
                ['exception' => $e]
            );
            $this->error = sprintf(__('Unable to refresh access token: %s'), $e->getMessage());
            return false;
        }

        $input = [
            'id'    => $this->fields['id'],
            'token' => json_encode($token->jsonSerialize()),
        ];

        $new_refresh_token = $token->getRefreshToken();
        if (!empty($new_refresh_token) && $new_refresh_token !== $refresh_token) {
            $input['refresh_token'] = $new_refresh_token;
        }

        return $this->update($input);
    }

    /**
     * Attempts a real (but minimal) protocol-level connection using the
     * current access token, refreshing it first if needed.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        $token = $this->getAccessToken();
        if ($token === null || self::isTokenExpired($token)) {
            if (!$this->refreshToken()) {
                return [
                    'success' => false,
                    'message' => $this->getLastError() ?? __('Unable to obtain a valid access token'),
                ];
            }
            $token = $this->getAccessToken();
        }

        if ($token === null) {
            return ['success' => false, 'message' => __('Unable to obtain a valid access token')];
        }

        $application = new OAuthApplication();
        if (!$application->getFromDB($this->fields['oauth_applications_id'])) {
            return ['success' => false, 'message' => __('Invalid OAuth application')];
        }

        try {
            if ($this->fields['type'] === self::TYPE_SMTP) {
                // No SMTP host/port is stored on this entity (SMTP sending
                // still goes through the legacy glpi_configs-based flow), so
                // only the token validity checked above can be verified here.
                return $this->probeSmtpConnection('', 0, $this->fields['email'], $token->getToken());
            }

            $defaults = $this->resolveProvider($application, self::TYPE_IMAP)::getImapDefaults();

            return $this->probeImapConnection(
                $defaults['host'],
                $defaults['port'],
                $defaults['ssl'],
                $this->fields['email'],
                $token->getToken()
            );
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Performs a minimal IMAP `AUTHENTICATE XOAUTH2` handshake and reports
     * what the server actually answered.
     *
     * Isolated in its own (protected) method so tests can stub it out
     * without opening a real network connection.
     *
     * @return array{success: bool, message: string}
     */
    protected function probeImapConnection(string $host, int $port, string $ssl, string $email, string $token): array
    {
        $transport = match (strtoupper($ssl)) {
            'SSL'   => 'ssl://',
            'TLS'   => 'tls://',
            default => '',
        };

        $errno = 0;
        $errstr = '';
        // Warnings are silenced on purpose: an unreachable mail server is an
        // expected diagnostic outcome, reported through the returned message.
        $socket = @stream_socket_client( // @phpstan-ignore theCodingMachineSafe.function
            $transport . $host . ':' . $port,
            $errno,
            $errstr,
            self::PROBE_TIMEOUT
        );
        if ($socket === false) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Unable to connect to %1$s:%2$d: %3$s'),
                    $host,
                    $port,
                    $errstr !== '' ? $errstr : __('connection refused')
                ),
            ];
        }

        stream_set_timeout($socket, self::PROBE_TIMEOUT);

        try {
            $greeting = fgets($socket);
            if ($greeting === false) {
                return [
                    'success' => false,
                    'message' => sprintf(__('%s closed the connection without sending a greeting.'), $host),
                ];
            }
            $matches = [];
            preg_match('/^\*\s+(BYE|NO|BAD)\b\s*(.*)$/i', trim($greeting), $matches);
            if ($matches !== []) {
                return [
                    'success' => false,
                    'message' => sprintf(__('%1$s refused the connection: %2$s'), $host, trim($matches[2])),
                ];
            }

            $auth = base64_encode(sprintf("user=%s\1auth=Bearer %s\1\1", $email, $token));
            fwrite($socket, 'GLPI1 AUTHENTICATE XOAUTH2 ' . $auth . "\r\n");

            // On failure, the server first sends a `+ <base64 JSON>` challenge
            // describing the error and waits for an empty client response
            // before sending its final tagged answer. Untagged `*` lines may
            // also be interleaved, so read until the tagged line shows up.
            $challenge = null;
            while (($line = fgets($socket)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if (str_starts_with($line, '+')) {
                    $challenge = trim(substr($line, 1));
                    fwrite($socket, "\r\n");
                    continue;
                }

                if (str_starts_with($line, '*')) {
                    continue; // untagged information, not the command result
                }

                $matches = [];
                preg_match('/^GLPI1\s+(OK|NO|BAD)\b\s*(.*)$/i', $line, $matches);
                if ($matches !== []) {
                    if (strtoupper($matches[1]) === 'OK') {
                        return ['success' => true, 'message' => sprintf(__('Connected to %s successfully.'), $host)];
                    }

                    return [
                        'success' => false,
                        'message' => $this->explainXoauth2Failure(trim($matches[2]), $challenge),
                    ];
                }
            }

            return [
                'success' => false,
                'message' => sprintf(
                    __('No answer from %s (the connection timed out or was closed during authentication).'),
                    $host
                ),
            ];
        } finally {
            fclose($socket);
        }
    }

    /**
     * Performs a minimal SMTP `EHLO` + `AUTH XOAUTH2` handshake.
     *
     * Isolated in its own (protected) method so tests can stub it out
     * without opening a real network connection.
     *
     * @return array{success: bool, message: string}
     */
    protected function probeSmtpConnection(string $host, int $port, string $email, string $token): array
    {
        if ($host === '') {
            // No SMTP host configured on this authorization: only the token
            // validity (already ensured by testConnection()) can be checked.
            return [
                'success' => true,
                'message' => __('The access token is valid. No SMTP server is configured on this authorization, so the connection itself was not tested.'),
            ];
        }

        $errno = 0;
        $errstr = '';
        // Warnings are silenced on purpose: an unreachable mail server is an
        // expected diagnostic outcome, reported through the returned message.
        $socket = @stream_socket_client($host . ':' . $port, $errno, $errstr, self::PROBE_TIMEOUT); // @phpstan-ignore theCodingMachineSafe.function
        if ($socket === false) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Unable to connect to %1$s:%2$d: %3$s'),
                    $host,
                    $port,
                    $errstr !== '' ? $errstr : __('connection refused')
                ),
            ];
        }

        stream_set_timeout($socket, self::PROBE_TIMEOUT);

        try {
            fgets($socket); // greeting
            fwrite($socket, "EHLO glpi\r\n");
            while (($line = fgets($socket)) !== false && preg_match('/^250-/', $line) === 1) {
                // consume multi-line EHLO response
            }

            $auth = base64_encode(sprintf("user=%s\1auth=Bearer %s\1\1", $email, $token));
            fwrite($socket, 'AUTH XOAUTH2 ' . $auth . "\r\n");

            $challenge = null;
            while (($response = fgets($socket)) !== false) {
                $response = trim($response);
                if ($response === '') {
                    continue;
                }

                if (str_starts_with($response, '334')) {
                    $challenge = trim(substr($response, 3));
                    fwrite($socket, "\r\n");
                    continue;
                }

                if (str_starts_with($response, '235')) {
                    return ['success' => true, 'message' => sprintf(__('Connected to %s successfully.'), $host)];
                }

                return [
                    'success' => false,
                    'message' => $this->explainXoauth2Failure($response, $challenge),
                ];
            }

            return [
                'success' => false,
                'message' => sprintf(
                    __('No answer from %s (the connection timed out or was closed during authentication).'),
                    $host
                ),
            ];
        } finally {
            fclose($socket);
        }
    }

    /**
     * Turns a rejected XOAUTH2 handshake into an actionable explanation.
     *
     * Providers answer a failed XOAUTH2 authentication with a base64-encoded
     * JSON payload (the SASL challenge) telling why the token was refused and
     * which scope the server expected.
     */
    private function explainXoauth2Failure(string $server_response, ?string $challenge): string
    {
        $payload = null;
        if ($challenge !== null && $challenge !== '') {
            try {
                $payload = json_decode(base64_decode($challenge, true), true);
            } catch (UrlException|JsonException) {
                // Unreadable challenge: fall back to the generic explanations.
            }
        }

        $status = is_array($payload) ? (string) ($payload['status'] ?? '') : '';
        $scope  = is_array($payload) ? (string) ($payload['scope'] ?? '') : '';

        $lines = [$this->getXoauth2FailureHint($server_response, $status)];

        if ($scope !== '') {
            $lines[] = sprintf(__('Scope expected by the server: %s'), $scope);
        }
        if ($status !== '') {
            $lines[] = sprintf(__('Status returned by the server: %s'), $status);
        }
        if ($server_response !== '') {
            $lines[] = sprintf(__('Server response: %s'), $server_response);
        }

        return implode("\n", $lines);
    }

    /**
     * Returns the most specific explanation available for a rejected
     * XOAUTH2 handshake.
     */
    private function getXoauth2FailureHint(string $server_response, string $status): string
    {
        // Exchange Online answers this when the token itself was accepted but
        // the mailbox could not be opened over the requested protocol.
        if (stripos($server_response, 'authenticated but not connected') !== false) {
            return __('The account was authenticated, but the mailbox could not be opened over this protocol. Check that IMAP is enabled for this mailbox, that the application has the "IMAP.AccessAsUser.All" permission with administrator consent, and that the account has a mailbox licence.');
        }

        if (stripos($server_response, 'IMAP access is disabled') !== false) {
            return __('IMAP access is disabled for this account. Enable it in the mailbox settings.');
        }

        return match ($status) {
            '400' => __('The mail server rejected the access token: it does not carry the permission required by this protocol. Grant the matching API permission to the application (with administrator consent), then create the authorization again.'),
            '401' => __('The mail server refused the access token. It may have been revoked, or this mailbox may not be allowed to use this protocol.'),
            default => __('The mail server refused the authentication. Check that the protocol is enabled for this mailbox and that the application has the required permission.'),
        };
    }

    /**
     * Revokes (deletes) this authorization. There is no provider-side
     * revocation endpoint to call; removing the row is the only local
     * "revoke" action, matching the behavior of the reference implementation.
     */
    public function revokeAuthorization(): bool
    {
        return $this->delete(['id' => $this->getID()], true);
    }
}

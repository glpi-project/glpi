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

declare(strict_types=1);

namespace Glpi\Security\ReAuth;

use Auth;
use DOMDocument;
use Glpi\Error\ErrorHandler;
use Glpi\Toolbox\HttpClient;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use User;

use function Safe\preg_replace;

/**
 * CAS re-authentication strategy.
 *
 * Verified out of band: the browser is sent to the CAS server with `renew=true`, which forces the
 * user to type their credentials again even when a CAS session is open. On the way back, the
 * service ticket is validated with `renew=true` as well, so that the CAS server rejects any ticket
 * that was not issued from a fresh credential entry, and the returned identity must be the one
 * of the user being re-authenticated.
 *
 * phpCAS is not used: its renewAuthentication() trusts the CAS user kept in session since the
 * login, and would never ask for the password again.
 *
 * It fails closed: an unreachable CAS server, an invalid ticket or an identity mismatch deny the
 * re-authentication.
 */
final class CasReAuthStrategy implements ReAuthStrategyInterface
{
    /**
     * Session key of the single-use marker binding a CAS round-trip to the user who started it.
     */
    private const string ROUND_TRIP_USERS_ID_KEY = 'glpi_reauth_cas_users_id';

    private const string CAS_NAMESPACE = 'http://www.yale.edu/tp/cas';

    public function __construct(
        private readonly ?HttpClient $http_client = null,
    ) {}

    #[Override]
    public function verify(int $users_id, Request $request): bool
    {
        // Verification is done by complete(), on the way back from the CAS server. A submission
        // to the core verify endpoint (e.g. a stale prompt) fails and re-renders the prompt.
        return false;
    }

    #[Override]
    public function getVerifyUrl(): string
    {
        global $CFG_GLPI;

        return $CFG_GLPI['root_doc'] . '/ReAuth/CAS';
    }

    #[Override]
    public function getVerifyHttpMethod(): string
    {
        return 'GET';
    }

    #[Override]
    public function isAvailable(int $users_id, int $entities_id = 0): bool
    {
        global $CFG_GLPI;

        // Whatever the user record, a session opened through CAS is verified by the CAS server.
        return ($_SESSION['glpiauthtype'] ?? Auth::NOT_YET_AUTHENTIFIED) === Auth::CAS
            && !empty($CFG_GLPI['cas_host']);
    }

    #[Override]
    public function getLabel(): string
    {
        return __('CAS');
    }

    #[Override]
    public function getPromptTemplate(): string
    {
        return 'pages/reauth/cas_form.html.twig';
    }

    #[Override]
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Start the round-trip for the given user and return the CAS login URL to redirect to.
     */
    public function start(int $users_id): string
    {
        $_SESSION[self::ROUND_TRIP_USERS_ID_KEY] = $users_id;

        return $this->getServerBaseUrl() . 'login?' . http_build_query([
            'service' => $this->getServiceUrl(),
            'renew'   => 'true',
        ]);
    }

    /**
     * Complete the round-trip started by start(), from the request coming back from CAS.
     */
    public function complete(int $users_id, Request $request): bool
    {
        // Single-use: consumed whatever the outcome.
        $started_by = $_SESSION[self::ROUND_TRIP_USERS_ID_KEY] ?? null;
        unset($_SESSION[self::ROUND_TRIP_USERS_ID_KEY]);

        if ($started_by !== $users_id) {
            return false;
        }

        $service_ticket = $request->query->get('ticket');
        if (!is_string($service_ticket) || $service_ticket === '') {
            return false;
        }

        $cas_user = $this->validateTicket($service_ticket);
        if ($cas_user === null) {
            return false;
        }

        // Resolved as the CAS login does. The identity kept by phpCAS is not used: during an
        // impersonation, it is the impersonator's one.
        $user = new User();
        return $user->getFromDBbyName($cas_user) && $user->getID() === $users_id;
    }

    /**
     * Validate the service ticket against the CAS server and return the authenticated user.
     */
    private function validateTicket(string $service_ticket): ?string
    {
        global $CFG_GLPI;

        $path = match ($CFG_GLPI['cas_version']) {
            'CAS_VERSION_1_0' => 'validate',
            'CAS_VERSION_2_0' => 'serviceValidate',
            default           => 'p3/serviceValidate',
        };

        try {
            $response = ($this->http_client ?? new HttpClient(context: Auth::class))->request(
                'GET',
                $this->getServerBaseUrl() . $path,
                [
                    'query' => [
                        'service' => $this->getServiceUrl(),
                        'ticket'  => $service_ticket,
                        'renew'   => 'true',
                    ],
                    'timeout'      => 10,
                    'max_duration' => 10,
                ]
            );
            $content = $response->getContent();
        } catch (Throwable $e) {
            ErrorHandler::logCaughtException($e);
            return null;
        }

        return $path === 'validate'
            ? $this->parseCas10Response($content)
            : $this->parseCas20Response($content);
    }

    /**
     * CAS 1.0: "yes\n<user>\n" on success, "no\n\n" otherwise.
     */
    private function parseCas10Response(string $content): ?string
    {
        $lines = explode("\n", str_replace("\r", '', $content));
        if ($lines[0] !== 'yes' || !isset($lines[1]) || $lines[1] === '') {
            return null;
        }

        return $lines[1];
    }

    /**
     * CAS 2.0 / 3.0: <cas:serviceResponse><cas:authenticationSuccess><cas:user>.
     */
    private function parseCas20Response(string $content): ?string
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($content, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return null;
        }

        $success = $document->getElementsByTagNameNS(self::CAS_NAMESPACE, 'authenticationSuccess')->item(0);
        if ($success === null) {
            return null;
        }

        $user = $success->getElementsByTagNameNS(self::CAS_NAMESPACE, 'user')->item(0);
        $name = $user !== null ? trim($user->textContent) : '';

        return $name !== '' ? $name : null;
    }

    /**
     * Same base URL as phpCAS: https://<host>[:<port>]/<uri>/.
     */
    private function getServerBaseUrl(): string
    {
        global $CFG_GLPI;

        $port = (int) $CFG_GLPI['cas_port'];
        $uri = preg_replace('#/+#', '/', '/' . $CFG_GLPI['cas_uri'] . '/');

        return 'https://' . $CFG_GLPI['cas_host'] . ($port !== 443 ? ':' . $port : '') . $uri;
    }

    /**
     * Callback URL, built from url_base as the CAS login does: the CAS server only issues
     * tickets for the services it knows.
     */
    private function getServiceUrl(): string
    {
        global $CFG_GLPI;

        return rtrim($CFG_GLPI['url_base'], '/') . '/ReAuth/CAS/Callback';
    }
}

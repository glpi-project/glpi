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

namespace Glpi\Tests\Glpi\Security\ReAuth;

use Glpi\Security\ReAuth\ReAuthManager;
use Glpi\Security\ReAuth\ReAuthStrategyEnum;
use Glpi\Security\ReAuth\ReAuthStrategyInterface;
use Glpi\Security\ReAuth\RedirectReAuthStrategyInterface;

/**
 * Helpers shared by the re-authentication tests: fake a web request context and
 * toggle the current session's re-authentication state.
 */
trait ReAuthTrait
{
    private function getReAuthManager(): ReAuthManager
    {
        return ReAuthManager::getInstance();
    }

    /**
     * Wipe the SingletonTrait instance cache so the next getInstance() returns a fresh manager.
     */
    private function resetReAuthManager(): void
    {
        $instances = new \ReflectionProperty(ReAuthManager::class, '_instances');
        $instances->setValue(null, []);
    }

    /**
     * Simulate a web (non-CLI) request context so that re-authentication
     * redirects can be triggered from a test.
     *
     * The superglobals populated here are the ones ReAuthManager::redirectToReauth()
     * reads to record the request that triggered the re-authentication.
     *
     * @param array<string, string> $get
     * @param array<string, string> $post
     */
    private function fakeWebContext(
        string $request_uri = '/front/central.php',
        string $method = 'GET',
        array $get = [],
        array $post = [],
        string $referer = 'https://glpi.example.org/front/central.php',
    ): void {
        // getMainRequest() falls back to Request::createFromGlobals() in the test context, so the
        // super globals must carry the exact keys Symfony reads: HTTPS (not REQUEST_SCHEME) drives
        // isSecure(), and QUERY_STRING (not the query part of REQUEST_URI) drives getUri().
        $query_string = parse_url($request_uri, PHP_URL_QUERY) ?? '';
        parse_str($query_string, $get_from_uri);

        $GLOBALS['GLPI_IS_COMMAND_LINE'] = false;
        $_SERVER['HTTPS']          = 'on';
        $_SERVER['HTTP_HOST']      = 'glpi.example.org';
        $_SERVER['REQUEST_URI']    = $request_uri;
        $_SERVER['QUERY_STRING']   = $query_string;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_REFERER']   = $referer;
        // A real web request always carries a client IP; without it SessionTracker::recordNewSession()
        // would try to insert a NULL ip_address when login() is called under the faked web context.
        $_SERVER['REMOTE_ADDR']    = '127.0.0.1';
        $_GET  = $get ?: $get_from_uri;
        $_POST = $post;
    }

    /**
     * Restore the globals mutated by fakeWebContext(). Must be called from tearDown().
     */
    private function restoreWebContext(): void
    {
        unset(
            $GLOBALS['GLPI_IS_COMMAND_LINE'],
            $_SERVER['HTTPS'],
            $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['QUERY_STRING'],
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['HTTP_REFERER'],
            $_SERVER['REMOTE_ADDR'],
        );
    }

    private function setReauthenticated(bool $reauthenticated): void
    {
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        if ($reauthenticated) {
            ($this->getReAuthManager())->authenticate();
        } else {
            unset($_SESSION['glpi_reauth_until']);
        }
    }

    /**
     * Highest priority among the native strategies currently available to the user.
     *
     * Mirrors the native branch of ReAuthManager::getAvailableStrategies() so tests
     * registering a plugin strategy can assert it strictly outranks everything the
     * user already has (a tie would let a native strategy win, since native ones are
     * resolved first and usort() is stable).
     */
    private function getHighestAvailableStrategyPriority(int $users_id): int
    {
        $priorities = [];
        foreach (ReAuthStrategyEnum::cases() as $case) {
            $strategy = $case->createStrategy();
            if ($strategy->isAvailable($users_id)) {
                $priorities[] = $strategy->getPriority();
            }
        }

        return $priorities === [] ? 0 : max($priorities);
    }

    /**
     * Build a throwaway strategy with a controllable label, priority and availability.
     */
    private function makeStrategy(string $label, int $priority, bool $available): ReAuthStrategyInterface
    {
        return new readonly class ($label, $priority, $available) implements ReAuthStrategyInterface {
            public function __construct(
                private string $label,
                private int $priority,
                private bool $available,
            ) {}

            public function verify(int $users_id, string $user_input): bool
            {
                return true;
            }

            public function isAvailable(int $users_id, int $entities_id = 0): bool
            {
                return $this->available;
            }

            public function getLabel(): string
            {
                return $this->label;
            }

            public function getPromptTemplate(): string
            {
                return 'pages/reauth/password_form.html.twig';
            }

            public function getPriority(): int
            {
                return $this->priority;
            }
        };
    }

    /**
     * Build a throwaway redirect-based strategy (verified out-of-band), with a
     * controllable label, priority, availability and re-authentication URL.
     */
    private function makeRedirectStrategy(
        string $label,
        int $priority,
        bool $available,
        string $reauth_url = '/plugins/example/front/reauth.php',
    ): RedirectReAuthStrategyInterface {
        return new readonly class ($label, $priority, $available, $reauth_url) implements RedirectReAuthStrategyInterface {
            public function __construct(
                private string $label,
                private int $priority,
                private bool $available,
                private string $reauth_url,
            ) {}

            public function verify(int $users_id, string $user_input): bool
            {
                throw new \LogicException('Redirect strategies are verified out-of-band.');
            }

            public function isAvailable(int $users_id, int $entities_id = 0): bool
            {
                return $this->available;
            }

            public function getLabel(): string
            {
                return $this->label;
            }

            public function getPromptTemplate(): string
            {
                return 'pages/reauth/password_form.html.twig';
            }

            public function getPriority(): int
            {
                return $this->priority;
            }

            public function getReauthUrl(int $users_id): string
            {
                return $this->reauth_url;
            }
        };
    }
}

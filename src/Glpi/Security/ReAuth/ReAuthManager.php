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

use Glpi\Exception\RedirectException;
use InvalidArgumentException;
use RuntimeException;
use Safe\DateTime;

use function Safe\parse_url;

final class ReAuthManager
{
    public const int REAUTH_DELAY_SECONDS = 15 * MINUTE_TIMESTAMP;

    private ?ReAuthStrategyInterface $strategy = null;

    /**
     * @throws RedirectException
     */
    public function checkReAuthenticationOrRedirect(): void
    {
        if ($this->isReAuthenticated()) {
            return;
        }

        $this->redirectToReauth();
    }

    /**
     * Redirect to reauth prompt and save current request data (url + post data)
     *
     * @throws RedirectException
     */
    public function redirectToReauth(): never
    {
        global $CFG_GLPI;

        $this->setRequestedTarget();

        $referer = \Html::getRefererUrl();
        // Set Origin URL except if it's a path to the reauth controller, to prevent looping.
        $this->setOriginURL(
            ($referer !== null && !$this->isReAuthRoute($referer)) ? $referer : $CFG_GLPI["root_doc"]
        );

        throw new RedirectException('/ReAuth/Prompt');
    }

    /**
     * User has a valid reauth session
     */
    public function isReAuthenticated(): bool
    {
        $current_limit_timestamp = $_SESSION['glpi_reauth_until'] ?? null;
        $calculated_limit_timestamp = (new DateTime($_SESSION['glpi_currenttime']))->getTimestamp();

        return $current_limit_timestamp !== null && $current_limit_timestamp > $calculated_limit_timestamp;
    }

    public function verify(string $user_input): bool
    {
        $strategy = $this->getStrategy();

        return $strategy->verify($_SESSION['glpiID'], $user_input);
    }

    /**
     * Make the user reauthenticated
     *
     * Consider current user as reauthenticated
     * Set the reauth session validity to now + delay (self::REAUTH_DELAY_SECONDS).
     * Used to make user reauthenticated just after login.
     */
    public function authenticate(): void
    {
        $_SESSION['glpi_reauth_until'] = (new DateTime($_SESSION['glpi_currenttime']))
            ->modify('+' . self::REAUTH_DELAY_SECONDS . ' seconds')
            ->getTimestamp();
    }

    public function getLabel(): string
    {
        return $this->getStrategy()->getLabel();
    }

    public function getPromptTemplate(): string
    {
        return $this->getStrategy()->getPromptTemplate();
    }

    public function getRequestedURL(): string
    {
        return $_SESSION['glpi_reauth_requested_url'] ?? '/';
    }

    /**
     * URL of the page the user was on when the reauth-requiring action was triggered.
     *
     * Used both as the "Cancel" target on the reauth prompt and as the referer
     * of the replayed request (@see getRedirectData()).
     */
    public function getOriginURL(): string
    {
        global $CFG_GLPI;

        return $_SESSION['glpi_reauth_origin_url'] ?? $CFG_GLPI["root_doc"];
    }

    public function setOriginURL(string $url): void
    {
        $_SESSION['glpi_reauth_origin_url'] = $url;
    }

    /**
     * query data + _glpi_http_referer (POST replays only)
     *
     * `_glpi_http_referer` is set to the origin page (the referer captured on
     * the first pass, @see getOriginURL()) so that Html::back()/getBackUrl() on
     * the replayed request returns the original page and not the reauth flow.
     * Html::getRefererUrl() only reads $_POST['_glpi_http_referer'], so it is
     * pointless to inject it for a replayed GET request (it would only ever
     * land in $_GET).
     *
     * @return array<string, string>
     */
    public function getRequestedPostData(): array
    {
        $reauth_data = $_SESSION['glpi_reauth_requested_post_data'] ?? [];

        if ($this->getRequestedMethod() !== 'POST') {
            return $reauth_data;
        }

        return $reauth_data + ['_glpi_http_referer' => $this->getOriginURL()];
    }

    /**
     * @return 'POST'|'GET'
     */
    public function getRequestedMethod(): string
    {
        return $_SESSION['glpi_reauth_requested_httpmethod'] ?? 'GET';
    }

    /**
     * returns true if at least one of the item_types require reauth
     *
     * @param array<int, class-string<\CommonGLPI>> $item_types item type to check
     */
    public function atLeastOneItemTypesRequiresReauthentication(array $item_types): bool
    {
        foreach ($item_types as $item_type) {
            if (!is_a($item_type, \CommonGLPI::class, true)) {
                throw new InvalidArgumentException(sprintf('Invalid item type "%s"', (string) $item_type));
            }
        }

        return array_reduce(
            $item_types,
            fn($carry, string $item_type) => $carry || $item_type::isUserReauthenticationNeeded(),
            false
        );
    }

    /**
     * Record the request that was requires reauthentication (url + POST/GET data)
     */
    private function setRequestedTarget(): void
    {
        $is_post = $_SERVER['REQUEST_METHOD'] === 'POST';

        // For POST requests, the GET query string in the action URL is preserved by the
        // browser on replay, so it must be stored. For GET requests, the browser rebuilds
        // the query string from the form fields on submit, so keeping it here would be
        // both useless and misleading.
        $request_path = $is_post ? $_SERVER['REQUEST_URI'] : explode('?', $_SERVER['REQUEST_URI'])[0];
        $current_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $request_path; // @todo utiliser requete symfony présente dans main à la base ? &| sanitize url

        $this->setRequestedURL($current_url);
        $this->setRequestedMethod($is_post ? 'POST' : 'GET');
        $this->setRequestedData($is_post ? $_POST : $_GET);
    }

    private function getStrategy(): ReAuthStrategyInterface
    {
        if ($this->strategy === null) {
            $this->strategy = $this->resolvePreferred($_SESSION['glpiID']);
        }

        return $this->strategy;
    }

    private function resolvePreferred(int $users_id): ReAuthStrategyInterface
    {
        $available = $this->getAvailableStrategies($users_id);

        if ($available === []) {
            throw new RuntimeException('No re-authentication strategy available for this user');
        }

        // Sort strategies by priority (descending): highest priority first
        usort($available, static fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        return $available[0];
    }

    private function isReAuthRoute(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return str_starts_with($path, '/ReAuth/');
    }

    /**
     * @return ReAuthStrategyInterface[]
     */
    private function getAvailableStrategies(int $users_id): array
    {
        $strategies = [];

        foreach (ReAuthStrategyEnum::cases() as $case) {
            $strategy = $case->createStrategy();
            if ($strategy->isAvailable($users_id)) {
                $strategies[] = $strategy;
            }
        }

        return $strategies;
    }

    private function setRequestedURL(string $url): void
    {
        $_SESSION['glpi_reauth_requested_url'] = $url;
    }

    /** @param array<string, string> $post */
    private function setRequestedData(array $post): void
    {
        $_SESSION['glpi_reauth_requested_post_data'] = $post;
    }

    /**
     * @param 'POST'|'GET' $http_method
     */
    private function setRequestedMethod(string $http_method): void
    {
        $_SESSION['glpi_reauth_requested_httpmethod'] = match ($http_method) {
            'GET'  => 'GET',
            'POST' => 'POST',
            default => throw new \LogicException(sprintf('Unsupported HTTP method for redirect: %s', $http_method)),
        };
    }
}

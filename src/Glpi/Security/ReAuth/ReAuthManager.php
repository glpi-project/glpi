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
use RuntimeException;
use Safe\DateTime;

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

        $this->redirect();
    }

    /**
     * Redirect to reauth prompt and save current request data (url + post data)
     *
     * @throws RedirectException
     */
    public function redirect(): never
    {
        $this->setRequestedTarget();
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

    public function initiate(): void
    {
        $this->authenticate();
    }

    public function verify(string $user_input): bool
    {
        $strategy = $this->getStrategy();

        return $strategy->verify($_SESSION['glpiID'], $user_input);
    }

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

    public function getTargetURL(): string
    {
        return $_SESSION['glpi_reauth_target_url'] ?? '/';
    }

    public function getCancelURL(): string
    {
        return $_SESSION['glpi_reauth_cancel_url'] ?? $this->getRedirectURL();
    }

    /** @return array<string, string> */
    public function getRedirectData(): array
    {
        return $_SESSION['glpi_reauth_data'] ?? [];
    }

    /**
     * @return 'POST'|'GET'
     */
    public function getRedirectMethod(): string
    {
        return $_SESSION['glpi_reauth_httpmethod'] ?? 'GET';
    }

    public function setCancelURL(string $url): void
    {
        $_SESSION['glpi_reauth_cancel_url'] = $url;
    }

    /**
     * returns true if at least one of the item_types require reauth
     *
     * @param array<int, class-string<\CommonGLPI>> $item_types item type to check
     */
    public function atLeastOneitemTypesRequiresReauthentication(mixed $item_types): bool
    {
        // @todo ajouter vérif sur la validité des item_types (doivent être des class-string de CommonGLPI) ?
        return array_reduce(
            $item_types,
            fn($carry, string $item_type) => $carry || $item_type::isUserReauthenticationNeeded(),
            false
        );
    }

    /**
     * Record the request that was requested before checking that a reauth is needed.
     */
    private function setRequestedTarget(): void
    {
        $current_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'])[0];

        $this->setRequestedURL($this->forcedRequestedURL ?? $current_url);
        $this->setRequestedMethod($_SERVER['REQUEST_METHOD'] === 'POST' ? 'POST' : 'GET');
        $this->setRequestedData($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET);
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
        $_SESSION['glpi_reauth_target_url'] = $url;
    }

    /** @param array<string, string> $post */
    private function setRequestedData(array $post): void
    {
        $_SESSION['glpi_reauth_data'] = $post;
    }

    /**
     * @param 'POST'|'GET' $http_method
     */
    private function setRequestedMethod(string $http_method): void
    {
        $_SESSION['glpi_reauth_httpmethod'] = match ($http_method) {
            'GET'  => 'GET',
            'POST' => 'POST',
            default => throw new \LogicException(sprintf('Unsupported HTTP method for redirect: %s', $http_method)),
        };
    }
}

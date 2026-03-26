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
    public const REAUTH_DELAY_SECONDS = 15 * MINUTE_TIMESTAMP;

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
        global $CFG_GLPI;

        $this->setSuccessRedirectURL(\Html::getRefererUrl() ?? $CFG_GLPI['url_base']);
        $this->setPostDataForRedirect($_POST);

        throw new RedirectException('/ReAuth/Prompt');
    }

    public function isReAuthenticated(): bool
    {
        if (GLPI_DISABLE_REAUTH) {
            return true;
        }

        $current_limit_timestamp = $_SESSION['glpi_reauth_until'] ?? null;
        $calculated_limit_timestamp = new DateTime($_SESSION['glpi_currenttime'])->getTimestamp();

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

    public function getRedirectSuccessURL(): string
    {
        return $_SESSION['glpi_reauth_redirect'] ?? '/';
    }

    private function setSuccessRedirectURL(string $url): void
    {
        $_SESSION['glpi_reauth_redirect'] = $url;
    }

    /** @param array<string, string> $post */
    private function setPostDataForRedirect(array $post): void
    {
        $_SESSION['glpi_reauth_postdata'] = $post;
    }

    /** @return array<string, string> */
    public function getPostDataForRedirect(): array
    {
        return $_SESSION['glpi_reauth_postdata'] ?? [];
    }
}

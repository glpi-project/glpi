<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LegacyPostRequestActionsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::FINISH_REQUEST => ['onFinishRequest'],
        ];
    }

    public function onFinishRequest(): void
    {
        $this->resetSessionAjaxParam();
        $this->triggerGlobalsDeprecation();
    }

    private function resetSessionAjaxParam(): void
    {
        \Session::resetAjaxParam();
    }

    private function triggerGlobalsDeprecation(): void
    {
        /** @var int|bool|null $AJAX_INCLUDE */
        global $AJAX_INCLUDE;
        if (isset($AJAX_INCLUDE)) {
            \Toolbox::deprecated('Using the global "$AJAX_INCLUDE" variable has been removed. Use "$this->setAjax()" from your controllers instead.', version: "11.0");
        }

        /** @var string|null $SECURITY_STRATEGY */
        global $SECURITY_STRATEGY;
        if (isset($SECURITY_STRATEGY)) {
            \Toolbox::deprecated('Using the global "$SECURITY_STRATEGY" variable has been removed. Use proper Route attributes in your controllers instead.', version: "11.0");
        }
    }
}

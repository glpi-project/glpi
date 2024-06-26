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

namespace Glpi\Security;

use Glpi\Http\FirewallListener;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SecurityStrategyAttributeListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER_ARGUMENTS => 'onKernelControllerArguments'];
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        /** @var SecurityStrategy[] $attributes */
        if (!\is_array($attributes = $event->getAttributes()[SecurityStrategy::class] ?? null)) {
            return;
        }

        $number_of_attributes = \count($attributes);
        if ($number_of_attributes > 1) {
            throw new \RuntimeException(\sprintf(
                'You can apply only one security strategy per HTTP request. You actually used the "%s" attribute %d times.',
                SecurityStrategy::class,
                $number_of_attributes,
            ));
        }

        $attribute = $attributes[0];

        $event->getRequest()->attributes->set(FirewallListener::STRATEGY_KEY, $attribute->strategy);
    }
}

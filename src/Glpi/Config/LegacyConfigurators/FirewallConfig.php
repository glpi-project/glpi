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

namespace Glpi\Config\LegacyConfigurators;

use Glpi\Config\ConfigProviderHasRequestTrait;
use Glpi\Config\ConfigProviderWithRequestInterface;
use Glpi\Config\LegacyConfigProviderInterface;
use Glpi\Http\Firewall;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class FirewallConfig implements LegacyConfigProviderInterface, ConfigProviderWithRequestInterface
{
    use ConfigProviderHasRequestTrait;

    public function __construct(
        #[Autowire('@service_container')]
        private readonly ContainerInterface $container
    ) {
    }

    public function execute(): void
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        $firewall = new Firewall($CFG_GLPI['root_doc']);
        $this->container->set(Firewall::class, $firewall);
        $firewall->applyStrategy($this->getRequest()->server->get('PHP_SELF'), null);
    }
}

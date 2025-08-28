<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace GlpiPlugin\Tester\Form;

use Computer;
use Glpi\Form\ServiceCatalog\ServiceCatalogLeafInterface;
use Glpi\UI\IllustrationManager;
use Override;

// Example of how a core class can be enabled for the service catalog without
// modifying the core itself.
final class ComputerForServiceCatalog implements ServiceCatalogLeafInterface
{
    public function __construct(
        private Computer $computer,
    ) {}

    #[Override]
    public function getServiceCatalogLink(): string
    {
        return $this->computer->getLinkURL();
    }

    #[Override]
    public function getServiceCatalogItemTitle(): string
    {
        return $this->computer->fields['name'];
    }

    #[Override]
    public function getServiceCatalogItemDescription(): string
    {
        return $this->computer->fields['comment'];
    }

    #[Override]
    public function getServiceCatalogItemIllustration(): string
    {
        return IllustrationManager::DEFAULT_ILLUSTRATION;
    }

    #[Override]
    public function isServiceCatalogItemPinned(): bool
    {
        return false;
    }
}

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

namespace Glpi\Form\ServiceCatalog;

use Glpi\Form\ServiceCatalog\Provider\FormProvider;
use Glpi\Form\ServiceCatalog\Provider\KnowbaseItemProvider;
use Glpi\Form\ServiceCatalog\Provider\LeafProviderInterface;
use Glpi\Toolbox\SingletonTrait;

final class HomeSearchManager
{
    use SingletonTrait;

    /** @var int */
    private const MAX_ITEMS_PER_TYPE = 20;

    /** @var LeafProviderInterface[] */
    private array $providers;

    private bool $providers_are_sorted = false;

    public function __construct()
    {
        $this->providers = [
            FormProvider::getInstance(),
            new KnowbaseItemProvider(),
        ];
    }

    /** @return array<string, ServiceCatalogLeafInterface[]> */
    public function getItems(ItemRequest $item_request): array
    {
        $item_request->context = ItemRequestContext::HOME_PAGE_SEARCH;
        $items_by_label = [];

        foreach ($this->getProviders() as $leaf_provider) {
            $items = $leaf_provider->getItems($item_request);
            if ($items === []) {
                // Skip empty data
                continue;
            }

            // Limit result size
            $items = array_slice($items, 0, self::MAX_ITEMS_PER_TYPE);

            // Add items to results
            $items_by_label[$leaf_provider->getItemsLabel()] = $items;
        }

        return $items_by_label;
    }

    public function registerPluginProvider(
        LeafProviderInterface $provider
    ): void {
        $this->providers[] = $provider;
        $this->providers_are_sorted = false;
    }

    /** @return LeafProviderInterface[] */
    private function getProviders(): array
    {
        if (!$this->providers_are_sorted) {
            $this->sortProviders();
        }

        return $this->providers;
    }

    private function sortProviders(): void
    {
        usort(
            $this->providers,
            fn(
                LeafProviderInterface $a,
                LeafProviderInterface $b,
            ): int => $a->getWeight() <=> $b->getWeight()
        );
        $this->providers_are_sorted = true;
    }
}

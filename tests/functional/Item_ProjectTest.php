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

namespace tests\units;

use DbTestCase;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\IsProjectAssetCapacity;
use Glpi\Features\Clonable;
use Item_Project;
use Project;
use ProjectState;
use Symfony\Component\DomCrawler\Crawler;
use Toolbox;

class Item_ProjectTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: IsProjectAssetCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['project_asset_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Item_Project$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: IsProjectAssetCapacity::class)]);

        foreach ($CFG_GLPI['project_asset_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Item_Project::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testShowForProjectAndAsset(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->login();

        $project = $this->createItem(Project::class, [
            'name'        => $this->getUniqueString(),
            'entities_id' => $this->getTestRootEntity(true),
            'priority'    => 4,
            'code' => 'project_code',
            'projectstates_id' => getItemByTypeName(ProjectState::class, 'Processing', true),
            'percent_done' => 45,
            'date_creation' => '2025-08-17',
        ], ['date_creation']);

        $assets = [];

        foreach ($CFG_GLPI['project_asset_types'] as $itemtype) {
            $assets[$itemtype] = $this->createItem($itemtype, [
                'name' => __FUNCTION__,
                'designation' => __FUNCTION__,
                'entities_id' => $this->getTestRootEntity(true),
                'serial' => "{$itemtype}-serial",
                'otherserial' => "{$itemtype}-otherserial",
            ], ['name', 'designation', 'entities_id', 'serial', 'otherserial']);
            $this->createItem(Item_Project::class, [
                'projects_id' => $project->getID(),
                'itemtype'    => $itemtype,
                'items_id'    => $assets[$itemtype]->getID(),
            ]);
        }

        ob_start();
        Item_Project::showForProject($project);
        $out = ob_get_clean();

        $crawler = new Crawler($out);
        $rows = $crawler->filter('table tbody tr[data-itemtype="Item_Project"]');
        $this->assertCount(count($CFG_GLPI['project_asset_types']), $rows);
        $project_types = array_combine(
            array_map(static fn($t) => $t::getTypeName(1), $CFG_GLPI['project_asset_types']),
            $CFG_GLPI['project_asset_types'],
        );
        foreach ($rows as $row) {
            $cells = (new Crawler($row))->filter('td');
            $itemtype = $project_types[trim($cells->getNode(1)->textContent)];
            $item = getItemForItemtype($itemtype);
            $this->assertStringContainsString($item->isEntityAssign() ? 'Root entity > _test_root_entity' : '-', trim($cells->getNode(2)->textContent));
            $this->assertStringContainsString(__FUNCTION__, trim($cells->getNode(3)->textContent));
            $this->assertStringContainsString($item->isField('serial') ? "{$itemtype}-serial" : '-', trim($cells->getNode(4)->textContent));
            $this->assertStringContainsString($item->isField('otherserial') ? "{$itemtype}-otherserial" : '-', trim($cells->getNode(5)->textContent));
        }

        foreach ($assets as $asset) {
            ob_start();
            $this->callPrivateMethod(new Item_Project(), 'showForAsset', $asset);
            $out = ob_get_clean();

            $crawler = new Crawler($out);
            $rows = $crawler->filter('table tbody tr');
            $this->assertCount(1, $rows);
            $cells = (new Crawler($rows->getNode(0)))->filter('td');
            $this->assertStringContainsString($project->getName(), trim($cells->getNode(0)->textContent));
            $this->assertStringContainsString('High', trim($cells->getNode(1)->textContent));
            $this->assertStringContainsString('project_code', trim($cells->getNode(2)->textContent));
            $this->assertStringContainsString('Processing', trim($cells->getNode(3)->textContent));
            $progress = new Crawler($cells->getNode(4));
            $this->assertStringContainsString('45 %', $progress->filter('.progress-bar')->text());
            $this->assertStringContainsString('2025-08-17', trim($cells->getNode(5)->textContent));
        }
    }
}

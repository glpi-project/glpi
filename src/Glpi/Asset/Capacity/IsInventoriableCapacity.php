<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Asset\Capacity;

use CommonGLPI;
use Glpi\Inventory\Inventory;
use Item_Environment;
use Item_Process;
use Override;
use Session;

class IsInventoriableCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Inventory::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Inventory::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("The GLPI agent can report inventory data for these assets");
    }

    public function getSearchOptions(string $classname): array
    {
        $tab = \Agent::rawSearchOptionsToAdd();
        $tab[] = [
            'id'                 => 42,
            'table'              => 'glpi_autoupdatesystems',
            'field'              => 'name',
            'name'               => \AutoUpdateSystem::getTypeName(1),
            'datatype'           => 'dropdown'
        ];
        return $tab;
    }

    public function getCloneRelations(): array
    {
        return [
            Item_Process::class,
            Item_Environment::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && ($this->countAssets($classname, ['is_dynamic' => 1]) > 0);
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        $count = $this->countAssets($classname, ['is_dynamic' => 1]);
        if ($count === 0) {
            return __('Not used');
        }
        return sprintf(
            _n(
                'Used by %1$s asset',
                'Used by %1$s assets',
                $count
            ),
            $count
        );
    }

    public function onClassBootstrap(string $classname): void
    {
        $this->registerToTypeConfig('inventory_types', $classname);
        $this->registerToTypeConfig('agent_types', $classname);
        $this->registerToTypeConfig('environment_types', $classname);
        $this->registerToTypeConfig('process_types', $classname);
        $this->registerToTypeConfig('ruleimportasset_types', $classname);

        //copy rules from "inventory model" (Computer only for now)

        CommonGLPI::registerStandardTab($classname, Item_Environment::class, 85);
        CommonGLPI::registerStandardTab($classname, Item_Process::class, 85);
    }

    public function onCapacityEnabled(string $classname): void
    {
        //create rules
        $rules = new \RuleImportAsset();
        $rules->initRules(true, $classname);
    }

    public function onCapacityDisabled(string $classname): void
    {
        /** @var \DBmysql $DB */
        global $DB;
        $this->unregisterFromTypeConfig('inventory_types', $classname);
        $this->unregisterFromTypeConfig('agent_types', $classname);
        $this->unregisterFromTypeConfig('environment_types', $classname);
        $this->unregisterFromTypeConfig('process_types', $classname);
        $this->unregisterFromTypeConfig('ruleimportasset_types', $classname);

        $env_item = new Item_Environment();
        $env_item->deleteByCriteria([
            'itemtype' => $classname,
        ], true, false);
        $this->deleteRelationLogs($classname, Item_Environment::class);

        $process_item = new Item_Process();
        $process_item->deleteByCriteria([
            'itemtype' => $classname,
        ], true, false);

        $this->deleteRelationLogs($classname, Item_Process::class);

        //remove rules
        $where = ['sub_type' => \RuleImportAsset::class];
        $joins = [
            'LEFT JOIN' => [
                'glpi_rulecriterias' => [
                    'FKEY' => [
                        'glpi_rules' => 'id',
                        'glpi_rulecriterias' => 'rules_id'
                    ]
                ]
            ]
        ];
        $where += [
            'criteria' => 'itemtype',
            'pattern' => $classname
        ];
        $DB->delete(\RuleImportAsset::getTable(), $where, $joins);
    }
}

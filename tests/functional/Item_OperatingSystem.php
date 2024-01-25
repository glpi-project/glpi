<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;
use Psr\Log\LogLevel;

/* Test for inc/item_operatingsystem.class.php */

class Item_OperatingSystem extends DbTestCase
{
    public function testGetTypeName()
    {
        $this->string(\Item_OperatingSystem::getTypeName())->isIdenticalTo('Item operating systems');
        $this->string(\Item_OperatingSystem::getTypeName(0))->isIdenticalTo('Item operating systems');
        $this->string(\Item_OperatingSystem::getTypeName(10))->isIdenticalTo('Item operating systems');
        $this->string(\Item_OperatingSystem::getTypeName(1))->isIdenticalTo('Item operating system');
    }

    /**
     * Create dropdown objects to be used
     *
     * @return array
     */
    private function createDdObjects()
    {
        $objects = [];
        foreach (['', 'Architecture', 'Version', 'Edition', 'KernelVersion'] as $object) {
            $classname = 'OperatingSystem' . $object;
            $instance = new $classname();
            $this->integer(
                (int)$instance->add([
                    'name' => $classname . ' ' . $this->getUniqueInteger()
                ])
            )->isGreaterThan(0);
            $this->boolean($instance->getFromDB($instance->getID()))->isTrue();
            $objects[$object] = $instance;
        }
        return $objects;
    }

    public function testAttachComputer()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

        $objects = $this->createDdObjects();
        $ios = new \Item_OperatingSystem();
        $input = [
            'itemtype'                          => $computer->getType(),
            'items_id'                          => $computer->getID(),
            'operatingsystems_id'               => $objects['']->getID(),
            'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
            'operatingsystemversions_id'        => $objects['Version']->getID(),
            'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
            'licenseid'                         => $this->getUniqueString(),
            'license_number'                    => $this->getUniqueString()
        ];
        $this->integer(
            (int)$ios->add($input)
        )->isGreaterThan(0);
        $this->boolean($ios->getFromDB($ios->getID()))->isTrue();

        $this->string($ios->getTabNameForItem($computer))
         ->isIdenticalTo("Operating systems <span class='badge'>1</span>");
        $this->integer(
            (int)\Item_OperatingSystem::countForItem($computer)
        )->isIdenticalTo(1);

        $expected_error = "/Duplicate entry '{$computer->getID()}-Computer-{$objects['']->getID()}-{$objects['Architecture']->getID()}' for key '(glpi_items_operatingsystems\.)?unicity'/";
        $this->boolean($ios->add($input))->isFalse();
        $this->hasSqlLogRecordThatMatches($expected_error, LogLevel::ERROR);

        $this->integer(
            (int)\Item_OperatingSystem::countForItem($computer)
        )->isIdenticalTo(1);

        $objects = $this->createDdObjects();
        $ios = new \Item_OperatingSystem();
        $input = [
            'itemtype'                          => $computer->getType(),
            'items_id'                          => $computer->getID(),
            'operatingsystems_id'               => $objects['']->getID(),
            'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
            'operatingsystemversions_id'        => $objects['Version']->getID(),
            'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
            'licenseid'                         => $this->getUniqueString(),
            'license_number'                    => $this->getUniqueString()
        ];
        $this->integer(
            (int)$ios->add($input)
        )->isGreaterThan(0);
        $this->boolean($ios->getFromDB($ios->getID()))->isTrue();

        $this->string($ios->getTabNameForItem($computer))
         ->isIdenticalTo("Operating systems <span class='badge'>2</span>");
        $this->integer(
            (int)\Item_OperatingSystem::countForItem($computer)
        )->isIdenticalTo(2);
    }

    public function testShowForItem()
    {
        $this->login();
        $computer = getItemByTypeName('Computer', '_test_pc01');

        foreach (['showForItem', 'displayTabContentForItem'] as $method) {
            $this->output(
                function () use ($method, $computer) {
                    \Item_OperatingSystem::$method($computer);
                }
            )->contains('operatingsystems_id');
        }

        $objects = $this->createDdObjects();
        $ios = new \Item_OperatingSystem();
        $input = [
            'itemtype'                          => $computer->getType(),
            'items_id'                          => $computer->getID(),
            'operatingsystems_id'               => $objects['']->getID(),
            'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
            'operatingsystemversions_id'        => $objects['Version']->getID(),
            'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
            'licenseid'                         => $this->getUniqueString(),
            'license_number'                    => $this->getUniqueString()
        ];
        $this->integer(
            (int)$ios->add($input)
        )->isGreaterThan(0);
        $this->boolean($ios->getFromDB($ios->getID()))->isTrue();

        foreach (['showForItem', 'displayTabContentForItem'] as $method) {
            $this->output(
                function () use ($method, $computer) {
                    \Item_OperatingSystem::$method($computer);
                }
            )->contains('operatingsystems_id');
        }

        $objects = $this->createDdObjects();
        $ios = new \Item_OperatingSystem();
        $input = [
            'itemtype'                          => $computer->getType(),
            'items_id'                          => $computer->getID(),
            'operatingsystems_id'               => $objects['']->getID(),
            'operatingsystemarchitectures_id'   => $objects['Architecture']->getID(),
            'operatingsystemversions_id'        => $objects['Version']->getID(),
            'operatingsystemkernelversions_id'  => $objects['KernelVersion']->getID(),
            'licenseid'                         => $this->getUniqueString(),
            'license_number'                    => $this->getUniqueString()
        ];
        $this->integer(
            (int)$ios->add($input)
        )->isGreaterThan(0);
        $this->boolean($ios->getFromDB($ios->getID()))->isTrue();

       //thera are now 2 OS linked, we will no longer show a form, but a list.
        foreach (['showForItem', 'displayTabContentForItem'] as $method) {
            $this->output(
                function () use ($method, $computer) {
                    \Item_OperatingSystem::$method($computer);
                }
            )->notContains('operatingsystems_id');
        }
    }

    public function testEntityAccess()
    {
        $this->login();
        $eid = getItemByTypeName('Entity', '_test_root_entity', true);
        $this->setEntity('_test_root_entity', true);

        $computer = new \Computer();
        $this->integer(
            (int)$computer->add([
                'name'         => 'Test Item/OS',
                'entities_id'  => $eid,
                'is_recursive' => 0
            ])
        )->isGreaterThan(0);

        $os = new \OperatingSystem();
        $this->integer(
            (int)$os->add([
                'name' => 'Test OS'
            ])
        )->isGreaterThan(0);

        $ios = new \Item_OperatingSystem();
        $this->integer(
            (int)$ios->add([
                'operatingsystems_id'   => $os->getID(),
                'itemtype'              => 'Computer',
                'items_id'              => $computer->getID()
            ])
        )->isGreaterThan(0);
        $this->boolean($ios->getFromDB($ios->getID()))->isTrue();

        $this->array($ios->fields)
         ->integer['operatingsystems_id']->isIdenticalTo($os->getID())
         ->string['itemtype']->isIdenticalTo('Computer')
         ->integer['items_id']->isIdenticalTo($computer->getID())
         ->integer['entities_id']->isIdenticalTo($eid)
         ->integer['is_recursive']->isIdenticalTo(0);

        $this->boolean($ios->can($ios->getID(), READ))->isTrue();

       //not recursive
        $this->setEntity('Root Entity', true);
        $this->boolean($ios->can($ios->getID(), READ))->isTrue();
        $this->setEntity('_test_child_1', true);
        $this->boolean($ios->can($ios->getID(), READ))->isFalse();
        $this->setEntity('_test_child_2', true);
        $this->boolean($ios->can($ios->getID(), READ))->isFalse();

        $this->setEntity('_test_root_entity', true);
        $this->boolean(
            (bool)$computer->update([
                'id'           => $computer->getID(),
                'is_recursive' => 1
            ])
        )->isTrue();
        $this->boolean($ios->getFromDB($ios->getID()))->isTrue();
        $this->array($ios->fields)
         ->integer['operatingsystems_id']->isIdenticalTo($os->getID())
         ->string['itemtype']->isIdenticalTo('Computer')
         ->integer['items_id']->isIdenticalTo($computer->getID())
         ->integer['entities_id']->isIdenticalTo($eid)
         ->integer['is_recursive']->isIdenticalTo(1);

       //not recursive
        $this->setEntity('Root Entity', true);
        $this->boolean($ios->can($ios->getID(), READ))->isTrue();
        $this->setEntity('_test_child_1', true);
        $this->boolean($ios->can($ios->getID(), READ))->isTrue();
        $this->setEntity('_test_child_2', true);
        $this->boolean($ios->can($ios->getID(), READ))->isTrue();
    }
}

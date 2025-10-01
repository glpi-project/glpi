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
use Glpi\Team\Team;

abstract class CommonITILSatisfaction extends DbTestCase
{
    /**
     * Return the name of the class this test class tests
     * @return class-string<\CommonITILSatisfaction>
     */
    protected function getTestedClass(): string
    {
        $test_class = str_replace('Test', '', static::class);
        // Rule class has the same name as the test class but in the global namespace
        return substr(strrchr($test_class, '\\'), 1);
    }

    public function testGetItemInstance()
    {
        /** @var \CommonITILSatisfaction $tested_class */
        $tested_class = $this->getTestedClass();
        $item = $tested_class::getItemInstance();
        // Verify the itemtype is a subclass of CommonITILObject
        $this->assertInstanceOf(\CommonITILObject::class, $item);
    }

    public function testGetSurveyUrl()
    {
        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        /** @var \CommonITILSatisfaction $tested_class */
        $tested_class = $this->getTestedClass();
        $item = $tested_class::getItemInstance();
        $itemtype = $item::class;
        $tag_prefix = strtoupper($item::getType());
        $items_id = $item->add([
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $root_entity_id,
            'solvedate'   => $_SESSION['glpi_currenttime'],
        ]);
        $this->assertGreaterThan(0, $items_id);

        $this->login(); // Authorized user required to update entity config

        // Set inquest_URL for root entity
        $entity = new \Entity();
        $config_suffix = $itemtype === 'Ticket' ? '' : ('_' . strtolower($itemtype));
        $inquest_url = "[ITEMTYPE],[ITEMTYPE_NAME],[{$tag_prefix}_ID],[{$tag_prefix}_NAME],[{$tag_prefix}_CREATEDATE],
            [{$tag_prefix}_SOLVEDATE],[{$tag_prefix}_PRIORITY]";
        $this->assertTrue(
            $entity->update([
                'id'                              => $root_entity_id,
                'inquest_URL' . $config_suffix    => $inquest_url,
                'inquest_config' . $config_suffix => \CommonITILSatisfaction::TYPE_EXTERNAL,
            ])
        );

        $expected = "{$itemtype},{$item->getTypeName(1)},{$items_id},{$item->fields['name']},{$item->getField('date')},
            {$item->getField('solvedate')},{$item->getField('priority')}";
        $generated = \Entity::generateLinkSatisfaction($item);
        $this->assertEquals($expected, $generated);
    }

    public function testGetIndexName()
    {
        $index_name = $this->getTestedClass()::getIndexName();
        $this->assertTrue(is_string($index_name));
        $this->assertNotEmpty($index_name);
    }

    public function testGetLogTypeID()
    {
        $tested_class = $this->getTestedClass();

        // Create an ITIL object
        $item = $tested_class::getItemInstance();
        $items_id = $item->add([
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'solvedate'   => $_SESSION['glpi_currenttime'],
        ]);
        $this->assertGreaterThan(0, $items_id);

        // Add a satisfaction
        /** @var \CommonITILSatisfaction $satisfaction */
        $satisfaction = new $tested_class();
        $satisfaction_id = $satisfaction->add([
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            $item::getForeignKeyField() => $items_id,
            'type'        => \CommonITILSatisfaction::TYPE_INTERNAL,
            'date'        => $_SESSION['glpi_currenttime'],
        ]);
        $this->assertGreaterThan(0, $satisfaction_id);

        $log_type = $satisfaction->getLogTypeID();
        $this->assertIsArray($log_type);
        $this->assertCount(2, $log_type);
        $this->assertEquals($item::class, $log_type[0]);

        $this->assertEquals($items_id, $log_type[1]);
    }

    public function testDateAnsweredSetOnAnswer()
    {
        $tested_class = $this->getTestedClass();

        // Create an ITIL object
        /** @var \CommonITILObject|Team $item */
        $item = $tested_class::getItemInstance();
        $items_id = $item->add([
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'solvedate'   => $_SESSION['glpi_currenttime'],
            'users_id_recipient' => getItemByTypeName('User', TU_USER, true),
        ]);
        $this->assertGreaterThan(0, $items_id);

        $this->assertTrue($item->addTeamMember('User', getItemByTypeName('User', TU_USER, true), [
            'role' => Team::ROLE_REQUESTER,
        ]));

        // Add a satisfaction
        /** @var \CommonITILSatisfaction $satisfaction */
        $satisfaction = new $tested_class();
        $satisfaction_id = $satisfaction->add([
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            $item::getForeignKeyField()  => $items_id,
            'type'        => \CommonITILSatisfaction::TYPE_INTERNAL,
            'date'        => $_SESSION['glpi_currenttime'],
        ]);
        $this->assertGreaterThan(0, $satisfaction_id);
        $this->assertNull($satisfaction->fields['date_answered']);

        $this->login();

        $this->assertTrue($satisfaction->update([
            $item::getForeignKeyField() => $items_id, // These items don't use `id` as the index field...
            'satisfaction' => 5,
        ]));

        $this->assertNotNull($satisfaction->fields['date_answered']);
        $this->assertEquals($_SESSION['glpi_currenttime'], $satisfaction->fields['date_answered']);
    }
}

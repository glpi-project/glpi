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
use Glpi\Team\Team;

abstract class CommonITILSatisfaction extends DbTestCase
{
    /**
     * Return the name of the class this test class tests
     * @return class-string<\CommonITILSatisfaction>
     */
    protected function getTestedClass(): string
    {
        $test_class = static::class;
        // Rule class has the same name as the test class but in the global namespace
        return substr(strrchr($test_class, '\\'), 1);
    }

    public function testGetItemtype()
    {
        /** @var \CommonITILSatisfaction $tested_class */
        $tested_class = $this->getTestedClass();
        $itemtype = $tested_class::getItemtype();
        // Verify the itemtype is a subclass of CommonITILObject
        $this->boolean(is_a($itemtype, \CommonITILObject::class, true))->isTrue();
    }

    public function testGetSurveyUrl()
    {
        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        /** @var \CommonITILSatisfaction $tested_class */
        $tested_class = $this->getTestedClass();
        $itemtype = $tested_class::getItemtype();
        $item = new $itemtype();
        $tag_prefix = strtoupper($item::getType());
        $items_id = $item->add([
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $root_entity_id,
            'solvedate'   => $_SESSION['glpi_currenttime'],
        ]);
        $this->integer($items_id)->isGreaterThan(0);

        $this->login(); // Authorized user required to update entity config

        // Set inquest_URL for root entity
        $entity = new \Entity();
        $config_suffix = $itemtype === 'Ticket' ? '' : ('_' . strtolower($itemtype));
        $inquest_url = "[ITEMTYPE],[ITEMTYPE_NAME],[{$tag_prefix}_ID],[{$tag_prefix}_NAME],[{$tag_prefix}_CREATEDATE],
            [{$tag_prefix}_SOLVEDATE],[{$tag_prefix}_PRIORITY]";
        $this->boolean(
            $entity->update([
                'id'                              => $root_entity_id,
                'inquest_URL' . $config_suffix    => $inquest_url,
                'inquest_config' . $config_suffix => \CommonITILSatisfaction::TYPE_EXTERNAL,
            ])
        )->isTrue();

        $expected = "{$itemtype},{$item->getTypeName(1)},{$items_id},{$item->fields['name']},{$item->getField('date')},
            {$item->getField('solvedate')},{$item->getField('priority')}";
        $generated = \Entity::generateLinkSatisfaction($item);
        $this->string($generated)->isEqualTo($expected);
    }

    public function testGetIndexName()
    {
        $index_name = $this->getTestedClass()::getIndexName();
        $this->boolean(is_string($index_name))->isTrue();
        $this->string($index_name)->isNotEmpty();
    }

    public function testGetLogTypeID()
    {
        $tested_class = $this->getTestedClass();
        $itilobject_type = $tested_class::getItemtype();

        // Create an ITIL object
        $item = new $itilobject_type();
        $items_id = $item->add([
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'solvedate'   => $_SESSION['glpi_currenttime'],
        ]);
        $this->integer($items_id)->isGreaterThan(0);

        // Add a satisfaction
        /** @var \CommonITILSatisfaction $satisfaction */
        $satisfaction = new $tested_class();
        $satisfaction_id = $satisfaction->add([
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            $itilobject_type::getForeignKeyField()  => $items_id,
            'type'        => \CommonITILSatisfaction::TYPE_INTERNAL,
            'date'        => $_SESSION['glpi_currenttime'],
        ]);
        $this->integer($satisfaction_id)->isGreaterThan(0);

        $log_type = $satisfaction->getLogTypeID();
        $this->boolean(is_array($log_type))->isTrue();
        $this->array($log_type)->size->isEqualTo(2);
        $this->string($log_type[0])->isEqualTo($itilobject_type);

        $this->integer($log_type[1])->isEqualTo($items_id);
    }

    public function testDateAnsweredSetOnAnswer()
    {
        $tested_class = $this->getTestedClass();
        $itilobject_type = $tested_class::getItemtype();

        // Create an ITIL object
        /** @var \CommonITILObject|Team $item */
        $item = new $itilobject_type();
        $items_id = $item->add([
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'solvedate'   => $_SESSION['glpi_currenttime'],
            'users_id_recipient' => getItemByTypeName('User', TU_USER, true),
        ]);
        $this->integer($items_id)->isGreaterThan(0);

        $this->boolean($item->addTeamMember('User', getItemByTypeName('User', TU_USER, true), [
            'role' => Team::ROLE_REQUESTER,
        ]))->isTrue();

        // Add a satisfaction
        /** @var \CommonITILSatisfaction $satisfaction */
        $satisfaction = new $tested_class();
        $satisfaction_id = $satisfaction->add([
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            $itilobject_type::getForeignKeyField()  => $items_id,
            'type'        => \CommonITILSatisfaction::TYPE_INTERNAL,
            'date'        => $_SESSION['glpi_currenttime'],
        ]);
        $this->integer($satisfaction_id)->isGreaterThan(0);
        $this->variable($satisfaction->fields['date_answered'])->isNull();

        $this->login();

        $this->boolean($satisfaction->update([
            $itilobject_type::getForeignKeyField() => $items_id, // These items don't use `id` as the index field...
            'satisfaction' => 5
        ]))->isTrue();

        $this->variable($satisfaction->fields['date_answered'])->isNotNull();
        $this->string($satisfaction->fields['date_answered'])->isEqualTo($_SESSION['glpi_currenttime']);
    }
}

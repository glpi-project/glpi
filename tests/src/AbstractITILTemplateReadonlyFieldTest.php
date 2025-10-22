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

namespace Glpi\Tests;

use CommonITILObject;
use DbTestCase;
use Glpi\Urgency;
use ITILCategory;
use ITILTemplate;
use ITILTemplatePredefinedField;
use ITILTemplateReadonlyField;
use Ticket;

abstract class AbstractITILTemplateReadonlyFieldTest extends DbTestCase
{
    abstract public function getITILClass(): CommonITILObject;

    /**
     * Helper to create a template and a category associated with it.
     *
     * @param array $readonly   Array of field names to be readonly.
     * @param array $predefined Array of predefined field values.
     *
     * @return int The ID of the created category.
     */
    protected function createTemplateAndCategory(array $readonly = [], array $predefined = []): int
    {
        $itil_object = $this->getITILClass();
        $itil_type = $itil_object->getType();

        // Create Template
        $template_class = $itil_type . 'Template';
        $template = new $template_class();
        $this->assertInstanceOf(ITILTemplate::class, $template);

        $template_input = [
            'name'        => 'test_template_' . mt_rand(),
            'is_active'   => 1,
            'entities_id' => 0,
            'is_recursive' => 1,
        ];
        $template_id = $template->add($template_input);
        $this->assertGreaterThan(0, $template_id, 'Template creation failed');

        // Add Readonly Fields
        if (!empty($readonly)) {
            $readonly_field_class = $itil_type . 'TemplateReadonlyField';
            $readonly_field = new $readonly_field_class();
            $this->assertInstanceOf(ITILTemplateReadonlyField::class, $readonly_field);

            $foreign_key_field = $readonly_field::$items_id;

            foreach ($readonly as $field_name) {
                $result = $readonly_field->add([
                    $foreign_key_field => $template_id,
                    'num'              => $this->getIdFromSearchOptions($field_name),
                ]);
                $this->assertNotFalse($result, "Failed to add readonly field '$field_name'");
            }
        }

        // Add Predefined Fields
        if (!empty($predefined)) {
            $predefined_field_class = $itil_type . 'TemplatePredefinedField';
            $predefined_field = new $predefined_field_class();
            $this->assertInstanceOf(ITILTemplatePredefinedField::class, $predefined_field);

            $foreign_key_field = $predefined_field::$items_id;

            foreach ($predefined as $field_name => $field_value) {
                $result = $predefined_field->add([
                    $foreign_key_field => $template_id,
                    'num'            => $this->getIdFromSearchOptions($field_name),
                    'value'          => $field_value,
                ]);
                $this->assertNotFalse($result, "Failed to add predefined field '$field_name'");
            }
        }

        // Create Category and associate template
        $category = new ITILCategory();
        $cat_id = $category->add(['name' => 'test_cat_' . mt_rand(), 'entities_id' => 0, 'is_recursive' => 1]);
        $this->assertGreaterThan(0, $cat_id, 'Category creation failed');

        $type = null;
        if ($itil_object instanceof Ticket) {
            $type = Ticket::INCIDENT_TYPE;
        } else {
            // For Change and Problem, the template is not type-specific in the same way.
            $type = true;
        }
        $template_field_name = $itil_object->getTemplateFieldName($type);

        $category->update([
            'id' => $cat_id,
            $template_field_name => $template_id,
        ]);

        return $cat_id;
    }

    protected function getIdFromSearchOptions(string $field): ?string
    {
        $item = $this->getITILClass();
        foreach ($item->getSearchOptionsMain() as $option) {
            if (isset($option['field']) && $option['field'] === $field) {
                return (string) $option['id'];
            }
        }
        return null;
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testHandleReadonlyFieldsWithNoTemplate(): void
    {
        $itil_object = $this->getITILClass();
        $input = [
            'urgency'           => Urgency::LOW->value,
            'name'              => 'Some content',
            'status'            => CommonITILObject::INCOMING,
            'entities_id'       => 0,
        ];
        if ($itil_object instanceof Ticket) {
            $input['type'] = Ticket::INCIDENT_TYPE;
        }

        $processed_input = $itil_object->enforceReadonlyFields($input, true);

        $this->assertEquals(Urgency::LOW->value, $processed_input['urgency']);
        $this->assertEquals('Some content', $processed_input['name']);
    }

    public function testHandleReadonlyFieldsOnAddWithPredefined(): void
    {
        $cat_id = $this->createTemplateAndCategory(['urgency'], ['urgency' => Urgency::HIGH->value]);

        $itil_object = $this->getITILClass();
        $input = [
            'urgency'           => Urgency::LOW->value,
            'name'              => 'Some content',
            'status'            => CommonITILObject::INCOMING,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ];
        if ($itil_object instanceof Ticket) {
            $input['type'] = Ticket::INCIDENT_TYPE;
        }

        $processed_input = $itil_object->enforceReadonlyFields($input, true);

        $this->assertEquals(Urgency::HIGH->value, $processed_input['urgency']);
        $this->assertEquals('Some content', $processed_input['name']);
    }

    public function testHandleReadonlyFieldsOnAddWithoutPredefined(): void
    {
        $cat_id = $this->createTemplateAndCategory(['urgency']);

        $itil_object = $this->getITILClass();
        $input = [
            'urgency'           => Urgency::LOW->value,
            'name'              => 'Some content',
            'status'            => CommonITILObject::INCOMING,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ];
        if ($itil_object instanceof Ticket) {
            $input['type'] = Ticket::INCIDENT_TYPE;
        }

        $processed_input = $itil_object->enforceReadonlyFields($input, true);

        $this->assertArrayNotHasKey('urgency', $processed_input); // Default value
        $this->assertEquals('Some content', $processed_input['name']);
    }

    public function testHandleReadonlyFieldsOnUpdateWithExistingValue(): void
    {
        $cat_id = $this->createTemplateAndCategory(['urgency']);

        $itil_object = $this->getITILClass();
        $add_input = [
            'name'              => 'Initial content',
            'status'            => CommonITILObject::ASSIGNED,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ];
        if ($itil_object instanceof Ticket) {
            $add_input['type'] = Ticket::INCIDENT_TYPE;
        }
        $item_id = $itil_object->add($add_input);
        $this->assertGreaterThan(0, $item_id);

        $itil_object->getFromDB($item_id);

        $update_input = [
            'id'      => $item_id,
            'urgency' => Urgency::HIGH->value,
            'name'    => 'Updated content',
            'status'  => CommonITILObject::ASSIGNED,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ];
        if ($itil_object instanceof Ticket) {
            $update_input['type'] = Ticket::INCIDENT_TYPE;
        }

        $processed_input = $itil_object->enforceReadonlyFields($update_input);

        $this->assertEquals(Urgency::MEDIUM->value, $processed_input['urgency']);
        $this->assertEquals('Updated content', $processed_input['name']);
    }

    public function testHandleReadonlyFieldsOnUpdateWithoutExistingValue(): void
    {
        $cat_id = $this->createTemplateAndCategory(['urgency']);

        $itil_object = $this->getITILClass();
        $add_input = [
            'name'              => 'Initial content',
            'status'            => CommonITILObject::ASSIGNED,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ];
        if ($itil_object instanceof Ticket) {
            $add_input['type'] = Ticket::INCIDENT_TYPE;
        }
        $item_id = $itil_object->add($add_input);
        $this->assertGreaterThan(0, $item_id);

        $itil_object->getFromDB($item_id);

        $update_input = [
            'id'      => $item_id,
            'urgency' => Urgency::LOW->value,
            'name'    => 'Updated content',
            'status'  => CommonITILObject::ASSIGNED,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ];
        if ($itil_object instanceof Ticket) {
            $update_input['type'] = Ticket::INCIDENT_TYPE;
        }

        $processed_input = $itil_object->enforceReadonlyFields($update_input);

        $this->assertEquals(Urgency::MEDIUM->value, $processed_input['urgency']); // Default value
        $this->assertEquals('Updated content', $processed_input['name']);
    }
}

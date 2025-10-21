<?php

namespace Glpi\Tests;

use CommonITILObject;
use DbTestCase;
use ITILCategory;
use Ticket; // For type constants

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
        $itil_class = $this->getITILClass();
        $templateClass = $itil_class::getTemplateClass();
        dump($templateClass);
        $template = new $templateClass();

        // ITILTemplate::add expects `_readonly` to be an array with field names as keys and 1 as value.
        $readonly_input = array_fill_keys($readonly, 1);

        $template_input = [
            'name'        => 'test_template_' . mt_rand(),
            'is_active'   => 1,
            'entities_id' => 0,
            'is_recursive' => 1,
            '_readonly'   => $readonly_input,
            '_predefined' => $predefined,
            '_mandatory'  => [],
            '_hidden'     => [],
        ];

        $template_id = $template->add($template_input);
        $this->assertGreaterThan(0, $template_id, 'Template creation failed');

        $category = new ITILCategory();
        $cat_id = $category->add(['name' => 'test_cat_' . mt_rand(), 'entities_id' => 0, 'is_recursive' => 1]);
        $this->assertGreaterThan(0, $cat_id, 'Category creation failed');

        $type = null;
        if ($itil_class instanceof Ticket) {
            $type = Ticket::INCIDENT_TYPE;
        } else {
            $type = true; // For Change and Problem
        }
        $template_field_name = $itil_class->getTemplateFieldName($type);

        $category->update([
            'id' => $cat_id,
            $template_field_name => $template_id,
        ]);

        return $cat_id;
    }

    public function testHandleReadonlyFieldsOnAddWithPredefined(): void
    {
        $cat_id = $this->createTemplateAndCategory(
            ['name'], // readonly
            ['name' => 'Predefined Name Value'] // predefined
        );

        $itilObject = $this->getITILClass();
        $input = [
            'name'              => 'User Input Name',
            'content'           => 'Some content',
            'status'            => CommonITILObject::INCOMING,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ];
        if ($itilObject instanceof Ticket) {
            $input['type'] = Ticket::INCIDENT_TYPE;
        }

        $processedInput = $itilObject->prepareInputForAdd($input);

        $this->assertEquals('Predefined Name Value', $processedInput['name']);
        $this->assertEquals('Some content', $processedInput['content']);
    }

    public function testHandleReadonlyFieldsOnAddWithoutPredefined(): void
    {
        $cat_id = $this->createTemplateAndCategory(
            ['name'] // readonly
        );

        $itilObject = $this->getITILClass();
        $input = [
            'name'              => 'User Input Name',
            'content'           => 'Some content',
            'status'            => CommonITILObject::INCOMING,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ];
        if ($itilObject instanceof Ticket) {
            $input['type'] = Ticket::INCIDENT_TYPE;
        }

        $processedInput = $itilObject->prepareInputForAdd($input);

        $this->assertEmpty($processedInput['name']);
        $this->assertEquals('Some content', $processedInput['content']);
    }

    public function testHandleReadonlyFieldsOnUpdateWithExistingValue(): void
    {
        $cat_id = $this->createTemplateAndCategory(
            ['name'] // readonly
        );

        $itilObject = $this->getITILClass();
        $item_id = $itilObject->add([
            'name'              => 'Existing Name Value',
            'content'           => 'Initial content',
            'status'            => CommonITILObject::ASSIGNED,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ]);

        $itilObject->getFromDB($item_id);

        $input = [
            'id'      => $item_id,
            'name'    => 'User Attempted New Name',
            'content' => 'Updated content',
            'status'  => CommonITILObject::ASSIGNED,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ];
        if ($itilObject instanceof Ticket) {
            $input['type'] = Ticket::INCIDENT_TYPE;
        }

        $processedInput = $itilObject->prepareInputForUpdate($input);

        $this->assertEquals('Existing Name Value', $processedInput['name']);
        $this->assertEquals('Updated content', $processedInput['content']);
    }

    public function testHandleReadonlyFieldsOnUpdateWithoutExistingValue(): void
    {
        $cat_id = $this->createTemplateAndCategory(
            ['name'] // readonly
        );

        $itilObject = $this->getITILClass();
        $item_id = $itilObject->add([
            'name'              => '', // No initial name
            'content'           => 'Initial content',
            'status'            => CommonITILObject::ASSIGNED,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ]);

        $itilObject->getFromDB($item_id);

        $input = [
            'id'      => $item_id,
            'name'    => 'User Attempted New Name',
            'content' => 'Updated content',
            'status'  => CommonITILObject::ASSIGNED,
            'itilcategories_id' => $cat_id,
            'entities_id'       => 0,
        ];
        if ($itilObject instanceof Ticket) {
            $input['type'] = Ticket::INCIDENT_TYPE;
        }

        $processedInput = $itilObject->prepareInputForUpdate($input);

        $this->assertArrayNotHasKey('name', $processedInput);
        $this->assertEquals('Updated content', $processedInput['content']);
    }
}

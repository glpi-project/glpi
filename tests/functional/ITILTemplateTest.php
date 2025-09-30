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
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/itiltemplate.class.php */
class ITILTemplateTest extends DbTestCase
{
    public static function itilProvider()
    {
        return [
            ['Ticket'],
            ['Change'],
            ['Problem'],
        ];
    }

    #[DataProvider('itilProvider')]
    public function testTemplateUsage($itiltype)
    {
        $this->login();

        //create template
        $tpl_class = '\\' . $itiltype . 'Template';
        $tpl = new $tpl_class();
        $tpl_id = (int) $tpl->add([
            'name'   => 'Template for ' . $itiltype,
        ]);
        $this->assertGreaterThan(0, $tpl_id);

        //add a mandatory field
        $mandat_class = '\\' . $itiltype . 'TemplateMandatoryField';
        $mandat = new $mandat_class();
        $this->assertGreaterThan(
            0,
            (int) $mandat->add([
                $mandat::$items_id   => $tpl_id,
                'num'                => $mandat->getFieldNum($tpl, 'Title'),
            ])
        );

        if ($itiltype === \Ticket::getType()) {
            $this->assertGreaterThan(
                0,
                (int) $mandat->add([
                    $mandat::$items_id   => $tpl_id,
                    'num'                => $mandat->getFieldNum($tpl, 'Location'),
                ])
            );
        }

        $this->assertGreaterThan(
            0,
            (int) $mandat->add([
                $mandat::$items_id   => $tpl_id,
                'num'                => $mandat->getFieldNum($tpl, 'Description'),
            ])
        );

        //add a predefined field
        $predef_class = '\\' . $itiltype . 'TemplatePredefinedField';
        $predef = new $predef_class();
        $this->assertGreaterThan(
            0,
            (int) $predef->add([
                $mandat::$items_id   => $tpl_id,
                'num'                => $predef->getFieldNum($tpl, 'Description'), //Description
                'value'              => 'Description from template',
            ])
        );

        $category = new \ITILCategory();
        $cat_field = strtolower($itiltype) . 'templates_id';
        if ($itiltype === \Ticket::getType()) {
            $cat_field .= '_demand';
        }
        $cat_id = (int) $category->add([
            'name'      => 'Category for a template',
            $cat_field  => $tpl_id,
        ]);
        $this->assertGreaterThan(0, $cat_id);

        $object = new $itiltype();
        $tpl_key = $object->getTemplateFormFieldName();
        $content = [
            'name'                  => '',
            'content'               => '',
            'itilcategories_id'     => $cat_id,
            $tpl_key                => $tpl_id,
            'entities_id'           => 0,
            'locations_id'          => 'NULL',
        ];
        if ($itiltype === \Ticket::getType()) {
            $content['type'] = \Ticket::INCIDENT_TYPE;
        }
        $tid = (int) $object->add($content);
        $this->assertSame(0, $tid);

        $err_msg = 'Mandatory fields are not filled. Please correct: Title'
         . ($itiltype === \Ticket::getType() ? ', Location' : '') . ', Description';
        $this->hasSessionMessages(ERROR, [$err_msg]);
        $this->assertFalse($object->checkRequiredFieldsFilled());

        $content['name']           = 'Title is required';
        $content['content']        = 'Description from template';
        $content['locations_id']   = getItemByTypeName('Location', '_location01', true);

        $tid = (int) $object->add($content);
        $this->assertSame(0, $tid);

        $this->hasSessionMessages(
            ERROR,
            [
                'You cannot use predefined description verbatim',
                'Mandatory fields are not filled. Please correct: Description',
            ]
        );
        $this->assertFalse($object->checkRequiredFieldsFilled());

        $content['content'] = 'A content for our ' . $itiltype;
        $tid = (int) $object->add($content);
        $this->assertGreaterThan(0, $tid);
        $this->assertTrue($object->checkRequiredFieldsFilled());
    }

    #[DataProvider('itilProvider')]
    public function testGetAllowedFields($itiltype)
    {
        $class = $itiltype . 'Template';
        $fields = $class::getAllowedFields();
        foreach ($fields as $field) {
            if (is_array($field)) {
                foreach ($field as $sfield) {
                    $this->checkField($itiltype, $sfield);
                }
            } else {
                $this->checkField($itiltype, $field);
            }
        }
    }

    /**
     * Check one field
     *
     * @param string $itiltype ITIL type
     * @param string $field    Field name
     *
     * @return void
     */
    private function checkField($itiltype, $field)
    {
        global $DB;

        if (!str_starts_with($field, '_') && 'items_id' != $field) {
            $this->assertTrue(
                $DB->fieldExists($itiltype::getTable(), $field),
                "$field in $itiltype"
            );
        } else {
            //howto test dynamic fields (those wich names begin with a "_")?
            //howto test items_id (from Ticket at least)?
            $empty = true;
        }
    }

    public static function fieldsProvider()
    {
        return [
            [
                'Ticket',
                [
                    1 => 'Title',
                    21 => 'Description',
                    12 => 'Status',
                    10 => 'Urgency',
                    11 => 'Impact',
                    3 => 'Priority',
                    15 => 'Opening date',
                    45 => 'Total duration',
                    18 => 'Time to resolve',
                    4 => 'Requester',
                    71 => 'Requester group',
                    5 => 'Technician',
                    8 => 'Technician group',
                    6 => 'Assigned to a supplier',
                    66 => 'Observer',
                    65 => 'Observer group',
                    7 => 'Category',
                    13 => 'Associated elements',
                    -2 => 'Approval request',
                    142 => 'Documents',
                    83 => 'Location',
                    9 => 'Request source',
                    37 => 'SLA Time to own',
                    30 => 'SLA Time to resolve',
                    190 => 'OLA Internal time to own',
                    191 => 'OLA Internal time to resolve',
                    155 => 'Time to own',
                    180 => 'Internal time to resolve',
                    185 => 'Internal time to own',
                    193 => 'Contract',
                ],
            ], [
                'Change',
                [
                    1 => 'Title',
                    21 => 'Description',
                    12 => 'Status',
                    10 => 'Urgency',
                    11 => 'Impact',
                    3 => 'Priority',
                    15 => 'Opening date',
                    45 => 'Total duration',
                    18 => 'Time to resolve',
                    4 => 'Requester',
                    71 => 'Requester group',
                    5 => 'Technician',
                    8 => 'Technician group',
                    6 => 'Assigned to a supplier',
                    66 => 'Observer',
                    65 => 'Observer group',
                    7 => 'Category',
                    13 => 'Associated elements',
                    -2 => 'Approval request',
                    142 => 'Documents',
                    18 => 'Time to resolve',
                    83 => 'Location',
                    60 => 'Analysis impact',
                    61 => 'Control list',
                    62 => 'Deployment plan',
                    63 => 'Backup plan',
                    67 => 'Checklist',
                ],
            ], [
                'Problem',
                [
                    1 => 'Title',
                    21 => 'Description',
                    12 => 'Status',
                    10 => 'Urgency',
                    11 => 'Impact',
                    3 => 'Priority',
                    15 => 'Opening date',
                    45 => 'Total duration',
                    18 => 'Time to resolve',
                    4 => 'Requester',
                    71 => 'Requester group',
                    5 => 'Technician',
                    8 => 'Technician group',
                    6 => 'Assigned to a supplier',
                    66 => 'Observer',
                    65 => 'Observer group',
                    7 => 'Category',
                    13 => 'Associated elements',
                    -2 => 'Approval request',
                    142 => 'Documents',
                    83 => 'Location',
                    60 => 'Impacts',
                    61 => 'Causes',
                    62 => 'Symptoms',
                ],
            ],
        ];
    }

    #[DataProvider('fieldsProvider')]
    public function testGetMandatoryFields($itemtype, $fields)
    {
        $tpl_class = '\\' . $itemtype . 'Template';
        $tpl = new $tpl_class();
        $class = $tpl_class . 'MandatoryField';
        $tpl_field = new $class();
        $result = $tpl_field->getAllFields($tpl);
        $this->assertSame($fields, $result);
    }

    #[DataProvider('itilProvider')]
    public function testGetTabNameForItem($itiltype)
    {
        $this->login();

        $tpl_class = '\\' . $itiltype . 'Template';
        $tpl = new $tpl_class();

        $expected = [
            1 => 'Preview',
        ];
        $this->assertSame(
            $expected,
            array_map('strip_tags', $tpl->getTabNameForItem($tpl)),
        );
    }

    #[DataProvider('itilProvider')]
    public function testTasks($itiltype)
    {
        $this->login();

        //create template
        $tpl_class = '\\' . $itiltype . 'Template';
        $tpl = new $tpl_class();

        $mandat_class = '\\' . $itiltype . 'TemplateMandatoryField';
        $mandat = new $mandat_class();

        $tpl_id = (int) $tpl->add([
            'name'   => 'Template for ' . $itiltype,
        ]);
        $this->assertGreaterThan(0, $tpl_id);

        $task_tpl = new \TaskTemplate();
        $tid1 = (int) $task_tpl->add([
            'name'         => 'First task template',
            'content'      => 'First task content',
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $tid1);
        $this->assertTrue($task_tpl->getFromDB($tid1));

        $tid2 = (int) $task_tpl->add([
            'name'         => 'Second task template',
            'content'      => 'Second task content',
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $tid1);

        //add predefined tasks
        $predef_class = '\\' . $itiltype . 'TemplatePredefinedField';
        $predef = new $predef_class();
        $puid = (int) $predef->add([
            $mandat::$items_id   => $tpl_id,
            'num'                => $predef->getFieldNum($tpl, 'Tasks'),
            'value'              => $tid1,
            'is_recursive'       => 1,
        ]);
        $this->assertGreaterThan(0, $puid);
        $this->assertTrue($predef->getFromDB($puid));

        $puid = (int) $predef->add([
            $mandat::$items_id   => $tpl_id,
            'num'                => $predef->getFieldNum($tpl, 'Tasks'),
            'value'              => $tid2,
            'is_recursive'       => 1,
        ]);
        $this->assertGreaterThan(0, $puid);
        $this->assertTrue($predef->getFromDB($puid));

        $category = new \ITILCategory();
        $cat_field = strtolower($itiltype) . 'templates_id';
        if ($itiltype === \Ticket::getType()) {
            $cat_field .= '_demand';
        }
        $cat_id = (int) $category->add([
            'name'      => 'Category for a template',
            $cat_field  => $tpl_id,
        ]);
        $this->assertGreaterThan(0, $cat_id);

        $object = new $itiltype();
        $tpl_key = $object->getTemplateFormFieldName();
        $content = [
            'name'                  => 'Title is required',
            'content'               => 'A content for our ' . $itiltype,
            'itilcategories_id'     => $cat_id,
            $tpl_key                => $tpl_id,
            'entities_id'           => 0,
            '_tasktemplates_id'     => [
                $tid1,
                $tid2,
            ],
        ];
        if ($itiltype === \Ticket::getType()) {
            $content['type'] = \Ticket::INCIDENT_TYPE;
        }

        $tid = (int) $object->add($content);
        $this->assertGreaterThan(0, $tid);

        global $DB;
        $task_class = $itiltype . 'Task';
        $iterator = $DB->request([
            'FROM'   => $task_class::getTable(),
            'WHERE'  => [
                $object->getForeignKeyField() => $tid,
            ],
        ]);
        $this->assertCount(2, $iterator);
    }

    #[DataProvider('itilProvider')]
    public function testGetITILTemplateToUse($itiltype)
    {
        $this->login('tech', 'tech');
        $itilobject = new $itiltype();

        //1- per default, no template is assigned
        $tt = $itilobject->getITILTemplateToUse();
        $this->assertTrue($tt->isNewItem(), 'Not an empty template');

        //2- create a category with a new template for it,
        //   and check the correct template is returned
        $category_tpl_id = $this->createTemplate($itiltype);
        $category = new \ITILCategory();

        $field = strtolower($itiltype) . 'templates_id';
        $cat_field = $field;
        if ($itiltype === \Ticket::getType()) {
            $cat_field .= '_demand';
        }
        $cat_id = (int) $category->add([
            'name'      => 'Category for a ' . $itiltype . ' template',
            $cat_field  => $category_tpl_id,
        ]);
        $this->assertGreaterThan(0, $cat_id);

        $type = ($itiltype == 'Ticket' ? \Ticket::DEMAND_TYPE : null);
        $tt = $itilobject->getITILTemplateToUse(0, $type, $cat_id);
        $this->assertFalse(
            $tt->isNewItem(),
            'Not template expected from category assignment'
        );
        $this->assertSame($category_tpl_id, (int) $tt->fields['id']);

        //3- edit existing entity with new template as default
        //   and check the correct template is returned
        //   check if category has precedence
        $entity_tpl_id = $this->createTemplate($itiltype);
        //login as admin to change entity conf
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_child_1');
        $this->assertTrue($entity->update(['id' => $entity->fields['id'], $field => $entity_tpl_id]));

        //add a predefined field
        $predef_class = '\\' . $itiltype . 'TemplatePredefinedField';
        $predef = new $predef_class();
        $input = [
            $predef::$items_id   => $entity_tpl_id,
            'num'                => 7, // Category
            'value'              => $cat_id,
        ];
        $this->createItem(
            $predef_class,
            $input
        );

        //login back as tech
        $this->login('tech', 'tech');

        $tt = $itilobject->getITILTemplateToUse(0, $type, 0, $entity->fields['id']);
        $this->assertFalse(
            $tt->isNewItem(),
            'Not template expected from entity assignment'
        );
        $this->assertSame($entity_tpl_id, (int) $tt->fields['id']);
        $this->assertSame($cat_id, (int) $tt->predefined['itilcategories_id']);

        $tt = $itilobject->getITILTemplateToUse(0, $type, $cat_id, $entity->fields['id']);
        $this->assertFalse(
            $tt->isNewItem(),
            'Not template expected from entity assignment overrided with category'
        );
        $this->assertSame($category_tpl_id, (int) $tt->fields['id']);

        //4- set default to a new template fo tech profile
        //   check the correct template is returned
        //   check if profile has precedence on entity
        //   check if category has precedence
        $profile_tpl_id = $this->createTemplate($itiltype);
        $profile = getItemByTypeName('Profile', 'Technician');
        $this->assertTrue($profile->update(['id' => $profile->fields['id'], $field => $profile_tpl_id]));

        //login again to refresh profile
        $this->login('tech', 'tech');

        $tt = $itilobject->getITILTemplateToUse(0, $type, 0);
        $this->assertFalse(
            $tt->isNewItem(),
            'Not template expected from profile assignment'
        );
        $this->assertSame($profile_tpl_id, (int) $tt->fields['id']);

        $tt = $itilobject->getITILTemplateToUse(0, $type, 0, $entity->fields['id']);
        $this->assertFalse(
            $tt->isNewItem(),
            'Not template expected from entity assignment overrided by profile'
        );
        $this->assertSame($profile_tpl_id, (int) $tt->fields['id']);

        $tt = $itilobject->getITILTemplateToUse(0, $type, $cat_id, $entity->fields['id']);
        $this->assertFalse(
            $tt->isNewItem(),
            'Not template expected'
        );
        $this->assertSame($category_tpl_id, (int) $tt->fields['id']);
    }

    /**
     * Check that all predefined fields are set in the default values
     */
    #[DataProvider('itilProvider')]
    public function testGetDefaultValues($itiltype)
    {
        $itemtype       = '\\' . $itiltype;
        $default_values = $itemtype::getDefaultValues();
        $tt_class       = $itiltype . 'Template';
        $tt             = new $tt_class();
        $fields         = $tt->getAllowedFields(true, true);
        $tt_predefined  = $tt_class . 'PredefinedField';
        $fields         = array_diff_key($fields, $tt_predefined::getExcludedFields());
        foreach ($fields as $field) {
            $this->assertArrayHasKey($field, $default_values);
        }
    }

    private function createTemplate($itiltype)
    {
        //create template
        $tpl_class = '\\' . $itiltype . 'Template';
        $tpl = new $tpl_class();
        $tpl_id = (int) $tpl->add([
            'name'   => 'Template for ' . $itiltype,
        ]);
        $this->assertGreaterThan(0, $tpl_id);
        return $tpl_id;
    }

    #[DataProvider('itilProvider')]
    public function testCleanDBonPurge($itiltype)
    {
        $this->login();

        //create template
        $tpl_class = '\\' . $itiltype . 'Template';
        $tpl = new $tpl_class();

        $tpl_id = $this->createItem(
            $tpl_class,
            [
                'name' => 'Template for ' . $itiltype,
            ]
        )->getID();

        //add a mandatory field
        $mandat_class = '\\' . $itiltype . 'TemplateMandatoryField';
        $mandat = new $mandat_class();
        $mandat_id = $this->createItem(
            $mandat_class,
            [
                $mandat::$items_id => $tpl_id,
                'num'              => $mandat->getFieldNum($tpl, 'Title'),
            ]
        )->getID();

        //add a predefined field
        $predef_class = '\\' . $itiltype . 'TemplatePredefinedField';
        $predef = new $predef_class();
        $predef_id = $this->createItem(
            $predef_class,
            [
                $mandat::$items_id   => $tpl_id,
                'num'                => $predef->getFieldNum($tpl, 'Description'), //Description
                'value'              => 'Description from template',
            ]
        )->getID();

        //add an hidden field
        $hidden_class = '\\' . $itiltype . 'TemplateHiddenField';
        $hidden = new $hidden_class();
        $hidden_id = $this->createItem(
            $hidden_class,
            [
                $mandat::$items_id => $tpl_id,
                'num'              => $hidden->getFieldNum($tpl, 'Status'),
            ]
        )->getID();

        // Delete the template
        $tpl->delete(['id' => $tpl_id], true);

        // Check that the mandatory field is deleted
        $this->assertEmpty($mandat->getFromDB($mandat_id));

        // Check that the predefined field is deleted
        $this->assertEmpty($predef->getFromDB($predef_id));

        // Check that the hidden field is deleted
        $this->assertEmpty($hidden->getFromDB($hidden_id));
    }
}

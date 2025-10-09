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

use CommonDBTM;
use Computer;
use DbTestCase;
use Generator;
use Glpi\Features\AssignableItem;
use Glpi\Features\Clonable;
use Glpi\Socket;
use Item_DeviceSimcard;
use PHPUnit\Framework\Attributes\DataProvider;
use Session;
use State;
use Symfony\Component\DomCrawler\Crawler;
use User;

/* Test for inc/dropdown.class.php */

class DropdownTest extends DbTestCase
{
    public function testShowLanguages()
    {

        $opt = [ 'display_emptychoice' => true, 'display' => false ];
        $out = \Dropdown::showLanguages('dropfoo', $opt);
        $this->assertStringContainsString("name='dropfoo'", $out);
        $this->assertStringContainsString("value='' selected", $out);
        $this->assertStringNotContainsString("value='0'", $out);
        $this->assertStringContainsString("value='fr_FR'", $out);

        $opt = ['display' => false, 'value' => 'cs_CZ', 'rand' => '1234'];
        $out = \Dropdown::showLanguages('language', $opt);
        $this->assertStringNotContainsString("value=''", $out);
        $this->assertStringNotContainsString("value='0'", $out);
        $this->assertStringContainsString("name='language' id='dropdown_language1234", $out);
        $this->assertStringContainsString("value='cs_CZ' selected", $out);
        $this->assertStringContainsString("value='fr_FR'", $out);
    }

    public static function dataTestImport()
    {
        return [
            // input,             name,  message
            [ [ ],                '',    'missing name'],
            [ [ 'name' => ''],    '',    'empty name'],
            [ [ 'name' => ' '],   '',    'space name'],
            [ [ 'name' => ' a '], 'a',   'simple name'],
            [ [ 'name' => 'foo'], 'foo', 'simple name'],
        ];
    }

    #[DataProvider('dataTestImport')]
    public function testImport($input, $result, $msg)
    {
        $id = \Dropdown::import('UserTitle', $input);
        if ($result) {
            $this->assertGreaterThan(0, (int) $id);
            $ut = new \UserTitle();
            $this->assertTrue($ut->getFromDB($id));
            $this->assertSame($result, $ut->getField('name'));
        } else {
            $this->assertLessThan(0, (int) $id);
        }
    }

    public static function dataTestTreeImport()
    {
        return [
            // input,                                  name,    completename, message
            [ [ ],                                     '',      '',           'missing name'],
            [ [ 'name' => ''],                          '',     '',           'empty name'],
            [ [ 'name' => ' '],                         '',     '',           'space name'],
            [ [ 'name' => ' a '],                       'a',    'a',          'simple name'],
            [ [ 'name' => 'foo'],                       'foo',  'foo',        'simple name'],
            [ [ 'completename' => 'foo > bar'],         'bar',  'foo > bar',  'two names'],
            [ [ 'completename' => ' '],                 '',     '',           'only space'],
            [ [ 'completename' => '>'],                 '',     '',           'only >'],
            [ [ 'completename' => ' > '],               '',     '',           'only > and spaces'],
            [ [ 'completename' => 'foo>bar'],           'bar',  'foo > bar',  'two names with no space'],
            [ [ 'completename' => '>foo>>bar>'],        'bar',  'foo > bar',  'two names with additional >'],
            [ [ 'completename' => ' foo >   > bar > '], 'bar',  'foo > bar',  'two names with garbage'],
        ];
    }

    #[DataProvider('dataTestTreeImport')]
    public function testTreeImport($input, $result, $complete, $msg)
    {
        $input['entities_id'] = getItemByTypeName('Entity', '_test_root_entity', true);
        $id = \Dropdown::import('Location', $input);
        if ($result) {
            $this->assertGreaterThan(0, (int) $id, $msg);
            $ut = new \Location();
            $this->assertTrue($ut->getFromDB($id));
            $this->assertSame($result, $ut->getField('name'));
            $this->assertSame($complete, $ut->getField('completename'));
        } else {
            $this->assertLessThanOrEqual(0, (int) $id);
        }
    }

    public function testGetDropdownNameAndComment()
    {
        $ret = \Dropdown::getDropdownName('not_a_known_table', 1);
        $this->assertSame('', $ret);

        $subcat_id = getItemByTypeName('TaskCategory', '_subcat_1', true);

        // basic test returns string only
        $expected_name = '_cat_1 > _subcat_1';
        $ret = \Dropdown::getDropdownName('glpi_taskcategories', $subcat_id);
        $this->assertSame($expected_name, $ret);

        // test of return with comments
        $expected_comments = <<<HTML
<span class="b">Complete name: </span>_cat_1 &gt; _subcat_1<br />
                <span class="b">Comments: </span>
    
Comment for sub-category _subcat_1
HTML;
        $ret = @\Dropdown::getDropdownName('glpi_taskcategories', $subcat_id, withcomment: true);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_taskcategories', $subcat_id);
        $this->assertSame($expected_comments, $ret);

        // test of return without $tooltip
        $expected_comments = 'Comment for sub-category _subcat_1';
        $ret = @\Dropdown::getDropdownName('glpi_taskcategories', $subcat_id, withcomment: true, tooltip: false);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_taskcategories', $subcat_id, tooltip: false);
        $this->assertSame($expected_comments, $ret);

        // test of return with translations
        $_SESSION["glpilanguage"] = Session::loadLanguage('fr_FR');
        $_SESSION['glpi_dropdowntranslations'] = \DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);
        $expected_name = 'FR - _cat_1 > FR - _subcat_1';
        $expected_comments = 'FR - Commentaire pour sous-catégorie _subcat_1';

        $ret = \Dropdown::getDropdownName('glpi_taskcategories', $subcat_id);
        $this->assertSame($expected_name, $ret);

        $ret = @\Dropdown::getDropdownName('glpi_taskcategories', $subcat_id, withcomment: true, tooltip: false);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_taskcategories', $subcat_id, tooltip: false);
        $this->assertSame($expected_comments, $ret);

        // switch back to default language
        $_SESSION["glpilanguage"] = Session::loadLanguage('en_GB');

        ////////////////////////////////
        // test for other dropdown types
        ////////////////////////////////

        ///////////
        // Computer
        $computer_id = getItemByTypeName('Computer', '_test_pc01', true);

        $expected_name = '_test_pc01';
        $ret = \Dropdown::getDropdownName('glpi_computers', $computer_id);
        $this->assertSame($expected_name, $ret);

        $expected_comments = 'Comment for computer _test_pc01';
        $ret = @\Dropdown::getDropdownName('glpi_computers', $computer_id, withcomment: true);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_computers', $computer_id);
        $this->assertSame($expected_comments, $ret);

        //////////
        // Contact
        $contact_id = getItemByTypeName('Contact', '_contact01_name', true);

        $expected_name = '_contact01_name _contact01_firstname';
        $ret = \Dropdown::getDropdownName('glpi_contacts', $contact_id);
        $this->assertSame($expected_name, $ret);

        // test of return with comments
        $expected_comments = <<<HTML
<span class="b">Phone: </span>0123456789<br />
            <span class="b">Phone 2: </span>0123456788<br />
            <span class="b">Mobile phone: </span>0623456789<br />
            <span class="b">Fax: </span>0123456787<br />
            <span class="b">Email: </span>_contact01_firstname._contact01_name@glpi.com<br />
                <span class="b">Comments: </span>
    
Comment for contact _contact01_name
HTML;
        $ret = @\Dropdown::getDropdownName('glpi_contacts', $contact_id, withcomment: true);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_contacts', $contact_id);
        $this->assertSame($expected_comments, $ret);

        // test of return without $tooltip
        $expected_comments = 'Comment for contact _contact01_name';
        $ret = @\Dropdown::getDropdownName('glpi_contacts', $contact_id, withcomment: true, tooltip: false);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_contacts', $contact_id, tooltip: false);
        $this->assertSame($expected_comments, $ret);

        ///////////
        // Supplier
        $supplier_id = getItemByTypeName('Supplier', '_suplier01_name', true);

        $expected_name = '_suplier01_name';
        $ret = \Dropdown::getDropdownName('glpi_suppliers', $supplier_id);
        $this->assertSame($expected_name, $ret);

        // test of return with comments
        $expected_comments = <<<HTML
<span class="b">Phone: </span>0123456789<br />
            <span class="b">Fax: </span>0123456787<br />
            <span class="b">Email: </span>info@_supplier01_name.com<br />
                <span class="b">Comments: </span>
    
Comment for supplier _suplier01_name
HTML;
        $ret = @\Dropdown::getDropdownName('glpi_suppliers', $supplier_id, withcomment: true);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_suppliers', $supplier_id);
        $this->assertSame($expected_comments, $ret);

        // test of return without $tooltip
        $expected_comments = 'Comment for supplier _suplier01_name';
        $ret = @\Dropdown::getDropdownName('glpi_suppliers', $supplier_id, withcomment: true, tooltip: false);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_suppliers', $supplier_id, tooltip: false);
        $this->assertSame($expected_comments, $ret);

        ///////////
        // Budget
        $budget_id = getItemByTypeName('Budget', '_budget01', true);

        $expected_name = '_budget01';
        $ret = \Dropdown::getDropdownName('glpi_budgets', $budget_id);
        $this->assertSame($expected_name, $ret);

        // test of return with comments
        $expected_comments = <<<HTML
<span class="b">Location: </span>_location01<br />
            <span class="b">Type: </span>_budgettype01<br />
            <span class="b">Start date: </span>2016-10-18 <br />
            <span class="b">End date: </span>2016-12-31 <br />
                <span class="b">Comments: </span>
    
Comment for budget _budget01
HTML;
        $ret = @\Dropdown::getDropdownName('glpi_budgets', $budget_id, withcomment: true);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_budgets', $budget_id);
        $this->assertSame($expected_comments, $ret);

        // test of return without $tooltip
        $expected_comments = 'Comment for budget _budget01';
        $ret = @\Dropdown::getDropdownName('glpi_budgets', $budget_id, withcomment: true, tooltip: false);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_budgets', $budget_id, tooltip: false);
        $this->assertSame($expected_comments, $ret);

        ///////////
        // Location
        $location_id = getItemByTypeName('Location', '_location01', true);

        $expected_name = '_location01';
        $ret = \Dropdown::getDropdownName('glpi_locations', $location_id);
        $this->assertSame($expected_name, $ret);

        // test of return with comments
        $expected_comments = <<<HTML
<span class="b">Complete name: </span>_location01<br />
                <span class="b">Comments: </span>
    
Comment for location _location01
HTML;
        $ret = @\Dropdown::getDropdownName('glpi_locations', $location_id, withcomment: true);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_locations', $location_id);
        $this->assertSame($expected_comments, $ret);

        // test of return without $tooltip
        $expected_comments = 'Comment for location _location01';
        $ret = @\Dropdown::getDropdownName('glpi_locations', $location_id, withcomment: true, tooltip: false);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_locations', $location_id, tooltip: false);
        $this->assertSame($expected_comments, $ret);

        //Location with code only:
        $location_id = getItemByTypeName('Location', '_location02 > _sublocation02', true);

        $expected_name = "_location02 > _sublocation02 - code_sublocation02";
        $ret = \Dropdown::getDropdownName('glpi_locations', $location_id);
        $this->assertSame($expected_name, $ret);

        // test of return with comments
        $expected_comments = <<<HTML
<span class="b">Complete name: </span>_location02 &gt; _sublocation02<br />
            <span class="b">Code: </span>code_sublocation02<br />
                <span class="b">Comments: </span>
    
Comment for location _sublocation02
HTML;
        $ret = @\Dropdown::getDropdownName('glpi_locations', $location_id, withcomment: true);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_locations', $location_id);
        $this->assertSame($expected_comments, $ret);

        // test of return without $tooltip
        $expected_comments = 'Comment for location _sublocation02';
        $ret = @\Dropdown::getDropdownName('glpi_locations', $location_id, withcomment: true, tooltip: false);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_locations', $location_id, tooltip: false);
        $this->assertSame($expected_comments, $ret);

        //Location with alias only:
        $location_id = getItemByTypeName('Location', '_location02 > _sublocation03', true);

        $expected_name = "alias_sublocation03";
        $ret = \Dropdown::getDropdownName('glpi_locations', $location_id);
        $this->assertSame($expected_name, $ret);

        // test of return with comments
        $expected_comments = <<<HTML
<span class="b">Complete name: </span>_location02 &gt; _sublocation03<br />
            <span class="b">Alias: </span>alias_sublocation03<br />
                <span class="b">Comments: </span>
    
Comment for location _sublocation03
HTML;
        $ret = @\Dropdown::getDropdownName('glpi_locations', $location_id, withcomment: true);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_locations', $location_id);
        $this->assertSame($expected_comments, $ret);

        // test of return without $tooltip
        $expected_comments = 'Comment for location _sublocation03';
        $ret = @\Dropdown::getDropdownName('glpi_locations', $location_id, withcomment: true, tooltip: false);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_locations', $location_id, tooltip: false);
        $this->assertSame($expected_comments, $ret);

        //Location with alias and code:
        $location_id = getItemByTypeName('Location', '_location02 > _sublocation04', true);

        $expected_name = "alias_sublocation04 - code_sublocation04";
        $ret = \Dropdown::getDropdownName('glpi_locations', $location_id);
        $this->assertSame($expected_name, $ret);

        // test of return with comments
        $expected_comments = <<<HTML
<span class="b">Complete name: </span>_location02 &gt; _sublocation04<br />
            <span class="b">Alias: </span>alias_sublocation04<br />
            <span class="b">Code: </span>code_sublocation04<br />
                <span class="b">Comments: </span>
    
Comment for location _sublocation04
HTML;
        $ret = @\Dropdown::getDropdownName('glpi_locations', $location_id, withcomment: true);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_locations', $location_id);
        $this->assertSame($expected_comments, $ret);

        // test of return without $tooltip
        $expected_comments = 'Comment for location _sublocation04';
        $ret = @\Dropdown::getDropdownName('glpi_locations', $location_id, withcomment: true, tooltip: false);
        $this->assertSame(['name' => $expected_name, 'comment' => $expected_comments], $ret);

        $ret = \Dropdown::getDropdownComments('glpi_locations', $location_id, tooltip: false);
        $this->assertSame($expected_comments, $ret);
    }

    public function testEmptyDropdownComments(): void
    {
        $item = $this->createItem(
            Computer::class,
            [
                'name'        => __METHOD__,
                'comment'     => '',
                'entities_id' => $this->getTestRootEntity(true),
            ],
        );
        $this->assertSame('', \Dropdown::getDropdownComments('glpi_computers', $item->getID()));
    }

    public static function dataGetValueWithUnit()
    {
        return [
            [1,      'auto',        null, '1024 KiB'],
            [1,      'auto',        null, '1024 KiB'],
            [1025,   'auto',        null, '1 GiB'],
            [1,      'year',        null, '1 year'],
            [2,      'year',        null, '2 years'],
            [3,      '%',           null, '3%'],
            ['foo',  'bar',         null, 'foo bar'],
            [1,      'month',       null, '1 month'],
            [2,      'month',       null, '2 months'],
            ['any',  '',            null, 'any'],
            [1,      'day',         null, '1 day'],
            [2,      'day',         null, '2 days'],
            [1,      'hour',        null, '1 hour'],
            [2,      'hour',        null, '2 hours'],
            [1,      'minute',      null, '1 minute'],
            [2,      'minute',      null, '2 minutes'],
            [1,      'second',      null, '1 second'],
            [2,      'second',      null, '2 seconds'],
            [1,      'millisecond', null, '1 millisecond'],
            [2,      'millisecond', null, '2 milliseconds'],
            [10,     'bar',         null, '10 bar'],

            [3.3597, '%',           0,    '3%'],
            [3.3597, '%',           2,    '3.36%'],
            [3.3597, '%',           6,    '3.359700%'],
            [3579,   'day',         0,    '3 579 days'],
        ];
    }

    #[DataProvider('dataGetValueWithUnit')]
    public function testGetValueWithUnit($input, $unit, $decimals, $expected)
    {
        $value = $decimals !== null
         ? \Dropdown::getValueWithUnit($input, $unit, $decimals)
         : \Dropdown::getValueWithUnit($input, $unit);
        $this->assertSame($expected, $value);
    }

    public static function getDropdownValueProvider()
    {
        return [
            [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'           => '_cat_1',
                                    'level'          => 1,
                                    'title'          => '_cat_1 - Comment for category _cat_1',
                                    'selection_text' => '_cat_1',
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_subcat_1',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                                2 => [
                                    'id'             => getItemByTypeName('TaskCategory', 'R&D', true),
                                    'text'           => 'R&D',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > R&D - Comment for sub-category _subcat_2',
                                    'selection_text' => '_cat_1 > R&D',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 3,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                    'searchText'            => 'subcat',
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'     => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'   => '_cat_1',
                                    'level'  => 1,
                                    'disabled' => true,
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_subcat_1',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 1,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                    'searchText'            => '_cat_1 > _subcat',
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'     => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'   => '_cat_1',
                                    'level'  => 1,
                                    'disabled' => true,
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_subcat_1',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 1,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                    'searchText'            => 'R&D',
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'     => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'   => '_cat_1',
                                    'level'  => 1,
                                    'disabled' => true,
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', 'R&D', true),
                                    'text'           => 'R&D',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > R&D - Comment for sub-category _subcat_2',
                                    'selection_text' => '_cat_1 > R&D',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 1,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 1,
                    'emptylabel'            => 'EEEEEE',
                    'itemtype'              => 'TaskCategory',
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'id'        => 0,
                            'text'      => 'EEEEEE',
                        ],
                        1 => [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'           => '_cat_1',
                                    'level'          => 1,
                                    'title'          => '_cat_1 - Comment for category _cat_1',
                                    'selection_text' => '_cat_1',
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_subcat_1',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                                2 => [
                                    'id'             => getItemByTypeName('TaskCategory', 'R&D', true),
                                    'text'           => 'R&D',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > R&D - Comment for sub-category _subcat_2',
                                    'selection_text' => '_cat_1 > R&D',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 3,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                    'used'                  => [getItemByTypeName('TaskCategory', '_cat_1', true)],
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'     => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'   => '_cat_1',
                                    'level'  => 1,
                                    'disabled' => true,
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_subcat_1',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                                2 => [
                                    'id'             => getItemByTypeName('TaskCategory', 'R&D', true),
                                    'text'           => 'R&D',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > R&D - Comment for sub-category _subcat_2',
                                    'selection_text' => '_cat_1 > R&D',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 2,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'Computer',
                    'entity_restrict'       => getItemByTypeName('Entity', '_test_child_2', true),
                ],
                'expected'  => [
                    'results'   => [
                        0 => [
                            'text'      => 'Root entity > _test_root_entity > _test_child_2',
                            'children'  => [
                                0 => [
                                    'id'     => getItemByTypeName('Computer', '_test_pc21', true),
                                    'text'   => '_test_pc21',
                                    'title'  => '_test_pc21',
                                ],
                                1 => [
                                    'id'     => getItemByTypeName('Computer', '_test_pc22', true),
                                    'text'   => '_test_pc22',
                                    'title'  => '_test_pc22',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count'     => 2,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'Computer',
                    'entity_restrict'       => '[' . getItemByTypeName('Entity', '_test_child_2', true) . ']',
                ],
                'expected'  => [
                    'results'   => [
                        0 => [
                            'text'      => 'Root entity > _test_root_entity > _test_child_2',
                            'children'  => [
                                0 => [
                                    'id'     => getItemByTypeName('Computer', '_test_pc21', true),
                                    'text'   => '_test_pc21',
                                    'title'  => '_test_pc21',
                                ],
                                1 => [
                                    'id'     => getItemByTypeName('Computer', '_test_pc22', true),
                                    'text'   => '_test_pc22',
                                    'title'  => '_test_pc22',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count'     => 2,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'Computer',
                    'entity_restrict'       => getItemByTypeName('Entity', '_test_child_2', true),
                    'searchText'            => '22',
                ],
                'expected'  => [
                    'results'   => [
                        0 => [
                            'text'      => 'Root entity > _test_root_entity > _test_child_2',
                            'children'  => [
                                0 => [
                                    'id'     => getItemByTypeName('Computer', '_test_pc22', true),
                                    'text'   => '_test_pc22',
                                    'title'  => '_test_pc22',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count'     => 1,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                    'searchText'            => 'subcat',
                    'toadd'                 => [
                        'key'  => 'value',
                        'key2' => "value with unescaped \t and escaped \\t",
                    ],
                ],
                'expected'  => [
                    'results' => [
                        [
                            'id'     => 'key',
                            'text'   => 'value',
                        ],
                        [
                            'id'     => 'key2',
                            'text'   => "value with unescaped \t and escaped \\t",
                        ],
                        [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'     => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'   => '_cat_1',
                                    'level'  => 1,
                                    'disabled' => true,
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_subcat_1',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 1,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                    'searchText'            => 'subcat',
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_cat_1 > _subcat_1',
                                    'level'          => 0,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 1,
                ],
                'session_params' => [
                    'glpiuse_flat_dropdowntree' => true,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'           => '_cat_1',
                                    'level'          => 0,
                                    'title'          => '_cat_1 - Comment for category _cat_1',
                                    'selection_text' => '_cat_1',
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_cat_1 > _subcat_1',
                                    'level'          => 0,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                                2 => [
                                    'id'             => getItemByTypeName('TaskCategory', 'R&D', true),
                                    'text'           => '_cat_1 > R&D',
                                    'level'          => 0,
                                    'title'          => '_cat_1 > R&D - Comment for sub-category _subcat_2',
                                    'selection_text' => '_cat_1 > R&D',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 3,
                ],
                'session_params' => [
                    'glpiuse_flat_dropdowntree' => true,
                ],
            ], [
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                    'searchText'            => 'subcat',
                    'permit_select_parent'  => true,
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'           => '_cat_1',
                                    'level'          => 1,
                                    'title'          => '_cat_1 - Comment for category _cat_1',
                                    'selection_text' => '_cat_1',
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_subcat_1',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 1,
                ],
            ], [
                // search using id on CommonTreeDropdown but without "glpiis_ids_visible" set to true -> no results
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                    'searchText'            => getItemByTypeName('TaskCategory', '_subcat_1', true),
                ],
                'expected'  => [
                    'results' => [
                    ],
                    'count' => 0,
                ],
                'session_params' => [
                    'glpiis_ids_visible' => false,
                ],
            ], [
                // search using id on CommonTreeDropdown with "glpiis_ids_visible" set to true -> results
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'TaskCategory',
                    'searchText'            => getItemByTypeName('TaskCategory', '_subcat_1', true),
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text'      => 'Root entity',
                            'children'  => [
                                0 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'           => '_cat_1',
                                    'level'          => 1,
                                    'disabled'       => true,
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_subcat_1 (' . getItemByTypeName('TaskCategory', '_subcat_1', true) . ')',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count' => 1,
                ],
                'session_params' => [
                    'glpiis_ids_visible' => true,
                ],
            ], [
                // search using id on "not a CommonTreeDropdown" but without "glpiis_ids_visible" set to true -> no results
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'DocumentType',
                    'searchText'            => getItemByTypeName('DocumentType', 'markdown', true),
                ],
                'expected'  => [
                    'results' => [
                    ],
                    'count' => 0,
                ],
                'session_params' => [
                    'glpiis_ids_visible' => false,
                ],
            ], [
                // search using id on "not a CommonTreeDropdown" with "glpiis_ids_visible" set to true -> results
                'params' => [
                    'display_emptychoice'   => 0,
                    'itemtype'              => 'DocumentType',
                    'searchText'            => getItemByTypeName('DocumentType', 'markdown', true),
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'id'             => getItemByTypeName('DocumentType', 'markdown', true),
                            'text'           => 'markdown (' . getItemByTypeName('DocumentType', 'markdown', true) . ')',
                            'title'          => 'markdown',
                        ],
                    ],
                    'count' => 1,
                ],
                'session_params' => [
                    'glpiis_ids_visible' => true,
                ],
            ], [
                'params' => [
                    'display_emptychoice' => 0,
                    'itemtype'            => 'ComputerModel',
                ],
                'expected'  => [
                    'results'   => [
                        [
                            'id'     => getItemByTypeName('ComputerModel', '_test_computermodel_1', true),
                            'text'   => '_test_computermodel_1 - CMP_ADEAF5E1',
                            'title'  => '_test_computermodel_1 - CMP_ADEAF5E1',
                        ],
                        [
                            'id'     => getItemByTypeName('ComputerModel', '_test_computermodel_2', true),
                            'text'   => '_test_computermodel_2 - CMP_567AEC68',
                            'title'  => '_test_computermodel_2 - CMP_567AEC68',
                        ],
                    ],
                    'count'     => 2,
                ],
            ], [
                'params' => [
                    'display_emptychoice' => 0,
                    'itemtype'            => 'ComputerModel',
                    'searchText'          => 'CMP_56',
                ],
                'expected'  => [
                    'results'   => [
                        [
                            'id'     => getItemByTypeName('ComputerModel', '_test_computermodel_2', true),
                            'text'   => '_test_computermodel_2 - CMP_567AEC68',
                            'title'  => '_test_computermodel_2 - CMP_567AEC68',
                        ],
                    ],
                    'count'     => 1,
                ],
            ], [
                'params' => [
                    'display_emptychoice' => 0,
                    'itemtype'            => Socket::class,
                    'searchText'          => '_socket01',
                ],
                'expected'  => [
                    'results'   => [
                        [
                            'id'     => getItemByTypeName(Socket::class, '_socket01', true),
                            'text'   => '_socket01',
                            'title'  => '_socket01 - Comment for socket _socket01',
                        ],
                    ],
                    'count'     => 1,
                ],
            ],
            // This test verifies the behavior of searches by ID when $_SESSION['glpiis_ids_visible'] is true.
            // Specifically, it checks that a WHERE clause is applied on the index name ("id")
            // when 'searchText' contains only numeric characters (one or more), with no other characters.
            // This condition is evaluated in two contexts: when the related object is either a CommonDropdown or a CommonTreeDropdown.
            // Therefore, we cover both cases by testing with TaskCategory (CommonTreeDropdown) and ComputerModel (CommonDropdown).
            // Additionally, we use both string and integer values for 'searchText' to ensure proper handling and to validate the regex used in preg_match.
            [
                'params' => [
                    'display_emptychoice' => 0,
                    'itemtype'            => 'TaskCategory',
                    'searchText'          => (int) getItemByTypeName(\TaskCategory::class, '_cat_1', true), // search commonTreeDropdown by id as int
                ],
                'expected'  => [
                    'results'   => [
                        [
                            'text'   => 'Root entity',
                            'children' => [
                                0 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'           => '_cat_1',
                                    'level'          => 1,
                                    'title'          => '_cat_1 - Comment for category _cat_1',
                                    'selection_text' => '_cat_1',
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_subcat_1',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                                2 => [
                                    'id'             => getItemByTypeName('TaskCategory', 'R&D', true),
                                    'text'           => 'R&D',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > R&D - Comment for sub-category _subcat_2',
                                    'selection_text' => '_cat_1 > R&D',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count'     => 3,
                ],
            ],
            [
                'params' => [
                    'display_emptychoice' => 0,
                    'itemtype'            => 'TaskCategory',
                    'searchText'          => (string) getItemByTypeName(\TaskCategory::class, '_cat_1', true), // search commonTreeDropdown by id as string
                ],
                'expected'  => [
                    'results'   => [
                        [
                            'text'   => 'Root entity',
                            'children' => [
                                0 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                                    'text'           => '_cat_1',
                                    'level'          => 1,
                                    'title'          => '_cat_1 - Comment for category _cat_1',
                                    'selection_text' => '_cat_1',
                                ],
                                1 => [
                                    'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                                    'text'           => '_subcat_1',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                                    'selection_text' => '_cat_1 > _subcat_1',
                                ],
                                2 => [
                                    'id'             => getItemByTypeName('TaskCategory', 'R&D', true),
                                    'text'           => 'R&D',
                                    'level'          => 2,
                                    'title'          => '_cat_1 > R&D - Comment for sub-category _subcat_2',
                                    'selection_text' => '_cat_1 > R&D',
                                ],
                            ],
                            'itemtype' => 'Entity',
                        ],
                    ],
                    'count'     => 3,
                ],
            ],
            [
                'params' => [
                    'display_emptychoice' => 0,
                    'itemtype'            => 'ComputerModel',
                    'searchText'          => (int) getItemByTypeName('ComputerModel', '_test_computermodel_1', true), // search CommonDropdown by id as int
                ],
                'expected'  => [
                    'results'   => [
                        [
                            'id'     => getItemByTypeName('ComputerModel', '_test_computermodel_1', true),
                            'text'   => '_test_computermodel_1 - CMP_ADEAF5E1',
                            'title'  => '_test_computermodel_1 - CMP_ADEAF5E1',
                        ],
                    ],
                    'count'     => 1,
                ],
            ],
            [
                'params' => [
                    'display_emptychoice' => 0,
                    'itemtype'            => 'ComputerModel',
                    'searchText'          => (string) getItemByTypeName('ComputerModel', '_test_computermodel_1', true), // search CommonDropdown by id as string
                ],
                'expected'  => [
                    'results'   => [
                        [
                            'id'     => getItemByTypeName('ComputerModel', '_test_computermodel_1', true),
                            'text'   => '_test_computermodel_1 - CMP_ADEAF5E1',
                            'title'  => '_test_computermodel_1 - CMP_ADEAF5E1',
                        ],
                    ],
                    'count'     => 1,
                ],
            ],
        ];
    }

    #[DataProvider('getDropdownValueProvider')]
    public function testGetDropdownValue($params, $expected, $session_params = [])
    {
        $this->login();

        $bkp_params = [];
        //set session params if any
        if (count($session_params)) {
            foreach ($session_params as $param => $value) {
                if (isset($_SESSION[$param])) {
                    $bkp_params[$param] = $_SESSION[$param];
                }
                $_SESSION[$param] = $value;
            }
        }

        $params['_idor_token'] = $this->generateIdor($params);

        $result = \Dropdown::getDropdownValue($params, false);

        //reset session params before executing test
        if (count($session_params)) {
            foreach ($session_params as $param => $value) {
                if (isset($bkp_params[$param])) {
                    $_SESSION[$param] = $bkp_params[$param];
                } else {
                    unset($_SESSION[$param]);
                }
            }
        }

        $this->assertSame($expected, $result);
    }

    public static function getDropdownConnectProvider()
    {
        return [
            [
                'params'    => [
                    'fromtype'  => 'Computer',
                    'itemtype'  => 'Printer',
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'id' => 0,
                            'text' => '-----',
                        ],
                        1 => [
                            'text' => "Root entity > _test_root_entity",
                            'children' => [
                                0 => [
                                    'id'     => getItemByTypeName('Printer', '_test_printer_all', true),
                                    'text'   => '_test_printer_all',
                                ],
                                1 => [
                                    'id'     => getItemByTypeName('Printer', '_test_printer_ent0', true),
                                    'text'   => '_test_printer_ent0',
                                ],
                            ],
                        ],
                        2 => [
                            'text' => "Root entity > _test_root_entity > _test_child_1",
                            'children' => [
                                0 => [
                                    'id'     => getItemByTypeName('Printer', '_test_printer_ent1', true),
                                    'text'   => '_test_printer_ent1',
                                ],
                            ],
                        ],
                        3 => [
                            'text' => "Root entity > _test_root_entity > _test_child_2",
                            'children' => [
                                0 => [
                                    'id'     => getItemByTypeName('Printer', '_test_printer_ent2', true),
                                    'text'   => '_test_printer_ent2',
                                ],
                            ],
                        ],
                    ],
                ],
            ], [
                'params'    => [
                    'fromtype'  => 'Computer',
                    'itemtype'  => 'Printer',
                    'used'      => [
                        'Printer' => [
                            getItemByTypeName('Printer', '_test_printer_ent0', true),
                            getItemByTypeName('Printer', '_test_printer_ent2', true),
                        ],
                    ],
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'id' => 0,
                            'text' => '-----',
                        ],
                        1 => [
                            'text' => "Root entity > _test_root_entity",
                            'children' => [
                                0 => [
                                    'id'     => getItemByTypeName('Printer', '_test_printer_all', true),
                                    'text'   => '_test_printer_all',
                                ],
                            ],
                        ],
                        2 => [
                            'text' => "Root entity > _test_root_entity > _test_child_1",
                            'children' => [
                                0 => [
                                    'id'     => getItemByTypeName('Printer', '_test_printer_ent1', true),
                                    'text'   => '_test_printer_ent1',
                                ],
                            ],
                        ],
                    ],
                ],
            ], [
                'params'    => [
                    'fromtype'     => 'Computer',
                    'itemtype'     => 'Printer',
                    'searchText'   => 'ent0',
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text' => "Root entity > _test_root_entity",
                            'children' => [
                                0 => [
                                    'id'     => getItemByTypeName('Printer', '_test_printer_ent0', true),
                                    'text'   => '_test_printer_ent0',
                                ],
                            ],
                        ],
                    ],
                ],
            ], [
                'params'    => [
                    'fromtype'     => 'Computer',
                    'itemtype'     => 'Printer',
                    'searchText'   => 'ent0',
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'text' => "Root entity > _test_root_entity",
                            'children' => [
                                0 => [
                                    'id'     => getItemByTypeName('Printer', '_test_printer_ent0', true),
                                    'text'   => '_test_printer_ent0 (' . getItemByTypeName('Printer', '_test_printer_ent0', true) . ')',
                                ],
                            ],
                        ],
                    ],
                ],
                'session_params' => [
                    'glpiis_ids_visible' => true,
                ],
            ],
        ];
    }

    #[DataProvider('getDropdownConnectProvider')]
    public function testGetDropdownConnect($params, $expected, $session_params = [])
    {
        $this->login();

        $bkp_params = [];
        //set session params if any
        if (count($session_params)) {
            foreach ($session_params as $param => $value) {
                if (isset($_SESSION[$param])) {
                    $bkp_params[$param] = $_SESSION[$param];
                }
                $_SESSION[$param] = $value;
            }
        }

        $params['_idor_token'] = $this->generateIdor($params);

        $result = \Dropdown::getDropdownConnect($params, false);

        //reset session params before executing test
        if (count($session_params)) {
            foreach ($session_params as $param => $value) {
                if (isset($bkp_params[$param])) {
                    $_SESSION[$param] = $bkp_params[$param];
                } else {
                    unset($_SESSION[$param]);
                }
            }
        }

        $this->assertSame($expected, $result);
    }

    public static function getDropdownNumberProvider()
    {
        return [
            [
                'params'    => [],
                'expected'  => [
                    'results'   => [
                        0 => [
                            'id'     => 1,
                            'text'   => '1',
                        ],
                        1 => [
                            'id'     => 2,
                            'text'   => '2',
                        ],
                        2 => [
                            'id'     => 3,
                            'text'   => '3',
                        ],
                        3 => [
                            'id'     => 4,
                            'text'   => '4',
                        ],
                        4 => [
                            'id'     => 5,
                            'text'   => '5',
                        ],
                        5 => [
                            'id'     => 6,
                            'text'   => '6',
                        ],
                        6 => [
                            'id'     => 7,
                            'text'   => '7',
                        ],
                        7 => [
                            'id'     => 8,
                            'text'   => '8',
                        ],
                        8 => [
                            'id'     => 9,
                            'text'   => '9',
                        ],
                        9 => [
                            'id'     => 10,
                            'text'   => '10',
                        ],
                    ],
                    'count'     => 10,
                ],
            ], [
                'params'    => [
                    'min'    => 10,
                    'max'    => 30,
                    'step'   => 10,
                ],
                'expected'  => [
                    'results'   => [
                        0 => [
                            'id'     => 10,
                            'text'   => '10',
                        ],
                        1 => [
                            'id'     => 20,
                            'text'   => '20',
                        ],
                        2 => [
                            'id'     => 30,
                            'text'   => '30',
                        ],
                    ],
                    'count'     => 3,
                ],
            ], [
                'params'    => [
                    'min'    => 10,
                    'max'    => 30,
                    'step'   => 10,
                    'used'   => [20],
                ],
                'expected'  => [
                    'results'   => [
                        0 => [
                            'id'     => 10,
                            'text'   => '10',
                        ],
                        1 => [
                            'id'     => 30,
                            'text'   => '30',
                        ],
                    ],
                    'count'     => 2,
                ],
            ], [
                'params'    => [
                    'min'    => 10,
                    'max'    => 30,
                    'step'   => 10,
                    'used'   => [20],
                    'toadd'  => [
                        5 => 'five',
                        6 => "value with unescaped \t and escaped \\t",
                    ],
                ],
                'expected'  => [
                    'results'   => [
                        [
                            'id'     => 5,
                            'text'   => 'five',
                        ],
                        [
                            'id'     => 6,
                            'text'   => "value with unescaped \t and escaped \\t",
                        ],
                        [
                            'id'     => 10,
                            'text'   => '10',
                        ],
                        [
                            'id'     => 30,
                            'text'   => '30',
                        ],
                    ],
                    'count'     => 2,
                ],
            ], [
                'params'    => [
                    'min'    => 10,
                    'max'    => 30,
                    'step'   => 10,
                    'used'   => [20],
                    'unit'   => 'second',
                ],
                'expected'  => [
                    'results'   => [
                        0 => [
                            'id'     => 10,
                            'text'   => '10 seconds',
                        ],
                        1 => [
                            'id'     => 30,
                            'text'   => '30 seconds',
                        ],
                    ],
                    'count'     => 2,
                ],
            ],
        ];
    }

    #[DataProvider('getDropdownNumberProvider')]
    public function testGetDropdownNumber($params, $expected)
    {
        global $CFG_GLPI;
        $orig_max = $CFG_GLPI['dropdown_max'];
        $CFG_GLPI['dropdown_max'] = 10;
        $result = \Dropdown::getDropdownNumber($params, false);
        $CFG_GLPI['dropdown_max'] = $orig_max;
        $this->assertSame($expected, $result);
    }

    public static function getDropdownUsersProvider()
    {
        return [
            [
                'params'    => [],
                'expected'  => [
                    'results' => [
                        0 => [
                            'id'     => 0,
                            'text'   => '-----',
                        ],
                        1 => [
                            'id'     => (int) getItemByTypeName('User', '_test_user', true),
                            'text'   => '_test_user',
                            'title'  => '_test_user - _test_user',
                        ],
                        2 => [
                            'id'     => (int) getItemByTypeName('User', 'glpi', true),
                            'text'   => 'glpi',
                            'title'  => 'glpi - glpi',
                        ],
                        3 => [
                            'id'     => (int) getItemByTypeName('User', 'normal', true),
                            'text'   => 'normal',
                            'title'  => 'normal - normal',
                        ],
                        4 => [
                            'id'     => (int) getItemByTypeName('User', 'post-only', true),
                            'text'   => 'post-only',
                            'title'  => 'post-only - post-only',
                        ],
                        5 => [
                            'id'     => (int) getItemByTypeName('User', 'tech', true),
                            'text'   => 'tech',
                            'title'  => 'tech - tech',
                        ],
                        6 => [
                            'id'     => (int) getItemByTypeName('User', 'jsmith123', true),
                            'text'   => 'Smith John',
                            'title'  => 'Smith John - jsmith123',
                        ],
                    ],
                    'count' => 6,
                ],
            ], [
                'params'    => [
                    'used'   => [
                        getItemByTypeName('User', 'glpi', true),
                        getItemByTypeName('User', 'tech', true),
                    ],
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'id'     => 0,
                            'text'   => '-----',
                        ],
                        1 => [
                            'id'     => (int) getItemByTypeName('User', '_test_user', true),
                            'text'   => '_test_user',
                            'title'  => '_test_user - _test_user',
                        ],
                        2 => [
                            'id'     => (int) getItemByTypeName('User', 'normal', true),
                            'text'   => 'normal',
                            'title'  => 'normal - normal',
                        ],
                        3 => [
                            'id'     => (int) getItemByTypeName('User', 'post-only', true),
                            'text'   => 'post-only',
                            'title'  => 'post-only - post-only',
                        ],
                        4 => [
                            'id'     => (int) getItemByTypeName('User', 'jsmith123', true),
                            'text'   => 'Smith John',
                            'title'  => 'Smith John - jsmith123',
                        ],
                    ],
                    'count' => 4,
                ],
            ], [
                'params'    => [
                    'all'    => true,
                    'used'   => [
                        getItemByTypeName('User', 'glpi', true),
                        getItemByTypeName('User', 'tech', true),
                        getItemByTypeName('User', 'normal', true),
                        getItemByTypeName('User', 'post-only', true),
                    ],
                ],
                'expected'  => [
                    'results' => [
                        0 => [
                            'id'     => 0,
                            'text'   => 'All',
                        ],
                        1 => [
                            'id'     => (int) getItemByTypeName('User', '_test_user', true),
                            'text'   => '_test_user',
                            'title'  => '_test_user - _test_user',
                        ],
                        2 => [
                            'id'     => (int) getItemByTypeName('User', 'jsmith123', true),
                            'text'   => 'Smith John',
                            'title'  => 'Smith John - jsmith123',
                        ],
                    ],
                    'count' => 2,
                ],
            ],
        ];
    }

    #[DataProvider('getDropdownUsersProvider')]
    public function testGetDropdownUsers($params, $expected)
    {
        $this->login();

        $params['_idor_token'] = Session::getNewIDORToken('User');
        $result = \Dropdown::getDropdownUsers($params, false);
        $this->assertSame($expected, $result);
    }

    /**
     * Test getDropdownValue with paginated results on
     * an CommonTreeDropdown
     *
     * @return void
     */
    public function testGetDropdownValuePaginate()
    {
        //let's add some content in Locations
        $location = new \Location();
        for ($i = 0; $i <= 20; ++$i) {
            $this->assertGreaterThan(
                0,
                (int) $location->add([
                    'name'   => "Test location $i",
                ])
            );
        }

        $post = [
            'itemtype'              => $location::getType(),
            'display_emptychoice'   => true,
            'entity_restrict'       => 0,
            'page'                  => 1,
            'page_limit'            => 10,
            '_idor_token'           => Session::getNewIDORToken($location::getType(), ['entity_restrict' => 0]),
        ];
        $values = \Dropdown::getDropdownValue($post);
        $values = (array) json_decode($values);

        $this->assertSame(10, $values['count']);
        $this->assertCount(2, $values['results']);

        $results = (array) $values['results'];
        $this->assertSame(
            [
                'id'     => 0,
                'text'   => '-----',
            ],
            (array) $results[0]
        );

        $list_results = (array) $results[1];
        $this->assertCount(3, $list_results);
        $this->assertSame('Root entity', $list_results['text']);
        $this->assertSame('Entity', $list_results['itemtype']);

        $children = (array) $list_results['children'];
        $this->assertCount(10, $children);
        $this->assertSame(
            [
                'id',
                'text',
                'level',
                'title',
                'selection_text',
            ],
            array_keys((array) $children[0])
        );

        $post['page'] = 2;
        $values = \Dropdown::getDropdownValue($post);
        $values = (array) json_decode($values);

        $this->assertEquals(10, $values['count']);

        $this->assertCount(10, $values['results']);
        $this->assertSame(
            [
                'id',
                'text',
                'level',
                'title',
                'selection_text',
            ],
            array_keys((array) $values['results'][0])
        );

        //use a array condition
        $post = [
            'itemtype'              => $location::getType(),
            'condition'             => ['name' => ['LIKE', "%3%"]],
            'display_emptychoice'   => true,
            'entity_restrict'       => 0,
            'page'                  => 1,
            'page_limit'            => 10,
            '_idor_token'           => Session::getNewIDORToken($location::getType(), ['entity_restrict' => 0, 'condition' => ['name' => ['LIKE', "%3%"]]]),
        ];
        $values = \Dropdown::getDropdownValue($post);
        $values = (array) json_decode($values);

        $this->assertEquals(3, $values['count']);
        $this->assertCount(2, $values['results']);

        //use a string condition
        // Put condition in session and post its key
        $condition_key = sha1(serialize($post['condition']));
        $_SESSION['glpicondition'][$condition_key] = $post['condition'];
        $post['condition']   = $condition_key;
        $post['_idor_token'] = Session::getNewIDORToken($location::getType(), ['entity_restrict' => 0, 'condition' => $condition_key]);
        $values = \Dropdown::getDropdownValue($post);
        $values = (array) json_decode($values);

        $this->assertEquals(3, $values['count']);
        $this->assertCount(2, $values['results']);

        //use a condition that does not exist in session
        $post = [
            'itemtype'              => $location::getType(),
            'condition'             => '`name` LIKE "%4%"',
            'display_emptychoice'   => true,
            'entity_restrict'       => 0,
            'page'                  => 1,
            'page_limit'            => 10,
            '_idor_token'           => Session::getNewIDORToken($location::getType(), ['entity_restrict' => 0, 'condition' => '`name` LIKE "%4%"']),
        ];
        $values = \Dropdown::getDropdownValue($post);
        $values = (array) json_decode($values);

        $this->assertEquals(10, $values['count']);
        $this->assertCount(2, $values['results']);
    }

    private function generateIdor(array $params = [])
    {
        $idor_add_params = [];
        if (isset($params['entity_restrict'])) {
            $idor_add_params['entity_restrict'] = $params['entity_restrict'];
        }
        return Session::getNewIDORToken(($params['itemtype'] ?? ''), $idor_add_params);
    }

    public function testDropdownParent()
    {
        // Create tree
        $state = new State();
        $state_1_id = $state->add(
            [
                'name'      => 'State 1',
                'states_id' => 0,
            ]
        );
        $this->assertGreaterThan(0, $state_1_id);

        $state = new State();
        $state_1_1_id = $state->add(
            [
                'name'      => 'State 1.1',
                'states_id' => $state_1_id,
            ]
        );
        $this->assertGreaterThan(0, $state_1_1_id);

        $state = new State();
        $state_1_1_1_id = $state->add(
            [
                'name'      => 'State 1.1.1',
                'states_id' => $state_1_1_id,
            ]
        );
        $this->assertGreaterThan(0, $state_1_1_1_id);

        $state = new State();
        $state_1_2_id = $state->add(
            [
                'name'      => 'State 1.2',
                'states_id' => $state_1_id,
            ]
        );
        $this->assertGreaterThan(0, $state_1_2_id);

        $state_2_id = $state->add(
            [
                'name'      => 'State 2',
                'states_id' => 0,
            ]
        );
        $this->assertGreaterThan(0, $state_2_id);

        $state_2_1_id = $state->add(
            [
                'name'      => 'State 2.1',
                'states_id' => $state_2_id,
            ]
        );
        $this->assertGreaterThan(0, $state_2_1_id);

        // Check filtering on "State 1"
        $tree_1 = \Dropdown::getDropdownValue(
            [
                'itemtype'            => $state->getType(),
                'parent_id'           => $state_1_id,
                'display_emptychoice' => false,
                'entity_restrict'     => 0,
                '_idor_token'         => Session::getNewIDORToken($state->getType()),
            ],
            false
        );

        $this->assertEquals(
            [
                'results' => [
                    [
                        'text' => 'Root entity',
                        'children' => [
                            [
                                'id'             => $state_1_id,
                                'text'           => 'State 1',
                                'level'          => 1,
                                'disabled'       => true,
                            ],
                            [
                                'id'             => $state_1_1_id,
                                'text'           => 'State 1.1',
                                'level'          => 2,
                                'title'          => 'State 1 > State 1.1',
                                'selection_text' => 'State 1 > State 1.1',
                            ],
                            [
                                'id'             => $state_1_1_1_id,
                                'text'           => 'State 1.1.1',
                                'level'          => 3,
                                'title'          => 'State 1 > State 1.1 > State 1.1.1',
                                'selection_text' => 'State 1 > State 1.1 > State 1.1.1',
                            ],
                            [
                                'id'             => $state_1_2_id,
                                'text'           => 'State 1.2',
                                'level'          => 2,
                                'title'          => 'State 1 > State 1.2',
                                'selection_text' => 'State 1 > State 1.2',
                            ],
                        ],
                        'itemtype' => 'Entity',
                    ],
                ],
                'count' => 3,
            ],
            $tree_1
        );

        // Check filtering on "State 1.1"
        $tree_1 = \Dropdown::getDropdownValue(
            [
                'itemtype'            => $state->getType(),
                'parent_id'           => $state_1_1_id,
                'display_emptychoice' => false,
                'entity_restrict'     => 0,
                '_idor_token'         => Session::getNewIDORToken($state->getType()),
            ],
            false
        );

        $this->assertEquals(
            [
                'results' => [
                    [
                        'text' => 'Root entity',
                        'children' => [
                            [
                                'id'             => $state_1_id,
                                'text'           => 'State 1',
                                'level'          => 1,
                                'disabled'       => true,
                            ],
                            [
                                'id'             => $state_1_1_id,
                                'text'           => 'State 1.1',
                                'level'          => 2,
                                'disabled'       => true,
                            ],
                            [
                                'id'             => $state_1_1_1_id,
                                'text'           => 'State 1.1.1',
                                'level'          => 3,
                                'title'          => 'State 1 > State 1.1 > State 1.1.1',
                                'selection_text' => 'State 1 > State 1.1 > State 1.1.1',
                            ],
                        ],
                        'itemtype' => 'Entity',
                    ],
                ],
                'count' => 1,
            ],
            $tree_1
        );

        // Check filtering on "State 2"
        $tree_1 = \Dropdown::getDropdownValue(
            [
                'itemtype'            => $state->getType(),
                'parent_id'           => $state_2_id,
                'display_emptychoice' => false,
                'entity_restrict'     => 0,
                '_idor_token'         => Session::getNewIDORToken($state->getType()),
            ],
            false
        );

        $this->assertEquals(
            [
                'results' => [
                    [
                        'text' => 'Root entity',
                        'children' => [
                            [
                                'id'             => $state_2_id,
                                'text'           => 'State 2',
                                'level'          => 1,
                                'disabled'       => true,
                            ],
                            [
                                'id'             => $state_2_1_id,
                                'text'           => 'State 2.1',
                                'level'          => 2,
                                'title'          => 'State 2 > State 2.1',
                                'selection_text' => 'State 2 > State 2.1',
                            ],
                        ],
                        'itemtype' => 'Entity',
                    ],
                ],
                'count' => 1,
            ],
            $tree_1
        );
    }

    public function testDropdownAncestors()
    {
        // Create tree
        $state = new State();
        $state_1_id = $state->add(
            [
                'name'      => 'State 1',
                'states_id' => 0,
            ]
        );
        $this->assertEmpty($state->getAncestors());

        $state = new State();
        $state_1_1_id = $state->add(
            [
                'name'      => 'State 1.1',
                'states_id' => $state_1_id,
            ]
        );
        $ancestors = iterator_to_array($state->getAncestors());
        $this->assertCount(1, $ancestors);
        $this->assertEquals('State 1', $ancestors[0]->fields['name']);

        $state = new State();
        $state->add(
            [
                'name'      => 'State 1.1.1',
                'states_id' => $state_1_1_id,
            ]
        );
        $ancestors = iterator_to_array($state->getAncestors());
        $this->assertCount(2, $ancestors);
        $this->assertEquals('State 1', $ancestors[0]->fields['name']);
        $this->assertEquals('State 1.1', $ancestors[1]->fields['name']);

        $state = new State();
        $state->add(
            [
                'name'      => 'State 1.2',
                'states_id' => $state_1_id,
            ]
        );
        $ancestors = iterator_to_array($state->getAncestors());
        $this->assertCount(1, $ancestors);
        $this->assertEquals('State 1', $ancestors[0]->fields['name']);


        $state_2_id = $state->add(
            [
                'name'      => 'State 2',
                'states_id' => 0,
            ]
        );
        $this->assertEmpty($state->getAncestors());

        $state->add(
            [
                'name'      => 'State 2.1',
                'states_id' => $state_2_id,
            ]
        );
        $ancestors = iterator_to_array($state->getAncestors());
        $this->assertCount(1, $ancestors);
        $this->assertEquals('State 2', $ancestors[0]->fields['name']);
    }

    /**
     * Data provider for testDropdownNumber
     *
     * @return Generator
     */
    public static function dropdownNumberProvider(): Generator
    {
        yield [
            'params' => [
                'min'  => 1,
                'max'  => 4,
                'step' => 1,
                'unit' => "",
            ],
            'expected' => [1, 2, 3, 4],
        ];
        yield [
            'params' => [
                'min' => 1,
                'max' => 4,
                'step' => 0.5,
                'unit' => "",
            ],
            'expected' => [
                1,
                1.5,
                2,
                2.5,
                3,
                3.5,
                4,
            ],
        ];

        yield [
            'params' => [
                'min' => 1,
                'max' => 4,
                'step' => 2,
                'unit' => "",
            ],
            'expected' => [
                1,
                3,
            ],
        ];

        yield [
            'params' => [
                'min' => 1,
                'max' => 4,
                'step' => 2.5,
                'unit' => "",
            ],
            'expected' => [
                1,
                3.5,
            ],
        ];

        yield [
            'params' => [
                'min' => 1,
                'max' => 4,
                'step' => 5.5,
                'unit' => "",
            ],
            'expected' => [
                1,
            ],
        ];
    }

    /**
     * Tests for Dropdown::DropdownNumber()
     *
     * @param array $params
     * @param array $expected
     *
     * @return void
     */
    #[DataProvider('dropdownNumberProvider')]
    public function testDropdownNumber(array $params, array $expected): void
    {
        $params['display'] = false;

        $data = \Dropdown::getDropdownNumber($params, false);
        $this->assertArrayHasKey('results', $data);
        $this->assertCount(count($expected), $data['results']);
        $this->assertSame(count($expected), $data['count']);

        foreach ($data['results'] as $key => $dropdown_entry) {
            $this->assertArrayHasKey("id", $dropdown_entry);
            $this->assertArrayHasKey("text", $dropdown_entry);

            $numeric_text_value = floatval($dropdown_entry['text']);
            $this->assertEquals($numeric_text_value, $dropdown_entry['id']);

            $this->assertEquals($expected[$key], $dropdown_entry['id']);
        }
    }

    public function testClone()
    {
        $this->login();

        $dropdowns = \Dropdown::getStandardDropdownItemTypes();
        foreach ($dropdowns as $items) {
            foreach ($items as $itemclass => $n) {
                if (is_subclass_of($itemclass, \CommonDropdown::class) && \Toolbox::hasTrait($itemclass, Clonable::class)) {
                    /** @var \CommonDropdown&Clonable $item */
                    $item = new $itemclass();

                    $extra_fields = $item->getAdditionalFields();
                    $input = [
                        'name' => __FUNCTION__,
                    ];
                    $parent_id = null;
                    foreach ($extra_fields as $field) {
                        if (!isset($field['type'])) {
                            continue;
                        }
                        if ($field['type'] === 'parent' && $parent_id === null) {
                            $this->assertGreaterThan(
                                0,
                                $parent_id = $item->add([
                                    'name' => __FUNCTION__ . '_parent',
                                ])
                            );
                        }
                        $value = match ($field['type']) {
                            'text' => $field['name'],
                            'bool' => 1,
                            'tinymce' => '<p>' . $field['name'] . '</p>',
                            'parent' => $parent_id,
                            default => null
                        };
                        if ($value !== null && isset($field['name']) && is_string($field['name'])) {
                            $input[$field['name']] = $value;
                        }
                    }
                    if ($itemclass === \NetworkName::class) {
                        $input['itemtype'] = 'Computer';
                        $input['items_id'] = 1;
                    }
                    $this->assertGreaterThan(0, $original_items_id = $item->add($input));
                    $original_fields = $item->fields;
                    $this->assertNotEquals($original_items_id, $item->clone());
                    foreach ($original_fields as $field => $value) {
                        $this->assertEquals($value, $item->fields[$field]);
                    }
                }
            }
        }
    }

    public static function assignableAssetsProvider()
    {
        return [
            [\CartridgeItem::class], [Computer::class], [\ConsumableItem::class], [\Monitor::class], [\NetworkEquipment::class],
            [\Peripheral::class], [\Phone::class], [\Printer::class], [\Software::class],
        ];
    }

    #[DataProvider('assignableAssetsProvider')]
    public function testGetDropdownValueAssignableItems($itemtype)
    {
        $this->login();

        $this->assertTrue(\Toolbox::hasTrait($itemtype, AssignableItem::class));

        // Create group for the user
        $group = new \Group();
        $this->assertGreaterThan(
            0,
            $groups_id = $group->add([
                'name' => __FUNCTION__,
                'entities_id' => $this->getTestRootEntity(true),
                'is_recursive' => 1,
            ])
        );
        // Add user to group
        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            $group_user->add(['groups_id' => $groups_id, 'users_id' => $_SESSION['glpiID']])
        );

        Session::loadGroups();

        // Create three items. One with the user assigned, one without, and one with a group assigned.
        $item = new $itemtype();
        $this->assertGreaterThan(
            0,
            $item->add([
                'name' => __FUNCTION__ . '1',
                'entities_id' => $this->getTestRootEntity(true),
            ])
        );
        $this->assertGreaterThan(
            0,
            $item->add([
                'name' => __FUNCTION__ . '2',
                'entities_id' => $this->getTestRootEntity(true),
                'users_id_tech' => $_SESSION['glpiID'],
            ])
        );
        $this->assertGreaterThan(
            0,
            $item->add([
                'name' => __FUNCTION__ . '3',
                'entities_id' => $this->getTestRootEntity(true),
                'groups_id_tech' => $groups_id,
            ])
        );

        $results = \Dropdown::getDropdownValue([
            'itemtype' => $itemtype,
            'display_emptychoice' => 0,
            '_idor_token' => Session::getNewIDORToken($itemtype),
        ], false)['results'];
        // get optgroup id (key in the results array) for the test root entity "_test_root_entity"
        $optgroup_id = array_search("Root _test_root_entity", array_column($results, 'text'));

        $this->assertContains(__FUNCTION__ . '1', array_column($results[$optgroup_id]['children'], 'text'));
        $this->assertContains(__FUNCTION__ . '2', array_column($results[$optgroup_id]['children'], 'text'));
        $this->assertContains(__FUNCTION__ . '3', array_column($results[$optgroup_id]['children'], 'text'));

        // Remove permission to read all items
        $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = READ_ASSIGNED;
        $results = \Dropdown::getDropdownValue([
            'itemtype' => $itemtype,
            'display_emptychoice' => 0,
            '_idor_token' => Session::getNewIDORToken($itemtype),
        ], false)['results'];
        $this->assertNotContains(__FUNCTION__ . '1', array_column($results[$optgroup_id]['children'], 'text'));
        $this->assertContains(__FUNCTION__ . '2', array_column($results[$optgroup_id]['children'], 'text'));
        $this->assertContains(__FUNCTION__ . '3', array_column($results[$optgroup_id]['children'], 'text'));

        // Remove permission to read assigned items
        $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = 0;
        $results = \Dropdown::getDropdownValue([
            'itemtype' => $itemtype,
            'display_emptychoice' => 0,
            '_idor_token' => Session::getNewIDORToken($itemtype),
        ], false)['results'];
        $children = $results[$optgroup_id]['children'] ?? null;
        if ($children === null) {
            $children = [];
        }
        $this->assertNotContains(__FUNCTION__ . '1', $children);
        $this->assertNotContains(__FUNCTION__ . '2', $children);
        $this->assertNotContains(__FUNCTION__ . '3', $children);
    }

    #[DataProvider('assignableAssetsProvider')]
    public function testGetDropdownFindNumAssignableItems($itemtype)
    {
        $this->login();

        $this->assertTrue(\Toolbox::hasTrait($itemtype, AssignableItem::class));

        // Create group for the user
        $group = new \Group();
        $this->assertGreaterThan(
            0,
            $groups_id = $group->add([
                'name' => __FUNCTION__,
                'entities_id' => $this->getTestRootEntity(true),
                'is_recursive' => 1,
            ])
        );
        // Add user to group
        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            $group_user->add(['groups_id' => $groups_id, 'users_id' => $_SESSION['glpiID']])
        );

        Session::loadGroups();

        // Create three items. One with the user assigned, one without, and one with a group assigned.
        $item = new $itemtype();
        $this->assertGreaterThan(
            0,
            $item->add([
                'name' => __FUNCTION__ . '1',
                'entities_id' => $this->getTestRootEntity(true),
            ])
        );
        $this->assertGreaterThan(
            0,
            $item->add([
                'name' => __FUNCTION__ . '2',
                'entities_id' => $this->getTestRootEntity(true),
                'users_id_tech' => $_SESSION['glpiID'],
            ])
        );
        $this->assertGreaterThan(
            0,
            $item->add([
                'name' => __FUNCTION__ . '3',
                'entities_id' => $this->getTestRootEntity(true),
                'groups_id_tech' => $groups_id,
            ])
        );
        // Create two items. One with the user as the owner, and one with a group as the owner.
        $this->assertGreaterThan(
            0,
            $item->add([
                'name' => __FUNCTION__ . '4',
                'entities_id' => $this->getTestRootEntity(true),
                'users_id' => $_SESSION['glpiID'],
            ])
        );
        $this->assertGreaterThan(
            0,
            $item->add([
                'name' => __FUNCTION__ . '5',
                'entities_id' => $this->getTestRootEntity(true),
                'groups_id' => $groups_id,
            ])
        );

        $results = \Dropdown::getDropdownFindNum([
            'itemtype' => $itemtype,
            'table' => $itemtype::getTable(),
            '_idor_token' => Session::getNewIDORToken($itemtype, [
                'table' => $itemtype::getTable(),
            ]),
        ], false)['results'];

        $this->assertContains(__FUNCTION__ . '1', array_column($results, 'text'));
        $this->assertContains(__FUNCTION__ . '2', array_column($results, 'text'));
        $this->assertContains(__FUNCTION__ . '3', array_column($results, 'text'));
        $this->assertContains(__FUNCTION__ . '4', array_column($results, 'text'));
        $this->assertContains(__FUNCTION__ . '5', array_column($results, 'text'));

        // Remove permission to read all items
        $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = READ_ASSIGNED;
        $results = \Dropdown::getDropdownFindNum([
            'itemtype' => $itemtype,
            'table' => $itemtype::getTable(),
            '_idor_token' => Session::getNewIDORToken($itemtype, [
                'table' => $itemtype::getTable(),
            ]),
        ], false)['results'];

        $this->assertNotContains(__FUNCTION__ . '1', array_column($results, 'text'));
        $this->assertContains(__FUNCTION__ . '2', array_column($results, 'text'));
        $this->assertContains(__FUNCTION__ . '3', array_column($results, 'text'));
        $this->assertNotContains(__FUNCTION__ . '4', array_column($results, 'text'));
        $this->assertNotContains(__FUNCTION__ . '5', array_column($results, 'text'));

        $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = READ_OWNED;
        $results = \Dropdown::getDropdownFindNum([
            'itemtype' => $itemtype,
            'table' => $itemtype::getTable(),
            '_idor_token' => Session::getNewIDORToken($itemtype, [
                'table' => $itemtype::getTable(),
            ]),
        ], false)['results'];

        $this->assertNotContains(__FUNCTION__ . '1', array_column($results, 'text'));
        $this->assertNotContains(__FUNCTION__ . '2', array_column($results, 'text'));
        $this->assertNotContains(__FUNCTION__ . '3', array_column($results, 'text'));
        $this->assertContains(__FUNCTION__ . '4', array_column($results, 'text'));
        $this->assertContains(__FUNCTION__ . '5', array_column($results, 'text'));

        // Remove permission to read assigned items
        $_SESSION['glpiactiveprofile'][$itemtype::$rightname] = 0;
        $results = \Dropdown::getDropdownFindNum([
            'itemtype' => $itemtype,
            'table' => $itemtype::getTable(),
            '_idor_token' => Session::getNewIDORToken($itemtype, [
                'table' => $itemtype::getTable(),
            ]),
        ], false)['results'];
        $this->assertNotContains(__FUNCTION__ . '1', array_column($results, 'text'));
        $this->assertNotContains(__FUNCTION__ . '2', array_column($results, 'text'));
        $this->assertNotContains(__FUNCTION__ . '3', array_column($results, 'text'));
        $this->assertNotContains(__FUNCTION__ . '4', array_column($results, 'text'));
        $this->assertNotContains(__FUNCTION__ . '5', array_column($results, 'text'));
    }

    public static function displayWithProvider(): iterable
    {
        yield [
            'item'        => new Computer(),
            'displaywith' => [],
            'filtered'    => [],
        ];

        yield [
            'item'        => new Computer(),
            'displaywith' => ['id', 'notavalidfield', 'serial'],
            'filtered'    => ['id', 'serial'],
        ];
    }

    #[DataProvider('displayWithProvider')]
    public function testFilterDisplayWith(CommonDBTM $item, array $displaywith, array $filtered): void
    {
        $instance = new \Dropdown();
        $this->assertEquals(
            $filtered,
            $this->callPrivateMethod($instance, 'filterDisplayWith', $item, $displaywith)
        );
    }

    public function testFilterDisplayWithLoggedIn(): void
    {
        $this->login('post-only', 'postonly');
        $dd = new \Dropdown();
        $this->assertEquals(
            ['serial'], // pin and puk disallowed by profile
            $this->callPrivateMethod(
                $dd,
                'filterDisplayWith',
                new Item_DeviceSimcard(),
                ['serial', 'pin', 'puk']
            )
        );

        $this->login();
        $dd = new \Dropdown();
        $this->assertEquals(
            ['serial', 'pin', 'puk'], // pin and puk allowed by profile
            $this->callPrivateMethod(
                $dd,
                'filterDisplayWith',
                new Item_DeviceSimcard(),
                ['serial', 'pin', 'puk']
            )
        );

        $this->logOut();
        $dd = new \Dropdown();
        $this->assertEquals(
            ['serial'], // pin and puk disallowed when not connected
            $this->callPrivateMethod(
                $dd,
                'filterDisplayWith',
                new Item_DeviceSimcard(),
                ['serial', 'pin', 'puk']
            )
        );

        $this->login('post-only', 'postonly');
        $dd = new \Dropdown();
        $this->assertEquals(
            ['id', 'firstname'], // all sensitive fields removed, and password_forget_token disallowed by profile
            $this->callPrivateMethod(
                $dd,
                'filterDisplayWith',
                new User(),
                ['id', 'firstname', 'password', 'personal_token', 'api_token', 'cookie_token', 'password_forget_token']
            )
        );

        $this->login();
        $dd = new \Dropdown();
        $this->assertEquals(
            ['id', 'firstname', 'password_forget_token'], // password_forget_token allowed by profile
            $this->callPrivateMethod(
                $dd,
                'filterDisplayWith',
                new User(),
                ['id', 'firstname', 'password', 'personal_token', 'api_token', 'cookie_token', 'password_forget_token']
            )
        );

        $this->logOut();
        $dd = new \Dropdown();
        $this->assertEquals(
            ['id', 'firstname'], // all sensitive fields removed, and password_forget_token disallowed when not connected
            $this->callPrivateMethod(
                $dd,
                'filterDisplayWith',
                new User(),
                ['id', 'firstname', 'password', 'personal_token', 'api_token', 'cookie_token', 'password_forget_token']
            )
        );
    }

    /**
     * Test that `getDropdownActors` for suppliers only returns active suppliers by default.
     * @return void
     */
    public function testSupplierActorDropdownOnlyActive()
    {
        $this->login();
        $this->createItem(\Supplier::class, [
            'name' => __FUNCTION__ . '_active',
            'is_active' => 1,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);
        $inactive_supplier = $this->createItem(\Supplier::class, [
            'name' => __FUNCTION__ . '_inactive',
            'is_active' => 0,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);
        $params = [
            'itemtype' => \Ticket::class,
            'actortype' => 'assign',
            'returned_itemtypes' => [\Supplier::class],
            'searchText' => '',
        ];
        $results = \Dropdown::getDropdownActors($params + ['_idor_token' => Session::getNewIDORToken(\Ticket::class, $params)], false);
        $this->assertNotEmpty($results['results'][0]['children']);
        $this->assertCount(0, array_filter($results['results'][0]['children'], function ($result) use ($inactive_supplier) {
            return $result['id'] === \Supplier::class . '_' . $inactive_supplier->getID();
        }));

        // If asking for inactive_deleted, it should return the inactive supplier
        $params['inactive_deleted'] = 1;
        $results = \Dropdown::getDropdownActors($params + ['_idor_token' => Session::getNewIDORToken(\Ticket::class, $params)], false);
        $this->assertNotEmpty($results['results'][0]['children']);
        $this->assertCount(1, array_filter($results['results'][0]['children'], function ($result) use ($inactive_supplier) {
            return $result['id'] === \Supplier::class . '_' . $inactive_supplier->getID();
        }));
    }

    public function testGetDeviceItemTypes()
    {
        $this->login();
        \Dropdown::resetItemtypesStaticCache();
        $types = \Dropdown::getDeviceItemTypes();
        // Not grouped by default (BC reasons)
        $this->assertCount(1, $types);
        $types = reset($types);
        $this->assertGreaterThanOrEqual(2, $types);
        foreach ($types as $type => $label) {
            $this->assertTrue(is_subclass_of($type, \CommonDevice::class));
            $this->assertIsString($label);
        }

        \Dropdown::resetItemtypesStaticCache();
        $types = \Dropdown::getDeviceItemTypes(true);
        $this->assertGreaterThanOrEqual(3, $types);
        foreach ($types as $category => $types_list) {
            $this->assertIsString($category);
            $this->assertIsArray($types_list);
            $this->assertGreaterThanOrEqual(1, $types_list);
            foreach ($types_list as $type => $label) {
                $this->assertTrue(is_subclass_of($type, \CommonDevice::class));
                $this->assertIsString($label);
            }
        }
    }

    public function testShowTimeStamp()
    {
        global $CFG_GLPI;
        $CFG_GLPI["time_step"] = 30;
        $out = \Dropdown::showTimeStamp('timestamp', [
            'display' => false,
            'min' => 30,
            'max' => 2 * DAY_TIMESTAMP,
        ]);
        $crawler = new Crawler($out);
        $options = $crawler->filter('option');
        $this->assertCount(97, $options); // 48 hours * 2 (30 min steps) + empty choice
        // Check the minutes are padded correctly if they are present
        foreach ($options as $option) {
            $text = $option->textContent;
            if (preg_match('/\d+h\d+/', $text)) {
                $this->assertMatchesRegularExpression('/h\d{2}$/', $text);
            }
        }
    }

    public function testGetLanguages()
    {
        global $CFG_GLPI;

        $this->assertCount(count($CFG_GLPI['languages']), \Dropdown::getLanguages());
    }
}

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
use Psr\Log\LogLevel;

/* Test for inc/rulecriteria.class.php */

class RuleCriteriaTest extends DbTestCase
{
    public function testGetForbiddenStandardMassiveAction()
    {
        $criteria = new \RuleCriteria();
        $this->assertCount(1, $criteria->getForbiddenStandardMassiveAction());
    }

    public function testConstruct()
    {
        $criteria = new \RuleCriteria('RuleDictionnarySoftware');
        $this->assertSame('RuleDictionnarySoftware', $criteria::$itemtype);
    }

    public function testPost_getFromDB()
    {
        $rule     = new \Rule();
        $criteria = new \RuleCriteria();

        $rules_id = $rule->add(['name'        => 'Ignore import',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, (int) $rules_id);

        $criteria_id = $criteria->add(['rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox 52',
        ]);
        $this->assertGreaterThan(0, (int) $criteria_id);

        $this->assertTrue($criteria->getFromDB($criteria_id));
        $this->assertSame('RuleDictionnarySoftware', $criteria::$itemtype);
    }

    public function testGetTypeName()
    {
        $this->assertSame('Criterion', \RuleCriteria::getTypeName(1));
        $this->assertSame('Criteria', \RuleCriteria::getTypeName(\Session::getPluralNumber()));
    }

    public function testRawTypeName()
    {
        $rule     = new \Rule();
        $criteria = new \RuleCriteria();

        $rules_id = $rule->add(['name'        => 'Ignore import',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, (int) $rules_id);

        $criteria_id = $criteria->add(['rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox 52',
        ]);
        $this->assertGreaterThan(0, (int) $criteria_id);

        $this->assertTrue($criteria->getFromDB($criteria_id));
        $this->assertSame('Software is Mozilla Firefox 52', $criteria->getFriendlyName());
    }

    public function testPost_addItem()
    {
        $this->login();
        $rule     = new \Rule();
        $criteria = new \RuleCriteria();

        $rules_id = $rule->add(['name'        => 'Ignore import',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, (int) $rules_id);

        $this->assertTrue($rule->getFromDB($rules_id));
        $this->updateDateMod($rules_id, '2017-03-31 00:00:00');

        $criteria_id = $criteria->add(['rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox 52',
        ]);
        $this->assertGreaterThan(0, (int) $criteria_id);

        $this->assertTrue($rule->getFromDB($rules_id));

        //By adding a criterion, rule's date_mod must have been updated
        $this->assertTrue($rule->fields['date_mod'] > '2017-03-31 00:00:00');
    }

    public function testPost_purgeItem()
    {
        $this->login();
        $rule     = new \Rule();
        $criteria = new \RuleCriteria();

        $rules_id = $rule->add(['name'        => 'Ignore import',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, (int) $rules_id);

        $this->assertTrue($rule->getFromDB($rules_id));

        $criteria_id = $criteria->add(['rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox 52',
        ]);
        $this->assertGreaterThan(0, (int) $criteria_id);
        $this->updateDateMod($rules_id, '2017-03-31 00:00:00');

        $this->assertTrue($criteria->delete(['id' => $criteria_id], true));
        $this->assertTrue($rule->getFromDB($rules_id));

        //By adding a critera, rule's date_mod must have been updated
        $this->assertTrue($rule->fields['date_mod'] > '2017-03-31 00:00:00');
    }

    public function testPrepareInputForAdd()
    {
        $rule     = new \Rule();
        $criteria = new \RuleCriteria();

        // Expects an array containing a `criteria` key
        $this->assertFalse($criteria->prepareInputForAdd('name'));
        $this->assertFalse($criteria->prepareInputForAdd(['rules_id' => 1]));

        $rules_id = $rule->add(['name'        => 'Ignore import',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, (int) $rules_id);

        $this->assertTrue($rule->getFromDB($rules_id));

        $input    = ['rules_id' => $rules_id, 'criteria' => 'name'];
        $this->assertSame($input, $criteria->prepareInputForAdd($input));

        // Expects prepareInputForAdd to return false if `rules_id` is not a valid ID
        $input = ['rules_id' => $rules_id + 1000, 'criteria' => 'name'];
        $this->assertFalse($criteria->prepareInputForAdd($input));
    }

    public function testGetSearchOptionsNew()
    {
        $criteria = new \RuleCriteria();
        $this->assertCount(3, $criteria->rawSearchOptions());
    }

    public function testGetRuleCriterias()
    {
        $rule     = new \Rule();
        $criteria = new \RuleCriteria();

        $rules_id = $rule->add(['name'        => 'Example rule',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleDictionnarySoftware',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 0,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, (int) $rules_id);

        $this->assertGreaterThan(
            0,
            (int) $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'Mozilla Firefox 52',
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'version',
                'condition' => \Rule::REGEX_NOT_MATCH,
                'pattern'   => '/(.*)/',
            ])
        );

        //Get criteria for the newly created rule
        $result   = $criteria->getRuleCriterias($rules_id);
        $this->assertCount(2, $result);
        $this->assertSame('name', $result[0]->fields['criteria']);
        $this->assertSame('version', $result[1]->fields['criteria']);

        //Try to get criteria for a non existing rule
        $this->assertEmpty($criteria->getRuleCriterias(100));
    }

    public function testMatchConditionWildcardOfFind()
    {
        $criteria = new \RuleCriteria();

        $criteria->fields = ['id' => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => \Rule::RULE_WILDCARD,
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Firefox',
                $results,
                $regex_result
            )
        );

        $criteria->fields = ['id' => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_FIND,
            'pattern'   => \Rule::RULE_WILDCARD,
        ];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Firefox',
                $results,
                $regex_result
            )
        );
    }

    public function testMatchConditionIsOrNot()
    {
        $criteria = new \RuleCriteria();

        $criteria->fields = ['id' => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox',
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Firefox',
                $results,
                $regex_result
            )
        );
        $this->assertSame('Mozilla Firefox', $results['name']);

        $results = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                ['Mozilla Firefox', 'foo'],
                $results,
                $regex_result
            )
        );
        $this->assertSame('Mozilla Firefox', $results['name']);

        $results = [];
        $this->assertFalse(
            $criteria->match(
                $criteria,
                ['foo', 'bar'],
                $results,
                $regex_result
            )
        );
        $this->assertEmpty($results);

        $results = [];
        $this->assertFalse(
            $criteria->match(
                $criteria,
                'foo',
                $results,
                $regex_result
            )
        );
        $this->assertEmpty($results);

        $criteria->fields = ['id' => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS_NOT,
            'pattern'   => 'Mozilla Firefox',
        ];

        $results = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'foo',
                $results,
                $regex_result
            )
        );
        $this->assertSame('Mozilla Firefox', $results['name']);
    }

    public function testMatchConditionExistsOrNot()
    {
        $criteria = new \RuleCriteria();

        $criteria->fields = ['id' => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_EXISTS,
            'pattern'   => '',
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Firefox',
                $results,
                $regex_result
            )
        );

        $this->assertEmpty($results);

        $results = [];
        $this->assertFalse(
            $criteria->match(
                $criteria,
                '',
                $results,
                $regex_result
            )
        );
        $this->assertEmpty($results);

        $results = [];
        $this->assertFalse(
            $criteria->match(
                $criteria,
                null,
                $results,
                $regex_result
            )
        );
        $this->assertEmpty($results);
    }

    public function testMatchContainsOrNot()
    {
        $criteria = new \RuleCriteria();

        $criteria->fields = ['id' => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_CONTAIN,
            'pattern'   => 'Firefox',
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Firefox',
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => 'Firefox'], $results);

        $this->assertTrue(
            $criteria->match(
                $criteria,
                'mozilla firefox',
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => 'Firefox'], $results);

        $this->assertFalse(
            $criteria->match(
                $criteria,
                '',
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => 'Firefox'], $results);

        $this->assertFalse(
            $criteria->match(
                $criteria,
                null,
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => 'Firefox'], $results);

        $criteria->fields = ['id' => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_NOT_CONTAIN,
            'pattern'   => 'Firefox',
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Konqueror',
                $results,
                $regex_result
            )
        );

        $this->assertSame(['name' => 'Firefox'], $results);
    }

    public function testMatchBeginEnd()
    {
        $criteria = new \RuleCriteria();

        $criteria->fields = ['id'        => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_BEGIN,
            'pattern'   => 'Mozilla',
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Firefox',
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => 'Mozilla'], $results);

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'mozilla firefox',
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => 'Mozilla'], $results);

        $results      = [];
        $regex_result = [];
        $this->assertFalse(
            $criteria->match(
                $criteria,
                '',
                $results,
                $regex_result
            )
        );
        $this->assertSame([], $results);

        $results      = [];
        $regex_result = [];
        $this->assertFalse(
            $criteria->match(
                $criteria,
                null,
                $results,
                $regex_result
            )
        );
        $this->assertSame([], $results);

        $criteria->fields = ['id' => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_END,
            'pattern'   => 'Firefox',
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Firefox',
                $results,
                $regex_result
            )
        );

        $this->assertSame(['name' => 'Firefox'], $results);

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'mozilla firefox',
                $results,
                $regex_result
            )
        );

        $this->assertSame(['name' => 'Firefox'], $results);

        $results      = [];
        $regex_result = [];
        $this->assertFalse(
            $criteria->match(
                $criteria,
                '',
                $results,
                $regex_result
            )
        );

        $this->assertSame([], $results);
    }

    public function testMatchRegex()
    {
        $criteria = new \RuleCriteria();

        $criteria->fields = ['id'        => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/Mozilla Firefox (.*)/',
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Firefox 52',
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => '/Mozilla Firefox (.*)/'], $results);
        $this->assertSame([0 => ['52']], $regex_result);

        $this->assertFalse(
            $criteria->match(
                $criteria,
                'Mozilla Thunderbird 52',
                $results,
                $regex_result
            )
        );

        $criteria->fields = ['id'        => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_NOT_MATCH,
            'pattern'   => '/Mozilla Firefox (.*)/',
        ];

        $results      = [];
        $regex_result = [];

        $this->assertFalse(
            $criteria->match(
                $criteria,
                'Mozilla Firefox 52',
                $results,
                $regex_result
            )
        );
        $this->assertSame([], $results);
        $this->assertSame([], $regex_result);

        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Thunderbird 52',
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => '/Mozilla Firefox (.*)/'], $results);
        $this->assertSame([], $regex_result);

        //another one
        $criteria->fields = ['id'        => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/Mozilla (Firefox|Thunderbird) (.*)/',
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Firefox 52',
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => '/Mozilla (Firefox|Thunderbird) (.*)/'], $results);
        $this->assertSame([0 => ['Firefox', '52']], $regex_result);

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Mozilla Thunderbird 52',
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => '/Mozilla (Firefox|Thunderbird) (.*)/'], $results);
        $this->assertSame([0 => ['Thunderbird', '52']], $regex_result);

        //test for #8117
        $criteria->fields = ['id'        => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/CN=([0-9a-z]+) VPN/',
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                'Subject = CN=val01 VPN Certificate,O=gefwm01..gnnxpz Subject = CN=val02 VPN Certificate,O=gefwm01..gnnxpz',
                $results,
                $regex_result
            )
        );
        $this->assertSame(['name' => '/CN=([0-9a-z]+) VPN/'], $results);
        $this->assertSame([0 => ['val01', 'val02']], $regex_result);
    }

    public function testMatchConditionUnderNotUnder()
    {
        $this->login();

        $criteria = new \RuleCriteria();
        $location = new \Location();

        $loc_1 = $location->import(['completename' => 'loc1',
            'entities_id' => 0, 'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $loc_1);

        $loc_2 = $location->import(['completename' => 'loc1 > sloc1',
            'entities_id' => 0, 'is_recursive' => 1,
            'locations_id' => $loc_1,
        ]);
        $this->assertGreaterThan(0, $loc_2);

        $loc_3 = $location->import(['completename' => 'loc3',
            'entities_id' => 0, 'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $loc_3);

        $criteria->fields = ['id'        => 1,
            'rules_id'  => 1,
            'criteria'  => 'locations_id',
            'condition' => \Rule::PATTERN_UNDER,
            'pattern'   => $loc_1,
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                $loc_1,
                $results,
                $regex_result
            )
        );

        $this->assertEmpty($results);
        $this->assertTrue(
            $criteria->match(
                $criteria,
                $loc_2,
                $results,
                $regex_result
            )
        );

        $this->assertFalse(
            $criteria->match(
                $criteria,
                $loc_3,
                $results,
                $regex_result
            )
        );

        $criteria->fields = ['id'        => 1,
            'rules_id'  => 1,
            'criteria'  => 'locations_id',
            'condition' => \Rule::PATTERN_NOT_UNDER,
            'pattern'   => $loc_1,
        ];

        $results      = [];
        $regex_result = [];
        $this->assertTrue(
            $criteria->match(
                $criteria,
                $loc_3,
                $results,
                $regex_result
            )
        );
        $this->assertEmpty($results);

        $this->assertFalse(
            $criteria->match(
                $criteria,
                $loc_2,
                $results,
                $regex_result
            )
        );
        $this->assertEmpty($results);
    }

    public function testGetConditions()
    {
        $common_conditions = [
            \Rule::PATTERN_IS, \Rule::PATTERN_IS_NOT, \Rule::PATTERN_CONTAIN, \Rule::PATTERN_NOT_CONTAIN,
            \Rule::PATTERN_BEGIN, \Rule::PATTERN_END, \Rule::REGEX_MATCH, \Rule::REGEX_NOT_MATCH,
            \Rule::PATTERN_EXISTS, \Rule::PATTERN_DOES_NOT_EXISTS,
        ];
        $this->assertArrayIsEqualIgnoringKeysOrder(
            $common_conditions,
            array_keys(\RuleCriteria::getConditions('RuleDictionnarySoftware'))
        );
        $this->assertArrayIsEqualIgnoringKeysOrder([
            ...$common_conditions, \Rule::PATTERN_UNDER, \Rule::PATTERN_NOT_UNDER,
        ], array_keys(\RuleCriteria::getConditions('RuleTicket', 'locations_id')));
        $this->assertArrayIsEqualIgnoringKeysOrder([
            \Rule::PATTERN_CONTAIN, \Rule::PATTERN_NOT_CONTAIN,
            \Rule::PATTERN_BEGIN, \Rule::PATTERN_END, \Rule::REGEX_MATCH, \Rule::REGEX_NOT_MATCH,
            \Rule::PATTERN_EXISTS, \Rule::PATTERN_DOES_NOT_EXISTS,
            \Rule::PATTERN_DATE_IS_BEFORE, \Rule::PATTERN_DATE_IS_AFTER, \Rule::PATTERN_DATE_IS_EQUAL,
            \Rule::PATTERN_DATE_IS_NOT_EQUAL,
        ], array_keys(\RuleCriteria::getConditions('RuleTicket', 'date')));
        $this->assertArrayIsEqualIgnoringKeysOrder([
            \Rule::PATTERN_CONTAIN, \Rule::PATTERN_NOT_CONTAIN,
            \Rule::PATTERN_BEGIN, \Rule::PATTERN_END, \Rule::REGEX_MATCH, \Rule::REGEX_NOT_MATCH,
            \Rule::PATTERN_EXISTS, \Rule::PATTERN_DOES_NOT_EXISTS,
            \Rule::PATTERN_DATE_IS_BEFORE, \Rule::PATTERN_DATE_IS_AFTER, \Rule::PATTERN_DATE_IS_EQUAL,
            \Rule::PATTERN_DATE_IS_NOT_EQUAL,
        ], array_keys(\RuleCriteria::getConditions('RuleAsset', 'last_inventory_update')));
        $this->assertArrayIsEqualIgnoringKeysOrder([
            ...$common_conditions, \Rule::PATTERN_CIDR, \Rule::PATTERN_NOT_CIDR,
        ], array_keys(\RuleCriteria::getConditions('RuleAsset', 'ip')));
    }

    public function testGetConditionByID()
    {
        $condition = \RuleCriteria::getConditionByID(
            \Rule::PATTERN_BEGIN,
            'RuleTicket',
            'locations_id'
        );
        $this->assertSame("starting with", $condition);

        $condition = \RuleCriteria::getConditionByID(
            \Rule::PATTERN_NOT_UNDER,
            'RuleTicket',
            'locations_id'
        );
        $this->assertSame('not under', $condition);
    }

    /**
     * Update rule modification date
     *
     * @param integer $rules_id Rule ID
     * @param string  $time     Time to set modification date to
     *
     * @return void
     */
    private function updateDateMod($rules_id, $time)
    {
        global $DB;

        $DB->update(
            'glpi_rules',
            ['date_mod' => $time],
            ['id' => $rules_id]
        );
    }

    public function testBadRegex()
    {
        $criteria = new \RuleCriteria();

        $criteria->fields = ['id'        => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/Mozilla Firefox (.*)', //bad regexp pattern
        ];

        $results      = [];
        $regex_result = [];

        $this->assertFalse(
            $criteria->match(
                $criteria,
                'Mozilla Firefox 52',
                $results,
                $regex_result
            )
        );
        $this->hasPhpLogRecordThatContains(
            'Invalid regular expression `/Mozilla Firefox (.*)`.',
            LogLevel::WARNING,
        );

        $criteria = new \RuleCriteria();

        $criteria->fields = ['id'        => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_NOT_MATCH,
            'pattern'   => '/Firefox (.*)', //bad regexp pattern
        ];

        $results      = [];
        $regex_result = [];


        $this->assertFalse(
            $criteria->match(
                $criteria,
                'Any value',
                $results,
                $regex_result
            )
        );
        $this->hasPhpLogRecordThatContains(
            'Invalid regular expression `/Firefox (.*)`.',
            LogLevel::WARNING,
        );
    }

    public static function ruleCriteriaMatchProvider(): iterable
    {
        // Checks quotes, slashes and HTML special chars handling
        yield [
            'condition' => \Rule::PATTERN_BEGIN,
            'pattern'   => "Besoin d'un",
            'value'     => "Besoin d'un ordinateur",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::PATTERN_BEGIN,
            'pattern'   => "<R&D>",
            'value'     => "<R&D> Test",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::PATTERN_BEGIN,
            'pattern'   => "\o",
            'value'     => "\o/",
            'matches'   => true,
        ];

        yield [
            'condition' => \Rule::PATTERN_END,
            'pattern'   => "d'un ordinateur",
            'value'     => "Besoin d'un ordinateur",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::PATTERN_END,
            'pattern'   => "<R&D>",
            'value'     => "Test <R&D>",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::PATTERN_END,
            'pattern'   => "/",
            'value'     => "\o/",
            'matches'   => true,
        ];

        yield [
            'condition' => \Rule::PATTERN_CONTAIN,
            'pattern'   => "d'un",
            'value'     => "Besoin d'un ordinateur",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::PATTERN_CONTAIN,
            'pattern'   => "<R&D>",
            'value'     => "<R&D> Test",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::PATTERN_CONTAIN,
            'pattern'   => "\\",
            'value'     => "\o/",
            'matches'   => true,
        ];

        yield [
            'condition' => \Rule::PATTERN_NOT_CONTAIN,
            'pattern'   => "d'un",
            'value'     => "Besoin d'un ordinateur",
            'matches'   => false,
        ];
        yield [
            'condition' => \Rule::PATTERN_NOT_CONTAIN,
            'pattern'   => "<R&D>",
            'value'     => "<R&D> Test",
            'matches'   => false,
        ];
        yield [
            'condition' => \Rule::PATTERN_NOT_CONTAIN,
            'pattern'   => "\\",
            'value'     => "\o/",
            'matches'   => false,
        ];

        yield [
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => "Besoin d'un ordinateur",
            'value'     => "Besoin d'un ordinateur",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => "<R&D> Test",
            'value'     => "<R&D> Test",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::PATTERN_NOT_CONTAIN,
            'pattern'   => "\o/",
            'value'     => "\o/",
            'matches'   => false,
        ];

        yield [
            'condition' => \Rule::PATTERN_IS_NOT,
            'pattern'   => "Besoin d'un ordinateur",
            'value'     => "Besoin d'un ordinateur",
            'matches'   => false,
        ];
        yield [
            'condition' => \Rule::PATTERN_IS_NOT,
            'pattern'   => "<R&D> Test",
            'value'     => "<R&D> Test",
            'matches'   => false,
        ];
        yield [
            'condition' => \Rule::PATTERN_IS_NOT,
            'pattern'   => "\o/",
            'value'     => "\o/",
            'matches'   => false,
        ];

        yield [
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => "/d'un/",
            'value'     => "Besoin d'un ordinateur",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => "/<R&D>/",
            'value'     => "<R&D> Test",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => "/\\\.+\/$/",
            'value'     => "\o/",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => "/line1.*line2/",
            'value'     => "line1\nline2",
            'matches'   => true,
        ];
        yield [
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => "/line1.*line3/",
            'value'     => "line1\n<p>line2</p>\nline3",
            'matches'   => true,
        ];
    }

    #[DataProvider('ruleCriteriaMatchProvider')]
    public function testMatch(int $condition, string $pattern, string $value, bool $matches)
    {
        $criteria = new \RuleCriteria();
        $criteria->fields = [
            'id'        => 1,
            'rules_id'  => 1,
            'criteria'  => 'name',
            'condition' => $condition,
            'pattern'   => $pattern,
        ];

        $results      = [];
        $regex_result = [];

        $this->assertEquals(
            $matches,
            $criteria->match($criteria, $value, $results, $regex_result)
        );

        if ($matches) {
            $this->assertEquals(['name' => $pattern], $results);
        }
    }
}

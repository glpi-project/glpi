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

namespace tests\units\Glpi\Asset;

use DbTestCase;
use Rule;
use RuleCollection;
use Session;

class DictionaryTest extends DbTestCase
{
    public function testInDictionaryList()
    {
        $this->login();

        $definition = $this->initAssetDefinition();
        $dictionaries = RuleCollection::getDictionnaries();

        $has_model_dictionary = false;
        $has_type_dictionary = false;

        foreach ($dictionaries as $group) {
            if ($group['type'] === 'Models') {
                foreach ($group['entries'] as $entry) {
                    if (
                        $entry['label'] === $definition->getAssetModelClassName()::getTypeName(Session::getPluralNumber())
                        && str_contains($entry['link'], 'front/asset/ruledictionarymodel.php?class=' . $definition->fields['system_name'])
                        && $entry['icon'] === $definition->getAssetModelClassName()::getIcon()
                    ) {
                        $has_model_dictionary = true;
                        break;
                    }
                }
            } elseif ($group['type'] === 'Types') {
                foreach ($group['entries'] as $entry) {
                    if (
                        $entry['label'] === $definition->getAssetTypeClassName()::getTypeName(Session::getPluralNumber())
                        && str_contains($entry['link'], 'front/asset/ruledictionarytype.php?class=' . $definition->fields['system_name'])
                        && $entry['icon'] === $definition->getAssetTypeClassName()::getIcon()
                    ) {
                        $has_type_dictionary = true;
                        break;
                    }
                }
            }
        }

        $this->assertTrue($has_model_dictionary);
        $this->assertTrue($has_type_dictionary);
    }

    public function testMenuContent()
    {
        $this->login();
        $definition = $this->initAssetDefinition();
        $rule_menu_content = Rule::getMenuContent();
        $model_key = 'model.' . $definition->fields['system_name'];
        $type_key = 'type.' . $definition->fields['system_name'];

        $this->assertArrayHasKey($model_key, $rule_menu_content['dictionnary']['options']);
        $this->assertArrayHasKey($type_key, $rule_menu_content['dictionnary']['options']);

        $this->assertEquals(
            $definition->getAssetModelClassName()::getTypeName(Session::getPluralNumber()),
            $rule_menu_content['dictionnary']['options'][$model_key]['title']
        );
        $model_page = $rule_menu_content['dictionnary']['options'][$model_key]['page'];
        $this->assertEquals('/front/asset/ruledictionarymodel.php?class=' . $definition->fields['system_name'], $model_page);
        $this->assertEquals($model_page, $rule_menu_content['dictionnary']['options'][$model_key]['links']['search']);
        $this->assertStringContainsString(
            '/front/asset/ruledictionarymodel.form.php?class=' . $definition->fields['system_name'],
            $rule_menu_content['dictionnary']['options'][$model_key]['links']['add']
        );

        $this->assertEquals(
            $definition->getAssetTypeClassName()::getTypeName(Session::getPluralNumber()),
            $rule_menu_content['dictionnary']['options'][$type_key]['title']
        );
        $type_page = $rule_menu_content['dictionnary']['options'][$type_key]['page'];
        $this->assertEquals('/front/asset/ruledictionarytype.php?class=' . $definition->fields['system_name'], $type_page);
        $this->assertEquals($type_page, $rule_menu_content['dictionnary']['options'][$type_key]['links']['search']);
        $this->assertStringContainsString(
            '/front/asset/ruledictionarytype.form.php?class=' . $definition->fields['system_name'],
            $rule_menu_content['dictionnary']['options'][$type_key]['links']['add']
        );
    }
}

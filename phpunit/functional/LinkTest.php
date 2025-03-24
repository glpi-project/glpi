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

class LinkTest extends DbTestCase
{
    public static function linkContentProvider(): iterable
    {
        // Empty link
        yield [
            'link'     => '',
            'safe_url' => false,
            'expected' => [''],
        ];
        yield [
            'link'     => '',
            'safe_url' => true,
            'expected' => ['#'],
        ];

        foreach ([true, false] as $safe_url) {
            // Link that is actually a title (it is a normal usage!)
            yield [
                'link'     => '[LOCATION] > [SERIAL]/[MODEL] ([USER])',
                'safe_url' => $safe_url,
                'expected' => [$safe_url ? '#' : '_location01 > ABC0004E6/_test_computermodel_1 (glpi)'],
            ];

            // Link that is actually a long text (it is a normal usage!)
            yield [
                'link'     => <<<TEXT
id:       [ID]
name:     [NAME]
serial:   [SERIAL]/[OTHERSERIAL]
model:    [MODEL]
location: [LOCATION] ([LOCATIONID])
domain:   [DOMAIN] ([NETWORK])
owner:    [USER]/[GROUP]
TEXT,
                'safe_url' => $safe_url,
                'expected' => [
                    $safe_url
                        ? '#'
                        : <<<TEXT
id:       %ITEM_ID%
name:     Test computer
serial:   ABC0004E6/X0000015
model:    _test_computermodel_1
location: _location01 (1)
domain:   domain1.tld (LAN)
owner:    glpi/_test_group_1
TEXT,
                ],
            ];

            // Valid http link
            yield [
                'link'     => 'https://[LOGIN]@[DOMAIN]/[FIELD:uuid]/',
                'safe_url' => $safe_url,
                'expected' => ['https://_test_user@domain1.tld/c938f085-4192-4473-a566-46734bbaf6ad/'],
            ];
        }

        // Javascript link
        yield [
            'link'     => 'javascript:alert(1);" title="[NAME]"',
            'safe_url' => false,
            'expected' => ['javascript:alert(1);" title="Test computer"'],
        ];
        yield [
            'link'     => 'javascript:alert(1);" title="[NAME]"',
            'safe_url' => true,
            'expected' => ['#'],
        ];
    }

    /**
     * @dataProvider linkContentProvider
     */
    public function testGenerateLinkContents(
        string $link,
        bool $safe_url,
        array $expected
    ): void {
        $this->login();

        // Create network
        $network = $this->createItem(
            \Network::class,
            [
                'name' => 'LAN',
            ]
        );

        // Create computer
        $item = $this->createItem(
            \Computer::class,
            [
                'name'              => 'Test computer',
                'serial'            => 'ABC0004E6',
                'otherserial'       => 'X0000015',
                'uuid'              => 'c938f085-4192-4473-a566-46734bbaf6ad',
                'entities_id'       => $_SESSION['glpiactive_entity'],
                'groups_id'         => getItemByTypeName(\Group::class, '_test_group_1', true),
                'locations_id'      => getItemByTypeName(\Location::class, '_location01', true),
                'networks_id'       => $network->getID(),
                'users_id'          => getItemByTypeName(\User::class, 'glpi', true),
                'computermodels_id' => getItemByTypeName(\ComputerModel::class, '_test_computermodel_1', true),
            ]
        );

        // Attach domains
        $domain1 = $this->createItem(
            \Domain::class,
            [
                'name'        => 'domain1.tld',
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]
        );
        $this->createItem(
            \Domain_Item::class,
            [
                'domains_id' => $domain1->getID(),
                'itemtype'   => \Computer::class,
                'items_id'   => $item->getID(),
            ]
        );
        $domain2 = $this->createItem(
            \Domain::class,
            [
                'name'        => 'domain2.tld',
                'entities_id' => $_SESSION['glpiactive_entity'],
            ]
        );
        $this->createItem(
            \Domain_Item::class,
            [
                'domains_id' => $domain2->getID(),
                'itemtype'   => \Computer::class,
                'items_id'   => $item->getID(),
            ]
        );

        $instance = new \Link();
        $expected = str_replace('%ITEM_ID%', $item->getID(), $expected);
        $this->assertEquals(
            $expected,
            $instance->generateLinkContents($link, $item, $safe_url)
        );

        // Validates that default values is true
        if ($safe_url) {
            $this->assertEquals(
                $expected,
                $instance->generateLinkContents($link, $item)
            );
        }
    }
}

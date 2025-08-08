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
use Symfony\Component\DomCrawler\Crawler;

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
                'link'     => '{{ LOCATION }} > {{ SERIAL }}/{{ MODEL }} ({{ USER }})',
                'safe_url' => $safe_url,
                'expected' => [$safe_url ? '#' : '_location01 > ABC0004E6/_test_computermodel_1 (glpi)'],
            ];

            // Link that is actually a long text (it is a normal usage!)
            yield [
                'link'     => <<<TEXT
id:       {{ ID }}
name:     {{ NAME }}
serial:   {{ SERIAL }}/{{ OTHERSERIAL }}
model:    {{ MODEL }}
location: {{ LOCATION }} ({{ LOCATIONID }})
domain:   {{ DOMAIN }} ({{ NETWORK }})
owner:    {{ USER }}/{{ GROUP }}
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
                'link'     => 'https://{{ LOGIN }}@{{ DOMAIN }}/{{ item.uuid }}/',
                'safe_url' => $safe_url,
                'expected' => ['https://_test_user@domain1.tld/c938f085-4192-4473-a566-46734bbaf6ad/'],
            ];
        }

        // Javascript link
        yield [
            'link'     => 'javascript:alert(1);" title="{{ NAME }}"',
            'safe_url' => false,
            'expected' => ['javascript:alert(1);" title="Test computer"'],
        ];
        yield [
            'link'     => 'javascript:alert(1);" title="{{ NAME }}"',
            'safe_url' => true,
            'expected' => ['#'],
        ];
        yield [
            'link'     => '{% for domain in DOMAINS %}{{ domain }} {% endfor %}',
            'safe_url' => false,
            'expected' => ['domain1.tld domain2.tld '],
        ];
        yield [
            'link'     => '{{ NAME }} {{ MAC }} {{ IP }}',
            'safe_url' => false,
            'expected' => [
                'ip%IP1_ID%' => 'Test computer aa:aa:aa:aa:aa:aa 10.10.13.12',
                'ip%IP2_ID%' => 'Test computer bb:bb:bb:bb:bb:bb 10.10.13.13',
            ],
        ];
    }

    #[DataProvider('linkContentProvider')]
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
            ],
            ['groups_id']
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

        $networkport_1 = $this->createItem('NetworkPort', [
            'name' => 'eth0',
            'itemtype' => 'Computer',
            'items_id' => $item->getID(),
            'instantiation_type' => 'NetworkPortEthernet',
            'mac' => 'aa:aa:aa:aa:aa:aa',
        ]);
        $networkport_2 = $this->createItem('NetworkPort', [
            'name' => 'eth1',
            'itemtype' => 'Computer',
            'items_id' => $item->getID(),
            'instantiation_type' => 'NetworkPortEthernet',
            'mac' => 'bb:bb:bb:bb:bb:bb',
        ]);
        $networkname_1 = $this->createItem('NetworkName', [
            'itemtype' => 'NetworkPort',
            'items_id' => $networkport_1->getID(),
        ]);
        $networkname_2 = $this->createItem('NetworkName', [
            'itemtype' => 'NetworkPort',
            'items_id' => $networkport_2->getID(),
        ]);
        $ip_1 = $this->createItem('IPAddress', [
            'itemtype' => 'NetworkName',
            'items_id' => $networkname_1->getID(),
            'name' => '10.10.13.12',
        ]);
        $ip_2 = $this->createItem('IPAddress', [
            'itemtype' => 'NetworkName',
            'items_id' => $networkname_2->getID(),
            'name' => '10.10.13.13',
        ]);

        $instance = new \Link();
        if (isset($expected['ip%IP1_ID%'])) {
            $expected['ip' . $ip_1->getID()] = $expected['ip%IP1_ID%'];
            unset($expected['ip%IP1_ID%']);
        }
        if (isset($expected['ip%IP2_ID%'])) {
            $expected['ip' . $ip_2->getID()] = $expected['ip%IP2_ID%'];
            unset($expected['ip%IP2_ID%']);
        }
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

    public function testGenerateLinkContents2(): void
    {
        $this->login();

        $item = $this->createItem(
            \Computer::class,
            [
                'name'         => 'Test computer 2',
                'entities_id'  => $_SESSION['glpiactive_entity'],
            ]
        );

        $instance = new \Link();
        $this->assertEquals(
            [],
            $instance->generateLinkContents('{{ NAME }} {{ MAC }} {{ IP }}', $item)
        );

        $this->assertEquals(
            ['#'],
            $instance->generateLinkContents('<script>alert(1);</script>', $item)
        );
    }
    public static function invalidLinkContentsProvider()
    {
        return [
            ['{{'],
            ['{% if ID'],
            ['{% if ID %}'],
        ];
    }

    #[DataProvider('invalidLinkContentsProvider')]
    public function testInvalidLinkContents($content)
    {
        $link = new \Link();
        $this->assertFalse($link->add([
            'link' => $content,
        ]));
        $this->hasSessionMessages(ERROR, [
            __('Invalid twig template syntax'),
        ]);
        $this->assertFalse($link->add([
            'data' => $content,
        ]));
        $this->hasSessionMessages(ERROR, [
            __('Invalid twig template syntax'),
        ]);
    }

    public function testShowAllLinksForItem()
    {
        $this->login();

        // Buttons to add links should only show if the user can update the item
        ob_start();
        \Link::showAllLinksForItem(getItemByTypeName(\Computer::class, '_test_pc01'));
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $this->assertEquals(2, $crawler->filter('a.btn')->count());
        $_SESSION['glpiactiveprofile']['computer'] = READ;
        ob_start();
        \Link::showAllLinksForItem(getItemByTypeName(\Computer::class, '_test_pc01'));
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $this->assertEquals(0, $crawler->filter('a.btn')->count());
    }
}

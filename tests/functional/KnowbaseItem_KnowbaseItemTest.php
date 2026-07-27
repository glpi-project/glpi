<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace test\units;

use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_KnowbaseItem;
use PHPUnit\Framework\Attributes\DataProvider;

final class KnowbaseItem_KnowbaseItemTest extends DbTestCase
{
    public static function prepareInputForAddProvider(): iterable
    {
        $tree = [
            'Article A' => [
                'Article A1' => [
                    'Article A1a' => [],
                    'Article A1b' => [],
                ],
                'Article A2' => [],
            ],
            'Article B' => [
                'Article B1' => [],
                'Article B2' => [],
            ],
        ];

        // Valid inputs
        yield "Valid input 1" => [
            'tree' => $tree,
            'input' => [
                'knowbaseitems_id' => "Article A",
                'knowbaseitems_id_parent' => "Article B",
            ],
            'expected' => true,
        ];
        yield "Valid input 2" => [
            'tree' => $tree,
            'input' => [
                'knowbaseitems_id' => "Article B1",
                'knowbaseitems_id_parent' => "Article B2",
            ],
            'expected' => true,
        ];

        // Missing values
        yield "Input must contain knowbaseitems_id" => [
            'tree' => $tree,
            'input' => [
                'knowbaseitems_id_parent' => "Article A",
            ],
            'expected' => false,
            'with_message' => "Missing 'knowbaseitems_id' value.",
        ];
        yield "Input must contain knowbaseitems_id_parent" => [
            'tree' => $tree,
            'input' => [
                'knowbaseitems_id' => "Article A",
            ],
            'expected' => false,
            'with_message' => "Missing 'knowbaseitems_id_parent' value.",
        ];

        // Invalid values
        yield "Value of knowbaseitems_id is invalid" => [
            'tree' => $tree,
            'input' => [
                'knowbaseitems_id' => 99999,
                'knowbaseitems_id_parent' => "Article B",
            ],
            'expected' => false,
            'with_message' => "Invalid 'knowbaseitems_id' value: '99999'.",
        ];
        yield "Value of knowbaseitems_id_parent is invalid" => [
            'tree' => $tree,
            'input' => [
                'knowbaseitems_id' => "Article B",
                'knowbaseitems_id_parent' => 99999,
            ],
            'expected' => false,
            'with_message' => "Invalid 'knowbaseitems_id_parent' value: '99999'.",
        ];

        // Can't link the article to itself
        yield "Link to itself is not allowed" => [
            'tree' => $tree,
            'input' => [
                'knowbaseitems_id' => "Article A",
                'knowbaseitems_id_parent' => "Article A",
            ],
            'expected' => false,
            'with_message' => "An article cannot be its own parent.",
        ];

        // Cyclic graph prevention
        yield "Can't link to direct parent" => [
            'tree' => $tree,
            'input' => [
                'knowbaseitems_id' => "Article A",
                'knowbaseitems_id_parent' => "Article A1",
            ],
            'expected' => false,
            'with_message' => "This link would create a cycle in the knowledge base tree.",
        ];
        yield "Can't link to indirect parent" => [
            'tree' => $tree,
            'input' => [
                'knowbaseitems_id' => "Article A",
                'knowbaseitems_id_parent' => "Article A1a",
            ],
            'expected' => false,
            'with_message' => "This link would create a cycle in the knowledge base tree.",
        ];
    }

    #[DataProvider('prepareInputForAddProvider')]
    public function testPrepareInputForAdd(
        array $tree,
        array $input,
        bool $expected,
        ?string $with_message = null,
    ): void {
        // Arrange: create the tree and rewrite the input
        $this->createTree($tree);
        $input = $this->replaceNamesInInputByIds($input);

        // Act: try to create the item
        $success = (bool) (new KnowbaseItem_KnowbaseItem())->add($input);

        // Assert: confirm the expected outcome
        $this->assertSame($expected, $success);
        if (!$expected) {
            $this->hasSessionMessageThatContains(htmlescape($with_message), ERROR);
            $this->hasNoSessionMessages([ERROR]);
        }
    }

    public static function prepareInputForUpdateProvider(): iterable
    {
        $tree = [
            'Article A' => [
                'Article A1' => [
                    'Article A1a' => [],
                    'Article A1b' => [],
                ],
                'Article A2' => [],
            ],
            'Article B' => [
                'Article B1' => [],
                'Article B2' => [],
            ],
        ];

        // Valid inputs
        yield "Change article A1 parent" => [
            'tree' => $tree,
            'input' => [
                'id' => 'Article A > Article A1',
                'knowbaseitems_id_parent' => "Article B",
            ],
            'expected' => true,
        ];
        yield "Change article B child" => [
            'tree' => $tree,
            'input' => [
                'id' => 'Article B > Article B1',
                'knowbaseitems_id' => "Article A",
            ],
            'expected' => true,
        ];

        // Invalid values
        yield "Value of knowbaseitems_id is invalid" => [
            'tree' => $tree,
            'input' => [
                'id' => 'Article A > Article A1',
                'knowbaseitems_id' => 99999,
            ],
            'expected' => false,
            'with_message' => "Invalid 'knowbaseitems_id' value: '99999'.",
        ];
        yield "Value of knowbaseitems_id_parent is invalid" => [
            'tree' => $tree,
            'input' => [
                'id' => 'Article A > Article A1',
                'knowbaseitems_id_parent' => 99999,
            ],
            'expected' => false,
            'with_message' => "Invalid 'knowbaseitems_id_parent' value: '99999'.",
        ];

        // Can't link the article to itself
        yield "Link to itself is not allowed" => [
            'tree' => $tree,
            'input' => [
                'id' => 'Article A > Article A1',
                'knowbaseitems_id' => "Article A",
            ],
            'expected' => false,
            'with_message' => "An article cannot be its own parent.",
        ];

        // Cyclic graph prevention
        yield "Can't link to direct parent" => [
            'tree' => $tree,
            'input' => [
                'id' => 'Article A1 > Article A1a',
                'knowbaseitems_id' => "Article A",
            ],
            'expected' => false,
            'with_message' => "This link would create a cycle in the knowledge base tree.",
        ];
    }

    #[DataProvider('prepareInputForUpdateProvider')]
    public function testPrepareInputForUpdate(
        array $tree,
        array $input,
        bool $expected,
        ?string $with_message = null,
    ): void {
        // Arrange: create the tree and rewrite the input
        $this->login();
        $this->createTree($tree);
        $input = $this->replaceNamesInInputByIds($input);

        // Act: try to update the item
        $item = KnowbaseItem_KnowbaseItem::getById(($input['id']));
        $success = (bool) $item->update($input);

        // Assert: confirm the expected outcome
        $this->assertSame($expected, $success);
        if (!$expected) {
            $this->hasSessionMessageThatContains(htmlescape($with_message), ERROR);
            $this->hasNoSessionMessages([ERROR]);
        }
    }

    /**
     * Create the KB articles described by $tree.
     */
    private function createTree(array $tree, int $parent_id = 0): void
    {
        foreach ($tree as $name => $children) {
            $article = $this->createItem(KnowbaseItem::class, [
                'name' => $name,
                'answer' => '',
            ]);

            if ($parent_id > 0) {
                $this->createItem(KnowbaseItem_KnowbaseItem::class, [
                    'knowbaseitems_id'        => $article->getID(),
                    'knowbaseitems_id_parent' => $parent_id,
                ]);
            }

            $this->createTree($children, $article->getID());
        }
    }

    /**
     * Provider data use item names instead of ids as the items haven't been
     * created yet when the provider run.
     *
     * Using this method replace theses temporary names by their matching ids.
     */
    private function replaceNamesInInputByIds(array $input): array
    {
        if (
            isset($input['knowbaseitems_id'])
            && \is_string($input['knowbaseitems_id'])
        ) {
            $input['knowbaseitems_id'] = getItemByTypeName(
                KnowbaseItem::class,
                $input['knowbaseitems_id'],
                true,
            );
        }

        if (
            isset($input['knowbaseitems_id_parent'])
            && \is_string($input['knowbaseitems_id_parent'])
        ) {
            $input['knowbaseitems_id_parent'] = getItemByTypeName(
                KnowbaseItem::class,
                $input['knowbaseitems_id_parent'],
                true,
            );
        }

        if (isset($input['id']) && \is_string($input['id'])) {
            // Format: "<parent name> > <child name>", identifying the link row
            // between these two articles.
            [$parent_name, $child_name] = array_map(
                'trim',
                explode('>', $input['id']),
            );

            $link = new KnowbaseItem_KnowbaseItem();
            $this->assertTrue($link->getFromDBByCrit([
                'knowbaseitems_id' => getItemByTypeName(
                    KnowbaseItem::class,
                    $child_name,
                    true,
                ),
                'knowbaseitems_id_parent' => getItemByTypeName(
                    KnowbaseItem::class,
                    $parent_name,
                    true,
                ),
            ]));
            $input['id'] = $link->getID();
        }

        return $input;
    }
}

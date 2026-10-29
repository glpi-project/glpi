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

namespace tests\units\Glpi\Helpdesk\Tile;

use Glpi\Helpdesk\Tile\ExternalPageTile;
use Glpi\Tests\AbstractTileRightsTest;
use PHPUnit\Framework\Attributes\DataProvider;

final class ExternalPageTileTest extends AbstractTileRightsTest
{
    protected function getTileClass(): string
    {
        return ExternalPageTile::class;
    }

    protected function getTileInput(): array
    {
        return [
            'title'        => 'GLPI project',
            'description'  => 'Link to GLPI project website',
            'illustration' => 'request-service',
            'url'          => 'https://glpi-project.org',
        ];
    }

    public static function tileUrlProvider(): iterable
    {
        // Valid URLs are kept unchanged
        yield 'absolute https URL' => [
            'url'      => 'https://glpi-project.org/path?query=1#anchor',
            'expected' => 'https://glpi-project.org/path?query=1#anchor',
        ];
        yield 'absolute http URL' => [
            'url'      => 'http://glpi-project.org',
            'expected' => 'http://glpi-project.org',
        ];
        yield 'relative URL' => [
            'url'      => '/front/ticket.php',
            'expected' => '/front/ticket.php',
        ];
        yield 'empty URL' => [
            'url'      => '',
            'expected' => '',
        ];

        // Unsafe URLs are removed
        yield 'javascript URL' => [
            'url'      => "javascript:alert('xss')",
            'expected' => '',
        ];
        yield 'javascript URL with mixed case and spaces' => [
            'url'      => " JaVaScRiPt:alert('xss')",
            'expected' => '',
        ];
        yield 'javascript URL with a network path' => [
            'url'      => "javascript://%0aalert('xss')",
            'expected' => '',
        ];
        yield 'data URL' => [
            'url'      => 'data:text/html,<script>alert(1)</script>',
            'expected' => '',
        ];
    }

    #[DataProvider('tileUrlProvider')]
    public function testGetTileUrl(string $url, string $expected): void
    {
        // Arrange: a tile whose URL is stored as is in the database
        $tile = $this->createItem(
            ExternalPageTile::class,
            ['url' => $url] + $this->getTileInput(),
        );

        // Act: reload the tile from the database and get its URL
        $tile = ExternalPageTile::getById($tile->getID());
        $tile_url = $tile->getTileUrl();

        // Assert: only safe URLs are returned
        $this->assertSame($expected, $tile_url);
    }
}

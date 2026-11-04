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

namespace tests\units\Glpi\RichText;

use Glpi\RichText\NoticeRenderer;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class NoticeRendererTest extends GLPITestCase
{
    public static function renderAllProvider(): iterable
    {
        yield 'Info' => [
            'content'  => '<div class="kb-notice kb-notice-info"><p>Text</p></div>',
            'expected' => '<div class="kb-notice kb-notice-info" role="note" aria-label="Info notice">'
                . '<i class="ti ti-info-circle-filled kb-notice-icon kb-notice-icon-info" aria-hidden="true"></i>'
                . '<p>Text</p></div>',
        ];
        yield 'Success' => [
            'content'  => '<div class="kb-notice kb-notice-success"><p>Text</p></div>',
            'expected' => '<div class="kb-notice kb-notice-success" role="note" aria-label="Success notice">'
                . '<i class="ti ti-circle-check-filled kb-notice-icon kb-notice-icon-success" aria-hidden="true"></i>'
                . '<p>Text</p></div>',
        ];
        yield 'Warning' => [
            'content'  => '<div class="kb-notice kb-notice-warning"><p>Text</p></div>',
            'expected' => '<div class="kb-notice kb-notice-warning" role="note" aria-label="Warning notice">'
                . '<i class="ti ti-alert-circle-filled kb-notice-icon kb-notice-icon-warning" aria-hidden="true"></i>'
                . '<p>Text</p></div>',
        ];
        yield 'Tip' => [
            'content'  => '<div class="kb-notice kb-notice-tip"><p>Text</p></div>',
            'expected' => '<div class="kb-notice kb-notice-tip" role="note" aria-label="Tip notice">'
                . '<i class="ti ti-star-filled kb-notice-icon kb-notice-icon-tip" aria-hidden="true"></i>'
                . '<p>Text</p></div>',
        ];
        yield 'Unknown type is left as is' => [
            'content'  => '<div class="kb-notice kb-notice-danger"><p>Text</p></div>',
            'expected' => '<div class="kb-notice kb-notice-danger"><p>Text</p></div>',
        ];
        yield 'Content without notice is left as is' => [
            'content'  => '<p>Text</p>',
            'expected' => '<p>Text</p>',
        ];
    }

    #[DataProvider('renderAllProvider')]
    public function testRenderAll(string $content, string $expected): void
    {
        $this->assertSame($expected, (new NoticeRenderer())->renderAll($content));
    }
}

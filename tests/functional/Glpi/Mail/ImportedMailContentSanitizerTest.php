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

namespace tests\units\Glpi\Mail;

use Glpi\Mail\ImportedMailContentSanitizer;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ImportedMailContentSanitizerTest extends GLPITestCase
{
    public static function contentProvider(): iterable
    {
        yield 'valid UTF-8 is preserved' => [
            'content'          => 'Solicitação válida com ação, ç e emoji 😀',
            'declared_charset' => null,
            'expected'         => 'Solicitação válida com ação, ç e emoji 😀',
            'changed'          => false,
        ];

        yield 'latin1 bytes are converted with declared charset' => [
            'content'          => mb_convert_encoding('Ação não concluída: número ç', 'ISO-8859-1', 'UTF-8'),
            'declared_charset' => 'ISO-8859-1',
            'expected'         => 'Ação não concluída: número ç',
            'changed'          => true,
        ];

        yield 'windows-1252 punctuation is converted' => [
            'content'          => mb_convert_encoding('“aspas”, travessão – e euro €', 'Windows-1252', 'UTF-8'),
            'declared_charset' => 'Windows-1252',
            'expected'         => '“aspas”, travessão – e euro €',
            'changed'          => true,
        ];

        yield 'mojibake is repaired without decoding html entities' => [
            'content'          => '<p>AÃ§Ã£o nÃ£o concluÃ­da &amp; conteÃºdo preservado</p>',
            'declared_charset' => null,
            'expected'         => '<p>Ação não concluída &amp; conteúdo preservado</p>',
            'changed'          => true,
        ];

        yield 'utf-8 bom is stripped' => [
            'content'          => "\xEF\xBB\xBFTexto com BOM",
            'declared_charset' => null,
            'expected'         => 'Texto com BOM',
            'changed'          => true,
        ];

        yield 'invalid control bytes are stripped but line breaks stay' => [
            'content'          => "Linha 1\x00\x07\nLinha 2",
            'declared_charset' => null,
            'expected'         => "Linha 1\nLinha 2",
            'changed'          => true,
        ];

        yield 'replacement mojibake token is normalized' => [
            'content'          => 'Conteúdo ï¿½ parcial',
            'declared_charset' => null,
            'expected'         => 'Conteúdo � parcial',
            'changed'          => true,
        ];
    }

    #[DataProvider('contentProvider')]
    public function testSanitizeImportedMailContent(
        string $content,
        ?string $declared_charset,
        string $expected,
        bool $changed
    ): void {
        $result = (new ImportedMailContentSanitizer())->sanitize($content, $declared_charset);

        $this->assertSame($expected, $result->getContent());
        $this->assertSame($changed, $result->hasChanged());
        $this->assertTrue(mb_check_encoding($result->getContent(), 'UTF-8'));
    }

    public function testInvalidUtf8BytesNeverEscapeSanitizer(): void
    {
        $result = (new ImportedMailContentSanitizer())->sanitize("Prefixo\xC3\x28Sufixo");

        $this->assertTrue(mb_check_encoding($result->getContent(), 'UTF-8'));
        $this->assertTrue($result->hasChanged());
        $this->assertStringContainsString('Prefixo', $result->getContent());
        $this->assertStringContainsString('Sufixo', $result->getContent());
    }
}

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

namespace tests\units\Glpi\Toolbox;

use Glpi\Toolbox\Sanitizer;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test class for src/Glpi/Toolbox/sanitizer.class.php
 */
class SanitizerTest extends \GLPITestCase
{
    public static function rawValueProvider(): iterable
    {
        // Non string values should not be altered
        yield [
            'value'           => null,
            'sanitized_value' => null,
        ];
        yield [
            'value'           => false,
            'sanitized_value' => false,
        ];
        yield [
            'value'           => 15,
            'sanitized_value' => 15,
        ];
        yield [
            'value'           => 0.56,
            'sanitized_value' => 0.56,
        ];
        $tempfile = tmpfile();
        yield [
            'value'           => $tempfile,
            'sanitized_value' => $tempfile,
        ];

        // Strings should be sanitized
        yield [
            'value'             => 'mystring',
            'sanitized_value'   => 'mystring',
            'htmlencoded_value' => 'mystring',
            'dbescaped_value'   => 'mystring',
        ];
        yield [
            'value'             => '5 > 1',
            'sanitized_value'   => '5 &#62; 1',
            'htmlencoded_value' => '5 &#62; 1',
            'dbescaped_value'   => '5 > 1',
        ];
        yield [
            'value'             => '<strong>string</strong>',
            'sanitized_value'   => '&#60;strong&#62;string&#60;/strong&#62;',
            'htmlencoded_value' => '&#60;strong&#62;string&#60;/strong&#62;',
            'dbescaped_value'   => '<strong>string</strong>',
        ];
        yield [
            'value'             => "<strong>text with slashable chars ' \n \"</strong>",
            'sanitized_value'   => "&#60;strong&#62;text with slashable chars \' \\n \\\"&#60;/strong&#62;",
            'htmlencoded_value' => "&#60;strong&#62;text with slashable chars ' \n \"&#60;/strong&#62;",
            'dbescaped_value'   => "<strong>text with slashable chars \' \\n \\\"</strong>",
        ];
        yield [
            'value'             => "text with ending slashable chars '\n\"",
            'sanitized_value'   => "text with ending slashable chars \'\\n\\\"",
            'htmlencoded_value' => "text with ending slashable chars '\n\"",
            'dbescaped_value'   => "text with ending slashable chars \'\\n\\\"",
        ];
        yield [
            'value'             => '<p>HTML containing a code snippet</p><pre>&lt;a href=&quot;/test&quot;&gt;link&lt;/a&gt;</pre>',
            'sanitized_value'   => '&#60;p&#62;HTML containing a code snippet&#60;/p&#62;&#60;pre&#62;&#38;lt;a href=&#38;quot;/test&#38;quot;&#38;gt;link&#38;lt;/a&#38;gt;&#60;/pre&#62;',
            'htmlencoded_value' => '&#60;p&#62;HTML containing a code snippet&#60;/p&#62;&#60;pre&#62;&#38;lt;a href=&#38;quot;/test&#38;quot;&#38;gt;link&#38;lt;/a&#38;gt;&#60;/pre&#62;',
            'dbescaped_value'   => '<p>HTML containing a code snippet</p><pre>&lt;a href=&quot;/test&quot;&gt;link&lt;/a&gt;</pre>',
        ];
        yield [
            'value'             => 'text many backslashes ' . str_repeat('\\', 3), // 3 backslashes
            'sanitized_value'   => 'text many backslashes ' . str_repeat('\\', 6), // escaped to 6 backslashes
            'htmlencoded_value' => 'text many backslashes ' . str_repeat('\\', 3),
            'dbescaped_value'   => 'text many backslashes ' . str_repeat('\\', 6),
        ];

        // Long string with many escapable chars should not be a problem
        $multiplier = 100000;
        yield [
            'value'             => str_repeat("<strong>text with slashable chars ' \n \"</strong>", $multiplier),
            'sanitized_value'   => str_repeat("&#60;strong&#62;text with slashable chars \' \\n \\\"&#60;/strong&#62;", $multiplier),
            'htmlencoded_value' => str_repeat("&#60;strong&#62;text with slashable chars ' \n \"&#60;/strong&#62;", $multiplier),
            'dbescaped_value'   => str_repeat("<strong>text with slashable chars \' \\n \\\"</strong>", $multiplier),
        ];

        // Strings in array should be sanitized
        yield [
            'value'             => [null, '<strong>string</strong>', 3.2, 'string', true, '<p>my</p>', 9798],
            'sanitized_value'   => [null, '&#60;strong&#62;string&#60;/strong&#62;', 3.2, 'string', true, '&#60;p&#62;my&#60;/p&#62;', 9798],
            'htmlencoded_value' => [null, '&#60;strong&#62;string&#60;/strong&#62;', 3.2, 'string', true, '&#60;p&#62;my&#60;/p&#62;', 9798],
            'dbescaped_value'   => [null, '<strong>string</strong>', 3.2, 'string', true, '<p>my</p>', 9798],
        ];
        yield [
            'value'             => [null, "<strong>text with slashable chars ' \n \"</strong>", 3.2, 'string', true, '<p>my</p>', 9798],
            'sanitized_value'   => [null, "&#60;strong&#62;text with slashable chars \' \\n \\\"&#60;/strong&#62;", 3.2, 'string', true, '&#60;p&#62;my&#60;/p&#62;', 9798],
            'htmlencoded_value' => [null, "&#60;strong&#62;text with slashable chars ' \n \"&#60;/strong&#62;", 3.2, 'string', true, '&#60;p&#62;my&#60;/p&#62;', 9798],
            'dbescaped_value'   => [null, "<strong>text with slashable chars \' \\n \\\"</strong>", 3.2, 'string', true, '<p>my</p>', 9798],
        ];

        // Namespaced itemtypes and MassiveAction identifier / "Class::method" callable should not be sanitized
        yield [
            'value'           => 'Glpi\Dashboard\Dashboard',
            'sanitized_value' => 'Glpi\Dashboard\Dashboard',
        ];
        yield [
            'value'           => ['itemtype' => 'Glpi\Dashboard\Dashboard'],
            'sanitized_value' => ['itemtype' => 'Glpi\Dashboard\Dashboard'],
        ];

        // callable syntax should not be sanitized
        yield [
            'value'           => 'Glpi\Socket:update',
            'sanitized_value' => 'Glpi\Socket:update',
        ];
        yield [
            'value'           => 'Glpi\Socket:update\' OR 1 = 1', // invalid syntax, should be sanitized
            'sanitized_value' => 'Glpi\\\Socket:update\\\' OR 1 = 1',
        ];
        yield [
            'value'             => "<strong>text with slashable chars ' \n \"</strong>",
            'sanitized_value'   => "&#60;strong&#62;text with slashable chars ' \n \"&#60;/strong&#62;",
            'htmlencoded_value' => "&#60;strong&#62;text with slashable chars ' \n \"&#60;/strong&#62;",
            'dbescaped_value'   => "<strong>text with slashable chars \' \\n \\\"</strong>",
            'db_escape'         => false,
        ];
    }

    #[DataProvider('rawValueProvider')]
    public function testSanitize(
        $value,
        $sanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null,
        $db_escape = true
    ) {
        $sanitizer = new Sanitizer();
        $this->assertEquals($sanitized_value, @$sanitizer->sanitize($value, $db_escape));

        if ($htmlencoded_value !== null) {
            // Calling `sanitize()` with `$db_escape = false` should produce HTML encoded value
            $this->assertEquals($htmlencoded_value, @$sanitizer->sanitize($value, false));
        }

        // Calling sanitize on sanitized value should have no effect
        $this->assertEquals($sanitized_value, @$sanitizer->sanitize($sanitized_value, $db_escape));
    }

    #[DataProvider('rawValueProvider')]
    public function testEncodeHtmlSpecialChars(
        $value,
        $sanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null,
        $db_escape = true
    ) {
        if (!is_string($htmlencoded_value)) {
            return; // Unrelated entry in provider
        }

        $sanitizer = new Sanitizer();
        $this->assertEquals($htmlencoded_value, @$sanitizer->encodeHtmlSpecialChars($value));

        // Calling encodeHtmlSpecialChars on escaped value should have no effect
        $this->assertEquals($htmlencoded_value, @$sanitizer->encodeHtmlSpecialChars($htmlencoded_value));
    }

    #[DataProvider('rawValueProvider')]
    public function testEncodeHtmlSpecialCharsRecursive(
        $value,
        $sanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null,
        $db_escape = true
    ) {
        if (!is_array($htmlencoded_value)) {
            return; // Unrelated entry in provider
        }

        $sanitizer = new Sanitizer();
        $this->assertEquals($htmlencoded_value, @$sanitizer->encodeHtmlSpecialCharsRecursive($value));

        // Calling encodeHtmlSpecialCharsRecursive on escaped value should have no effect
        $this->assertEquals($htmlencoded_value, @$sanitizer->encodeHtmlSpecialCharsRecursive($htmlencoded_value));
    }

    #[DataProvider('rawValueProvider')]
    public function testDbEscape(
        $value,
        $sanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null,
        $db_escape = true
    ) {
        if (!is_string($dbescaped_value)) {
            return; // Unrelated entry in provider
        }

        $sanitizer = new Sanitizer();
        $this->assertEquals($dbescaped_value, @$sanitizer->dbEscape($value));

        // Calling dbEscape on escaped value should have no effect
        $this->assertEquals($dbescaped_value, @$sanitizer->dbEscape($dbescaped_value));
    }

    #[DataProvider('rawValueProvider')]
    public function testDbEscapeRecursive(
        $value,
        $sanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null,
        $db_escape = true
    ) {
        if (!is_array($dbescaped_value)) {
            return; // Unrelated entry in provider
        }

        $sanitizer = new Sanitizer();
        $this->assertEquals($dbescaped_value, @$sanitizer->dbEscapeRecursive($value));

        // Calling dbEscapeRecursive on escaped value should have no effect
        $this->assertEquals($dbescaped_value, @$sanitizer->dbEscapeRecursive($dbescaped_value));
    }

    public static function sanitizedValueProvider(): iterable
    {
        foreach (self::rawValueProvider() as $data) {
            yield [
                'value'             => $data['sanitized_value'],
                'unsanitized_value' => $data['value'],
                'htmlencoded_value' => $data['htmlencoded_value'] ?? null,
                'dbescaped_value'   => $data['dbescaped_value'] ?? null,
            ];
        }

        // Data produced by old XSS cleaning process
        yield [
            'value'             => '&lt;strong&gt;string&lt;/strong&gt;',
            'unsanitized_value' => '<strong>string</strong>',
        ];
        yield [
            'value'             => [null, '&lt;strong&gt;string&lt;/strong&gt;', 3.2, 'string', true, '&lt;p&gt;my&lt;/p&gt;', 9798],
            'unsanitized_value' => [null, '<strong>string</strong>', 3.2, 'string', true, '<p>my</p>', 9798],
        ];

        // Data misencoded found in some ITIL followups
        yield [
            'value'             => '&lt;img src=&quot;/front/document.send.php?docid=24&amp;tickets_id=12&quot; /&gt; ...',
            'unsanitized_value' => '<img src="/front/document.send.php?docid=24&amp;tickets_id=12" /> ...',
        ];
    }

    #[DataProvider('sanitizedValueProvider')]
    public function testUnanitize(
        $value,
        $unsanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null
    ) {
        $sanitizer = new Sanitizer();
        $this->assertEquals($unsanitized_value, @$sanitizer->unsanitize($value));

        // Calling unsanitize multiple times should not corrupt unsanitized value
        $this->assertEquals($unsanitized_value, @$sanitizer->unsanitize($unsanitized_value));
    }

    #[DataProvider('sanitizedValueProvider')]
    public function testDbUnescape(
        $value,
        $unsanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null
    ) {
        if (!is_string($dbescaped_value)) {
            return; // Unrelated entry in provider
        }

        $sanitizer = new Sanitizer();
        $this->assertEquals($unsanitized_value, @$sanitizer->dbUnescape($dbescaped_value));

        // Calling dbUnescape multiple times should not corrupt value
        $this->assertEquals($unsanitized_value, @$sanitizer->dbUnescape($unsanitized_value));
    }

    #[DataProvider('sanitizedValueProvider')]
    public function testDbUnescapeRecursive(
        $value,
        $unsanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null
    ) {
        if (!is_array($dbescaped_value)) {
            return; // Unrelated entry in provider
        }

        $sanitizer = new Sanitizer();
        $this->assertEquals($unsanitized_value, @$sanitizer->dbUnescapeRecursive($dbescaped_value));

        // Calling dbUnescapeRecursive multiple times should not corrupt value
        $this->assertEquals($unsanitized_value, @$sanitizer->dbUnescapeRecursive($unsanitized_value));
    }

    #[DataProvider('sanitizedValueProvider')]
    public function testDecodeHtmlSpecialChars(
        $value,
        $unsanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null
    ) {
        if (!is_string($htmlencoded_value)) {
            return; // Unrelated entry in provider
        }

        $sanitizer = new Sanitizer();
        $this->assertEquals($unsanitized_value, @$sanitizer->decodeHtmlSpecialChars($htmlencoded_value));

        // Calling decodeHtmlSpecialChars multiple times should not corrupt value
        $this->assertEquals($unsanitized_value, @$sanitizer->decodeHtmlSpecialChars($unsanitized_value));
    }

    #[DataProvider('sanitizedValueProvider')]
    public function testDecodeHtmlSpecialCharsRecursive(
        $value,
        $unsanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null
    ) {
        if (!is_array($htmlencoded_value)) {
            return; // Unrelated entry in provider
        }

        $sanitizer = new Sanitizer();
        $this->assertEquals($unsanitized_value, @$sanitizer->decodeHtmlSpecialCharsRecursive($htmlencoded_value));

        // Calling decodeHtmlSpecialCharsRecursive multiple times should not corrupt value
        $this->assertEquals($unsanitized_value, @$sanitizer->decodeHtmlSpecialCharsRecursive($unsanitized_value));
    }

    public static function isHtmlEncodedValueProvider(): iterable
    {
        yield [
            'value'      => 'mystring',
            'is_encoded' => false,
        ];
        yield [
            'value'      => '5 > 1',
            'is_encoded' => false,
        ];
        yield [
            'value'      => '5 &#62; 1',
            'is_encoded' => true,
        ];
        yield [
            'value'      => '<strong>string</strong>',
            'is_encoded' => false,
        ];
        yield [
            'value'      => '&#60;strong&#62;string&#60;/strong&#62;',
            'is_encoded' => true,
        ];
        yield [
            'value'      => "<strong>text with slashable chars ' \n \"</strong>",
            'is_encoded' => false,
        ];
        yield [
            'value'      => "&#60;strong&#62;text with slashable chars \' \\n \\\"&#60;/strong&#62;",
            'is_encoded' => true,
        ];
    }

    #[DataProvider('isHtmlEncodedValueProvider')]
    public function testIsHtmlEncoded(string $value, bool $is_encoded)
    {
        $sanitizer = new Sanitizer();
        $this->assertSame($is_encoded, @$sanitizer->isHtmlEncoded($value));
    }

    public static function isDbEscapedValueProvider(): iterable
    {
        global $DB;

        // Raw char should not be considered as escaped
        yield [
            'value'      => "\\", // raw string: `[BACKSLASH]`
            'is_escaped' => false,
        ];
        yield [
            'value'      => "'",
            'is_escaped' => false,
        ];
        yield [
            'value'      => '"',
            'is_escaped' => false,
        ];
        yield [
            'value'      => "\n",
            'is_escaped' => false,
        ];

        // Values escaped by $DB should be considered as escaped
        yield [
            'value'      => $DB->escape("\\"), // raw string: `[BACKSLASH]`
            'is_escaped' => true,
        ];
        yield [
            'value'      => $DB->escape("'"),
            'is_escaped' => true,
        ];
        yield [
            'value'      => $DB->escape('"'),
            'is_escaped' => true,
        ];
        yield [
            'value'      => $DB->escape("\n"),
            'is_escaped' => true,
        ];

        // Manually backslashed char should be considered as escaped
        yield [
            'value'      => "\\\\", // raw string: `\[BACKSLASH]`
            'is_escaped' => true,
        ];
        yield [
            'value'      => '\"',
            'is_escaped' => true,
        ];
        yield [
            'value'      => "\'",
            'is_escaped' => true,
        ];
        yield [
            'value'      => "\\n",
            'is_escaped' => true,
        ];

        // 2 x backslashes do not escape quotes/backslashes.
        yield [
            'value'      => "\\\\\\", // raw string: `\[BACKSLASH][BACKSLASH]` (i.e. escaped backslash + unescaped backslash)
            'is_escaped' => false,
        ];
        yield [
            'value'      => "\\\\'", // raw string: `\[BACKSLASH]'` (i.e. escaped backslash + unescaped quote)
            'is_escaped' => false,
        ];
        yield [
            'value'      => '\\\\"', // raw string: `\[BACKSLASH]"` (i.e. escaped backslash + unescaped quote
            'is_escaped' => false,
        ];

        // 3 x backslashes do escape quotes/backslashes (escaped backslash followed by escaped char).
        yield [
            'value'      => "\\\\\\\\", // raw string: `\[BACKSLASH]\[BACKSLASH]` (i.e. escaped backslash + escaped backslash)
            'is_escaped' => true,
        ];
        yield [
            'value'      => "\\\\\\'", // raw string: `\[BACKSLASH]\'` (i.e. escaped backslash + escaped quote)
            'is_escaped' => true,
        ];
        yield [
            'value'      => '\\\\\"', // raw string: `\[BACKSLASH]\"` (i.e. escaped backslash + escaped quote
            'is_escaped' => true,
        ];

        // Control chars already contains a backslash which should not be considered as an escaping backslash.
        yield [
            'value'      => "\\\n", // raw string: `[BACKSLASH][EOL]` (i.e. unescaped backslash + unescaped EOL)
            'is_escaped' => false,
        ];
        yield [
            'value'      => "\\\\n", // raw string: `[BACKSLASH]\[EOL]` (i.e. unescaped backslash + escaped EOL)
            'is_escaped' => false,
        ];
        yield [
            'value'      => "\\\\\\n", // raw string: `\[BACKSLASH]\[EOL]` (i.e. escaped backslash + escaped EOL)
            'is_escaped' => true,
        ];
        yield [
            'value'      => "\\\\\\\\n", // raw string: `\[BACKSLASH][BACKSLASH]\[EOL]` (i.e. escaped backslash + unescaped backslash + escaped EOL)
            'is_escaped' => false,
        ];

        // `a` is not escapable, so preceding backslash has to be considered as an unescaped backslash.
        yield [
            'value'      => "\a", // raw string: `[BACKSLASH]a` (i.e. unescaped backslash + `a`)
            'is_escaped' => false,
        ];
        yield [
            'value'      => "\\a", // raw string: `[BACKSLASH]a` (i.e. unescaped backslash + `a`)
            'is_escaped' => false,
        ];
        yield [
            'value'      => "\\\a", // raw string: `\[BACKSLASH]a` (i.e. escaped backslash + `a`)
            'is_escaped' => true,
        ];
        yield [
            'value'      => "\\\\a", // raw string: `\[BACKSLASH]a` (i.e. escaped backslash + `a`)
            'is_escaped' => true,
        ];
        yield [
            'value'      => "\\\\\a", // raw string: `\[BACKSLASH][BACKSLASH]a` (i.e. escaped backslash + unescaped backslash + `a`)
            'is_escaped' => false,
        ];

        // Check real values
        $txt = <<<TXT
This string contains unexpected chars:
- ' (a quote);
- " (another quote).
It also contains EOL chars.
TXT;
        yield [
            'value'      => $txt,
            'is_escaped' => false,
        ];
        yield [
            'value'      => $DB->escape($txt),
            'is_escaped' => true,
        ];

        // Values with no special chars are never considered as escaped, as escaping process cannot alter them.
        yield [
            'value'      => 'String with no escapable char',
            'is_escaped' => false,
        ];
        yield [
            'value'      => $DB->escape('String with no escapable char'),
            'is_escaped' => false,
        ];
    }

    #[DataProvider('isDbEscapedValueProvider')]
    public function testIsDbEscaped(string $value, bool $is_escaped)
    {
        $sanitizer = new Sanitizer();

        $this->assertSame($is_escaped, @$sanitizer->isDbEscaped($value), $value);
    }

    #[DataProvider('rawValueProvider')]
    public function testSanitizationReversibility(
        $value,
        $sanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null,
        $db_escape = true
    ) {
        $sanitizer = new Sanitizer();

        // Value should stay the same if it has been sanitized then unsanitized
        $this->assertEquals($value, @$sanitizer->unsanitize(@$sanitizer->sanitize($value, $db_escape)));

        // Re-sanitize a value provide the same result as first sanitization
        $this->assertEquals($sanitized_value, @$sanitizer->sanitize(@$sanitizer->unsanitize($value), $db_escape));
    }

    public static function isNsClassOrCallableIdentifierProvider(): iterable
    {
        yield [
            'value'    => 'mystring',
            'is_class' => false,
        ];
        yield [
            'value'    => 'Computer',
            'is_class' => false, // not in a namespace
        ];
        yield [
            'value'    => 'Glpi\Socket',
            'is_class' => true,
        ];
        yield [
            'value'    => 'Glpi\\\\Socket',
            'is_class' => false, // namespace separator are escaped, so it is not considered as a valid classname
        ];
        yield [
            'value'    => 'Glpi\Dashboard\Dashboard$1',
            'is_class' => true, // special format for tab names
        ];
    }

    #[DataProvider('isNsClassOrCallableIdentifierProvider')]
    public function testIsNsClassOrCallableIdentifier(string $value, bool $is_class)
    {
        $sanitizer = new Sanitizer();
        $this->assertSame($is_class, @$sanitizer->isNsClassOrCallableIdentifier($value));
    }

    public static function stringableObjectProvider(): iterable
    {
        yield [
            'value'             => new class ("' > '") {
                private string $val;

                public function __construct(string $val)
                {
                    $this->val = $val;
                }

                public function __toString()
                {
                    return $this->val;
                }
            },
            'sanitized_value'   => "\' &#62; \'",
            'htmlencoded_value' => "' &#62; '",
            'dbescaped_value'   => "\' > \'",
        ];
    }

    #[DataProvider('stringableObjectProvider')]
    public function testSanitizeStringableObject(
        $value,
        $sanitized_value,
        $htmlencoded_value = null,
        $dbescaped_value = null
    ) {
        $sanitizer = new Sanitizer();

        $this->assertEquals($sanitized_value, @$sanitizer->sanitize($value, true));

        // Calling sanitize on sanitized value should have no effect
        $this->assertEquals($sanitized_value, @$sanitizer->sanitize($sanitized_value));

        // Check HTML encoding only
        $this->assertEquals($htmlencoded_value, @$sanitizer->sanitize($value, false));
        $this->assertEquals($htmlencoded_value, @$sanitizer->encodeHtmlSpecialChars($value));
        $this->assertEquals([$htmlencoded_value], @$sanitizer->encodeHtmlSpecialCharsRecursive([$value]));

        // Check escaping only
        $this->assertEquals($dbescaped_value, @$sanitizer->dbEscape($value));
        $this->assertEquals([$dbescaped_value], @$sanitizer->dbEscapeRecursive([$value]));
    }
}

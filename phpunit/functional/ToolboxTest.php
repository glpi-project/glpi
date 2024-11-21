<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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
use Generator;
use Glpi\Api\Deprecated\TicketFollowup;
use Glpi\Features\Clonable;
use Glpi\Features\DCBreadcrumb;
use Glpi\Features\Kanban;
use Glpi\Features\PlanningEvent;
use Glpi\Toolbox\Sanitizer;
use Glpi\Toolbox\VersionParser;
use ITILFollowup;
use stdClass;
use Ticket;
use Psr\Log\LogLevel;

/* Test for inc/toolbox.class.php */

class ToolboxTest extends DbTestCase
{
    public function testGetRandomString()
    {
        for ($len = 20; $len < 50; $len += 5) {
           // Low strength
            $str = \Toolbox::getRandomString($len);
            $this->assertSame($len, strlen($str));
            $this->assertTrue(ctype_alnum($str));
        }
    }

    public static function slugifyProvider()
    {
        return [
            [
                'string'   => 'My - string èé  Ê À ß',
                'expected' => 'my-string-ee-e-a-ss'
            ], [
                //https://github.com/glpi-project/glpi/issues/2946
                'string'   => 'Έρευνα ικανοποίησης - Αιτήματα',
                'expected' => 'ereuna-ikanopoieses-aitemata'
            ], [
                'string'   => 'a-valid-one',
                'expected' => 'a-valid-one',
            ]
        ];
    }

    /**
     * @dataProvider slugifyProvider
     */
    public function testSlugify($string, $expected)
    {
        $this->assertSame($expected, \Toolbox::slugify($string));
    }

    public static function filenameProvider()
    {
        return [
            [
                'name'  => '00-logoteclib.png',
                'expected'  => '00-logoteclib.png',
            ], [
                // Space is missing between "France" and "très" due to a bug in laminas-mail
                'name'  => '01-Screenshot-2018-4-12 Observatoire - Francetrès haut débit.png',
                'expected'  => '01-screenshot-2018-4-12-observatoire-francetres-haut-debit.png',
            ], [
                'name'  => '01-test.JPG',
                'expected'  => '01-test.JPG',
            ], [
                'name'  => '15-image001.png',
                'expected'  => '15-image001.png',
            ], [
                'name'  => '18-blank.gif',
                'expected'  => '18-blank.gif',
            ], [
                'name'  => '19-ʂǷèɕɩɐɫ ȼɦâʁȿ.gif',
                'expected'  => '19-secl-chas.gif',
            ], [
                'name'  => '20-specïal chars.gif',
                'expected'  => '20-special-chars.gif',
            ], [
                'name'  => '24.1-长文件名，将导致内容处置标头中的连续行.txt',
                'expected'  => '24.1-zhang-wen-jian-ming-jiang-dao-zhi-nei-rong-chu-zhi-biao-tou-zhong-de-lian-xu-xing.txt',
            ], [
                'name'  => '24.2-中国字符.txt',
                'expected'  => '24.2-zhong-guo-zi-fu.txt',
            ], [
                'name'  => '25-New Text - Document.txt',
                'expected'  => '25-new-text-document.txt',
            ], [
                'name'     => 'Έρευνα ικανοποίησης - Αιτήματα',
                'expected' => 'ereuna-ikanopoieses-aitemata'
            ], [
                'name'     => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc gravida, nisi vel scelerisque feugiat, tellus purus volutpat justo, vel aliquam nibh nibh sit amet risus. Aenean eget urna et felis molestie elementum nec sit amet magna. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum nec malesuada elit, non luctus mi. Aliquam quis velit justo. Donec id pulvinar nunc. Phasellus.txt',
                'expected' => 'lorem-ipsum-dolor-sit-amet-consectetur-adipiscing-elit.-nunc-gravida-nisi-vel-scelerisque-feugiat-tellus-purus-volutpat-justo-vel-aliquam-.txt'
            ]
        ];
    }

    /**
     * @dataProvider filenameProvider
     */
    public function testFilename($name, $expected)
    {
        $this->assertSame($expected, \Toolbox::filename($name));
        $this->assertLessThanOrEqual(255, strlen($expected));
    }

    public static function dataGetSize()
    {
        return [
            [1,                                  '1 B'],
            [1025,                               '1 KiB'],
            [1100000,                            '1.05 MiB'],
            [1100000000,                         '1.02 GiB'],
            [1100000000000,                      '1 TiB'],
            [1100000000000 * 1024,               '1 PiB'],
            [1100000000000 * 1024 * 1024,        '1 EiB'],
            [1100000000000 * 1024 * 1024 * 1024, '1 ZiB'],
        ];
    }

    /**
     * @dataProvider dataGetSize
     */
    public function testGetSize($input, $expected)
    {
        $this->assertSame($expected, \Toolbox::getSize($input));
    }

    public function testGetIPAddress()
    {
        // Save values
        $saveServer = $_SERVER;

        // Test REMOTE_ADDR
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';
        $ip = \Toolbox::getRemoteIpAddress();
        $this->assertEquals('123.123.123.123', $ip);

        // Restore values
        $_SERVER = $saveServer;
    }

    public function testFormatOutputWebLink()
    {
        $this->assertSame(
            'http://www.glpi-project.org/',
            \Toolbox::formatOutputWebLink('www.glpi-project.org/')
        );
        $this->assertSame(
            'http://www.glpi-project.org/',
            \Toolbox::formatOutputWebLink('http://www.glpi-project.org/')
        );
        $this->assertSame(
            'https://www.glpi-project.org/',
            \Toolbox::formatOutputWebLink('https://www.glpi-project.org/')
        );
    }

    public function testgetBijectiveIndex()
    {
        foreach (
            [
                1   => 'A',
                2   => 'B',
                27  => 'AA',
                28  => 'AB',
                53  => 'BA',
                702 => 'ZZ',
                703 => 'AAA',
            ] as $number => $bij_string
        ) {
            $this->assertSame($bij_string, \Toolbox::getBijectiveIndex($number));
        }
    }

    public static function cleanIntegerProvider()
    {
        return [
            [1, '1'],
            ['1', '1'],
            ['a1', '1'],
            ['-1', '-1'],
            ['-a1', '-1'],
        ];
    }

    /**
     * @dataProvider cleanIntegerProvider
     */
    public function testCleanInteger($value, $expected)
    {
        $this->assertSame($expected, \Toolbox::cleanInteger($value));
    }

    public static function jsonDecodeProvider()
    {
        return [
            [
                '{"Monitor":[6],"Computer":[35]}',
                ['Monitor' => [6], 'Computer' => [35]]
            ], [
                '{\"Monitor\":[\"6\"],\"Computer\":[\"35\"]}',
                ['Monitor' => ["6"], 'Computer' => ["35"]]
            ], [
                '{\"content\":\"&#60;p&#62;HTML !&#60;/p&#62;\"}',
                ['content' => '<p>HTML !</p>']
            ]
        ];
    }

    /**
     * @dataProvider jsonDecodeProvider
     */
    public function testJsonDecode($json, $expected)
    {
        $this->assertSame($expected, \Toolbox::jsonDecode($json, true));
    }


    public static function isJSONProvider()
    {
        return [
            [
                '{"validJson":true}',
                true
            ], [
                '{"invalidJson":true',
                false
            ], [
                '"valid"',
                true
            ], [
                'null',
                true
            ], [
                1000,
                true
            ], [
                [1, 2, 3],
                false
            ], [
                (object) ['json' => true],
                false
            ], [
                '{ bad content',
                false
            ], [
                file_get_contents(GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/computer_1.json'),
                true
            ]
        ];
    }

    /**
     * @dataProvider isJsonProvider
     */
    public function testIsJSON($json, bool $expected)
    {
        $this->assertSame(
            $expected,
            \Toolbox::isJSON($json)
        );
    }



    public function testInvalidJsonDecode()
    {
        $invalid = '"Monitor":"6","Computer":"35"';
        $this->assertSame($invalid, \Toolbox::jsonDecode($invalid, true));
        $this->hasPhpLogRecordThatContains('Unable to decode JSON string! Is this really JSON?', LogLevel::NOTICE);
    }

    public static function ucProvider()
    {
        return [
            ['hello you', 'Hello you'],
            ['HEllO you', 'HEllO you'],
            ['éè', 'Éè'],
            ['ÉÈ', 'ÉÈ']
        ];
    }

    /**
     * @dataProvider ucProvider
     */
    public function testUcfirst($in, $out)
    {
        $this->assertSame($out, \Toolbox::ucfirst($in));
    }

    public static function shortcutProvider()
    {
        return [
            ['My menu', 'm', '<u>M</u>y menu'],
            ['Do something', 't', 'Do some<u>t</u>hing'],
            ['Any menu entry', 'z', 'Any menu entry'],
            ['Computer', 'O', 'C<u>o</u>mputer']
        ];
    }

    /**
     * @dataProvider shortcutProvider
     */
    public function testShortcut($string, $letter, $expected)
    {
        $this->assertSame($expected, \Toolbox::shortcut($string, $letter));
    }

    public static function strposProvider()
    {
        return [
            ['Where is Charlie?', 'W', 0, 0],
            ['Where is Charlie?', 'W', 1, false],
            ['Where is Charlie?', 'w', 0, false],
            ['Where is Charlie?', '?', 0, 16],
            ['Where is Charlie?', '?', 3, 16],
            ['Where is Charlie?', 'e', 0, 2],
            ['Where is Charlie?', 'e', 2, 2],
            ['Where is Charlie?', 'e', 3, 4],
            ['Où est Charlie ?', 'ù', 0, 1]
        ];
    }

    /**
     * @dataProvider strposProvider
     */
    public function testStrpos($string, $search, $offset, $expected)
    {
        $this->assertSame(
            $expected,
            \Toolbox::strpos($string, $search, $offset)
        );
    }

    public static function padProvider()
    {
        return [
            ['GLPI', 10, " ", STR_PAD_RIGHT, 'GLPI      '],
            ['éè', 10, " ", STR_PAD_RIGHT, 'éè        '],
            ['GLPI', 10, " ", STR_PAD_LEFT, '      GLPI'],
            ['éè', 10, " ", STR_PAD_LEFT, '        éè'],
            ['GLPI', 10, " ", STR_PAD_BOTH, '   GLPI   '],
            ['éè', 10, " ", STR_PAD_BOTH, '    éè    '],
            ['123', 10, " ", STR_PAD_BOTH, '   123    ']
        ];
    }

    /**
     * @dataProvider padProvider
     */
    public function testStr_pad($string, $length, $char, $pad, $expected)
    {
        $this->assertSame(
            $expected,
            \Toolbox::str_pad($string, $length, $char, $pad)
        );
    }

    public static function strlenProvider()
    {
        return [
            ['GLPI', 4],
            ['Où ça ?', 7]
        ];
    }

    /**
     * @dataProvider strlenProvider
     */
    public function testStrlen($string, $length)
    {
        $this->assertSame($length, \Toolbox::strlen($string));
    }

    public static function substrProvider()
    {
        return [
            ['I want a substring', 0, -1, 'I want a substring'],
            ['I want a substring', 9, -1, 'substring'],
            ['I want a substring', 9, 3, 'sub'],
            ['Caractères accentués', 0, -1, 'Caractères accentués'],
            ['Caractères accentués', 11, -1, 'accentués'],
            ['Caractères accentués', 11, 8, 'accentué']
        ];
    }

    /**
     * @dataProvider substrProvider
     */
    public function testSubstr($string, $start, $length, $expected)
    {
        $this->assertSame(
            $expected,
            \Toolbox::substr($string, $start, $length)
        );
    }

    public static function lowercaseProvider()
    {
        return [
            ['GLPI', 'glpi'],
            ['ÉÈ', 'éè'],
            ['glpi', 'glpi']
        ];
    }

    /**
     * @dataProvider lowercaseProvider
     */
    public function testStrtolower($upper, $lower)
    {
        $this->assertSame($lower, \Toolbox::strtolower($upper));
    }

    public static function uppercaseProvider()
    {
        return [
            ['glpi', 'GLPI'],
            ['éè', 'ÉÈ'],
            ['GlPI', 'GLPI']
        ];
    }

    /**
     * @dataProvider uppercaseProvider
     */
    public function testStrtoupper($lower, $upper)
    {
        $this->assertSame($upper, \Toolbox::strtoupper($lower));
    }

    public static function utfProvider()
    {
        return [
            ['a simple string', true],
            ['caractère', true],
            [mb_convert_encoding('caractère', 'ISO-8859-15'), false],
            [mb_convert_encoding('simple string', 'ISO-8859-15'), true]
        ];
    }

    /**
     * @dataProvider utfProvider
     */
    public function testSeems_utf8($string, $utf)
    {
        $this->assertSame($utf, \Toolbox::seems_utf8($string));
    }

    public function testSaveAndDeletePicture()
    {
        // Save an image twice
        $test_file = __DIR__ . '/../files/test.png';
        copy(__DIR__ . '/../../pics/add_dropdown.png', $test_file); // saved image will be removed from FS
        $first_pict = \Toolbox::savePicture($test_file);
        $this->assertMatchesRegularExpression('#[^/]+/.+\.png#', $first_pict); // generated random name inside subdir

        copy(__DIR__ . '/../../pics/add_dropdown.png', $test_file); // saved image will be removed from FS
        $second_pict = \Toolbox::savePicture($test_file);
        $this->assertMatchesRegularExpression('#[^/]+/.+\.png#', $second_pict); // generated random name inside subdir

        // Check that second saving of same image is not overriding first saved image.
        $this->assertNotEquals($second_pict, $first_pict);

        // Delete saved images
        $this->assertTrue(\Toolbox::deletePicture($first_pict));
        $this->assertTrue(\Toolbox::deletePicture($second_pict));

        // Save not an image
        $this->assertFalse(\Toolbox::savePicture(__DIR__ . '/../notanimage.jpg'));

        // Save and delete nonexistent files
        $this->assertFalse(\Toolbox::savePicture('notafile.jpg'));
        $this->assertFalse(\Toolbox::deletePicture('notafile.jpg'));
    }

    public static function getPictureUrlProvider()
    {
        global $CFG_GLPI;

        return [
            [
                'path' => '',
                'url'  => null,
            ],
            [
                'path' => 'image.jpg',
                'url'  => $CFG_GLPI['root_doc'] . '/front/document.send.php?file=_pictures/image.jpg',
            ],
            [
                'path' => 'xss\' onclick="alert(\'PWNED\')".jpg',
                'url'  => $CFG_GLPI['root_doc'] . '/front/document.send.php?file=_pictures/xss&apos; onclick=&quot;alert(&apos;PWNED&apos;)&quot;.jpg',
            ],
        ];
    }

    /**
     * @dataProvider getPictureUrlProvider
     */
    public function testGetPictureUrl($path, $url)
    {
        $this->assertSame($url, \Toolbox::getPictureUrl($path));
    }

    /**
     * Data provider for self::testConvertTagToImage().
     */
    public static function convertTagToImageProvider()
    {
        $data = [];

        foreach ([\Computer::class, \Change::class, \Problem::class, \Ticket::class] as $itemtype) {
            $item = new $itemtype();
            $item->fields['id'] = mt_rand(1, 50);

            $img_url = '/front/document.send.php?docid={docid}'; //{docid} to replace by generated doc id
            if ($item instanceof \CommonDBTM) {
                $img_url .= '&itemtype=' . $item->getType();
                $img_url .= '&items_id=' . $item->fields['id'];
            }

            $data[] = [
                'item'         => $item,
                'expected_url' => $img_url,
            ];

            if ($item instanceof \CommonITILObject) {
                $fup = new \ITILFollowup();
                $fup->input['_job'] = $item;
                $data[] = [
                    'item'         => $fup,
                    'expected_url' => $img_url,
                ];

                $solution = new \ITILSolution();
                $solution->input['_job'] = $item;
                $data[] = [
                    'item'         => $solution,
                    'expected_url' => $img_url,
                ];

                $task_itemtype = $itemtype . 'Task';
                $task = new $task_itemtype();
                $task->input['_job'] = $item;
                $data[] = [
                    'item'         => $task,
                    'expected_url' => $img_url,
                ];
            }
        }

        return $data;
    }

    /**
     * Check conversion of tags to images.
     *
     * @dataProvider convertTagToImageProvider
     */
    public function testConvertTagToImage($item, $expected_url)
    {

        $img_tag = uniqid('', true);

       // Create document in DB
        $document = new \Document();
        $doc_id = $document->add([
            'name'     => 'basic document',
            'filename' => 'img.png',
            'mime'     => 'image/png',
            'tag'      => $img_tag,
        ]);
        $this->assertGreaterThan(0, (int)$doc_id);

        $content_text   = '<img id="' . $img_tag . '" width="10" height="10" />';
        $expected_url   = str_replace('{docid}', $doc_id, $expected_url);
        $expected_result = '<a href="' . $expected_url . '" target="_blank" ><img alt="' . $img_tag . '" width="10" src="' . $expected_url . '" /></a>';

        // Processed data is expected to be sanitized, and expected result should remain sanitized
        $this->assertEquals(
            Sanitizer::sanitize($expected_result),
            \Toolbox::convertTagToImage(Sanitizer::sanitize($content_text), $item, [$doc_id => ['tag' => $img_tag]])
        );

        // Processed data may also be escaped using Toolbox::addslashes_deep(), and expected result should be escaped too
        $this->assertEquals(
            \Toolbox::addslashes_deep($expected_result),
            \Toolbox::convertTagToImage(\Toolbox::addslashes_deep($content_text), $item, [$doc_id => ['tag' => $img_tag]])
        );

        // Processed data may also be not sanitized, and expected result should not be sanitized
        $this->assertEquals(
            $expected_result,
            \Toolbox::convertTagToImage($content_text, $item, [$doc_id => ['tag' => $img_tag]])
        );
    }

    /**
     * Data provider for self::testBaseUrlInConvertTagToImage().
     */
    public static function convertTagToImageBaseUrlProvider()
    {
        $item = new \Ticket();
        $item->fields['id'] = mt_rand(1, 50);

        $img_url = '/front/document.send.php?docid={docid}'; //{docid} to replace by generated doc id
        $img_url .= '&itemtype=' . $item->getType();
        $img_url .= '&items_id=' . $item->fields['id'];

        return [
            [
                'url_base'     => 'http://glpi.domain.org',
                'item'         => $item,
                'expected_url' => $img_url,
            ],
            [
                'url_base'     => 'http://www.domain.org/glpi/v9.4/',
                'item'         => $item,
                'expected_url' => '/glpi/v9.4/' . $img_url,
            ],
        ];
    }

    /**
     * Check base url handling in conversion of tags to images.
     *
     * @dataProvider convertTagToImageBaseUrlProvider
     */
    public function testBaseUrlInConvertTagToImage($url_base, $item, $expected_url)
    {

        $img_tag = uniqid('', true);

        // Create document in DB
        $document = new \Document();
        $doc_id = $document->add([
            'name'     => 'basic document',
            'filename' => 'img.png',
            'mime'     => 'image/png',
            'tag'      => $img_tag,
        ]);
        $this->assertGreaterThan(0, (int)$doc_id);

        $content_text   = '<img id="' . $img_tag . '" width="10" height="10" />';
        $expected_url   = str_replace('{docid}', $doc_id, $expected_url);
        $expected_result = '<a href="' . $expected_url . '" target="_blank" ><img alt="' . $img_tag . '" width="10" src="' . $expected_url . '" /></a>';

        // Save old config
        global $CFG_GLPI;
        $old_url_base = $CFG_GLPI['url_base'];

        // Get result
        $CFG_GLPI['url_base'] = $url_base;
        $result = \Toolbox::convertTagToImage($content_text, $item, [$doc_id => ['tag' => $img_tag]]);

        // Restore config
        $CFG_GLPI['url_base'] = $old_url_base;

        // Validate result
        $this->assertEquals($expected_result, $result);
    }

    /**
     * Check conversion of tags to images when contents contains multiple inlined images.
     */
    public function testConvertTagToImageWithMultipleInlinedImg()
    {

        $img_tag_1 = uniqid('', true);
        $img_tag_2 = uniqid('', true);
        $img_tag_3 = uniqid('', true);

        $item = new \Ticket();
        $item->fields['id'] = mt_rand(1, 50);

       // Create multiple documents in DB
        $document = new \Document();
        $doc_id_1 = $document->add([
            'name'     => 'document 1',
            'filename' => 'img1.png',
            'mime'     => 'image/png',
            'tag'      => $img_tag_1,
        ]);
        $this->assertGreaterThan(0, (int)$doc_id_1);

        $document = new \Document();
        $doc_id_2 = $document->add([
            'name'     => 'document 2',
            'filename' => 'img2.png',
            'mime'     => 'image/png',
            'tag'      => $img_tag_2,
        ]);
        $this->assertGreaterThan(0, (int)$doc_id_2);

        $document = new \Document();
        $doc_id_3 = $document->add([
            'name'     => 'document 3',
            'filename' => 'img3.png',
            'mime'     => 'image/png',
            'tag'      => $img_tag_3,
        ]);
        $this->assertGreaterThan(0, (int)$doc_id_3);

        $doc_data = [
            $doc_id_1 => ['tag' => $img_tag_1],
            $doc_id_2 => ['tag' => $img_tag_2],
            $doc_id_3 => ['tag' => $img_tag_3],
        ];

        $content_text    = '';
        $expected_result = '';
        foreach ($doc_data as $doc_id => $doc) {
            $expected_url    = '/front/document.send.php?docid=' . $doc_id;
            $expected_url    .= '&itemtype=' . $item->getType();
            $expected_url    .= '&items_id=' . $item->fields['id'];
            $content_text    .= '<img id="' . $doc['tag'] . '" width="10" height="10" />';
            $expected_result .= '<a href="' . $expected_url . '" target="_blank" ><img alt="' . $doc['tag'] . '" width="10" src="' . $expected_url . '" /></a>';
        }

        // Processed data is expected to be sanitized, and expected result should remain sanitized
        $this->assertEquals(
            Sanitizer::sanitize($expected_result),
            \Toolbox::convertTagToImage(Sanitizer::sanitize($content_text), $item, $doc_data)
        );

        // Processed data may also be escaped using Toolbox::addslashes_deep(), and expected result should be escaped too
        $this->assertEquals(
            \Toolbox::addslashes_deep($expected_result),
            \Toolbox::convertTagToImage(\Toolbox::addslashes_deep($content_text), $item, $doc_data)
        );

        // Processed data may also be not sanitized, and expected result should not be sanitized
        $this->assertEquals(
            $expected_result,
            \Toolbox::convertTagToImage($content_text, $item, $doc_data)
        );
    }

    /**
     * Check conversion of tags to images when multiple document matches same tag.
     */
    public function testConvertTagToImageWithMultipleDocMatchesSameTag()
    {

        $img_tag = uniqid('', true);

        $item = new \Ticket();
        $item->fields['id'] = mt_rand(1, 50);

       // Create multiple documents in DB
        $document = new \Document();
        $doc_id_1 = $document->add([
            'name'     => 'duplicated document 1',
            'filename' => 'img.png',
            'mime'     => 'image/png',
            'tag'      => $img_tag,
        ]);
        $this->assertGreaterThan(0, (int)$doc_id_1);

        $document = new \Document();
        $doc_id_2 = $document->add([
            'name'     => 'duplicated document 2',
            'filename' => 'img.png',
            'mime'     => 'image/png',
            'tag'      => $img_tag,
        ]);
        $this->assertGreaterThan(0, (int)$doc_id_2);

        $content_text    = '<img id="' . $img_tag . '" width="10" height="10" />';
        $expected_url_1    = '/front/document.send.php?docid=' . $doc_id_1;
        $expected_url_1     .= '&itemtype=' . $item->getType();
        $expected_url_1     .= '&items_id=' . $item->fields['id'];
        $expected_result_1 = '<a href="' . $expected_url_1 . '" target="_blank" ><img alt="' . $img_tag . '" width="10" src="' . $expected_url_1 . '" /></a>';
        $expected_url_2    = '/front/document.send.php?docid=' . $doc_id_2;
        $expected_url_2     .= '&itemtype=' . $item->getType();
        $expected_url_2     .= '&items_id=' . $item->fields['id'];
        $expected_result_2 = '<a href="' . $expected_url_2 . '" target="_blank" ><img alt="' . $img_tag . '" width="10" src="' . $expected_url_2 . '" /></a>';


        // Processed data is expected to be sanitized, and expected result should remain sanitized
        $this->assertEquals(
            Sanitizer::sanitize($expected_result_1),
            \Toolbox::convertTagToImage(Sanitizer::sanitize($content_text), $item, [$doc_id_1 => ['tag' => $img_tag]])
        );
        $this->assertEquals(
            Sanitizer::sanitize($expected_result_2),
            \Toolbox::convertTagToImage(Sanitizer::sanitize($content_text), $item, [$doc_id_2 => ['tag' => $img_tag]])
        );

        // Processed data may also be escaped using Toolbox::addslashes_deep(), and expected result should be escaped too
        $this->assertEquals(
            \Toolbox::addslashes_deep($expected_result_1),
            \Toolbox::convertTagToImage(\Toolbox::addslashes_deep($content_text), $item, [$doc_id_1 => ['tag' => $img_tag]])
        );
        $this->assertEquals(
            \Toolbox::addslashes_deep($expected_result_2),
            \Toolbox::convertTagToImage(\Toolbox::addslashes_deep($content_text), $item, [$doc_id_2 => ['tag' => $img_tag]])
        );

        // Processed data may also be not sanitized, and expected result should not be sanitized
        $this->assertEquals(
            $expected_result_1,
            \Toolbox::convertTagToImage($content_text, $item, [$doc_id_1 => ['tag' => $img_tag]])
        );
        $this->assertEquals(
            $expected_result_2,
            \Toolbox::convertTagToImage($content_text, $item, [$doc_id_2 => ['tag' => $img_tag]])
        );
    }

    /**
     * Check conversion of tags to images when content contains multiple times same inlined image.
     */
    public function testConvertTagToImageWithDuplicatedInlinedImg()
    {

        $img_tag = uniqid('', true);

        $item = new \Ticket();
        $item->fields['id'] = mt_rand(1, 50);

       // Create multiple documents in DB
        $document = new \Document();
        $doc_id = $document->add([
            'name'     => 'img 1',
            'filename' => 'img.png',
            'mime'     => 'image/png',
            'tag'      => $img_tag,
        ]);
        $this->assertGreaterThan(0, (int)$doc_id);

        $content_text     = '<img id="' . $img_tag . '" width="10" height="10" />';
        $content_text    .= $content_text;
        $expected_url     = '/front/document.send.php?docid=' . $doc_id;
        $expected_url    .= '&itemtype=' . $item->getType();
        $expected_url    .= '&items_id=' . $item->fields['id'];
        $expected_result  = '<a href="' . $expected_url . '" target="_blank" ><img alt="' . $img_tag . '" width="10" src="' . $expected_url . '" /></a>';
        $expected_result .= $expected_result;

        // Processed data is expected to be sanitized, and expected result should remain sanitized
        $this->assertEquals(
            Sanitizer::sanitize($expected_result),
            \Toolbox::convertTagToImage(Sanitizer::sanitize($content_text), $item, [$doc_id => ['tag' => $img_tag]])
        );

        // Processed data may also be escaped using Toolbox::addslashes_deep(), and expected result should be escaped too
        $this->assertEquals(
            \Toolbox::addslashes_deep($expected_result),
            \Toolbox::convertTagToImage(\Toolbox::addslashes_deep($content_text), $item, [$doc_id => ['tag' => $img_tag]])
        );

        // Processed data may also be not sanitized, and expected result should not be sanitized
        $this->assertEquals(
            $expected_result,
            \Toolbox::convertTagToImage($content_text, $item, [$doc_id => ['tag' => $img_tag]])
        );
    }

    public function testConvertTagToImageAlreadyInLink()
    {
        $img_1_tag = uniqid('', true);
        $img_2_tag = uniqid('', true);

        $item = new \Ticket();
        $item->fields['id'] = mt_rand(1, 50);

        $document_1 = $this->createItem(
            \Document::class,
            [
                'name'     => 'basic document',
                'filename' => 'img.png',
                'mime'     => 'image/png',
                'tag'      => $img_1_tag,
            ]
        );
        $document_2 = $this->createItem(
            \Document::class,
            [
                'name'     => 'another document',
                'filename' => 'img2.png',
                'mime'     => 'image/png',
                'tag'      => $img_2_tag,
            ]
        );

        $expected_url_1 = 'http://localhost/test/1';
        $content_text   = <<<HTML
            Some contents with <a href="http://example.org/">a link</a>
            and a first image <a href="{$expected_url_1}" target="_blank"><img id="{$img_1_tag}" width="10" height="10" /></a> inside a link
            then a second image surrounded by links <a href="http://www.example.org/">link1</a> <img id="{$img_2_tag}" width="10" height="10" /> <a href="http://www.example.org/2">link2</a>
HTML;

        $image_1_src = sprintf(
            '/front/document.send.php?docid=%d&itemtype=%s&items_id=%d',
            $document_1->getID(),
            $item->getType(),
            $item->fields['id']
        );
        $image_2_src = sprintf(
            '/front/document.send.php?docid=%d&itemtype=%s&items_id=%d',
            $document_2->getID(),
            $item->getType(),
            $item->fields['id']
        );
        $expected_result  = <<<HTML
            Some contents with <a href="http://example.org/">a link</a>
            and a first image <a href="{$expected_url_1}" target="_blank"><img alt="{$img_1_tag}" width="10" src="{$image_1_src}" /></a> inside a link
            then a second image surrounded by links <a href="http://www.example.org/">link1</a> <a href="{$image_2_src}" target="_blank" ><img alt="{$img_2_tag}" width="10" src="{$image_2_src}" /></a> <a href="http://www.example.org/2">link2</a>
HTML;

        $docs_data = [
            $document_1->getID() => ['tag' => $img_1_tag],
            $document_2->getID() => ['tag' => $img_2_tag]
        ];

        // Processed data is expected to be sanitized, and expected result should remain sanitized
        $this->assertEquals(
            Sanitizer::sanitize($expected_result),
            \Toolbox::convertTagToImage(Sanitizer::sanitize($content_text), $item, $docs_data)
        );

        // Processed data may also be escaped using Toolbox::addslashes_deep(), and expected result should be escaped too
        $this->assertEquals(
            \Toolbox::addslashes_deep($expected_result),
            \Toolbox::convertTagToImage(\Toolbox::addslashes_deep($content_text), $item, $docs_data)
        );

        // Processed data may also be not sanitized, and expected result should not be sanitized
        $this->assertEquals(
            $expected_result,
            \Toolbox::convertTagToImage($content_text, $item, $docs_data)
        );
    }

    public static function shortenNumbers()
    {
        return [
            [
                'number'    => 1500,
                'precision' => 1,
                'expected'  => '1.5K',
            ], [
                'number'    => 1600,
                'precision' => 0,
                'expected'  => '2K',
            ], [
                'number'    => 1600000,
                'precision' => 1,
                'expected'  => '1.6M',
            ], [
                'number'    => 1660000,
                'precision' => 1,
                'expected'  => '1.7M',
            ], [
                'number'    => 1600000000,
                'precision' => 1,
                'expected'  => '1.6B',
            ], [
                'number'    => 1600000000000,
                'precision' => 1,
                'expected'  => '1.6T',
            ], [
                'number'    => "14%",
                'precision' => 1,
                'expected'  => '14%',
            ], [
                'number'    => "test",
                'precision' => 1,
                'expected'  => 'test',
            ]
        ];
    }

    /**
     * @dataProvider shortenNumbers
     */
    public function testShortenNumber($number, int $precision, string $expected)
    {
        $this->assertEquals(
            $expected,
            \Toolbox::shortenNumber($number, $precision, false)
        );
    }

    public static function colors()
    {
        return [
            [
                'bg_color' => "#FFFFFF",
                'offset'   => 40,
                'fg_color' => '#999999',
            ], [
                'bg_color' => "#FFFFFF",
                'offset'   => 50,
                'fg_color' => '#808080',
            ], [
                'bg_color' => "#000000",
                'offset'   => 40,
                'fg_color' => '#666666',
            ], [
                'bg_color' => "#000000",
                'offset'   => 50,
                'fg_color' => '#808080',
            ], [
                'bg_color' => "rgba(255, 255, 255, 1)",
                'offset'   => 40,
                'fg_color' => '#999999',
            ], [
                'bg_color' => "rgba(255, 255, 255, 1)",
                'offset'   => 50,
                'fg_color' => '#808080',
            ], [
                'bg_color' => "rgba(0, 0, 0, 1)",
                'offset'   => 40,
                'fg_color' => '#666666',
            ], [
                'bg_color' => "rgba(0, 0, 0, 1)",
                'offset'   => 50,
                'fg_color' => '#808080',
            ], [
                'bg_color' => "rgba(0, 0, 0, 0.5)",
                'offset'   => 40,
                'fg_color' => '#666666',
            ], [
                'bg_color' => "rgba(0, 0, 0, 0.5)",
                'offset'   => 50,
                'fg_color' => '#808080',
            ],
        ];
    }

    /**
     * @dataProvider colors
     */
    public function testGetFgColor(string $bg_color, int $offset, string $fg_color)
    {
        $this->assertEquals(
            $fg_color,
            \Toolbox::getFgColor($bg_color, $offset)
        );
    }

    public static function testIsCommonDBTMProvider()
    {
        return [
            [
                'class'         => TicketFollowup::class,
                'is_commondbtm' => false,
            ],
            [
                'class'         => Ticket::class,
                'is_commondbtm' => true,
            ],
            [
                'class'         => ITILFollowup::class,
                'is_commondbtm' => true,
            ],
            [
                'class'         => "Not a real class",
                'is_commondbtm' => false,
            ],
        ];
    }

    /**
     * @dataProvider testIsCommonDBTMProvider
     */
    public function testIsCommonDBTM(string $class, bool $is_commondbtm)
    {
        $this->assertSame(
            $is_commondbtm,
            \Toolbox::isCommonDBTM($class)
        );
    }

    public static function testIsAPIDeprecatedProvider()
    {
        return [
            [
                'class'         => TicketFollowup::class,
                'is_deprecated' => true,
            ],
            [
                'class'         => Ticket::class,
                'is_deprecated' => false,
            ],
            [
                'class'         => ITILFollowup::class,
                'is_deprecated' => false,
            ],
            [
                'class'         => "Not a real class",
                'is_deprecated' => false,
            ],
        ];
    }

    /**
     * @dataProvider testIsAPIDeprecatedProvider
     */
    public function testIsAPIDeprecated(string $class, bool $is_deprecated)
    {
        $this->assertSame(
            $is_deprecated,
            \Toolbox::isAPIDeprecated($class)
        );
    }

    public static function urlProvider()
    {
        return [
            ['http://localhost', true],
            ['https://localhost', true],
            ['https;//localhost', false],
            ['https://glpi-project.org', true],
            [' http://my.host.com', false],
            ['http://my.host.com', true],
            ['http://my.host.com/', true],
            ['http://my.host.com/glpi/', true],
            ['http://my.host.com /', false],
            ['http://localhost:8080', true],
            ['http://localhost:8080/', true],
            ['http://my.host.com:8080/glpi/', true],
            ['http://my.host.com:8080 /', false],
            ['http://my.host.com: 8080/', false],
            ['http://my.host.com :8080/', false],
            ['http://helpdesk.global.glpi-project.org', true],
            ['http://dev.helpdesk.global.glpi-project.org', true],
            ['http://127.0.0.1', true],
            ['http://127.0.0.1/glpi', true],
            ['http://127.0.0.1:8080', true],
            ['http://127.0.0.1:8080/', true],
            ['http://127.0.0.1 :8080/', false],
            ['http://127.0.0.1 :8080 /', false],
            ['http://::1', false], // IPv6 addresses must be in square brackets
            ['http://[::1]', true],
            ['http://[::1]/glpi', true],
            ['http://[::1]:8080/', true],
            ['http://[::1]:8080/', true],
            ['HTTPS://[::1]:8080/', true],
            ['www.my.host.com', false],
            ['127.0.0.1', false],
            ['[::1]', false],
            ['http://my.host.com/subdir/glpi/', true],
            ['http://my.host.com/~subdir/glpi/', true],
            ['https://localhost<', false],
            ['https://localhost"', false],
            ['https://localhost\'', false],
            ['https://localhost?test=true', true],
            ['https://localhost?test=true&othertest=false', true],
            ['https://localhost/front/computer.php?is_deleted=0&as_map=0&criteria[0][link]=AND&criteria[0][field]=80&criteria[0][searchtype]=equals&criteria[0][value]=254&search=Search&itemtype=Computer', true],
            ['https://localhost?test=true&#38;othertest=false', true], /* sanitized URL, &#38; is & */
            ['https://localhost/this+is+a+test', true] // + to denote a space allowed
        ];
    }

    /**
     * @dataProvider urlProvider
     */
    public function testIsValidWebUrl(string $url, bool $result)
    {
        $this->assertSame(
            $result,
            \Toolbox::isValidWebUrl($url),
            $url
        );
    }

    public function testDeprecated()
    {
        \Toolbox::deprecated('Calling this function is deprecated');
        $this->hasPhpLogRecordThatContains(
            'Calling this function is deprecated',
            LogLevel::NOTICE
        );
    }

    public function testDeprecatedPast()
    {
        // Test planned deprecation in the past
        \Toolbox::deprecated('Calling this function is deprecated', true, '10.0');
        $this->hasPhpLogRecordThatContains(
            'Calling this function is deprecated',
            LogLevel::NOTICE
        );
    }

    public function testDeprecatedCurrent()
    {
        // Test planned deprecation in current version
        \Toolbox::deprecated('Calling this function is deprecated', true, GLPI_VERSION);
        $this->hasPhpLogRecordThatContains(
            'Calling this function is deprecated',
            LogLevel::NOTICE
        );
    }

    public function testFutureDeprecated()
    {
        // Test planned deprecation in the future does NOT throw an error
        \Toolbox::deprecated('Calling this function is deprecated', true, '99.0');
        $this->assertTrue(true); //non empty test
    }


    public static function hasTraitProvider()
    {
        return [
            [\Computer::class, Clonable::class, true],
            [\Monitor::class, Clonable::class, true],
            [\CommonITILObject::class, Clonable::class, true],
            [\Ticket::class, Clonable::class, true],
            [\Plugin::class, Clonable::class, false],
            [\Project::class, Kanban::class, true],
            [\Computer::class, Kanban::class, false],
            [\Computer::class, DCBreadcrumb::class, true],
            [\Ticket::class, DCBreadcrumb::class, false],
            [\CommonITILTask::class, PlanningEvent::class, true],
            [\Computer::class, PlanningEvent::class, false],
        ];
    }

    /**
     * @dataProvider hasTraitProvider
     */
    public function testHasTrait(string $class, string $trait, bool $result)
    {
        $this->assertSame($result, \Toolbox::hasTrait($class, $trait));
    }

    public function testGetDocumentsFromTag()
    {
       // No tag provided in the tested text
        $output = \Toolbox::getDocumentsFromTag('');
        $this->AssertCount(0, $output);

       // Create a document to emulate a document upload
        $filename = 'foo.png';
        copy(FIXTURE_DIR . '/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);
        $tag = \Rule::getUuid();
        $input = [
            'filename' => 'foo.png',
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                $tag,
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ]
        ];
        $document = new \Document();
        $document->add($input);
        $this->assertFalse($document->isnewItem());

        $output = \Toolbox::getDocumentsFromTag("foo #$tag# bar ");
        $this->AssertCount(1, $output);
    }

    public static function appendParametersProvider()
    {
        return [
            [
                [
                    'a'   => 'test1',
                    'b'   => 'test2'
                ], '&', 'a=test1&b=test2'
            ],
            [
                [
                    'a'   => [
                        'test1', 'test2'
                    ],
                    'b'   => 'test3'
                ], '&', 'a%5B0%5D=test1&a%5B1%5D=test2&b=test3' // '[' converted to %5B, ']' converted to %5D
            ],
            [
                [
                    'a'   => [
                        'test1', 'test2'
                    ],
                    'b'   => 'test3*'
                ], '&', 'a%5B0%5D=test1&a%5B1%5D=test2&b=test3%2A' // '[' converted to %5B, ']' converted to %5D
            ],
            [
                [
                    'a'   => 'test1',
                    'b'   => 'test2'
                ], '_', 'a=test1_b=test2'
            ],
            [
                [
                    'a'   => [
                        'test1', 'test2'
                    ],
                    'b'   => 'test3'
                ], '_', 'a%5B0%5D=test1_a%5B1%5D=test2_b=test3' // '[' converted to %5B, ']' converted to %5D
            ],
            [
                [
                    'a'   => 'test1',
                    [], // Empty array Should be ignored
                    'b'   => 'test2'
                ], '&', 'a=test1&b=test2'
            ],
        ];
    }

    /**
     * @dataProvider appendParametersProvider
     */
    public function testAppendParameters(array $params, string $separator, string $expected)
    {
        $this->assertEquals($expected, \Toolbox::append_params($params, $separator));
    }

    /**
     * Data provider for testIsFloat
     *
     * @return Generator
     */
    public static function testIsFloatProvider(): Generator
    {
        yield [
            'value'    => null,
            'expected' => false,
        ];

        yield [
            'value'    => "",
            'expected' => false,
        ];

        yield [
            'value' => "1",
            'expected' => false
        ];

        yield [
            'value' => "1.5",
            'expected' => true
        ];

        yield [
            'value' => "7.5569569",
            'expected' => true
        ];

        yield [
            'value' => "0",
            'expected' => false
        ];

        yield [
            'value' => 3.4,
            'expected' => true
        ];

        yield [
            'value' => 3,
            'expected' => false
        ];

        yield [
            'value' => "not a float",
            'expected' => false,
            'warning' => "Calling isFloat on string"
        ];

        yield [
            'value' => new stdClass(),
            'expected' => false,
            'warning' => "Calling isFloat on object"
        ];

        yield [
            'value' => [],
            'expected' => false,
            'warning' => "Calling isFloat on array"
        ];
    }

    /**
     * Tests for Toolbox::IsFloat()
     *
     * @dataProvider testIsFloatProvider
     *
     * @param mixed $value
     * @param bool $expected
     * @param string|null $warning
     *
     * @return void
     */
    public function testIsFloat($value, bool $expected, ?string $warning = null): void
    {
        $result = \Toolbox::isFloat($value);
        $this->assertEquals($expected, $result);
        if (!is_null($warning)) {
            $this->hasPhpLogRecordThatContains(
                $warning,
                LogLevel::WARNING
            );
        }
    }

    /**
     * Data provider for testgetDecimalNumbers
     *
     * @return Generator
     */
    public static function testgetDecimalNumbersProvider(): Generator
    {
        yield [
            'value' => "1",
            'decimals' => 0
        ];

        yield [
            'value' => "1.5",
            'decimals' => 1
        ];

        yield [
            'value' => "7.5569569",
            'decimals' => 7
        ];

        yield [
            'value' => "0",
            'decimals' => 0
        ];

        yield [
            'value' => 3.4,
            'decimals' => 1
        ];

        yield [
            'value' => 3,
            'decimals' => 0
        ];

        yield [
            'value' => "not a float",
            'decimals' => 0,
            'warning' => "Calling getDecimalNumbers on string"
        ];

        yield [
            'value' => new stdClass(),
            'decimals' => 0,
            'warning' => "Calling getDecimalNumbers on object"
        ];

        yield [
            'value' => [],
            'decimals' => 0,
            'warning' => "Calling getDecimalNumbers on array"
        ];

        yield [
            'value' => 3.141592653589791415926535897914159265358979,
            'decimals' => 13 // floatval() round up after 13 decimals
        ];
    }

    /**
     * Tests for Toolbox::getDecimalNumbers()
     *
     * @dataProvider testgetDecimalNumbersProvider
     *
     * @param mixed $value
     * @param int $decimals
     * @param string|null $warning
     *
     * @return void
     */
    public function testGetDecimalNumbers($value, int $decimals, ?string $warning = null): void
    {
        $result = \Toolbox::getDecimalNumbers($value);
        $this->assertEquals($decimals, $result);
        if (!is_null($warning)) {
            $this->hasPhpLogRecordThatContains(
                $warning,
                LogLevel::WARNING
            );
        }
    }

    /**
     * Data provider for testGetMioSizeFromString
     *
     * @return Generator
     */
    public static function testGetMioSizeFromStringProvider(): Generator
    {
        yield [
            'size'     => "1024",
            'expected' => 1024,
        ];

        yield [
            'size'     => "1024 mo",
            'expected' => 1024,
        ];

        yield [
            'size'     => "1024 mio",
            'expected' => 1024,
        ];

        yield [
            'size'     => "1024MO",
            'expected' => 1024,
        ];

        yield [
            'size'     => "2 gio",
            'expected' => 2048,
        ];

        yield [
            'size'     => "2gO",
            'expected' => 2048,
        ];

        yield [
            'size'     => "2 tio",
            'expected' => 2097152,
        ];

        yield [
            'size'     => "2TO",
            'expected' => 2097152,
        ];

        yield [
            'size'     => '>200',
            'expected' => '>200',
        ];

        yield [
            'size'     => '200AA',
            'expected' => '200AA',
        ];

        yield [
            'size'     => '200 AA',
            'expected' => '200 AA',
        ];
    }

    /**
     * Tests for Toolbox::getMioSizeFromString()
     *
     * @dataProvider testGetMioSizeFromStringProvider
     *
     * @param string $value
     * @param mixed $expected
     *
     * @return void
     */
    public function testGetMioSizeFromString(string $size, $expected): void
    {
        $result = \Toolbox::getMioSizeFromString($size);
        $this->assertEquals($expected, $result);
    }

    public static function safeUrlProvider(): iterable
    {
        // Invalid URLs are refused
        yield [
            'url'      => '',
            'expected' => false,
        ];
        yield [
            'url'      => ' ',
            'expected' => false,
        ];

        // Invalid schemes are refused
        yield [
            'url'      => 'file://tmp/test',
            'expected' => false,
        ];
        yield [
            'url'      => 'test://localhost/',
            'expected' => false,
        ];

        // Local file are refused
        yield [
            'url'      => '//tmp/test',
            'expected' => false,
        ];

        // http, https and feed URLs are accepted, unless they contains a user or port information
        foreach (['http', 'https', 'feed'] as $scheme) {
            foreach (['', '/', '/path/to/feed.php'] as $path) {
                yield [
                    'url'      => sprintf('%s://localhost%s', $scheme, $path),
                    'expected' => true,
                ];
                yield [
                    'url'      => sprintf('%s://localhost:8080%s', $scheme, $path),
                    'expected' => false,
                ];
                yield [
                    'url'      => sprintf('%s://test@localhost%s', $scheme, $path),
                    'expected' => false,
                ];
                yield [
                    'url'      => sprintf('%s://test:pass@localhost%s', $scheme, $path),
                    'expected' => false,
                ];
            }
        }

        // Custom allowlist with multiple entries
        $custom_allowlist = [
            '|^https://\w+:[^/]+@calendar.mydomain.tld/|',
            '|//intra.mydomain.tld/|',
        ];
        yield [
            'url'       => 'https://calendar.external.tld/',
            'expected'  => false,
            'allowlist' => $custom_allowlist,
        ];
        yield [
            'url'       => 'https://user:pass@calendar.mydomain.tld/',
            'expected'  => true, // validates first item of allowlist
            'allowlist' => $custom_allowlist,
        ];
        yield [
            'url'       => 'http://intra.mydomain.tld/news.feed.php',
            'expected'  => true, // validates second item of allowlist
            'allowlist' => $custom_allowlist,
        ];
    }


    /**
     * @dataProvider safeUrlProvider
     */
    public function testIsUrlSafe(string $url, bool $expected, ?array $allowlist = null): void
    {
        $params = [$url];
        if ($allowlist !== null) {
            $params[] = $allowlist;
        }
        $this->assertSame($expected, call_user_func_array('Toolbox::isUrlSafe', $params));
    }
}

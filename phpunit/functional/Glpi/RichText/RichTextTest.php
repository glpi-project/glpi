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

namespace tests\units\Glpi\RichText;

use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Form\Tag\Tag;
use Glpi\RichText\RichText;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test class for src/Glpi/RichText/richtext.class.php
 */
class RichTextTest extends \GLPITestCase
{
    public static function getSafeHtmlProvider(): iterable
    {
        // Empty content would not be altered
        yield [
            'content'                => null,
            'encode_output_entities' => false,
            'expected_result'        => '',
        ];
        yield [
            'content'                => '',
            'encode_output_entities' => false,
            'expected_result'        => '',
        ];

        // Handling of encoded result (to be used in textarea for instance)
        yield [
            'content'                => '<p>Some HTML content with special chars like &gt; &amp; &lt;.</p>',
            'encode_output_entities' => true,
            'expected_result'        => '&lt;p&gt;Some HTML content with special chars like &amp;gt; &amp;amp; &amp;lt;.&lt;/p&gt;',
        ];

        // Handling of special chars (<, > and &)
        $xml_sample = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
  <desc><![CDATA[Some CDATA content]]></desc>
  <url>http://www.glpi-project.org/void?test=1&amp;debug=1</url>
</root>
XML;
        $content = '<h1>XML example</h1>' . "\n" . htmlspecialchars($xml_sample);
        $result  = '<h1>XML example</h1>' . "\n" . str_replace(['&', '<', '>', '"', '='], ['&amp;', '&lt;', '&gt;', '&#34;', '&#61;'], $xml_sample);
        yield [
            'content'                => $content,
            'encode_output_entities' => false,
            'expected_result'        => $result,
        ];

        // Handling of plain-text transformation
        yield [
            'content'                => <<<PLAINTEXT
Plain text content created by mailcollector.

1. br should be added for each "\\r?\\n"

2. contained URL are no longer transformed into links:
 - www.glpi-project.org
 - mailto:test@glpi-project.org

PLAINTEXT,
            'encode_output_entities' => false,
            'expected_result'        => <<<HTML
<p>Plain text content created by mailcollector.<br />
<br />
1. br should be added for each &#34;\\r?\\n&#34;<br />
<br />
2. contained URL are no longer transformed into links:<br />
 - www.glpi-project.org<br />
 - mailto:test&#64;glpi-project.org<br />
</p>
HTML,
        ];

        // Cleaning of HTML structure
        yield [
            'content'                => <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>This title should be stripped</title>
</head>
<body>
  <h1>Test</h1>
  
  <style>
    body {
      color: red;
    }
  </style>
  
  <p>Hello world!</p>
  <script>$(function(){ dosomething(); });</script>
</body>
HTML,
            'encode_output_entities' => false,
            'expected_result'        => <<<HTML
  <h1>Test</h1>
  
  <p>Hello world!</p>
HTML,
        ];

        // Unauthorized elements should be cleaned, other should be preserved
        yield [
            'content'                => <<<HTML
<h1>Form element should be removed</h1>
<form action="http://malicious/phishing.php">
  <div>
    <label>e-mail:</label><input type="email" /><br />
    <label>password:</label><input type="password" />
    <input type="hidden" name="test3" value="malicious-input" />
    <button type="submit">OK</button>
    <select name="test1">
        <option value="1">Opt 1</option>
        <option value="2">Opt 2</option>
    </select>
    <textarea name="test2">Some textarea content</textarea>
  </div>
</form>

<h1>Only allowed schemes should be allowed in links</h1>
<p>
  <a href="/test-1">test relative link</a>
  <a href="http://domain.tld/test-2">test HTTP</a>
  <a href="https://domain.tld/test-3">test HTTPS</a>
  <a href="ftp://intranet/path/to/ftp-dir">test FTP</a>
  <a href="notes://notes.local/path/to/document">test Notes</a>

  <a href="hack://powned.domain/phishing">invalid link</a>
</p>

<h1>on* attributes should be removed</h1>
<p>
  <a onclick="steal_cookies()" href="/test">test</a>
  <img onload="steal_cookies()" src="logo.png" alt="test image 1" />
  <img onerror="steal_cookies()" src="/does/not/exists.jpg" alt="test image 2" />
</p>

<h1>Iframes should be removed (by default)</h1>
<iframe src="/path/to/iframe"></iframe>

<h1>Applets, embed and objects should be removed</h1>
<applet code="Main.class"></applet>
<embed src="test.swf" type="application/x-shockwave-flash"></embed>
<object classid="clsid:CAFEEFAC-0015-0000-0000-ABCDEFFEDCBA"><param name="code" value="Main.class"></object>

<h1>Comments and CDATA should be removed</h1>
<p>
  <!-- This is an HTML comment -->
  Legit text
  <![CDATA[Some CDATA]]>
</p>
<P>Uppercase tag will be normalized to lowercase tag</P>

<h1>Legit elements should be preserved</h1>
<h2>All</h2>
<h3>titles</h3>
<h4>levels</h4>
<h5>are</h5>
<h6>allowed</h6>
<p>paragraphs are allowed</p>
<p>
  Legitimate links href are OK:
  <a href="/test">http link</a>,
  <a href="mailto:test@glpi-project.org">mailto link</a>,
  ...
</p>
<ul>
  <li>
    <ul>
      <li>
        <ol>
          <li>Nested</li>
          <li>lists</li>
          <li>are</li>
          <li>allowed</li>
        </ol>
      </li>
    </ul>
  </li>
</ul>
<p>
  img, audio and video are allowed
  <img src="/path/to/img.jpg" alt="img" />
  <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIAQMAAAD+wSzIAAAABlBMVEX///+/v7+jQ3Y5AAAADklEQVQI12P4AIX8EAgALgAD/aNpbtEAAAAASUVORK5CYII" alt="b64 img" />
  <audio src="/path/to/audio.mp3"></audio>
  <video><source src="/path/to/video.mp4" type="video/mp4" /></video>
</p>
<div>
  blockquote and pre are preserved
  <blockquote>
    <p>
      Bla bla<br />
      bla bla
    </p>
  </blockquote>
  <pre>
  public function () {
    alert('Hello world!');
  }
  </pre>
</div>
HTML,
            'encode_output_entities' => false,
            'expected_result'        => <<<HTML
<h1>Form element should be removed</h1>

  <div>
    <label>e-mail:</label><br />
    <label>password:</label>
    
    OK
    
        Opt 1
        Opt 2
    
    Some textarea content
  </div>


<h1>Only allowed schemes should be allowed in links</h1>
<p>
  <a href="/test-1">test relative link</a>
  <a href="http://domain.tld/test-2">test HTTP</a>
  <a href="https://domain.tld/test-3">test HTTPS</a>
  <a href="ftp://intranet/path/to/ftp-dir">test FTP</a>
  <a href="notes://notes.local/path/to/document">test Notes</a>

  <a>invalid link</a>
</p>

<h1>on* attributes should be removed</h1>
<p>
  <a href="/test">test</a>
  <img src="logo.png" alt="test image 1" />
  <img src="/does/not/exists.jpg" alt="test image 2" />
</p>

<h1>Iframes should be removed (by default)</h1>


<h1>Applets, embed and objects should be removed</h1>




<h1>Comments and CDATA should be removed</h1>
<p>
  
  Legit text
  
</p>
<p>Uppercase tag will be normalized to lowercase tag</p>

<h1>Legit elements should be preserved</h1>
<h2>All</h2>
<h3>titles</h3>
<h4>levels</h4>
<h5>are</h5>
<h6>allowed</h6>
<p>paragraphs are allowed</p>
<p>
  Legitimate links href are OK:
  <a href="/test">http link</a>,
  <a href="mailto:test&#64;glpi-project.org">mailto link</a>,
  ...
</p>
<ul>
  <li>
    <ul>
      <li>
        <ol>
          <li>Nested</li>
          <li>lists</li>
          <li>are</li>
          <li>allowed</li>
        </ol>
      </li>
    </ul>
  </li>
</ul>
<p>
  img, audio and video are allowed
  <img src="/path/to/img.jpg" alt="img" />
  <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIAQMAAAD&#43;wSzIAAAABlBMVEX///&#43;/v7&#43;jQ3Y5AAAADklEQVQI12P4AIX8EAgALgAD/aNpbtEAAAAASUVORK5CYII" alt="b64 img" />
  <audio src="/path/to/audio.mp3"></audio>
  <video><source src="/path/to/video.mp4" type="video/mp4" /></video>
</p>
<div>
  blockquote and pre are preserved
  <blockquote>
    <p>
      Bla bla<br />
      bla bla
    </p>
  </blockquote>
  <pre>
  public function () {
    alert(&#039;Hello world!&#039;);
  }
  </pre>
</div>
HTML,
        ];

        // Deprecated html attributes should not be transformed into styles
        // see #11580
        yield [
            'content'                => '<table height=100 width=0 align="left" cellspacing=10 style="width: 100%;"><tr><td>Test</td></tr></table>',
            'encode_output_entities' => false,
            'expected_result'        => '<table height="100" width="0" align="left" cellspacing="10" style="width: 100%;"><tr><td>Test</td></tr></table>',
        ];

        yield 'User mention tag must be preserved' => [
            'content' => <<<HTML
<p>
  Hi <span contenteditable="false" data-user-mention="true" data-user-id="2">@glpi</span>&nbsp;
  ...
</p>
HTML,
            'encode_output_entities' => false,
            'expected_result' => <<<HTML
<p>
  Hi <span contenteditable="false" data-user-mention="true" data-user-id="2">&#64;glpi</span> 
  ...
</p>
HTML,
        ];
        yield 'Do not remove content editable on span' => [
            'content' => '<span contenteditable="true">Editable content</span>',
            'encode_output_entities' => false,
            'expected_result' => '<span contenteditable="true">Editable content</span>',
        ];

        $tag = new Tag(
            label: __("My label"),
            value: 5, // Fake id
            provider: new AnswerTagProvider(),
        );
        yield 'Html content of form tags should not be modified' => [
            'content' => $tag->html,
            'encode_output_entities' => false,
            'expected_result' => $tag->html,
        ];

        yield '`class` attributes should be preserved' => [
            'content'                => '<p class="myclass">Test</p><div class="alert">/!\ Warning !</div>',
            'encode_output_entities' => false,
            'expected_result'        => '<p class="myclass">Test</p><div class="alert">/!\ Warning !</div>',
        ];
    }

    #[DataProvider('getSafeHtmlProvider')]
    public function testGetSafeHtml(
        ?string $content,
        bool $encode_output_entities,
        string $expected_result
    ) {
        $richtext = new RichText();

        $this->assertEquals(
            $expected_result,
            $richtext->getSafeHtml($content, $encode_output_entities)
        );
    }

    public function testGetSafeHtmlDoChangeDocPath()
    {
        global $CFG_GLPI;

        // Images path should be corrected when root doc changed
        // see #15113

        $richtext = new RichText();

        foreach (['', '/glpi', '/path/to/glpi'] as $expected_prefix) {
            $CFG_GLPI['root_doc'] = $expected_prefix;
            foreach (['/previous/glpi/path', '', '/glpi'] as $previous_prefix) {
                $content = <<<HTML
    <p>
      Images path should be corrected when root doc changed:
      <a href="{$previous_prefix}/front/document.send.php?docid=180&amp;itemtype=Ticket&amp;items_id=515" target="_blank">
        <img src="{$previous_prefix}/front/document.send.php?docid=180&amp;itemtype=Ticket&amp;items_id=515" alt="34c09468-b2d8e96f-64f991f5ce1660.58639912" width="248">
      </a>
    </p>
HTML;
                $encode_output_entities = false;
                $expected_result = <<<HTML
    <p>
      Images path should be corrected when root doc changed:
      <a href="{$expected_prefix}/front/document.send.php?docid&#61;180&amp;itemtype&#61;Ticket&amp;items_id&#61;515" target="_blank">
        <img src="{$expected_prefix}/front/document.send.php?docid&#61;180&amp;itemtype&#61;Ticket&amp;items_id&#61;515" alt="34c09468-b2d8e96f-64f991f5ce1660.58639912" width="248" />
      </a>
    </p>
HTML;
                $this->assertEquals(
                    $expected_result,
                    $richtext->getSafeHtml($content, $encode_output_entities)
                );
            }
        }
    }

    public static function getTextFromHtmlProvider(): iterable
    {
        global $CFG_GLPI;

        // Handling of basic content
        yield [
            'content'                => '<p>Some HTML text</p>',
            'keep_presentation'      => false,
            'compact'                => false,
            'encode_output_entities' => false,
            'preserve_case'          => false,
            'preserve_line_breaks'   => false,
            'expected_result'        => 'Some HTML text',
        ];

        // Handling of encoded result (to be used in textarea for instance)
        yield [
            'content'                => '<p>Some HTML content with special chars like &gt; &amp; &lt;.</p>',
            'keep_presentation'      => false,
            'compact'                => false,
            'encode_output_entities' => true,
            'preserve_case'          => false,
            'preserve_line_breaks'   => false,
            'expected_result'        => 'Some HTML content with special chars like &gt; &amp; &lt;.',
        ];

        // Handling of special chars (<, > and &)
        $xml_sample = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
  <desc><![CDATA[Some CDATA content]]></desc>
  <url>http://www.glpi-project.org/void?test=1&amp;debug=1</url>
</root>
XML;
        $content = '<h1>XML example</h1>' . "\n" . htmlspecialchars($xml_sample);
        $result  = <<<PLAINTEXT
XML example <?xml version="1.0" encoding="UTF-8"?> <root> <desc><![CDATA[Some CDATA content]]></desc> <url>http://www.glpi-project.org/void?test=1&amp;debug=1</url> </root>
PLAINTEXT;
        yield [
            'content'                => $content,
            'keep_presentation'      => false,
            'compact'                => false,
            'encode_output_entities' => false,
            'preserve_case'          => false,
            'preserve_line_breaks'   => false,
            'expected_result'        => $result,
        ];

        // Simple text without presentation from complex HTML
        $content = <<<HTML
<h1>A title</h1>
<p>Text in a paragraph</p>
<ul>
  <li>el 1</li>
  <li>el 2</li>
</ul>
<div>
  <a href="/front/computer.form.php?id=150"><img src="/path/to/img" alt="an image" /></a>
  Should I yell <strong>for the important words</strong>?
</div>
HTML;
        yield [
            'content'                => $content,
            'keep_presentation'      => false,
            'compact'                => false,
            'encode_output_entities' => false,
            'preserve_case'          => false,
            'preserve_line_breaks'   => false,
            'expected_result'        => 'A title Text in a paragraph el 1 el 2 Should I yell for the important words?',
        ];
        yield [
            'content'                => $content,
            'keep_presentation'      => false,
            'compact'                => false,
            'encode_output_entities' => false,
            'preserve_case'          => false,
            'preserve_line_breaks'   => true,
            'expected_result'        => <<<PLAINTEXT
A title
Text in a paragraph

el 1
el 2

Should I yell for the important words?
PLAINTEXT,
        ];

        // Text with presentation from complex HTML
        yield [
            'content'                => $content,
            'keep_presentation'      => true,
            'compact'                => false,
            'encode_output_entities' => false,
            'preserve_case'          => false,
            'preserve_line_breaks'   => false,
            'expected_result'        => <<<PLAINTEXT
A TITLE

Text in a paragraph

 	* el 1
 	* el 2

 [an image] [{$CFG_GLPI['url_base']}/front/computer.form.php?id=150] Should I yell FOR THE IMPORTANT WORDS? 
PLAINTEXT,
        ];

        // Text with presentation from complex HTML (compact mode)
        yield [
            'content'                => $content,
            'keep_presentation'      => true,
            'compact'                => true,
            'encode_output_entities' => false,
            'preserve_case'          => false,
            'preserve_line_breaks'   => false,
            'expected_result'        => <<<PLAINTEXT
A TITLE

Text in a paragraph

 	* el 1
 	* el 2

 [an image] Should I yell FOR THE IMPORTANT WORDS? 
PLAINTEXT,
        ];

        // Text with presentation from complex HTML (with no case transformation)
        yield [
            'content'                => $content,
            'keep_presentation'      => true,
            'compact'                => true,
            'encode_output_entities' => false,
            'preserve_case'          => true,
            'preserve_line_breaks'   => false,
            'expected_result'        => <<<PLAINTEXT
A title

Text in a paragraph

 	* el 1
 	* el 2

 [an image] Should I yell for the important words? 
PLAINTEXT,
        ];
    }

    #[DataProvider('getTextFromHtmlProvider')]
    public function testGetTextFromHtml(
        string $content,
        bool $keep_presentation,
        bool $compact,
        bool $encode_output_entities,
        bool $preserve_case,
        bool $preserve_line_breaks,
        string $expected_result
    ) {
        $richtext = new RichText();

        $this->assertEquals(
            $expected_result,
            $richtext->getTextFromHtml(
                $content,
                $keep_presentation,
                $compact,
                $encode_output_entities,
                $preserve_case,
                $preserve_line_breaks
            )
        );
    }

    public static function isRichTextHtmlContentProvider(): iterable
    {
        yield [
            'content'                => <<<PLAINTEXT
This is a plain text content.
It should not be considered as HTML, although it contains < and > chars.
PLAINTEXT,
            'expected_result'        => false,
        ];

        yield [
            'content'                => <<<HTML
<a href="/">GLPI</a>
HTML,
            'expected_result'        => true,
        ];

        yield [
            'content'                => <<<HTML
<p>Some HTML text</p>
HTML,
            'expected_result'        => true,
        ];

        yield [
            'content'                => <<<HTML
<DIV>Uppercase HTML tag</DIV>
HTML,
            'expected_result'        => true,
        ];
    }

    #[DataProvider('isRichTextHtmlContentProvider')]
    public function testIsRichTextHtmlContent(string $content, bool $expected_result)
    {
        $richtext = new RichText();

        $this->assertSame($expected_result, $richtext->isRichTextHtmlContent($content));
    }
}

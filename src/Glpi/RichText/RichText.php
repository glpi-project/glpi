<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\RichText;

use Document;
use Html;
use Html2Text\Html2Text;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

use function Safe\getimagesize;
use function Safe\json_encode;
use function Safe\preg_match;
use function Safe\preg_match_all;
use function Safe\preg_replace;

final class RichText
{
    /**
     * Get safe HTML string based on user input content.
     *
     * @since 10.0.0
     *
     * @param null|string   $content        HTML string to be made safe
     * @param boolean       $encode_output  Indicates whether the output should be encoded (encoding of HTML special chars)
     *
     * @return string
     *
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     */
    public static function getSafeHtml(?string $content, bool $encode_output = false): string
    {

        if (empty($content)) {
            return '';
        }

        $content = self::normalizeHtmlContent($content);

        $content = self::getHtmlSanitizer()->sanitize($content);

        // Remove extra lines
        $content = trim($content, "\r\n");

        if ($encode_output) {
            $content = htmlescape($content);
        }

        return $content;
    }

    /**
     * Get text from HTML string based on user input content.
     *
     * @since 10.0.0
     *
     * @param string  $content                HTML string to be made safe
     * @param boolean $keep_presentation      Indicates whether the presentation elements have to be replaced by plaintext equivalents
     * @param boolean $compact                Indicates whether the output should be compact (limited line length, no links URL, ...)
     * @param boolean $encode_output          Indicates whether the output should be encoded (encoding of HTML special chars)
     * @param boolean $preserve_line_breaks   Indicates whether the line breaks should be preserved
     *
     * @return string
     */
    public static function getTextFromHtml(
        string $content,
        bool $keep_presentation = true,
        bool $compact = false,
        bool $encode_output = false,
        bool $preserve_case = false,
        bool $preserve_line_breaks = false
    ): string {
        global $CFG_GLPI;

        $content = self::normalizeHtmlContent($content);

        if ($keep_presentation) {
            if ($compact) {
                $options = ['do_links' => 'none', 'width' => 0,];
            } else {
                $options = ['width' => 0];

                // Convert domain relative links to absolute links
                $content = preg_replace(
                    '/((?:href|src)=[\'"])(\/[^\/].*)([\'"])/',
                    '$1' . $CFG_GLPI['url_base'] . '$2$3',
                    $content
                );
            }

            $options['preserve_case'] = $preserve_case;

            $html = new class ($content, $options) extends Html2Text {
                protected function toupper($str)
                {
                    if ($this->options['preserve_case'] === true) {
                        return $str;
                    }

                    return parent::toupper($str);
                }
            };
            $content = $html->getText();
        } else {
            // Remove HTML tags
            $content = strip_tags($content);

            if (!$preserve_line_breaks) {
                // Remove multiple whitespace sequences
                $content = preg_replace('/\s+/', ' ', trim($content));
            } else {
                // Remove supernumeraries whitespaces chars but preserve line breaks
                $content = trim($content);
                $content = preg_replace('/[ \t]+/', ' ', $content); // compact horizontal spaces
                $content = preg_replace('/[\r\v\f]/', "\n", $content); // normalize vertical spaces
                $content = preg_replace('/\n +/', "\n", $content); // remove spaces at start of each line

                $content = preg_replace('/\n{3,}/', "\n\n", $content); // compact line breaks to keep only relevant ones
            }

            // Content is no more considered as HTML, decode its entities
            $content = html_entity_decode($content);
        }

        // Remove extra lines
        $content = trim($content, "\r\n");

        if ($encode_output) {
            $content = htmlescape($content);
        }

        return $content;
    }

    /**
     * Check if provided content is rich-text HTML content.
     *
     * @param string $content
     *
     * @return bool
     */
    public static function isRichTextHtmlContent(string $content): bool
    {
        $html_tags = [
            // Most common inlined tag (handle manual HTML input, usefull for $CFG_GLPI['text_login'])
            'a',
            'b',
            'em',
            'i',
            'img',
            'span',
            'strong',

            // Content separators
            'br',
            'hr',

            // Main blocks
            'blockquote',
            'div',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'p',
            'pre',
            'table',
            'ul',
            'ol',
        ];
        return preg_match('/<(' . implode('|', $html_tags) . ')(\s+[^>]*)?>/i', $content) === 1;
    }

    /**
     * Normalize HTML content.
     *
     * @param string $content
     *
     * @return string
     */
    private static function normalizeHtmlContent(string $content)
    {
        if (self::isRichTextHtmlContent($content)) {
            // Remove contentless HTML tags
            // Remove also surrounding spaces:
            // - only horizontal spacing chars leading the tag in its line (\h*),
            // - any spacing char that follow the tag unless they are preceded by a newline (\s*\n+?).
            $leading_spaces = '\h*';
            $following_spaces = '\s*\n+?';
            $content = preg_replace(
                [
                    '/' . $leading_spaces . '<!DOCTYPE[^>]*>' . $following_spaces . '/si',
                    '/' . $leading_spaces . '<head[^>]*>.*?<\/head[^>]*>' . $following_spaces . '/si',
                    '/' . $leading_spaces . '<script[^>]*>.*?<\/script[^>]*>' . $following_spaces . '/si',
                    '/' . $leading_spaces . '<style[^>]*>.*?<\/style[^>]*>' . $following_spaces . '/si',
                ],
                '',
                $content
            );
        } else {
            // If content is not rich text content, convert it to HTML.
            // Required to correctly render content that came:
            // - from "simple text mode" from GLPI prior to 9.4.0;
            // - from a basic textarea;
            // - from an external input (API, CalDAV client, ...).

            if (preg_match('/(<|>)/', $content)) {
                // Input was not HTML, and special chars were not saved as HTML entities.
                // We have to encode them into HTML entities.
                $content = htmlescape($content);
            }

            // Plain text line breaks have to be transformed into <br /> tags.
            $content = '<p>' . nl2br($content) . '</p>';
        }

        $content = self::fixImagesPath($content);

        return $content;
    }

    /**
     * Get enhanced HTML string based on user input content.
     *
     * @since 10.0.0
     *
     * @param null|string   $content HTML string to enahnce
     * @param array         $params  Enhancement parameters
     *
     * @return string
     */
    public static function getEnhancedHtml(?string $content, array $params = []): string
    {
        $p = [
            'images_gallery' => false,
            'user_mentions'  => true,
            'images_lazy'    => true,
            'text_maxsize'   => GLPI_TEXT_MAXSIZE,
        ];
        $p = array_replace($p, $params);

        $content_size = strlen($content ?? '');

        // Sanitize content first (security and to decode HTML entities)
        $content = self::getSafeHtml($content);

        if ($p['user_mentions']) {
            $content = UserMention::refreshUserMentionsHtmlToDisplay($content);
        }

        if ($p['images_lazy']) {
            $content = self::loadImagesLazy($content);
        }

        if ($p['images_gallery']) {
            $content = self::replaceImagesByGallery($content);
        }

        if ($p['text_maxsize'] > 0 && $content_size > $p['text_maxsize']) {
            $content = <<<HTML
<div class="long_text">$content
    <p class='read_more'>
        <span class='read_more_button'>...</span>
    </p>
</div>
HTML;
            $content .= Html::scriptBlock('$(function() { read_more(); });');
        }

        return $content;
    }


    /**
     * Ensure current GLPI URL prefix (`$CFG_GLPI["root_doc"]`) is used in images URLs.
     * It permits to fix path to images that are broken when GLPI URL prefix is changed.
     *
     * @param string $content
     *
     * @return string
     */
    private static function fixImagesPath(string $content): string
    {
        global $CFG_GLPI;

        $patterns = [
            // href attribute, surrounding by " or '
            '/ (href)="[^"]*\/front\/document\.send\.php([^"]+)" /',
            "/ (href)='[^']*\/front\/document\.send\.php([^']+)' /",

            // src attribute, surrounding by " or '
            '/ (src)="(?!data:)[^"]*\/front\/document\.send\.php([^"]+)" /',
            "/ (src)='(?!data:)[^']*\/front\/document\.send\.php([^']+)' /",
        ];

        foreach ($patterns as $pattern) {
            $result = preg_replace(
                $pattern,
                sprintf(' $1="%s/front/document.send.php$2" ', $CFG_GLPI["root_doc"]),
                $content
            );
            if ($result === null) {
                $log_msg = sprintf(
                    '`preg_replace()` with pattern `%s` failed: `%s`.',
                    $pattern,
                    preg_last_error_msg()
                );
                trigger_error($log_msg, E_USER_WARNING);
                return $content;
            }
            $content = $result;
        }

        return $content;
    }


    /**
     * insert `loading="lazy" into img tag
     *
     * @since 10.0.3
     *
     * @param string  $content
     *
     * @return string
     */
    private static function loadImagesLazy(string $content): string
    {
        return preg_replace(
            '/<img([\w\W]+?)\/+>/',
            '<img$1 loading="lazy">',
            $content
        );
    }

    /**
     * Replace images by gallery component in rich text.
     *
     * @since 10.0.0
     *
     * @param string  $content
     *
     * @return string
     */
    private static function replaceImagesByGallery(string $content): string
    {

        $image_matches = [];
        preg_match_all(
            '/<a[^>]*>\s*<img[^>]*src=["\']([^"\']*document\.send\.php\?docid=([0-9]+)(?:&[^"\']+)?)["\'][^>]*>\s*<\/a>/',
            $content,
            $image_matches,
            PREG_SET_ORDER
        );
        foreach ($image_matches as $image_match) {
            $img_tag = $image_match[0];
            $docsrc  = $image_match[1];
            $docid   = $image_match[2];

            // Special chars are encoded in `src` attribute. We decode them to be sure to work with "raw" data.
            $docsrc  = htmlspecialchars_decode($image_match[1], ENT_QUOTES);

            $document = new Document();
            if ($document->getFromDB($docid)) {
                $docpath = GLPI_DOC_DIR . '/' . $document->fields['filepath'];
                if (Document::isImage($docpath)) {
                    //find width / height define by user
                    $width = null;
                    if (preg_match("/width=[\"|'](\d+)(\.\d+)?[\"|']/", $img_tag, $wmatches)) {
                        $width = intval($wmatches[1]);
                    }
                    $height = null;
                    if (preg_match("/height=[\"|'](\d+)(\.\d+)?[\"|']/", $img_tag, $hmatches)) {
                        $height = intval($hmatches[1]);
                    }

                    //find real size from image
                    $imgsize = getimagesize($docpath);

                    $gallery = self::imageGallery([
                        [
                            'src' => $docsrc,
                            'w'   => $imgsize[0],
                            'h'   => $imgsize[1],
                            'thumbnail_w' => $width,
                            'thumbnail_h' => $height,
                        ],
                    ]);
                    $content = str_replace($img_tag, $gallery, $content);
                }
            }
        }

        return $content;
    }


    /**
     * Creates a PhotoSwipe image gallery.
     *
     * @since 10.0.0
     *
     * @param array $imgs  Array of image info
     *                      - src The public path of img
     *                      - w   The width of img
     *                      - h   The height of img
     * @param array $options
     * @return string completed gallery
     */
    private static function imageGallery(array $imgs, array $options = []): string
    {
        $p = [
            'controls' => [
                'close'        => true,
                'zoom'         => true,
            ],
            'rand'               => mt_rand(),
            'gallery_item_class' => '',
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $out = '';
        $out .= "<div class='pswp-img" . htmlescape($p['rand']) . " " . htmlescape($p['gallery_item_class']) . "' itemscope itemtype='http://schema.org/ImageGallery'>";
        foreach ($imgs as $img) {
            if (!isset($img['thumbnail_src'])) {
                $img['thumbnail_src'] = $img['src'];
            }
            $out .= "<figure itemprop='associatedMedia' itemscope itemtype='http://schema.org/ImageObject'>";
            $out .= "<a href='" . htmlescape($img['src']) . "' itemprop='contentUrl' data-index='0'>";
            $width_attr = isset($img['thumbnail_w']) ? "width='" . ((int) $img['thumbnail_w']) . "'" : "";
            $height_attr = isset($img['thumbnail_h']) ? "height='" . ((int) $img['thumbnail_h']) . "'" : "";
            $out .= "<img src='" . htmlescape($img['thumbnail_src']) . "' itemprop='thumbnail' loading='lazy' {$width_attr} {$height_attr}>";
            $out .= "</a>";
            $out .= "</figure>";
        }
        $out .= "</div>";

        $items_json = json_encode($imgs);
        $close_json = json_encode($p['controls']['close'] ?? false);
        $zoom_json  = json_encode($p['controls']['zoom'] ?? false);

        $next_title     = json_encode(__('Next (arrow right)'));
        $prev_title     = json_encode(__('Previous (arrow left)'));
        $close_title    = json_encode(__('Close (Esc)'));
        $download_title = json_encode(__('Download'));
        $zoom_title     = json_encode(__('Zoom in/out'));

        $js = <<<JAVASCRIPT
      (function($) {
         $('.pswp-img{$p['rand']}').on('click', 'figure', function(event) {
            event.preventDefault();

            const options = {
                index: $(this).index(),
                bgOpacity: 0.7,
                dataSource: {$items_json},
                index: $(this).closest('figure').parent().index(),

                close: {$close_json},
                zoom: {$zoom_json},

                arrowNextTitle: {$next_title},
                arrowPrevTitle: {$prev_title},
                closeTitle: {$close_title},
                downloadTitle: {$download_title},
                zoomTitle: {$zoom_title},
            };
            const gallery = new PhotoSwipe(options);
            gallery.on(
               'uiRegister',
               function () {
                  this.ui.registerElement({
                     name: 'download',
                     isButton: true,
                     html: '<a class="text-white" target="_blank" download=""><i class="fa-solid fa-download"></i></a>',
                     order: 8,
                     onInit: (el, pswp) => {
                        pswp.on('change', () => {
                           el.getElementsByTagName('a')[0].href = pswp.currSlide.data.src;
                        });
                     }
                  });
               }
            );
            gallery.init();
        });
      })(jQuery);

JAVASCRIPT;

        $out .= Html::scriptBlock($js);

        return $out;
    }

    private static function getHtmlSanitizer(): HtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowLinkSchemes([
                'aim',
                'app',
                'feed',
                'file',
                'ftp',
                'gopher',
                'http',
                'https',
                'irc',
                'mailto',
                'news',
                'nntp',
                'sftp',
                'ssh',
                'tel',
                'telnet',
                'notes',
            ])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->withMaxInputLength(-1)
        ;

        // Block some elements (tag is removed but contents is preserved)
        $blocked_elements = [
            'html',
            'body',

            // form elements
            'form',
            'button',
            'input',
            'select',
            'datalist',
            'option',
            'optgroup',
            'textarea',
        ];
        foreach ($blocked_elements as $blocked_element) {
            $config = $config->blockElement($blocked_element);
        }

        // Drop some elements (tag and contents are removed)
        $dropped_elements = [
            'head',
            'script',

            // header elements used to link external resources
            'link',
            'meta',

            // elements used to embed potential malicious external application
            'applet',
            'canvas',
            'embed',
            'object',
        ];
        foreach ($dropped_elements as $dropped_element) {
            $config = $config->dropElement($dropped_element);
        }

        // Allow class and style attribute
        $config = $config->allowAttribute('class', '*');
        $config = $config->allowAttribute('style', '*');

        if (GLPI_ALLOW_IFRAME_IN_RICH_TEXT) {
            $config = $config->allowElement('iframe')->dropAttribute('srcdoc', '*');
        }

        // Keep attributes specific to rich text auto completion
        $rich_text_completion_attributes = [
            // required for proper display of autocompleted tags
            'contenteditable',

            // required for user mentions and form tags
            'data-user-mention',
            'data-user-id',
            'data-form-tag',
            'data-form-tag-value',
            'data-form-tag-provider',
        ];
        foreach ($rich_text_completion_attributes as $attribute) {
            $config = $config->allowAttribute($attribute, 'span');
        }

        return new HtmlSanitizer($config);
    }
}

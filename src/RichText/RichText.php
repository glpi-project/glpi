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

namespace Glpi\RichText;

use Document;
use Glpi\Toolbox\Sanitizer;
use Html;
use Html2Text\Html2Text;
use Toolbox;

final class RichText
{
    /**
     * Get safe HTML string based on user input content.
     *
     * @since 10.0.0
     *
     * @param null|string   $content                HTML string to be made safe
     * @param boolean       $encode_output_entities Indicates whether the output should be encoded (encoding of HTML special chars)
     *
     * @return string
     */
    public static function getSafeHtml(?string $content, bool $encode_output_entities = false): string
    {

        if (empty($content)) {
            return '';
        }

        $content = self::normalizeHtmlContent($content, true);

       // Remove unsafe HTML using htmLawed
        $config = Toolbox::getHtmLawedSafeConfig();
        $config['keep_bad'] = 6; // remove invalid/disallowed tag but keep content intact
        $content = htmLawed($content, $config);

       // Special case : remove the 'denied:' for base64 img in case the base64 have characters
       // combinaison introduce false positive
        foreach (['png', 'gif', 'jpg', 'jpeg'] as $imgtype) {
            $content = str_replace(
                sprintf('src="denied:data:image/%s;base64,', $imgtype),
                sprintf('src="data:image/%s;base64,', $imgtype),
                $content
            );
        }

       // Remove extra lines
        $content = trim($content, "\r\n");

        if ($encode_output_entities) {
            $content = Html::entities_deep($content);
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
     * @param boolean $encode_output_entities Indicates whether the output should be encoded (encoding of HTML special chars)
     * @param boolean $preserve_line_breaks   Indicates whether the line breaks should be preserved
     *
     * @return string
     */
    public static function getTextFromHtml(
        string $content,
        bool $keep_presentation = true,
        bool $compact = false,
        bool $encode_output_entities = false,
        bool $preserve_case = false,
        bool $preserve_line_breaks = false
    ): string {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $content = self::normalizeHtmlContent($content, false);

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
           // Remove HTML tags using htmLawed
            $config = Toolbox::getHtmLawedSafeConfig();
            $config['elements'] = 'none';
            $config['keep_bad'] = 6; // remove invalid/disallowed tag but keep content intact
            $content = htmLawed($content, $config);

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
            $content = Html::entity_decode_deep($content);
        }

       // Remove extra lines
        $content = trim($content, "\r\n");

        if ($encode_output_entities) {
            $content = Html::entities_deep($content);
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
     * @param bool   $enhanced_html  Apply optionnal transformations to enhance produced HTML (autolink for instance)
     *
     * @return string
     */
    private static function normalizeHtmlContent(string $content, bool $enhanced_html = false)
    {

        $content = Sanitizer::getVerbatimValue($content);

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
                $content = Html::entities_deep($content);
            }

           // Plain text line breaks have to be transformed into <br /> tags.
            $content = '<p>' . nl2br($content) . '</p>';
        }

        if ($enhanced_html) {
            // URLs have to be transformed into <a> tags.
            /** @var array $autolink_options */
            global $autolink_options;
            $autolink_options['strip_protocols'] = false;
            $content = autolink($content, false, ' target="_blank"');
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
            $content .= HTML::scriptBlock('$(function() { read_more(); });');
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $patterns = [
            // href attribute, surrounding by " or '
            '/ (href)="[^"]*\/front\/document\.send\.php([^"]+)" /',
            "/ (href)='[^']*\/front\/document\.send\.php([^']+)' /",

            // src attribute, surrounding by " or '
            '/ (src)="[^"]*\/front\/document\.send\.php([^"]+)" /',
            "/ (src)='[^']*\/front\/document\.send\.php([^']+)' /",
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace(
                $pattern,
                sprintf(' $1="%s/front/document.send.php$2" ', $CFG_GLPI["root_doc"]),
                $content
            );
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
                        ]
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
                'share'        => true,
                'fullscreen'   => true,
                'zoom'         => true,
            ],
            'rand'               => mt_rand(),
            'gallery_item_class' => ''
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $out = "<div id='psgallery{$p['rand']}' class='pswp' tabindex='-1'
         role='dialog' aria-hidden='true'>";
        $out .= "<div class='pswp__bg'></div>";
        $out .= "<div class='pswp__scroll-wrap'>";
        $out .= "<div class='pswp__container'>";
        $out .= "<div class='pswp__item'></div>";
        $out .= "<div class='pswp__item'></div>";
        $out .= "<div class='pswp__item'></div>";
        $out .= "</div>";
        $out .= "<div class='pswp__ui pswp__ui--hidden'>";
        $out .= "<div class='pswp__top-bar'>";
        $out .= "<div class='pswp__counter'></div>";

        if (isset($p['controls']['close']) && $p['controls']['close']) {
            $out .= "<button class='pswp__button pswp__button--close' title='" . __('Close (Esc)') . "'></button>";
        }

        if (isset($p['controls']['share']) && $p['controls']['share']) {
            $out .= "<button class='pswp__button pswp__button--share' title='" . __('Share') . "'></button>";
        }

        if (isset($p['controls']['fullscreen']) && $p['controls']['fullscreen']) {
            $out .= "<button class='pswp__button pswp__button--fs' title='" . __('Toggle fullscreen') . "'></button>";
        }

        if (isset($p['controls']['zoom']) && $p['controls']['zoom']) {
            $out .= "<button class='pswp__button pswp__button--zoom' title='" . __('Zoom in/out') . "'></button>";
        }

        $out .= "<div class='pswp__preloader'>";
        $out .= "<div class='pswp__preloader__icn'>";
        $out .= "<div class='pswp__preloader__cut'>";
        $out .= "<div class='pswp__preloader__donut'></div>";
        $out .= "</div></div></div></div>";
        $out .= "<div class='pswp__share-modal pswp__share-modal--hidden pswp__single-tap'>";
        $out .= "<div class='pswp__share-tooltip'></div>";
        $out .= "</div>";
        $out .= "<button class='pswp__button pswp__button--arrow--left' title='" . __('Previous (arrow left)') . "'>";
        $out .= "</button>";
        $out .= "<button class='pswp__button pswp__button--arrow--right' title='" . __('Next (arrow right)') . "'>";
        $out .= "</button>";
        $out .= "<div class='pswp__caption'>";
        $out .= "<div class='pswp__caption__center'></div>";
        $out .= "</div></div></div></div>";

        $out .= "<div class='pswp-img{$p['rand']} {$p['gallery_item_class']}' itemscope itemtype='http://schema.org/ImageGallery'>";
        foreach ($imgs as $img) {
            if (!isset($img['thumbnail_src'])) {
                $img['thumbnail_src'] = $img['src'];
            }
            $out .= "<figure itemprop='associatedMedia' itemscope itemtype='http://schema.org/ImageObject'>";
            $out .= "<a href='{$img['src']}' itemprop='contentUrl' data-index='0'>";
            $width_attr = isset($img['thumbnail_w']) ? "width='{$img['thumbnail_w']}'" : "";
            $height_attr = isset($img['thumbnail_h']) ? "height='{$img['thumbnail_h']}'" : "";
            $out .= "<img src='" . htmlspecialchars($img['thumbnail_src'], ENT_QUOTES) . "' itemprop='thumbnail' loading='lazy' {$width_attr} {$height_attr}>";
            $out .= "</a>";
            $out .= "</figure>";
        }
        $out .= "</div>";

        $items_json = json_encode($imgs);
        $dltext = __('Download');
        $js = <<<JAVASCRIPT
      (function($) {
         var pswp = document.getElementById('psgallery{$p['rand']}');

         $('.pswp-img{$p['rand']}').on('click', 'figure', function(event) {
            event.preventDefault();

            var options = {
                index: $(this).index(),
                bgOpacity: 0.7,
                showHideOpacity: true,
                shareButtons: [
                  {id:'download', label:'{$dltext}', url:'{{raw_image_url}}', download:true}
                ]
            }

            var lightBox = new PhotoSwipe(pswp, PhotoSwipeUI_Default, {$items_json}, options);
            lightBox.init();
        });
      })(jQuery);

JAVASCRIPT;

        $out .= Html::scriptBlock($js);

        return $out;
    }
}

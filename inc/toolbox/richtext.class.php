<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Toolbox;

use Html;
use Html2Text\Html2Text;
use Toolbox;

class RichText {

   /**
    * Get safe HTML string based on user input content.
    *
    * @since 10.0.0
    *
    * @param string  $content                HTML string to be made safe
    * @param boolean $sanitized_input        Indicates whether the input has been transformed by GLPI sanitize process
    * @param boolean $encode_output_entities Indicates whether the output should be encoded (encoding of HTML special chars)
    *
    * @return string
    */
   public static function getSafeHtml(string $content, bool $sanitized_input = false, bool $encode_output_entities = false): string {

      if ($sanitized_input) {
         $content = Toolbox::unclean_cross_side_scripting_deep($content);
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
    * @param boolean $sanitized_input        Indicates whether the input has been transformed by GLPI sanitize process
    * @param boolean $encode_output_entities Indicates whether the output should be encoded (encoding of HTML special chars)
    *
    * @return string
    */
   public static function getTextFromHtml(
      string $content, bool $keep_presentation = true, bool $sanitized_input = false, bool $encode_output_entities = false
   ): string {
      global $CFG_GLPI;

      if ($sanitized_input) {
         $content = Toolbox::unclean_cross_side_scripting_deep($content);
      }

      $content = self::normalizeHtmlContent($content, false);

      if ($keep_presentation) {
         // Convert domain relative links to absolute links
         $content = preg_replace(
            '/((?:href|src)=[\'"])(\/[^\/].*)([\'"])/',
            '$1' . $CFG_GLPI['url_base'] . '$2$3',
            $content
         );

         $html = new Html2Text($content, ['width' => 0]);
         $content = $html->getText();
      } else {
         // Remove HTML tags using htmLawed
         $config = Toolbox::getHtmLawedSafeConfig();
         $config['elements'] = 'none';
         $config['keep_bad'] = 6; // remove invalid/disallowed tag but keep content intact
         $content = htmLawed($content, $config);

         // Remove supernumeraries whitespaces chars
         $content = preg_replace('/\s+/', ' ', trim($content));

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
   public static function isRichTextHtmlContent(string $content): bool {
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
      ];
      return preg_match('/<(' . implode('|', $html_tags) . ')(\s+[^>]*)?>/', $content) === 1;
   }

   /**
    * Normalize HTML content.
    *
    * @param string $content
    * @param bool   $enhanced_html  Apply optionnal transformations to enhance produced HTML (autolink for instance)
    *
    * @return string
    */
   private static function normalizeHtmlContent(string $content, bool $enhanced_html = false) {
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

         if ($enhanced_html) {
            // URLs have to be transformed into <a> tags.
            $content = autolink($content, false);
         }
      }

      return $content;
   }
}

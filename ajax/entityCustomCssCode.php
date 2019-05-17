<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], 'entityCustomCssCode.php')) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

$entity = new Entity();
$entity->getFromDB($_POST['entities_id']);

if (isset($_POST['enable_custom_css']) && isset($_POST['entities_id'])) {
   $enable_custom_css = $_POST['enable_custom_css'] == '1';

   if (Entity::CONFIG_PARENT == $_POST['enable_custom_css']) {
      $custom_css_code = Entity::getUsedConfig(
         'enable_custom_css',
         $entity->fields['entities_id'],
         'custom_css_code'
      );
   } else {
      $custom_css_code = $entity->fields['custom_css_code'];
   }

   $rand = mt_rand();

   echo '<textarea id="custom_css_code_'. $rand . '" name="custom_css_code" ';
   if (!$enable_custom_css) {
      echo 'disabled';
   }
   echo '>';
   echo Html::entities_deep($custom_css_code);
   echo '</textarea>';

   $editor_options = [
      'mode'               => 'text/css',
      'lineNumbers'        => true,

      // Autocomplete with CTRL+SPACE
      'extraKeys'          => [
         'Ctrl-Space' => 'autocomplete',
      ],

      // Code folding configuration
      'foldGutter' => true,
      'gutters'    => [
         'CodeMirror-linenumbers',
         'CodeMirror-foldgutter'
      ],
   ];
   if (!$enable_custom_css) {
      $editor_options['readOnly'] = 'nocursor'; // readonly and no type cursor if input disabled
   }

   echo Html::scriptBlock('
      $(function() {
         var textarea = document.getElementById("custom_css_code_' . $rand . '");
         var editor = CodeMirror.fromTextArea(textarea, ' . json_encode($editor_options) . ');

         // Fix bad display of gutter (see https://github.com/codemirror/CodeMirror/issues/3098 )
         setTimeout(function () {editor.refresh();}, 10);

         if (' . (!$enable_custom_css ? 'true' : 'false') . ') {
            $(textarea).siblings(".CodeMirror").addClass("input-disabled");
         }
      });
   ');
}

<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], 'entityCustomCssCode.php')) {
    $AJAX_INCLUDE = 1;
    include('../inc/includes.php');
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
            'custom_css_code',
            ''
        );
    } else {
        $custom_css_code = $entity->fields['custom_css_code'];
    }

    $rand = mt_rand();

    echo '<textarea id="custom_css_code_' . $rand . '" name="custom_css_code" ';
    if (!$enable_custom_css) {
        echo 'disabled';
    }
    echo '>';
    echo Html::entities_deep($custom_css_code);
    echo '</textarea>';

    echo Html::scriptBlock(<<<JAVASCRIPT
        $(function() {
            const textarea = document.getElementById("custom_css_code_{$rand}");
            const editor = new CodeMirror.EditorView(
                {
                    extensions: [
                        CodeMirror.setup,
                        CodeMirror.languages.css(),
                        CodeMirror.EditorView.contentAttributes.of({contenteditable: !textarea.disabled}),
                        CodeMirror.EditorView.lineWrapping,
                    ],
                    doc: textarea.value,
                }
            );
            textarea.parentNode.insertBefore(editor.dom, textarea.nextSibling);
            textarea.form.addEventListener(
                'submit',
                function () {
                    textarea.value = editor.state.doc.toString();
                }
            );
        });
JAVASCRIPT
    );
}

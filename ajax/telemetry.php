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

use Glpi\Application\View\TemplateRenderer;

use function Safe\json_encode;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!($_SESSION['telemetry_from_install'] ?? false)) {
    Session::checkRight("config", READ);
    $hide_sensitive_data = false;
} else {
    $hide_sensitive_data = true;
}

echo Html::css("lib/monaco.css");

$twig_params = [
    'info' => json_encode(Telemetry::getTelemetryInfos($hide_sensitive_data), JSON_PRETTY_PRINT),
    'description' => __("We only collect the following data: plugins usage, performance and responsiveness statistics about user interface features, memory, and hardware configuration."),
];
// language=Twig
echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
    <p>{{ description }}</p>
    <div id='telemetry-preview' style="height: 400px"></div>
    <script type="module">
        import('/js/modules/Monaco/MonacoEditor.js').then(() => {
            window.GLPI.Monaco.createEditor('telemetry-preview', 'javascript', `{{ info|escape('js') }}`, [], {
                readOnly: true,
                minimap: {
                    enabled: false
                },
                automaticLayout: true
            });
        });
    </script>
TWIG, $twig_params);

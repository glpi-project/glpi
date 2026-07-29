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

use Glpi\Application\View\TemplateRenderer;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRightsOr(OAuthApplication::$rightname, [CREATE, UPDATE]);

$provider = $_REQUEST['provider'] ?? '';
$item_id  = (int) ($_REQUEST['item_id'] ?? 0);

if ($provider !== OAuthApplication::AZURE) {
    return;
}

$item = new OAuthApplication();
$tenant_id = ($item_id > 0 && $item->getFromDB($item_id)) ? ($item->fields['tenant_id'] ?? '') : '';

// language=twig
echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
    {% import 'components/form/fields_macros.html.twig' as fields %}
    {{ fields.textField(
        'tenant_id',
        tenant_id,
        __('Tenant ID'),
        {
            helper: __('Required for Microsoft Azure (directory/tenant ID)'),
            required: true,
            field_class: '',
        }
    ) }}
TWIG, ['tenant_id' => $tenant_id]);

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

use Glpi\Application\View\TemplateRenderer;

class WebhookTest extends CommonGLPI
{
    public static $rightname         = 'config';


    public static function getTypeName($nb = 0)
    {
        return _n('Webhook test', 'Webhooks test', $nb);
    }


    public static function canCreate()
    {
        return true;
    }


    public function showForm($id, array $options = [])
    {
        $webhook = new Webhook();
        TemplateRenderer::getInstance()->display('pages/setup/webhook/webhooktest.html.twig', [
            'webhook' => $webhook
        ]);
        return true;
    }


    public static function getMenuContent()
    {
        $menu = [];
        if (Webhook::canView()) {
            $menu = [
                'title'    => _n('Webhook', 'Webhooks', Session::getPluralNumber()),
                'page'     => '/front/webhook.php',
                'icon'     => static::getIcon(),
            ];
            $menu['links']['search'] = '/front/webhook.php';
            $menu['links']['add'] = '/front/webhook.form.php';

            $mp_icon     = WebhookTest::getIcon();
            $mp_title    = WebhookTest::getTypeName();
            $webhook_test = "<i class='$mp_icon pointer' title='$mp_title'></i><span class='d-none d-xxl-block'>$mp_title</span>";
            $menu['links'][$webhook_test] = '/front/webhooktest.php';
        }
        if (count($menu)) {
            return $menu;
        }
        return [];
    }


    public static function getIcon()
    {
        return "ti ti-webhook";
    }
}

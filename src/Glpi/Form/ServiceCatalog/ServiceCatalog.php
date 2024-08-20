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

namespace Glpi\Form\ServiceCatalog;

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Form;
use Override;

final class ServiceCatalog extends CommonGLPI
{
    #[Override]
    public static function getTypeName($nb = 0)
    {
        return __("Service catalog");
    }

    public static function getIcon()
    {
        return "ti ti-notes";
    }

    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // This tab is only available for forms
        if (!($item instanceof Form)) {
            return "";
        }

        return self::createTabEntry(self::getTypeName());
    }

    #[Override]
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        // This tab is only available for forms
        if (!($item instanceof Form)) {
            return false;
        }

        $twig = TemplateRenderer::getInstance();
        echo $twig->render('pages/admin/form/service_catalog_tab.html.twig', [
            'form' => $item,
            'icon' => self::getIcon(),
        ]);

        return true;
    }
}

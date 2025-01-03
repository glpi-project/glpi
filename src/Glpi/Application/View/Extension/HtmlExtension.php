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

namespace Glpi\Application\View\Extension;

use Html;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HtmlExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('html_clean_id', Html::cleanId(...)),
            new TwigFunction('html_display_title', Html::displayTitle(...)),
            new TwigFunction('html_file', Html::file(...)),
            new TwigFunction('html_init_editor_system', Html::initEditorSystem(...)),
            new TwigFunction('html_js_ajax_dropdown', Html::jsAjaxDropdown(...)),
            new TwigFunction('html_parse_attributes', Html::parseAttributes(...)),
            new TwigFunction('html_print_ajax_pager', Html::printAjaxPager(...)),
            new TwigFunction('html_progress', Html::progress(...)),

            new TwigFunction('html_header', Html::header(...)),
            new TwigFunction('html_help_header', Html::helpHeader(...)),
            new TwigFunction('html_pop_header', Html::popHeader(...)),
            new TwigFunction('html_simple_header', Html::simpleHeader(...)),
            new TwigFunction('html_null_header', Html::nullHeader(...)),

            new TwigFunction('html_footer', Html::footer(...)),
            new TwigFunction('html_pop_footer', Html::popFooter(...)),

            new TwigFunction('html_show_checkbox', Html::showCheckbox(...)),
            new TwigFunction('html_show_date_time_field', Html::showDateTimeField(...)),
            new TwigFunction('html_show_massive_action_checkbox', Html::showMassiveActionCheckBox(...)),
            new TwigFunction('html_show_massive_actions', Html::showMassiveActions(...)),
            new TwigFunction('html_show_simple_form', Html::showSimpleForm(...)),
            new TwigFunction('html_show_tooltip', Html::showToolTip(...)),
        ];
    }
}

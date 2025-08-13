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

namespace Glpi\Application\View\Extension;

use CommonDBTM;
use Glpi\Form\FormTranslation;
use Glpi\Helpdesk\HelpdeskTranslation;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Locale;
use Session;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class I18nExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('__', '__'),
            new TwigFunction('_n', '_n'),
            new TwigFunction('_x', '_x'),
            new TwigFunction('_nx', '_nx'),
            new TwigFunction('get_current_locale', [$this, 'getCurrentLocale']),
            new TwigFunction('get_plural_number', [Session::class, 'getPluralNumber']),
            new TwigFunction('translate_form_item_key', $this->translateFormItemKey(...)),
            new TwigFunction('translate_helpdesk_item_key', $this->translateHelpdeskItemKey(...)),
        ];
    }

    public function getCurrentLocale(): array
    {
        return Locale::parseLocale($_SESSION['glpilanguage'] ?? 'en_GB');
    }

    public function translateFormItemKey(
        CommonDBTM&ProvideTranslationsInterface $item,
        string $key,
        int $count = 1
    ): ?string {
        return FormTranslation::translate($item, $key, $count);
    }

    public function translateHelpdeskItemKey(
        CommonDBTM&ProvideTranslationsInterface $item,
        string $key,
        int $count = 1
    ): ?string {
        return HelpdeskTranslation::translate($item, $key, $count);
    }
}

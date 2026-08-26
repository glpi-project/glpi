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

namespace Twig\Components\Alert;

abstract class AbstractAlert
{
    /**
     * Var is exposed in the template, for the backward compatibility.
     * So type can be used with {{ component('Alert', {color: 'warning') }}
     *
     * @var 'primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark'
     */
    public string $color = 'info';

    public ?string $heading = null;

    public string $content = '';

    public bool $dismissible = false;

    public string $icon = '';

    public bool $important = false;

    public ?string $link_url = null;

    public ?string $link_text = null;

    public bool $link_blank = false;

    public function getClasses(): string
    {
        $classes = ['alert', 'alert-' . $this->color];

        if ($this->dismissible) {
            $classes = [...$classes, 'alert-dismissible', 'fade', 'show'];
        }

        if ($this->important) {
            $classes[] = 'alert-important';
        }

        return implode(' ', $classes);
    }

    public function getResolvedIcon(): string
    {
        if ($this->icon !== '') {
            return $this->icon;
        }

        return match ($this->color) {
            'success' => 'ti ti-check',
            'warning' => 'ti ti-alert-triangle',
            'danger' => 'ti ti-exclamation-circle',
            default => 'ti ti-info-circle',
        };
    }
}

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

namespace Glpi\RichText;

use function Safe\preg_replace_callback;

/**
 * Adds the icon and the accessible label to the KB notices at render time,
 * so that the label follows the reader's language.
 */
final class NoticeRenderer
{
    public function renderAll(string $sanitized_html): string
    {
        if (!str_contains($sanitized_html, 'kb-notice')) {
            return $sanitized_html;
        }

        $types = $this->getTypes();

        return preg_replace_callback(
            '/<div class="kb-notice kb-notice-(' . implode('|', array_keys($types)) . ')">/',
            fn(array $matches): string => sprintf(
                '<div class="kb-notice kb-notice-%1$s" role="note" aria-label="%2$s">'
                . '<i class="ti %3$s kb-notice-icon kb-notice-icon-%1$s" aria-hidden="true"></i>',
                $matches[1],
                htmlescape($types[$matches[1]]['label']),
                $types[$matches[1]]['icon']
            ),
            $sanitized_html
        );
    }

    /**
     * @return array<string, array{label: string, icon: string}>
     */
    private function getTypes(): array
    {
        return [
            'info'    => ['label' => __('Info notice'), 'icon' => 'ti-info-circle-filled'],
            'success' => ['label' => __('Success notice'), 'icon' => 'ti-circle-check-filled'],
            'warning' => ['label' => __('Warning notice'), 'icon' => 'ti-alert-circle-filled'],
            'tip'     => ['label' => __('Tip notice'), 'icon' => 'ti-star-filled'],
        ];
    }
}

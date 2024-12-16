<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\UI;

use Glpi\Application\View\TemplateRenderer;

final class IllustrationManager
{
    public string $icons_definition_file;
    public string $icons_sprites_path;

    public function __construct(
        ?string $icons_definition_file = null,
        ?string $icons_sprites_path = null,
    ) {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->icons_definition_file = $icons_definition_file ?? GLPI_ROOT
            . '/public/lib/glpi-project/illustrations/icons.json'
        ;
        $this->icons_sprites_path = $icons_sprites_path ?? '/lib/glpi-project/illustrations/glpi-illustrations.svg'
        ;
    }

    /**
     * @param int|null $size Height and width (px). Will be set to 100% if null.
     */
    public function renderIcon(string $icon_id, ?int $size = null): string
    {
        $twig = TemplateRenderer::getInstance();
        return $twig->render('components/illustration/icon.svg.twig', [
            'file_path' => $this->icons_sprites_path,
            'icon_id'   => $icon_id,
            'size'      => $this->computeSize($size),
        ]);
    }

    /** @return string[] */
    public function getAllIconsIds(): array
    {
        $json = file_get_contents($this->icons_definition_file);
        $definition = json_decode($json, associative: true);

        return array_keys($definition);
    }

    private function computeSize(?int $size = null): string
    {
        if ($size === null) {
            return "100%";
        }

        return $size . "px";
    }
}

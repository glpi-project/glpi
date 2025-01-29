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

namespace Glpi\UI;

use Glpi\Application\View\TemplateRenderer;
use RuntimeException;

final class IllustrationManager
{
    private string $icons_definition_file;
    private string $icons_sprites_path;
    private string $scenes_gradient_sprites_path;
    private ?array $icons_definitions = null;

    public const DEFAULT_ILLUSTRATION = "request-service";

    public function __construct(
        ?string $icons_definition_file = null,
        ?string $icons_sprites_path = null,
        ?string $scenes_gradient_sprites_path = null,
    ) {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->icons_definition_file = $icons_definition_file ?? GLPI_ROOT
            . '/public/lib/glpi-project/illustrations/icons.json'
        ;
        $this->icons_sprites_path = $icons_sprites_path ??
            '/lib/glpi-project/illustrations/glpi-illustrations-icons.svg'
        ;
        $this->scenes_gradient_sprites_path = $scenes_gradient_sprites_path ??
            '/lib/glpi-project/illustrations/glpi-illustrations-scenes-gradient.svg'
        ;

        $this->checkIconFile($this->icons_definition_file);
        $this->checkIconFile(GLPI_ROOT . "/public/$this->scenes_gradient_sprites_path");
        $this->checkIconFile(GLPI_ROOT . "/public/$this->icons_sprites_path");
    }

    /**
     * @param int|null $size Height and width (px). Will be set to 100% if null.
     */
    public function renderIcon(string $icon_id, ?int $size = null): string
    {
        $icons = $this->getIconsDefinitions();
        $twig = TemplateRenderer::getInstance();
        return $twig->render('components/illustration/icon.svg.twig', [
            'file_path' => $this->icons_sprites_path,
            'icon_id'   => $icon_id,
            'size'      => $this->computeSize($size),
            'title'     => $icons[$icon_id]['title'] ?? "",
        ]);
    }

    /**
     * @param int|null $size Height and width (px). Will be set to 100% if null.
     */
    public function renderScene(string $icon_id, ?int $size = null): string
    {
        $twig = TemplateRenderer::getInstance();
        return $twig->render('components/illustration/icon.svg.twig', [
            'file_path' => $this->scenes_gradient_sprites_path,
            'icon_id'   => $icon_id,
            'size'      => $this->computeSize($size),
        ]);
    }

    /** @return string[] */
    public function getAllIconsIds(): array
    {
        return array_keys($this->getIconsDefinitions());
    }

    public function countIcons(string $filter = ""): int
    {
        if ($filter == "") {
            return count($this->getIconsDefinitions());
        }

        $icons = array_filter(
            $this->getIconsDefinitions(),
            fn ($icon) => str_contains(
                strtolower($icon['title']),
                strtolower($filter),
            )
        );

        return count($icons);
    }

    /** @return string[] */
    public function searchIcons(
        string $filter = "",
        int $page = 1,
        int $page_size = 30,
    ): array {
        $icons = array_filter(
            $this->getIconsDefinitions(),
            fn ($icon) => str_contains(
                strtolower($icon['title']),
                strtolower($filter),
            )
        );

        $icons = array_slice(
            array: $icons,
            offset: ($page - 1) * $page_size,
            length: $page_size,
        );

        return array_keys($icons);
    }

    private function getIconsDefinitions(): array
    {
        if ($this->icons_definitions === null) {
            $json = file_get_contents($this->icons_definition_file);
            if ($json === false) {
                throw new \RuntimeException();
            }
            $this->icons_definitions = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);
        }

        return $this->icons_definitions;
    }

    private function computeSize(?int $size = null): string
    {
        if ($size === null) {
            return "100%";
        }

        return $size . "px";
    }

    private function checkIconFile(string $file): void
    {
        if (!is_readable($file)) {
            throw new RuntimeException("Failed to read file: $file");
        }
    }
}

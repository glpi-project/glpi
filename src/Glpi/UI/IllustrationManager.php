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
    private ?array $icons_definitions = null;

    public const DEFAULT_ILLUSTRATION = "request-service";

    /**
     * Dummy file that is generated by the `php bin/console tools:generate_illustration_translations` command.
     * Its only role is to contains the title of each icons so that the `vendor/bin/extract-locales` script can extract
     * them.
     */
    public const TRANSLATION_FILE = GLPI_ROOT . '/resources/.illustrations_translations.php';

    private const CUSTOM_ILLUSTRATION_DIR = GLPI_PICTURE_DIR . "/illustrations";

    public function __construct(
        ?string $icons_definition_file = null,
        ?string $icons_sprites_path = null,
    ) {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->icons_definition_file = $icons_definition_file ?? GLPI_ROOT
            . '/public/lib/glpi-project/illustrations/icons.json'
        ;
        $this->icons_sprites_path = $icons_sprites_path ?? '/lib/glpi-project/illustrations/glpi-illustrations-icons.svg'
        ;

        $this->checkIconFile($this->icons_definition_file);
        $this->checkIconFile(GLPI_ROOT . "/public/$this->icons_sprites_path");
        $this->validateOrInitCustomIllustrationDir();
    }

    /**
     * @param int|null $size Height and width (px). Will be set to 100% if null.
     */
    public function renderIcon(string $icon_id, ?int $size = null): string
    {
        $custom_icon_prefix = "file://";
        if (str_starts_with($icon_id, $custom_icon_prefix)) {
            return $this->renderCustomIcon(
                substr($icon_id, strlen($custom_icon_prefix)),
                $size
            );
        } else {
            return $this->renderNativeIcon($icon_id, $size);
        }
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
                strtolower(_x("Icon", $icon['title'])),
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

    public function getAllIconsTitles(): array
    {
        $icons = $this->getIconsDefinitions();
        $titles = [];
        foreach ($icons as $icon) {
            $titles[] = $icon['title'];
        }

        return $titles;
    }

    public function saveCustomIllustration(string $id, string $path): void
    {
        if (!rename($path, self::CUSTOM_ILLUSTRATION_DIR . "/$id")) {
            throw new RuntimeException();
        }
    }

    public function getCustomIllustrationFile(string $id): ?string
    {
        $file_path = realpath(self::CUSTOM_ILLUSTRATION_DIR . "/$id");
        $custom_dir_path = realpath(self::CUSTOM_ILLUSTRATION_DIR);

        if (
            // Make sure $id is not maliciously reading from others directories
            !str_starts_with($file_path, $custom_dir_path)
            || !file_exists($file_path)
        ) {
            return null;
        }

        return $file_path;
    }

    private function validateOrInitCustomIllustrationDir(): void
    {
        if (
            !file_exists(self::CUSTOM_ILLUSTRATION_DIR)
            && !mkdir(self::CUSTOM_ILLUSTRATION_DIR)
        ) {
            throw new RuntimeException();
        }
    }

    private function renderNativeIcon(string $icon_id, ?int $size = null): string
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

    private function renderCustomIcon(string $icon_id, ?int $size = null): string
    {
        $twig = TemplateRenderer::getInstance();
        return $twig->render('components/illustration/custom_icon.html.twig', [
            'url'   => "/UI/Illustration/CustomIllustration/$icon_id",
            'size'  => $this->computeSize($size),
        ]);
    }

    private function getIconsDefinitions(): array
    {
        if ($this->icons_definitions === null) {
            $json = file_get_contents($this->icons_definition_file);
            if ($json === false) {
                throw new RuntimeException();
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

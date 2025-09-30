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

namespace Glpi\Helpdesk\Tile;

use CommonDBTM;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Glpi\ItemTranslation\Context\TranslationHandler;
use Glpi\Session\SessionInfo;
use Glpi\UI\IllustrationManager;
use Override;

final class ExternalPageTile extends CommonDBTM implements TileInterface, ProvideTranslationsInterface
{
    public static $rightname = 'config';

    public const TRANSLATION_KEY_TITLE = 'title';
    public const TRANSLATION_KEY_DESCRIPTION = 'description';

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }

    #[Override]
    public function getLabel(): string
    {
        return __("External page");
    }

    #[Override]
    public static function canCreate(): bool
    {
        return self::canUpdate();
    }

    #[Override]
    public static function canPurge(): bool
    {
        return self::canUpdate();
    }

    #[Override]
    public function getTitle(): string
    {
        return $this->fields['title'] ?? "";
    }

    #[Override]
    public function getDescription(): string
    {
        return $this->fields['description'] ?? "";
    }

    #[Override]
    public function getIllustration(): string
    {
        return $this->fields['illustration'] ?? IllustrationManager::DEFAULT_ILLUSTRATION;
    }

    #[Override]
    public function getTileUrl(): string
    {
        return $this->fields['url'] ?? "";
    }

    #[Override]
    public function isAvailable(SessionInfo $session_info): bool
    {
        return true;
    }

    #[Override]
    public function getDatabaseId(): int
    {
        return $this->fields['id'];
    }

    #[Override]
    public function getConfigFieldsTemplate(): string
    {
        return "pages/admin/external_page_tile_config_fields.html.twig";
    }

    #[Override]
    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Item_Tile::class,
            ]
        );
    }

    #[Override]
    public function listTranslationsHandlers(): array
    {
        $handlers = [];
        $key = sprintf('%s_%d', self::getType(), $this->getID());
        $category_name = sprintf('%s: %s', $this->getLabel(), $this->fields['title'] ?? NOT_AVAILABLE);
        if (!empty($this->getTitle())) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_TITLE,
                name: __('Title'),
                value: $this->getTitle(),
                is_rich_text: false,
                category: $category_name
            );
        }
        if (!empty($this->getDescription())) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_DESCRIPTION,
                name: __('Description'),
                value: $this->getDescription(),
                is_rich_text: true,
                category: $category_name
            );
        }

        return $handlers;
    }
}

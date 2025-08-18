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

namespace GlpiPlugin\Tester\Form;

use CommonDBTM;
use Glpi\Helpdesk\Tile\Item_Tile;
use Glpi\Helpdesk\Tile\TileInterface;
use Glpi\Session\SessionInfo;
use Glpi\UI\IllustrationManager;
use Override;

final class CustomTile extends CommonDBTM implements TileInterface
{
    public static $rightname = 'config';

    #[Override]
    public function getWeight(): int
    {
        return 40;
    }

    #[Override]
    public function getLabel(): string
    {
        return __("Custom tile");
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
        return "My custom title";
    }

    #[Override]
    public function getDescription(): string
    {
        return "My custom description";
    }

    #[Override]
    public function getIllustration(): string
    {
        return IllustrationManager::DEFAULT_ILLUSTRATION;
    }

    #[Override]
    public function getTileUrl(): string
    {
        return "https://my-custom-url.com";
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
        return "@tester/custom_tile.html.twig";
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
}

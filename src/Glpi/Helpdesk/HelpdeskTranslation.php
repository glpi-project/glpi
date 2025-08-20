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

namespace Glpi\Helpdesk;

use CommonGLPI;
use Config;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Helpdesk\Tile\TilesManager;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Glpi\ItemTranslation\ItemTranslation;
use Override;

final class HelpdeskTranslation extends ItemTranslation implements ProvideTranslationsInterface
{
    public static $rightname = 'form';

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n('Helpdesk translation', 'Helpdesk translations', $nb);
    }

    #[Override]
    public static function getIcon()
    {
        return "ti ti-language";
    }

    #[Override]
    public static function getTable($classname = null)
    {
        if (is_a($classname ?? self::class, ItemTranslation::class, true)) {
            return parent::getTable(ItemTranslation::class);
        }
        return parent::getTable($classname);
    }

    #[Override]
    public function getName($options = []): string
    {
        return Dropdown::getLanguageName($this->fields['language']);
    }

    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof Config) {
            $translations = array_reduce(
                self::getTranslationsForHelpdesk(),
                fn($carry, $translation) => $carry + [$translation->fields['language'] => $translation],
                []
            );

            return self::createTabEntry(
                self::getTypeName(),
                count($translations)
            );
        }

        return '';
    }

    #[Override]
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        if ($item instanceof Config) {
            $translations = array_reduce(
                self::getTranslationsForHelpdesk(),
                fn($carry, $translation) => $carry + [$translation->fields['language'] => $translation],
                []
            );
            $available_languages = self::getLanguagesCanBeAddedToTranslation();
            TemplateRenderer::getInstance()->display('pages/admin/helpdesk_home_translations.html.twig', [
                'item'                => new self(),
                'translations'        => $translations,
                'available_languages' => $available_languages,
            ]);

            return true;
        }

        return false;
    }

    #[Override]
    public function listTranslationsHandlers(): array
    {
        $tiles_manager = TilesManager::getInstance();
        $entities = array_map(
            fn($entity_id) => Entity::getById($entity_id),
            array_keys((new Entity())->find())
        );
        $entities_handlers = array_map(
            fn($entity) => $entity->listTranslationsHandlers(),
            $entities
        );

        $tiles = $tiles_manager->getAllTiles();
        $tiles = array_filter($tiles, fn($tile) => $tile instanceof ProvideTranslationsInterface);
        $tiles_handlers = array_map(fn(ProvideTranslationsInterface $tile) => $tile->listTranslationsHandlers(), $tiles);

        return array_merge(...$entities_handlers, ...$tiles_handlers);
    }

    public static function getTranslationsForHelpdesk(): array
    {
        $tiles_manager = TilesManager::getInstance();
        $entities = array_map(
            fn($entity_id) => Entity::getById($entity_id),
            array_keys((new Entity())->find())
        );

        return array_merge(
            ...array_map(
                fn($entity) => self::getTranslationsForItem($entity),
                $entities
            ),
            ...array_map(
                fn($item) => self::getTranslationsForItem($item),
                $tiles_manager->getAllTiles()
            )
        );
    }

    /**
     * Get remaining languages that can be added to a helpdesk translation
     *
     * @return array<string, string> List of languages (code => name)
     */
    public static function getLanguagesCanBeAddedToTranslation(): array
    {
        $helpdesk_translations = array_map(
            fn(ItemTranslation $translation) => $translation->fields['language'],
            self::getTranslationsForHelpdesk()
        );

        return array_combine(
            array_diff(array_keys(Dropdown::getLanguages()), $helpdesk_translations),
            array_map(
                fn($language) => Dropdown::getLanguageName($language),
                array_diff(array_keys(Dropdown::getLanguages()), $helpdesk_translations)
            )
        );
    }

    #[Override]
    protected function getTranslationsHandlersForStats(): array
    {
        return $this->listTranslationsHandlers();
    }

    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        return [
            'itemtype' => array_map(static fn($tile) => $tile::class, (TilesManager::getInstance())->getTileTypes()),
        ];
    }
}

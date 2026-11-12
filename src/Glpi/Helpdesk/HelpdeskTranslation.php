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

namespace Glpi\Helpdesk;

use CommonGLPI;
use Config;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Helpdesk\Tile\TilesManager;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Glpi\ItemTranslation\Context\TranslationHandler;
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
        global $DB;

        $tiles_manager = TilesManager::getInstance();

        // Only entities with their own helpdesk title provide translation handlers
        $entities_iterator = $DB->request([
            'FROM'  => Entity::getTable(),
            'WHERE' => [
                'NOT' => ['custom_helpdesk_home_title' => (string) Entity::CONFIG_PARENT],
            ] + getEntitiesRestrictCriteria(Entity::getTable()),
        ]);
        $entities_handlers = [];
        foreach ($entities_iterator as $row) {
            $entity = new Entity();
            $entity->getFromResultSet($row);
            $entity->post_getFromDB();
            $entities_handlers[] = $entity->listTranslationsHandlers();
        }

        $tiles = $tiles_manager->getAllTiles();
        $tiles = array_filter($tiles, fn($tile) => $tile instanceof ProvideTranslationsInterface);
        $tiles_handlers = array_map(fn(ProvideTranslationsInterface $tile) => $tile->listTranslationsHandlers(), $tiles);

        return array_merge(...$entities_handlers, ...$tiles_handlers);
    }

    public static function getTranslationsForHelpdesk(): array
    {
        global $DB;

        $tiles_manager = TilesManager::getInstance();

        // Join on entities to ignore orphan translations without loading every entity
        $entities_translations = [];
        $iterator = $DB->request([
            'SELECT'     => self::getTable() . '.*',
            'FROM'       => self::getTable(),
            'INNER JOIN' => [
                Entity::getTable() => [
                    'ON' => [
                        self::getTable()   => self::$items_id,
                        Entity::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE'      => [self::getTable() . '.' . self::$itemtype => Entity::class],
        ]);
        foreach ($iterator as $row) {
            $translation = new self();
            $translation->getFromResultSet($row);
            $entities_translations[] = $translation;
        }

        return array_merge(
            $entities_translations,
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
        // Filter out handlers with empty values and those that do not have a translation yet
        return array_map(
            fn(array $handlers) => array_filter(
                $handlers,
                fn(TranslationHandler $handler) => !empty($handler->getValue()) || !empty(self::getForItemKeyAndLanguage(
                    $handler->getItem(),
                    $handler->getKey(),
                    $this->fields['language']
                )?->getTranslation())
            ),
            $this->listTranslationsHandlers()
        );
    }

    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        return [
            'itemtype' => array_map(static fn($tile) => $tile::class, (TilesManager::getInstance())->getTileTypes()),
        ];
    }
}

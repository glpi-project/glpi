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

namespace Glpi\Form;

use CommonTreeDropdown;
use DropdownTranslation;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\Form\ServiceCatalog\ServiceCatalogCompositeInterface;
use Glpi\Form\ServiceCatalog\ServiceCatalogItemInterface;
use Glpi\UI\IllustrationManager;
use Override;

final class Category extends CommonTreeDropdown implements ServiceCatalogCompositeInterface
{
    public $can_be_translated = true;

    public static $rightname = 'form';

    /** @var ServiceCatalogItemInterface[] $children */
    private array $children = [];

    #[Override]
    public static function getTypeName($nb = 0): string
    {
        return _n('Service catalog category', 'Service catalog categories', $nb);
    }

    #[Override]
    public static function getIcon(): string
    {
        return "ti ti-tags";
    }

    #[Override]
    protected function insertTabs($options = []): array
    {
        $tabs = [];
        $this->addStandardTab(Form::class, $tabs, $options);

        return $tabs;
    }

    #[Override]
    public function getAdditionalFields()
    {
        $fields = parent::getAdditionalFields();
        $fields[] = [
            'name'        => 'description',
            'label'       => __('Description'),
            'type'        => 'tinymce',
            'form_params' => ['enable_images' => false, 'full_width' => false],
            'list'        => false,
        ];
        $fields[] = [
            'name'  => 'illustration',
            'label' => __('Illustration'),
            'type'  => 'illustration',
            'list'  => false,
        ];

        return $fields;
    }

    #[Override]
    public function rawSearchOptions(): array
    {
        $options = parent::rawSearchOptions();
        $options[] = [
            'id'                => '3',
            'table'             => $this->getTable(),
            'field'             => 'description',
            'name'              => __('Description'),
            'datatype'          => 'text',
        ];

        return $options;
    }

    #[Override]
    public function getServiceCatalogItemTitle(): string
    {
        return DropdownTranslation::getTranslatedValue(
            $this->fields['id'],
            self::class,
            'name',
            value: $this->fields['name'],
        );
    }

    #[Override]
    public function getServiceCatalogItemDescription(): string
    {
        return DropdownTranslation::getTranslatedValue(
            $this->fields['id'],
            self::class,
            'description',
            value: $this->fields['description'] ?? ''
        );
    }

    #[Override]
    public function getServiceCatalogItemIllustration(): string
    {
        return $this->fields['illustration'] ?: IllustrationManager::DEFAULT_ILLUSTRATION;
    }

    #[Override]
    public function isServiceCatalogItemPinned(): bool
    {
        return false;
    }

    #[Override]
    public function getChildrenUrlParameters(): string
    {
        return http_build_query(['category' => $this->getID()]);
    }

    #[Override]
    public function getChildrenItemRequest(
        ItemRequest $item_request,
    ): ItemRequest {
        return new ItemRequest(
            access_parameters: $item_request->getFormAccessParameters(),
            category_id: $this->getID(),
        );
    }

    #[Override]
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    #[Override]
    public function getChildren(): array
    {
        return $this->children;
    }
}

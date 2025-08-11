<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Features\ZonableModelPicture;

use function Safe\json_decode;
use function Safe\json_encode;

class Stencil extends CommonDBChild implements ZonableModelPicture
{
    public static $itemtype = "itemtype";
    public static $items_id = "items_id";

    private CommonDBTM $item;

    public static function getTypeName($nb = 0): string
    {
        return __('Stencil');
    }

    public static function getIcon(): string
    {
        return 'ti ti-shape';
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_stencils';
    }

    /**
     * Returns the stencil associated with the given item
     *
     * @param CommonDBTM $item
     * @return Stencil|null
     */
    public static function getStencilFromItem(CommonDBTM $item): ?Stencil
    {
        $itemtype = $item::getType();
        $items_id = $item->getID();

        switch ($item->getType()) {
            case NetworkEquipment::getType():
            case NetworkEquipmentModel::getType():
                $stencil = new NetworkEquipmentModelStencil();
                break;
            default:
                return null;
        }

        $stencil->getFromDBByCrit([
            'itemtype' => $itemtype,
            'items_id' => $items_id,
        ]);
        $stencil->item = $item;

        return $stencil;
    }

    /**
     * Returns the stencil associated with the given ID
     *
     * @param int $id
     * @return Stencil|null
     */
    public static function getStencilFromID(int $id): ?Stencil
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_stencils.id',
                'glpi_stencils.itemtype',
                'glpi_stencils.items_id',
            ],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'glpi_stencils.id' => $id,
            ],
        ]);

        if ($iterator->count() == 1) {
            $itemtype = $iterator->current()['itemtype'];
            $items_id = $iterator->current()['items_id'];

            $item = getItemForItemtype($itemtype);
            if ($item->getFromDB($items_id)) {
                return self::getStencilFromItem($item);
            }
        }

        return null;
    }

    /**
     * Returns the item associated with the stencil
     *
     * @return CommonDBTM|null
     */
    public function getStencilItem(): ?CommonDBTM
    {
        if (isset($this->item)) {
            return $this->item;
        }

        if (!isset($this->fields['itemtype']) || !isset($this->fields['items_id'])) {
            return null;
        }

        $itemtype = $this->fields['itemtype'];
        $items_id = $this->fields['items_id'];

        $item = getItemForItemtype($itemtype);
        if (!$item || !$item->getFromDB($items_id)) {
            return null;
        }

        return $item;
    }

    /**
     * Returns the fields to display in the stencil editor
     *
     * @return array
     */
    public function getPicturesFields(): array
    {
        return [];
    }

    /**
     * Returns the parameters to pass to the stencil template
     *
     * Available parameters:
     * - nb_zones_label (only for editor)
     * - define_zones_label (only for editor)
     * - zone_label (only for editor)
     * - zone_number_label (only for editor)
     * - save_zone_data_label (only for editor)
     * - add_zone_label (only for editor)
     * - remove_zone_label (only for editor)
     * - anchor_id (only for display)
     *
     * @param bool $editor
     * @return array
     */
    public function getParams(bool $editor): array
    {
        return [];
    }

    /**
     * Returns the maximum number of zones allowed for the stencil
     *
     * @return int
     */
    public function getMaxZoneNumber(): int
    {
        return 256;
    }

    public function prepareInputForAdd($input)
    {
        // Check if the "nb_zones" property is set
        if (!isset($input['nb_zones'])) {
            return [];
        }

        // Limits the number of zones to the maximum allowed number of zones.
        $input['nb_zones'] = min($input['nb_zones'], $this->getMaxZoneNumber());

        return $input;
    }

    /**
     * Add zones to the stencil
     *
     * @param array $input
     * @return void
     */
    public function addNewZones(array $input): void
    {
        $this->update(array_merge($input, [
            'nb_zones' => $this->fields['nb_zones'] + ($input['nb-new-zones'] ?? 1),
        ]));
    }

    /**
     * Remove zones from the stencil
     *
     * @param array $input
     * @return void
     */
    public function removeZones(array $input): void
    {
        // Compute the new number of zones
        $nbZones = $this->fields['nb_zones'] - ($input['nb-remove-zones'] ?? 1);

        // Remove zones with id > $nbZones
        $zones = array_filter(
            json_decode($this->fields['zones'] ?? '{}', true),
            fn($id) => $id <= $nbZones,
            ARRAY_FILTER_USE_KEY
        );

        // Update the stencil
        $this->update(array_merge($input, [
            'zones'    => json_encode($zones, JSON_FORCE_OBJECT),
            'nb_zones' => $this->fields['nb_zones'] - ($input['nb-remove-zones'] ?? 1),
        ]));
    }

    /**
     * Reset zones to their default values
     *
     * @param array $input
     * @return void
     */
    public function resetZones(array $input): void
    {
        // Decode the zones
        $zones = json_decode($this->fields['zones'] ?? '{}', true);

        // Remove the zone corresponding to the given id
        unset($zones[$input['zone-id'] ?? null]);

        // Update the stencil
        $this->update(array_merge($input, [
            'zones' => json_encode($zones, JSON_FORCE_OBJECT),
        ]));
    }

    /**
     * Load the cropper library
     *
     * @return void
     */
    public static function loadLibs(): void
    {
        echo Html::script("lib/cropper.js");
        echo Html::script("js/stencil-editor.js");
        echo Html::scss('css/standalone/stencil-editor.scss');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if (!($item instanceof CommonDBTM)) {
            return '';
        }

        $nb = count(json_decode(static::getStencilFromItem($item)->fields['zones'] ?? '{}', true));

        return self::createTabEntry(static::getTypeName(), $nb, $item::getType());
    }

    public function displayStencil(): void
    {
        $item = $this->getStencilItem();

        if ($item == null) {
            return;
        }

        self::loadLibs();

        $pictures = [];
        foreach ($this->getPicturesFields() as $picture_field) {
            if (
                empty($item->getItemtypeOrModelPicture($picture_field))
                || !isset($item->getItemtypeOrModelPicture($picture_field)[0]['src'])
            ) {
                continue;
            }

            $pictures[] = $item->getItemtypeOrModelPicture($picture_field)[0]['src'];
        }

        $model_fk      = $item->getModelForeignKeyField();
        $model         = $item->getModelClassInstance();
        $model->getFromDB($item->fields[$model_fk]);
        $model_stencil = self::getStencilFromItem($model);

        TemplateRenderer::getInstance()->display('stencil/view.html.twig', [
            'item'              => $item,
            'stencil'           => $this,
            'zones'             => json_decode($model_stencil->fields['zones'] ?? '{}', true),
            'pictures'          => $pictures,
            'params'            => array_merge(
                $this->getParams(false),
                ['is_editor_view' => false]
            ),
        ]);
    }

    public function displayStencilEditor(): void
    {
        $item = $this->getStencilItem();

        if ($item == null || $item->getID() <= 0) {
            return;
        }

        self::loadLibs();

        $pictures = [];
        foreach ($this->getPicturesFields() as $picture_field) {
            $picture = $item->getItemtypeOrModelPicture($picture_field);
            if ($picture !== [] && isset($picture[0]['src'])) {
                $pictures[] = $picture[0]['src'];
            }
        }

        $self = self::getStencilFromItem($item);
        TemplateRenderer::getInstance()->display('stencil/editor.html.twig', [
            'item'              => $item,
            'stencil'           => $this,
            'itemtype'          => $item->gettype(),
            'items_id'          => $item->getID(),
            'id'                => $self->fields['id'] ?? 0,
            'zones_json'        => $self->fields['zones'] ?? '{}',
            'zones'             => json_decode($self->fields['zones'] ?? '{}', true),
            'nb_zones'          => $self->fields['nb_zones'] ?? 1,
            'pictures'          => $pictures,
            'params'            => array_merge(
                $this->getParams(true),
                ['is_editor_view' => true]
            ),
        ]);
    }

    /**
     * Returns the HTML code for the label of a stencil zone
     *
     * @param bool $editor
     * @param array $zone
     * @return string Label HTML code
     */
    public function getZoneLabel(bool $editor, array $zone): string
    {
        return TemplateRenderer::getInstance()->render('stencil/parts/label.html.twig', [
            'label' => $zone['label'],
        ]);
    }

    /**
     * Returns the HTML code for the popover of a stencil zone
     *
     * @param bool $editor
     * @param array $zone
     * @return string Popover HTML code
     */
    public function getZonePopover(bool $editor, array $zone): string
    {
        return '';
    }
}

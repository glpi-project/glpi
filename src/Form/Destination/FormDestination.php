<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Form\Destination;

use CommonDBChild;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Form;
use InvalidArgumentException;
use Override;
use ReflectionClass;

final class FormDestination extends CommonDBChild
{
    /**
     * Parent item is a Form
     */
    public static $itemtype = Form::class;
    public static $items_id = 'forms_forms_id';

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return __('Items to create', $nb);
    }

    #[Override]
    public static function getIcon()
    {
        return "ti ti-arrow-forward";
    }

    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // Only for forms
        if (!($item instanceof Form)) {
            return false;
        }

        $count = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $count = $this->countForForm($item);
        }

        return self::createTabEntry(
            self::getTypeName(),
            $count,
            null,
            self::getIcon() // Must be passed manually for some reason
        );
    }

    #[Override]
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        // Only for forms
        if (!($item instanceof Form)) {
            return false;
        }

        $renderer = TemplateRenderer::getInstance();
        $renderer->display('pages/admin/form/form_destination.html.twig', [
            'icon'                         => self::getIcon(),
            'form'                         => $item,
            'controller_url'               => self::getFormURL(),
            'default_destination_object'   => new FormDestinationTicket(),
            'destinations'                 => $item->getDestinations(),
            'available_destinations_types' => [
                FormDestinationTicket::class => \Ticket::getTypeName(1),
                FormDestinationChange::class  => \Change::getTypeName(1),
                FormDestinationProblem::class  => \Problem::getTypeName(1),
            ],
        ]);

        return true;
    }

    #[Override]
    public static function canCreate()
    {
        // Must be able to update forms
        return Form::canUpdate();
    }

    #[Override]
    public function canCreateItem()
    {
        $form = Form::getByID($this->fields['forms_forms_id']);
        if (!$form) {
            return false;
        }

        // Must be able to update the parent form
        return $form->canUpdateItem();
    }

    #[Override]
    public static function canPurge()
    {
        // Must be able to update forms
        return Form::canUpdate();
    }

    #[Override]
    public function canPurgeItem()
    {
        $form = Form::getByID($this->fields['forms_forms_id']);
        if (!$form) {
            return false;
        }

        // Must be able to update the parent form
        return $form->canUpdateItem();
    }

    #[Override]
    public function prepareInputForAdd($input)
    {
        // Validate forms fk
        $form_id = $input[Form::getForeignKeyField()] ?? null;
        if ($form_id === null) {
            throw new InvalidArgumentException("Missing form id");
        }

        // Validate parent Form object
        $form = Form::getByID($form_id);
        if (!$form) {
            throw new InvalidArgumentException("Form not found");
        }

        // Validate itemtype
        $type = $input['itemtype'] ?? null;
        if (
            $type === null
            || !is_a($type, AbstractFormDestinationType::class, true)
            || (new ReflectionClass($type))->isAbstract()
        ) {
            throw new InvalidArgumentException("Invalid itemtype");
        }

        // Set default name
        if (!isset($input['name'])) {
            $input['name'] = $type::getTypeName(1);
        }

        return $input;
    }

    /**
     * Get the concrete destination item using the specified class.
     *
     * @return FormDestinationInterface|null
     */
    public function getConcreteDestinationItem(): ?FormDestinationInterface
    {
        $class = $this->fields['itemtype'];

        if (!is_a($class, FormDestinationInterface::class, true)) {
            return null;
        }

        if ((new ReflectionClass($class))->isAbstract()) {
            return null;
        }

        return new $class();
    }

    /**
     * Get valid destinations for a given form
     *
     * @param Form $form
     *
     * @return AbstractFormDestinationType[]
     */
    protected function getDestinationsForForm(Form $form): array
    {
        $destinations = [];
        $raw_data = $this->find(['forms_forms_id' => $form->getID()]);

        foreach ($raw_data as $row) {
            if (
                !is_a($row['itemtype'], AbstractFormDestinationType::class, true)
                || (new ReflectionClass($row['itemtype']))->isAbstract()
            ) {
                // Invalid itemtype, maybe from a disabled plugin
                continue;
            }

            $destination = $row['itemtype']::getById($row['items_id']);
            if (!$destination) {
                continue;
            }

            $destinations[] = $destination;
        }

        return $destinations;
    }

    /**
     * Count the number of form_desinations items for a form
     *
     * @param Form $form
     *
     * @return int
     */
    protected function countForForm(Form $form): int
    {
        return countElementsInTable(
            self::getTable(),
            ['forms_forms_id' => $form->getID()],
        );
    }
}

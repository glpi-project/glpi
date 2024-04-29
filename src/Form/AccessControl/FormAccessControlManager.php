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

namespace Glpi\Form\AccessControl;

use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\ControlTypeInterface;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\Form;
use ReflectionClass;
use Session;

final class FormAccessControlManager
{
    /**
     * Singleton instance.
     */
    private static ?FormAccessControlManager $instance = null;

    /**
     * Private constructor (singleton).
     */
    private function __construct()
    {
    }

    /**
     * Singleton access method.
     *
     * @return FormAccessControlManager
     */
    public static function getInstance(): FormAccessControlManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * Get access controls for the given form.
     *
     * @param Form $form
     * @param bool $only_active If true, only active controls are returned.
     *                          Setting this to false will trigger lazy creation
     *                          for missing access controls.
     *
     * @return FormAccessControl[]
     */
    public function getAccessControlsForForm(
        Form $form,
        bool $only_active = true
    ): array {
        $controls = [];
        $raw_controls = (new FormAccessControl())->find([
            Form::getForeignKeyField() => $form->getId(),
            'is_active'                => $only_active ? true : [true, false],
        ]);

        // Make sure all returned data are valid (some data might come from
        // disabled plugins).
        $defined_strategies = [];
        foreach ($raw_controls as $row) {
            if (!$this->isValidAccessControlType($row['strategy'])) {
                continue;
            }

            $control = new FormAccessControl();
            $control->getFromResultSet($row);
            $control->post_getFromDB();
            $controls[] = $control;
            $defined_strategies[] = $row['strategy'];
        }

        if (!$only_active) {
            // Lazy creation of missing controls types.
            $missing_strategies = array_filter(
                $this->getPossibleAccessControlsStrategies(),
                fn ($strategy) => !in_array($strategy::class, $defined_strategies)
            );

            // Create an access control for each missing strategies.
            foreach ($missing_strategies as $missing_strategy) {
                $control = new FormAccessControl();
                $strategy = $missing_strategy::class;

                $new_control_id = !$control->add([
                    Form::getForeignKeyField() => $form->getId(),
                    'strategy'                 => $strategy,
                    'is_active'                => false,
                ]);

                if ($new_control_id) {
                    trigger_error(
                        "Fail to create control type: `$strategy`",
                        E_USER_WARNING
                    );
                }

                $controls[] = $control;
            }
        }

        // Sort by is_active + strategy weight
        usort($controls, function ($a, $b) {
            if ($a->isActive() && !$b->isActive()) {
                return -1;
            } elseif (!$a->isActive() && $b->isActive()) {
                return 1;
            } else {
                $strategy = $a->getStrategy();
                return $strategy->getWeight() <=> $strategy->getWeight();
            }
        });

        return $controls;
    }

    /**
     * Check if the current user can answer the given form.
     *
     * @param Form $form
     * @param FormAccessParameters $parameters
     *
     * @return bool
     */
    public function canAnswerForm(
        Form $form,
        FormAccessParameters $parameters
    ): bool {
        // Form administrators can preview all forms.
        if (Session::haveRight(Form::$rightname, READ)) {
            return true;
        }

        $access_controls_policies = $this->getAccessControlsForForm($form);
        if (count($access_controls_policies) === 0) {
            // Refuse access if no access controls are configured.
            return false;
        }

        return $this->validateAccessControlsPolicies(
            $access_controls_policies,
            $parameters
        );
    }

    private function validateAccessControlsPolicies(
        array $policies,
        FormAccessParameters $parameters
    ): bool {
        // TODO: for now, we use an unanimous decision system.
        // Future PR will allow to pick between unanimous and affirmative
        // strategies.

        /** @var FormAccessControl[] $policies */
        foreach ($policies as $policiy) {
            $can_answer = $policiy->getStrategy()->canAnswer(
                $policiy->getConfig(),
                $parameters
            );

            if (!$can_answer) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the given class is a valid access control type.
     *
     * @param string $class
     *
     * @return bool
     */
    protected function isValidAccessControlType(string $class): bool
    {
        return
            is_a($class, ControlTypeInterface::class, true)
            && !(new ReflectionClass($class))->isAbstract()
        ;
    }

    /**
     * Get all possible access control strategies.
     *
     * @return ControlTypeInterface[]
     */
    protected function getPossibleAccessControlsStrategies(): array
    {
        $types = [
            new AllowList(),
            new DirectAccess(),
        ];

        // TODO: plugin support

        return $types;
    }
}

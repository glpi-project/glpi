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

namespace Glpi\Form\AccessControl;

use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\ControlTypeInterface;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\Form;

final class FormAccessControlManager
{
    /**
     * Singleton instance.
     */
    private static ?FormAccessControlManager $instance = null;

    /** @var ControlTypeInterface[] */
    private array $plugins_policies = [];

    /**
     * Private constructor (singleton).
     */
    private function __construct() {}

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

    public function createMissingAccessControlsForForm(
        Form $form
    ) {
        $access_controls = $form->getAccessControls();
        $defined_strategies = $this->getDefinedStrategies($access_controls);
        $missing_strategies = $this->getMissingStrategies($defined_strategies);

        // Create an access control for each missing strategies.
        foreach ($missing_strategies as $missing_strategy) {
            $this->createMissingStrategyForForm($form, $missing_strategy);
        }

        // Clear lazy loaded data
        $form->getFromDB($form->getID());
    }

    public function getActiveAccessControlsForForm(
        Form $form
    ) {
        $controls = $form->getAccessControls();
        $controls = array_filter(
            $controls,
            fn($control) => $control->fields['is_active'],
        );
        return array_values($controls);
    }

    public function allowUnauthenticatedAccess(Form $form): bool
    {
        $access_controls = $this->getActiveAccessControlsForForm($form);
        return array_reduce(
            $access_controls,
            fn($acc, $control) => $acc || $control->getStrategy()->allowUnauthenticated($control->getConfig()),
            false
        );
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
        // Form administrators can bypass restrictions while previewing forms.
        if ($parameters->isBypassingRestrictions()) {
            return true;
        }

        if ($form->isDeleted()) {
            return false;
        }

        if (!$form->isActive()) {
            return false;
        }

        $access_controls_policies = $this->getActiveAccessControlsForForm($form);
        if (count($access_controls_policies) === 0) {
            // Refuse access if no access controls are configured.
            return false;
        }

        return $this->validateAccessControlsPolicies(
            $form,
            $access_controls_policies,
            $parameters
        );
    }

    /**
     * @param FormAccessControl[] $controls
     * @return FormAccessControl[]
     */
    public function sortAccessControls(array $controls): array
    {
        // Sort by is_active + strategy weight
        usort($controls, function (FormAccessControl $a, FormAccessControl $b) {
            if ($a->fields['is_active'] && !$b->fields['is_active']) {
                return -1;
            } elseif (!$a->fields['is_active'] && $b->fields['is_active']) {
                return 1;
            } else {
                $strategy = $a->getStrategy();
                return $strategy->getWeight() <=> $strategy->getWeight();
            }
        });

        return $controls;
    }

    /**
     * Get an array access controls warnings messages (string) for a given
     * form.
     *
     * @param Form $form
     * @return string[]
     */
    public function getWarnings(Form $form): array
    {
        $warnings = [];
        $warnings = $this->addWarningIfFormIsNotActive($form, $warnings);
        $warnings = $this->addWarningIfFormHasNoActivePolicies($form, $warnings);

        // Add Access Control Strategy warnings
        $access_controls = $this->getPossibleAccessControlsStrategies();
        foreach ($access_controls as $access_control) {
            array_push($warnings, ...$access_control->getWarnings($form));
        }

        return $warnings;
    }

    public function registerPluginAccessControlPolicy(
        ControlTypeInterface $control
    ): void {
        $this->plugins_policies[] = $control;
    }

    private function addWarningIfFormIsNotActive(
        Form $form,
        array $warnings
    ): array {
        if (!$form->isActive()) {
            $warnings[] = __('This form is not visible to anyone because it is not active.');
        }

        return $warnings;
    }

    private function addWarningIfFormHasNoActivePolicies(
        Form $form,
        array $warnings
    ): array {
        if (count($this->getActiveAccessControlsForForm($form)) === 0) {
            $warnings[] = __('This form will not be visible to any users as there are currently no active access policies.');
        }

        return $warnings;
    }

    private function validateAccessControlsPolicies(
        Form $form,
        array $policies,
        FormAccessParameters $parameters
    ): bool {
        /** @var AccessVote[] $votes */
        $votes = [];

        /** @var FormAccessControl[] $policies */
        foreach ($policies as $policiy) {
            $votes[] = $policiy->getStrategy()->canAnswer(
                $form,
                $policiy->getConfig(),
                $parameters
            );
        }

        $can_answer = $this->computeVote($votes);
        return $can_answer;
    }

    /** @param AccessVote[] $votes */
    private function computeVote(array $votes): bool
    {
        $has_at_least_one_deny_vote = in_array(AccessVote::Deny, $votes);
        $has_at_least_one_grant_vote = in_array(AccessVote::Grant, $votes);

        if ($has_at_least_one_deny_vote) {
            return false;
        } elseif ($has_at_least_one_grant_vote) {
            return true;
        } else {
            return false;
        }
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
            ...$this->plugins_policies,
        ];

        return $types;
    }

    /**
     * @param FormAccessControl[] $access_controls
     * @return ControlTypeInterface[]
     */
    private function getDefinedStrategies(array $access_controls)
    {
        return array_map(
            fn($control) => $control->getStrategy(),
            $access_controls
        );
    }

    /**
     * @param ControlTypeInterface[] $defined_strategies
     * @return ControlTypeInterface[]
     */
    private function getMissingStrategies(array $defined_strategies)
    {
        $defined_strategies_classes = array_map(
            fn($strategy) => $strategy::class,
            $defined_strategies
        );

        return array_filter(
            $this->getPossibleAccessControlsStrategies(),
            fn(ControlTypeInterface $strategy) => !in_array(
                $strategy::class,
                $defined_strategies_classes
            )
        );
    }

    private function createMissingStrategyForForm(
        Form $form,
        ControlTypeInterface $missing_strategy
    ) {
        $form_access_control = new FormAccessControl();
        $strategy = $missing_strategy::class;

        $new_control_id = $form_access_control->add([
            Form::getForeignKeyField() => $form->getId(),
            'strategy'                 => $strategy,
            'is_active'                => false,
        ]);

        if (!$new_control_id) {
            trigger_error(
                "Fail to create control type: `$strategy`",
                E_USER_WARNING
            );
        }
    }
}

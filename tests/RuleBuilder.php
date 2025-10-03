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

/**
 * Helper class to configurate rule creation in DbTestCase::createRule()
 */
class RuleBuilder
{
    /**
     * @property string $name Rule name
     */
    protected string $name;

    /**
     * @property string $operator 'AND' or 'OR'
     */
    protected string $operator;

    /**
     * @property int $condition RuleTicket::ONADD and/or RuleTicket::ONUPDATE (bitmask)
     */
    protected int $condition;

    /**
     * @property bool $is_recursive
     */
    protected bool $is_recursive;

    /**
     * @property int $entities_id
     */
    protected int $entities_id;

    /**
     * @property array $criteria
     */
    protected array $criteria;

    /**
     * @property array $actions
     */
    protected array $actions;

    /**
     * @property string $name Rule name
     */
    protected string $rule_type;

    protected ?int $ranking = null;

    /**
     * @param string $name Rule name
     */
    public function __construct(string $name, ?string $rule_type = null)
    {
        $this->name = $name;

        // Default values
        $this->operator     = "AND";
        $this->rule_type    = $rule_type ?? RuleTicket::class;
        $this->is_recursive = true;
        $this->entities_id  = getItemByTypeName(Entity::class, '_test_root_entity', true);
        $this->criteria     = [];
        $this->actions      = [];

        assert(is_a($this->rule_type, Rule::class, true), '$rule_type parameter must be a subclass of \Rule');

        if (is_a($this->rule_type, RuleCommonITILObject::class, true)) {
            $this->condition = RuleTicket::ONADD | RuleTicket::ONUPDATE;
        } else {
            $this->condition = 0;
        }
    }

    /**
     * Set condition
     *
     * @param int $condition RuleTicket::ONADD and/or RuleTicket::ONUPDATE
     *
     * @return self
     */
    public function setCondtion(int $condition): self
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Set operator
     *
     * @param string $operator 'AND' or 'OR'
     *
     * @return self
     */
    public function setOperator(string $operator): self
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * Set entity configuration
     *
     * @param bool $is_recursive
     *
     * @return self
     */
    public function setIsRecursive(int $is_recursive): self
    {
        $this->is_recursive = $is_recursive;
        return $this;
    }

    /**
     * Set entity configuration
     *
     * @param int $entities_id
     *
     * @return self
     */
    public function setEntity(int $entities_id): self
    {
        $this->entities_id = $entities_id;
        return $this;
    }

    /**
     * Set rule rank
     *
     * @param int $ranking
     *
     * @return self
     */
    public function setRanking(int $ranking): self
    {
        $this->ranking = $ranking;
        return $this;
    }

    /**
     * Add criteria
     *
     * @param string $criteria key of an item of Rule::getCriterias()
     * @param int $condition Rule::PATTERN_IS, ...
     * @param mixed $pattern value to match
     *
     * @return self
     */
    public function addCriteria(
        string $criteria,
        int $condition,
        mixed $pattern
    ): self {
        $this->criteria[] = [
            'criteria'  => $criteria,
            'condition' => $condition,
            'pattern'   => $pattern,
        ];
        return $this;
    }

    /**
     * Add action
     *
     * @param string $action_type 'assign', etc
     * @param string $field key of an item of Rule::getActions()
     * @param mixed $value
     *
     * @return self
     */
    public function addAction(
        string $action_type,
        string $field,
        $value
    ): self {
        $this->actions[] = [
            'action_type' => $action_type,
            'field'       => $field,
            'value'       => $value,
        ];
        return $this;
    }

    /**
     * Get rule name
     *
     * @return string Rule name
     */
    public function getRuleType(): string
    {
        return $this->rule_type;
    }

    /**
     * Get rule name
     *
     * @return string Rule name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get rule operator
     *
     * @return string 'AND' or 'OR'
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Get rule condition
     *
     * @return int RuleTicket::ONADD and/or RuleTicket::ONUPDATE (bitmask)
     */
    public function getCondition(): int
    {
        return $this->condition;
    }

    /**
     * Get rule entity configuration
     *
     * @return bool
     */
    public function isRecursive(): bool
    {
        return $this->is_recursive;
    }

    /**
     * Get rule entity configuration
     *
     * @return int
     */
    public function getEntity(): int
    {
        return $this->entities_id;
    }

    /**
     * Get rule criteria
     *
     * @return array
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    /**
     * Get rule actions
     *
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get rule ranking, if defined
     *
     * @return ?int
     */
    public function getRanking(): ?int
    {
        return $this->ranking;
    }
}

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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Destination\ConfigFieldWithStrategiesInterface;
use Glpi\Form\Destination\FormDestinationManager;
use Override;

abstract class SLMFieldConfig implements
    JsonFieldInterface,
    ConfigFieldWithStrategiesInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGY = 'strategy';
    public const SLM_ID = 'slm_id';
    public const EXTRA_DATA = 'extra_data';

    /**
     * @param SLMFieldStrategy|string $strategy Strategy enum or key string for plugin strategies
     * @param int|null $specific_slm_id Specific SLM ID for SPECIFIC_VALUE strategy
     * @param array<string, mixed> $extra_data Extra data for plugin strategies
     */
    final public function __construct(
        private SLMFieldStrategy|string $strategy,
        private ?int $specific_slm_id = null,
        private array $extra_data = [],
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $strategy_key = $this->strategy instanceof SLMFieldStrategy
            ? $this->strategy->value
            : $this->strategy;

        return [
            self::STRATEGY => $strategy_key,
            self::SLM_ID => $this->specific_slm_id,
            self::EXTRA_DATA => $this->extra_data,
        ];
    }

    #[Override]
    public static function getStrategiesInputName(): string
    {
        return self::STRATEGY;
    }

    /**
     * Get the strategy key.
     *
     * @return string
     */
    public function getStrategyKey(): string
    {
        return $this->strategy instanceof SLMFieldStrategy
            ? $this->strategy->value
            : $this->strategy;
    }

    /**
     * Get the strategy instance.
     *
     * @return SLMFieldStrategyInterface
     */
    public function getStrategy(): SLMFieldStrategyInterface
    {
        if ($this->strategy instanceof SLMFieldStrategy) {
            return $this->strategy;
        }

        $strategy = FormDestinationManager::getInstance()->getSLMFieldStrategy($this->strategy);
        return $strategy ?? SLMFieldStrategy::FROM_TEMPLATE;
    }

    /**
     * @return array<SLMFieldStrategyInterface>
     */
    public function getStrategies(): array
    {
        return [$this->getStrategy()];
    }

    public function getSpecificSLMID(): ?int
    {
        return $this->specific_slm_id;
    }

    /**
     * Get extra data for plugin strategies.
     *
     * @return array<string, mixed>
     */
    public function getExtraData(): array
    {
        return $this->extra_data;
    }

    /**
     * Get a specific extra data value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getExtraDataValue(string $key, mixed $default = null): mixed
    {
        return $this->extra_data[$key] ?? $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Override]
    public static function jsonDeserialize(array $data): static
    {
        $strategy_key = $data[self::STRATEGY] ?? "";

        // Try core enum first, then check registered plugin strategies
        $strategy = SLMFieldStrategy::tryFrom($strategy_key);
        if ($strategy === null) {
            $plugin_strategy = FormDestinationManager::getInstance()->getSLMFieldStrategy($strategy_key);
            if ($plugin_strategy !== null) {
                // Use the key for plugin strategies
                $strategy = $strategy_key;
            } else {
                // Fallback to default
                $strategy = SLMFieldStrategy::FROM_TEMPLATE;
            }
        }

        return new static(
            strategy: $strategy,
            specific_slm_id: $data[self::SLM_ID] ?? null,
            extra_data: $data[self::EXTRA_DATA] ?? [],
        );
    }
}

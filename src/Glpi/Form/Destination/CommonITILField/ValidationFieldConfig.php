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
use Override;

final class ValidationFieldConfig implements
    JsonFieldInterface,
    ConfigFieldWithStrategiesInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGIES = 'strategies';
    public const STRATEGY_CONFIGS = 'strategy_configs';

    /**
     * @param array<ValidationFieldStrategyConfig> $strategy_configs
     */
    public function __construct(
        private array $strategy_configs = []
    ) {
        // Ensure we have at least one strategy
        if ($this->strategy_configs === []) {
            $this->strategy_configs[] = new ValidationFieldStrategyConfig(
                ValidationFieldStrategy::NO_VALIDATION
            );
        }
    }

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategy_configs = [];
        foreach ($data[self::STRATEGY_CONFIGS] as $config_data) {
            $strategy_configs[] = ValidationFieldStrategyConfig::jsonDeserialize($config_data);
        }
        return new self($strategy_configs);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGY_CONFIGS => array_map(
                fn(ValidationFieldStrategyConfig $config) => $config->jsonSerialize(),
                $this->strategy_configs
            ),
        ];
    }

    #[Override]
    public static function getStrategiesInputName(): string
    {
        return self::STRATEGIES;
    }

    /**
     * @return array<ValidationFieldStrategy>
     */
    public function getStrategies(): array
    {
        return array_map(
            fn(ValidationFieldStrategyConfig $config) => $config->getStrategy(),
            $this->strategy_configs
        );
    }

    /**
     * @return array<ValidationFieldStrategyConfig>
     */
    public function getStrategyConfigs(): array
    {
        return $this->strategy_configs;
    }

    public function getStrategyConfigByIndex(int $index): ?ValidationFieldStrategyConfig
    {
        return $this->strategy_configs[$index] ?? null;
    }
}

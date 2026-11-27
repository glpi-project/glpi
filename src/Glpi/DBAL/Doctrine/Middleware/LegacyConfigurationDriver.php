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

namespace Glpi\DBAL\Doctrine\Middleware;

use DBmysql;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Glpi\DBAL\Doctrine\ConnectionParamsFactory;
use RuntimeException;
use SensitiveParameter;

final class LegacyConfigurationDriver extends AbstractDriverMiddleware
{
    public function __construct(
        Driver $wrapped_driver,
        private readonly string $file,
        private readonly string $class,
    ) {
        parent::__construct($wrapped_driver);
    }

    public function connect(
        #[SensitiveParameter]
        array $params,
    ): Connection {
        if (!class_exists($this->class, false) && is_readable($this->file)) {
            include_once($this->file);
        }
        if (!is_a($this->class, DBmysql::class, true)) {
            throw new RuntimeException(sprintf('Database configuration class "%s" is not defined.', $this->class));
        }

        return parent::connect(
            array_replace($params, ConnectionParamsFactory::fromConfigClass($this->class))
        );
    }
}

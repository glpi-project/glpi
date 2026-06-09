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

use Laminas\I18n\Translator\Translator;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

// This file contains globals types declarations.
// It permits to indicates to IDEs what type is the global variable.

/** @var DBmysql $DB */
global $DB;

/** @var array<string,mixed> $CFG_GLPI */
global $CFG_GLPI;

/** @var bool $HEADER_LOADED */
global $HEADER_LOADED;

/** @var CacheInterface $GLPI_CACHE */
global $GLPI_CACHE;

/** @var LoggerInterface $PHPLOGGER */
global $PHPLOGGER;

/** @var Migration $migration */
global $migration;

/** @var array<string, array<string, callable>> $PLUGIN_HOOKS */
global $PLUGIN_HOOKS;

/** @var Translator $TRANSLATE */
global $TRANSLATE;

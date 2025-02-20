--
-- ---------------------------------------------------------------------
--
-- GLPI - Gestionnaire Libre de Parc Informatique
--
-- http://glpi-project.org
--
-- @copyright 2015-2025 Teclib' and contributors.
-- @licence   https://www.gnu.org/licenses/gpl-3.0.html
--
-- ---------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of GLPI.
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.
--
-- ---------------------------------------------------------------------
--

INSERT INTO `glpi_profilerights` (`id`, `profiles_id`, `name`, `rights`) VALUES
(824, 1, 'plugin_genericobject_tablets', 0),
(823, 8, 'plugin_genericobject_tablets', 0),
(822, 7, 'plugin_genericobject_tablets', 0),
(821, 6, 'plugin_genericobject_tablets', 0),
(820, 5, 'plugin_genericobject_tablets', 0),
(819, 4, 'plugin_genericobject_tablets', 127),
(818, 3, 'plugin_genericobject_tablets', 0),
(817, 2, 'plugin_genericobject_tablets', 0),
(816, 1, 'plugin_genericobject_smartphones', 33),
(815, 8, 'plugin_genericobject_smartphones', 0),
(814, 7, 'plugin_genericobject_smartphones', 33),
(813, 6, 'plugin_genericobject_smartphones', 111),
(812, 5, 'plugin_genericobject_smartphones', 33),
(811, 4, 'plugin_genericobject_smartphones', 127),
(810, 3, 'plugin_genericobject_smartphones', 127),
(809, 2, 'plugin_genericobject_smartphones', 33),
(808, 8, 'plugin_genericobject_types', 0),
(807, 7, 'plugin_genericobject_types', 0),
(806, 6, 'plugin_genericobject_types', 0),
(805, 5, 'plugin_genericobject_types', 0),
(804, 4, 'plugin_genericobject_types', 127),
(803, 3, 'plugin_genericobject_types', 0),
(802, 2, 'plugin_genericobject_types', 0),
(801, 1, 'plugin_genericobject_types', 0);

# TODO Add a type in `glpi_profiles.helpdesk_item_type`
# TODO Add `glpi_fieldunicities` data
# TODO Add `glpi_displaypreferences` data
# TODO Add `glpi_savedsearches` data
# TODO Add relations with GLPI core items
# TODO Add child items (e.g. Infocom)

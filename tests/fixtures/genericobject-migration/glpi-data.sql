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

INSERT INTO `glpi_profilerights` (`profiles_id`, `name`, `rights`) VALUES
(1, 'plugin_genericobject_tablets', 0),
(8, 'plugin_genericobject_tablets', 0),
(7, 'plugin_genericobject_tablets', 0),
(6, 'plugin_genericobject_tablets', 0),
(5, 'plugin_genericobject_tablets', 0),
(4, 'plugin_genericobject_tablets', 127),
(3, 'plugin_genericobject_tablets', 0),
(2, 'plugin_genericobject_tablets', 0),
(1, 'plugin_genericobject_smartphones', 33),
(8, 'plugin_genericobject_smartphones', 0),
(7, 'plugin_genericobject_smartphones', 33),
(6, 'plugin_genericobject_smartphones', 111),
(5, 'plugin_genericobject_smartphones', 33),
(4, 'plugin_genericobject_smartphones', 127),
(3, 'plugin_genericobject_smartphones', 127),
(2, 'plugin_genericobject_smartphones', 33),
(8, 'plugin_genericobject_types', 0),
(7, 'plugin_genericobject_types', 0),
(6, 'plugin_genericobject_types', 0),
(5, 'plugin_genericobject_types', 0),
(4, 'plugin_genericobject_types', 127),
(3, 'plugin_genericobject_types', 0),
(2, 'plugin_genericobject_types', 0),
(1, 'plugin_genericobject_types', 0);

UPDATE `glpi_profiles` SET `helpdesk_item_type` = '["Computer", "PluginGenericobjectTablet"]' WHERE `id` IN (1, 6);
UPDATE `glpi_profiles` SET `helpdesk_item_type` = '["Computer", "PluginGenericobjectSmartphone", "PluginGenericobjectTablet", "Software"]' WHERE `id` IN (3, 4);

INSERT INTO `glpi_fieldunicities` (`name`, `is_recursive`, `itemtype`, `entities_id`, `fields`, `is_active`, `action_refuse`, `action_notify`, `comment`, `date_mod`, `date_creation`) VALUES
('Smartphone uniqueness', 1, 'PluginGenericobjectSmartphone', 0, 'name,serial', 1, 1, 0, '', '2025-03-12 12:13:46', '2025-03-05 16:34:56'),
('Tablet uniqueness', 0, 'PluginGenericobjectTablet', 3, 'name', 0, 0, 1, '', '2025-03-12 12:13:46', '2025-03-05 16:34:56');

INSERT INTO `glpi_dropdowntranslations` (`items_id`, `itemtype`, `language`, `field`, `value`) VALUES
(1, 'PluginGenericobjectBar', 'fr_FR', 'name', 'Bar 1 (FR)'),
(1, 'PluginGenericobjectBar', 'es_SP', 'name', 'Bar 1 (ES)'),
(2, 'PluginGenericobjectBar', 'es_SP', 'name', 'Bar 2 (ES)');

INSERT INTO `glpi_infocoms` (`items_id`, `itemtype`, `entities_id`, `is_recursive`, `buy_date`, `use_date`, `warranty_duration`, `warranty_info`, `suppliers_id`, `order_number`, `delivery_number`, `immo_number`, `value`, `warranty_value`, `sink_time`, `sink_type`, `sink_coeff`, `comment`, `bill`, `budgets_id`, `alert`, `order_date`, `delivery_date`, `inventory_date`, `warranty_date`, `date_mod`, `date_creation`, `decommission_date`, `businesscriticities_id`) VALUES
(2, 'PluginGenericobjectSmartphone', 0, 0, '2025-03-03', '2025-03-12', 12, NULL, 0, NULL, NULL, NULL, '1500.0000', '250.0000', 3, 2, 0, NULL, NULL, 0, 0, '2025-03-03', '2025-03-11', NULL, '2025-03-12', '2025-03-12 20:56:50', '2025-03-12 20:55:47', NULL, 0);

INSERT INTO `glpi_contracts_items` (`items_id`, `itemtype`, `contracts_id`) VALUES
(1, 'PluginGenericobjectSmartphone', 4),
(1, 'PluginGenericobjectSmartphone', 7),
(3, 'PluginGenericobjectSmartphone', 12);

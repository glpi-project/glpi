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

// Core
const TiptapCore = require('@tiptap/core');
const TiptapStarterKit = require('@tiptap/starter-kit');

// Extensions
const TiptapLink = require('@tiptap/extension-link');
const TiptapImage = require('@tiptap/extension-image');
const TiptapPlaceholder = require('@tiptap/extension-placeholder');
const TiptapSuggestion = require('@tiptap/suggestion');
const TiptapBubbleMenu = require('@tiptap/extension-bubble-menu');

// Table extensions
const TiptapTable = require('@tiptap/extension-table');
const TiptapTableRow = require('@tiptap/extension-table-row');
const TiptapTableHeader = require('@tiptap/extension-table-header');
const TiptapTableCell = require('@tiptap/extension-table-cell');

// Floating UI for popups (slash commands menu)
const FloatingUI = require('@floating-ui/dom');

// Expose to global scope
window.TiptapCore = TiptapCore;
window.TiptapStarterKit = TiptapStarterKit;
window.TiptapLink = TiptapLink;
window.TiptapImage = TiptapImage;
window.TiptapPlaceholder = TiptapPlaceholder;
window.TiptapSuggestion = TiptapSuggestion;
window.TiptapBubbleMenu = TiptapBubbleMenu;
window.TiptapTable = TiptapTable;
window.TiptapTableRow = TiptapTableRow;
window.TiptapTableHeader = TiptapTableHeader;
window.TiptapTableCell = TiptapTableCell;
window.FloatingUI = FloatingUI;

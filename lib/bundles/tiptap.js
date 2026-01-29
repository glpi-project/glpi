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

// Helper to resolve ESM default/named exports from CommonJS require()
const resolve = (mod, name) => mod.default || mod[name] || mod;

// Core - keep full module for Editor class and utilities
const TiptapCore = require('@tiptap/core');

// Extensions - resolve to actual extension class
const TiptapStarterKit = resolve(require('@tiptap/starter-kit'), 'StarterKit');
const TiptapLink = resolve(require('@tiptap/extension-link'), 'Link');
const TiptapImage = resolve(require('@tiptap/extension-image'), 'Image');
const TiptapPlaceholder = resolve(require('@tiptap/extension-placeholder'), 'Placeholder');
const TiptapSuggestion = resolve(require('@tiptap/suggestion'), 'Suggestion');
const TiptapBubbleMenu = resolve(require('@tiptap/extension-bubble-menu'), 'BubbleMenu');

// Table extensions
const TiptapTable = resolve(require('@tiptap/extension-table'), 'Table');
const TiptapTableRow = resolve(require('@tiptap/extension-table-row'), 'TableRow');
const TiptapTableHeader = resolve(require('@tiptap/extension-table-header'), 'TableHeader');
const TiptapTableCell = resolve(require('@tiptap/extension-table-cell'), 'TableCell');

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

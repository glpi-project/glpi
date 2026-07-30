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
const TiptapImage = resolve(require('@tiptap/extension-image'), 'Image');
const TiptapPlaceholder = resolve(require('@tiptap/extension-placeholder'), 'Placeholder');
const TiptapSuggestion = resolve(require('@tiptap/suggestion'), 'Suggestion');
const TiptapBubbleMenu = resolve(require('@tiptap/extension-bubble-menu'), 'BubbleMenu');

// Table extensions
const TableKit = resolve(require('@tiptap/extension-table'), 'TableKit');

// File handler for drag & drop / paste image uploads
const TiptapFileHandler = resolve(require('@tiptap/extension-file-handler'), 'FileHandler');

// ProseMirror primitives for table grips (decorations, cell selection, structural edits)
const TiptapPMState = require('@tiptap/pm/state');
const TiptapPMView = require('@tiptap/pm/view');
const TiptapPMTables = require('@tiptap/pm/tables');

// Floating UI for popups (slash commands menu)
const FloatingUI = require('@floating-ui/dom');

// Expose to global scope
window.TiptapCore = TiptapCore;
window.TiptapStarterKit = TiptapStarterKit;
window.TiptapImage = TiptapImage;
window.TiptapPlaceholder = TiptapPlaceholder;
window.TiptapSuggestion = TiptapSuggestion;
window.TiptapBubbleMenu = TiptapBubbleMenu;
window.TableKit = TableKit;
window.TiptapFileHandler = TiptapFileHandler;
window.TiptapPMState = TiptapPMState;
window.TiptapPMView = TiptapPMView;
window.TiptapPMTables = TiptapPMTables;
window.FloatingUI = FloatingUI;

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

require('../../../js/modules/kanban.js');

// Set some constants
window.KanbanTestEnv = {};
window.KanbanTestEnv.test_users_id = 1;
window.KanbanTestEnv.test_entities_id = 1;
window.KanbanTestEnv.test_projects_id = 1000;
window.KanbanTestEnv.project_supported_itemtypes = {
   Project: {
      name: 'Project',
      icon: 'fas fa-columns',
      fields: {
         projects_id: {
            type: 'hidden',
            value: window.KanbanTestEnv.test_projects_id
         },
         name: {
            placeholder: 'Name'
         },
         content: {
            placeholder: 'Content',
            type: 'textarea'
         },
         users_id: {
            type: 'hidden',
            value: window.KanbanTestEnv.test_users_id
         },
         entities_id: {
            type: 'hidden',
            value: window.KanbanTestEnv.test_entities_id
         },
         is_recursive: {
            type: 'hidden',
            value: 0
         }
      }
   },
   ProjectTask: {
      name: 'Project',
      icon: 'fas fa-columns',
      fields: {
         projects_id: {
            type: 'hidden',
            value: window.KanbanTestEnv.test_projects_id
         },
         name: {
            placeholder: 'Name'
         },
         content: {
            placeholder: 'Content',
            type: 'textarea'
         },
         projecttasktemplates_id: {
            type: 'hidden',
            value: 0
         },
         projecttasks_id: {
            type: 'hidden',
            value: 0
         },
         entities_id: {
            type: 'hidden',
            value: window.KanbanTestEnv.test_entities_id
         },
         is_recursive: {
            type: 'hidden',
            value: 0
         }
      }
   }
};

window.KanbanTestEnv.TestKanban = new window.GLPIKanban({
   element: "#kanban",
   rights: {
      create_item: true,
      delete_item: true,
      create_column: true,
      modify_view: true,
      order_card: true,
      create_card_limited_columns: [],
   },
   supported_itemtypes: window.KanbanTestEnv.project_supported_itemtypes,
   dark_theme: true,
   max_team_images: 4,
   column_field: {
      id: 'projectstates_id',
      extra_fields: {
         color: {
            type: 'color'
         }
      }
   },
   background_refresh_interval: 0,
   item: {
      itemtype: 'Project',
      items_id: window.KanbanTestEnv.test_projects_id
   }
});

window.AjaxMock.start();

window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/kanban.php', 'GET', {
   action: 'get_switcher_dropdown'
}, () => {
   return `<select name='kanban-board-switcher' id='dropdown_kanban-board-switcher935502599' size='1'>
      <option value='-1'>Global</option>
      <option value='1' selected>Test Kanban</option>
   </select>`;
}));

window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/kanban.php', 'GET', {
   action: 'refresh'
}, () => {
   return {
      10: {
         name: 'Test column 0',
         _protected: true,
         header_color: '#FF0000',
         items: {
            0: {
               id: 'Project-10',
               title: 'Test project',
               title_tooltip: 'This is a test project',
               content: `<div class='kanban-plugin-content'></div>
      <div class='kanban-core-content'>
         <div class='flex-break'></div>
         <i class='fas fa-map-signs' title='Milestone'></i>
         <div class='flex-break'></div>
         <progress id='progress2129652812' class='sr-only' max='100' value='30' onchange='updateProgress("2129652812")' title='30%'></progress>
         <div aria-hidden='true' data-progressid='2129652812' data-append-percent='1' class='progress' title='30%'>
            <span aria-hidden='true' class='progress-fg' style='width: 30%'></span>
         </div>
      </div>
      <div class='kanban-plugin-content'></div>`,
               _readonly: true,
               is_deleted: true,
               _form_link: 'http://localhost/front/project.form.php?id=10'
            }
         }
      }
   };
}));

// Replace init function to be able to mock AJAX call(s) and prepare the DOM
window.KanbanTestEnv.TestKanban._init = window.KanbanTestEnv.TestKanban.init;
window.KanbanTestEnv.TestKanban.init = () => {
   // Add a Kanban board element
   document.body.innerHTML = `<html><body><div id='kanban' class='kanban'></div></body></html>`;
   window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/kanban.php', 'GET', {
      action: 'load_column_state'
   }, (data) => {
      return {
         timestamp: new Date().toLocaleString(),
         state: null
      };
   }, true));
   window.KanbanTestEnv.TestKanban._init();
};

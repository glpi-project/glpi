window.$ = window.jQuery = require('jquery');
require('../../../js/modules/kanban.js');

// Set faux CFG_GLPI variable. We cannot get the real values since they are set inline in PHP.
window.CFG_GLPI = {
   root_doc: '/'
};

// Add a Kanban board element
document.body.innerHTML = `<div id='kanban' class='kanban'></div>`;

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

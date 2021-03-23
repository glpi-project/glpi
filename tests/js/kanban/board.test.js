describe('Kanban board', () => {

   test('Kanban board initial properties', () => {
      // Test board property initialization
      expect(window.KanbanTestEnv.TestKanban.element).toBe('#kanban');
      expect(window.KanbanTestEnv.TestKanban.ajax_root).toBe('//ajax/');
      expect(window.KanbanTestEnv.TestKanban.item).toStrictEqual({
         itemtype: 'Project',
         items_id: window.KanbanTestEnv.test_projects_id
      });
      expect(window.KanbanTestEnv.TestKanban.config.dark_theme).toBe(true);
      expect(window.KanbanTestEnv.TestKanban.config.supported_itemtypes).toStrictEqual(window.KanbanTestEnv.project_supported_itemtypes);
      expect(window.KanbanTestEnv.TestKanban.config.column_field).toStrictEqual({
         id: 'projectstates_id',
         extra_fields: {
            color: {
               type: 'color'
            }
         }
      });
      expect(window.KanbanTestEnv.TestKanban.config.show_toolbar).toBe(true);
      expect(window.KanbanTestEnv.TestKanban.config.background_refresh_interval).toBe(0);
      expect(window.KanbanTestEnv.TestKanban.config.max_team_images).toBe(4);

      // Test rights
      const rights = window.KanbanTestEnv.TestKanban.rights;
      expect(rights.canCreateItem()).toBe(true);
      expect(rights.canDeleteItem()).toBe(true);
      expect(rights.canCreateColumn()).toBe(true);
      expect(rights.canModifyView()).toBe(true);
      expect(rights.canOrderCard()).toBe(true);
      expect(rights.getAllowedColumnsForNewCards()).toStrictEqual([]);
   });

   // We are going to use the DOM now, so the Kanban needs initialized
   window.KanbanTestEnv.TestKanban.init();

   test('Kanban element', () => {
      const kanban_el = $(window.KanbanTestEnv.TestKanban.element);
      expect(kanban_el.length).toBe(1);
      expect(kanban_el.find('.kanban-container').length).toBe(1);
   });
});

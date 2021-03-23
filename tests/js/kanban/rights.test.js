import KanbanRights from "../../../js/modules/kanban/rights";

describe('Kanban rights', () => {
   test('constructor', () => {
      let rights = new KanbanRights();
      expect(rights.canCreateItem()).toBe(false);
      expect(rights.canDeleteItem()).toBe(false);
      expect(rights.canCreateColumn()).toBe(false);
      expect(rights.canModifyView()).toBe(false);
      expect(rights.canOrderCard()).toBe(false);
      expect(rights.getAllowedColumnsForNewCards()).toStrictEqual([]);

      rights = new KanbanRights({
         'create_item': true,
         'delete_item': true,
         'create_column': true,
         'modify_view': true,
         'order_card': true,
         'create_card_limited_columns': [0, 2, 4]
      });
      expect(rights.canCreateItem()).toBe(true);
      expect(rights.canDeleteItem()).toBe(true);
      expect(rights.canCreateColumn()).toBe(true);
      expect(rights.canModifyView()).toBe(true);
      expect(rights.canOrderCard()).toBe(true);
      expect(rights.getAllowedColumnsForNewCards()).toStrictEqual([0, 2, 4]);
   });
});

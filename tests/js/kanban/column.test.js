import KanbanCard from "../../../js/modules/kanban/card.js";

require('./bootstrap.js');
import KanbanColumn from "../../../js/modules/kanban/column.js";

describe('Kanban column', () => {
   afterEach(() => {
      jest.resetAllMocks();
   });

   const column0 = new KanbanColumn({
      id: 10,
      board: window.KanbanTestEnv.TestKanban,
      name: 'Test Column 0',
      header_color: '#FF0000',
      _protected: true,
      folded: true,
      cards: []
   });
   const column1 = new KanbanColumn({
      id: 11,
      board: window.KanbanTestEnv.TestKanban,
      name: 'Test Column 1'
   });

   test('Initial properties', () => {
      expect(column0.getID()).toBe(10);
      expect(column0.getName()).toBe('Test Column 0');
      expect(column0.getHeaderColor()).toBe('#FF0000');
      expect(column0.getProtected()).toBe(true);
      expect(column0.collapsed).toBe(true);
      expect(column0.cards).toStrictEqual([]);

      expect(column1.getID()).toBe(11);
      expect(column1.getName()).toBe('Test Column 1');
      expect(column1.getHeaderColor()).toBe('transparent');
      expect(column1.getProtected()).toBe(false);
      expect(column1.collapsed).toBe(false);
      expect(column1.cards).toStrictEqual([]);
   });

   test('setters', () => {
      column0.setName('Test Column 0 Modified');
      column0.setHeaderColor('#00FF00');
      column0.setProtected(false);
      expect(column0.name).toBe('Test Column 0 Modified');
      expect(column0.header_color).toBe('#00FF00');
      expect(column0.protected).toBe(false);

      //Reset protected value for other tests
      column0.setProtected(true);
   });

   test('getElement', () => {
      expect(column0.getElement()).toBe('#column-projectstates_id-10');
      expect(column1.getElement()).toBe('#column-projectstates_id-11');
   });

   test('getIDFromElement', () => {
      expect(KanbanColumn.getIDFromElement('#column-projectstates_id-10')).toBe(10);
      expect(KanbanColumn.getIDFromElement('#column-projectstates_id-11')).toBe(11);

      expect(KanbanColumn.getIDFromElement($('<div id="column-projectstates_id-10"></div>'))).toBe(10);
      expect(KanbanColumn.getIDFromElement($('<div id="column-projectstates_id-11"></div>'))).toBe(11);
   });

   test('show', () => {
      const ajaxSpy = jest.spyOn($, 'ajax');
      column0.show();
      expect(ajaxSpy).toBeCalledWith({
         type: 'POST',
         url: column0.board.ajax_root + 'kanban.php',
         data: {
            action: "show_column",
            column: column0.id,
            kanban: column0.board.item
         },
         contentType: 'application/json',
         complete: expect.any(Function)
      });
   });

   test('hide', () => {
      const ajaxSpy = jest.spyOn($, 'ajax');
      column0.hide();
      expect(ajaxSpy).toBeCalledWith({
         type: 'POST',
         url: column0.board.ajax_root + 'kanban.php',
         data: {
            action: "hide_column",
            column: column0.id,
            kanban: column0.board.item
         },
         contentType: 'application/json',
         complete: expect.any(Function)
      });
   });

   test('toggleCollapse', () => {
      const ajaxSpy = jest.spyOn($, 'ajax');
      column0.toggleCollapse();
      expect(ajaxSpy).toBeCalledWith({
         type: 'POST',
         url: column0.board.ajax_root + 'kanban.php',
         data: {
            action: "expand_column",
            column: column0.id,
            kanban: column0.board.item
         },
         contentType: 'application/json'
      });

      column1.toggleCollapse();
      expect(ajaxSpy).toBeCalledWith({
         type: 'POST',
         url: column1.board.ajax_root + 'kanban.php',
         data: {
            action: "collapse_column",
            column: column1.id,
            kanban: column1.board.item
         },
         contentType: 'application/json'
      });
   });
});

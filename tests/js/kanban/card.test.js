import KanbanColumn from "../../../js/kanban/column";

require('./bootstrap.js');
import KanbanCard from "../../../js/kanban/card";

describe('Kanban card', () => {
   const column0 = new KanbanColumn({
      id: 10,
      board: window.KanbanTestEnv.TestKanban,
      name: 'Test Column 0',
      header_color: '#FF0000',
      _protected: true,
      folded: true,
      cards: []
   });

   const card0 = new KanbanCard(column0, {
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
      readonly: true,
      is_deleted: true,
      _form_link: 'http://localhost/front/project.form.php?id=10'
   });

   const card1 = new KanbanCard(column0, {
      id: 'Project-11',
      _form_link: 'http://localhost/front/project.form.php?id=11'
   });

   test('Initial properties', () => {
      expect(card0.getID()).toBe('Project-10');
      expect(card0.title).toBe('Test project');
      expect(card0.title_tooltip).toBe('This is a test project');
      expect(card0.content).toBe(`<div class='kanban-plugin-content'></div>
      <div class='kanban-core-content'>
         <div class='flex-break'></div>
         <i class='fas fa-map-signs' title='Milestone'></i>
         <div class='flex-break'></div>
         <progress id='progress2129652812' class='sr-only' max='100' value='30' onchange='updateProgress("2129652812")' title='30%'></progress>
         <div aria-hidden='true' data-progressid='2129652812' data-append-percent='1' class='progress' title='30%'>
            <span aria-hidden='true' class='progress-fg' style='width: 30%'></span>
         </div>
      </div>
      <div class='kanban-plugin-content'></div>`);
      expect(card0.readonly).toBe(true);
      expect(card0.is_deleted).toBe(true);
      expect(card0.form_link).toBe('http://localhost/front/project.form.php?id=10');

      expect(card1.getID()).toBe('Project-11');
      expect(card1.title).toBe('');
      expect(card1.title_tooltip).toBe('');
      expect(card1.content).toBe('');
      expect(card1.readonly).toBe(false);
      expect(card1.is_deleted).toBe(false);
      expect(card1.form_link).toBe('http://localhost/front/project.form.php?id=11');
   });
});

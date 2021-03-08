require('./bootstrap.js');

describe('Kanban Global', () => {
   test('Global board assignment', () => {
      expect(window.GLPIKanban).toBeDefined();
   });
});

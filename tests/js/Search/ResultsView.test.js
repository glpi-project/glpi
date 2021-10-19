require('../../../js/modules/Search/ResultsView.js');
require('../../../js/modules/Search/Table.js');

describe('Search ResultsView', () => {
   beforeEach(() => {
      jest.clearAllMocks();
   });
   $(document.body).append(`
    <div class="ajax-container search-display-data">
        <form id="massformComputer" data-search-itemtype="Computer">
            <div class="table-responsive-md">
                <table id="search_9439839" class="search-results">
                </table>
            </div>
        </form>
    </div>
`);

   const results_view = new GLPI.Search.ResultsView('massformComputer', GLPI.Search.Table);
   test('Class exists', () => {
      expect(GLPI).toBeDefined();
      expect(GLPI.Search).toBeDefined();
      expect(GLPI.Search.ResultsView).toBeDefined();
   });
   test('getView', () => {
      expect(results_view.getView() instanceof GLPI.Search.Table).toBeTrue();
   });
});

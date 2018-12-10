/**
 * Adapted from https://github.com/kurtnovack/stickytoolbar
 */
tinymce.PluginManager.add('stickytoolbar', function(editor, url) {
   var offset = editor.settings.sticky_offset ? editor.settings.sticky_offset : 0;

   editor.on('init', function() {
      editor.setSticky();
   });

   $(window).on('scroll', function() {
      editor.setSticky();
   });

   editor.setSticky = function () {
      offset = editor.settings.sticky_offset ? editor.settings.sticky_offset : 0;
      var container = editor.editorContainer;
      var toolbars = $(container).find('.mce-toolbar-grp');
      var statusbar = $(container).find('.mce-statusbar');

      if (editor.isSticky()) {
         $(container).css({
            paddingTop: toolbars.outerHeight()
         });

         if (editor.isAtBottom()) {
            toolbars.css({
               top: 'auto',
               bottom: statusbar.outerHeight(),
               position: 'absolute',
               width: '100%',
               borderBottom: 'none'
            });
         } else {
            toolbars.css({
               top: offset,
               bottom: 'auto',
               position: 'fixed',
               width: $(container).width(),
               borderBottom: '1px solid rgba(0,0,0,0.2)'
            });
         }
      } else {
         $(container).css({
            paddingTop: 0
         });

         toolbars.css({
            top: 0,
            position: 'relative',
            width: 'auto',
            borderBottom: 'none'
         });
      }
   };

   editor.isSticky = function () {
      var container = editor.editorContainer,
          editorTop = container.getBoundingClientRect().top;

      if (editorTop < offset) {
         return true;
      }

      return false;
   };

   editor.isAtBottom = function () {
      const container = editor.getContainer();

      const editorPosition = container.getBoundingClientRect().top,
            statusbar = container.querySelector('.mce-statusbar'),
            topPart = container.querySelector('.mce-top-part');

      const statusbarHeight = statusbar ? statusbar.offsetHeight : 0,
            topPartHeight = topPart ? topPart.offsetHeight : 0;

      const stickyHeight = -(container.offsetHeight - topPartHeight - statusbarHeight);

      if (editorPosition < stickyHeight + offset) {
         return true;
      }

      return false;
   };
});

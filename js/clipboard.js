$(function() {
   // set a function to track drag hover event
   $(document).on("click", ".copy_to_clipboard_wrapper", function(event) {

      // find the good element
      var target = $(event.target);
      if (target.attr('class') == 'copy_to_clipboard_wrapper') {
         target = target.find('*');
      }

      // copy text
      target.select();
      var succeed;
      try {
         succeed = document.execCommand("copy");
      } catch(e) {
         succeed = false;
      }
      target.blur();

      // indicate success
      if (succeed) {
         $('.copy_to_clipboard_wrapper.copied').removeClass('copied');
         target.parent('.copy_to_clipboard_wrapper').addClass('copied');
      } else {
         target.parent('.copy_to_clipboard_wrapper').addClass('copyfail');
      }
   });
});

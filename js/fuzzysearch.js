$(document).ready(function() {
   // when the shortcut for fuzzy is called
   $(document).bind('keyup', 'alt+ctrl+g', function() {
      console.log('start fuzzy search');

      // retrieve html of fuzzy input
      $.get(CFG_GLPI.root_doc+'/ajax/fuzzysearch.php', {
         'action': 'getHtml'
      }, function(html) {
         $(document.body).append(html);

         // when a key is pressed in fuzzy input, launch match
         $("#fuzzysearch input").focus()
            .bind('keyup', function(key) {
            switch (key.key) {
               case "Escape":
                  removeFuzzy();
                  break;

               case "ArrowUp":
                  selectPrev();
                  break;

               case "ArrowDown":
                  selectNext();
                  break;

               case "Enter":
                  // find url, if one selected, go for it, else try to find first element
                  var url = $("#fuzzysearch .results .selected a").attr('href');
                  if (url == undefined) {
                     url = $("#fuzzysearch .results li:first a").attr('href');
                  }
                  if (url != undefined) {
                     document.location = CFG_GLPI.root_doc+url;
                  }
                  break;

               default:
                  startFuzzy();
                  break;
            }
         })

         // when losing focus on input, remove fuzzy input
         .focusout(function() {
            removeFuzzy();
         });

         setTimeout(function() {
            if ($("#fuzzysearch .results li").length == 0) {
               startFuzzy();
            }
         }, 100);
      });
   });

   var startFuzzy = function() {
      // prepapre options for fuzzy lib
      var fuzzy_options = {
         pre: "<b>",
         post: "</b>",
         extract: function(el) {
            return el.title;
         }
      };

      // retrieve input
      var input_text = $("#fuzzysearch input").val();

      // retrieve list of possible navigation
      $.getJSON(CFG_GLPI.root_doc+'/ajax/fuzzysearch.php', {
         'action': 'getList'
      }, function(list) {
         //clean old results
         $("#fuzzysearch .results").empty();

         // launch fuzzy search on this list
         var results = fuzzy.filter(input_text, list, fuzzy_options);

         // append new results
         results.map(function(el) {
            //console.log(el.string);
            $("#fuzzysearch .results")
               .append("<li><a href='"+el.original.url+"'>"+el.string+"</a></li>")
         });
      });
   };

   var removeFuzzy = function() {
      $("#fuzzysearch, .fuzzymodal").remove();
   };

   var selectNext = function() {
      if ($("#fuzzysearch .results .selected").length == 0) {
         $("#fuzzysearch .results li:first()").addClass("selected");
      } else  {
         $("#fuzzysearch .results .selected:not(:last-child)")
            .removeClass('selected')
            .next()
            .addClass("selected");
      }

      scrollToSelected();
   };

   var selectPrev = function() {
      if ($("#fuzzysearch .results .selected").length == 0) {
         $("#fuzzysearch .results li:last()").addClass("selected");
      } else  {
         $("#fuzzysearch .results .selected:not(:first-child)")
            .removeClass('selected')
            .prev()
            .addClass("selected");
      }
      scrollToSelected();
   };

   var scrollToSelected = function() {
      var results = $("#fuzzysearch .results");
      var selected = results.find('.selected');

      results.scrollTop(results.scrollTop() + selected.position().top - results.height()/2 + selected.height()/2);
   };
});

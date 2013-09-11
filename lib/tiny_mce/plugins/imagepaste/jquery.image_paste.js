// IE detection
function isIE () {
   var myNav = navigator.userAgent.toLowerCase();
   return (myNav.indexOf('msie') != -1) ? parseInt(myNav.split('msie')[1]) : false;
}


/**
 * imagePaste : Jquery plugin for CHROME and FIREFOX. It paste and send image from clipboard 
 *
 * @param params     Json     - image_name : Upload image name
 *                            - name : Name of the textarea
 *                            - modalName : Name of the modal window
 *                            - errorMsg : Message to display on init error
 *                            - initMsg : Message to display on init
 *                            - root_doc : Root of glpi
 *
 * @return plugin object
 **/
(function($) {
   $.fn.imagePaste = function(params) {
      
      init();// Start the plugin
      var imagepaste = this;
      
      function init(){
         
         this.textarea = document.getElementById(params.name+"_ifr");
         this.pasteddata = '';

         $('#image_paste').append("<div id='image_paste_result' contenteditable='true' style='display:none'></div>\n\
               <div id='desc_paste_image' \n\
                  class='tracking left' \n\
                  contenteditable='true' \n\
                  onpaste='imagePaste.handlepaste(this, event)'>"+params.initMsg+"</div>");

         $('#desc_paste_image').css({
            'border':'1px solid #ABADB3',
            'background-color' : 'white',
            'height':'415px',
            'width':'570px',
            'overflow':'auto'
         }).focus(function(){
            $(this).css({
               'border':'1px solid red'
            });
         }).focus();
         
         $(document).click(function(){
            $('#desc_paste_image').focus();
         });
         
         $('#image_paste_result').css({
            'margin':'2px 0px 2px 0px'
         });
         
         $('#image_paste a').css({
            'margin-right':'5px'
         });
         
         $('#paste_image_menu').css({
            'margin-bottom':'5px'
         });

         this.btnClear = $('#clear_image');
         this.btnClear.click(function() {
            clearImage();
         });

         this.btnUpload = $('#upload_image');
         this.btnUpload.click(function() {
            stockImage();
         });
      }
      
      function error(){
         $('#image_paste_result').html("<span class='red'>"+params.errorMsg+"</span>").show().delay(2000).fadeOut('slow');
      }
   
      this.handlepaste = function(elem, e) {
         var savedcontent = $(elem).html();
         if (e && e.clipboardData && e.clipboardData.getData && e.clipboardData.items) {
            // Webkit - get data from clipboard, put into editdiv, cleanup, then cancel event
            var items = e.clipboardData.items;
            
            if(items != undefined){
               for (var i = 0; i < items.length; ++i) {
                  if (items[i].kind == 'file' &&
                     items[i].type.indexOf('image/') !== -1) {

                     var blob = items[i].getAsFile();

                     var reader = new FileReader();
                     reader.onload = function(event){
                        $(elem).html(event.target.result); //event.target.results contains the base64 code to create the image.
                        processpaste(elem, savedcontent);
                     };
                     reader.readAsDataURL(blob);//Convert the blob from clipboard to base64
                  } else {
                     error();
                  }
               }
            }

            //waitforpastedata(elem, savedcontent, params.initMsg);
            if (e.preventDefault) {
               e.stopPropagation();
               e.preventDefault();
            }
            return false;
         } else {// Everything else - empty editdiv and allow browser to paste content into it, then cleanup
            $(elem).html("");
            waitforpastedata(elem, savedcontent, params.initMsg);
            return true;
         }
      }

      function waitforpastedata(elem, savedcontent, initMsg) {
         if (elem.childNodes && elem.childNodes.length > 0) {
            if(elem.firstChild.tagName === "IMG") 
               processpaste(elem, savedcontent);
            else {
               $(elem).html(initMsg);
               error();
               return false;
            }               
         } else {
            that = {
               e: elem, 
               s: savedcontent
            }
            that.callself = function () {
               waitforpastedata(that.e, that.s, initMsg)
            }
            setTimeout(that.callself,20);
         }
      }

      function processpaste(elem, savedcontent) {
         // If image element, we get the src
         if(elem.firstChild != undefined && elem.firstChild.tagName === "IMG"){
            this.pasteddata = elem.firstChild.src;
         } else {// else text element
            this.pasteddata = $(elem).html();
         }

         if(this.pasteddata != ''){
            $(elem).html(savedcontent);

            var img = document.createElement('img');
            img.src = this.pasteddata;
            img.id = 'pasteddata';
            $('#desc_paste_image').html(img);
            
            // Crop image paste
            if(jQuery().Jcrop) {
               jQuery(function($){
                  $('#pasteddata').Jcrop({
                     onSelect: imagepaste.setCoords, onChange: imagepaste.setCoords
                  });
               });
            }
            
            this.btnUpload.show()
            this.btnClear.show()
         }
      }
         
      this.setCoords = function(c){
         imagepaste.imageCoordinates = {'img_x':c.x, 'img_y':c.y, 'img_w':c.w, 'img_h':c.h};
      };
      
      function clearImage() {
         this.btnClear.hide();
         this.btnUpload.hide();
         $('#desc_paste_image').html(params.initMsg);
      } 
      
      function stockImage(){

         if(this.textarea != null) {
            // Stock the image bytes
            $(this.textarea).append('<input type="hidden" name="stock_image['+params.image_name+'][data]" value="'+this.pasteddata+'"/>');
            if(imagepaste.imageCoordinates != undefined || imagepaste.imageCoordinates != null){
               $(this.textarea).append('<input type="hidden" name="stock_image['+params.image_name+'][coordinates]" value="'+encodeURIComponent(JSON.stringify(imagepaste.imageCoordinates))+'"/>');
            }
            
            // Insert the tag in textarea
            this.textarea.contentWindow.document.body.innerHTML += '#'+params.image_name;
            // Close modal window
            $('#'+params.modalName).dialog('close');
         }
      }
      
         
      return this;
   }
}(jQuery));
   
/**
 * imagePaste : Jquery plugin for IE. It paste and send image from clipboard 
 *
 * @param params     Json     - image_name : Upload image name
 *                            - name : Name of the textarea
 *                            - modalName : Name of the modal window
 *                            - errorMsg : Message to display on error
 *                            - initMsg : Message to display on init
 *                            - root_doc : Root of glpi
 *
 * @return plugin object
 **/
(function($) {
   $.fn.IE_support_imagePaste = function(params) {
      
      init();// Start the plugin
      
      function init(){
         
         this.textarea = document.getElementById(params.name+"_ifr");
         
         $('#image_paste').append("<form name='image_paste_form' action='#none'>\n\
            <div id='image_paste_result' contenteditable='true' style='display:none'></div>\n\
            <div>\n\
               <applet id='desc_paste_image'\n\
                       archive='"+params.root_doc+"/lib/tiny_mce/plugins/imagepaste/IE_support/Supa.jar'\n\
                       code='de.christophlinder.supa.SupaApplet'>\n\
                  <param name='trace' value='true'>\n\
                  <param name='imagecodec' value='png'>\n\
                  <param name='encoding' value='base64'>\n\
                  <param name='previewscaler' value='fit to canvas'>\n\
               </applet> \n\
            </div>\n\
         </form>");

         $('#desc_paste_image').css({
            'border':'1px solid #ABADB3',
            'margin-top':'5px',
            'height':'415px',
            'width':'570px'
         });
         
         $('#image_paste_result').css({
            'margin':'2px 0px 2px 0px'
         });
         
         $('#image_paste a').css({
            'margin-right':'5px'
         });
      }
      
      function error(){
         $('#image_paste_result').html("<span class='red'>"+params.errorMsg+"</span>").show().delay(2000).fadeOut('slow');
      }
   
      function Supa() {
         this.ping = function (supaApplet) {
            try {
               // IE will throw an exception if you try to access the method in a 
               // scalar context, i.e. if( supaApplet.pasteFromClipboard ) ...
               return supaApplet.ping();
            } catch (e) {
               return false;
            }
         };

         this.isArray = function(obj) {
            if (obj.constructor.toString().indexOf("Array") == -1)
               return false;
            else
               return true;
         };
      };

      // TODO: This is here for some backwards compatibility and should go away with
      // one of the next releases
      function supa() {
         return new Supa();
      };
      
      $('#paste_image').click(function() {
         handlepasteIE();
      }).show();

      function handlepasteIE() {
         var s = new Supa();
         // Call the paste() method of the applet.
         // This will paste the image from the clipboard into the applet :)
         var applet = document.getElementById("desc_paste_image");
         $('#image_paste_result').html('').hide();
         
         try {

            if(!s.ping(applet)) {
               throw "SupaApplet is not loaded (yet)";
            }

            var err = applet.pasteFromClipboard(); 
            switch(err) {
               // No errors : applet is loaded
               case 0:
               
                  var btnClear = $('#clear_image');
                  btnClear.click(function() {
                     clearImage();
                     btnClear.hide();
                     btnUpload.hide();
                  });

                  var btnUpload = $('#upload_image');
                  btnUpload.click(function() {
                     stockImage(applet.getEncodedString());
                     btnClear.hide();
                     btnUpload.hide();
                  });
                  $('#paste_image').hide();
                  btnClear.show();
                  btnUpload.show();
                  break;
//               case 1:
//                  alert("Unknown Error");
//                  break;
//               case 2:
//                  alert("Empty clipboard");
//                  break;
//               case 3:
//                  alert("Clipboard content not supported. Only image data is supported.");
//                  break;
//               case 4:
//                  alert("Clipboard in use by another application. Please try again in a few seconds.");
//                  break;
               default:
                  error();
            }
         } catch(e) {
//            alert(e);
            throw e;
         }
      }
      
      function stockImage(pasteddata){
         if(this.textarea != null) {
            // Stock the image bytes
            $(this.textarea).append('<input type="hidden" name="stock_image['+params.image_name+'][data]" value="'+escape(pasteddata)+'"/>');

            // Input to handle IE file decoding in php treatment
            $(this.textarea).append('<input type="hidden" name="IE_support" value="true"/>');
            
            // Insert the tag in textarea
            this.textarea.contentWindow.document.body.innerHTML += '#'+params.image_name;
            // Close modal window
            $('#'+params.modalName).dialog('close');
         }
      }
         
      function clearImage() {
         document.getElementById("desc_paste_image").clear();
      } 

      return this;
   }
}(jQuery));



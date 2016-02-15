/**
 * imagePaste : Jquery plugin for CHROME and FIREFOX. Paste and send an image from clipboard 
 *
 * @param params     Json     - name                string   Name of the textarea
 *                            - filename            string   Generated name of the file
 *                            - lang                json     locales loaded in plugin
 *                            - root_doc            string   Root of glpi
 *                            - showfilesize        boolean  show file size with file name
 *                                                           use selector to display
 *                            - rand                int
 *
 * @return plugin object
 **/
(function($) {
   $.fn.imagePaste = function(params) {
 
      var imagepaste = this;
      init();// Start the plugin

      function init(){
         
         this.pasteddata = '';
         this.file       = '';
         
         $('#image_paste').append(
               "<div id='desc_paste_image' \n\
                  contenteditable='true' \n\
                  onpaste='tinyMCE.imagePaste.handlepaste(this, event)'><b>"+params.lang.pasteimage+"</b></div>\n\
               <input style='display:none' id='fileupload"+params.rand+"' type='file' name='stock_image[]' \n\
                  data-url='"+params.root_doc+"/front/fileupload.php?name=stock_image&showfilesize="+params.showfilesize+"'>\n\
               <div id='image_paste_result' style='display:none'></div>\n\
               <div id='image_paste_result2'>\n\
                  <div id='progress"+params.rand+"' style='display:none'>\n\
                     <div class='uploadbar' style='width: 0%;'></div>\n\
                  </div>\n\
                  <div id='filedata"+params.rand+"'></div>\n\
               </div>\n\
               <div id='paste_image_menu' class='center' style='display:none'> \n\
                  <a id='upload_image' class='vsubmit'>"+params.lang.save+"</a>\n\
                  <a id='clear_image' class='vsubmit'>"+params.lang.cancel+"</a>\n\
               </div>"
         );
         // Try to put CSS on main CSS page
         $('#desc_paste_image').focusout(function(){
            $('#image_paste').css({
               'border':'1px dashed #cccccc'
            });
         });
         
         $('#image_paste').css({
            'cursor':'pointer',
            'min-height': '65px',
            'max-height': '500px'
         });
         
         $('#desc_paste_image').css({
            'max-height': '475px',
            'min-height': '67px',
            'overflow':'auto'
         }).focus(function(){
            $('#image_paste').css({
               'border':'1px dashed orange'
            });
         });
         
         $('#image_paste_result').css({
            'margin':'2px 0px 2px 0px'
         });
         
         $('#paste_image_menu').css({
            'margin-top':'5px',
            'height':'25px'
         });

         imagepaste.btnClear = $('#clear_image');
         imagepaste.btnClear.click(function() {
            clearImage();
         });

         imagepaste.btnUpload = $('#upload_image');
         imagepaste.btnUpload.click(function() {
            stockImage();
         });
         
         imagepaste.menu = $('#paste_image_menu');
         
         // Handle ctrl+v if needed
         if($('#desc_paste_image').is(":focus")) {
            $(document).keydown(function(e){
               if (e.ctrlKey && (e.keyCode == 86)) {
                  imagepaste.handlepaste($('#desc_paste_image'), e);
               }
            });
         }
         // Handle drop
         $( "#image_paste" ).bind( "drop", function(event, ui) {
             $('#desc_paste_image').focus();
         });

      }
      
      function error(errorMsg){
         $('#image_paste_result').html("<b class='red'>"+errorMsg+"</b>").show().delay(2000).fadeOut('slow');
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
                        imagepaste.processpaste(elem, savedcontent);
                     };
                     reader.readAsDataURL(blob);//Convert the blob from clipboard to base64
                  } else {
                     error(params.lang.itemnotfound);
                  }
               }
            }

            //waitforpastedata(elem, savedcontent, params.lang.pasteimage);
            if (e.preventDefault) {
               e.stopPropagation();
               e.preventDefault();
            }
            return false;
         } else {// Everything else - empty editdiv and allow browser to paste content into it, then cleanup
            $(elem).html('');
            waitforpastedata(elem, savedcontent);
            $(elem).html('<b>'+savedcontent+'</b>');
            return true;
         }
      }

      function waitforpastedata(elem, savedcontent) {
         if (elem.childNodes && elem.childNodes.length > 0) {
            if(elem.firstChild.tagName === "IMG") 
               imagepaste.processpaste(elem, savedcontent);
            else {
               $(elem).html('<b>'+savedcontent+'</b>');
               error(params.lang.itemnotfound);
               return false;
            }               
         } else {
            that = {
               e: elem, 
               s: savedcontent
            }
            that.callself = function () {
               waitforpastedata(that.e, that.s)
            }
            setTimeout(that.callself,20);
         }
      }

      this.processpaste = function(elem, savedcontent, file) {
         // File element
         if(file != undefined && file != null){
            imagepaste.file = file;
         }
         
         // If image element, we get the src
         if(elem.firstChild != undefined && elem.firstChild.tagName === "IMG"){
            imagepaste.pasteddata = elem.firstChild.src;
         } else {// else text element
            imagepaste.pasteddata = $(elem).html();
         }
         
         // Check mime
         var mime_ok = false;
         switch(imagepaste.pasteddata.substr(0, imagepaste.pasteddata.indexOf(';'))){
             case 'data:image/gif':case 'data:image/jpg':case 'data:image/jpeg':case 'data:image/png':
                mime_ok = true;
                break;
         }
         
         if(imagepaste.pasteddata != '' && mime_ok){
            $(elem).html('<b>'+savedcontent+'<b>');

            var img = document.createElement('img');
            img.src = imagepaste.pasteddata;
            imagepaste.pasteddata = undefined;
            img.id = 'pasteddata';

            $('#desc_paste_image').html(img);

            $($('#pasteddata')).load(function() {
               if($('#pasteddata').height() > params.maxsize || $('#pasteddata').width() > params.maxsize){
                  error(params.lang.toolarge);
                  clearImage();
                  
               } else {
                  // Crop image paste
                  if(jQuery().Jcrop) {
                     jQuery(function($){
                        $('#pasteddata').Jcrop({
                           onSelect: imagepaste.setCoords, onChange: imagepaste.setCoords
                        });
                     });
                  }
                  
                  imagepaste.menu.show();
               }
            });
            
         } else {
            error(params.lang.itemnotfound);
            clearImage();
         }
      }
         
      imagepaste.setCoords = function(c){
         imagepaste.imageCoordinates = {'img_x':c.x, 'img_y':c.y, 'img_w':c.w, 'img_h':c.h};
      };
      
      function clearImage() {
         imagepaste.menu.hide();
         $('#desc_paste_image').html('<b>'+params.lang.pasteimage+'</b>');
         imagepaste.pasteddata = undefined;
         imagepaste.file       = undefined;
      } 
      
      function stockImage(){
         if(imagepaste.file != null){
            var fileList = new Array(imagepaste.file);
         } else {
            var fileList = new Array(dataURItoBlob($('#pasteddata').attr('src')));
         }
         imagepaste.stockimage = true;
         eval('uploadFile'+params.rand+'()');
         $('#fileupload'+params.rand).fileupload('add', {files: fileList});
         clearImage();
      }
      
      function dataURItoBlob(dataURI) {
         var byteString, 
             mimestring 

         if(dataURI.split(',')[0].indexOf('base64') !== -1 ) {
             byteString = atob(dataURI.split(',')[1])
         } else {
             byteString = decodeURI(dataURI.split(',')[1])
         }

         mimestring = dataURI.split(',')[0].split(':')[1].split(';')[0]

         var content = new Array();
         for (var i = 0; i < byteString.length; i++) {
             content[i] = byteString.charCodeAt(i)
         }
         var blob = new Blob([new Uint8Array(content)], {type: mimestring});
         blob.name = params.filename;
         
         return blob;
      }
      
      function generateFileName(){
         return 'pastedImage';
      }
         
      return this;
   }
}(jQuery));
   
/**
 * imagePaste : Jquery plugin for IE. Paste and send an image from clipboard 
 *
 * @param params     Json     - name                string   Name of the textarea
 *                            - filename            string   Generated name of the file
 *                            - lang                json     locales loaded in plugin
 *                            - root_doc            string   Root of glpi
 *                            - showfilesize        boolean  show file size with file name
 *                                                           use selector to display
 *                            - rand                int
 * @return plugin object
 **/
(function($) {
   $.fn.IE_support_imagePaste = function(params) {
      
      var imagepaste = this;
      init();// Start the plugin
      
      function init(){

         $('#image_paste').append("<form name='image_paste_form' action='#none'>\n\
               <div style='display:none'>\n\
                  <applet id='desc_paste_image'\n\
                          archive='"+params.root_doc+"/lib/jqueryplugins/imagepaste/IE_support/Supa.jar'\n\
                          code='de.christophlinder.supa.SupaApplet'>\n\
                     <param name='imagecodec' value='png'>\n\
                     <param name='trace' value='true'>\n\
                     <param name='encoding' value='base64'>\n\
                     <param name='previewscaler' value='fit to canvas'>\n\
                  </applet> \n\
               </div>\n\
            </form>\n\
            <div id='desc_paste_image2'><b>"+params.lang.pasteimage+"</b></div>\n\
            <input style='display:none' id='fileupload"+params.rand+"' type='file' name='stock_image[]' \n\
               data-url='"+params.root_doc+"/front/fileupload.php?name=stock_image&showfilesize="+params.showfilesize+"'>\n\
            <div id='image_paste_result' style='display:none'></div>\n\
            <div id='image_paste_result2'>\n\
               <div id='progress"+params.rand+"' style='display:none'>\n\
                  <div class='uploadbar' style='width: 0%;'></div>\n\
               </div>\n\
               <div id='filedata"+params.rand+"'></div>\n\
            </div>\n\
            <div id='paste_image_menu' class='center' style='display:none'>\n\
               <a id='upload_image' class='vsubmit'>"+params.lang.save+"</a>\n\
               <a id='clear_image' class='vsubmit'>"+params.lang.cancel+"</a>\n\
            </div>");

         $('#image_paste_result').css({
            'margin':'2px 0px 2px 0px'
         });
         
         $('#paste_image_menu').css({
            'margin-top':'5px',
            'height':'25px'
         });

         $('#desc_paste_image2').focusout(function(){
            $('#image_paste').css({
               'border':'1px dashed #cccccc'
            });
         });
         
         $('#image_paste').css({
            'cursor':'pointer',
            'max-height': '500px',
            'max-width': '700px'
         });
         
         $('#desc_paste_image2').css({
            'max-height': '470px',
            'max-width': '700px',
            'overflow':'auto'
         }).focus(function(){
            $('#image_paste').css({
               'border':'1px dashed orange'
            });
         });
         
         // Handle ctrl+v if needed
         $(document).keydown(function(e){
            if (e.ctrlKey && (e.keyCode == 86)) {
               processpaste();
            }
         });
         
         imagepaste.menu = $('#paste_image_menu');
         
         var btnClear = $('#clear_image');
         btnClear.click(function() {
            clearImage();
         });
         
         imagepaste.pasteddata = undefined;
         imagepaste.stockimage = undefined;
      }
      
      function error(errorMsg){
         $('#image_paste_result').html("<b class='red'>"+errorMsg+"</b>").show().delay(2000).fadeOut('slow');
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
      

      function processpaste() {
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
                  var img = document.createElement('img');
                  img.src = 'data:image/png;base64,'+applet.getEncodedString();
                  img.id = 'pasteddata';
                  
                  $('#desc_paste_image2').html(img);
                  
                  $($('#pasteddata')).load(function() {
                     if($('#pasteddata').height() > params.maxsize || $('#pasteddata').width() > params.maxsize){
                        error(params.lang.toolarge);
                        clearImage();
                     } else {
                        // Crop image paste
                        if(jQuery().Jcrop) {
                           jQuery(function($){
                              $('#pasteddata').Jcrop({
                                 onSelect: imagepaste.setCoords, 
                                 onChange: imagepaste.setCoords
                              });
                           });
                        }
                     
                        var btnUpload = $('#upload_image');
                        btnUpload.click(function() {
                           stockImage(applet.getEncodedString());
                           clearImage();
                        });

                        imagepaste.menu.show();
                     }
                  });
                  break;
               case 1:
                  error("Unknown Error");
                  break;
               case 2:
                  error("Empty clipboard");
                  break;
               case 3:
                  error(params.lang.itemnotfound);
                  break;
               case 4:
                  error("Clipboard in use by another application. Please try again in a few seconds.");
                  break;
               default:
                  error(params.lang.itemnotfound);
            }
         } catch(e) {
            error(e);
            throw e;
         }
      }
      
      imagepaste.setCoords = function(c){
         imagepaste.imageCoordinates = {'img_x':c.x, 'img_y':c.y, 'img_w':c.w, 'img_h':c.h};
      };
      
      function stockImage(pasteddata){
         // Insert the tag in textarea
         if(pasteddata != null || pasteddata != undefined){
            var fileList = new Array(b64toBlob(pasteddata));
            imagepaste.pasteddata = true;
            imagepaste.stockimage = true;
            eval('uploadFile'+params.rand+'()');
            $('#fileupload'+params.rand).fileupload('add', {files: fileList});
         }
         clearImage();
      }
      
      function b64toBlob(b64Data, contentType, sliceSize) {
         contentType = contentType || 'image/png';
         sliceSize = sliceSize || 1024;

         function charCodeFromCharacter(c) {
            return c.charCodeAt(0);
         }
         b64Data = b64Data.replace(/\s/g, '');
         var byteCharacters = atob(b64Data);
         var byteArrays = [];

         for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
            var slice = byteCharacters.slice(offset, offset + sliceSize);
            var byteNumbers = Array.prototype.map.call(
               slice, charCodeFromCharacter);
            var byteArray = new Uint8Array(byteNumbers);

            byteArrays.push(byteArray);
         }

         var blob = new Blob(byteArrays, {
            type: contentType
         });
         blob.name = params.filename;
         
         return blob;
      }
         
      function clearImage() {
         document.getElementById("desc_paste_image").clear();
         $('#desc_paste_image2').html("<b>"+params.lang.pasteimage+"</b>");
         imagepaste.menu.hide();
      } 

      return this;
   }
}(jQuery));


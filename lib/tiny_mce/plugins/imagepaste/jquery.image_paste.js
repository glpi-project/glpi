/**
 * imagePaste : Jquery plugin for CHROME and FIREFOX. It paste and send image from clipboard 
 *
 * @param params     Json     - name                string   Name of the textarea
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
                  <a id='upload_image' class='vsubmit' style='display:none'>"+params.lang.save+"</a>\n\
                  <a id='clear_image' class='vsubmit' style='display:none'>"+params.lang.cancel+"</a>\n\
               </div>"
         );

         $('#desc_paste_image').focusout(function(){
            $('#image_paste').css({
               'border':'1px dashed #cccccc'
            });
         });
         
         $('#image_paste').css({
            'cursor':'pointer'
         });
         
         $('#desc_paste_image').css({
            'min-height': '65px',
            'max-height': '500px',
            'max-width': '500px',
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
         $(document).keydown(function(e){
            if (e.ctrlKey && (e.keyCode == 86)) {
               $('#desc_paste_image').focus();
               imagepaste.handlepaste($('#desc_paste_image'), e);
            }
         });
         
         // Handle drop
         $( "#image_paste" ).bind( "drop", function(event, ui) {
             $('#desc_paste_image').focus();
         });

      }
      
      function error(){
         $('#image_paste_result').html("<b class='red'>"+params.lang.itemnotfound+"</b>").show().delay(2000).fadeOut('slow');
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
                     error();
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
            $(elem).html("");
            waitforpastedata(elem, savedcontent, params.lang.pasteimage);
            return true;
         }
      }

      function waitforpastedata(elem, savedcontent, initMsg) {
         if (elem.childNodes && elem.childNodes.length > 0) {
            if(elem.firstChild.tagName === "IMG") 
               imagepaste.processpaste(elem, savedcontent);
            else {
               $(elem).html('<b>'+initMsg+'</b>');
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

      this.processpaste = function(elem, savedcontent) {
         // If image element, we get the src
         if(elem.firstChild != undefined && elem.firstChild.tagName === "IMG"){
            imagepaste.pasteddata = elem.firstChild.src;
         } else {// else text element
            imagepaste.pasteddata = $(elem).html();
         }

         if(imagepaste.pasteddata != ''){
            $(elem).html(savedcontent);

            var img = document.createElement('img');
            img.src = imagepaste.pasteddata;
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
            
            imagepaste.btnUpload.show();
            imagepaste.btnClear.show();
            imagepaste.menu.show();
         }
      }
         
      imagepaste.setCoords = function(c){
         imagepaste.imageCoordinates = {'img_x':c.x, 'img_y':c.y, 'img_w':c.w, 'img_h':c.h};
      };
      
      function clearImage(type) {
         imagepaste.btnClear.hide();
         imagepaste.btnUpload.hide();
         imagepaste.menu.hide();
         $('#desc_paste_image').html('<b>'+params.lang.pasteimage+'</b>');
         if(type != 'stockImage'){
            tinyMCE.imagePaste.pasteddata = undefined;
         }
      } 
      
      function stockImage(){
         var fileList = new Array(dataURItoBlob(imagepaste.pasteddata));
         uploadFile();
         $('#fileupload'+params.rand).fileupload('add', {files: fileList});
         clearImage('stockImage');
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

         return new Blob([new Uint8Array(content)], {type: mimestring});
      }
      
         
      return this;
   }
}(jQuery));
   
/**
 * imagePaste : Jquery plugin for IE. It paste and send image from clipboard 
 *
 * @param params     Json     - name                string   Name of the textarea
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
               <div>\n\
                  <applet id='desc_paste_image'\n\
                          archive='"+params.root_doc+"/lib/tiny_mce/plugins/imagepaste/IE_support/Supa.jar'\n\
                          code='de.christophlinder.supa.SupaApplet'>\n\
                     <param name='imagecodec' value='png'>\n\
                     <param name='trace' value='true'>\n\
                     <param name='encoding' value='base64'>\n\
                     <param name='previewscaler' value='fit to canvas'>\n\
                  </applet> \n\
               </div>\n\
            </form>\n\
            <input style='display:none' id='fileupload"+params.rand+"' type='file' name='stock_image[]' \n\
               data-url='"+params.root_doc+"/front/fileupload.php?name=stock_image&showfilesize="+params.showfilesize+"'>\n\
            <div id='image_paste_result' style='display:none'></div>\n\
            <div id='image_paste_result2'>\n\
               <div id='progress"+params.rand+"'>\n\
                  <div class='uploadbar' style='width: 0%;'></div>\n\
               </div>\n\
               <div id='filedata"+params.rand+"'></div>\n\
            </div>\n\
            <div id='paste_image_menu' class='center' style='display:none'>\n\
               <a id='paste_image' class='vsubmit' style='display:none'>"+params.lang.pasteimage+"</a>\n\
               <a id='upload_image' class='vsubmit' style='display:none'>"+params.lang.save+"</a>\n\
               <a id='clear_image' class='vsubmit' style='display:none'>"+params.lang.cancel+"</a>\n\
            </div>");

//         $('#desc_paste_image').css({
//            'height': '200px',
//            'width': '450px'
//         });

         $('#image_paste_result').css({
            'margin':'2px 0px 2px 0px'
         });
         
         $('#paste_image_menu').css({
            'margin-top':'5px',
            'height':'25px'
         }).show();
         
         
         // Handle ctrl+v if needed
         $(document).keydown(function(e){
            if (e.ctrlKey && (e.keyCode == 86)) {
               handlepasteIE();
            }
         });
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
      
      function stockImage(pasteddata){
         // Insert the tag in textarea
         if(pasteddata != null || pasteddata != undefined){
            var fileList = new Array(b64toBlob(pasteddata));
            uploadFile();
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

         var blob = new Blob(byteArrays, {type: contentType});
         return blob;
         
     }
         
      function clearImage() {
         document.getElementById("desc_paste_image").clear();
         $('#paste_image_menu').show();
         $('#paste_image').show();
      } 

      return this;
   }
}(jQuery));


///**
// * uploadFile : Upload files
// *
// * @param params     Json     - step                string  upload step
// *                            - params              json    options
// * @return plugin object
// **/     
//function uploadFile(step, params){
//   $('#image_paste_result2').show();
//   $('#fileupload'+params.rand).fileupload({
//      dataType: 'json',
//      dropZone: params.dropzone != false ? $('#'+params.dropzone):false,
//      acceptFileTypes: '/(\.|\/)(gif|jpe?g|png)$/i',
//
//      progressall: function (e, data) {
//         if(step == 'saveToGlpi'){
//            var progress = parseInt(data.loaded / data.total * 100, 10);
//            $('#progress'+params.rand).show();
//            $('#progress'+params.rand+' .uploadbar').css(
//               'width',
//               progress + '%'
//            );
//            $('#progress'+params.rand+' .uploadbar').text(progress + '%').delay(2000).fadeOut('slow');
//         }
//      },
//      done: function (e, data) {
//         var reader = new FileReader();
//         // Image detected and pasted in the plugin area
//         if(data.originalFiles != undefined && data.originalFiles != null && step != 'saveToGlpi'){
//            reader.readAsDataURL(data.originalFiles[0]);//Convert the blob from clipboard to base64
//            reader.onloadend = function(e){
//               $('#desc_paste_image').html(e.target.result);
//               tinyMCE.imagePaste.processpaste($('#desc_paste_image'), '');
//            }
//
//         // Load image tag, and display image uploaded
//         } else {
//            $.ajax({
//               type: "POST",
//               url: params.root_doc+'/ajax/getFileTag.php',
//               data: {'data':data.result.stock_image},
//               dataType: 'JSON',
//               success: function(response){
//                  displayImagePasted(data.result.stock_image, response, params);
//               }
//            });
//         }
//      }
//   });
//}
//
///**
// * displayImagePasted : Callback function of uploadFile, to display uploaded files
// *
// * @param params     Json     - data                json    files upload data
// *                            - tag                 json    tags of files
// *                            - params              json    options
// * @return plugin object
// **/
//function displayImagePasted(data, tag, params){
//   $.each(data, function (index, file) {
//      if (file.error == undefined) {
//         var p = $('<p/>').attr('id',file.id).html('<b>'+params.lang.file+' : </b>'+file.display+' <b>'+params.lang.tag+' : </b>'+tag[index]).appendTo('#'+params.showfilecontainer);
//         var p2 = $('<p/>').attr('id',file.id+'2').css({'display':'none'}).appendTo('#'+params.showfilecontainer);
//         var elementsIdToRemove = {0:file.id, 1:file.id+'2'};
//         
//         // File
//         $('<input/>').attr('type', 'hidden').attr('name', '_stock_image['+tinyMCE.imagePaste.fileindex+']').attr('value', file.display).appendTo(p);
//         
//         // Tag
//         $('<input/>').attr('type', 'hidden').attr('name', '_tag['+tinyMCE.imagePaste.fileindex+']').attr('value', tag[index]).appendTo(p);
//         
//         // Coordinates
//         if((tinyMCE.imagePaste.imageCoordinates != undefined || tinyMCE.imagePaste.imageCoordinates != null)){
//            $('<input/>').attr('type', 'hidden').attr('name', '_coordinates['+tinyMCE.imagePaste.fileindex+']').attr('value', encodeURIComponent(JSON.stringify(tinyMCE.imagePaste.imageCoordinates))).appendTo(p2);
//            tinyMCE.imagePaste.imageCoordinates = null;
//         }
//         // Delete button
//         $('<img src="'+params.root_doc+'/pics/delete.png">').click(function(){
//            deleteImagePasted(elementsIdToRemove, tag[index]);
//         }).appendTo(p);
//         
//         // Progress bar
//         $('#progress'+params.rand+' .uploadbar').text(params.lang.uploadsuccessful);
//         $('#progress'+params.rand+' .uploadbar').css('width', '100%');
//         tinyMCE.imagePaste.fileindex++;
//         
//         // Insert tag in textarea
//         tinyMCE.activeEditor.execCommand('mceInsertContent', false, '<p>'+tag[index]+'</p>');
//      } else {
//         $('#progress'+params.rand+' .uploadbar').text(params.lang.uploaderror);
//         $('#progress'+params.rand+' .uploadbar').css('width', '100%');
//      }
//   });
//}
//
///**
// * deleteImagePasted : Remove a file
// *
// * @param params     Json     - elementsIdToRemove  json    files upload data
// *                            - tagToRemove         json    tag of file to remove
// *                            
// * @return plugin object
// **/
//function deleteImagePasted(elementsIdToRemove, tagToRemove){
//   // Remove file display lines
//   $.each(elementsIdToRemove, function (index, id) {
//       $('#'+id).remove(); 
//   });
//
//   // Remove tag from textarea
//   tinyMCE.activeEditor.setContent(tinyMCE.activeEditor.getContent().replace('<p>'+tagToRemove+'</p>', '')); 
// 
//   // File counter
//   if(tinyMCE.imagePaste.fileindex > 0){
//      tinyMCE.imagePaste.fileindex--;
//   }
//}


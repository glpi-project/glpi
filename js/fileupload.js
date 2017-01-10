/**
 * Uploads a file receive on tinyMCE to Server
 *
 * @param      {blob}    file           The file to upload
 * @param      {Object}  editor         instance of editor (TinyMCE)
 */
function uploadFile(file, editor) {
   var returnTag = false;

   //Create formdata from file to send with ajax request
   var formdata = new FormData();
   formdata.append('filename[0]', file, file.name);
   formdata.append('name', 'filename');

   // upload file with ajax
   $.ajax({
      type: 'POST',
      url: '../ajax/fileupload.php',
      data: formdata,
      processData: false,
      contentType: false,
      dataType: 'JSON',
      async: false,
      success: function(data) {
         $.each(data, function(index, element) {
            if(element[0].error == undefined) {
               returnTag = '';
               var tag = getFileTag(element);
               //if is an image add tag
               if(isImage(file)){
                  returnTag = tag.tag;
               }
               //display uploaded file
               displayUploadedFile(element[0], tag, editor);
            } else {
               returnTag = false;
               alert(element[0].error);
            }
         });
      },

      error: function (request, status, error) {
         alert(request.responseText);
      }
   });

   return returnTag;
}

/**
 * Gets the file tag.
 *
 * @param      {(boolean|string)}  data receive from uploadFile
 * @return     {(boolean|string)}  The file tag.
 */
var getFileTag = function(data) {
   var returnString = '';

   $.ajax({
      type: 'POST',
      url: '../ajax/getFileTag.php',
      data: {'data':data},
      dataType: 'JSON',
      async: false,
      success: function(data) {
         returnString = data[0];
      },
      error: function (request, status, error) {
         console.log(request.responseText);
         returnString=false;
      }
   });

   return returnString;
};

/**
 * Display list of uploaded file with their size
 *
 * @param      {JSON}    file          The file
 * @param      {String}  tag           The tag
 * @param      {String}  filecontainer The dom id of the list file container
 * @param      {Object}  editor        The TinyMCE editor instance
 */
var fileindex = 0;
var displayUploadedFile = function(file, tag, editor) {
   // find the nearest fileupload_info where to append file list
   var current_dom_point = $(editor.targetElm);
   var iteration = 0;
   do {
      current_dom_point = current_dom_point.parent();
      filecontainer = current_dom_point.find('.fileupload_info')
      iteration++;
   } while (filecontainer.length <= 0 && iteration < 30);

   if (filecontainer.length) {
      var rand = Math.random();
      var ext  = file.name.split('.').pop();

      var p    = $('<p/>')
                  .attr('id',file.id)
                  .html(getExtIcon(ext)+'&nbsp;'+
                        '<b>'+file.display+'</b>'+
                        '&nbsp;('
                        +getSize(file.size)+')&nbsp;')
                  .appendTo(filecontainer);

      var p2   = $('<p/>')
                 .attr('id',file.id+'2')
                 .css({'display':'none'})
                 .appendTo(filecontainer);

      // File
      $('<input/>')
         .attr('type', 'hidden')
         .attr('name', '_filename['+fileindex+']')
         .attr('value',file.name).appendTo(p);

      // Tag
      $('<input/>')
         .attr('type', 'hidden')
         .attr('name', '_tag_filename['+fileindex+']')
         .attr('value', tag.name)
         .appendTo(p);

      // Delete button
      var elementsIdToRemove = {0:file.id, 1:file.id+'2'};
      $('<img src="../pics/delete.png" class="pointer">').click(function() {
         deleteImagePasted(elementsIdToRemove, tag.tag, editor);
      }).appendTo(p);

      fileindex++;
   }
};

/**
 * Remove image pasted or droped
 *
 * @param      {Array}   elementsIdToRemove  The elements identifier to remove
 * @param      {string}  tagToRemove         The tag to remove
 * @param      {Object}  editor              The editor
 */
var deleteImagePasted = function(elementsIdToRemove, tagToRemove, editor) {
   // Remove file display lines
   $.each(elementsIdToRemove, function (index, element) {
      $('#'+element).remove();
   });

   if (typeof editor !== "undefined"
       && typeof editor.dom !== "undefined") {
      editor.setContent(editor.getContent().replace('<p>'+tagToRemove+'</p>', ''));

      var regex = new RegExp('#', 'g');
      editor.dom.remove(tagToRemove.replace(regex, ''));
   }
};

/**
 * Insert an (uploaded) image in the the tinymce 'editor'
 *
 * @param  {Object}   TinyMCE editor instance
 * @param  {Blob}     fileImg
 * @param  {string}   tag
 */
var insertImgFromFile = function(editor, fileImg, tag) {
   var urlCreator = window.URL || window.webkitURL;
   var imageUrl   = urlCreator.createObjectURL(fileImg);
   var regex      = new RegExp('#', 'g');
   var maxHeight  = $(tinyMCE.activeEditor.getContainer()).height() - 60 ;
   var maxWidth   = $(tinyMCE.activeEditor.getContainer()).width()  - 120;

   if (window.FileReader && window.File && window.FileList && window.Blob ) {
      var reader = new FileReader();
      reader.onload = (function(theFile) {
         var image    = new Image();
         image.src    = theFile.target.result;
         image.onload = function() {
            // access image size here
            var imgWidth  = this.width;
            var imgHeight = this.height;
            var ratio     = 0;

            if(imgWidth > maxWidth){
               ratio     = maxWidth / imgWidth; // get ratio for scaling image
               imgHeight = imgHeight * ratio;   // Reset height to match scaled image
               imgWidth  = imgWidth * ratio;    // Reset width to match scaled image
            }

            // Check if current height is larger than max
            if(imgHeight > maxHeight){
               ratio     = maxHeight / imgHeight; // get ratio for scaling image
               imgWidth  = imgWidth * ratio;      // Reset width to match scaled image
               imgHeight = imgHeight * ratio;     // Reset height to match scaled image
            }

            editor.execCommand('mceInsertContent', false,
                               "<img width='"+imgWidth+"' height='"+imgHeight+"'' id='"+tag.replace(regex,'')+"' src='"+imageUrl+"'>");
         };
      });
      reader.readAsDataURL(fileImg);

   } else{
      console.log('thanks to update your browser to get preview of image');
   }
};

/**
 * Convert dataURI to BLOB
 *
 * @param      {Object}  dataURI  The data uri
 * @return     {Blob}    { description_of_the_return_value }
 */
var dataURItoBlob = function(dataURI) {
   // convert base64/URLEncoded data component to raw binary data held in a string
   var byteString;
   if (dataURI.split(',')[0].indexOf('base64') >= 0) {
      byteString = atob(dataURI.split(',')[1]);
   } else {
      byteString = unescape(dataURI.split(',')[1]);
   }

   // separate out the mime component
   var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

   // write the bytes of the string to a typed array
   var ia = new Uint8Array(byteString.length);
   for (var i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
   }

   return new Blob([ia], {type:mimeString});
};

/**
* Function to check if data paste on TinyMCE is an image
*
* @param      String content  The img tag
* @return     String mimeType   return mimeType of data
*/
var isImageFromPaste = function(content) {
   return content.match(new RegExp('<img.*data:image\/')) !== null;
};

/**
* Function to check if data paste on TinyMCE is an image
*
* @param      String content  The img tag
* @return     String mimeType   return mimeType of data
*/
var isImageBlobFromPaste = function(content) {
   return content.match(new RegExp('<img.*src="blob:')) !== null;
};

/**
* Function to extract src tag from img tag process bu TinyMCE
*
* @param      {string}  content  The img tag
*/
var extractSrcFromImgTag = function(content) {
   var match = content.match(/<img[^>]*?src=['"](.*)['"]/)
   if (match !== null) {
      return match[1];
   }
   return '';
};

/**
* Function to extract src tag from img tag process bu TinyMCE
*
* @param      {string}  content  The img tag
*/
var extractSrcFromBlobImgTag = function(content) {
   var match = content.match(/\<img[^>]+src="(blob:http%3A\/\/[^">]+)/);
      if (match == null) {
         var match = content.match(/\<img[^>]+src="(blob:http:\/\/[^">]+)/);

         if (match == null) {
            var match = content.match(/\<img[^>]+src="(blob:[^">]+)/);
         }
      }

   return match[1];
};

/**
 * Insert an image file into the specified tinyMce editor
 * @param  {Object} editor The tinyMCE editor
 * @param  {Blob}   image  The image to insert
 */
var insertImageInTinyMCE = function(editor, image) {
   // set a tempory tag in mce editor
   var state_upload = '[** UPLOADING FILE == '+image.name+' **]';
   editor.execCommand('mceInsertContent', false, state_upload);

   //make ajax call for upload doc
   var tag = uploadFile(image, editor);

   //replace upload state by html render of image
   replaceContent(editor, state_upload, '');

   if (tag !== false) {
      insertImgFromFile(editor, image, tag);
   }

   //Set cursor at the end
   setCursorAtTheEnd(editor);
};

/**
 * Plugin for tinyMce editor who intercept paste event
 * to check if a file upload can be proceeded
 * @param  {[Object]} editor TinyMCE editor
 */
if (typeof tinymce != 'undefined') {
   tinymce.PluginManager.add('glpi_upload_doc', function(editor) {
      editor.on('drop', function(event) {
         if (event.dataTransfer
             && event.dataTransfer.files.length > 0) {
            stopEvent(event);

            // for each dropped files
            $.each(event.dataTransfer.files, function(index, element) {
               insertImageInTinyMCE(editor, element);
            });
         }
      });

      editor.on('PastePreProcess', function(event) {
         //Check if data is an image
         if (isImageFromPaste(event.content)) {
            stopEvent(event);

            //extract base64 data
            var base64 = extractSrcFromImgTag(event.content);

            //transform to blob and insert into editor
            if (base64.length) {
               var file = dataURItoBlob(base64);
               insertImageInTinyMCE(editor, file);
            }

         } else if (isImageBlobFromPaste(event.content)) {
            stopEvent(event);

            var src = extractSrcFromBlobImgTag(event.content);
            var xhr = new XMLHttpRequest();
            xhr.open('GET', src, true);
            xhr.responseType = 'blob';
            xhr.onload = function(event) {
               if (this.status == 200) {
                  // fill missing file properties
                  var file  = new Blob([this.response], {type: 'image/png'});
                  file.name = 'image_paste'+ Math.floor((Math.random() * 10000000) + 1)+".png";

                  insertImageInTinyMCE(editor, file);
               } else{
                  console.log("paste error");
               }
            };
            xhr.send();
         }
      });
   });
}


$(function() {
   // set a function to track drag hover event
   $(document).bind('dragover', function (event) {
      event.preventDefault();

      var dropZone = $('.dropzone'),
          foundDropzone,
          timeout = window.dropZoneTimeout;

      if (!timeout) {
            dropZone.addClass('dragin');
      } else {
         clearTimeout(timeout);
      }

      var found = false,
          node = event.target;

      do {
         if ($(node).hasClass('draghoverable')) {
            found = true;
            foundDropzone = $(node);
            break;
         }

         node = node.parentNode;
      } while (node != null);

      dropZone.removeClass('dragin draghover');

      if (found) {
         foundDropzone.addClass('draghover');
      }
   });

   // remove dragover styles on drop
   $(document).bind('drop', function(event) {
      event.preventDefault();
      $('.draghoverable').removeClass('draghover');

      // if file present, insert it in filelist
      if (typeof event.originalEvent.dataTransfer.files) {
         $.each(event.originalEvent.dataTransfer.files, function(index, element) {
            uploadFile(element, {
               targetElm: $(event.target).find('.fileupload_info')
            });
         });
      }
   });
});
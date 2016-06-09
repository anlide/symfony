$(document).ready(function() {
  CKEDITOR.allowedContent = true;
  var CKEditorConfig = {};
  var fileman = '/resource/fileman/index.html';
  var filemanUpload = '/resource/fileman/php/upload.php';
  CKEditorConfig.filebrowserBrowseUrl = fileman;
  CKEditorConfig.filebrowserImageBrowseUrl = fileman+'?type=image';
  CKEditorConfig.width = '800';
  CKEditorConfig.font_names = "Arial/Arial, Helvetica, sans-serif;Comic Sans MS/Comic Sans MS, cursive;Courier New/Courier New, Courier, monospace;Georgia/Georgia, serif;Lucida Sans Unicode/Lucida Sans Unicode, Lucida Grande, sans-serif;Tahoma/Tahoma, Geneva, sans-serif;Times New Roman/Times New Roman, Times, serif;Trebuchet MS/Trebuchet MS, Helvetica, sans-serif;Verdana/Verdana, Geneva, sans-serif"
  CKEditorConfig.font_names = 'Open Sans/open sans;Roboto/Roboto-Light;Play/Play, PlayBold;' + CKEditorConfig.font_names;
  CKEDITOR.replace( 'message' , CKEditorConfig );
});

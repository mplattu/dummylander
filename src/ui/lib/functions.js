$.fn.extend({
  insertAtCaret: function(myValue) {
    this.each(function() {
      if (document.selection) {
        this.focus();
        var sel = document.selection.createRange();
        sel.text = myValue;
        this.focus();
      } else if (this.selectionStart || this.selectionStart == '0') {
        var startPos = this.selectionStart;
        var endPos = this.selectionEnd;
        var scrollTop = this.scrollTop;
        this.value = this.value.substring(0, startPos) + myValue + this.value.substring(endPos,this.value.length);
        this.focus();
        this.selectionStart = startPos + myValue.length;
        this.selectionEnd = startPos + myValue.length;
        this.scrollTop = scrollTop;
      } else {
        this.value += myValue;
        this.focus();
      }
    });
    return this;
  }
});

function getSelected(selector) {
  if ($(selector).length < 1) {
    return undefined;
  }

  var textComponent = $(selector)[0];
  var selectedText;

  if (textComponent.selectionStart !== undefined) {
    // Standards Compliant Version
    var startPos = textComponent.selectionStart;
    var endPos = textComponent.selectionEnd;
    selectedText = textComponent.value.substring(startPos, endPos);
  }
  else if (document.selection !== undefined) {
    // IE Version
    textComponent.focus();
    var sel = document.selection.createRange();
    selectedText = sel.text;
  }

  return selectedText;
}

// Convert bytes to human-readable format
// https://stackoverflow.com/questions/10420352/converting-file-size-in-bytes-to-human-readable-string#answer-20732091
function humanFileSize(size) {
  var i = Math.floor( Math.log(size) / Math.log(1024) );
  return ( size / Math.pow(1024, i) ).toFixed(1) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
};

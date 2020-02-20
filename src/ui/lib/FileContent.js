class FileContent {
  constructor(file_content_id) {
    this.file_data = null;
    this.file_content_id = file_content_id;

    if ($(this.file_content_id).length != 1) {
      console.error("FileContent: Given "+this.file_content_id+" points to "+$(this.file_content_id).length+" objects");
    }
  }

  get_files_count() {
    if (this.file_data == null) {
      return 0;
    }

    return this.file_data.length;
  }

  get_files_table_html() {
    var body_rows = [];
    var biw = new BootstrapIconWrapper();

    if (this.get_files_count() == 0) {
      return "<p>No files. Upload one:</p>";
    }

    for (var n=0; n < this.get_files_count(); n++) {
      var html = "<tr>";
      html += "<td>"+this.file_data[n].name+"</td>";
      html += "<td>"+humanFileSize(this.file_data[n].size)+"</td>";
      html += "<td><button type='button' class='btn btn-outline-danger btn-sm button_file_delete' data-filename='"+this.file_data[n].name+"'>"+biw.x_circle_fill+"</button>";
      html += "</tr>";
      body_rows.push(html);
    }

    return "<table class='table'><thead><tr><th scope='col'>Filename</th><th scope='col'>Size</th><th scope='col'>Delete</th></tr></thead><tbody>"+body_rows.join('')+"</tbody></table>";
  }

  set_data(data) {
    this.file_data = data;
    $(this.file_content_id).html(this.get_files_table_html());
  }
}

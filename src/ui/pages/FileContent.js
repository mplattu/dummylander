class FileContent {
  constructor(file_content_id) {
    this.file_data = null;
    this.file_content_id = file_content_id;

    if ($(this.file_content_id).length != 1) {
      console.error("FileContent: Given "+this.file_content_id+" points to "+$(this.file_content_id).length+" objects");
    }

    this.server_connect = null;
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

    this.activate_events();
  }

  activate_events() {
    // Activate file delete buttons
    $(".button_file_delete").off();
    $(".button_file_delete").on('click', {obj:this}, this.delete_file);

    $("#button_file_upload").off();
    $("#button_file_upload").on('click', {obj:this}, this.upload_file);

    $("#file_upload").off();
    $("#file_upload").on('change', {obj:this}, this.update_upload_filename);
    $("#file_upload").val("");
    this.update_upload_filename();
  }

  update_upload_filename() {
    var filename = $("#file_upload").val();
    filename = filename.split(/(\\|\/)/g).pop();

    if (filename === "") {
      filename = "Choose file";
    }

    $("#file_upload_label").text(filename);
  }

  update(file_data) {
    if (file_data != undefined) {
      // File data was given as a parameter
      this.set_data(file_data);
    }
    else {
      // Need to get a fresh file data from the server
      this.server_connect = new ServerConnect(SERVER_URL, $("#password").val());

      var obj=this;
      this.server_connect.get_file_data()
        .then(result_data => obj.set_data(result_data))
        .catch(error => alert(error));
    }
  }

  upload_file(event) {
    if ($("#file_upload").val() == "") {
      return;
    }

    var obj=event.data.obj;

    obj.server_connect = new ServerConnect(SERVER_URL, $("#password").val());

    obj.server_connect.upload_file($('#file_upload')[0].files[0])
      .then(function (result_data) {
        obj.update();
      })
      .catch(error => alert(error));
  }

  delete_file(event) {
    var filename = $(this).attr('data-filename');

    var obj = event.data.obj;

    obj.server_connect = new ServerConnect(SERVER_URL, $("#password").val());

    obj.server_connect.delete_file(filename)
    .then(function (result_data) {
      obj.update();
    })
    .catch(error => alert(error));
  }
}

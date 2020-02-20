class FileSelector {
  constructor(server_url, password) {
    this.server_connect = new ServerConnect(server_url, password);
    this.resolve_get_filename = null;
  }

  get_filename() {
    var obj = this;
    return new Promise(function (resolve, reject) {
      obj.server_connect.get_file_data()
        .then(function (result_data) {
          obj.resolve_get_filename = resolve;
          obj.get_filename_create_modal(result_data);
        })
        .catch(error => alert(error));
    });
  }

  get_filename_create_modal(file_data) {
    var modal_html = [];
    var biw = new BootstrapIconWrapper();

    modal_html.push('<div class="container-fluid">');

    jQuery.each(file_data, function(n, this_file) {
      modal_html.push('<div class="row page_modal_row">');

      modal_html.push('<div class="col-1">');
      modal_html.push(
        '<button type="button" class="btn btn-outline-primary btn-sm page_modal_button" data-filename="'+this_file.name+'">'+
        biw.check+
        '</button>');
      modal_html.push('</div>');

      modal_html.push('<div class="col-8">');
      modal_html.push(this_file.name);
      modal_html.push('</div>');

      modal_html.push('<div class="col-3">');
      modal_html.push(this_file.size);
      modal_html.push('</div>');

      modal_html.push('</div>');
    });

    modal_html.push('</div>');

    // Clear all existing events
    $("#page_modal").off();
    $("#page_modal_button").off();

    $("#page_modal_body").html(modal_html.join(""));
    $("#page_modal_title").text("Choose image file");

    $("#page_modal").modal('show');
    $("#page_modal").on('hidden.bs.modal', {obj: this}, this.get_filename_cancel);
    $("#page_modal").on('shown.bs.modal', {obj: this}, this.get_filename_create_modal_buttons);
  }

  get_filename_create_modal_buttons(event) {
    $(".page_modal_button").on('click', {obj: event.data.obj}, event.data.obj.get_filename_selected);
  }

  get_filename_selected(event) {
    // Clear event to avoid detection of Cancel click
    $("#page_modal").off('hidden.bs.modal');

    var filename = $(this).attr('data-filename');
    $("#page_modal").modal('hide');
    event.data.obj.resolve_get_filename(filename);
  }

  get_filename_cancel(event) {
    // Modal was closed using Cancel button
    event.data.obj.resolve_get_filename("");
  }
}

class PageContent {
  constructor(page_content_id) {
    this.page_data = null;
    this.page_content_id = page_content_id;
    this.on_change_func = null;
    this.page_data_has_changed = false;

    if ($(this.page_content_id).length != 1) {
      console.error("PageContent: Given "+this.page_content_id+" points to "+$(this.page_content_id).length+" objects");
    }

    console.log("Executed PageContent constructor");

    this.fields = {};

    this.fields.page_values = {
      'title': 'Title',
      'favicon-ico': 'Favicon (URL)',
      'description': 'Description',
      'style-css': 'Custom CSS'
    };

    this.fields.section_values = {
      'margin': "Margin",
      'padding': "Padding",
      'height': "Height",
      'color': "Font color",
      'background-image': "Background image (URL)",
      'font-family-google': "Google font name"
    }
  }

  get_parts_count() {
    if (this.page_data == null) {
      return 0;
    }

    if (this.page_data.parts == null) {
      return 0;
    }

    return this.page_data.parts.length;
  }

  render_editor_input(field, name) {
    if (field == "color") {
      return '<input class="page_field color_field form-control" type="text" name="'+name+'" id="'+name+'">';
    }

    if (field == "text") {
      return '<textarea class="section_field form-control" name="'+name+'" id="'+name+'" rows="5"></textarea>';
    }

    // Fallback: single-line text
    return '<input class="page_field form-control" type="text" name="'+name+'" id="'+name+'">';
  }

  render_editor_fields_page() {
    var html = [];

    // Fields for page attributes
    html.push('<div class="section_page">');
    for (var field in this.fields.page_values) {
      var name = 'page_'+field;

      html.push('<div class="row">'
        +'<div class="col-4">'+this.fields.page_values[field]+'</div>'
        +'<div class="col-8">'+this.render_editor_input(field, name)+'</div>'
        +'</div>');
    }
    html.push('</div>');

    return html.join("\n");
  }

  render_editor_fields_section(n) {
    var html = [];

    html.push('<div class="section_group">');
    for (var field in this.fields.section_values) {
      var name = 'section_'+n+'_'+field;

      html.push('<div class="row">'
        +'<div class="col-4">'+this.fields.section_values[field]+'</div>'
        +'<div class="col-8">'+this.render_editor_input(field, name)+'</div>'
        +'</div>');
    }

    var name='section_'+n+'_text';

    html.push('<div class="row"><div class="col-12"><label for="'+name+'">Text</label>'+this.render_editor_input('text', name)+'</div></div>');
    html.push('</div>');

    return html.join("\n");
  }

  render_editor_sectionborder() {
    return '<div class="row"><div class="col-12"><hr></div></div>';
  }

  render_editor() {
    var html = [];

    // Page-level fields
    html.push(this.render_editor_fields_page());
    html.push(this.render_editor_sectionborder());

    // Fields for each existing section
    var html_section = [];
    for (var n=0; n < this.get_parts_count(); n++) {
      html_section.push(this.render_editor_fields_section(n))
    }

    html.push(html_section.join(this.render_editor_sectionborder()));

    $(".page_field").off();
    $(".section_field").off();
    $(".color_field").off();

    $(this.page_content_id).html(html.join("\n"));

    $(".page_field").on('keyup', {obj: this}, this.update_object_value);
    $(".section_field").on('keyup', {obj: this}, this.update_object_value);
  }

  get_luma(hex_color) {
    if (typeof hex_color != "string") {
      return 0;
    }

    var c = hex_color.substring(1);      // strip #
    var rgb = parseInt(c, 16);   // convert rrggbb to decimal
    var r = (rgb >> 16) & 0xff;  // extract red
    var g = (rgb >>  8) & 0xff;  // extract green
    var b = (rgb >>  0) & 0xff;  // extract blue

    return 0.2126 * r + 0.7152 * g + 0.0722 * b; // per ITU-R BT.709
  }

  update_color_field(event) {
    var selector = "#"+event.target.id;
    $(selector).css('background-color', $(selector).val());

    var luma = this.get_luma($(selector).val());
    if (luma > 230) {
      $(selector).css("color", "#495057");
    }
    else if (luma > 128) {
      $(selector).css("color", "black")
    }
    else {
      $(selector).css("color", "white");
    }
  }

  update_object_value(event) {
    var target_attrs = event.target.id.split("_");
    var changed = false;
    var new_value = event.target.value;

    if (target_attrs[0] == "page") {
      var old_value = event.data.obj.page_data.page_values[target_attrs[1]];

      if (old_value != new_value) {
        if (old_value != undefined || (new_value != "")) {
          changed = true;
          event.data.obj.page_data.page_values[target_attrs[1]] = new_value;
        }
      }
    }

    if (target_attrs[0] == "section") {
      var old_value = event.data.obj.page_data.parts[target_attrs[1]][target_attrs[2]];

      if (old_value != new_value) {
        if (old_value != undefined || (new_value != "")) {
          changed = true;
          event.data.obj.page_data.parts[target_attrs[1]][target_attrs[2]] = new_value;
        }
      }
    }

    if (target_attrs[2] == "color") {
      event.data.obj.update_color_field(event);
    }

    if (changed) {
      event.data.obj.page_data_has_changed = true;
      if (event.data.obj.on_change_func != null) {
        event.data.obj.on_change_func();
      }
    }
  }

  update_editor_values() {
    // Update page attributes
    for (var field in this.fields.page_values) {
      var name = '#page_'+field;
      if (this.page_data.page_values[field] != null) {
        $(name).val(this.page_data.page_values[field]);
      }
    }

    // Update sections
    for (var n=0; n < this.get_parts_count(); n++) {
      for (var field in this.fields.section_values) {
        var name = '#section_'+n+'_'+field;

        if (this.page_data.parts[n][field] != null) {
          $(name).val(this.page_data.parts[n][field]);
        }
      }

      if (this.page_data.parts[n].text != null) {
        $("#section_"+n+"_text").val(this.page_data.parts[n].text);
      }
    }
  }

  activate_colorpicker() {
    $(".color_field").colorpicker({
      useAlpha:false,
      fallbackColor:"#ffffff"
    });

    $(".color_field").on("colorpickerChange", {obj: this}, this.update_object_value);

    // Reset color backgrounds
    $(".color_field").each(function () {
      $(this).trigger("keyup", {obj: this});
    });
  }

  on_change(func) {
    this.on_change_func = func;
  }

  set_data(data) {
    this.page_data = data;

    this.render_editor();
    this.update_editor_values();
    this.activate_colorpicker();

    this.page_data_has_changed = false;
  }

  get_data() {
    this.page_data_has_changed = false;
    return this.page_data;
  }
}

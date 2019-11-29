class PageContent {
  constructor(page_content_id) {
    this.page_data = null;
    this.page_content_id = page_content_id;

    if ($(this.page_content_id).length != 1) {
      console.error("PageContent: Given "+this.page_content_id+" points to "+$(this.page_content_id).length+" objects");
    }

    console.log("Executed PageContent constructor");

    this.fields = {};

    this.fields.page_values = {
      'title': 'Title',
      'favicon-ico': 'Favicon (URL)',
      'description': 'Description'
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

  render_editor() {
    var html = [];

    // Fields for page attributes
    html.push('<div class="section_page">');
    for (var field in this.fields.page_values) {
      var name = 'page_'+field;

      html.push('<p>'
        +this.fields.page_values[field]
        +': <input class="page_field" type="text" name="'+name+'" id="'+name+'">'
        +'</p>');
    }
    html.push('</div>');

    // Fields for each existing section
    for (var n=0; n < this.get_parts_count(); n++) {
      html.push('<div class="section_group">');
      for (var field in this.fields.section_values) {
        var name = 'section_'+n+'_'+field;

        html.push('<p>'
          +this.fields.section_values[field]
          +': <input class="section_field" type="text" name="'+name+'" id="'+name+'">'
          +'</p>');
      }

      var name='section_'+n+'_text';

      html.push('<p><textarea class="section_field" name="'+name+'" id="'+name+'"></textarea></p>');
      html.push('</div>');
    }

    $(".page_field").off();
    $(".section_field").off();

    $(this.page_content_id).html(html.join("\n"));

    //$(".page_field").on('keyup', this.update_object_value);
    //$(".section_field").on("keyup", this.update_object_value);
    $(".page_field").on('keyup', {obj: this}, this.update_object_value);
    $(".section_field").on("keyup", {obj: this}, this.update_object_value);
  }

  update_object_value(event) {
    var target_attrs = event.target.id.split("_");

    if (target_attrs[0] == "page") {
      event.data.obj.page_data.page_values[target_attrs[1]] = event.target.value;
    }

    if (target_attrs[0] == "section") {
      event.data.obj.page_data.parts[target_attrs[1]][target_attrs[2]] = event.target.value;
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

  set_data(data) {
    this.page_data = data;

    this.render_editor();
    this.update_editor_values();
  }

  get_data() {
    return this.page_data;
  }
}

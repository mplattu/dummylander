class EditContent {
  constructor(page_content_id) {
    this.TEXTAREA_MAX_HEIGHT = 400;
    this.page_data = null;
    this.page_content_id = page_content_id;
    this.on_change_func = null;
    this.page_data_has_changed = false;

    if ($(this.page_content_id).length != 1) {
      console.error("EditContent: Given "+this.page_content_id+" points to "+$(this.page_content_id).length+" objects");
    }

    this.fields = {};

    this.fields.page_values = {
      'title': 'Title',
      'favicon-ico': 'Favicon (URL)',
      'description': 'Description',
      'image': 'Sharing Image',
      'keywords': 'Keywords',
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

  textarea_buttons_html(target_id) {
    var biw = new BootstrapIconWrapper();

    var html = [];
    html.push('<button type="button" class="btn btn-outline-primary btn-sm" data-action="link" data-target="'+target_id+'">'+biw.reply+'</button>');
    html.push('<button type="button" class="btn btn-outline-primary btn-sm" data-action="image" data-target="'+target_id+'">'+biw.image+'</button>');
    html.push('<button type="button" class="btn btn-outline-primary btn-sm" data-action="bold" data-target="'+target_id+'">'+biw.type_bold+'</button>');
    html.push('<button type="button" class="btn btn-outline-primary btn-sm" data-action="italic" data-target="'+target_id+'">'+biw.type_italic+'</button>');
    html.push('<button type="button" class="btn btn-outline-primary btn-sm" data-action="code" data-target="'+target_id+'">'+biw.code+'</button>');

    return html.join("\n");
  }

  render_editor_input(field, name) {
    if (field == "color") {
      return '<input class="page_field color_field form-control" type="text" name="'+name+'" id="'+name+'">';
    }

    if (field == "text") {
      return '<span class="section_text_control" id="'+name+'_control">'+this.textarea_buttons_html(name)+'</span><textarea class="section_field form-control section_text" name="'+name+'" id="'+name+'" rows="5"></textarea>';
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
    var biw = new BootstrapIconWrapper();

    var name_advanced = "section_advanced section_advanced_"+n;

    html.push('<div class="section_group">');

    var name='section_'+n+'_text';
    html.push('<div class="row"><div class="col-12"><label for="'+name+'" class="label_text section_text_label">Text</label>'+this.render_editor_input('text', name)+'</div></div>');
    html.push('<div class="row"><div class="col-12">');
    html.push('<button type="button" class="btn btn-secondary btn-sm button_part button_advanced" data-partnumber="'+n+'">'+biw.chevron_down+'</button>');
    html.push('<button type="button" class="btn btn-secondary btn-sm button_part button_move_down" data-partnumber="'+n+'">'+biw.arrow_down+'</button>');
    html.push('<button type="button" class="btn btn-secondary btn-sm button_part button_move_up" data-partnumber="'+n+'">'+biw.arrow_up+'</button>');
    html.push('<button type="button" class="btn btn-secondary btn-sm button_part button_add_part" data-partnumber="'+n+'">'+biw.plus+'</button>');
    html.push('<button type="button" class="btn btn-danger btn-sm button_part button_delete_part" data-partnumber="'+n+'">'+biw.trash+'</button>');
    html.push('</div></div>');

    for (var field in this.fields.section_values) {
      var name = 'section_'+n+'_'+field;

      html.push('<div class="row '+name_advanced+'">'
        +'<div class="col-4">'+this.fields.section_values[field]+'</div>'
        +'<div class="col-8">'+this.render_editor_input(field, name)+'</div>'
        +'</div>');
    }

    html.push('</div>');

    return html.join("\n");
  }

  render_editor_sectionborder() {
    // Currently there is no constantly visible section border
    return '';
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

    this.advanced_hide();

    $(".page_field").on('keyup', {obj: this}, this.update_object_value);
    $(".section_field").on('keyup', {obj: this}, this.update_object_value);

    $(".button_advanced").on("click", {obj: this}, this.button_advanced_toggle);
    $(".button_move_down").on("click", {obj: this}, this.button_move_down);
    $(".button_move_up").on("click", {obj: this}, this.button_move_up);
    $(".button_add_part").on("click", {obj: this}, this.button_add_part);
    $(".button_delete_part").on("click", {obj: this}, this.button_delete_part);


    $(".button_move_up[data-partnumber=0]").attr("disabled", true);
    $(".button_move_down[data-partnumber="+(this.get_parts_count()-1)+"]").attr("disabled", true);

    if (this.get_parts_count() < 2) {
      $(".button_delete_part").attr("disabled", true);
    }
  }

  advanced_hide() {
    var biw = new BootstrapIconWrapper();
    $(".section_advanced").css('display', 'none');
    $(".button_advanced").html(biw.chevron_down);
  }

  button_advanced_toggle(event) {
    var biw = new BootstrapIconWrapper();
    var part = $(this).attr('data-partnumber');

    if ($(".section_advanced_"+part).css('display') == 'none') {
      event.data.obj.advanced_hide();
      $(".section_advanced_"+part).css('display', 'flex');
      $(this).html(biw.chevron_up);
    }
    else {
      $(".section_advanced_"+part).css('display', 'none');
      $(this).html(biw.chevron_down);
    }
  }

  button_add_part(event) {
    var part = parseInt($(this).attr('data-partnumber'));
    event.data.obj.part_insert(part+1);
  }

  part_insert(part) {
    var new_page_data = this.page_data;
    new_page_data.parts.splice(part, 0, {});
    this.set_data_internal(new_page_data, true);
  }

  button_move_up(event) {
    var part = parseInt($(this).attr('data-partnumber'));
    event.data.obj.part_move(part, part-1);
  }

  button_move_down(event) {
    var part = parseInt($(this).attr('data-partnumber'));
    event.data.obj.part_move(part, part+1);
  }

  part_move(part_from, part_to) {
    var tmp = this.page_data.parts[part_to];
    this.page_data.parts[part_to] = this.page_data.parts[part_from];
    this.page_data.parts[part_from] = tmp;
    var new_page_data = this.page_data;
    this.set_data_internal(new_page_data, true);
  }

  button_delete_part(event) {
    var part = parseInt($(this).attr('data-partnumber'));
    event.data.obj.part_delete(part);
  }

  part_delete(part) {
    var new_page_data = this.page_data;
    new_page_data.parts.splice(part, 1);
    this.set_data_internal(new_page_data, true);
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
      event.data.obj.on_change_call();
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

  activate_textarea_autoheight() {
    $('textarea').off('input');

    var obj = this;

    $('textarea').each(function () {
      var new_height = this.scrollHeight;
      if (new_height > obj.TEXTAREA_MAX_HEIGHT) {
        new_height = obj.TEXTAREA_MAX_HEIGHT;
      }
      this.setAttribute('style', 'height:' + (new_height) + 'px;overflow-y:hidden;');
    }).on('input', function () {
      this.style.height = 'auto';
      this.style.height = (this.scrollHeight) + 'px';
    });
  }

  activate_textarea_buttons() {
    $(".section_text_control").hide();

    $(".section_text").on('focusin', {obj: this}, this.textarea_gets_focus);
    $(".section_text").on('focusout', {obj: this}, this.textarea_loses_focus);
  }

  textarea_gets_focus(event) {
    setTimeout(function () {
      var controls_id = event.target.id+"_control";
      $("#"+controls_id).show();

      $("#"+controls_id).unbind();
      $("#"+controls_id).children().unbind();

      var obj = event.data.obj;

      $("#"+controls_id).children().click(function(event) {
        var action = $(this).attr('data-action');
        var target_selector = "#"+$(this).attr('data-target');
        $(target_selector).focus();

        if (action == "link") {
          obj.textarea_button_link(target_selector);
        }

        if (action == "image") {
          obj.textarea_button_image(target_selector);
        }

        if (action == "bold") {
          obj.textarea_button_bold(target_selector);
        }

        if (action == "italic") {
          obj.textarea_button_italic(target_selector);
        }

        if (action == "code") {
          obj.textarea_button_code(target_selector);
        }
      });
    }, 100);
  }

  textarea_button_link(target_selector) {
    var selected = getSelected(target_selector);
    if (selected == "") {
      selected = "[LINK TEXT](https://url)";
    }
    else if ((selected.indexOf('https://') == 0) && (selected.indexOf('http://') == 0)) {
      selected = "[LINK TEXT]("+selected+")";
    }
    else {
      selected = "["+selected+"](https://url)";
    }

    $(target_selector).insertAtCaret(selected);
    $(target_selector).keyup();
  }

  textarea_button_image(target_selector) {
    if (getSelected(target_selector) != "") {
      return;
    }

    var fs = new FileSelector(SERVER_URL, $("#password").val());

    // Create FileSelector showing only files with mimetype image/*
    fs.get_filename("^image/")
    .then(function (filename) {
      if (filename && filename != "") {
        $(target_selector).insertAtCaret("![ALTERNATIVE TEXT]("+filename+")");
        $(target_selector).keyup();
      }
    })
    .catch(error => alert(error));

  }

  textarea_button_bold(target_selector) {
    var selected = getSelected(target_selector);
    if (selected == "") {
      selected = "BOLD TEXT";
    }
    $(target_selector).insertAtCaret("**"+selected+"**");
    $(target_selector).keyup();
  }

  textarea_button_italic(target_selector) {
    var selected = getSelected(target_selector);
    if (selected == "") {
      selected = "ITALIC TEXT";
    }
    $(target_selector).insertAtCaret("*"+selected+"*");
    $(target_selector).keyup();
  }

  textarea_button_code(target_selector) {
    var selected = getSelected(target_selector);
    if (selected == "") {
      selected = "CODE BLOCK";
    }

    var replace = "`"+selected+"`";
    if (selected.indexOf("\n") > -1) {
      // Has more than one lines
      replace = "```\n"+selected+"```\n";
    }

    $(target_selector).insertAtCaret(replace);
    $(target_selector).keyup();
  }

  textarea_loses_focus(event) {
    setTimeout(function () {
      var $focused = $(":focus");
      if ($focused.attr('data-target') != undefined) {
        $("#"+$focused.attr('data-target')).focus();
        return;
      }

      $(".section_text_control").hide();
    });
  }

  on_change(func) {
    this.on_change_func = func;
  }

  on_change_call() {
    if (this.on_change_func != null) {
      this.on_change_func();
    }
  }

  set_data_internal(data, data_has_changed) {
    this.page_data = data;

    this.render_editor();
    this.update_editor_values();
    this.activate_colorpicker();
    this.activate_textarea_autoheight();
    this.activate_textarea_buttons();

    this.page_data_has_changed = data_has_changed;
    this.on_change_call();
  }

  set_data(data) {
    this.set_data_internal(data, false);
  }

  update() {
    // Get latest page data from server and view it
    var server_connect = new ServerConnect(SERVER_URL, $("#password").val());

    var obj = this;
    server_connect.get_page_data()
      .then(function(page_data) {
        obj.set_data(page_data);
      })
      .catch(error => alert(error));
  }

  publish() {
    // Publish current page data to server
    var server_connect = new ServerConnect(SERVER_URL, $("#password").val());

    var obj = this;
    server_connect.set_page_data(obj.get_data())
      .then(function(page_data) {
        $("#button_publish").addClass("btn-success");
        obj.on_change_call();
        setTimeout(function() { $("#button_publish").removeClass("btn-success"); mode_set('edit'); }, 1000);
      })
      .catch(error => alert(error));
  }

  get_data() {
    this.page_data_has_changed = false;
    return this.page_data;
  }
}

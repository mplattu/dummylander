var SERVER_URL="index.php";

<!-- include:src/ui/lib/functions.js -->
<!-- include:src/ui/lib/PageContent.js -->
<!-- include:src/ui/lib/FileContent.js -->
<!-- include:src/ui/lib/SettingsContent.js -->

var page_content = null;
var file_content = null;
var settings_content = null;

function update_edit() {
  var post_data = {
    type:"POST",
    url: SERVER_URL,
    data: {
      password: $("#password").val(),
      function: "get"
    }
  };

  var jqxhr = $.post(post_data)
    .done(function(data) {
      var data_obj = JSON.parse(data);

      if (data_obj.success) {
        $("#login_content").hide();
        $("#header_publish").show();
        mode_set('edit');
        page_content.set_data(data_obj.data);
      }
      else {
        $("#message_loginfailed").show();
        $("#password").val('');
        setTimeout(function () { $("#password").focus(); }, 1);
        console.error("update_edit() failed. Retrieved data:", data_obj);
        if (data_obj.message != "") {
          alert(data_obj.message);
        }
      }
    })
    .fail(function(data) {
      alert("update_edit() failed. See console");
      console.error("update_edit() failed. Retrieved data:", data);
    });
}

function set_data() {
  var post_data = {
    type: "POST",
    url: SERVER_URL,
    data: {
      password: $("#password").val(),
      function: "set",
      data: JSON.stringify(page_content.get_data())
    }
  };

  var jqxhr = $.post(post_data)
    .done(function(data) {
      var data_obj = JSON.parse(data);

      if (data_obj.success) {
        $("#button_publish").addClass("btn-success");
        update_header_publish();
        setTimeout(function() { $("#button_publish").removeClass("btn-success"); mode_set('edit'); }, 1000);
      }
      else {
        alert("set_data() failed. See console");
        console.error("set_data() failed. Data:", data_obj);
      }
    })
    .fail(function(data) {
      alert("set_data() failed. See console");
      console.error("set_data() failed. Data:", data);
    });
}

function update_preview() {
  var post_data = {
    type: "POST",
    url: SERVER_URL,
    data: {
      password: $("#password").val(),
      function: "preview",
      data: JSON.stringify(page_content.get_data())
    }
  };

  var jqxhr = $.post(post_data)
    .done(function(data) {
      var data_obj = JSON.parse(data);

      if (data_obj.success) {
        $("#page_content_preview").html(data_obj.data.html);

        // Handle Google font CSS links
        $("[href*='https://fonts.googleapis.com/css?family='][rel='stylesheet']").remove();
        $("head").append(data_obj.data.head);

        mode_set('preview');
      }
      else {
        alert("update_preview() failed. See console");
        console.error("update_preview() failed. Data:", data_obj);
      }
    })
    .fail(function(data) {
      alert("update_preview() failed. See console");
      console.error("update_preview() failed. Data:", data);
    });
}

function update_file(backend_function, filename) {
  if (backend_function == undefined) {
    backend_function = "file_list";
  }

  var post_data = {
    type: "POST",
    url: SERVER_URL,
    data: {
      password: $("#password").val(),
      function: backend_function,
      data: filename
    }
  };

  var jqxhr = $.post(post_data)
    .done(function(data) {
      var data_obj = JSON.parse(data);

      if (data_obj.success) {
        $(".button_file_delete").off();

        mode_set('file');
        file_content.set_data(data_obj.data);

        activate_file_delete_buttons();
      }
      else {
        alert("update_file() failed. See console.");
        console.error("update_file() failed. Data:", data_obj);
      }
    })
    .fail(function(data) {
      alert("update_file() failed. See console");
      console.error("update_file() failed. Data:", data);
    });
}

function upload_file() {
  if ($("#file_upload").val() == "") {
    return;
  }

  var data = new FormData();
  data.append('file_upload', $('#file_upload')[0].files[0]);
  data.append('password', $("#password").val());
  data.append('function', 'file_upload');

  $.ajax({
    url: SERVER_URL,
    data: data,
    cache: false,
    contentType: false,
    processData: false,
    method: 'POST',
    success: function(data) {
      var data_obj = JSON.parse(data);

      if (data_obj.success) {
        $(".button_file_delete").off();

        mode_set('file');
        file_content.set_data(data_obj.data);

        activate_file_delete_buttons();

        $("#file_upload").val("");
        update_upload_filename();
      }
      else {
        if (data_obj.message != "") {
          alert("File upload failed: "+data_obj.message);
        }
        else {
          alert("upload_file() failed. See console.");
          console.error("upload_file() failed. Data:", data_obj);
        }
      }
    },
    error: function(data, error) {
      alert("upload_file() failed. See console.");
      console.error("upload_file() failes. Data:", data, error);
    }
  });
}

function update_upload_filename() {
  var filename = $("#file_upload").val();
  filename = filename.split(/(\\|\/)/g).pop();

  if (filename === "") {
    filename = "Choose file";
  }

  $("#file_upload_label").text(filename);
}

function show_settings() {
  mode_set('settings');
  settings_content.render_settings();
}

function mode_set(mode) {
  // Show other than login screen

  $(".button_mode").prop("disabled", false);
  $("#button_"+mode+"_mode").prop("disabled", true);

  $(".page_content").hide();
  $("#page_content_"+mode).show();

  $(".container").css('padding-top',$("#header_publish_inner").height()+50);
}

function mode_set_login() {
  // Show login screen

  $(".page_content").hide();
  $("#login_content").show();
  $("#header_publish").hide();

  setTimeout(
    function () {
      $(".container").css('padding-top',$("#header_publish_inner").height()+50);
      $("#password").focus();
    },
    1
  );
}

function update_header_publish() {
  if (page_content.page_data_has_changed) {
    $("#button_publish").prop("disabled", false);
    $("#button_cancel").prop("disabled", false);
  }
  else {
    $("#button_publish").prop("disabled", true);
    $("#button_cancel").prop("disabled", true);
  }
}

function update_header_buttons() {
  var biw = new BootstrapIconWrapper();

  $("#button_preview_mode").html(biw.book);
  $("#button_edit_mode").html(biw.pencil);
  $("#button_file_mode").html(biw.folder);
  $("#button_publish").html(biw.cloud_upload);
  $("#button_cancel").html(biw.x_circle);
  $("#button_settings").html(biw.command);
}

function activate_file_delete_buttons() {
  $(".button_file_delete").click(function () {
    update_file("file_delete", $(this).attr('data-filename'));
  });
}

$(document).ready(function () {
  console.log("AdminUI.js is ready!");

  // Header is shown after successful login
  mode_set_login();
  update_header_buttons();

  mode_set('edit');

  page_content = new PageContent("#page_content_edit");
  file_content = new FileContent("#page_content_file_inner");
  settings_content = new SettingsContent(
    "#page_content_settings",
    SERVER_URL,
    function() {
      $("#password").val("");
      mode_set_login();
    }
  );

  $("#button_login").click(function () {
    update_edit();
  });

  $("#button_edit_mode").click(function() {
    mode_set('edit');
  });

  $("#button_preview_mode").click(function () {
    update_preview();
  });

  $("#button_file_mode").click(function () {
    update_file();
  });

  $("#button_cancel").click(function () {
    if (confirm("Are you sure you want to discard your changes?")) {
      update_edit();
    }
  });

  $("#button_publish").click(function () {
    set_data();
  });

  $("#button_file_upload").click(function () {
    upload_file();
  });

  $("#button_settings").click(function () {
    show_settings();
  });

  // Login when enter pressed
  $("#password").on("keypress", function (e) {
    if (e.which == 13) {
      update_edit();
    }
    $("#message_loginfailed").hide();
  });

  page_content.on_change(function () {
    update_header_publish();
  });

  $("#file_upload").change(function () {
    update_upload_filename();
  });
});

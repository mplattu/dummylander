var SERVER_URL="index.php";

<!-- include:src/ui/lib/functions.js -->
<!-- include:src/ui/lib/ServerConnect.js -->

<!-- include:src/ui/lib/FileSelector.js -->

<!-- include:src/ui/lib/PageContent.js -->
<!-- include:src/ui/lib/FileContent.js -->
<!-- include:src/ui/lib/SettingsContent.js -->

var page_content = null;
var file_content = null;
var settings_content = null;

function mode_set(mode) {
  // Show other than login screen

  $("#header_publish").show();
  $("#login_content").hide();

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

function login() {
  // Try log in with password in the password field

  var server_connect = new ServerConnect(SERVER_URL, $("#password").val());
  server_connect.check_password()
    .then(function(correct_password) {
      if (correct_password) {
        page_content.update_edit();
        mode_set('edit');
      }
      else {
        $("#password").val('');
        setTimeout(function () { $("#password").focus(); }, 1);

        $("#message_loginfailed").show();
        setTimeout(function() { $("#message_loginfailed").hide(); }, 3000);
      }
    });
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

$(document).ready(function () {
  console.log("AdminUI.js is ready!");

  // Header is shown after successful login
  mode_set_login();
  update_header_buttons();

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
    login();
  });

  $("#button_edit_mode").click(function() {
    mode_set('edit');
  });

  $("#button_preview_mode").click(function () {
    page_content.update_preview()
    mode_set('preview');
  });

  $("#button_file_mode").click(function () {
    file_content.update();
    mode_set('file');
  });

  $("#button_cancel").click(function () {
    if (confirm("Are you sure you want to discard your changes?")) {
      page_content.update_edit();
      mode_set('edit');
    }
  });

  $("#button_publish").click(function () {
    page_content.publish();
  });

  $("#button_settings").click(function () {
    settings_content.render_settings();
    mode_set('settings');
  });

  // Login when enter pressed
  $("#password").on("keypress", function (e) {
    if (e.which == 13) {
      login();
    }
    $("#message_loginfailed").hide();
  });

  page_content.on_change(function () {
    update_header_publish();
  });
});

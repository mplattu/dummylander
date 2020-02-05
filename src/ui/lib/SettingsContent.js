class SettingsContent {
  constructor(page_content_id, server_url, login_screen_function) {
    this.page_content_id = page_content_id;
    this.server_url = server_url;
    this.login_screen_function = login_screen_function;

    if ($(this.page_content_id).length != 1) {
      console.error("SettingsContent: Given "+this.page_content_id+" points to "+$(this.page_content_id).length+" objects");
    }
  }

  button_change_password(event) {
    var old_pass = $("#old_password").val();
    var new_pass_1 = $("#new_password_1").val();
    var new_pass_2 = $("#new_password_2").val();

    if (new_pass_1 != new_pass_2) {
      alert("New passwords do not match");
      return;
    }

    event.data.obj.change_password(event.data.obj, old_pass, new_pass_1);
  }

  change_password(obj, old_pass, new_pass) {
    var post_data = {
      type: "POST",
      url: obj.server_url,
      data: {
        password: $("#password").val(),
        data: {
          old_password: old_pass,
          new_password: new_pass
        },
        function: "change_password"
      }
    };

    var jqxhr = $.post(post_data)
      .done(function(data) {
        var data_obj = JSON.parse(data);

        if (data_obj.success) {
          obj.set_status("Password was changed. Now log in with a new password.");
          setTimeout(obj.login_screen_function, 5000);
        }
        else {
          obj.set_status("Could not change password: "+data_obj.message);
          console.error("Could not change password. Data:", data_obj);
        }
      })
      .fail(function(data) {
        obj.set_status("Could not change password.");
        console.error("Could not change password. Data:", data);
      });
  }

  update_controls(event) {
    // Update status message and submit button based on the inputs
    var old_pass = $("#old_password").val();
    var new_pass_1 = $("#new_password_1").val();
    var new_pass_2 = $("#new_password_2").val();

    if (old_pass != "" && new_pass_1 != "" && new_pass_1 == new_pass_2) {
      $("#button_change_password").prop("disabled", false);
      event.data.obj.set_status("");
      return;
    }

    if (new_pass_1 != new_pass_2) {
      event.data.obj.set_status("Passwords do not match");
    }

    $("#button_change_password").prop("disabled", true);
  }

  set_status(message) {
    $("#change_status").text(message);
  }

  render_settings() {
    var html = "<h1>Settings</h1>\
    <form onsubmit='return false'>\
      <div class='form-group'>\
        <label for='old_password'>Current Password</label>\
        <input id='old_password' name='old_password' type='password' class='form-control password_field' />\
      </div>\
      <div class='form-group'>\
        <label for='new_password_1'>New Password</label>\
        <input id='new_password_1' name='new_password_1' type='password' class='form-control password_field' />\
      </div>\
      <div class='form-group'>\
        <label for='new_password_2'>New Password (repeat)</label>\
        <input id='new_password_2' name='new_password_2' type='password' class='form-control password_field' />\
      </div>\
      <div class='form-group'>\
        <input type='button' id='button_change_password' value='Change Password' class='btn btn-primary'>\
      </div>\
      <div class='form-group'><div id='change_status'></div></div>\
    </form>";

    $(this.page_content_id).html(html);

    this.activate_events();
    this.update_controls();
  }

  activate_events() {
    $("#button_change_password").off();
    $(".password_field").off();

    $("#button_change_password").on("click", {obj: this}, this.button_change_password);
    $(".password_field").on("keyup", {obj: this}, this.update_controls);

    setTimeout(function() { $("#old_password").focus(); }, 1);
  }
}

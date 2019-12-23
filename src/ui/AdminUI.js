var SERVER_URL="index.php";

<!-- include:src/ui/lib/PageContent.js -->

var page_content = null;

function get_data() {
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
        mode_edit();
        page_content.set_data(data_obj.data);
      }
      else {
        $("#message_loginfailed").show();
        $("#password").val('');
        setTimeout(function () { $("#password").focus(); }, 1);
        console.error("get_data() failed. Retrieved data:", data_obj);
        if (data_obj.message != "") {
          alert(data_obj.message);
        }
      }
    })
    .fail(function(data) {
      alert("get_data() failed. See console");
      console.error("get_data() failed. Retrieved data:", data);
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
        setTimeout(function() { $("#header_publish").hide(500); }, 2000)
        setTimeout(function() { $("#button_publish").removeClass("btn-success"); mode_edit(); }, 2600);
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

function mode_preview() {
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

        $("#page_content_preview").show();
        $("#page_content_edit").hide();

        $("#button_preview_mode").addClass("btn-success");
        setTimeout(function() {
          $("#button_preview_mode").removeClass("btn-success").hide();
          $("#button_edit_mode").show();
        }, 2600);
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

function mode_edit() {
  $("#button_edit_mode").hide();
  $("#button_preview_mode").show();

  $("#page_content_preview").hide();
  $("#page_content_edit").show();
}

function update_header_publish() {
  if (page_content.page_data_has_changed) {
    $("#header_publish").show();
  }
  else {
    $("#header_publish").hide();
  }
}

$(document).ready(function () {
  console.log("AdminUI.js is ready!");
  $("#header_publish").hide();
  mode_edit();

  page_content = new PageContent("#page_content_edit");

  $("#button_login").click(function () {
    get_data();
  });

  $("#button_edit_mode").click(function() {
    mode_edit();
  });

  $("#button_preview_mode").click(function () {
    mode_preview();
  });

  $("#button_cancel").click(function () {
    if (confirm("Are you sure you want to discard your changes?")) {
      get_data();
    }
  });

  $("#button_publish").click(function () {
    set_data();
  });

  // Login when enter pressed
  $("#password").on("keypress", function (e) {
    if (e.which == 13) {
      get_data();
    }
    $("#message_loginfailed").hide();
  });

  page_content.on_change(function () {
    update_header_publish();
  });

  setTimeout(function () { $("#password").focus(); }, 1);
});

var SERVER_URL="index.php";

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
      console.log(data);
      $("#page_content").val(data);
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
      data: $("#page_content").val()
    }
  };

  var jqxhr = $.post(post_data)
    .done(function(data) {
      alert("Page data was saved");
      console.log(data);
    })
    .fail(function(data) {
      alert("set_data() failed. See console");
      console.error("set_data() failed. Data:", data);
    });
}

$(document).ready(function () {
  console.log("AdminUI.js is ready!");

  $("#button_get").click(function () {
    get_data();
  });

  $("#button_set").click(function () {
    set_data();
  });
});

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

$(document).ready(function () {
  console.log("AdminUI.js is ready!");

  $("#button_download").click(function () {
    get_data();
  });
});

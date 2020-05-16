class PreviewContent {
  constructor(page_content_id) {
    this.page_content_id = page_content_id;

    if ($(this.page_content_id).length != 1) {
      console.error("PreviewContent: Given "+this.page_content_id+" points to "+$(this.page_content_id).length+" objects");
    }
  }

  update(page_data) {
    var server_connect = new ServerConnect(SERVER_URL, $("#password").val());

    var obj = this;
    server_connect.get_preview_html(page_data)
      .then(function(page_data) {
        $("#page_content_preview").html(page_data.html);

        // Handle Google font CSS links
        $("[href*='https://fonts.googleapis.com/css?family='][rel='stylesheet']").remove();
        $("head").append(page_data.head);
      })
      .catch(error => alert(error));
  }
}

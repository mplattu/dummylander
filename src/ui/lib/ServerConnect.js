class ServerConnect {
  constructor(server_url, password) {
    this.server_url = server_url;
    this.password = password;
  }

  get_file_data() {
    var obj = this;
    return new Promise(function(resolve, reject) {
      var post_data = {
        type: "POST",
        url: obj.server_url,
        data: {
          password: obj.password,
          function: "file_list"
        }
      };

      var jqxhr = $.post(post_data)
        .done(function(data) {
          var data_obj = JSON.parse(data);

          if (data_obj.success) {
            resolve(data_obj.data);
          }
          else {
            console.error("ServerConnect.get_file_data() failed. Data:", data_obj);
            reject(new Error("Server returned error. See console."));
          }
        })
        .fail(function(data) {
          console.error("ServerConnect.get_file_data() failed. Data:", data);
          reject(new Error("Could not contact server. See console."));
        });
    });
  }
}

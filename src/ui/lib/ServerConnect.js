class ServerConnect {
  constructor(server_url, password) {
    this.server_url = server_url;
    this.password = password;
  }

  post(post_data) {
    return new Promise(function(resolve, reject) {
      var jqxhr = $.post(post_data)
        .done(function(data) {
          var data_obj = JSON.parse(data);

          if (data_obj.success) {
            resolve(data_obj.data);
          }
          else {
            console.error("ServerConnect.post() failed. Data:", data_obj);
            reject(new Error("Server returned error. See console."));
          }
        })
        .fail(function(data) {
          console.error("ServerConnect.post() failed. Data:", data);
          reject(new Error("Could not contact server. See console."));
        });
    });
  }

  check_password() {
    var obj = this;
    return new Promise(function(resolve, reject) {
      var post_data = {
        type: "POST",
        url: obj.server_url,
        data: {
          password: obj.password,
          function: "get"
        }
      };

      obj.post(post_data)
        .then(data => resolve(true))
        .catch(data => resolve(false))
    });
  }

  get_page_data() {
    var obj = this;
    return new Promise(function(resolve, reject) {
      var post_data = {
        type: "POST",
        url: obj.server_url,
        data: {
          password: obj.password,
          function: "get"
        }
      };

      obj.post(post_data)
        .then(data => resolve(data))
        .catch(error => alert(error));
    });
  }

  set_page_data(page_data) {
    var obj = this;
    return new Promise(function(resolve, reject) {
      var post_data = {
        type: "POST",
        url: obj.server_url,
        data: {
          password: obj.password,
          function: "set",
          data: JSON.stringify(page_data)
        }
      };

      obj.post(post_data)
        .then(data => resolve(data))
        .catch(error => alert(error));
    });
  }

  get_preview_html(page_data) {
    var obj = this;
    return new Promise(function(resolve, reject) {
      var post_data = {
        type: "POST",
        url: obj.server_url,
        data: {
          password: obj.password,
          function: "preview",
          data: JSON.stringify(page_data)
        }
      };

      obj.post(post_data)
        .then(data => resolve(data))
        .catch(error => alert(error));
    });
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

      obj.post(post_data)
        .then(data => resolve(data))
        .catch(error => alert(error));
    });
  }

  delete_file(filename) {
    var obj = this;
    return new Promise(function(resolve, reject) {
      var post_data = {
        type: "POST",
        url: obj.server_url,
        data: {
          password: obj.password,
          function: "file_delete",
          data: filename
        }
      };

      obj.post(post_data)
        .then(data => resolve(data))
        .catch(error => alert(error));
    });
  }

  upload_file(file_to_upload) {
    var data = new FormData();
    data.append('file_upload', file_to_upload);
    data.append('password', this.password);
    data.append('function', 'file_upload');

    var obj = this;

    return new Promise(function (resolve, reject) {
      $.ajax({
        url: obj.server_url,
        data: data,
        cache: false,
        contentType: false,
        processData: false,
        method: 'POST',
        success: function(data) {
          var data_obj = JSON.parse(data);

          if (data_obj.success) {
            resolve(data_obj.data);
          }
          else {
            console.error("ServerConnect.upload_file() failed. Data:", data_obj);
            reject(new Error("Server returned error. See console."));
          }
        },
        error: function(data, error) {
          console.error("ServerConnect.upload_file() failed. Data:", data);
          reject(new Error("Could not contact server. See console."));
        }
      });
    });
  }

}

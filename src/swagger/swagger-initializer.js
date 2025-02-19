window.onload = function() {
  //<editor-fold desc="Changeable Configuration Block">

  if (location.hostname == "localhost") {
    var path = "localhost/git/api.lovd.nl/src";
  } else {
    var path = "api.lovd.nl";
  }

  // the following lines will be replaced by docker/configurator, when it runs in a docker-container
  window.ui = SwaggerUIBundle({
    urls: [
        {
          name: "v1 (2022-11-29)",
          url: window.location.protocol + "//" + path + "/v1/swagger.json"
        },
        {
          name: "v2 (2025-02-19)",
          url: window.location.protocol + "//" + path + "/v2/swagger.json"
        }
    ],
    "urls.primaryName": "v2 (2025-02-19)",
    dom_id: '#swagger-ui',
    deepLinking: true,
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIStandalonePreset
    ],
    plugins: [
      SwaggerUIBundle.plugins.DownloadUrl
    ],
    layout: "StandaloneLayout"
  });

  //</editor-fold>
};

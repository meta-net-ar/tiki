<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>{tr}API Documentation{/tr}</title>
    <link rel="stylesheet" type="text/css" href="{$asset_path}swagger-ui.css" />
    <link rel="icon" type="image/png" href="{$asset_path}favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="{$asset_path}favicon-16x16.png" sizes="16x16" />
    <style>
      html
      {
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
      }


      *,
      *:before,
      *:after
      {
        box-sizing: inherit;
      }


      body
      {
        margin:0;
        background: #fafafa;
      }
    </style>
  </head>

  <body>
    <div id="loading-spinner"  style="display:block; position: absolute; top: 50%; left: 50%;">
        <img src="../img/spinner.gif" alt="{tr}Loading...{/tr}" />
    </div>
    <div id="swagger-ui"></div>

    <script src="{$asset_path}swagger-ui-bundle.js" charset="UTF-8"> </script>
    <script>
    window.onload = function() {
      // Begin Swagger UI call region
      const ui = SwaggerUIBundle({
        url: "{$base_url}api/docs/index.yaml",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        onComplete: function() {
            // Set an interval to check for endpoint elements
            const checkLoaded = setInterval(() => {
                const endpointsLoaded = document.querySelectorAll('.opblock').length > 0;


                // Hide the "No operations defined in spec!" message if it exists
                const noOperationsMessage = document.querySelector('#swagger-ui h3');
                if (noOperationsMessage && noOperationsMessage.innerText === "No operations defined in spec!") {
                    noOperationsMessage.style.display = 'none';
                }


                if (endpointsLoaded) {
                     // Endpoints are fully loaded, remove the spinner and show Swagger UI
                    document.getElementById('loading-spinner').style.display = 'none';
                    document.getElementById('swagger-ui').style.display = 'block';


                    clearInterval(checkLoaded);  // Stop checking once  loaded
                }
            }, 100); // Check every 100ms
        },
        onFailure: function(error) {
           // Hide the loading spinner and display an error message
            const loadingElement = document.getElementById('loading-spinner');
            loadingElement.style.display = 'none';


            document.getElementById('loading').innerHTML =  "<h3>{tr}Failed to load API documentation.{/tr}</h3>";
        },
      });
      // End Swagger UI call region


      window.ui = ui;
    };
  </script>
  </body>
</html>

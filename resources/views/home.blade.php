<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Green Hostel</title>

  @vite(['resources/css/greenhostel.css', 'resources/js/app.js'])

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

  <script>
    window.addEventListener('DOMContentLoaded', function(){ setTimeout(()=>window.dispatchEvent(new Event('gmap-ready')), 50); });
  </script>
</head>
<body>
  <div id="app"></div>
</body>
</html>

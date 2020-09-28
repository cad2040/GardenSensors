<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="Garden Sensors Dashboard" content="" />
        <title>Garden Sensors Dashboard</title>
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
        <style>
          /* Style the stats tabs */
          div.tab {
          overflow: hidden;
          border: 1px solid #ccc;
          background-color: #f1f1f1;
          }

          /* Style the buttons inside the tab */
          div.tab button {
          background-color: inherit;
          float: left;
          border: none;
          outline: none;
          cursor: pointer;
          padding: 14px 16px;
          transition: 0.3s; 
          }

          /* Change background color of buttons on hover */
          div.tab button:hover {
          background-color: #ddd;
          }

          /* Create an active/current tablink class */
          div.tab button.active {
          background-color: #ccc;
          }

          /* Style the tab content */
          .tabcontent {
          display: none;
          padding: 6px 12px;
          border: 1px solid #ccc;
          border-top: none;
          }
        </style>
    </head>

    <body class = "application">
      <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container-fluid">
      <div class="navbar-header">
        <a class="navbar-brand">
        <h1 class="dash-title dash-title-languages" style="color:#000000;"> 
          Garden Sensors Dashboard</h1>
        </a>
      </div>
      </div>
      </div>
      <div class="w3-col s8 w3-left">
      <div class="table-title">
        Plot
      <div id="grid-1-2">
      <div>
      <iframe width="100%" height="650px" 
      frameborder="0" scrolling="yes" 
      src="http://192.168.1.210:8123/local/SensorSector01Plot.html">
      </iframe>
      </div>
      </div>
      </div>
      </div>
    </body>
</html>

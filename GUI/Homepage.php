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

      <!---Create Tab Buttons--->
      <div class="tab">
        <button class="tablinks" onclick="openDash(event, 'Dashboard')"
                id="defaultOpen">Dashboard</button>
        <button class="tablinks" onclick="openDash(event, 'Add Sensor')">
        Add Sensor</button>
        <button class="tablinks" onclick="openDash(event, 'Add Plant')">
        Add Plant</button>
        <button class="tablinks" onclick="openDash(event, 'Assign Sensor')">
        Assign Sensor</button>
      </div>

      <?php
        $url="MYSQL IP:3306";
        $username="MYSQL UNAME";
        $password="MYSQL PASS";
        $conn=mysqli_connect($url,$username,$password,"SoilSensors");
        if(!$conn){
        die('Could not Connect My Sql:' .mysql_error());
        }
      ?>

      <div id="Dashboard" class="tabcontent">        
      <div class="w3-col s8 w3-left">
      <div class="table-title">
      <?php
        $query="SELECT * FROM SoilSensors.Plots";
        if ($result = mysqli_query($conn, $query)) {
        while ($row = mysqli_fetch_row($result)) {
        $sensor=$row[2];
        echo $sensor;
      ?>
      <div id=>
      <div>
      <iframe width="100%" height="450px" 
      frameborder="0" scrolling="yes" 
      src= <?php echo $row[3];?>>
      </iframe>
      </div>
      </div>
      <?php
        }
        mysqli_free_result($result);
        }
      ?>
      </div>
      </div>
      </div>



      <div id="Add Sensor" class="tabcontent">
      <div class="w3-col s8 w3-left">
      <div class="table-title">
        Plot
      <div id="grid-1-2">
      <div>
      

      </div>
      </div>
      </div>
      </div>
      </div>




      <div id="Add Plant" class="tabcontent">
      <div class="w3-col s8 w3-left">
      <div class="table-title">
        Plot
      <div id="grid-1-2">
      <div>
      

      </div>
      </div>
      </div>
      </div>
      </div>





      <div id="Assign Sensor" class="tabcontent">
      <div class="w3-col s8 w3-left">
      <div class="table-title">
        Plot
      <div id="grid-1-2">
      <div>
      

      </div>
      </div>
      </div>
      </div>
      </div>

      <script>
        // Javascript function to show only selected tab contents
        function openDash(evt, dashName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className
                    .replace(" active", "");
            }
            document.getElementById(dashName).style.display = "block";
            evt.currentTarget.className += " active";
        }
      </script>
      <script>
        // Javascript function to set default selected tab
        document.getElementById("defaultOpen").click();
      </script>
    </body>
</html>

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
        <button class="tablinks" onclick="openDash(event, 'Show Sensors')">
        Show Sensors</button>
        <button class="tablinks" onclick="openDash(event, 'Add Sensor')">
        Add Sensor</button>
        <button class="tablinks" onclick="openDash(event, 'Show Plant Data')">
        Show Plant Data</button>
        <button class="tablinks" onclick="openDash(event, 'Add Plant')">
        Add Plant</button>
        <button class="tablinks" onclick="openDash(event, 'Update Plant Data')">
        Update Plant Data</button>
        <button class="tablinks" onclick="openDash(event, 'Assign Sensor')">
        Assign Sensor</button>
      </div>

      <?php
        $url="MYSQLHOST:3306";
        $username="SoilSensors";
        $password="MYSQLPASS";
        $conn=mysqli_connect($url,$username,$password,"SoilSensors");
        if(!$conn){
        die('Could not Connect My Sql:' .mysql_error());
        }
      ?>

      <div id="Dashboard" class="tabcontent">        
      <div class="w3-col s8 w3-left">
      <div class="table-title">
      <br>
      <?php
        $query="SELECT * FROM SoilSensors.Plots";
        if ($result = mysqli_query($conn, $query)) {
        while ($row = mysqli_fetch_row($result)) {
        $sensor=$row[2];
        echo $sensor;
      ?>
      <div id=>
      <div>
      <iframe id = <?php echo $sensor; ?> width="100%" height="450px" 
      frameborder="0" scrolling="yes" 
      src= <?php echo $row[3];?>>
      </iframe>
      </div>
        <script type = "text/javascript">
          var iframe = document.getElementById(<?php echo $sensor; ?>);
          iframe.src = iframe.src;
        </script>
      </div>
      <?php
        }
        mysqli_free_result($result);
        }
        mysqli_close($conn)
      ?>
      </div>
      </div>
      </div>


      <div id="Show Sensors" class="tabcontent">
      <div class="w3-col s8 w3-left">
      <div class="table-title">
        <br><br>
        Current Sensor Assignments
      <div id="grid-1-2">
      <div>
      <br>
      <?php
        $conn=mysqli_connect($url,$username,$password,"SoilSensors");
        if(!$conn){
        die('Could not Connect My Sql:' .mysql_error());
        }
        $ShowAssignments="SELECT S.sensor, DP.plant FROM SoilSensors.Sensors S 
                                        JOIN SoilSensors.FactPlants FP ON S.id = FP.sensor_id
                                        JOIN SoilSensors.DimPlants DP ON FP.plant_id = DP.id";
        if ($result = mysqli_query($conn, $ShowAssignments)) {
         echo "<table border='1'>
                  <tr>
                  <th>Sensor</th>
                  <th>Assigned Plant</th>
                  </tr>";
          while ($row = mysqli_fetch_row($result)) {
          echo "<tr>";
          echo "<td>" . $row[0] . "</td>";
          echo "<td>" . $row[1] . "</td>";
          echo "</tr>";
          }
          mysqli_free_result($result);
          }
          echo "</table>";
          mysqli_close($conn)
      ?>
      </div>
      </div>
      </div>
      </div>
      </div>


      <div id="Add Sensor" class="tabcontent">
      <div class="w3-col s8 w3-left">
      <div class="table-title">
        <br><br>
        Add a new sensor to database
      <div id="grid-1-2">
      <div>
      <br>
      <form action="insertSensor.php" method="post">
        Sensor name: <input type="text" name="sname" /><br><br>
        <input type="submit" />
      </form>
      </div>
      </div>
      </div>
      </div>
      </div>


      <div id="Show Plant Data" class="tabcontent">
      <div class="w3-col s8 w3-left">
      <div class="table-title">
        <br><br>
        Current Plant settings
      <div id="grid-1-2">
      <div>
      <br>
      <?php
        $conn=mysqli_connect($url,$username,$password,"SoilSensors");
        if(!$conn){
        die('Could not Connect My Sql:' .mysql_error());
        }
        $ShowPlantData="SELECT plant, minSoilMoisture, maxSoilMoisture FROM SoilSensors.DimPlants";
        if ($result = mysqli_query($conn, $ShowPlantData)) {
         echo "<table border='1'>
                  <tr>
                  <th>Plant</th>
                  <th>min Soil Moisture</th>
                  <th>max Soil Moisture</th>
                  </tr>";
          while ($row = mysqli_fetch_row($result)) {
          echo "<tr>";
          echo "<td>" . $row[0] . "</td>";
          echo "<td>" . $row[1] . "</td>";
          echo "<td>" . $row[2] . "</td>";
          echo "</tr>";
          }
          mysqli_free_result($result);
          }
          echo "</table>";
          mysqli_close($conn)
      ?>
      </div>
      </div>
      </div>
      </div>
      </div>


      <div id="Add Plant" class="tabcontent">
      <div class="w3-col s8 w3-left">
      <div class="table-title">
        <br><br>
        Add new plant to database
      <div id="grid-1-2">
      <div>
      <br>
      <form action="insertPlant.php" method="post">
        Plant Name: <input type="text" name="pname" /><br><br>
        Min Soil Moisture: <input type="text" name="soilmin" /><br><br>
        Max Soil Moisture: <input type="text" name="soilmax" /><br><br>
        <input type="submit" />
      </form>
      </div>
      </div>
      </div>
      </div>
      </div>


      <div id="Update Plant Data" class="tabcontent">
      <div class="w3-col s8 w3-left">
      <div class="table-title">
        <br><br>
        update plant min/max soil moisture
      <div id="grid-1-2">
      <div>
      <br>
      <form action="updatePlant.php" method="post">
      <?php
        $conn=mysqli_connect($url,$username,$password,"SoilSensors");
        if(!$conn){
        die('Could not Connect My Sql:' .mysql_error());
        }
        $sqlPlants2="SELECT plant FROM SoilSensors.DimPlants";
      ?>
        <label for="plantsup">Select plant</label>
        <select name="plantsup" id="plantsup">
        <?php
          if ($result = mysqli_query($conn, $sqlPlants2)) {
          while ($row = mysqli_fetch_row($result)) {
          echo "<option value='" . $row[0] . "'>" . $row[0] . "</option>";
          }
          mysqli_free_result($result);
          }
          mysqli_close($conn)
        ?>
        </select> <br><br>
        Min Soil Moisture: <input type="text" name="soilminup" /><br><br>
        Max Soil Moisture: <input type="text" name="soilmaxup" /><br><br>
        <input type="submit" />
      </form>
      </div>
      </div>
      </div>
      </div>
      </div>


      <div id="Assign Sensor" class="tabcontent">
      <div class="w3-col s8 w3-left">
      <div class="table-title">
      <br><br>
        Assign plant type to sensor
      <div id="grid-1-2">
      <div>
      <?php
        $sqlPlants="SELECT plant FROM SoilSensors.DimPlants";
        $sqlSensors="SELECT sensor FROM SoilSensors.Sensors";
        $conn=mysqli_connect($url,$username,$password,"SoilSensors");
        if(!$conn){
        die('Could not Connect My Sql:' .mysql_error());
        }
      ?>
      <form action="assignSensor.php"method="POST">
        <select name="plants" id="plants">
          <?php
          if ($result = mysqli_query($conn, $sqlPlants)) {
          while ($row = mysqli_fetch_row($result)) {
          echo "<option value='" . $row[0] . "'>" . $row[0] . "</option>";
          }
          mysqli_free_result($result);
          }
          ?>
        </select>
        <select name="sensors" id="sensors">
          <?php
          if ($result = mysqli_query($conn, $sqlSensors)) {
          while ($row = mysqli_fetch_row($result)) {
          echo "<option value='" . $row[0] . "'>" . $row[0] . "</option>";
          }
          mysqli_free_result($result);
          }
          mysqli_close($conn)
          ?>
        </select>
        <input type="submit" />
      <?form>
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

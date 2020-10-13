<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
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
    <body>
        <?php
        $url="MYSQLHOST:3306";
        $username="SoilSensors";
        $password="MYSQLPASS";
        $conn=mysqli_connect($url,$username,$password,"SoilSensors");
        if(!$conn){
        die('Could not Connect My Sql:' .mysql_error());
        }
        $sqlsensors="SELECT id, sensor FROM SoilSensors.Sensors WHERE sensor ='".$_POST[sensors]."'";
        if($result = mysqli_query($conn, $sqlsensors))  {
        while ($row = mysqli_fetch_row($result)) {
          $sensorid=$row[0]; 
          }
        mysqli_free_result($result);
        }
        
        $sensorscheck="SELECT * FROM SoilSensors.Pins WHERE sensor_id =".$sensorid;
        if($result = mysqli_query($conn, $sensorscheck))  {
        if (mysqli_num_rows($result)>0) {
        $sqlupdate="UPDATE SoilSensors.Pins SET pin =".$_POST[pin]." WHERE sensor_id=".$sensorid;
        if (mysqli_query($conn,$sqlupdate))
        {
        echo "Sensor record updated successfully";
        } else {
        echo "Error: " . $sql . "" . mysqli_error($conn);
        }
        } else {
        $sqlinsert="INSERT INTO SoilSensors.Pins (sensor_id,pin) 
                  VALUES (".$sensorid.",".$_POST[pin].")";
        if (mysqli_query($conn,$sqlinsert))
        {
        echo "New sensor assignment created successfully";
        } else {
        echo "Error: " . $sql . "" . mysqli_error($conn);
        }
        }
        mysqli_free_result($result);
        }
        mysqli_close($conn)
        ?>
        <br>
        <br>
        <br>
        <form action="Homepage.php">
        <button>"Return to Dashboard" </button>
      </form>
    </body>
</html>

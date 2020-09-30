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
        $url="MYSQLHOSTIP:3306";
        $username="SoilSensors";
        $password="MYSQLPWORD";
        $conn=mysqli_connect($url,$username,$password,"SoilSensors");
        if(!$conn){
        die('Could not Connect My Sql:' .mysql_error());
        }
        $sql="INSERT INTO SoilSensors.Sensors (sensor,inserted) VALUES ('".$_POST[sname]."',NOW())";
        if (mysqli_query($conn,$sql))
        {
        echo "New record created successfully";
        } else {
        echo "Error: " . $sql . "" . mysqli_error($conn);
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

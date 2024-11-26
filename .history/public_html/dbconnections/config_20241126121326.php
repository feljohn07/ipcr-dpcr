<?php
$servername = "auth-db1570.hstgr.io"; 
$username = "u574655838_Godisoursavior";
$password = "Asscatipcr.com123"; 
// $username = "localhost";
// $password = ""; 
$dbname = "u574655838_Godisoursavior"; 


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>


<?php
    // Simple mysql connection file
    $servername = "";
    $username = "";
    $password = "";
    $dbname = "";
            
    $conn = mysqli_connect($servername, $username, $password, $dbname);
            
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
?>
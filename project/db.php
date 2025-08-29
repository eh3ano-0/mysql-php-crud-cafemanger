<?php
$servername = "127.0.0.1"; 
$username = "";
$password = "";
$dbname = "cafesql";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

// بررسی اتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

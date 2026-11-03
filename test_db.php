<?php
$servername = "localhost";
$username   = "root";
$password   = "b54eawxj5h8ev";
$dbname     = "DOA_MASTER";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully";

$conn->close();
?>
<?php
function connectDB() {
    $conn = new mysqli("localhost", "root", "", "ticketing");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
?>

<?php
// Database credentials
$HOSTNAME = 'localhost';
$USERNAME = 'root';
$PASaSWORD = '';
$DATABASE = 'study_planner';

// Create connection
$con = mysqli_connect($HOSTNAME, $USERNAME, $PASSWORD, $DATABASE);

// Check connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: Uncomment this line to confirm connection during testing
// echo "Connection successful";
?>
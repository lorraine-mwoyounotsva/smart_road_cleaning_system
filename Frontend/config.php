<?php

// Database configuration for a PHP application using XAMPP
$host = 'localhost';         
$user = 'root';              // default user in XAMPP
$password = '';              // default has no password
$dbname = 'smart_cleaning_db';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function base_url($path = '') {
    return 'http://' . $_SERVER['HTTP_HOST'] . '/smart_road_cleaning_system/' . ltrim($path, '/');
}

?>
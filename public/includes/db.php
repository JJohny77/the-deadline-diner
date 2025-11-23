<?php
// Database connection settings
$host = "127.0.0.1"; // ή "localhost" αν έτσι είναι στο Workbench
$port = 3306;        // αν στο Workbench είναι άλλο, βάλε το άλλο
$username = "root";  // ή ο χρήστης που βλέπεις εκεί
$password = "Olympiakos7"; 
$database = "deadline_diner";

// Connect to MySQL
$conn = mysqli_connect($host, $username, $password, $database, $port);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Optional: Set UTF-8 encoding
mysqli_set_charset($conn, "utf8mb4");
?>

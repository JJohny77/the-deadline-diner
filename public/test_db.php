<?php
include "includes/db.php";

echo "<h2>Database Test</h2>";

$result = mysqli_query($conn, "SHOW TABLES");

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

echo "<strong>Tables found:</strong><br><br>";

while ($row = mysqli_fetch_array($result)) {
    echo "- " . $row[0] . "<br>";
}

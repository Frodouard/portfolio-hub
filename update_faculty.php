<?php
/**
 * Quick update script to change Software Engineering to Information Technology
 * Run this once then delete it.
 */
require_once __DIR__ . '/includes/db.php';

$query = "UPDATE departments SET department_name = 'Information Technology', department_code = 'IT' WHERE department_name = 'Software Engineering'";

if (mysqli_query($conn, $query)) {
    echo "✓ Faculty updated successfully to Information Technology";
    echo "<br><a href='dashboard.php'>Go back to dashboard</a>";
} else {
    echo "Error updating database: " . mysqli_error($conn);
}

mysqli_close($conn);
?>

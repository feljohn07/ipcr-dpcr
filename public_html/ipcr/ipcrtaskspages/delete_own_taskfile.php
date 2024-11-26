<?php
session_start();
include '../../dbconnections/config.php'; // Include your database connection

// Ensure user is logged in
if (!isset($_SESSION['idnumber'])) {
    die('Unauthorized access');
}

// Check if the required POST parameters are set
if (isset($_POST['file']) && isset($_POST['task_id']) && isset($_POST['group_task_id'])) {
    $file = urldecode($_POST['file']); // Decode the file name
    $task_id = $_POST['task_id'];
    $group_task_id = $_POST['group_task_id'];

    // Prepare SQL statement to delete the file from the ipcr_file_submitted table
    $stmt = $conn->prepare("DELETE FROM ipcr_file_submitted WHERE task_id = ? AND group_task_id = ? AND file_name = ?");
    $stmt->bind_param("sss", $task_id, $group_task_id, $file);

    // Execute the query
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "File deleted successfully.";

            // Decrement the documents_uploaded count
            $updateStmt = $conn->prepare("UPDATE ipcrsubmittedtask SET documents_uploaded = documents_uploaded - 1 WHERE task_id = ? AND group_task_id = ?");
            $updateStmt->bind_param("ss", $task_id, $group_task_id);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            echo "No file found to delete.";
        }
    } else {
        echo "Error deleting file: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
} else {
    echo "Required data not provided.";
}

// Close the database connection
$conn->close();
?>

<?php
// Database connection
include __DIR__ . '/../../../dbconnections/config.php';

// Check first if email already exists
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {

    // Retrieve the email and idnumber from the POST data
    $email = $_POST['email'];
    $idnumber = $_POST['idnumber']; // Assuming the idnumber is passed via the form

    // ================== Verify Email ======================================

    // Update email in the database where idnumber matches
    global $conn; // Make sure $conn is available inside the function

    // Check if the email already exists in the database (excluding the current user's email)
    $countQuery = "SELECT COUNT(*) FROM usersinfo WHERE gmail=? AND idnumber != ?";

    if ($stmt = $conn->prepare($countQuery)) {
        $stmt->bind_param("si", $email, $idnumber); // Bind the email and idnumber (as an integer)
        $stmt->execute();
        $stmt->bind_result($emailCount);
        $stmt->fetch();
        $stmt->close();

        // If email already exists for another account, show the message
        if ($emailCount > 0) {
            echo 'Email Used.';
            return; // Stop further execution if the email exists for another account
        } else {
            echo 'Email not used.';
        }

    } else {
        echo "Error preparing statement: " . $conn->error;
        return;
    }

    // ======================================================================
}

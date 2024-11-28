<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the submitted email value
    if (isset($_POST['email'])) {
        $email = $_POST['email'];
        // Process the email (e.g., validation, database operations, etc.)
        echo "Received email: " . htmlspecialchars($email);


        // Retrieve the value of the hidden field
        $hiddenFieldValue = $_POST['hidden_field'];

        echo "Email: " . htmlspecialchars($email);
        echo "Hidden Field Value: " . htmlspecialchars($hiddenFieldValue);
    } else {
        echo "Email not provided!";
    }
} else {
    echo "Invalid request method!";
}

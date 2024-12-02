<?php
// send_email_async.php
include __DIR__ . '/send_email.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    header('Content-Type: application/json');
    // Get the POST data from the request body
    $data = json_decode(file_get_contents('php://input'), true);

    // Check if message is set in the request
    if (isset($data['message'])) {
        $message = $data['message'];
        sendEmail('feljohn.loe.bangasan@gmail.com', $data['message']);
    }

}
?>
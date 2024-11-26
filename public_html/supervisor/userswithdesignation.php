<?php
session_start();
include '../dbconnections/config.php'; // Include your actual database connection file

// Check if user is logged in
if (!isset($_SESSION['idnumber'])) {
    echo "Please log in to view the list of users.";
    exit();
}

// Get logged-in user's college
$user_college = $_SESSION['college'];

// Prepare SQL statement to fetch users not from the same college with valid designations
$sql = "SELECT idnumber, firstname, lastname, suffix, college, role, gmail, picture, designation 
        FROM usersinfo 
        WHERE college != ? 
        AND designation IS NOT NULL 
        AND designation != 'none'";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

$stmt->bind_param("s", $user_college);

if (!$stmt->execute()) {
    die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
}

$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users List</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
            text-align: center;
        }
        th, td {
            padding: 8px;
            vertical-align: middle; /* Center content vertically */
            text-align: center;
        }
        .profile-img {
            width: 50px; /* Set explicit width */
            height: 50px; /* Set explicit height */
            object-fit: cover; /* Maintain aspect ratio and cover the entire area */
            display: block; /* Ensure image is block-level for centering */
            margin: 0 auto; /* Center horizontally */
        }
    </style>
</head>
<body>
    <h1>Users With Designation</h1>
    <?php
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<thead>";
        echo "<tr><th>Profile</th><th>ID Number</th><th>First Name</th><th>Last Name</th><th>Suffix</th><th>College</th><th>Role</th><th>Designation</th></tr>";
        echo "</thead>";
        echo "<tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td><img src='data:image/jpeg;base64," . base64_encode($row['picture']) . "' alt='Profile Picture' class='profile-img'></td>
                    <td>" . htmlspecialchars($row['idnumber']) . "</td>
                    <td>" . htmlspecialchars($row['firstname']) . "</td>
                    <td>" . htmlspecialchars($row['lastname']) . "</td>
                    <td>" . htmlspecialchars($row['suffix']) . "</td>
                    <td>" . htmlspecialchars($row['college']) . "</td>
                    <td>" . htmlspecialchars($row['role']) . "</td>
                    <td>" . htmlspecialchars($row['designation']) . "</td> <!-- Added designation here -->
                    <td>" . htmlspecialchars($row['gmail']) . "</td>
                  </tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "No users found not from your college with valid designations.";
    }

    $stmt->close();
    ?>
</body>
</html>
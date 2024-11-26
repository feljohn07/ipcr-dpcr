<?php
session_start();
include '../../../dbconnections/config.php'; // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION['idnumber'])) {
    header('Location: ../../ipcrdash.php'); // Redirect to login if not logged in
    exit();
}

// Initialize variables
$groupTaskId = $_GET['group_task_id'] ?? null;
$semesterId = $_GET['semester_id'] ?? null;

// Check if the group_task_id and semester_id are set in session
if (!isset($_SESSION['original_group_task_id']) || !isset($_SESSION['original_semester_id'])) {
    // Store original values in session if not set
    $_SESSION['original_group_task_id'] = $groupTaskId;
    $_SESSION['original_semester_id'] = $semesterId;
} else {
    // If the values in the URL don't match the session values, redirect
    if ($_SESSION['original_group_task_id'] !== $groupTaskId || $_SESSION['original_semester_id'] !== $semesterId) {
        header('Location: ../../ipcrdash.php'); // Redirect back to the dashboard
        exit();
    }
}

$categorizedTasks = [
    'strategic' => [],
    'core' => [],
    'support' => []
];

// Fetch tasks for the specified group task ID and semester ID
if ($groupTaskId && $semesterId) {
    $stmt = $conn->prepare("
    SELECT task_id, task_name, description, documents_required, due_date, task_type 
    FROM ipcrsubmittedtask 
    WHERE group_task_id = ? AND id_of_semester = ?
    ORDER BY task_id
");
$stmt->bind_param("ss", $groupTaskId, $semesterId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Group tasks by their type
    $categorizedTasks[$row['task_type']][] = $row; 
}

    $stmt->close();
} else {
    echo "Invalid request.";
    exit();
}

// Set the default timezone to the Philippines
date_default_timezone_set('Asia/Manila');

// Handle form submission for editing tasks and adding new tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update existing tasks
// Update existing tasks
foreach ($_POST['tasks'] as $taskData) {
    $taskId = $taskData['task_id'];
    $taskName = $taskData['task_name'];
    $description = $taskData['description'];
    $documentsRequired = $taskData['documents_required'];
    $dueDate = $taskData['due_date']; // Get the due date from the form

    // Update the task in the database
    $updateStmt = $conn->prepare("
        UPDATE ipcrsubmittedtask 
        SET task_name = ?, description = ?, documents_required = ?, due_date = ? 
        WHERE task_id = ?
    ");
    $updateStmt->bind_param("ssssi", $taskName, $description, $documentsRequired, $dueDate, $taskId);
    $updateStmt->execute();
    $updateStmt->close();
}
    // Insert new tasks
    if (isset($_POST['new_tasks'])) {
        // Fetch the semester name based on the semester_id
        $semesterName = '';
        $semesterId = $_GET['semester_id'] ?? null;

        if ($semesterId) {
            $semesterQuery = $conn->prepare("SELECT semester_name FROM semester_tasks WHERE semester_id = ?");
            $semesterQuery->bind_param("s", $semesterId);
            $semesterQuery->execute();
            $semesterResult = $semesterQuery->get_result();

            if ($semesterRow = $semesterResult->fetch_assoc()) {
                $semesterName = $semesterRow['semester_name'];
            }
            $semesterQuery->close();
        }

        foreach ($_POST['new_tasks'] as $newTaskData) {
            $taskName = $conn->real_escape_string($newTaskData['task_name']);
            $description = $conn->real_escape_string($newTaskData['description']);
            $documentsRequired = (int)$newTaskData['documents_required'];
            $taskType = $newTaskData['task_type']; // This should be set based on the button clicked
            $dueDate = $newTaskData['due_date']; // Capture the due date
        
            // Retrieve user information from session
            $college = $_SESSION['college'];
            $idnumber = $_SESSION['idnumber'];
            $firstname = $_SESSION['firstname'];
            $lastname = $_SESSION['lastname'];
        
            // Insert new task into the database
            $sql = "INSERT INTO ipcrsubmittedtask (task_name, description, documents_required, task_type, group_task_id, id_of_semester, college, idnumber, firstname, lastname, name_of_semester, created_at, is_read, due_date) 
                    VALUES ('$taskName', '$description', $documentsRequired, '$taskType', '$groupTaskId', '$semesterId', '$college', '$idnumber', '$firstname', '$lastname', '$semesterName', NOW(), 1, '$dueDate')";
        
            if (!$conn->query($sql)) {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }

    // Redirect back to the tasks page or show a success message
    header('Location: ../../ipcrdash.php'); // Change to your tasks page
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tasks</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f1f5f9; /* Lighter background for better readability */
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #1e3a8a; /* Darker blue for heading */
            font-size: 26px;
            margin-bottom: 20px;
        }
        form {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            padding: 25px;
            max-width: 100%;
            margin: auto;
        }
        h3 {
            color: #1e3a8a; /* Dark blue for subheadings */
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            border-bottom: 2px solid #ccc;
            padding-bottom: 5px;
        }
        label {
            display: block;
            font-size: 15px;
            color: #374151; /* Dark gray for labels */
            margin-bottom: 6px;
            font-weight: 600;
            text-align: center;
        }
        .task {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .task div {
            flex: 1;
            min-width: 240px;
        }
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #94a3b8; /* Light gray border */
            border-radius: 6px;
            font-size: 15px;
            color: #1f2937; /* Darker text for better readability */
            background-color: #f8fafc;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            border-color: #2563eb; /* Bright blue on focus */
            box-shadow: 0 0 6px rgba(37, 99, 235, 0.3);
        }
        textarea {
            resize: vertical;
            height: 80px;
        }
        button[type="submit"] {
            background-color: #2563eb; /* Bright blue button */
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 17px;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.2s;
        }
        button[type="submit"]:hover {
            background-color: #1e40af; /* Darker blue on hover */
            transform: translateY(-2px);
        }
        button[type="submit"]:active {
            transform: translateY(0);
        }
        hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }
        
        button.delete-task {
        background-color: #dc2626; /* Red color for delete button */
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 15px;
        font-weight: bold;
        transition: background-color 0.3s, transform 0.2s;
        display: inline-block; /* Ensure it behaves like a button */
        }

        button.delete-task:hover {
            background-color: #b91c1c; /* Darker red on hover */
            transform: translateY(-2px);
        }

        button.delete-task:active {
            transform: translateY(0);
        }

        button.add-task-button {
            background-color: #4caf50; /* Green color for add task button */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.2s;
            display: inline-block; /* Ensure it behaves like a button */
            margin-top: 10px; /* Add some space above the button */
        }

        button.add-task-button:hover {
            background-color: #388e3c; /* Darker green on hover */
            transform: translateY(-2px);
        }

        button.add-task-button:active {
            transform: translateY(0);
        }
    </style>
    <script>
function deleteTask(taskId) {
    if (confirm("Are you sure you want to delete this task?")) {
        // Send an AJAX request to delete the task
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../actions/delete_own_task.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // Set up the onload event handler
        xhr.onload = function() {
            console.log('Response Text:', xhr.responseText); // Log the response for debugging
            console.log('Response Length:', xhr.responseText.length); // Log the length of the response
            if (xhr.status === 200) {
                if (xhr.responseText.trim() === 'success') {
                    document.getElementById('task_' + taskId).remove();
                } else {
                    alert('Failed to delete task: ' + xhr.responseText); // Include the response text in the alert
                }
            }
        };

        // Send the request with the task_id
        xhr.send('task_id=' + taskId); // Send task_id to delete
    }
}   

function addTaskRow(type) {
    const taskContainer = document.getElementById(type + '_tasks');
    const taskId = Date.now(); // Unique ID for the new task

    const newTaskRow = `
        <div class="task" id="task_${taskId}">
            <input type="hidden" name="new_tasks[${taskId}][task_id]" value="${taskId}">
            <div>
                <label for="task_name_${taskId}">Task Name:</label>
                <textarea id="task_name_${taskId}" name="new_tasks[${taskId}][task_name]" required></textarea>
            </div>
            <div>
                <label for="description_${taskId}">Description:</label>
                <textarea id="description_${taskId}" name="new_tasks[${taskId}][description]" required></textarea>
            </div>
            <div>
                <label for="documents_required_${taskId}">Target:</label>
                <input type="number" id="documents_required_${taskId}" name="new_tasks[${taskId}][documents_required]" min="0" required>
            </div>
            <div>
                <label for="due_date_${taskId}">Due Date:</label>
                <input type="date" id="due_date_${taskId}" name="new_tasks[${taskId}][due_date]" required>
            </div>
            <input type="hidden" name="new_tasks[${taskId}][task_type]" value="${type}"> <!-- Automatically set task type -->
            <div>
                <button type="button" class="delete-task" onclick="deleteTask(${taskId})">Delete Task</button>
            </div>
        </div>
        <hr>
    `;
    taskContainer.insertAdjacentHTML('beforeend', newTaskRow);
}
    </script>
        <script>
        function goBack() {
            // Navigate to the specified page
            window.location.href = '../../ipcrdash.php';
        }
    </script>
</head>
<body>
<button id="back-button" type="button" style="background-color: green; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; position: fixed; right: 20px; top: 20px; z-index: 1000;" onclick="goBack()">Back</button>
    <h2>Edit Tasks</h2>
    <form method="POST" action="">
        <?php foreach ($categorizedTasks as $type => $tasks): ?>
            <h3><?php echo ucfirst($type); ?> Tasks</h3>
            <div id="<?php echo $type; ?>_tasks">
                <?php foreach ($tasks as $task): ?>
                    <div class="task" id="task_<?php echo $task['task_id']; ?>">
                        <input type="hidden" name="tasks[<?php echo $task['task_id']; ?>][task_id]" value="<?php echo htmlspecialchars($task['task_id']); ?>">
                        <div>
                            <label for="task_name_<?php echo $task['task_id']; ?>">Task Name:</label>
                            <textarea id="task_name_<?php echo $task['task_id']; ?>" name="tasks[<?php echo $task['task_id']; ?>][task_name]" required><?php echo htmlspecialchars($task['task_name']); ?></textarea>
                        </div>
                        <div>
                            <label for="description_<?php echo $task['task_id']; ?>">Description:</label>
                            <textarea id="description_<?php echo $task['task_id']; ?>" name="tasks[<?php echo $task['task_id']; ?>][description]" required><?php echo htmlspecialchars($task['description']); ?></textarea>
                        </div>
                        <div>
                            <label for="documents_required_<?php echo $task['task_id']; ?>">Target:</label>
                            <input type="number" id="documents_required_<?php echo $task['task_id']; ?>" name="tasks[<?php echo $task['task_id']; ?>][documents_required]" value="<?php echo htmlspecialchars($task['documents_required']); ?>" min="0" required>
                        </div>
                        <div>
                            <label for="due_date_<?php echo $task['task_id']; ?>">Due Date:</label>
                            <input type="date" id="due_date_<?php echo $task['task_id']; ?>" name="tasks[<?php echo $task['task_id']; ?>][due_date]" value="<?php echo htmlspecialchars($task['due_date']); ?>" required>
                        </div>
                        <div>
                            <button type="button" class="delete-task" onclick="deleteTask(<?php echo $task['task_id']; ?>)">Delete Task</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="add-task-button" onclick="addTaskRow('<?php echo $type; ?>')">Add Task</button>
        <?php endforeach; ?>
        <button type="submit" style="margin-top: 20px;">Save Changes</button>
    </form>
</body>
</html>
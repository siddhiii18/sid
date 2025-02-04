<?php
session_start();
include 'db.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['student_id'])) {
    header('Location: index.php?dashboard=1');
    exit();
}

// Handle Registration
if (isset($_POST['register'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $sql = "INSERT INTO students (first_name, last_name, email, password) 
            VALUES ('$first_name', '$last_name', '$email', '$password')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Registration successful! You can now <a href='index.php'>log in</a>.";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Handle Login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM students WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        if (password_verify($password, $student['password'])) {
            $_SESSION['student_id'] = $student['student_id'];
            header('Location: index.php?dashboard=1');
        } else {
            echo "Invalid credentials.";
        }
    } else {
        echo "No user found.";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

// Handle Attendance Marking (Admin side) - For simplicity, we mark attendance in the same file
if (isset($_POST['mark_attendance'])) {
    $attendance_date = $_POST['attendance_date'];
    $schedule_id = $_POST['schedule_id'];
    $student_id = $_POST['student_id'];
    $status = $_POST['status'];

    $sql = "INSERT INTO attendance (student_id, schedule_id, attendance_date, status) 
            VALUES ('$student_id', '$schedule_id', '$attendance_date', '$status')";

    if ($conn->query($sql) === TRUE) {
        echo "Attendance marked successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">

        <!-- Registration Form -->
        <?php if (!isset($_GET['login']) && !isset($_GET['dashboard'])): ?>
        <h2>Student Registration</h2>
        <form method="post">
            First Name: <input type="text" name="first_name" required><br>
            Last Name: <input type="text" name="last_name" required><br>
            Email: <input type="email" name="email" required><br>
            Password: <input type="password" name="password" required><br>
            <input type="submit" name="register" value="Register">
        </form>

        <p>Already have an account? <a href="?login=1">Login</a></p>

        <?php endif; ?>

        <!-- Login Form -->
        <?php if (isset($_GET['login'])): ?>
        <h2>Student Login</h2>
        <form method="post">
            Email: <input type="email" name="email" required><br>
            Password: <input type="password" name="password" required><br>
            <input type="submit" name="login" value="Login">
        </form>

        <p>Don't have an account? <a href="?register=1">Register</a></p>

        <?php endif; ?>

        <!-- Dashboard - Display courses and attendance -->
        <?php if (isset($_GET['dashboard']) && isset($_SESSION['student_id'])): ?>
        <h2>Welcome to the Student Dashboard</h2>
        <?php
        // Fetch courses and attendance details
        $student_id = $_SESSION['student_id'];
        $sql = "SELECT c.course_name, c.course_code, s.schedule_id, s.day_of_week, s.start_time, s.end_time
                FROM courses c
                JOIN schedules s ON c.course_id = s.course_id
                JOIN attendance a ON s.schedule_id = a.schedule_id
                WHERE a.student_id = '$student_id'";
        $result = $conn->query($sql);

        echo "<h3>Your Enrolled Courses</h3>";
        while ($row = $result->fetch_assoc()) {
            echo "Course: " . $row['course_name'] . " (" . $row['course_code'] . ")<br>";
            echo "Day: " . $row['day_of_week'] . ", Time: " . $row['start_time'] . " - " . $row['end_time'] . "<br>";

            // Show attendance status
            $attendance_sql = "SELECT status FROM attendance WHERE student_id = '$student_id' AND schedule_id = " . $row['schedule_id'];
            $attendance_result = $conn->query($attendance_sql);
            $attendance = $attendance_result->fetch_assoc();
            echo "Attendance: " . $attendance['status'] . "<br><br>";
        }
        ?>

        <a href="?logout=1">Logout</a>

        <!-- Admin Section for Marking Attendance -->
        <h3>Mark Attendance (Admin)</h3>
        <form method="post">
            Student ID: <input type="text" name="student_id" required><br>
            Schedule ID: <input type="text" name="schedule_id" required><br>
            Date: <input type="date" name="attendance_date" required><br>
            Status: 
            <select name="status">
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
            </select><br>
            <input type="submit" name="mark_attendance" value="Mark Attendance">
        </form>

        <?php endif; ?>

    </div>
</body>
</html>

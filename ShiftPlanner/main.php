<?php
    require_once 'config.php';

    require_login();
    $msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<head>
<meta charset="utf-8"><title>Main Page</title>
<title>Main Page</title>
</head>
<body>
    <?php if ($msg): ?>
        <div style="color:red"><?=htmlspecialchars($msg)?></div>
        <br>
    <?php endif; ?>
    <a href="logout.php">Logout</a> 
    <br>
    <?php if (!empty($_SESSION)): ?>
        <div style="color:green">Logged in as: <?=htmlspecialchars($_SESSION['fname'] . ' ' . $_SESSION['lname'])?></div>
    <?php endif; ?>  
    <br>
    <?php if(check_position('Manager')): ?>
        <form method="get" action="create_employee.php">
            <button type="submit">Add Employee</button>
        </form>
        <br>
        <form method="get" action="create_position.php">
            <button type="submit">Add Position</button>
        </form>
        <br>
        <form method="get" action="create_shift.php">
            <button type="submit">Add Shift</button>
        </form>
        <br>
        <form method="get" action="schedule_employee.php">
            <button type="submit">Schedule Employee</button>
        </form>
        <br>
        <form method="get" action="manage_time_off.php">
            <button type="submit">Manage Time Off Requests</button>
        </form>
        <br>
        <form method="get" action="edit_shifts.php">
            <button type="submit">Edit Shifts</button>
        </form>
    <?php endif; ?>
    <br>
    <form method="get" action="request_time_off.php">
        <button type="submit">Request Time Off</button>
    </form>
    <br>
    <form method="get" action="view_schedule.php">
        <button type="submit">View Scheduled Shifts</button>
    </form>
    <br>
    <form method="get" action="view_time_off.php">
        <button type="submit">View Time Off Requests</button>
    </form>
    <br>
</body>
</html>
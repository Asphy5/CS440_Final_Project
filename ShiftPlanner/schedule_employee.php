<?php
    require_once 'config.php';

    require_login();

    if (!check_position('Manager')) {
        header('Location: main.php?msg=unauthorized');
        exit;
    }

    $errors = [];
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrive and sanitize form inputs
        $fname = trim($_POST['fname'] ?? '');
        $lname = trim($_POST['lname'] ?? '');
        $shift_name = trim($_POST['shift_name'] ?? '');
        $shift_date = trim($_POST['shift_date'] ?? '');

        try{
            $pdo = get_pdo();
            $pdo->beginTransaction();

            // Get Employee ID
            $stmt = $pdo->prepare("SELECT Eid FROM Employees WHERE First_Name = :fname AND Last_Name = :lname");
            $stmt->execute([
                'fname' => $fname,
                'lname' => $lname
            ]);
            $eid = $stmt->fetchColumn();

            if (!$eid) {
                $errors[] = 'Employee not found.';
                $success = false;
            }

            // Get Shift ID
            $stmt = $pdo->prepare("SELECT Sid FROM Shifts WHERE Shift_Type = :shift_name");
            $stmt->execute(['shift_name' => $shift_name]);
            $sid = $stmt->fetchColumn();

            if (!$sid) {
                $errors[] = 'Shift not found.';
                $success = false;
            }

            // Check for approved time off requests
            $stmt = $pdo->prepare("
                SELECT count(*) FROM TimeOffRequests 
                WHERE Eid = :eid AND Status = 'Approved' 
                AND :shift_date BETWEEN Start_Date AND End_Date
            ");

            $stmt->execute([
                'eid' => $eid,
                'shift_date' => $shift_date
            ]);

            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Employee has approved time off for this date.';
                $success = false;
            }

            // Insert into Schedule if there are no errors
            if (empty($errors)){
                $stmt = $pdo->prepare("
                    INSERT INTO Schedule(Eid, Sid, Shift_Date) 
                    VALUES (:eid, :sid, :shift_date)
                ");

                $stmt->execute([
                    'eid' => $eid,
                    'sid' => $sid,
                    'shift_date' => $shift_date
                ]);
                $success = true;
                $pdo->commit();
            } 
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
            $success = false;
        }
    }
?>

<!DOCTYPE html>
<head>
<meta charset="utf-8"><title>Schedule Employees</title>
<title>Schedule Employees</title>
</head>
<body>
    <?php if ($errors): ?>
        <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color:green">Employee Successfully Scheduled.</div>
    <?php endif; ?>
    
    <a href="main.php">Return to Menu</a>

    <h2>Schedule Employees</h2>
    <form method="post" action="schedule_employee.php">
        <label>Employee Name: <br>
            <input name="fname" maxlength="20" required>   
            <input name="lname" maxlength="20" required>
        </label> <br> <br>

        <label>Shift Name: <br>
            <input name="shift_name" maxlength="20" required>
        </label> <br> <br>

        <label>Shift Date: <br>
            <input type="date" name="shift_date" required>
        </label> <br> <br>

        <button type="submit">Add Shift</button>
    </form>

</body>
<?php
    require_once 'config.php';

    require_login();

    if (!check_position('Manager')) {
        header('Location: main.php?msg=unauthorized');
        exit;
    }

    $errors = [];
    $pdo = get_pdo();
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrive and sanitize form inputs
        $new_shift_name = trim($_POST['Shift_Name'] ?? '');
        $new_shift_date = trim($_POST['Shift_Date'] ?? '');
        $old_shift_name = trim($_POST['Old_Shift_Name'] ?? '');
        $old_shift_date = trim($_POST['Old_Shift_Date'] ?? '');

        if ($new_shift_name === '' || $new_shift_date === '') {
            $errors[] = 'All fields are required.';
        }

        // Get Employee ID and Shift ID
        $fname = trim($_POST['First_Name'] ?? '');
        $lname = trim($_POST['Last_Name'] ?? '');

        $stmt = $pdo->prepare("SELECT Eid FROM Employees WHERE First_Name = :fname AND Last_Name = :lname");
        $stmt->execute([':fname' => $fname, ':lname' => $lname]);
        $eid = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT Sid FROM Shifts WHERE Shift_Type = :shift_name");
        $stmt->execute([':shift_name' => $old_shift_name]);
        $sid = $stmt->fetchColumn();

        // Attempt to edit the schedule
        if (empty($errors)) {
            try{
                $pdo->beginTransaction();

                if (!$eid || !$sid) {
                    header('Location: view_full_schedule.php?msg=' . urlencode('Invalid shift id.' . $sid . $eid));
                    exit;
                }

                // Check for approved time off requests
                $stmt = $pdo->prepare("
                    SELECT count(*) FROM TimeOffRequests 
                    WHERE Eid = :eid AND Status = 'Approved' 
                    AND :shift_date BETWEEN Start_Date AND End_Date
                ");

                $stmt->execute([
                    'eid' => $eid,
                    'shift_date' => $new_shift_date
                ]);

                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'Employee has approved time off for this date.';
                    $success = false;
                }

                // Get new Shift ID
                $stmt = $pdo->prepare("SELECT Sid FROM Shifts WHERE Shift_Type = :shift_name");
                $stmt->execute(['shift_name' => $new_shift_name]);
                $new_sid = $stmt->fetchColumn();

                // Update schedule
                if (empty($errors)){
                    $stmt = $pdo->prepare("
                        UPDATE Schedule 
                        SET Sid = :sid, Shift_Date = :shift_date
                        WHERE Eid = :eid AND Sid = :old_sid AND Shift_Date = :old_shift_date
                    ");

                    $stmt->execute([
                        'sid' => $new_sid,
                        'shift_date' => $new_shift_date,
                        'eid' => $eid,
                        'old_sid' => $sid
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
    }
?>

<!DOCTYPE html>
<head>
<meta charset="utf-8"><title>Edit Schedule</title>
<title>Edit Schedule</title>
</head>
<body>
    <?php if ($errors): ?>
        <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color:green">Schedule Successfully Edited.</div>
    <?php endif; ?>
    
    <a href="view_full_schedule.php">Return to Menu</a>

    <h2>Edit Schedule</h2>
    <form method="post" action="edit_schedule.php">
        <input type="hidden" name="Old_Shift_Name" value="<?= htmlspecialchars($_POST['Old_Shift_Name'] ?? '') ?>">
        <input type="hidden" name="Old_Shift_Date" value="<?= htmlspecialchars($_POST['Old_Shift_Date'] ?? '') ?>">
        <input type="hidden" name="First_Name" value="<?= htmlspecialchars($_POST['First_Name'] ?? '') ?>">
        <input type="hidden" name="Last_Name" value="<?= htmlspecialchars($_POST['Last_Name'] ?? '') ?>">

        <label>Shift Name: <br>
            <input name="Shift_Name" maxlength="20" required>
        </label> <br> <br>

        <label>Shift Date: <br>
            <input type="date" name="Shift_Date" required>
        </label> <br> <br>

        <button type="submit">Edit Shift</button>
    </form>
</body>
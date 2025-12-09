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
        $sid = intval($_POST['Sid'] ?? 0);
        $shift_name = trim($_POST['Shift_Type'] ?? '');
        $start_time = trim($_POST['Start_Time'] ?? '');
        $end_time = trim($_POST['End_Time'] ?? '');

        if ($sid <= 0 || !is_numeric($sid)) {
            header('Location: edit_shift.php?msg=' . urlencode('Invalid shift id.'));
            exit;
        }

        if ($shift_name === '' || $start_time === '' || $end_time === '') {
            $errors[] = 'All fields are required.';
        }
 
        if (empty($errors)) {
            try{
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("
                    UPDATE Shifts 
                    SET Shift_Type = :shift_name, Start_Time = :start_time, End_Time = :end_time 
                    WHERE Sid = :sid
                ");
                $stmt->execute([
                    'shift_name' => $shift_name,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'sid' => $sid
                ]);
                $pdo->commit();
                $success = true;
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
<meta charset="utf-8"><title>Edit Shift</title>
<title>Edit Shift</title>
</head>
<body>
    <?php if ($errors): ?>
        <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color:green">Shift edited successfully.</div>
    <?php endif; ?>
    
    <a href="edit_shifts.php">Return to Shift View</a>

    <h2>Edit Shift</h2>
    <form method="post" action="process_edit_shift.php">
        <input type="hidden" name="Sid" value="<?= htmlspecialchars($_POST['Sid'] ?? '') ?>">

        <label>Shift Name: <br>
            <input name="Shift_Type" value="<?= htmlspecialchars($_POST['Shift_Type'] ?? '') ?>" maxlength="20" required>
        </label> <br> <br>

        <label>Start Time: <br>
            <input type="time" name="Start_Time" value="<?= htmlspecialchars($_POST['Start_Time'] ?? '') ?>" required>
        </label> <br> <br>

        <label>End Time: <br>
            <input type="time" name="End_Time" value="<?= htmlspecialchars($_POST['End_Time'] ?? '') ?>" required>
        </label> <br> <br>

        <button type="submit">Edit Shift</button>
    </form>

</body>
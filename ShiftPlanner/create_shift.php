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
        $shift_name = trim($_POST['shift_name'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');

        try{
            $pdo->beginTransaction();

            // Check if shift already exists
            $stmt = $pdo->prepare("SELECT count(*) FROM Shifts WHERE Shift_Type = :shift_name");
            $stmt->execute(['shift_name' => $shift_name]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Shift already exists.';
                $success = false;
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO Shifts(Shift_Type, Start_Time, End_Time) 
                    VALUES (:shift_name, :start_time, :end_time)
                ");

                $stmt->execute([
                    'shift_name' => $shift_name,
                    'start_time' => $start_time,
                    'end_time' => $end_time
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
<meta charset="utf-8"><title>Add Shift</title>
<title>Add Shift</title>
</head>
<body>
    <?php if ($errors): ?>
        <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color:green">Shift added successfully.</div>
    <?php endif; ?>
    
    <a href="main.php">Return to Menu</a>

    <h2>Add Shift</h2>
    <form method="post" action="create_shift.php">
        <label>Shift Name: <br>
            <input name="shift_name" maxlength="20" required>
        </label> <br> <br>

        <label>Start Time: <br>
            <input type="time" name="start_time" required>
        </label> <br> <br>

        <label>End Time: <br>
            <input type="time" name="end_time" required>
        </label> <br> <br>

        <button type="submit">Add Shift</button>
    </form>

</body>
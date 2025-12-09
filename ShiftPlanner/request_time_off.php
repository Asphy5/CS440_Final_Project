<?php
    require_once 'config.php';

    require_login();

    $errors = [];
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve and sanitize form inputs
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ??'');
        $reason = trim($_POST['reason'] ?? '');

        // Attempt to submit request
        try {
            $pdo = get_pdo();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO TimeOffRequests (Eid, Start_Date, End_Date, Reason, Status) 
                VALUES (:eid, :start_date, :end_date, :reason, 'Pending')
            ");

            $stmt->execute([
                ':eid' => $_SESSION['user'],
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':reason' => $reason,
            ]);

            $pdo->commit();
            $success = true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Error submitting request: ' . $e->getMessage();
        }
    }
?>

<!DOCTYPE html>
<head>
<meta charset="utf-8"><title>Request Time Off</title>
<title>Request Time Off</title>
</head>
<body>
    <?php if ($errors): ?>
        <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color:green">Request Successfully submitted.</div>
    <?php endif; ?>
    
    <a href="main.php">Return to Menu</a>

    <h2>Request Time Off</h2>
    <form method="post" action="request_time_off.php">
        <label>Start Date: <br>
            <input type="date" name="start_date" required>
        </label> <br> <br>

        <label>End Date: <br>
            <input type="date" name="end_date" required>
        </label> <br> <br>

        <label>Reason: <br>
            <textarea name="reason" maxlength="100" required></textarea>
        </label> <br> <br>

        <button type="submit">Submit Request</button>
    </form>

</body>
<?php
    require_once 'config.php';

    require_login();

    if (!check_position('Manager')) {
        header('Location: main.php?msg=unauthorized');
        exit;
    }

    $pdo = get_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrive and sanitize form inputs
        $shift_name = trim($_POST['Shift_Name'] ?? '');
        $shift_date = trim($_POST['Shift_Date'] ?? '');
        $fname = trim($_POST['First_Name'] ?? '');
        $lname = trim($_POST['Last_Name'] ?? '');

        // Get Employee ID and Shift ID
        $fname = trim($_POST['First_Name'] ?? '');
        $lname = trim($_POST['Last_Name'] ?? '');

        $stmt = $pdo->prepare("SELECT Eid FROM Employees WHERE First_Name = :fname AND Last_Name = :lname");
        $stmt->execute([':fname' => $fname, ':lname' => $lname]);
        $eid = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT Sid FROM Shifts WHERE Shift_Type = :shift_name");
        $stmt->execute([':shift_name' => $shift_name]);
        $sid = $stmt->fetchColumn();

        // Attempt to delete the shift
        try{
            $stmt = $pdo->prepare("
                DELETE FROM Schedule 
                WHERE Eid = :eid AND Sid = :sid AND Shift_Date = :shift_date
            ");
            
            $stmt->execute([
                ':eid' => $eid,
                ':sid' => $sid,
                ':shift_date' => $shift_date
            ]);

            header('Location: view_full_schedule.php?msg=' . urlencode('Shift deleted successfully.'));
            exit;
        } catch (PDOException $e) {
            header('Location: view_full_schedule.php?msg=' . urlencode('Shift deletion failed.'));
            exit;
        }
    }
?>
<?php
    require_once 'config.php';

    require_login();

    if (!check_position('Manager')) {
        header('Location: main.php?msg=unauthorized');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrive and sanitize form inputs
        $sid = intval($_POST['Sid'] ?? 0);

        if ($sid <= 0 || !is_numeric($sid)) {
            header('Location: edit_shifts.php?msg=' . urlencode('Invalid shift id.'));
            exit;
        }

        // Attempt to delete the shift
        try{
            $pdo = get_pdo();
            $stmt = $pdo->prepare("
                DELETE FROM Shifts 
                WHERE Sid = :sid
            ");
            
            $stmt->execute([
                ':sid' => $sid
            ]);

            header('Location: edit_shifts.php?msg=' . urlencode('Shift deleted successfully.'));
            exit;
        } catch (PDOException $e) {
            header('Location: edit_shifts.php?msg=' . urlencode('Shift deletion failed.'));
            exit;
        }
    }


?>
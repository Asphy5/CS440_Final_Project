<?php
    require_once 'config.php';

    require_login();

    if (!check_position('Manager')) {
        header('Location: main.php?msg=unauthorized');
        exit;
    }

    $errors = [];
    $pdo = get_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve and sanitize form inputs
        $rid = intval($_POST['Rid'] ?? 0);

        if ($rid <= 0) {
            $errors[] = 'Invalid request ID.';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM TimeOffRequests WHERE Rid = :rid");
                $stmt->execute(['rid' => $rid]);

                header('Location: manage_time_off.php?msg=Request deleted successfully.');
                exit;
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
?>
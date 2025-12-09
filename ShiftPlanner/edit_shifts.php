<?php
    require_once 'config.php';

    require_login();

    if (!check_position('Manager')) {
        header('Location: main.php?msg=unauthorized');
        exit;
    }

    $errors = [];
    $pdo = get_pdo();
    $msg = $_GET['msg'] ?? '';

    // Page settings
    $limit = 20;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;

    // Get total number of shifts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM view_shifts");
    $stmt->execute();
    $totalShifts = $stmt->fetchColumn();

    // Calculate total pages
    $totalPages = max(1, ceil($totalShifts / $limit));

    // Function to generate page URL
    function page_url($p) {
        return "edit_shift.php?page=$p";
    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Shifts</title>
<body>
    <a href="main.php">Return</a>
    <div class="container">
        <?php if ($errors): ?>
            <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
        <?php endif; ?>
        <?php if ($msg): ?>
            <div style="color:red"><?=htmlspecialchars($msg)?></div>
            <br>
        <?php endif; ?>
        <h2>All Shifts</h2>
        <table border="1">
            <tr>   
                <th>Shift Name</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Action</th>
            </tr>
            <?php
                try {
                    $stmt = $pdo->prepare("SELECT * FROM Shifts LIMIT :limit OFFSET :offset");
                    $stmt->execute([
                        'limit' => $limit, 
                        'offset' => $offset
                    ]);
                    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($shifts as $shift) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($shift['Shift_Type']) . "</td>";
                        echo "<td>" . htmlspecialchars($shift['Start_Time']) . "</td>";
                        echo "<td>" . htmlspecialchars($shift['End_Time']) . "</td>";
                        echo "<td>";
                        echo "<form method='POST' action='process_edit_shift.php'>";
                        echo "<input type='hidden' name='Sid' value='" . htmlspecialchars($shift['Sid']) . "'>";
                        echo "<input type='submit' value='Edit'>";
                        echo '<form method="POST" action="process_delete_shift.php" style="display:inline;" ';
                        echo 'onsubmit="return confirm(\'Are you sure you want to delete this shift?\');">';
                        echo "<input type='hidden' name='Sid' value='" . htmlspecialchars($shift['Sid']) . "'>";
                        echo "<input type='submit' value='Delete'>";
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } catch (PDOException $e) {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            ?>
        </table>
        <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= page_url($page - 1) ?>">&laquo; Prev</a>
                <?php endif; ?>

                <strong>Page <?= $page ?> of <?= $totalPages ?></strong>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= page_url($page + 1) ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
    </div>
</body>
</html>
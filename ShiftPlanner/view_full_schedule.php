<?php
    require_once 'config.php';

    require_login();

    if (!check_position('Manager')) {
        header('Location: main.php?msg=unauthorized');
        exit;
    }

    $msg = $_GET['msg'] ?? '';
    $errors = [];
    $pdo = get_pdo();

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
        return "view_shifts.php?page=$p";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Shifts</title>
<body>
    <a href="view_schedule.php">Return</a>
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
                <th>Employee</th>
                <th>Shift Name</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            <?php
                try {
                    $stmt = $pdo->prepare("SELECT * FROM view_all_shifts LIMIT :limit OFFSET :offset");
                    $stmt->execute([
                        'limit' => $limit, 
                        'offset' => $offset
                    ]);
                    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($shifts as $shift) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($shift['First_Name']) . " " . htmlspecialchars($shift['Last_Name']) . "</td>";
                        echo "<td>" . htmlspecialchars($shift['Shift_Type']) . "</td>";
                        echo "<td>" . htmlspecialchars($shift['Start_Time']) . "</td>";
                        echo "<td>" . htmlspecialchars($shift['End_Time']) . "</td>";
                        echo "<td>" . htmlspecialchars($shift['Shift_Date']) . "</td>";
                        echo "<td>";
                        echo '<form method="POST" action="edit_schedule.php" style="display:inline;">';
                        echo '<input type="hidden" name="First_Name" value="' . htmlspecialchars($shift['First_Name']) . '">';
                        echo '<input type="hidden" name="Last_Name" value="' . htmlspecialchars($shift['Last_Name']) . '">';
                        echo '<input type="hidden" name="Old_Shift_Date" value="' . htmlspecialchars($shift['Shift_Date']) . '">';
                        echo '<input type="hidden" name="Old_Shift_Name" value="' . htmlspecialchars($shift['Shift_Type']) . '">';
                        echo '<button type="submit">Edit</button>';
                        echo '</form>';
                        echo '<form method="POST" action="process_delete_schedule.php" style="display:inline;" ';
                        echo 'onsubmit="return confirm(\'Are you sure you want to delete this shift?\');">';
                        echo '<input type="hidden" name="First_Name" value="' . htmlspecialchars($shift['First_Name']) . '">';
                        echo '<input type="hidden" name="Last_Name" value="' . htmlspecialchars($shift['Last_Name']) . '">';
                        echo '<input type="hidden" name="Shift_Date" value="' . htmlspecialchars($shift['Shift_Date']) . '">';
                        echo '<input type="hidden" name="Shift_Name" value="' . htmlspecialchars($shift['Shift_Type']) . '">';
                        echo '<button type="submit">Delete</button>';
                        echo '</form>';

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
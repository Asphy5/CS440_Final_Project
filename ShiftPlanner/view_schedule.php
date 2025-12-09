<?php
    require_once 'config.php';

    require_login();

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

    // Retrieve total scheduled hours for the logged-in user
    $hours = 0;
    try {
        $stmt = $pdo->prepare("
            SELECT Hours FROM Employees 
            WHERE Eid = :eid
        ");
        $stmt->execute([':eid' => $_SESSION['user']]);
        $hours = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Shifts</title>
<body>
    <a href="main.php">Return</a>
    <?php if (check_position('Manager')): ?>
        <a href="view_full_schedule.php">View All Scheduled Shifts</a>
    <?php endif; ?>
    <div class="container">
        <?php if ($errors): ?>
            <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
        <?php endif; ?>
        <h2>
            Shifts For: <?= htmlspecialchars($_SESSION['fname']) ?> <?= htmlspecialchars($_SESSION['lname']) ?>
             | Scheduled For: <?= htmlspecialchars($hours['Hours']) ?> Hours
        </h2>
        <table border="1">
            <tr>
                <th>Shift Name</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Date</th>
            </tr>
            <?php
                try {
                    $stmt = $pdo->prepare("SELECT * FROM view_shifts LIMIT :limit OFFSET :offset");
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
                        echo "<td>" . htmlspecialchars($shift['Shift_Date']) . "</td>";
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
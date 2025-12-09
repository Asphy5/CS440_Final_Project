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
        return "view_time_off.php?page=$p";
    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Time Off Requests</title>
<body>
    <a href="main.php">Return</a>
    <div class="container">
        <?php if ($errors): ?>
            <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
        <?php endif; ?>
        <h2>Requests For: <?= htmlspecialchars($_SESSION['fname']) ?> <?= htmlspecialchars($_SESSION['lname']) ?></h2>
        <table border="1">
            <tr>
                <th>Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Reason</th>
                <th>Status</th>
            </tr>
            <?php
                try {
                    $stmt = $pdo->prepare("SELECT * FROM view_time_off LIMIT :limit OFFSET :offset");
                    $stmt->execute([
                        'limit' => $limit, 
                        'offset' => $offset
                    ]);
                    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($requests as $request) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($request['First_Name']) . " " . htmlspecialchars($request['Last_Name']) . "</td>";
                        echo "<td>" . htmlspecialchars($request['Start_Date']) . "</td>";
                        echo "<td>" . htmlspecialchars($request['End_Date']) . "</td>";
                        echo "<td>" . htmlspecialchars($request['Reason']) . "</td>";
                        echo "<td>" . htmlspecialchars($request['Status']) . "</td>";
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
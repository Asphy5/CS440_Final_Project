<?php
    require_once 'config.php';

    require_login();

    if (!check_position('Manager')) {
        header('Location: main.php?msg=unauthorized');
        exit;
    }

    $errors = [];
    $msg = $_GET['msg'] ?? '';
    $pdo = get_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve and sanitize form inputs
        $status = trim($_POST['Status'] ?? '');
        $rid = intval($_POST['Rid'] ?? 0);
        if ($status != 'Approved' && $status != 'Denied' && $status != 'Pending') {
            $errors[] = 'Something went wrong.';
        }

        // Update time off request status
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    UPDATE TimeOffRequests 
                    SET Status = :status 
                    WHERE Rid = :rid
                ");
                $stmt->execute([
                    'status' => $status,
                    'rid' => $rid
                ]);

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }

    // Page settings
    $limit = 20;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;

    // Get total number of shifts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM manage_time_off");
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
        <?php if ($msg): ?>
            <div style="color:green"><?=htmlspecialchars($msg)?></div>
            <br>
        <?php endif; ?>
        <h2>Manage Time Off Requests</h2>
        <table border="1">
            <tr>
                <th>Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php
                try {
                    $stmt = $pdo->prepare("SELECT * FROM manage_time_off LIMIT :limit OFFSET :offset");
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
                        echo "<td>";
                        echo "<form action='manage_time_off.php' method='post' style='display:inline;' onchange='this.submit()'>";
                        echo "<input type='hidden' name='Rid' value='" . htmlspecialchars($request['Rid']) . "'>";
                        echo "<select name='Status'>";
                        if ($request['Status'] === 'Pending') {
                            echo "<option value=''>" . htmlspecialchars($request['Status']) . "</option>";
                        }
                        echo "<option value='Approved'" . ($request['Status'] == 'Approved' ? ' selected' : '') . ">Approved</option>";
                        echo "<option value='Denied'" . ($request['Status'] == 'Denied' ? ' selected' : '') . ">Denied</option>";
                        echo "</select>";
                        echo "</form>";
                        echo "</td>";
                        echo "<td>";
                        echo "<form action='delete_time_off.php' method='post' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this request?\");'>";
                        echo "<input type='hidden' name='Rid' value='" . htmlspecialchars($request['Rid']) . "'>";
                        echo "<button type='submit' style='background:none;border:none;color:#c00;cursor:pointer;padding:0;'>";
                        echo "Delete";
                        echo "</button>";
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
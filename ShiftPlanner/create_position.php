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
        $pname = trim($_POST['pname'] ?? '');

        try{
            $pdo->beginTransaction();

            // Check if position already exists
            $stmt = $pdo->prepare("SELECT count(*) FROM Positions WHERE Pname = :pname");
            $stmt->execute(['pname' => $pname]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Position already exists.';
                $success = false;
            } else {
                $stmt = $pdo->prepare('INSERT INTO Positions(Pname) VALUES (:pname)');
                $stmt->execute(['pname' => $pname]);
                $pdo->commit();
                $success = true;
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
<meta charset="utf-8"><title>Add Position</title>
<title>Add Position</title>
</head>
<body>
    <?php if ($errors): ?>
        <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color:green">Position added successfully.</div>
    <?php endif; ?>
    
    <a href="main.php">Return to Menu</a>

    <h2>Add Position</h2>
    <form method="post" action="create_position.php">
        <label>Position Name: <br>
            <input name="pname" maxlength="20" required>
        </label> <br> <br>

        <button type="submit">Add Position</button>
    </form>

</body>
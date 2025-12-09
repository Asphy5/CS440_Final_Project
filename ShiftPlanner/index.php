<?php
    require_once 'config.php';

    $msg = $_GET['msg'] ?? '';
    $errors = [];
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eid = trim($_POST['eid'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        // Validate Employee ID
        if (empty($eid) || strlen($eid) != 8) {
            $errors[] = 'Invalid Credentials';
        }
    
        if (empty($errors)){
            $success = login($eid, $password);
            if (!$success) {
                $errors[] = 'Invalid Credentials';
            }
            else{
                header('Location: main.php');
                exit;
            }
        }
    }
?>

<!DOCTYPE html>
<head>
<meta charset="utf-8"><title>Main Page</title>
<title>Main Page</title>
</head>
<body>
    <?php if ($msg): ?>
        <div style="color:green"><?=htmlspecialchars($msg)?></div>
        <br>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
        <br>
    <?php endif; ?>
    <form method="post" action="">
        <label>Employee ID: <br>
        <input name="eid" maxlength="8">
        </label> <br> <br>

        <label>Password: <br>
        <input type="password" name="password">
        </label> <br> <br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
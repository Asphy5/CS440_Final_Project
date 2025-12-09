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
        // Retrieve and sanitize form inputs
        $fname = trim($_POST['fname'] ?? '');
        $lname = trim($_POST['lname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $pay = floatval($_POST['pay'] ?? '');
        $pname = trim($_POST['pname'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate inputs
        if (empty($fname) || empty($lname) || empty($phone) || empty($pay) || empty($pname) || empty($password)) {
            $errors[] = 'All fields are required.';
        } else if (empty($confirm_password) || $password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        } else if ($pay <= 0) {
            $errors[] = 'Hourly rate must be a positive number.';
        }

        // Ensure position exists
        $stmt = $pdo->prepare("SELECT Pid FROM Positions WHERE Pname = :pname");
        $stmt->execute(['pname' => $pname]);
        $pid = $stmt->fetchColumn();
        if (!$pid) {
            $errors[] = 'Position does not exist.';
        }

        if (empty($errors)){
            // Attempt to add the new employee to the database
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT INTO Employees (First_Name, Last_Name, Phone, Pay, Hours, Pid) 
                    VALUES (:fname, :lname, :phone, :pay, 0, :pid)
                ");
                $stmt->execute([
                    ':fname' => $fname,
                    ':lname' => $lname,
                    ':phone' => $phone,
                    ':pay' => $pay,
                    ':pid' => $pid,
                ]);

                $pdo->commit();

                // Attempt to create a database user for the new employee
                $stmt = $pdo->prepare("SELECT Eid FROM Employees WHERE First_Name = :fname AND Last_Name = :lname ORDER BY Eid DESC LIMIT 1");
                $stmt->execute([':fname' => $fname, ':lname' => $lname]);
                $eid = $stmt->fetchColumn();

                $stmt = $pdo->prepare("CREATE USER '" . $eid . "'@'localhost' IDENTIFIED BY '" . $password . "';");
                $stmt->execute();

                

                // Grant necessary privileges to the new user
                if ($pname === 'Manager') {
                    // Grant Manager privileges
                    $stmt = $pdo->prepare("
                        GRANT SELECT, INSERT, UPDATE, DELETE ON ShiftPlanner.* TO " . $eid . "@'localhost' WITH GRANT OPTION;
                    ");
                    $stmt->execute();

                    $stmt = $pdo->prepare("GRANT CREATE USER ON *.* TO '" . $eid . "'@'localhost' WITH GRANT OPTION;");
                    $stmt->execute();

                    $stmt = $pdo->prepare("GRANT RELOAD ON *.* TO '" . $eid . "'@'localhost' WITH GRANT OPTION;");
                    $stmt->execute();

                    $pdo->exec("FLUSH PRIVILEGES;");
                }
                else{
                    // Grant Employee privileges
                    $stmt = $pdo->prepare("
                        GRANT SELECT ON ShiftPlanner.Schedule TO " . $eid . "@'localhost';
                    ");
                    $stmt->execute();

                    $stmt = $pdo->prepare("
                        GRANT SELECT ON ShiftPlanner.view_shifts TO " . $eid . "@'localhost';
                    ");
                    $stmt->execute();

                    $stmt = $pdo->prepare("
                        GRANT SELECT ON ShiftPlanner.Employees TO " . $eid . "@'localhost';
                    ");
                    $stmt->execute();

                    $stmt = $pdo->prepare("
                        GRANT SELECT ON ShiftPlanner.Positions TO " . $eid . "@'localhost';
                    ");
                    $stmt->execute();

                    $stmt = $pdo->prepare("
                        GRANT SELECT, INSERT ON ShiftPlanner.TimeOffRequests TO " . $eid . "@'localhost';
                    ");
                    $stmt->execute();

                    $stmt = $pdo->prepare("
                        GRANT SELECT ON ShiftPlanner.view_time_off TO " . $eid . "@'localhost';
                    ");
                    $stmt->execute();

                    $pdo->exec("FLUSH PRIVILEGES;");
                }
                $success = true;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()){
                    $pdo->rollBack();
                }
                $errors[] = 'Error adding employee: ' . $e->getMessage();
                $success = false;
            }
        }
    }
?>

<!DOCTYPE html>
<head>
<meta charset="utf-8"><title>Hire Employee</title>
<title>Hire Employee</title>
</head>
<body>
    <?php if ($errors): ?>
        <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="color:green">Employee added successfully with ID <?= htmlspecialchars($eid) ?>.</div>
    <?php endif; ?>

    <a href="main.php">Return to Menu</a>

    <h2>Add Employee</h2>
    <form method="post" action="create_employee.php">
        <label>First Name:<br>
            <input name="fname" maxlength="20" required>
        </label> <br> <br>
        
        <label>Last Name:<br>
            <input name="lname" maxlength="20" required>
        </label> <br> <br>

        <label>Phone Number:<br>
            <input name="phone" maxlength="20" required>
        </label> <br> <br>
        
        <label>Hourly Rate:<br>
            <input name="pay" required>
        </label> <br> <br>

        <label>Position:<br>
            <input name="pname" maxlength="20" required>
        </label> <br> <br>

        <label>Password:<br>
            <input type="password" name="password" required>
        </label> <br> <br>

        <label>Confirm Password:<br>
            <input type="password" name="confirm_password" required>
        </label> <br> <br>

        <button type="submit">Add Employee</button>
    </form>

</body>
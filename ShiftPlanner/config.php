<?php
    session_start();
    ini_set('session.cookie_httponly', value: 1);
    ini_set('session.use_strict_mode', 1);

    ini_set('log_errors', 'On');
    ini_set('display_errors', 'On');
    ini_set('error_reporting', E_ALL);

    function login($eid, $password) {
        // Attempt to connect to the database
        $dbhost = '127.0.0.1';
        $dbname = 'ShiftPlanner';
        $dbuser = $eid;
        $dbpass = $password;

        // Create a database object
        $dsn = "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try{
            // Try to create the PDO object
            $pdo = new PDO($dsn, $dbuser, $dbpass, $options);

            // Successful login, set session variables
            $stmt = $pdo->prepare("SELECT First_Name, Last_Name FROM Employees WHERE Eid = :eid");
            $stmt->execute(['eid' => $eid]);
            $user = $stmt->fetch();

            session_regenerate_id(true);
            $_SESSION['user'] = $eid;
            $_SESSION['password'] = $password;
            $_SESSION['fname'] = $user['First_Name'] ?? '';
            $_SESSION['lname'] = $user['Last_Name'] ?? '';

            // Check the user's position from the database
            $stmt = $pdo->prepare("SELECT Pname FROM Positions WHERE Pid = (SELECT Pid FROM Employees WHERE Eid = :eid)");
            $stmt->execute(['eid' => $_SESSION['user']]);
            $position = $stmt->fetchColumn();
            $_SESSION['position'] = $position ?: '';

            return true;
        } catch(PDOException $e){
            return false;
        }
    }

    function get_pdo(){
        $dbhost = '127.0.0.1';
        $dbname = 'ShiftPlanner';
        $dbuser = $_SESSION['user'] ?? '';
        $dbpass = $_SESSION['password'] ?? '';

        $dsn = "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4";
        try{
            return new PDO($dsn, $dbuser, $dbpass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            header('Location: index.php?=failed_to_connect');
            exit;
        }
    }

    // Send user to login if not logged in
    function require_login() {
    if (empty($_SESSION['user'])) {
        header('Location: /index.php');
        exit;
    }
    }

    function check_position($position) {
        if (!isset($_SESSION['position']) || $_SESSION['position'] !== $position) {
            return false;
        }

        return true;
    }

    function destroy_session() {
        // Unset all of the session variables
        session_start();
        $_SESSION = [];

        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();
    }
?>
<?php
    require_once 'config.php';

    require_login();
    
    destroy_session();
    header('Location: index.php?msg=Logged out successfully.');
    exit;
?>
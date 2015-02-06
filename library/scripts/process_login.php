<?php
    require_once("includes/config.php");

    $cpm_session = new sec_session($secure);
    $cpm_session->sec_session_start();

    if (isset($_POST['email'], $_POST['p'])) {
        $email = rawurlencode($_POST['email']);
        $password = $_POST['p']; // The hashed password.
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : false;

        if ($cpm_session->sec_login($email, $password) == true) {
            // Login success 
            header('Location: ' .($redirect ? $redirect : '../index.php'));
        } else {
            // Login failed 
            header('Location: ./scripts/error.php');
        }
    } else {
        // The correct POST variables were not sent to this page. 
        echo 'Invalid Request';
    }

?>

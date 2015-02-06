<?php
    require_once("includes/config.php");

    $cpm_session = new sec_session($secure);
    $cpm_session->sec_session_start();
    $cpm_session->sec_logout('../index.php');
?>

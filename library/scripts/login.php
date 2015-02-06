<?php
    require_once("includes/config.php");

    $cpm_session = new sec_session($secure);
    $cpm_session->sec_session_start();
    $logged_in = $cpm_session->sec_login_check();
    if ($logged_in == true) {
        $logged = 'in';
    } else {
        $logged = 'out';
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Custom WordPress Plugin Manager: Log In</title>
        <link rel="stylesheet" href="css/auth.css" />
        <script type="text/JavaScript" src="js/jquery.js"></script> 
        <script type="text/JavaScript" src="js/modernizr-2.7.1.js"></script> 
        <script type="text/JavaScript" src="js/placeholder.js"></script> 
        <script type="text/JavaScript" src="js/sha512.js"></script> 
        <script type="text/JavaScript" src="js/forms.js"></script> 
    </head>
    <body>
        <h1>Custom WordPress Plugin Manager</h1>
        <form action="process_login.php" method="post" name="login_form" id="slick-login">
            <label for="email">email</label><input type="text" name="email" class="placeholder" placeholder="email address" autocomplete="off">
            <label for="password">password</label><input type="password" name="password" class="placeholder" id="password" placeholder="password" autocomplete="off">
            <input type="button" value="Log In" onclick="formhash(this.form, this.form.password);" />
            <div id="info">
                <?php if (isset($_GET['error'])) : ?>
                    <p class="error">Error Logging In!</p>
                    <?php endif; ?>
                <p class="status">You are currently logged <?php echo $logged ?>.</p>
                <?php if ($logged_in) : ?>
                    <p>If you are done, please <a href="logout.php">log out</a>.</p>
                    <?php elseif ($allow_registration) : ?>
                    <p>If you don't have a login, please <a href="register.php">register</a></p>
                    <?php endif; ?>
            </div>
        </form>
    </body>
</html>

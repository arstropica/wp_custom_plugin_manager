<?php
    require_once("includes/config.php");
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Custom WordPress Plugin Manager: Registration successful!</title>
        <link rel="stylesheet" href="css/auth.css" />
    </head>
    <body>
        <h1>Registration successful!</h1>
        <form id="slick-login" name="registration_form" class="register">
            <div id="info">
                <p>You can now go back to the <a href="<?php echo $rel_path_to_app; ?>">login page</a> and log in.</p>
            </div>
        </form>
    </body>
</html>

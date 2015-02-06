<?php
    require_once("includes/config.php");
    $mode = empty($_REQUEST['mode']) ? "create" : $_REQUEST['mode'];
    $cpm_session = new sec_session($secure);
    $cpm_session->sec_session_start();
    if ($cpm_session->sec_login_check()) {
        $user_account_info = $cpm_session->sec_login_info();
        if ($user_account_info) {
            $username = $user_account_info['username'];
            $email = $user_account_info['email'];
        }
    }
    if ((! $allow_registration) && ($mode == "create") && ($username != "admin")) {
        header('Location: ../index.php');
        exit;
    }
    $messages = array();
    $error = false;
    $registration_success = false;
    require_once("includes/register.inc.php");
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Custom WordPress Plugin Manager: Create / Edit Account</title>
        <link rel="stylesheet" href="css/auth.css" />
        <link rel="stylesheet" href="css/msgBar.css" />
        <script type="text/JavaScript" src="js/jquery.js"></script> 
        <script type="text/JavaScript" src="js/modernizr-2.7.1.js"></script> 
        <script type="text/JavaScript" src="js/placeholder.js"></script> 
        <script type="text/JavaScript" src="js/msgBar.js"></script> 
        <script type="text/JavaScript" src="js/sha512.js"></script> 
        <script type="text/JavaScript" src="js/forms.js"></script>
        <script type="text/JavaScript">
            $(function(){
                <?php
                    if (isset($messages['error'])) {
                        foreach ($messages['error'] as $_error) {
                            echo "\$('#notifications').bar_message('error', '" . $_error . "');\n";
                        }
                    }
                    if (isset($messages['updated'])) {
                        foreach ($messages['updated'] as $_updated) {
                            echo "\$('#notifications').bar_message('success', '" . $_updated . "');\n";
                        }
                    }
                    if ($registration_success) {
                        echo "setTimeout(function(){window.location.href='" . $rel_path_to_app . "scripts/register_success.php';}, 3000);\n";
                    }
                ?>
            });
        </script> 
    </head>
    <body>
        <!-- Registration form to be output if the POST variables are not
        set or if the registration script caused an error. -->
        <div id="notifications"></div>
        <h1>Custom WordPress Plugin Manager: Create / Edit Account</h1>
        <form id="slick-login" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" name="registration_form" class="register">
            <table id="slick_table">
                <tr>
                    <td class="label required">Username : </td>
                    <td class="input<?php if (isset($error['username'])) echo " error"; ?>">
                        <?php if (isset($error['username'])) echo "<span class=\"error\">" . $error['username'] ."</span>\n"; ?>
                        <label for="username">Username</label><input type="text" name="username" class="placeholder" placeholder="enter username" value="<?php echo $username; ?>" <?php if ($mode == "update") echo "readonly=\"readonly\""; ?> autocomplete="off" />
                    </td>
                </tr>
                <tr>
                    <td class="label required">Email : </td>
                    <td class="input<?php if (isset($error['email'])) echo " error"; ?>">
                        <?php if (isset($error['email'])) echo "<span class=\"error\">" . $error['email'] ."</span>\n"; ?>
                        <label for="email">Email</label><input type="text" name="email" class="placeholder" placeholder="enter email address" value="<?php echo $email; ?>" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td class="label required">Password : </td>
                    <td class="input<?php if (isset($error['password'])) echo " error"; ?>">
                        <?php if (isset($error['password'])) echo "<span class=\"error\">" . $error['password'] ."</span>\n"; ?>
                        <label for="password">Password</label><input type="password" name="password" class="placeholder" placeholder="enter password" value="" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td class="label required">Confirm Password : </td>
                    <td class="input<?php if (isset($error['confirmpwd'])) echo " error"; ?>">
                        <?php if (isset($error['confirmpwd'])) echo "<span class=\"error\">" . $error['confirmpwd'] ."</span>\n"; ?>
                        <label for="confirmpwd">Confirm Password</label><input type="password" name="confirmpwd" class="placeholder" placeholder="renter password" value="" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <input type="button" value="Save" onclick="return regformhash(this.form, this.form.username, this.form.email, this.form.password, this.form.confirmpwd);" />
                    </td>
                </tr>
            </table>
            <div id="info">
                <ul>
                    <li>Usernames may contain only digits, upper and lower case letters and underscores</li>
                    <li>Emails must have a valid email format</li>
                    <li>Passwords must be at least 6 characters long</li>
                    <li>Passwords must contain
                        <ul>
                            <li>At least one upper case letter (A..Z)</li>
                            <li>At least one lower case letter (a..z)</li>
                            <li>At least one number (0..9)</li>
                        </ul>
                    </li>
                    <li>Your password and confirmation must match exactly</li>
                </ul>
                <?php if (isset($messages['save'])) : ?>
                    <p>If you are done, please go to the <a href="../index.php">Plugin Manager</a>.</p>
                    <?php endif; ?>
            </div>
        </form>
    </body>
</html>

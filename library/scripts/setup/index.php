<?php
    require_once( dirname(__FILE__) . "/../includes/config.php");

    $cpm_session = new sec_session($secure);
    $cpm_session->sec_session_start();

    if($cpm_session->sec_login_check() != true) {
        $logged_in = false;
        header('Location: ../login.php', true, 302);
        exit;
        // exit('You are not authorized to access this page, please login.');
    } else {
        $logged_in = true;
    }
    // $logged_in = $cpm_session->sec_login_check();
    
    if ($logged_in == true) {
        $logged = 'in';
    } else {
        $logged = 'out';
    }
    $svn_user = "";
    $svn_pass = "";
    $svn_path_to_plugins = "";
    $svn_url = "";
    $remote_auth = 0;
    $global_author = "";
    $error = false;
    $messages = false;

    if ($_POST) {
        if (empty($_POST['svn_user']) === false) $svn_user = $_POST['svn_user'];
        if (empty($_POST['svn_pass']) === false) $svn_pass = $_POST['svn_pass'];
        if (empty($_POST['svn_url']) === false) {
            $svn_url = $_POST['svn_url'];
        } else {
            $error['svn_url'] = "This field is required.";
        }
        if (empty($_POST['svn_path_to_plugins']) === false) {
            $svn_path_to_plugins = $_POST['svn_path_to_plugins'];
        } else {
            $error['svn_path_to_plugins'] = "This field is required.";
        }
        if (isset($_POST['remote_auth'])) {
            $remote_auth = $_POST['remote_auth'];
        } else {
            $error['remote_auth'] = "This field is required.";
        }
        if (empty($_POST['global_author']) === false) $global_author = $_POST['global_author'];
        if (! $error) {
            $wp_repo = new wp_custom_repo(false, false, array('svn_url' => $svn_url, 'svn_path_to_plugins' => $svn_path_to_plugins, 'svn_user' => $svn_user, 'svn_pass' => $svn_pass));
            if ($wp_repo->valid) {
                $save_options = $connection->set_svn_options($svn_url, $svn_path_to_plugins,$svn_user, $svn_pass, $global_author, $remote_auth);
                if (! $save_options) {
                    $error['save'] = "Oops. Your options were not saved.";
                } else {
                    $messages['save'] = "Your options have been saved.";
                }
            } else {
                $error['save'] = "Oops. Your repository url does not appear to be accessible. Your options were not saved.";
            }
        } else {
            $error['save'] = "Oops. Some required options are missing. Your options were not saved.";
        }
    } else {
        $svn_options = $connection->get_svn_options();
        if ($svn_options) {
            extract($svn_options);
        }
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Custom WordPress Plugin Manager: Configuration</title>
        <link rel="stylesheet" href="../scripts/css/auth.css" />
        <link rel="stylesheet" href="../scripts/css/msgBar.css" />
        <script type="text/JavaScript" src="../scripts/js/jquery.js"></script> 
        <script type="text/JavaScript" src="../scripts/js/modernizr-2.7.1.js"></script> 
        <script type="text/JavaScript" src="../scripts/js/placeholder.js"></script> 
        <script type="text/JavaScript" src="../scripts/js/msgBar.js"></script> 
        <script type="text/JavaScript" src="../scripts/js/forms.js"></script> 
        <script type="text/JavaScript">
            $(function(){
                <?php
                    if (isset($error['save'])) {
                        echo "\$('#notifications').bar_message('error', '" . $error['save'] . "');\n";
                    }
                    if (isset($messages['save'])) {
                        echo "\$('#notifications').bar_message('success', '" . $messages['save'] . "');\n";
                    }
                ?>
            });
        </script> 
    </head>
    <body>
        <div id="notifications"></div>
        <h1>Custom WordPress Plugin Manager: Configuration</h1>
        <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" name="login_form" id="slick-login" class="config">
            <table id="slick_table">
                <tr>
                    <td class="label required">Repository Base Url *: </td>
                    <td class="input<?php if (isset($error['svn_url'])) echo " error"; ?>">
                        <?php if (isset($error['svn_url'])) echo "<span class=\"error\">" . $error['svn_url'] ."</span>\n"; ?>
                        <label for="svn_url">Repository Base Url</label><input type="text" name="svn_url" class="placeholder" placeholder="http://your.repo.com" value="<?php echo $svn_url; ?>" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td class="label required">Path to plugins folder *: </td>
                    <td class="input<?php if (isset($error['svn_path_to_plugins'])) echo " error"; ?>">
                        <?php if (isset($error['svn_path_to_plugins'])) echo "<span class=\"error\">" . $error['svn_path_to_plugins'] ."</span>\n"; ?>
                        <label for="svn_path_to_plugins">Path to plugins folder</label><input type="text" name="svn_path_to_plugins" class="placeholder" id="svn_path_to_plugins" placeholder="e.g. updates/plugins" value="<?php echo $svn_path_to_plugins; ?>" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td class="label required">Remote authorization *: </td>
                    <td class="input<?php if (isset($error['remote_auth'])) echo " error"; ?>">
                        <?php if (isset($error['remote_auth'])) echo "<span class=\"error\">" . $error['remote_auth'] ."</span>\n"; ?>
                        <label for="remote_auth">Require remote authorization</label><select name="remote_auth" id="remote_auth"><option value="0"<?php if ($remote_auth == 0) echo " selected=\"selected\""; ?>>No</option><option value="1"<?php if ($remote_auth == 1) echo " selected=\"selected\""; ?>>Yes</option></select>
                    </td>
                </tr>
                <tr>
                    <td class="label">Default Plugin Authorship: </td>
                    <td class="input">
                        <label for="global_author">Default Plugin Authorship</label><input type="text" name="global_author" class="placeholder" id="global_author" placeholder="e.g. Joe the Developer" value="<?php echo $global_author; ?>" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td class="label">Subversion Username: </td>
                    <td class="input">
                        <label for="svn_user">Subversion Username</label><input type="text" name="svn_user" class="placeholder" id="svn_user" placeholder="Subversion Username" value="<?php echo $svn_user; ?>" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td class="label">Subversion Password: </td>
                    <td class="input">
                        <label for="svn_pass">Subversion Password</label><input type="password" name="svn_pass" class="placeholder" id="svn_pass" placeholder="Subversion Password" value="<?php echo $svn_pass; ?>" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <input type="button" value="Save" onclick="this.form.submit();" />
                    </td>
                </tr>
            </table>
            <div id="info">
                <?php if (isset($messages['save'])) : ?>
                    <p>If you are done, please go to the <a href="../index.php">Plugin Manager</a>.</p>
                    <?php endif; ?>
            </div>
        </form>
    </body>
</html>

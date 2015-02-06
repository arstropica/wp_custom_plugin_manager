<?php
    $debug= empty($_GET['debug']) ? false : $_GET['debug'];
    require_once("includes/config.php");

    $cpm_session = new sec_session($secure);
    $cpm_session->sec_session_start();

    if($cpm_session->sec_login_check() != true) {
        header('Location: ./login.php', true, 302);
        exit;
        // exit('You are not authorized to access this page, please login.');
    }
    $user_account_info = $cpm_session->sec_login_info();
    $username = $user_account_info['username'];
    $wp_repo = new wp_custom_repo(false, false);
    ob_start();
    if ($debug) {
        $plugins = $wp_repo->get_plugins();
    }
    $messages = array();
    if ($_POST && isset($_POST['availability'])) {
        $plugin_availability = $_POST['availability'];
        foreach ($plugin_availability as $_pluginslug => $_plugin_versions) {
            foreach ($_plugin_versions as $_plugin_version => $_availability) {
                $is_active = isset($_availability['active']) ? 1 : 0;
                $connection->set_plugin_info_in_db($_pluginslug, $_plugin_version, $is_active);
            }
        }
        $messages['updated'][] = "Plugin Settings have been updated!";
    }
    $table_content = "<tr><td class='noplugins' colspan=\"5\" style=\"text-align: center\">No Plugins found.</td></tr>\n";
    $filter = "<input class='tablefilter' type='text' value='' style='width: 175px; min-width: 55px;' />";
    $errors = ob_get_clean();
    if ($errors) {
        foreach (array_filter(explode("\n", $errors)) as $_error) {
            $messages['error'][] = $_error;
        }
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Cache-Control" content="no-cache">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta http-equiv="Lang" content="en">
        <title>Custom WordPress Plugin Manager</title>
        <link rel='stylesheet' type='text/css' href='scripts/js/tablesorter/themes/blue/style.css' />
        <link rel='stylesheet' type='text/css' href='scripts/css/manager.css' />
        <link rel="stylesheet" type='text/css' href="scripts/css/msgBar.css" />
        <script type='text/javascript' src='scripts/js/jquery.js'></script>
        <script type='text/javascript' src='scripts/js/moment.min.js'></script>
        <script type='text/javascript' src='scripts/js/tablesorter/jquery.tablesorter.min.js'></script>
        <script type="text/JavaScript" src="scripts/js/msgBar.js"></script> 
        <script type='text/javascript' src='scripts/js/manager.js'></script>
        <script type="text/JavaScript">
            (function($) {
                $(function(){
                    <?php
                        if (isset($messages['error'])) {
                            foreach($messages['error'] as $_error) {
                                echo "\$('#notices').bar_message('error', '" . $_error . "');\n";
                            }
                        }
                        if (isset($messages['updated'])) {
                            foreach($messages['updated'] as $_update) {
                                echo "\$('#notices').bar_message('success', '" . $_update . "');\n";
                            }
                        }
                    ?>
                });
            })(jQuery);
        </script> 
    </head>
    <body>
        <div id="notices"></div>
        <div class="wrap">
            <form id="plugin_manager" name="plugin_manager" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
                <h1>Custom WordPress Plugin Manager</h1>
                <div id="main">
                    <div id="content">
                        <div class="ajax_loading">Please wait...</div>
                        <?php if ($debug) : ?>
                            <textarea cols="10" rows="25" style="width: 100%; height: 80%;">
                                <?php var_dump($plugins); ?>
                            </textarea>
                            <?php endif; ?>
                        <table id="plugin_status_table" class="tablesorter">
                            <thead>
                                <tr><th>Plugin Name</th><th>Version</th><th>Last Modified</th><th>Has Info file (readme.txt)</th><th>Update Availability</th></tr>
                                <tr class='filterrow'><td><?php echo $filter; ?></td><td>&nbsp;</td><td><?php echo $filter; ?></td><td><?php echo $filter; ?></td><td><input type='checkbox' class='toggle_availability' /></td></tr>
                            </thead>
                            <tbody>
                                <?php echo $table_content; ?>
                            </tbody>
                        </table>
                        <div style="clear:both; width: 100%;"></div>

                    </div>
                </div>
                <div id="sidebar">
                    <ul id="plugin_manager_options">
                        <li class="username">Hi, <strong><?php echo $username; ?></strong></li>
                        <li><a href="register.php?mode=update" title="Edit Account">Edit Account</a></li>
                        <?php if ($username == "admin") : ?>
                            <li><a href="setup/index.php" title="Settings">Change Settings</a></li>
                            <?php endif; ?>
                        <li><a href="logout.php" title="Settings">Logout</a></li>
                        <li class="form_actions">
                            <button type="submit" class="submitButton">Save</button>
                            <button type="button" class="reloadButton" onclick="location.reload(true)">Reload</button>
                            <div style="clear:both; width: 100%;"></div>
                        </li>
                    </ul>
                </div>
            </form>
        </div>  
    </body>
</html>

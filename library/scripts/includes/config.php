<?php
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Config
    // Database Settings
    define("DATABASE_HOST", "localhost");     // The host you want to connect to.
    define("DATABASE_USER", "svn_admin");    // The database username. 
    define("DATABASE_PASS", "UF6d1Wn04V");    // The database password. 
    define("DATABASE_NAME", "wordpress_svn_manager");    // The database name.

    // Login Settings
    $secure = false; // DEVELOPMENT ONLY
    $allow_registration = false;

    /*
    *** Do not modify below this line ***
    */
    function cpm_error_handler ($errno, $errstr, $errfile, $errline) {
        switch ($errno) {
            case E_USER_ERROR: {
                if ( ! headers_sent()) {
                    $_SESSION['cpm_error'] = $errstr;
                    session_write_close();
                    header('Location: ./scripts/500.php');
                    exit;
                }
                break;         
            }
            default: {
                return false;
                break;
            }
        }
        return false;
    }
    $cpm_error_handler = set_error_handler("cpm_error_handler");
    $script_include_dir = dirname(__FILE__);
    $abs_path_to_app = dirname(dirname(dirname(__FILE__))) . '/';
    $rel_path_to_app = str_replace($_SERVER['DOCUMENT_ROOT'], '', $abs_path_to_app);
    $session_name = 'cpm_session_id';   // Set a custom session name
    require_once($script_include_dir . "/../../includes/phpsvnclient/phpsvnclient.php");
    require_once("parse_readme.php");
    require_once("wp_formatting.php");
    require_once("functions.php");
    require_once("wp_custom_repo.php");
    require_once("cpm_classes.php");
    if (! file_exists($abs_path_to_app . ".htaccess")) {
        sec_session::sec_htaccess($rel_path_to_app);
    }

    $connection = cpm_db::_getInstance();
    $mysqli = $connection->_mysqli();
?>

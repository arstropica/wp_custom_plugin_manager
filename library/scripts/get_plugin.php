<?php
    ob_start();
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    require_once("includes/config.php");

    $output = array('outcome' => 0);
    if (empty($_GET['action'])) return false;
    // if (empty($_GET['slug'])) return false;

    $action = $_GET['action'];
    $slug = isset($_GET['slug']) ? $_GET['slug'] : false;
    $version = isset($_GET['version']) ? $_GET['version'] : false;
    $email = isset($_GET['email']) ? $_GET['email'] : false;
    $pass = isset($_GET['pass']) ? $_GET['pass'] : false;
    $format = isset($_GET['format']) ? $_GET['format'] : "json";
    $status = isset($_GET['status']) ? $_GET['status'] : "active";
    $jsoncallback = isset($_GET['jsoncallback']) ? $_GET['jsoncallback'] : "processJSON";

    $cpm_session = new sec_session($secure);
    $cpm_session->sec_session_start();
    $authorized = $cpm_session->sec_login_check();

    if (! $authorized) {
        $svn_options = $connection->get_svn_options();
        if ($svn_options && isset($svn_options['remote_auth'])) {
            $remote_auth = $svn_options['remote_auth'];
        } else {
            $remote_auth = true;
        }
        if ($remote_auth) {
            $authorized = $cpm_session->sec_login(rawurlencode($email), $pass);
        } else {
            $authorized = true;
        }
    }
    if ($authorized) {
        $wp_repo = new wp_custom_repo($status, true);
        switch($action) {
            case 'metadata' : {
                header('Content-Type: application/json');
                $plugin_meta = $wp_repo->plugin_info($slug, $version, $email, $pass);
                $output = $plugin_meta ? $plugin_meta : array();
                // exit(json_encode($output, JSON_FORCE_OBJECT));
                break;
            }

            case 'ping' : {
                $output['outcome'] = 1;
                break;
            }

            case 'list_plugins' : {
                $output['data'] = $wp_repo->get_plugins($slug, $version);
                $output['outcome'] = $output['data'] ? 1 : 0;
                break;
            }
            case 'plugin_information' : {
                if (empty($slug) === false) {
                    $output['data'] = $wp_repo->plugin_info($slug, $version, $email, $pass);
                    $output['outcome'] = $output['data'] ? 1 : 0;     
                }
                break;
            }
            case 'plugin_readme' : {
                if ((empty($slug) === false) && (empty($version) === false)) {
                    $_plugin_info = get_plugin_info_from_db($slug, $version);
                    if (@empty($_plugin_info['README']) === false) {
                        exit($_plugin_info['README']);
                    } else {
                        exit;
                    }   
                }
                break;
            }
            case 'plugin_download' : {
                if (empty($slug) === false) {
                    $wp_repo->plugin_info($slug, $version, $email, $pass);
                    $wp_repo->plugin_dl($slug, $version);
                    ob_end_flush();
                    exit;     
                }
                break;
            }
            default: {
                // die($output);
            }
        }
    } else {
        http_response_code(401);
    }
    $output['extrainfo'] = ob_get_clean();
    switch ($format) {
        case 'json' : {
            exit (json_encode($output, JSON_FORCE_OBJECT));
            break;
        }
        case 'jsonp' : {
            exit ($jsoncallback . "(" . json_encode($output, JSON_FORCE_OBJECT) . ")");
            break;
        }
        default : {
            exit (var_export($output, true));
            break;
        }
    }


    function url_exists($url) {
        if (!$fp = curl_init($url)) return false;
        return true;
    }

?>

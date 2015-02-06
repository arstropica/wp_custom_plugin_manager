<?php
    /*
    Plugin Name: ArsTropica Plugin Manager
    Plugin URI: http://arstropica.com
    Description: This plugin will allow you to control updates for plugins that are hosted at a subversion repository.
    Author: Akin Williams
    Version: 0.3
    Author URI: http://www.arstropica.com
    Text Domain: at-plugin-updater
    Domain Path: lang
    */

    /*ini_set('display_errors', 1);
    error_reporting(E_ALL);*/

    $ds_plugin_updater = new ds_plugin_updater();
    add_action('init', array($ds_plugin_updater, 'init'));

    class ds_plugin_updater {

        var $network_menu_page;
        var $blog_menu_page;
        var $ds_plugin_updater_url;
        var $ds_plugin_updater_user;
        var $ds_plugin_updater_pass;
        var $ds_plugin_updater_activeupdate;
        private static $plugin_data;

        public function init() {
            if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
                // Makes sure the plugin is defined before trying to use it
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
                require_once( ABSPATH . '/wp-includes/pluggable.php' );    
            }
            if (! class_exists('pups'))
                require_once('plugins_used_plugin.php');
            if ( ! class_exists('PluginUpdateChecker_1_3'))
                require_once('plugin-update-checker.php');
            if(is_network_admin() && current_user_can('manage_network_options')) {
                self::$plugin_data = get_plugin_data(__FILE__, false);
                add_action('network_admin_menu', array($this, 'add_network_options_page'));
            } elseif (is_admin() && current_user_can('manage_options')) {
                self::$plugin_data = get_plugin_data(__FILE__, false);
                add_action('admin_menu', array($this, 'add_options_page'));
            } else {
                return;
            }

            // Settings changed?
            if (isset($_POST['action']) && $_POST['action'] == 'save_ds-plugin-updater_settings')
                $this->applySettings();

            $this->ds_plugin_updater_url = get_site_option('at-plugin-updater-url', false);
            $this->ds_plugin_updater_user = get_site_option('at-plugin-updater-user', false);
            $this->ds_plugin_updater_pass = get_site_option('at-plugin-updater-pass', false);
            $this->ds_plugin_updater_activeupdate = get_site_option('at-plugin-updater-activeupdate', array());
            $this->plugin_updates();
        }

        public function add_network_options_page(){
            $this->network_menu_page = add_submenu_page('settings.php', 'ArsTropica Plugin Manager', 'ArsTropica Plugin Manager', 'manage_network_options', 'at-plugin-updater', array(&$this, 'ds_plugin_udpater_settings_page'));
            add_action('admin_print_scripts-'.$this->network_menu_page, array($this, 'add_scripts'));
            add_action('admin_print_styles-'.$this->network_menu_page, array($this, 'add_styles'));
        }

        public function add_options_page(){
            $this->blog_menu_page = add_options_page("ArsTropica Plugin Manager", "ArsTropica Plugin Manager", 'manage_options', "at-plugin-updater", array(&$this, 'ds_plugin_udpater_settings_page'));
            add_action('admin_print_scripts-'.$this->blog_menu_page, array($this, 'add_scripts'));
            add_action('admin_print_styles-'.$this->blog_menu_page, array($this, 'add_styles'));
        }

        function ds_plugin_udpater_settings_page() {
            global $pagenow;
            $pups = new pups();
            if (empty($this->ds_plugin_updater_url)) {
                $_GET['tab'] = 'settings';
            }
            $strTab = (isset($_GET['tab'])?$_GET['tab']:'homepage');
            $subtitle = (isset($_GET['tab'])?ucwords($_GET['tab']):'');
            // Show update message if settings saved
            if (isset($_POST['at-plugin-updater_settings_submit']) && $_POST['at-plugin-updater_settings_submit'] == 'Y')
                echo '<div id="message" class="updated fade"><p>'.__('Changes saved','at-plugin-updater').'</p></div>';

            echo '<div class="wrap">';
            echo '<h2>'.__('Custom Plugin Updater' . (($subtitle) ? ' : ' . $subtitle : ''), 'at-plugin-updater').'</h2>';
            // Show tabs
            $strTab = $this->showSettingsTabs($strTab);
            echo '<form class="at-plugin-updater-settings" method="post" action="'.admin_url(($pagenow == 'settings.php'?'network/':'').$pagenow.'?page=at-plugin-updater&tab='.$strTab).'">';
            echo '<input type="hidden" name="action" value="save_ds-plugin-updater_settings" />';
            wp_nonce_field('at-plugin-updater_settings');
            switch ($strTab) {
                case 'settings' : {
                    echo '<h3>'.__('Remote Settings', 'at-plugin-updater').'</h3>';
                    $this->ds_plugin_settings();
                    break;
                }
                case 'homepage' : 
                default : {
                    echo '<h3>'.__('Activate Plugin Updates', 'at-plugin-updater').'</h3>';
                    $repo_plugins_url = "http://" . $this->ds_plugin_updater_url . "/get_plugin.php?action=list_plugins";
                    if ($this->ds_plugin_updater_user) $repo_plugins_url .= "&email=" . $this->ds_plugin_updater_user;
                    if ($this->ds_plugin_updater_pass) $repo_plugins_url .= "&pass=" . hash('sha512', $this->ds_plugin_updater_pass);
                    $pups->displayPluginsAsTable($repo_plugins_url, $this->ds_plugin_updater_activeupdate); 
                    break;
                }
            }
            // Show submit button
            echo '<div class="tablenav bottom">';
            // echo '<p class="submit" style="clear: both;padding:0;margin:0"><input type="submit" name="Submit"  class="button-primary" value="'.__('Save settings', 'at-plugin-updater').'" /><input type="hidden" name="at-plugin-updater_settings_submit" value="Y" /></p>';
            submit_button();
            echo '<input type="hidden" name="at-plugin-updater_settings_submit" value="Y" />';
            echo '</div>';
            echo '</form></div>';
        }

        function showSettingsTabs($strCurr = 'homepage') {
            $aryTabs = array(
            'homepage' => __('Home','at-plugin-updater'),
            'settings' => __('Settings','at-plugin-updater')
            );
            if (empty($strCurr)) $strCurr = 'homepage';
            elseif (!isset($aryTabs[$strCurr]) && $strCurr != 'homepage') $strCurr = 'settings';
            echo '<div id="icon-themes" class="icon32"><br></div>';
            echo '<h2 class="nav-tab-wrapper">';
            foreach($aryTabs as $strTab => $strName) {
                $strClass = ($strTab == $strCurr?' nav-tab-active':'');
                echo '<a class="nav-tab'.$strClass.'" href="?page=at-plugin-updater&tab='.$strTab.'">'.$strName.'</a>';
            }
            echo '</h2>';
            return $strCurr;
        }

        function ds_plugin_settings() {
        ?>
        <table class="form-table" id="at-plugin-updater-url-settings">
            <tr valign="top">
                <th scope="row"><label for="site_name"><?php _e( 'Plugin Manager URL' ) ?></label></th>
                <td>
                    <p>http:// <input name="at-plugin-updater-url" type="text" id="at-plugin-updater-url" class="regular-text" value="<?php echo esc_attr( $this->ds_plugin_updater_url ); ?>" /> /get_plugin.php</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="site_user"><?php _e( 'Plugin Manager Username (optional)' ) ?></label></th>
                <td>
                    <p><input name="at-plugin-updater-user" type="text" id="at-plugin-updater-user" class="regular-text" value="<?php echo esc_attr( $this->ds_plugin_updater_user ); ?>" autocomplete="off" /></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="site_pass"><?php _e( 'Plugin Manager Password (optional)' ) ?></label></th>
                <td>
                    <p><input name="at-plugin-updater-pass" type="password" id="at-plugin-updater-pass" class="regular-text" value="<?php echo $this->ds_plugin_updater_pass; ?>" autocomplete="off" /></p>
                </td>
            </tr>
        </table>
        <?php
        }

        function applySettings() {
            if ($_POST) {
                if ( !current_user_can('manage_options') )
                    wp_die( __('Cheatin&#8217; uh?') );            
                //cross check the given referer
                check_admin_referer('at-plugin-updater_settings');
                foreach ($_POST as $key => $value) {
                    switch ($key) {
                        case 'at-plugin-updater-activeupdate' : {
                            update_site_option('at-plugin-updater-activeupdate', $value);
                            break;
                        }
                        case 'at-plugin-updater-url' : {
                            update_site_option('at-plugin-updater-url', $value);
                            break;
                        }
                        case 'at-plugin-updater-user' : {
                            update_site_option('at-plugin-updater-user', $value);
                            break;
                        }
                        case 'at-plugin-updater-pass' : {
                            update_site_option('at-plugin-updater-pass', $value);
                            break;
                        }
                    }
                }

            }
        }

        function getPluginURL() {
            // Return plugins URL + /wp-piwik/
            return trailingslashit(plugins_url(). '/' . basename(dirname(__FILE__)));
        }

        function add_styles() {
            wp_enqueue_style('at-plugin-updater', $this->getPluginURL().'css/at-plugin-updater.css',array(),self::$plugin_data['Version']);
        }

        function add_scripts() {
            wp_enqueue_script('sha-512', $this->getPluginURL().'js/sha512.js',array(),self::$plugin_data['Version']);
            wp_enqueue_script('at-plugin-updater', $this->getPluginURL().'js/at-plugin-updater.js',array('jquery','sha-512'),self::$plugin_data['Version']);
        }

        function plugin_updates() {
            if ($this->ds_plugin_updater_url && $this->ds_plugin_updater_activeupdate && is_array($this->ds_plugin_updater_activeupdate)) {
                $plugins_dir = (defined(WP_PLUGIN_DIR)) ? WP_PLUGIN_DIR : ABSPATH . 'wp-content/plugins';
                $mu_plugins_dir = (defined(WPMU_PLUGIN_DIR)) ? WPMU_PLUGIN_DIR : ABSPATH . 'wp-content/mu-plugins';
                foreach($this->ds_plugin_updater_activeupdate as $local_path) {
                    if (file_exists($plugins_dir . "/$local_path")) {
                        $plugin_path = $plugins_dir . "/$local_path";
                    } elseif (file_exists($mu_plugins_dir . "/$local_path")) {
                        $plugin_path = $plugins_dir . "/$local_path";
                    } else {
                        $plugin_path = false;
                    }                        
                    if ($plugin_path) {
                        if (basename(dirname($local_path)) == basename($local_path, ".php")) {
                            $plugin_slug = basename($local_path, ".php");
                        } else {
                            $plugin_slug = basename(dirname($local_path));
                        }
                        $update_url = "http://" . $this->ds_plugin_updater_url . "/get_plugin.php?action=metadata&slug=" . $plugin_slug;
                        if ($this->ds_plugin_updater_user) $update_url .= "&email=" . $this->ds_plugin_updater_user;
                        if ($this->ds_plugin_updater_pass) $update_url .= "&pass=" . hash('sha512', $this->ds_plugin_updater_pass);
                        $update = new PluginUpdateChecker($update_url, $plugin_path, $plugin_slug);
                        // var_dump($update_url, $plugin_path, $plugin_slug);
                    }
                }
            }
        }

    }
?>

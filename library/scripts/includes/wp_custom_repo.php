<?php
    require_once('cpm_classes.php');

    class wp_custom_repo {
        var $client;
        var $svn_url;
        var $svn_user;
        var $svn_pass = false;
        var $svn_path_to_plugins = false;
        var $global_author = false;
        var $plugins;
        var $status;
        var $parser;
        var $cpm_db;
        var $valid;

        public function __construct($status = "active", $headless = true, $overrides = false) {
            $this->status = $status;
            $this->plugins = array();
            $this->cpm_db =  cpm_db::_getInstance();
            if (($overrides) && (is_array($overrides)) && isset($overrides['svn_url']) && isset($overrides['svn_path_to_plugins'])) {
                $svn_options = array_filter($overrides);
            } else {
                $svn_options = $this->get_svn_options();
            }
            if ($svn_options) {
                foreach ($svn_options as $option_name => $option_value) {
                    $this->$option_name = $option_value;
                }
                $this->client = new phpsvnclient($this->svn_url,$this->svn_user,$this->svn_pass);
                $this->parser = new WordPress_Readme_Parser();
            } else {
                if ($headless) {
                    $exception = new cpmException("Credentials for the Subversion Repository were not found.");
                    exit;
                } else {
                    $exception = new cpmException("Credentials for the Subversion Repository were not found.", '../setup/index.php');
                }
            }
            $this->valid = $this->test_svn_options();
        }

        public function get_svn_options() {
            $svn_options = $this->cpm_db->get_svn_options();
            if ($svn_options && is_array($svn_options)) {
                return $svn_options;
            }
            return false;
        }
        
        public function test_svn_options() {
            return $this->client->getVersion() > 0;
        }

        public function get_plugins($slug = false, $version = false) {
            $_path = trailingslashit("/" . $this->svn_path_to_plugins);
            $status = $this->status;
            $_plugins = $this->client->getDirectoryFiles($_path);
            if (is_array($_plugins) && (count($_plugins) > 1)) {
                for ($i = 1; $i < count($_plugins); $i ++) {
                    if ($_plugins[$i]['type'] == "directory") {
                        $_plugin_versions = $this->client->getDirectoryFiles($_plugins[$i]['path']);
                        $_plugin_slug = basename($_plugins[$i]['path']);
                        if (is_array($_plugin_versions) && (count($_plugin_versions) > 1) && (!$slug || $slug == $_plugin_slug)) {
                            for ($j = 1; $j < count($_plugin_versions); $j ++) {
                                if ($_plugin_versions[$j]['type'] == "directory") {
                                    $_plugin_version = $this->client->getDirectoryFiles($_plugin_versions[$j]['path']);
                                    if (is_array($_plugin_version) && (count($_plugin_version) == 2) && (basename($_plugin_version[1]['path']) == $_plugin_slug)) {
                                        $_version = substr(basename($_plugin_versions[$j]['path']), 1);
                                        if ($version === false || $_version == $version) {
                                            $db_info = $this->plugin_info_db($_plugin_slug, $_version);
                                            if ((@empty($db_info['ACTIVE']) === false) || ($status != "active")) {
                                                $this->plugins['plugins'][$_plugin_slug]['parent_dir'] = $_plugins[$i]['path'];
                                                $this->plugins['plugins'][$_plugin_slug]['versions'][$_version] = array('path' => $_plugin_version[1]['path'], 'last_modified' => $_plugin_version[1]['last-mod'], 'readme' => false, 'active' => false);
                                                if ($db_info) {
                                                    if (empty($db_info['README']) === false) {
                                                        $this->plugins['plugins'][$_plugin_slug]['versions'][$_version]['readme'] = "http://" . $_SERVER['HTTP_HOST'] . "/scripts/get_plugin.php?action=plugin_readme&slug=$_plugin_slug&version=$_version";
                                                    } elseif (@$this->client->getFile($_plugin_version[1]['path'] . "/readme.txt")) {
                                                        $this->plugins['plugins'][$_plugin_slug]['versions'][$_version]['readme'] = $_plugin_version[1]['path'] . "/readme.txt";
                                                    }
                                                    if (empty($db_info['ACTIVE']) === false) {
                                                        $this->plugins['plugins'][$_plugin_slug]['versions'][$_version]['active'] = true;
                                                    }
                                                } elseif (@$this->client->getFile($_plugin_version[1]['path'] . "/readme.txt")) {
                                                    $this->plugins['plugins'][$_plugin_slug]['versions'][$_version]['readme'] = $_plugin_version[1]['path'] . "/readme.txt";
                                                }                                      
                                            }
                                        }
                                    }
                                }
                            }
                        }                        
                    }
                }
            }
            return $this->plugins;
        }

        public function plugin_info($slug, $version = false, $email = false, $pass = false) {
            $global_author = $this->global_author;
            $status = $this->status;
            $headers = array();
            if (! $this->plugins) {
                $plugins = $this->get_plugins($slug, $version);
            } else {
                $plugins = $this->plugins;
            }

            if (isset($plugins['plugins'][$slug]['versions'])) {
                if ($status == "active") {
                    $plugins['plugins'][$slug]['versions'] = array_filter($plugins['plugins'][$slug]['versions'], function($_plugin){
                        return $_plugin['active'];
                    });                      
                }
                $_plugin = end($plugins['plugins'][$slug]['versions']);
                $plugin_path = $_plugin['path'];
                $_version = key($plugins['plugins'][$slug]['versions']);
                $last_updated = false;
                $plugin_files = $this->client->getDirectoryFiles($plugin_path);
                if (is_array($plugin_files) && (count($plugin_files) > 1)) {
                    for ($i = 0; $i < count($plugin_files); $i ++) {
                        if ($plugin_files[$i]['type'] == 'file') {
                            if (stristr(basename($plugin_files[$i]['path']), ".php")) {
                                $file_content = $this->client->getFile($plugin_files[$i]['path']);
                                if ($_headers = wcr_get_file_data($file_content, false, 'content')) {
                                    $last_updated = $plugin_files[$i]['last-mod'];
                                    $headers = array_merge($headers, $_headers);
                                    if (! isset($headers['version'])) $headers['version'] = $_version;
                                    if ($global_author) $headers['Author'] = (isset($headers['Author'])) ? implode(", ", array_merge(array_map("trim", explode(",", $headers['Author'])), array($global_author))) : $global_author;
                                }                                               
                            } elseif (basename($plugin_files[$i]['path']) == "readme.txt") {
                                $file_content = $this->client->getFile($plugin_files[$i]['path']);
                                if ($_headers = $this->parser->parse_readme($file_content, 'content')) {
                                    $last_updated = $plugin_files[$i]['last-mod'];
                                    $headers = array_merge($_headers, $headers);
                                }                                
                            }
                        }
                    }
                }
                if ($file_content = $this->plugin_readme_db($slug, $_version)) {
                    if ($_headers = $this->parser->parse_readme($file_content, 'content')) {
                        $headers = array_merge($_headers, $headers);
                    }                                
                }                                
            }
            if ($headers) {
                $headers['slug'] = $slug;
                $headers['download_url'] = "http://" . $_SERVER['HTTP_HOST'] . "/scripts/get_plugin.php?action=plugin_download&slug=$slug" . ($version ? "&version=$version" : "") . ($email ? "&email=$email" : "") . ($pass ? "&pass=$pass" : "");
                if ($last_updated) $headers['last_updated'] = date("Y-m-d", strtotime($last_updated));
                foreach ($headers as $header => $hvalue) {
                    switch ($header) {
                        case 'PluginURI' : {
                            if (! isset($headers['homepage'])) $headers['homepage'] = $hvalue;
                            break;
                        }
                        case 'requires_at_least' : {
                            if (! isset($headers['requires'])) $headers['requires'] = $hvalue;
                            break;
                        }
                        case 'tested_up_to' : {
                            if (! isset($headers['tested'])) $headers['tested'] = $hvalue;
                            break;
                        }
                        case 'Author' : {
                            if (! isset($headers['author'])) $headers['author'] = $hvalue;
                            break;
                        }
                        case 'AuthorURI' : {
                            if (! isset($headers['author_homepage'])) $headers['author_homepage'] = $hvalue;
                            break;
                        }
                    }
                }

            }
            return $headers;
        }

        function plugin_readme_db($slug, $version) {
            $info = $this->plugin_info_db($slug, $version);
            return @empty($info['README']) ? false : $info['README'];
        }

        function plugin_info_db($slug, $version) {
            $info = $this->cpm_db->get_plugin_info_from_db($slug, $version);
            return $info;
        }

        function get_plugin_files($slug, $version) {
            $output = false;
            if (! $this->plugins) {
                $plugins = $this->get_plugins($slug, $version);
            } else {
                $plugins = $this->plugins;
            }
            if (@isset($plugins['plugins'][$slug]['versions'])) {
                if (@isset($plugins['plugins'][$slug]['versions'][$version])) {
                    $_plugin = $plugins['plugins'][$slug]['versions'][$version];
                } elseif (! $version) {
                    $_plugin = end($plugins['plugins'][$slug]['versions']);
                    $version = key($plugins['plugins'][$slug]['versions']);
                } else {
                    return $output;
                }
                $plugin_path = $_plugin['path'];
                $dir_files = $this->client->getDirectoryTree($plugin_path, -1, true);
                if (is_array($dir_files)) {
                    $files = array_filter($dir_files, function($entry){
                        if (is_array($entry) && isset($entry['type']) && $entry['type'] == 'file') {
                            return true;
                        } else {
                            return false;
                        }
                    });

                    if ($files && is_array($files)) {
                        $output['absolute'] = array_filter(
                        array_map(function($entry){
                            if (isset($entry['path']) && isset($entry['status']) && ($entry['status'] == 'HTTP/1.1 200 OK')) {
                                return $entry['path'];
                            }
                        }, $files)
                        );

                        if ($output['absolute'] && is_array($output['absolute'])) {
                            $output['local'] = $output['absolute'];
                            array_walk($output['local'], function(&$entry, $index, $strip){
                                $entry = str_replace($strip, "", $entry);
                            },
                            trailingslashit(dirname($plugin_path)));
                        }
                    }
                }
            }
            return $output;
        }

        function plugin_dl($slug, $version) {
            $file_array = $this->get_plugin_files($slug, $version);
            $tmp_file = false;
            if ($file_array) {
                $zip = new ZipArchive();

                # create a temp file & open it
                $tmp_file = tempnam('.','');
                $zip->open($tmp_file, ZipArchive::CREATE);

                # loop through each file
                foreach (array_keys($file_array['absolute']) as $findex) {
                    $file_contents = $this->client->getFile($file_array['absolute'][$findex]);
                    $zip->addFromString($file_array['local'][$findex], $file_contents);
                }

                # close zip
                $zip->close();

                # send the file to the browser as a download
                header('Content-disposition: attachment; filename=' . $slug . '.zip');
                header('Content-type: application/zip');
                readfile($tmp_file);
                unlink($tmp_file);
            }
        }
    }

?>

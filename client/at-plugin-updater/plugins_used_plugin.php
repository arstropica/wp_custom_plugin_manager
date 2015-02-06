<?php

    class pups {
        var $myPluginFiles;

        function __construct() {
            $activePluginsResult = array_keys(get_plugins());
            /*$activePluginsResult = get_settings('active_plugins');
            $allPluginsResult = get_plugins();
            echo "<script>console.dir(" . json_encode($activePluginsResult, JSON_FORCE_OBJECT) . ");</script>\n";
            echo "<script>console.dir(" . json_encode($allPluginsResult, JSON_FORCE_OBJECT) . ");</script>\n";*/
            if (is_array($activePluginsResult)) {
                $pup_plugin_files = $activePluginsResult;
            } else {
                $pup_plugin_files = explode("\n",$activePluginsResult);
            };
            $this->myPluginFiles = array_values($pup_plugin_files);
            if (is_array($this->myPluginFiles[0])) {
                // new style - used the keys, not the values
                $this->myPluginFiles = array_keys($pup_plugin_files);
            };
            sort($this->myPluginFiles); // Alphabetize by filename. Better way?
            $this->myPluginFiles=array_unique($this->myPluginFiles);

            $this->myPluginFiles = $this->pups_getPlugins();
        }

        function pups_getPluginData($plugin_file) {
            if (trim($plugin_file) == "") return '';
            if (!file_exists(ABSPATH . '/wp-content/plugins/' .
            $plugin_file)) return '';
            if (!is_readable(ABSPATH . '/wp-content/plugins/' .
            $plugin_file)) return '';
            $plugin_slug = basename($plugin_file, '.php');
            $plugin_data = implode('', file(ABSPATH .
            '/wp-content/plugins/' . $plugin_file));
            preg_match("|Plugin Name:(.*)|i", $plugin_data,
            $plugin_name);
            if ('' == $plugin_name[1]) return '';
            preg_match("|Plugin URI:(.*)|i", $plugin_data, $plugin_uri);
            preg_match("|Description:(.*)|i", $plugin_data,
            $description);
            preg_match("|Author:(.*)|i", $plugin_data, $author_name);
            preg_match("|Author URI:(.*)|i", $plugin_data, $author_uri);
            if ( preg_match("|Version:(.*)|i", $plugin_data, $version) )
                $version = $version[1];
            else
                $version ='';

            $description = wptexturize($description[1]);
            $description = wp_kses($description, array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array()) );

            if ('' == $plugin_uri) {
                $plugin = $plugin_name[1];
            } else {
                $plugin = __("<a href='".@trim($plugin_uri[1])."' title='Visit plugin homepage'>{$plugin_name[1]}</a>");
            }

            if ('' == $author_uri) {
                $author = $author_name[1];
            } else {
                $author = __("<a href='".trim($author_uri[1])."' title='Visit author homepage'>{$author_name[1]}</a>");
            }

            return array('plugin_name' => trim($plugin_name[1]), 'plugin_uri' => $plugin_uri[1], 'description' => $description, 'author_name' => $author_name[1], 'author_uri' => $author_uri[1], 'version' => $version, 'plugin' => $plugin, 'author' => $author, 'plugin_slug' => $plugin_slug );
        }

        function pups_sortPlugins($plug1, $plug2) {
            return @strnatcasecmp($plug1['plugin_name'], $plug2['plugin_name']);
        }


        function pups_getPlugins() {
            $result = array();
            foreach($this->myPluginFiles as $plugin_file) {
                $current = $this->pups_getPluginData($plugin_file);
                if ('' != $current)
                    $result[$current['plugin_name']] = $current;
            };
            if ($result) {
                uksort($result, array($this, 'pups_sortPlugins'));
            }
            $this->myPluginFiles = $result;
            return $this->myPluginFiles;
        }

        function displayPluginsAsTable($repo_url, $selected = array()) {
            $showDescription="1";
            $style = '';
            $tableStr = ' class="wp-list-table widefat plugins" cellspacing="0" style="margin: 10px auto;" ';
            $affected_plugins = false;
            if ($repo_url) {
                $plugins = array_merge(get_plugins(), get_mu_plugins());
                $repo_plugins = $this->get_remote_plugins($repo_url);
                if ($repo_plugins && is_array($repo_plugins)) {
                    $affected_plugins = array_intersect_ukey($plugins, $repo_plugins, function($installed_filename, $remote_slug){
                        if ((strcasecmp(basename($installed_filename, ".php"), $remote_slug) === 0) || (strcasecmp(basename(dirname($installed_filename)), $remote_slug) === 0)) {
                            return 0;
                        } else {
                            return strcasecmp(basename($installed_filename, ".php"), $remote_slug); 
                        }
                    });
                }              
            }
            // var_dump($repo_plugins);
            // var_dump($plugins);
        ?>
        <table <?php print $tableStr; ?> >
            <?php            
                if ($affected_plugins && $repo_url) :
                ?>
                <thead>
                    <tr>
                        <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input id="cb-select-all-1" type="checkbox"></th>
                        <th scope="col" class="manage-column column-name"><?php _e('Plugin'); ?></th>
                        <th scope="col" class="manage-column column-version"><?php _e('Version'); ?></th>
                        <th scope="col" class="manage-column column-author"><?php _e('Author'); ?></th>
                        <?php if ($showDescription == "1") { ?>
                            <th scope="col" class="manage-column column-description"><?php _e('Description'); ?></th>
                            <?php }; ?>
                    </tr>
                </thead>
                <?php
                    $style = '';
                    foreach($affected_plugins as $plugin_file => $plugin_data) {

                        $plugin_slug = basename($plugin_file, '.php');
                        $plugin_name = $plugin_data['Name'];
                        $plugin_uri = $plugin_data['PluginURI'];
                        $plugin_title = "<a href='".$plugin_uri."' title='Visit plugin homepage'>{$plugin_name}</a>";
                        $author_name = $plugin_data['Author'];
                        $author_uri = $plugin_data['AuthorURI'];
                        $author = "<a href='".$author_uri."' title='Visit plugin homepage'>{$author_name}</a>";
                        $version = $plugin_data['Version'];
                        $description = $plugin_data['Description'];
                        $checked = in_array($plugin_file, $selected) ? "checked=\"checked\"" : "";

                        $style = ('class="alternate"' == $style) ? '' :
                        'class="alternate"';

                        echo "
                        <tr $style>
                        ";
                        echo "<th scope=\"row\" class=\"check-column\">
                        <input type=\"checkbox\" name=\"at-plugin-updater-activeupdate[]\" value=\"$plugin_file\" id=\"at-plugin-updater-activeupdate_$plugin_file\" value=\"1\" $checked />
                        </th>\n";
                        echo "                        
                        <td>$plugin_title
                        <!--\n";
                        print "Plugin Name: ".$plugin_name."\n";
                        print "Plugin URI: ".$plugin_uri."\n";
                        print "Plugin Slug: ".$plugin_slug."\n";
                        print "Author: ".$author_name."\n";
                        print "Description: ".htmlspecialchars($description)."\n";
                        print "Author URI: ".$author_uri."\n";
                        print "Version: ".$version."\n";
                        echo "-->
                        </td>
                        <td>$version</td>
                        <td>$author</td>
                        ";
                        if($showDescription) {
                            echo "
                            <td>$description</td>
                            ";
                        };
                        echo "
                        </tr>
                        ";                                               
                    };
                    else :
                    echo "<tr $style><td>No matching plugins could be found.  Please ensure a valid URL has been entered in Settings or check your Custom Repository Manager to configure and activate plugins.</td></tr>\n";
                    endif;
            ?>

        </table>

        <?php

        }
        // end of that function

        function displayPluginsAsList() {
        ?>
        <ul>
            <?php
                foreach($this->myPluginFiles as $plugin_file) {
                    $plugin = $plugin_file['plugin'];
                    echo "
                    <li> $plugin </li>";
                };
            ?>

        </ul>

        <?php

        }
        // end of that function

        function get_remote_plugins($url) {
            $outcome = false;

            if( !class_exists( 'WP_Http' ) )
                include_once( ABSPATH . WPINC. '/class-http.php' );

            $request = new WP_Http;
            $result = $request->request( $url, array( 'timeout' => 30 ) );

            if ($result && is_array($result) && isset($result['body'])) {
                $json = $result['body'];
                $decoded = json_decode($json, true);
                if ($decoded['outcome'] && @isset($decoded['data']['plugins'])) {
                    $outcome = $decoded['data']['plugins'];
                }
            }

            return $outcome;

        }

    }

?>
<?php
    /*ini_set('display_errors', 1);
    error_reporting(E_ALL);*/

    require_once("includes/config.php");

    $cpm_session = new sec_session($secure);
    $cpm_session->sec_session_start();
    $logged_in = $cpm_session->sec_login_check();
    if($logged_in == false) {
        header('Location: ./scripts/login.php', true, 302);
        exit;
        // exit('You are not authorized to access this page, please login.');
    }
    require_once($script_include_dir . "/../../includes/HTML_To_Markdown.php");

    $readme_template = false;
    $pluginname = "";
    $contributors = "";
    $tags = "";
    $requires = "";
    $tested = "";
    $stable = "";
    $shortdesc = "";
    $longdesc = "";
    $donatelink = "";
    $install = "Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.";
    $faq = "";
    $changelog = "";
    $screenshot1 = "";
    $screenshot2 = "";
    $video_count = "";
    $arbitrary = "";

    if (isset($_GET['slug'])) {
        $slug = $_GET['slug'];
        $version = empty($_GET['version']) ? false : $_GET['version'];
        $wp_repo = new wp_custom_repo(false, false);
        $plugin_information = $wp_repo->plugin_info($slug, $version);
        if ($plugin_information) {
            foreach ($plugin_information as $key => $value) {
                switch ($key) {
                    case "name" : {
                        $pluginname = $value;
                        break;
                    }
                    case "contributors" : {
                        $contributors = implode(", ", $value);
                        break;
                    }
                    case "tags" : {
                        $tags = implode(", ", $value);
                        break;
                    }
                    case "requires_at_least" : {
                        $requires = $value;
                        break;
                    }
                    case "tested_up_to" : {
                        $tested = $value;
                        break;
                    }
                    case "stable_tag" : {
                        $stable = $value;
                        break;
                    }
                    case "sections" : {
                        foreach ($value as $subkey => $child_value) {
                            switch ($subkey) {
                                case 'description' : {
                                    $longdesc = $child_value;
                                    break;
                                }
                                case 'installation' : {
                                    $install = $child_value;
                                    break;
                                }
                                case 'change_log' : 
                                case 'changelog' : {
                                    $changelog = $child_value;
                                    break;
                                }
                                case 'frequently_asked_questions' :
                                case 'faq' : {
                                    $faq = $child_value;
                                    break;
                                }
                                case 'arbitrary' : 
                                case 'arbitrary_section' : {
                                    $arbitrary .= $child_value . "\n";
                                    break;
                                }
                            }
                        }
                        break;
                    }
                    case "short_description" : {
                        $shortdesc = $value;
                        break;
                    }
                    case "donate_link" : 
                    case "donatelink" : {
                        $donatelink = $value;
                        break;
                    }
                    case "screenshots" : {
                        foreach ($value as $idx => $ss) {
                            $screenshot = "screenshot" . $idx;
                            $$screenshot = $ss;
                        }
                        break;
                    }
                    case "arbitrary" : 
                    case "remaining_content" : {
                        $arbitrary .= $value . "\n";
                        break;
                    }
                }
            }

            foreach ($plugin_information as $key => $value) {
                switch ($key) {
                    case "Name" : {
                        if (empty($pluginname)) $pluginname = $value;
                        break;
                    }
                    case "Author" : {
                        if (empty($contributors)) $contributors = $value;
                        break;
                    }
                    case "version" : {
                        if (empty($stable)) $stable = $value;
                        break;
                    }
                    case "Description" : {
                        if (empty($shortdesc)) $shortdesc = $value;
                        break;
                    }
                }
            }
        }
    } 
    if (isset($_POST['readme']) && isset($_POST['text'])) { 
        if ($_POST['text'] == 'saveastext') {
            header('Content-Type: text/plain'); // you can change this based on the file type
            header('Content-Disposition: attachment; filename="readme.txt"');
            echo $_POST['readme'];
            exit();
        } elseif ($_POST['text'] == 'savetodb' && isset($_POST['slug']) && ! empty($_POST['version'])) {
            $connection->set_plugin_info_in_db($_POST['slug'], $_POST['version'], false, $_POST['readme']);
            exit("<h2>Readme.txt for " . $_POST['slug'] . " saved</h2>");
        }
    } elseif (isset($_POST)) {
        $pre_processed_readme_array = array();
        $processed_readme_array = array();
        foreach ($_POST as $key => $value) {
            switch ($key) {
                case "pluginname" :
                case "contributors" :
                case "tags" :
                case "requires" :
                case "tested" :
                case "stable" :
                case "shortdesc" :
                case "longdesc" :
                case "donatelink" :
                case "install" :
                case "faq" :
                case "changelog" :
                case "screenshot1" :
                case "screenshot2" :
                case "video_count" :
                case "arbitrary" : {
                    $pre_processed_readme_array[$key] = $value;
                    break;
                }
            }
        }
        if ($pre_processed_readme_array) {
            extract($pre_processed_readme_array);
            foreach ($pre_processed_readme_array as $_section => $_content) {
                $markdown = new HTML_To_Markdown($_content, array('header_style' => 'setext', 'strip_tags' => true));
                $processed_readme_array[$_section] = $markdown->convert($_content);
            }
        }
        if ($processed_readme_array) {
            $readme_template = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/scripts/includes/readme.tmpl.txt");
            foreach ($processed_readme_array as $_section => $_content) {
                $readme_template = str_replace("{" . $_section . "}", $_content, $readme_template);
            }
            $readme_template = preg_replace("/\{\\w+\}/i", "", $readme_template);
            // var_dump(array_map(function($obj){return $obj->output;}, $processed_readme_array));
            // var_dump($processed_readme_array);
        }
    }

?>
<!DOCTYPE html>
<html>
    <head>
        <style type="text/css" media="screen">
            @import "/scripts/css/uniform/uniform.css";
            #wrapper {
                width: 800px;
                margin: 0 auto;
            }
            H2.title {
                text-align: center;
                font-family: Tahoma, Verdana, Arial, sans-serif;
                font-weight: normal;
            }
            fieldset img {
                padding: 0;
            }

            #shortdesc {
                height: 5em;
            }

            .counter {
                margin: 0.5em 0pt 0pt 30%;
            }

            span.toomuch {
                color:#CC0000;
                font-weight:600;
            }

            div.video_container {
                margin: 0.5em 0 0 30%;
            }
        </style>

        <!--[if lte ie 6]>
        <style type="text/css" media="screen">
        @import "/scripts/css/uniform/uniform-ie6.css";
        </style>
        <![endif]-->
        <title>WordPress Readme Generator</title>
    </head>
    <body>
        <div id="wrapper">
            <h2 class="title">WordPress Plugin Readme Generator</h2>
            <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" id="readme_form" class="uniForm">

                <div id="errorMsg">
                    <h3>Oops!, We Have a Problem.</h3>
                    <ol>
                    </ol>
                </div>

                <?php if ($readme_template) : ?>
                    <fieldset class="inlineLabels readme">
                        <legend>ReadMe.txt</legend>
                        <div class="ctrlHolder">
                            <textarea name="readme" id="readme" rows="10" cols="25" style='width: 100%; height: 550px; margin: 0 auto;'><?php echo $readme_template; ?></textarea>
                        </div>
                    </fieldset>
                    <?php endif; ?>

                <fieldset class="inlineLabels<?php echo ($readme_template) ? " disabled" : ""; ?>">
                    <legend>Required fields</legend>

                    <div class="ctrlHolder">
                        <label for="pluginname"><em>*</em>Plugin Name</label>
                        <input name="pluginname" id="pluginname" size="35" maxlength="50" class="textInput" type="text" value="<?php echo $pluginname; ?>">
                    </div>

                    <div class="ctrlHolder">
                        <label for="contributors"><em>*</em>Contributors</label>
                        <input name="contributors" id="contributors" size="35" maxlength="50" class="textInput" type="text" value="<?php echo $contributors; ?>">
                        <p class="formHint">"Contributors" is a comma separated list of wp.org/wp-plugins.org usernames</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="tags"><em>*</em>Tags</label>
                        <input name="tags" id="tags" size="35" maxlength="50" class="textInput" type="text" value="<?php echo $tags; ?>">
                        <p class="formHint">"Tags" is a comma separated list of tags that apply to the plugin</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="requires"><em>*</em>Requires at least</label>
                        <input name="requires" id="requires" size="35" maxlength="20" class="textInput" type="text" value="<?php echo $requires; ?>">
                        <p class="formHint">"Requires at least" is the lowest version that the plugin will work on</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="tested"><em>*</em>Tested up to</label>
                        <input name="tested" id="tested" size="35" maxlength="20" class="textInput" type="text" value="<?php echo $tested; ?>">
                        <p class="formHint">"Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on higher versions... this is just the highest one you've verified.</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="stable"><em>*</em>Stable Tag</label>
                        <input name="stable" id="stable" size="35" maxlength="20" class="textInput" type="text" value="<?php echo $stable; ?>">
                        <p class="formHint">Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for stable. If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where you put the stable version, in order to eliminate any doubt.</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="shortdesc"><em>*</em>Short Description</label>
                        <textarea name="shortdesc" id="shortdesc" rows="3" cols="25" maxlength = "150" ><?php echo $shortdesc; ?></textarea>
                        <p class="formHint">Description of the Plugin in less than 2 - 3 sentences. Maximum 150 characters, rest will be truncated.</p>
                    </div>
                </fieldset>

                <fieldset class="inlineLabels<?php echo ($readme_template) ? " disabled" : ""; ?>">
                    <legend>Optional fields</legend>

                    <div class="ctrlHolder">
                        <label for="longdesc">Long Description</label>
                        <textarea name="longdesc" id="longdesc" rows="8" cols="25"><?php echo $longdesc; ?></textarea>
                        <p class="formHint">Detailed description of the Plugin. If it is not specified then the short description is used</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="donatelink">Donate Link</label>
                        <input name="donatelink" id="donatelink" size="35" maxlength="75" class="textInput" type="text" value="<?php echo $donatelink; ?>">
                        <p class="formHint">If you accept donations then specify the link to your donations page</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="install">Installation Instruction</label>
                        <textarea name="install" id="install" rows="5" cols="25">
                            <?php echo $install; ?>
                        </textarea>
                        <p class="formHint">Describe how to install the Plugin and get it working</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="faq">Frequently Asked Questions</label>
                        <textarea name="faq" id="faq" rows="5" cols="25"><?php echo $faq; ?></textarea>
                        <p class="formHint">Frequently Asked Questions (if any)</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="changelog">Change Log</label>
                        <textarea name="changelog" id="changelog" rows="5" cols="25"><?php echo $changelog; ?></textarea>
                        <p class="formHint">Change Log (if any)</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="screenshot1">First Screenshot Description</label>
                        <textarea name="screenshot1" id="screenshot1" rows="5" cols="25"><?php echo $screenshot1; ?></textarea>
                        <p class="formHint">Description for first screenshot. The file should be named screenshot-1.(png|jpg|jpeg|gif) and should be placed in the directory of the stable readme.txt. The file extension can be any one of these png, jpg, jpeg, gif</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="screenshot2">Second Screenshot Description</label>
                        <textarea name="screenshot2" id="screenshot2" rows="5" cols="25"><?php echo $screenshot2; ?></textarea>
                        <p class="formHint">Description for the second screenshot. The file should be named screenshot-1.(png|jpg|jpeg|gif) and should be placed in the directory of the stable readme.txt. The file extension can be any one of these png, jpg, jpeg, gif</p>
                    </div>

                    <div class="ctrlHolder">
                        <label for="arbitrary">Arbitrary section</label>
                        <textarea name="arbitrary" id="arbitrary" rows="5" cols="25"><?php echo $arbitrary; ?></textarea>
                        <p class="formHint">You may provide arbitrary information here. This may be of use for extremely complicated plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
                            "installation." </p>
                    </div>

                </fieldset>
                <input type="hidden" name="slug" value="<?php echo $slug; ?>" />
                <input type="hidden" name="version" value="<?php echo $version; ?>" />
                <div class="buttonHolder">
                    <?php if ($readme_template) : ?>
                        <input type="hidden" name="text" id="text" />
                        <button type="button" class="editButton">Edit</button>
                        <button type="submit" name="save" id="save" class="saveButton">Save</button>
                        <button type="submit" name="download" id="download" class="downloadButton">Download</button>
                        <?php endif; ?>
                    <button type="reset" class="resetButton<?php echo ($readme_template) ? " disabled" : ""; ?>">Reset</button>
                    <button type="submit" name="submit" id="submit" class="submitButton<?php echo ($readme_template) ? " disabled" : ""; ?>">Generate</button>
                </div>

            </form>
            <script src="js/jquery.js" type="text/javascript"></script>
            <script src="js/jquery-migrate.min.js" type="text/javascript"></script>
            <script src="js/tiny_mce/tiny_mce.js" type="text/javascript"></script>
            <script src="js/uni-form.js" type="text/javascript"></script>
            <script type="text/javascript">
                // Auto set on page load...
                jQuery(document).ready(function(){
                    jQuery('.uniForm .disabled').hide().find(':input, button').attr('disabled', true);
                    jQuery('.editButton').on('click', function(e){
                        e.preventDefault();
                        jQuery('.uniForm .disabled').show().find(':input, button').removeAttr('disabled');
                        jQuery('.saveButton, .editButton, .downloadButton, .inlineLabels.readme').hide();
                        return false;
                    });
                    jQuery('.downloadButton').on('click', function(e){
                        jQuery('#text').val("saveastext");
                    });
                    jQuery('.saveButton').on('click', function(e){
                        jQuery('#text').val("savetodb");
                    });
                    jQuery('.submitButton').on('click', function(e){
                        jQuery('#text').val("");
                    });
                    jQuery('form.uniForm').uniform();
                    setMaxLength();
                    /*jQuery('.add_video').live('click', function (e) {
                    var $this = jQuery(this), 
                    $parent = $this.parent('.video_container');

                    $parent.clone().insertAfter($parent);
                    $this.remove();
                    jQuery('#video_count').val(parseInt(jQuery('#video_count').val(), 10) + 1);
                    });*/
                });

                function setMaxLength() {
                    var x = document.getElementsByTagName('textarea');
                    var counter = document.createElement('div');
                    counter.className = 'counter';
                    for (var i=0;i<x.length;i++) {
                        if (x[i].getAttribute('maxlength')) {
                            var counterClone = counter.cloneNode(true);
                            counterClone.relatedElement = x[i];
                            counterClone.innerHTML = '<span>0</span>/'+x[i].getAttribute('maxlength');
                            x[i].parentNode.insertBefore(counterClone,x[i].nextSibling);
                            x[i].relatedElement = counterClone.getElementsByTagName('span')[0];

                            x[i].onkeyup = x[i].onchange = checkMaxLength;
                            x[i].onkeyup();
                        }
                    }
                }

                function checkMaxLength() {
                    var maxLength = this.getAttribute('maxlength');
                    var currentLength = this.value.length;
                    if (currentLength > maxLength)
                        this.relatedElement.className = 'toomuch';
                    else
                        this.relatedElement.className = '';
                    this.relatedElement.firstChild.nodeValue = currentLength;
                    // not innerHTML
                }
            </script>
            <script type="text/javascript">
                tinyMCE.init({
                    theme : "advanced",
                    mode : "exact",
                    plugins: "paste",
                    elements : "longdesc,install,changelog,faq,screenshot1,screenshot2",
                    theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,bullist,numlist,separator,undo,redo,separator,link,unlink,separator,cut,copy,paste,separator,pastetext,pasteword",
                    theme_advanced_buttons2 : "",
                    theme_advanced_buttons3 : "",
                    theme_advanced_toolbar_location : "top",
                    theme_advanced_toolbar_align : "left",
                    theme_advanced_statusbar_location : "bottom",
                    extended_valid_elements : "a[name|href|target|title],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|name]"
                });
                tinyMCE.init({
                    theme : "advanced",
                    mode : "exact",
                    elements : "arbitrary",
                    plugins: "paste",
                    theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,bullist,numlist,separator,undo,redo,separator,link,unlink,separator,cut,copy,paste,separator,pastetext,pasteword,|,formatselect",
                    theme_advanced_buttons2 : "",
                    theme_advanced_buttons3 : "",
                    theme_advanced_toolbar_location : "top",
                    theme_advanced_toolbar_align : "left",
                    theme_advanced_statusbar_location : "bottom",
                    extended_valid_elements : "a[name|href|target|title],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|name]"
                });
            </script>
        </div>
    </body>
</html>
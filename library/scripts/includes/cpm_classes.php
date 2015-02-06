<?php
    require_once('config.php');

    class cpm_db {
        private static $_instance;
        private $conn;
        private $database_host;
        private $database_user;
        private $database_pass;
        private $database_name;

        public static function _getInstance($database_host = false, $database_user = false, $database_pass = false, $database_name = false) {
            if(!self::$_instance) { // If no instance then make one
                self::$_instance = new self($database_host, $database_user, $database_pass, $database_name);
            }
            return self::$_instance;
        }

        // Constructor
        private function __construct($database_host = false, $database_user = false, $database_pass = false, $database_name = false) {
            if (! $this->conn) {
                if ($this->_connection_pre_check($database_host, $database_user, $database_pass, $database_name)) {
                    $conn = new mysqli($this->database_host, $this->database_user, $this->database_pass, $this->database_name);
                    // Error handling
                    if($conn->connect_error) {
                        trigger_error("Failed to connect to MySQL: " . $conn->connect_error,
                        E_USER_ERROR);
                    } else {
                        $this->conn = $conn;
                        if (! $this->init_database()) {
                            trigger_error("Failed to create database tables.",
                            E_USER_ERROR);
                        }      
                    }                                                                                                 
                } else {
                    trigger_error("Failed to connect to MySQL: Missing parameters.",
                    E_USER_ERROR);
                }
            }
        }

        private function _connection_pre_check($database_host = false, $database_user = false, $database_pass = false, $database_name = false) {
            $outcome = true;
            foreach (array('database_host' => $database_host, 'database_user' => $database_user, 'database_pass' => $database_pass, 'database_name' => $database_name) as $param => $value) {
                if ($value) {
                    $this->$param = $value;
                } elseif (! $value && defined(strtoupper($param))) {
                    $this->$param = constant(strtoupper($param));
                } else {
                    $outcome = false;
                }               
            }
            return $outcome;
        }

        public function init_database(){
            $database_name = $this->database_name;
            $outcome = true;
            /* check connection */
            if ($this->conn->connect_errno) {
                exit("Something is wrong with your database settings. Please ensure the database has been created and your connection parameters are valid.");
            }

            $check_readme_sql = "SELECT * FROM $database_name.README LIMIT 1;";
            $check_readme_res = $this->conn->query($check_readme_sql);

            if($check_readme_res === false) {
                $readme_query = "CREATE TABLE IF NOT EXISTS $database_name.README (
                ID int(11) AUTO_INCREMENT,
                PLUGIN varchar(255) NOT NULL,
                VERSION varchar(255) NOT NULL,
                README text,
                ACTIVE int(1) NOT NULL DEFAULT 0,
                PRIMARY KEY  (ID)
                )";
                $readme_res = $this->conn->query($readme_query);
                $outcome = $readme_res ? $outcome : false;
            }

            $check_members_sql = "SELECT * FROM $database_name.members LIMIT 1;";
            $check_members_res = $this->conn->query($check_members_sql);

            if ($check_members_res === false) {
                $members_query = "CREATE TABLE IF NOT EXISTS $database_name.members (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(30) NOT NULL,
                email VARCHAR(50) NOT NULL,
                password CHAR(128) NOT NULL,
                salt CHAR(128) NOT NULL 
                ) ENGINE = InnoDB;
                ";
                $members_res = $this->conn->query($members_query);
                $outcome = $members_res ? $outcome : false;

                if ($members_res) {
                    $random_salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), TRUE));
                    $admin_pass = hash('sha512', hash('sha512', 'admin') . $random_salt);
                    $admin_email = rawurlencode('admin@example.com');
                    $admin_username = 'admin';
                    if ($admin_stmt = $this->conn->prepare("INSERT INTO $database_name.members (username, email, password, salt) VALUES (?, ?, ?, ?);")) {
                        $admin_stmt->bind_param('ssss', $admin_username, $admin_email, $admin_pass, $random_salt);
                        // Execute the prepared query.
                        $admin_res = $admin_stmt->execute();
                        $outcome = $admin_res ? $outcome : false;                        
                    } else {
                        $outcome = false;
                    }
                }
            } else {
                $check_admin_sql = "SELECT * FROM $database_name.members WHERE username = 'admin';";
                $check_admin_res = $this->conn->query($check_admin_sql);
                if ($check_admin_res && ($check_admin_res->num_rows == 0)) {
                    $random_salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), TRUE));
                    $admin_pass = hash('sha512', hash('sha512', 'admin') . $random_salt);
                    $admin_email = rawurlencode('admin@example.com');
                    $admin_username = 'admin';
                    if ($admin_stmt = $this->conn->prepare("INSERT INTO $database_name.members (username, email, password, salt) VALUES (?, ?, ?, ?);")) {
                        $admin_stmt->bind_param('ssss', $admin_username, $admin_email, $admin_pass, $random_salt);
                        // Execute the prepared query.
                        $admin_res = $admin_stmt->execute();
                        $outcome = $admin_res ? $outcome : false;                        
                    } else {
                        $outcome = false;
                    }
                }
            }

            $check_login_sql = "SELECT * FROM $database_name.login_attempts LIMIT 1;";
            $check_login_res = $this->conn->query($check_login_sql);

            if ($check_login_res === false) {
                $login_query = "CREATE TABLE IF NOT EXISTS $database_name.login_attempts (
                user_id INT(11) NOT NULL,
                time VARCHAR(30) NOT NULL
                ) ENGINE=InnoDB
                ";
                $login_res = $this->conn->query($login_query);
                $outcome = $login_res ? $outcome : false;
            }

            $svn_options_sql = "SELECT * FROM $database_name.svn_options LIMIT 1;";
            $svn_options_res = $this->conn->query($svn_options_sql);

            if ($svn_options_res === false) {
                $svn_query = "CREATE TABLE IF NOT EXISTS $database_name.svn_options (
                id int(11) AUTO_INCREMENT,
                option_name VARCHAR(30) NOT NULL,
                option_value VARCHAR(255) NOT NULL,
                PRIMARY KEY  (id)
                ) ENGINE=InnoDB;";
                $svn_res = $this->conn->query($svn_query);
                $outcome = $svn_res ? $outcome : false;
            }
            return $outcome;
        }

        public function get_svn_options() {
            $database_name = $this->database_name;
            $svn_options = false;
            $svn_options_sql = "SELECT * FROM $database_name.svn_options;";
            $svn_options_res = $this->conn->query($svn_options_sql);

            if ($svn_options_res && ($svn_options_res->num_rows >= 4)) {
                $svn_options = array();
                while ($svn_option = $svn_options_res->fetch_assoc()) {
                    $svn_options[$svn_option['option_name']] = rawurldecode($svn_option['option_value']);
                }
                /* free result set */
                $svn_options_res->free();
            }
            return $svn_options;
        }

        public function set_svn_options($svn_url, $svn_path_to_plugins, $svn_user = false, $svn_pass = false, $global_author = false, $remote_auth = false) {
            $success = true;
            $database_name = $this->database_name;
            $svn_options = array();
            foreach (array(
            'svn_url' => $svn_url, 
            'svn_path_to_plugins' => $svn_path_to_plugins, 
            'svn_user' => $svn_user, 
            'svn_pass' => $svn_pass, 
            'global_author' => $global_author,
            'remote_auth' => $remote_auth
            ) as $field => $value) {
                if ($field !== false) {
                    switch ($field) {
                        case 'svn_url' : {
                            $svn_options[$field] = rawurlencode(trailingslashit(trim($value)));
                            break;
                        }
                        case 'svn_path_to_plugins' : {
                            $svn_options[$field] = $this->conn->real_escape_string(rawurlencode(trim($value, "/\\")));
                            break;
                        }
                        default : {
                            $svn_options[$field] = $this->conn->real_escape_string($value);
                            break;    
                        }
                    }                    
                }                    
            }
            foreach ($svn_options as $field => $value) {
                $svn_options_sql = "SELECT * FROM $database_name.svn_options WHERE option_name = '$field';";
                $svn_options_res = $this->conn->query($svn_options_sql);
                if ($svn_options_res) {
                    if ($svn_options_res->num_rows > 0) {
                        $svn_option_sql = "UPDATE $database_name.svn_options SET option_value = '$value' WHERE option_name = '$field';";
                    } else {
                        $svn_option_sql = "INSERT INTO $database_name.svn_options (option_name, option_value) VALUES ('$field', '$value');";
                    }
                    $svn_option_res = $this->conn->query($svn_option_sql);
                    $success = $svn_option_res ? $success : false;
                }
            }
            return $success;
        }

        public function get_plugin_info_from_db($slug, $version) {
            $row = false;
            $database_name = $this->database_name;
            $check_sql = "SELECT * FROM $database_name.README WHERE PLUGIN LIKE '" . $this->conn->real_escape_string($slug) . "' AND VERSION LIKE '" . $this->conn->real_escape_string($version) . "';";
            $check_res = $this->conn->query($check_sql);
            if ($check_res && ($check_res->num_rows > 0)) {
                $row = $check_res->fetch_assoc();
            }
            $check_res->close();
            return $row;
        }

        public function set_plugin_info_in_db($slug, $version, $active=false, $readme=null) {
            $row = false;
            $database_name = $this->database_name;
            $check_sql = "SELECT * FROM $database_name.README WHERE PLUGIN LIKE '" . $this->conn->real_escape_string($slug) . "' AND VERSION LIKE '" . $this->conn->real_escape_string($version) . "';";
            $check_res = $this->conn->query($check_sql);
            if ($check_res && ($check_res->num_rows > 0)) {
                $sql = "UPDATE $database_name.README SET " . (($active !== false) ? "ACTIVE = $active, " : "") . "version = '" . $this->conn->real_escape_string($version) . "'" . ($readme ? ", README = '$readme'" : "") . " WHERE PLUGIN LIKE '" . $this->conn->real_escape_string($slug) . "' AND VERSION LIKE '" . $this->conn->real_escape_string($version) . "';";
            } else {
                $sql = "INSERT INTO $database_name.README (PLUGIN, VERSION, README" . (($active !== false) ? ", ACTIVE" : "") . ") VALUES ('" . $this->conn->real_escape_string($slug) . "', '" . $this->conn->real_escape_string($version) . "', '" . $this->conn->real_escape_string($readme) . "'" . (($active !== false) ? ", '" . $this->conn->real_escape_string($active) . "'" : "") . ");";
            }
            $result = $this->conn->query($sql);

            return $result;
        }

        // Get mysqli connection
        public function _mysqli() {
            return $this->conn;
        }

        // Magic method clone is empty to prevent duplication of connection
        private function __clone() { }

    }
    // Singleton Session Auth Class
    class sec_singleton {
        private static $_instance;
        private $login = false;
        private $mysqli;
        private $email;
        private $username;

        /*
        Get an instance of the Database
        @return Instance
        */
        public static function _getInstance() {
            if(!self::$_instance) { // If no instance then make one
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        // Constructor
        private function __construct() {
            $cpm_db = cpm_db::_getInstance();
            if ($cpm_db) {
                $this->mysqli = $cpm_db->_mysqli();
            } else {
                trigger_error("Failed to initialize MySQL class.",
                E_USER_ERROR);
            }
        }

        function _checkbrute($user_id) {
            // Get timestamp of current time 
            $now = time();

            // All login attempts are counted from the past 2 hours. 
            $valid_attempts = $now - (2 * 60 * 60);

            if ($stmt = $this->mysqli->prepare("SELECT time 
            FROM login_attempts <code><pre>
            WHERE user_id = ? 
            AND time > '$valid_attempts'")) {
                $stmt->bind_param('i', $user_id);

                // Execute the prepared query. 
                $stmt->execute();
                $stmt->store_result();

                // If there have been more than 5 failed logins 
                if ($stmt->num_rows > 5) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        function _login($email, $password, $force=false) {
            if ($this->login && (! $force)) {
                return $this->login;
            }
            // Using prepared statements means that SQL injection is not possible. 
            if ($stmt = $this->mysqli->prepare("SELECT id, username, password, salt 
            FROM members
            WHERE email = ?
            LIMIT 1")) {
                $stmt->bind_param('s', $email);  // Bind "$email" to parameter.
                $stmt->execute();    // Execute the prepared query.
                $stmt->store_result();

                // get variables from result.
                $stmt->bind_result($user_id, $username, $db_password, $salt);
                $stmt->fetch();

                // hash the password with the unique salt.
                $password = hash('sha512', $password . $salt);
                if ($stmt->num_rows == 1) {
                    // If the user exists we check if the account is locked
                    // from too many login attempts 

                    if ($this->_checkbrute($user_id) == true) {
                        // Account is locked 
                        // Send an email to user saying their account is locked
                        trigger_error("Your account has been locked due to too many unsuccessful login attempts.",
                        E_USER_ERROR);
                        return false;
                    } else {
                        // Check if the password in the database matches
                        // the password the user submitted.
                        if ($db_password == $password) {
                            // Password is correct!
                            // Get the user-agent string of the user.
                            $user_browser = $_SERVER['HTTP_USER_AGENT'];
                            // XSS protection as we might print this value
                            $user_id = preg_replace("/[^0-9]+/", "", $user_id);
                            $_SESSION['user_id'] = $user_id;
                            // XSS protection as we might print this value
                            $username = preg_replace("/[^a-zA-Z0-9_\-]+/", 
                            "", 
                            $username);
                            $_SESSION['username'] = $username;
                            $_SESSION['email'] = $email;
                            $_SESSION['login_string'] = hash('sha512', 
                            $password . $user_browser);
                            $this->email = $email;
                            $this->username = $username;
                            // Login successful.
                            $this->login = array('email' => $email, 'username' => $username);
                            return $this->login;
                        } else {
                            // Password is not correct
                            // We record this attempt in the database
                            $now = time();
                            $this->mysqli->query("INSERT INTO login_attempts(user_id, time)
                            VALUES ('$user_id', '$now')");
                            return false;
                        }
                    }
                } else {
                    // No user exists.
                    return false;
                }
            }
        }

        function _info() {
            if (! $this->login) {
                $this->login = $_SESSION;
            }
            return $this->login;
        }

        function _logout($redirect=false) {
            $_SESSION = array();
            $this->login = false;

            // get session parameters 
            $params = session_get_cookie_params();

            // Delete the actual cookie. 
            setcookie(session_name(),
            '', time() - 42000, 
            $params["path"], 
            $params["domain"], 
            $params["secure"], 
            $params["httponly"]);

            // Destroy session 
            session_destroy();
            if ($redirect) {
                header('Location: ' . $redirect);
                exit;
            }
        }

        // Magic method clone is empty to prevent duplication of connection
        private function __clone() { }

        // Get mysqli connection
        public function _mysqli() {
            return $this->mysqli;
        }
    }

    final class sec_session {

        private $secure;
        private $session_name = 'cpm_session_id';
        private $handler;
        private $mysqli;

        function __construct($secure=true) {
            $this->secure = $secure;
            $this->handler = sec_singleton::_getInstance();
            $this->mysqli = $this->handler->_mysqli();
        }

        function sec_session_start() {
            // This stops JavaScript being able to access the session id.
            $httponly = true;
            // Forces sessions to only use cookies.
            if (ini_set('session.use_only_cookies', 1) === FALSE) {
                header("Location: ./scripts/error.php?err=Could not initiate a safe session (ini_set)");
                exit();
            }
            // Gets current cookies params.
            $cookieParams = session_get_cookie_params();
            session_set_cookie_params($cookieParams["lifetime"],
            $cookieParams["path"], 
            $cookieParams["domain"], 
            $this->secure,
            $httponly);
            // Sets the session name to the one set above.
            session_name($this->session_name);
            session_start();            // Start the PHP session 
            session_regenerate_id();    // regenerated the session, delete the old one. 
        }

        function sec_htaccess($rel_path_to_plugin) {
            $success = false;
            $abs_path_to_plugin = $_SERVER['DOCUMENT_ROOT'] . $rel_path_to_plugin;
            if (is_dir($abs_path_to_plugin)) {
                $f_htaccess = dirname(__FILE__) . "/../../includes/_htaccess.txt";
                if (file_exists($f_htaccess)) {
                    $htaccess = file_get_contents($f_htaccess);
                    $htaccess = str_replace('{RELPATH}', trailingslashit($rel_path_to_plugin), $htaccess);
                    $success = file_put_contents(trailingslashit($abs_path_to_plugin) . ".htaccess", $htaccess, EXTR_OVERWRITE);
                }                                                                                  
            }
            return $success;
        }

        function sec_login_check() {
            // Check if all session variables are set 
            if (isset($_SESSION['user_id'], 
            $_SESSION['username'], 
            $_SESSION['login_string'])) {

                $user_id = $_SESSION['user_id'];
                $login_string = $_SESSION['login_string'];
                $username = $_SESSION['username'];

                // Get the user-agent string of the user.
                $user_browser = $_SERVER['HTTP_USER_AGENT'];

                if ($stmt = $this->mysqli->prepare("SELECT password 
                FROM members 
                WHERE id = ? LIMIT 1")) {
                    // Bind "$user_id" to parameter. 
                    $stmt->bind_param('i', $user_id);
                    $stmt->execute();   // Execute the prepared query.
                    $stmt->store_result();

                    if ($stmt->num_rows == 1) {
                        // If the user exists get variables from result.
                        $stmt->bind_result($password);
                        $stmt->fetch();
                        $login_check = hash('sha512', $password . $user_browser);

                        if ($login_check == $login_string) {
                            // Logged In!!!! 
                            return true;
                        } else {
                            // Not logged in 
                            return false;
                        }
                    } else {
                        // Not logged in 
                        return false;
                    }
                } else {
                    // Not logged in 
                    return false;
                }
            } else {
                // Not logged in 
                return false;
            }
        }

        function sec_login_info(){
            return $this->handler->_info();
        }

        function sec_login($email, $password, $force = false){
            $login_session = $this->handler->_login($email, $password, $force);
            return $login_session;
        }

        function sec_logout($redirect = false){
            $this->handler->_logout($redirect);
        }
    }

    class cpmException {
        // Redefine the exception so message isn't optional
        public function __construct($message, $redirect = false) {
            if ( ! headers_sent() && $redirect) {
                header('Location: ' . $redirect);
                exit;
            } else {
                throw new Exception($message);
            }
            return false;
        }
    }

?>

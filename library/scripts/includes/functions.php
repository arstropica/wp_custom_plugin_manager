<?php
    function cpm_db_prepare_string($str) {
        return rawurlencode(trim($str));
    }

    function sec_esc_url($url) {

        if ('' == $url) {
            return $url;
        }

        $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);

        $strip = array('%0d', '%0a', '%0D', '%0A');
        $url = (string) $url;

        $count = 1;
        while ($count) {
            $url = str_replace($strip, '', $url, $count);
        }

        $url = str_replace(';//', '://', $url);

        $url = htmlentities($url);

        $url = str_replace('&amp;', '&#038;', $url);
        $url = str_replace("'", '&#039;', $url);

        if ($url[0] !== '/') {
            // We're only interested in relative links from $_SERVER['PHP_SELF']
            return '';
        } else {
            return $url;
        }
    }

    function wcr_get_file_data( $file, $default_headers = false, $mode = 'file') {
        if (! $default_headers) $default_headers = array
            (
            'Name' => 'Plugin Name',
            'PluginURI' => 'Plugin URI',
            'Version' => 'Version',
            'Description' => 'Description',
            'Author' => 'Author',
            'AuthorURI' => 'Author URI',
            'TextDomain' => 'Text Domain',
            'DomainPath' => 'Domain Path',
            'Network' => 'Network',
            // Site Wide Only is deprecated in favor of Network.
            '_sitewide' => 'Site Wide Only',
            );
        if ($mode == 'file') {
            // We don't need to write to the file, so just open for reading.
            $fp = fopen( $file, 'r' );

            // Pull only the first 8kiB of the file in.
            $file_data = fread( $fp, 8192 );

            // PHP will close file handle, but we are good citizens.
            fclose( $fp );       
        } else {
            $file_data = $file;
        }

        // Make sure we catch CR-only line endings.
        $file_data = str_replace( "\r", "\n", $file_data );

        $all_headers = $default_headers;

        foreach ( $all_headers as $field => $regex ) {
            if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] )
                $all_headers[ $field ] = _cleanup_header_comment( $match[1] );
            else
                $all_headers[ $field ] = '';
        }

        return array_filter($all_headers);

    }

    if (! function_exists('_cleanup_header_comment')) :
        function _cleanup_header_comment($str) {
            return trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $str));
        }
        endif;

    if (! function_exists('trailingslashit')) :
        function trailingslashit($string) {
            return untrailingslashit($string) . '/';
        }
        endif;

    if (! function_exists('untrailingslashit')) :
        function untrailingslashit($string) {
            return rtrim($string, '/');
        }
        endif;

    if (! function_exists('apply_filters')) :
        function apply_filters($tag, $value){
            return $value;
        }
        endif;

    if (! function_exists('get_option')) :
        function get_option($option){
            return false;
        }
        endif;

    if (! function_exists('wp_kses')) :
        function wp_kses($string){
            return $string;
        }
        endif;

    if (! function_exists('wp_load_alloptions')) :
        function wp_load_alloptions() {
            return false;
        }
        endif;

    if (! function_exists('wp_kses_normalize_entities')) :
        function wp_kses_normalize_entities($string) {
            # Disarm all entities by converting & to &amp;

            $string = str_replace('&', '&amp;', $string);

            # Change back the allowed entities in our entity whitelist

            $string = preg_replace_callback('/&amp;([A-Za-z]{2,8}[0-9]{0,2});/', 'wp_kses_named_entities', $string);
            $string = preg_replace_callback('/&amp;#(0*[0-9]{1,7});/', 'wp_kses_normalize_entities2', $string);
            $string = preg_replace_callback('/&amp;#[Xx](0*[0-9A-Fa-f]{1,6});/', 'wp_kses_normalize_entities3', $string);

            return $string;
        }
        endif;

    if (! function_exists('wp_kses_named_entities')) :
        function wp_kses_named_entities($matches) {
            global $allowedentitynames;

            if ( empty($matches[1]) )
                return '';

            $i = $matches[1];
            return ( ( ! in_array($i, $allowedentitynames) ) ? "&amp;$i;" : "&$i;" );
        }
        endif;

    if (! function_exists('wp_kses_normalize_entities2')) :
        function wp_kses_normalize_entities2($matches) {
            if ( empty($matches[1]) )
                return '';

            $i = $matches[1];
            if (valid_unicode($i)) {
                $i = str_pad(ltrim($i,'0'), 3, '0', STR_PAD_LEFT);
                $i = "&#$i;";
            } else {
                $i = "&amp;#$i;";
            }

            return $i;
        }
        endif;

    if (! function_exists('wp_kses_normalize_entities3')) :
        function wp_kses_normalize_entities3($matches) {
            if ( empty($matches[1]) )
                return '';

            $hexchars = $matches[1];
            return ( ( ! valid_unicode(hexdec($hexchars)) ) ? "&amp;#x$hexchars;" : '&#x'.ltrim($hexchars,'0').';' );
        }
        endif;

    if (! function_exists('wp_allowed_protocols')) :
        function wp_allowed_protocols() {
            static $protocols;

            if ( empty( $protocols ) ) {
                $protocols = array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn', 'tel', 'fax', 'xmpp' );
                $protocols = apply_filters( 'kses_allowed_protocols', $protocols );
            }

            return $protocols;
        }
        endif;

    if (!function_exists('http_response_code')) :
        function http_response_code($code = NULL) {

            if ($code !== NULL) {

                switch ($code) {
                    case 100: $text = 'Continue'; break;
                    case 101: $text = 'Switching Protocols'; break;
                    case 200: $text = 'OK'; break;
                    case 201: $text = 'Created'; break;
                    case 202: $text = 'Accepted'; break;
                    case 203: $text = 'Non-Authoritative Information'; break;
                    case 204: $text = 'No Content'; break;
                    case 205: $text = 'Reset Content'; break;
                    case 206: $text = 'Partial Content'; break;
                    case 300: $text = 'Multiple Choices'; break;
                    case 301: $text = 'Moved Permanently'; break;
                    case 302: $text = 'Moved Temporarily'; break;
                    case 303: $text = 'See Other'; break;
                    case 304: $text = 'Not Modified'; break;
                    case 305: $text = 'Use Proxy'; break;
                    case 400: $text = 'Bad Request'; break;
                    case 401: $text = 'Unauthorized'; break;
                    case 402: $text = 'Payment Required'; break;
                    case 403: $text = 'Forbidden'; break;
                    case 404: $text = 'Not Found'; break;
                    case 405: $text = 'Method Not Allowed'; break;
                    case 406: $text = 'Not Acceptable'; break;
                    case 407: $text = 'Proxy Authentication Required'; break;
                    case 408: $text = 'Request Time-out'; break;
                    case 409: $text = 'Conflict'; break;
                    case 410: $text = 'Gone'; break;
                    case 411: $text = 'Length Required'; break;
                    case 412: $text = 'Precondition Failed'; break;
                    case 413: $text = 'Request Entity Too Large'; break;
                    case 414: $text = 'Request-URI Too Large'; break;
                    case 415: $text = 'Unsupported Media Type'; break;
                    case 500: $text = 'Internal Server Error'; break;
                    case 501: $text = 'Not Implemented'; break;
                    case 502: $text = 'Bad Gateway'; break;
                    case 503: $text = 'Service Unavailable'; break;
                    case 504: $text = 'Gateway Time-out'; break;
                    case 505: $text = 'HTTP Version not supported'; break;
                    default:
                        exit('Unknown http status code "' . htmlentities($code) . '"');
                        break;
                }

                $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

                header($protocol . ' ' . $code . ' ' . $text);

                $GLOBALS['http_response_code'] = $code;

            } else {

                $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);

            }

            return $code;

        }
        endif;
?>

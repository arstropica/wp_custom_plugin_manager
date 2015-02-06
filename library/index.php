<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (isset($_GET['redir'])) {
    $file = reset(explode('?', $_GET['redir']));
    $orig = dirname(__FILE__) . DIRECTORY_SEPARATOR . $file;
    $redir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR .  $file;
    $_include = false;
    if (file_exists($file)) {
        $_include = $file;
    } elseif (file_exists($orig)) {
        $_include = $orig;
    } elseif (file_exists($redir)) {
        $_include = $redir;
    } else {
        $_include = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . '404.php';
    }
    $ext = pathinfo($_include, PATHINFO_EXTENSION);
    $mime_type = false;
    switch ($ext) {
        case 'css':
            $mime_type = 'text/css';
            header('Content-type: ' . $mime_type);            
            break;
        case 'js':
            $mime_type = 'application/javascript';
            header('Content-type: ' . $mime_type);            
        default:
            break;
    }
    include($_include);
} else {
    include_once('scripts/manager.php');
}

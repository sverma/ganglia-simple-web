<?php 
include_once( "../open_lib/class.TemplatePower.inc.php" );
session_start() ; // Create a new session 
$session_valid = false ;

if ( isset($_COOKIE["USER_AUTH_TOKEN_KEY"]) ) {
    // I got a USER_AUTH_TOKEN_KEY Cookie so either request is legitimate or forged
    // Check if i have session's key "auth_key"
    if ( isset ( $_SESSION["auth_token"] ) ) {
        // Check if they match 
        if ( $_SESSION["auth_token"] == $_COOKIE["USER_AUTH_TOKEN_KEY"] ) {
            // Request doesn't need password  login 
            $session_valid = true ;
        }
    }
}
if ( ! $session_valid ) { 
    $panel_login = new TemplatePower("../templates/default/login.tpl") ;
    $panel_login->prepare() ;
    $panel_login->printToScreen() ;
} else { 
    $url = 'http://' . $_SERVER['HTTP_HOST'] . "/index.php" ; 
    header("Location: $url");
    exit () ; 
}
?>

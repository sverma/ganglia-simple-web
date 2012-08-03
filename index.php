<?php
//Main index page , generated via index.tpl .
include("global_conf.php") ; // mandatory to include in all php files 
include_once( "open_lib/class.TemplatePower.inc.php" );
include_once( "lib/ganglia_config.php" );
$config_ob = new ganglia_config() ; 
$config = $config_ob->all_config() ; 

/**
 * Below code is for authetication mechanism : Its my first time so bare the consequences , Its advisable to leave this code aside for a while  
 */
#include_once("lib/validate_session.php") ; 

$name = "" ; //If we are going to authenticate user , set the username here 
// Get the details from the session or just use Guest 
if ( isset( $_SESSION ) ) { 
    $firstname = $_SESSION["firstName"] ; 
    $lastname = $_SESSION["lastName"] ; 
    global $name ; 
    $name  = $firstname . " " . $lastname ; 
} else { 
    global $name ;
    $name = "Guest" ; 
}
$panel_login = new TemplatePower("templates/default/index.tpl") ;
$panel_login->prepare() ;
$panel_login->assign( "user" , $name ) ; 
$panel_login->assign( "bug_email" , $config["common"]["bug_list"]) ; 
$panel_login->printToScreen() ;
?>

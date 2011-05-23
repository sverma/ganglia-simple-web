<? 
//Main index page , generated via index.tpl . 

include_once( "open_lib/class.TemplatePower.inc.php" );
#include_once("lib/validate_session.php") ; 
$firstname = $_SESSION["firstName"] ; 
$lastname = $_SESSION["lastName"] ; 
$name = $firstname . " " . $lastname ; 
//$panel_head = new TemplatePower("templates/default/header.tpl") ;
//$panel_head->prepare() ; 
#$panel_head->printToScreen() ; 
$panel_login = new TemplatePower("templates/default/index_tmp.tpl") ;
$panel_login->prepare() ;
$panel_login->assign( "user" , $name ) ; 
$panel_login->printToScreen() ;
?>

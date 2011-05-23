<? 
include_once("validate_session.php") ;
echo "<HR> <p>  TEsting Cookies and SEssions ... Cya </p> <HR>" ;
session_start() ; 
echo "<HR> <p> sessions </p> <p> " . print_r($_SESSION) ; 
echo "<HR> <p> cookies </p> <p> " . print_r($_COOKIE) ; 
echo "<a href='destroy.php' > Destroy the session </a>" ; 
?>

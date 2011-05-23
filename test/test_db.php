<? 
include ("../lib/update_user.php") ; 

$email = "saurabh.ve@directi.com" ; 
$record = new ganglia_mysql_interface() ; 
$record->add_project ( $email , "blah" ) ; 



?>

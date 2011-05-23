<?php 
function error_handler($errno, $errstr, $errfile, $errline)
{
    throw new Exception($errstr, $errno);
}
//set_error_handler('error_handler');
    try{
    $handle = file_get_contents("somefile.txt");
    throw new Exception ("new exception to test " ) ; 
    }
catch(Exception $ex)
{
    error_log( "Something went wrong" . $ex->getmessage() )  ;
}
 
?>

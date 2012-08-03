<?php
ini_set( 'display_errors' , 'On' ) ; 
include("../lib/get_data_curl.php") ; 
include("../lib/update_user.php") ; 
//get the posted values
$email_pattern = '/^[^@\s<&>]+@([-a-z0-9]+\.)+[a-z]{2,}$/i';
$clean = array() ; 
if (preg_match($email_pattern, $_POST['user_name'])) 
{ 
    $clean['username'] = $_POST['user_name']; 
    $clean['password'] = $_POST['password'];
    if ( ! preg_match("/.+\@directi.com$/" , $clean['username'])) { 
        echo $_POST['user_name'] . " dont a valid user name for Directi.com domain" ;
        exit() ; 
    }
} else { 
    echo $_POST['user_name'] ; 
    echo "username not entered as a email address " ; 
    return ; 
}
$user_name=$clean['username'] ; 
$pass=$clean['password'] ; 
//now validating the username and password
$auth_url= "http://user.api.pw/auth/authenticate.json?appkey=testkey.pw&username=$user_name&password=$pass"; 
$curl_init = new get_data_curl() ; 
$response = $curl_init->get_data( $auth_url ) ; 
$ret_json = json_decode($response , true) ;
//print_r($ret_json) ; 
//if username exists
if ( preg_match( "/success/i" , $ret_json["status"] ) ) { 
    session_start() ; 
    setcookie ( "USER_AUTH_TOKEN_KEY" , $ret_json["data"]["auth_token"] , time() + 72000 , "/" ) ; 
    // Change the auth Token in sessions 
    $_SESSION["auth_token"] = $ret_json["data"]["auth_token"] ; 
    $_SESSION["firstName"] = $ret_json["data"]["user"]["firstName"] ;
    $_SESSION["lastName"] = $ret_json["data"]["user"]["lastName"];
    $_SESSION["emailAddresses"] = $ret_json["data"]["user"]["emailAddresses"];
    $mysql = new ganglia_mysql_interface () ; 
    $_SESSION["Name"] = $_SESSION['firstName'] . $_SESSION['lastName'] ; 
    $mysql->add_user($_SESSION["Name"] , $_SESSION["emailAddresses"][0] ) ; 
    echo "yes" ;
}else { 
    echo "no";
}
?>

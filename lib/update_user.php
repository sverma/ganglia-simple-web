<?php 
include ( "ganglia_config.php") ; 
/* 
    Class that is interface for mysql operation . 
    It inherits mysql configuration from ganglia configuration class . 
    Usage : 
        $mysql = new mysql_interface ( ) ;
        $mysql->get_result( $query ) ; 
    
    Functions : 
        A. $result = get_result ( $query ) ; 
        it sends the $query query to mysql connection and fetches all the rows of fetched query result and sends them as numerical index array with each index as numeric and associative array , it can become a big array so use it with precaution 
        
        B.) add_user( $name , $email ) 
        It will update the ganglia databse with the user if the user has logged in for first time 
        
        C.) add_project ( $email , $project ) 
            it will update the csv list of project a user is associated to with the new project 
            
*/
class ganglia_mysql_interface { 
    private $dbname ; 
    private $dbuser ; 
    private $password; 
    private $server ; 
    public $resource ; 
    
    public function __construct ( ) { 
        $config = new ganglia_config () ; 
        $mysql_config = $config->mysql_config() ; 
        $this->server = $mysql_config["hostname"] ; 
        $this->dbuser = $mysql_config["user"] ; 
        $this->password = $mysql_config["password"] ; 
        $this->resource = mysql_connect ( $this->server, $this->dbuser, $this->password ) ; 
        $this->dbname = $mysql_config["database"] ;
        mysql_select_db(  $this->dbname , $this->resource) ; 
        return $this->resource ; 
    } 
    public function get_result ( $query ) { 
        $result = mysql_query($query , $this->resource ) ; 
        $all_result = array()  ; 
        while ( $row = mysql_fetch_array ( $result , MYSQL_BOTH ) ) { 
            array_push ( $all_result , $row ) ; 
        }
        return $all_result ; 
    }   
    public function add_user ( $name , $email ) { 
        $query = "insert into users ( name , email ) values ( \"$name\" , \"$email\" ) ; " ; 
        $result = mysql_query($query , $this->resource ) ;
        return ; 
    } 
    public function add_project ( $email , $project ) { 
        $query = "select projects from users where email=\"$email\"";
        $current_projects = $this->get_result( $query ) ;
        $new_projects = "" ; 
        if  ( $current_projects[0]["projects"] != NULL ) {  
            $new_projects =  $this->extend_csv($current_projects[0]["projects"] , $project ) ; 
        } else {
            $new_projects =  $project ; 
        } 
        $query = "update users set projects=\"$new_projects\" ";
        $result = mysql_query($query , $this->resource ) ;
        return ; 
    } 
    public function extend_csv ( $data , $value ) { // function that expects a comma seprated string and push a new value aand return a new string 
        $current = explode(',' , $data  ) ; 
        if ( ! in_array($value , $current ) )  { 
            array_push ( $current , $value ) ;      
        }
        $new = implode(',' , $current ) ; 
        return $new ; 
    } 
}
?>

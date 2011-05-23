<?
/* Class that parses configuration of ganglia web interface 
   Usage :
        $config = new ganglia_config() ;
        $mysql_config = $config->mysql_config ;         
    
   Functions : 
        A.) all_config() 
            Returns a associative array of all config of  ganglia ini config file 
        B.) mysql_config() 
            Returns a associate array of mysql configuration from the ganglia ini cofig file 
        C.) global_config() 
            Returns a associate array of the global configs 
        D.) config_loc( $config_file_loc ) 
            Expects a string and set it to the ganglia config file 
            Defaults to conf/conf.ini
 */
include_once("global_conf.php") ; 
class ganglia_config { 
     
    public  $config_file_loc ; // Config file location 
    private $ini_array ;  
    
    public function __construct ( ) { 
        $this->config_file_loc  = __ROOT__ . "/conf/conf.ini" ; 
        $this->ini_array = array() ; 
        $this->ini_array = parse_ini_file($this->config_file_loc, true);
    } 
    
    public function all_config () { 
        return $this->ini_array ; 
    } 
    
    public function global_config() { 
        return $GLOBALS ; 
    }
    
    public function mysql_config() { 
        return $this->ini_array["mysql"] ; 
    }
    
    public function config_loc ( $config_loc ) { 
        $this->config_file_loc = $config_loc ; 
    } 
}
?>

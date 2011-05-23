<?php
class gmetad_conf  { 
    protected $gmetad_ip ; 
    protected $gmetad_port ; 
    public function __construct () { 
        $this->gmetad_ip = "localhost" ;
        $this->gmetad_port  = "8651" ; 
    }
    public function set_gmetad_conf  ($ip , $port) { 
        $this->gmetad_ip = $ip ; 
        $this->gmetad_port = $port ; 
    }
        
} 

/**
 * @class parse_xml 
 * 
 * @uses gmetad_conf
 * @package 
 * @version $id$
 * @copyright Directi pvt ltd
 * @author Saurabh verma <saurabhverma@ymail.com 
 * @license php 5.2.13 , Zend Engine v2.2.0
 * 
 *
 */
class parse_xml extends gmetad_conf { 
    
    private $errstr ; 
    private $errno ; 
    private $timeout; 
    private $parser ;
    private $xml_ob ; 
    protected $grids ; 
    protected $servers ; 
    protected $clusters; 
    protected $metrics; 
    protected $metrics_group;
    private $parse_err ; 
    public $debug; 
    
    public function __construct() {
        parent::__construct() ; 
        $this->errno = "" ;
        $this->errstr = "" ; 
        $this->timeout = "10" ; 
        $xml_string = "";
        $this->grids = array() ; 
        $this->servers = array() ; 
        $this->clusters = array() ; 
        $this->metrics = array() ; 
        $this->metrics_group = array() ; 
        $this->metrics_group["non_indexed"] = array() ; 
        $this->metrics_group["indexed"] = array() ; 
        $fp = fsockopen( "$this->gmetad_ip" , "$this->gmetad_port" , $this->errno, $this->errstr, $this->timeout);
        $full_data = "" ; 
        while ( !feof($fp)) { 
            $data = fread($fp, 16384); 
             $full_data .= $data ;
        }
        $this->xml_ob =  new SimpleXMLElement ( $full_data ) ;
    }
    public function debug_host ( $hostname ) { // debugs a host level details 
        if ( $hostname ) { 
            $this->debug = array() ; 
            $this->debug["host"] = "$hostname" ; 
        } 
    } 
    /* Main function for parsing XML logic , Using SimpleXML parsing and iterating over the XML object */
    public function parse () { 
        $grid_name = $this->xml_ob->GRID["NAME"] ;
        $this->grids["$grid_name"]["clusters"] = array() ;
        $this->grids["$grid_name"]["servers"] = array() ;
        $this->grids["$grid_name"]["metrics"] = array() ;
        $this->grids["$grid_name"]["metrics_group"] = array() ;
        foreach ( $this->xml_ob->GRID->CLUSTER as $cluster ) {
            $cluster_name = (string) $cluster["NAME"] ;
            array_push($this->grids["$grid_name"]["clusters"] , (string) $cluster["NAME"]) ;
            if ( ! array_key_exists ( "$cluster" , $this->clusters ) )  {
                $this->clusters["$cluster_name"] = array() ;
            }
            if ( ! array_key_exists ( "servers" , $this->clusters["$cluster_name"] ) )  {
                $this->clusters["$cluster_name"]["servers"] = array() ; 
            }
            foreach ( $cluster->HOST as $host ) {
                $server_name = (string) $host["NAME"] ;
                array_push($this->grids["$grid_name"]["servers"] , (string) $host["NAME"] ) ;
                array_push($this->clusters["$cluster_name"]["servers"] , $server_name ) ;
                if ( ! array_key_exists("$server_name" , $this->servers ) ) {
                    $this->servers["$server_name"] = array() ;
                    $this->servers["$server_name"]["non_indexed_metrics"] = array() ; 
                    $this->servers["$server_name"]["indexed_metrics"] = array() ; 
                }
                if ( $host->METRIC ) {
                    foreach ( $host->METRIC as $metric ) {
                        $indexed = false ; 
                        // First check whether metric is a indexed metric 
                        // Following a convention of <metric_group>.<index>.<metric>
                        $metric_name = (string) $metric["NAME"] ;
                        if ( ( isset ( $this->debug ) ) && ( $this->debug["host"] == "$server_name" ) )  { 
                            echo " Metric to be processed for hostname $server_name is : $metric_name <hr>" ; 
                        } 
            
                        $index = "" ;
                        if ( preg_match(  "/(.+?)\.(.+?)\.(.+?)$/" , $metric_name , $matches ) )  { //Found a metric with index 
                            $indexed = true ; 
                            $metric_name = $matches[3] ; 
                            $index = $matches[2] ;
                            if ( ! array_key_exists ( "$metric_name" , $this->servers["$server_name"]["indexed_metrics"] ) ) { 
                                $this->servers["$server_name"]["indexed_metrics"]["$metric_name"] = array ()   ;
                            }
                            array_push( $this->servers["$server_name"]["indexed_metrics"]["$metric_name"] , $index ) ; 
                            if ( (  isset ( $this->debug ) ) && ( $this->debug["host"] == $server_name ) )  { 
                                echo "<hr>Found $metric_name is a indexed metric with index as $index " ;
                                print_r( $this->servers["$server_name"]["indexed_metrics"]["$metric_name"] ) ; 
                                echo "<hr>"; 
                            } 
                            
                        } else {    
                            array_push( $this->servers["$server_name"]["non_indexed_metrics"] , $metric_name ) ; 
                        } 
                        if ( ! array_key_exists("$metric_name" , $this->metrics ))  {
                            $this->metrics["$metric_name"] = array() ;
                        }
                        if ( ! array_key_exists("metrics"  , $this->clusters["$cluster_name"] ) ) { 
                            $this->clusters["$cluster_name"]["metrics"]  = array() ; 
                        }
                        if ( ! in_array ( $metric_name , $this->clusters["$cluster_name"]["metrics"] )) { 
                            array_push( $this->clusters["$cluster_name"]["metrics"] ,  (string) $metric_name ) ; 
                        }
                        if (  (string) $metric["UNITS"]  ) {  
                            $this->metrics["$metric_name"]["units"] = (string) $metric["UNITS"] ;
                        } 
                        foreach( $metric->EXTRA_DATA->EXTRA_ELEMENT as $metric_data ) {
                            $name = $metric_data["NAME"] ;
                            $val = $metric_data["VAL"]  ;
                            $this->metrics["$metric_name"]["$name"] = $val ;
                            if ( $name == "GROUP" ) {
                                $metrics_group_key ; 
                                if ( $indexed ) {
                                    $metrics_group_key =& $this->metrics_group["indexed"] ; 
                                } else {
                                    $metrics_group_key = &$this->metrics_group["non_indexed"] ;
                                } 
                                if ( ! array_key_exists("$val" , $metrics_group_key )  )  {
                                    $metrics_group_key["$val"] = array() ;
                                }
                                if ( ! in_array ( "$metric_name" , $metrics_group_key["$val"]  )  )  {
                                    array_push ( $metrics_group_key["$val"]   , "$metric_name" ) ;
                                }
                                if ( ! array_key_exists("$cluster_name" , $this->grids["$grid_name"]["metrics_group"] ) ) { 
                                    $this->grids["$grid_name"]["metrics_group"]["$cluster_name"] = array() ; 
                                } 
                                if ( ! in_array ( "$val" , $this->grids["$grid_name"]["metrics_group"]["$cluster_name"] ) )  { 
                                    array_push ( $this->grids["$grid_name"]["metrics_group"]["$cluster_name"] , "$val" ) ; 
                                }
                            }
                        }
                        if ( ! array_key_exists("servers" , $this->metrics["$metric_name"] ))  {
                            $this->metrics["$metric_name"]["servers"] = array() ;
                        }
                        array_push ( $this->metrics["$metric_name"]["servers"] , $server_name ) ;
                        $this->metrics["$metric_name"]["indexed"] = $indexed ; 
                    }
                }
            }
        }
    }
    public function debug ( ) { 
        print_r($this->grids) ; 
    } 
    private function check_key ( $array , $key ) { 
        if ( ! array_key_exists ( "$key" , $array ) ) { 
            throw new Exception ( "$key not defined in the xml" ) ;
        }
        return  ; 
    }
    public function get_clusters_from_grid ( $grid ) { 
        try { 
            $this->check_key($this->grids , $grid ) ; 
        }
        catch ( Exception $e ) { 
            $this->parse_err = 'Exception caught: ' . $e->getMessage() ; 
            return(-1) ; 
        }
        return ( $this->grids["$grid"]["clusters"] ) ; 
    }
    protected function check_scalar_or_array ( $in_var )  { 
        $var = array() ; 
        if ( ! is_array($in_var ) ) { 
            array_push ( $var , $in_var ) ; 
        }else { 
            $var = $in_var ; 
        }
        return $var ; 
    }
    public function get_servers_from_clusters ( $clusters ) { 
        $return_ob = array()  ; 
        $l_clusters = $this->check_scalar_or_array($clusters) ; 
        foreach ( $l_clusters as $cluster ) { 
            try { 
                $this->check_key($this->clusters, $cluster) ; 
            }
            catch ( Exception $e ) { 
                $this->parse_err = 'Exception caught: ' . $e->getMessage()  ;
                return(-1) ; 
            }
            $return_ob["$cluster"] = $this->clusters["$cluster"]["servers"]; 
        }
        return ( $return_ob ) ; 
    
    }
    
    public function get_metrics_from_servers ( $servers ) {
        $return_ob = array()  ;
        $l_servers = $this->check_scalar_or_array($servers) ; 
        foreach ( $l_servers as $server ) { 
            try { 
                $this->check_key($this->servers , $server ) ; 
            } 
            catch ( Exception  $e ) {
                $this->parse_err = 'Exception caught: ' . $e->getMessage()  ;
                return (-1 ) ; 
            }
            $return_ob["$server"] = $this->servers["$server"] ; 
        }
        return ( $return_ob ) ; 
    }
        
    public function get_servers_from_metrics ( $metrics ) { 
        $return_ob = array()  ;
        $l_metrics = $this->check_scalar_or_array( $metrics ) ; 
        foreach ( $l_metrics as $metric ) { 
           try {
                $this->check_key($this->metrics , $metric ) ;
            }
            catch ( Exception  $e ) {
                $this->parse_err = 'Exception caught: ' . $e->getMessage() ;
                return (-1 ) ;
            }
            $return_ob["$metric"] = $this->metrics["$metric"]["servers"] ;
        }
        return ( $return_ob ) ;
    }
    
    public function get_metrics_from_clusters ( $cluster ) { 
        $return_ob = array() ; 
        $l_clusters = $this->check_scalar_or_array( $cluster ) ; 
        foreach ( $l_clusters as $cluster ) { 
            try {
                $this->check_key($this->clusters , $cluster ) ;
            } 
            catch ( Exception  $e ) {
                $this->parse_err = 'Exception caught: ' . $e->getMessage() ;
                return (-1 ) ;
            }
            $return_ob["$cluster"] = $this->clusters["$cluster"]["metrics"] ;
        }
        return ( $return_ob ) ;
        
    }

    public function get_all_metrics ( ) { 
        return ( array_keys($this->metrics) ) ; 
    }
    
    public function get_all_servers () { 
        return ( array_keys ( $this->servers ) ) ; 
    }
    
    public function get_all_clusters () { 
        return ( array_keys ( $this->clusters ) ) ; 
    }
    
    public function get_grid_name () { 
        return ( array_keys ( $this->grids ) ) ; 
    }
    
    public function get_cluster_from_servername ( $servername ) { 
        $all_clusters = $this->get_all_clusters() ; 
        foreach ( $all_clusters as $cluster ) { 
            $all_servers = $this->get_servers_from_clusters("$cluster") ;   
            if ( in_array ( $servername , $all_servers["$cluster"] ) ) { 
                return ( $cluster ) ; 
            } 
        } 
        return ( -1)  ; 
    }
    public function getParseErr ( ) { 
        return ( $this->parse_err ) ; 
    } 
    
    public function get_all_metrics_group () { 
        $indexed = array_keys($this->metrics_group["indexed"])  ;  
        $non_indexed = array_keys($this->metrics_group["non_indexed"])  ;   
        $all = array_merge($indexed , $non_indexed ) ; 
        return ( $all  ) ; 
    }
    public function get_metrics_group_from_clusters ( $clusters ) { 
        $return_ob = array()  ;
        $grids = $this->get_grid_name() ; 
        $l_clusters = $this->check_scalar_or_array( $clusters ) ;
        foreach ( $grids as $grid_name ) { 
            foreach ( $l_clusters as $cluster ) {
                try { 
                    $this->check_key($this->grids["$grid_name"]["metrics_group"]  , $cluster ) ;
                }
                catch ( Exception $e ) { 
                    $this->parse_err = 'Exception caught: ' . $e->getMessage() ;
                    return (-1) ; 
                }
                $return_ob["$cluster"] = $this->grids["$grid_name"]["metrics_group"]["$cluster"] ;
            }
        }
        return ( $return_ob ) ; 
    }
    public function get_metrics_from_groups ( $groups ) { 
       $return_ob = array()  ;
       $l_groups = $this->check_scalar_or_array( $groups ) ;
        foreach ( $l_groups as $group ) {
           $all_metrics_groups = array_merge($this->metrics_group["indexed"]  ,  $this->metrics_group["non_indexed"]  ) ; 
           try {
                $this->check_key( $all_metrics_groups , $group) ;
            }
            catch ( Exception  $e ) {
                $this->parse_err = 'Exception caught: ' . $e->getMessage() . " metrics group: "  ;
                return (-1 ) ;
            }
            if ( array_key_exists($group , $this->metrics_group["indexed"] ))  { 
                $return_ob["$group"] = $this->metrics_group["indexed"]["$group"] ;
            } else { 
                $return_ob["$group"] = $this->metrics_group["non_indexed"]["$group"] ;
            }
        }
        return ( $return_ob ) ;
    }
    public function check_group_if_indexed ( $group ) { 
        if ( array_key_exists($group , $this->metrics_group["indexed"] ))  {
            return true ; 
        } else { 
            return false ; 
        }
    }
    public function get_metric_indexes_server ( $metric , $server ) { 
        if  ( isset($this->servers["$server"]["indexed_metrics"]["$metric"] ) ) { 
            return $this->servers["$server"]["indexed_metrics"]["$metric"] ; 
        } else { 
            try { 
                throw new Exception ( "$metric doesn't have index for $server or $metric not defined" ); 
            } 
            catch ( Exception $e  ) {   
                $this->parse_err = 'Exception caught: ' . $e->getMessage() ; 
            } 
            return -1 ; 
        } 
    }  
    public function get_metric_details ( $metrics ) {
        $return_ob = array()  ;
        $l_metrics = $this->check_scalar_or_array( $metrics ) ;
        foreach ( $l_metrics as $metric ) { 
            $return_ob["$metric"] = array () ; 
            if ( isset( $this->metrics["$metric"]["DESC"] ) ) { 
                $return_ob["$metric"]["DESC"] = $this->metrics["$metric"]["DESC"]; 
            } 
            if ( isset( $this->metrics["$metric"]["TITLE"] ) ) {
                $return_ob["$metric"]["TITLE"] = $this->metrics["$metric"]["TITLE"]; 
            } 
            if ( isset ( $this->metrics["$metric"]["units"] ) ) { 
                $return_ob["$metric"]["units"] = $this->metrics["$metric"]["units"];
            } 
        } 
        return ( $return_ob ) ; 
    } 
}
?>

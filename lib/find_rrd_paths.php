<?php 
/* Class to construct path to RRD files . 
Expects a array of metrics , servers ad clusters and return 


*/

include ("parse_xml.php") ;
// Simpel class to find RRD PATH for a metric 
class find_rrd_path { 
    protected $RRD_ROOT ; 
    protected $cluster ; 
    protected $server ; 
    protected $metric ;
    
    public function __construct () { 
        $this->RRD_ROOT = "/var/lib/ganglia/rrds" ; 
    }
    
    public function find_path () { 
        $path = "$this->RRD_ROOT" . "/$this->cluster" . "/$this->server" . "/$this->metric" . ".rrd" ; 
        return $path ; 
    }
    
    public function init ( $cluster , $server , $metric )  { 
        $this->cluster = $cluster ; 
        $this->server = $server ; 
        $this->metric = $metric ; 
    }
}

// Class to find all clustes , servers and metrics expected 
class all_metrics extends find_rrd_path { 
    protected $clusters ;
    protected $servers ;
    protected $metrics ;
    protected $xml_ob ;
    private $paths ;
    protected $global_cluster ; 
    
    public function __construct () { 
        parent::__construct() ;
        $this->clusters = array()  ; 
        $this->global_cluster = false ;
        $this->servers = array() ; 
        $this->metrics = array() ; 
        $this->paths = array () ; 
        $this->paths["metrics"] = array() ; 
    }
    // function to add metrics to be graphed 
    public function add_metric ($metric) { 
        if ( ! in_array( $metric , $this->metrics ) ) { 
            array_push ( $this->metrics , $metric ) ; 
        }
    } 
    
    public function add_cluster ( $cluster ) { 
        array_push ( $this->clusters , $cluster ) ; 
    } 
    
    public function add_server ( $server ) { 
        if ( ! in_array( $server , $this->servers ) ) {
            array_push ( $this->servers , $server ) ;
        } 
    } 

    public function set_global_cluster ( $cluster ) { 
        $this->global_cluster = "$cluster" ; 
    } 
    public function create_paths ( ) { 
        foreach ( $this->metrics as $metric ) { 
            $this->paths["$metric"] = array() ; 
            // We have a metric to graph 
            /*
                foreach ( $this->clusters as $cluster ) { 
                    $l_servers = $this->xml_ob->get_servers_from_clusters("$cluster") ;
                    foreach ( $l_servers["$cluster"] as $server ) { 
                        $this->init($cluster, $server , $metric) ; 
                        $path = $this->find_path() ; 
                        array_push($this->paths["$metric"] , $path) ; 
                    }
                }
            */
                foreach ( $this->servers as $server ) {             
                    $cluster = "" ; 
                    if (  $this->global_cluster  )  {
                        $cluster = $this->global_cluster ; 
                    } else { 
                        if ( ! isset ( $this->xml_ob ) ) { 
                            $this->xml_ob = new parse_xml() ; 
                            $this->xml_ob->parse() ; 
                        } 
                        $cluster = $this->xml_ob->get_cluster_from_servername("$server") ;
                    } 
                    $this->init($cluster, $server , $metric) ;
                    $path = $this->find_path() ;
                    array_push($this->paths["$metric"] , $path) ;
                }
            } 
        
        return ( $this->paths ) ; 
    }
}
?>

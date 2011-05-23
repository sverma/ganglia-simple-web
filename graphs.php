<?php
ini_set( 'display_errors' , 'On' ) ;
#include_once ("lib/validate_session.php")  ; 
include_once ( "lib/find_rrd_paths.php" ) ;
include_once ( "lib/rrdgraph_data.php" ) ;
$servers  = array() ;
if ( isset( $_GET["servers"] )  ) {
    $servers = explode ( ',' , $_GET["servers"] ) ;
} else { 
    $servers = array ( "outbound-us1.mailhostbox.com" ) ; 
}
$metrics = array() ; 
if ( isset( $_GET["metrics"] ) ) {
    $metrics = explode ( ',' , $_GET["metrics"] ) ;
} else { //error
    $metrics = array( "cpu_user" ) ; 
}
$cluster = "" ; 
if  ( isset( $_GET["cluster"] ) )  { 
    $cluster = $_GET["cluster"];
} 

/* Setting up defualt graph properties , should be moved from here to conf.php */
$graph_properties = array( 
    "width" => 200 , 
    "height" => 200 , 
    "end" => "now" , 
    "start" => -1 * 3660 , 
    "graph_type" => "average" , 
    "vertical-label" => "units" , 
) ; 
// default values 

$defaults = array ( 
    "graph_size" => array ( 
        "small" => "400X200" , "medium" => "500X400" , "large" => "600X500" ) , 
    "graph_interval" => array ( 
        "hour" => 3600 , "day" => 3600*24 , "week" => 3600*24*7 , "month" => 3600*24*7*4 , "year" => 3600*24*7*4*12 ) , 
) ; 
// Generate options query 
$config_ob = new ganglia_config() ;
$config = $config_ob->all_config() ;
$option = '';
foreach ( $config["graph_defaults"] as $name => $value ) {
    if ( array_key_exists($name , $defaults ) ) { 
        if ( $name == "graph_size" ) { 
            if ( preg_match ( "/^(\d+)X(\d+)$/" ,  $defaults[$name]["$_GET[$name]"]  , $matches ) )  { 
                $graph_properties["width"] = $matches[1] ; 
                $graph_properties["height"] = $matches[2] ; 
            } 
        } else if ( $name == "graph_interval" ) { 
                $graph_properties["start"] = -1 * $defaults[$name][$_GET["$name"]] ; 
        } 
    }  else if ( isset($_GET["$name"] ) ) { 
                $graph_properties["$name"] = $_GET["$name"];
    } else if ( isset ($defaults[$name] ) )  { 
                $graph_properties["$name"] = $defaults[$name]; 
    }  
}

$title = "" ; 
if ( ( isset($cluster) ) && ( ! preg_match ( "/all/" , $cluster ) ) )  { 
    $title .= "<$cluster>" ; 
} 

$all = new all_metrics() ;
if ( ! preg_match ( "/all/i"  , $cluster ) ) { 
    $all->set_global_cluster("$cluster") ; 
}
foreach ( $metrics as $metric ) { 
    $all->add_metric( $metric ) ; 
    foreach ( $servers as $server ) { 
        $all->add_server($server) ;
    }
}
$all_rrd_paths = $all->create_paths($all) ; 
$data_obj = new RRD_graph() ;
$data_obj->set_properties( $graph_properties ) ;
// If we are graphing single metric multiple servers and then color scheme should be constant 
if ( count($metrics) == 1 ) { 
    $data_obj->constant_color = true ; 
} 
foreach ( $metrics as $metric ) { 
    foreach ( $all_rrd_paths[$metric] as $path ) {
        $data_obj->add_ds("sum", $path , "AVERAGE") ;
    }
}
$data_obj->create_graph() ; 
$command_arg  = $data_obj->get_rrd_cmd_arg() ; 
$command = 'rrdtool graph - ' . $command_arg ; 

if ( ! isset($_GET["debug"]) )  { 
    //Make sure the image is not cached
    header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");   // Date in the past
    header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
    header ("Cache-Control: no-cache, must-revalidate");   // HTTP/1.1
    header ("Pragma: no-cache");                     // HTTP/1.0
    header ("Content-type: image/png");
    passthru($command) ; 
    //passthru("$command 1>/tmp/met.png") ; 
} else { 
    print_r($command) ; 
    echo ("<HR>") ; 
    echo ($command) ; 
    //passthru("echo $command") ; 
}
?>

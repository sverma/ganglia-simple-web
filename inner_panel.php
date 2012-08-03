<?php
//include ("lib/validate_session.php") ; 
include ("./lib/get_data_curl.php") ;
include_once( "open_lib/class.TemplatePower.inc.php" );
include ("lib/parse_xml.php") ;  
include ("lib/ganglia_config.php") ;  
#include_once ("lib/validate_session.php")  ;
// This PHP scripts user inner.tpl and creates DIVs pannel for metrics 
// First get the list of server names and metrics 
$servers  = array() ; 
if ( isset( $_GET["servers"] )  ) { 
    $servers = explode ( ',' , $_GET["servers"] ) ; 
} else { 
    // error 
} 

$metric_group = array() ; 

if ( isset( $_GET["metrics_group"] ) ) { 
    $metric_group = explode ( ',' , $_GET["metrics_group"] )  ; 
} else { 
    // error 
}

$cluster  ="" ; 
if ( isset( $_GET["cluster"] ) ) { 
    $cluster = $_GET["cluster"] ; 
}


$total_metrics  = array() ; 
$xml_ob = new parse_xml() ; 
$xml_ob->parse() ; 
// Generate options query 
$config_ob = new ganglia_config() ;
$config = $config_ob->all_config() ;
$option = '';



$panel_tpl = init_template("templates/default/inner_panel.tpl") ;
$indexed_panel_tpl = init_template("templates/default/indexed_panel.tpl") ;

foreach ( $metric_group  as $group ) { 
    $indexed= false ; 
    $ret_grp_metrics = $xml_ob->get_metrics_from_groups($group) ; 
    if ( $ret_grp_metrics == -1 ) {
        error_log($xml_ob->getParseErr() ) ; 
    } 
    $grp_metrics = $ret_grp_metrics[$group];
    $metric = "";
    if ( isset ( $_GET["metric"] ) && ! preg_match("/^all$/i" , $_GET["metric"] )  ) { // We got a single metric graph 
        $metric = $_GET["metric"] ; 
        $grp_metrics = array () ; 
        array_push ( $grp_metrics , $metric ) ; 
    } 
    if ( isset($conf["join"] ))  {  // if the graphs contains all the metircs in a single image 
    } else { 
        // create code for DIV elements
        foreach ( $grp_metrics as $metric ) { 
            // We got a single server graph  
            $details = $xml_ob->get_metric_details($metric ) ; 
            $vtitle = ""; $option=""; 
            foreach ( $config["graph_defaults"] as $name => $value ) {
                if ( isset ( $_GET[$name] ) ) {
                    $option .= '&' . $name . "=" . $_GET["$name"] ;
                } 
            }
            if ( ( isset( $details["$metric"]["units"] ) ) && ( ! preg_match("/^\s/", $details["$metric"]["units"])   )  ){ 
                $vtitle .= "&vertical-label=" . $details["$metric"]["units"] ;     
            }   else { 
                $vtitle .= "&vertical-label=" . "numbers" ; 
            } 
                $option .=  $vtitle; 
            $indexed = $xml_ob->check_group_if_indexed($group) ;
            if ( $indexed ) {  // We got a indexed group 
                                // Get the list of all indexed associated with this 
                // first find the super set of all indexes 
                $metric_indexes = array () ;
                foreach ( $servers as $server ) { 
                    $indexes_server = $xml_ob->get_metric_indexes_server(  $metric , $server ) ;    
                    if ( is_array($indexes_server ))  { 
                        foreach ( $indexes_server as $uniq_index ) { 
                            if ( ! isset($metric_indexes["$uniq_index"] ) )  {
                                $metric_indexes["$uniq_index"] = $server  ;
                            }else { 
                                $metric_indexes["$uniq_index"] .= ',' .$server ; 
                            }
                        }    
                    } 
                }  
                $indexed_panel_tpl->newBlock("group") ;
                $indexed_panel_tpl->assign("group" , $group ) ;
                $indexed_panel_tpl->assign("o_metric" , $metric ) ;
                foreach ( $metric_indexes as $index => $o_servers ) {
                    $o_metric = "$group" . ".$index" . ".$metric" ; 
                    $indexed_panel_tpl->newBlock("index") ;
                    $indexed_panel_tpl->assign("metric" , $o_metric ) ;
                    $indexed_panel_tpl->assign( "servers" , $o_servers ) ;
                    $indexed_panel_tpl->assign( "cluster" , $cluster ) ;
                    $indexed_panel_tpl->assign( "options" , $option ) ;
                }
            } else { 
                $panel_tpl->newBlock("metric") ;
                $panel_tpl->assign("metric" , $metric ) ;
                $panel_tpl->assign( "servers" , $_GET["servers"] ) ;
                $panel_tpl->assign( "cluster" , $cluster ) ;
                $panel_tpl->assign( "options" , $option ) ;
            }   
        } 
    }   
}

$panel_tpl->printToScreen() ;
$indexed_panel_tpl->printToScreen() ;


function init_template( $file ) {
    $tpl = new TemplatePower("$file") ;
    $tpl->prepare() ;
    return $tpl ;
}

?>

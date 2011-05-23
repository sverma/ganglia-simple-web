<?php 
include ("../lib/restutils.php") ;
include ("../lib/parse_xml.php") ; 
$data = RestUtils::processRequest();  
$ganglia = new parse_xml () ; 
$ganglia->parse() ; 
switch ( $data->getMethod()) { 
    case 'get': 
        $request_var = $data->getRequestvars() ; 
        break ; 
}
$LIST_ALL = "" ; 
if ( ! array_key_exists ( "list" , $request_var ) )  { 
    RestUtils::sendResponse("501" ) ;
} else { 
    if ( ( count ( array_keys ( $request_var ) ) == 1  ) || ( ( count ( array_keys ( $request_var ) ) == 2  ) && ( array_key_exists("method", $request_var ) ) ) ) { // It means user asked without a filter creteria 
        $LIST_ALL = true ; 
    }
}

switch ( $request_var["list"] ) { 
    case 'clusters' : 
        if ( $LIST_ALL == true ) { 
            $result = $ganglia->get_all_clusters() ; 
            $data->send_response(200,$result) ;
            break; 
        }
        if ( ! array_key_exists ( "grid" , $request_var ) )  {
            RestUtils::sendResponse ( "400" , "Should enter a grid to get clusters" ) ; 
        }else { 
            $result = $ganglia->get_clusters_from_grid($request_var["grid"]) ; 
            $data->send_response($result) ; 
        }
        break;
    case 'servers':
        if ( $LIST_ALL == true ) {
            $result = $ganglia->get_all_servers() ;
            $return_ob = array() ; 
            $return_ob["All"] = $result ; 
            $data->send_response(200,$return_ob) ;
            break;
        }
         
        if ( array_key_exists( "clusters" , $request_var ) ) {  
            $result = $ganglia->get_servers_from_clusters($request_var["clusters"]) ;
            if ( $result == -1 ) { 
                $result = array() ; 
                $result["error"] = $ganglia->getParseErr() ; 
                $data->send_response(501, $result) ; 
                break;
            }
            $data->send_response(200,$result) ;
        }else if ( array_key_exists( "metrics" , $request_var  ) ) { 
            $result = $ganglia->get_servers_from_metrics($request_var["metrics"]) ;
            if ( $result == -1 ) {
                $result = array() ;
                $result["error"] = $ganglia->getParseErr() ;
                $data->send_response(501, $result) ; 
                break;
            }
            $data->send_response(200,$result) ; 
        }else { 
            $result["error"] = "List of servers asked but without specifying the right creteria" ; 
            $data->send_response(501, $result) ;
            break ; 
        }
        break ; 
    case 'metrics': 
        if ( $LIST_ALL ) {
            $result = $ganglia->get_all_metrics() ;
            $return_ob = array() ; 
            $return_ob["All"] = $result ; 
            $data->send_response(200,$return_ob) ;
            break;
        }
        if ( array_key_exists( "servers" , $request_var ) ) {
            $result = $ganglia->get_metrics_from_servers($request_var["servers"] ) ; 
            if ( $result == -1 ) {
                $result = array() ;
                $result["error"] = $ganglia->getParseErr() ;
                $data->send_response(501, $result) ;
                break;
            }
            $data->send_response(200,$result) ;
        }else if ( array_key_exists( "metrics_grp" , $request_var ) ) { 
            $result = $ganglia->get_metrics_from_groups($request_var["metrics_grp"] ) ;
            if ( $result == -1 ) {
                $result = array() ;
                $result["error"] = $ganglia->getParseErr() ;
                $data->send_response(501, $result) ;
                break;
            }
            $data->send_response(200,$result) ;
            break;
        }else if ( array_key_exists( "clusters" , $request_var ) ) {
            $result = $ganglia->get_metrics_from_clusters($request_var["clusters"]) ;
            if ( $result == -1 ) {
                $result = array() ;
                $result["error"] = $ganglia->getParseErr() ;
                $data->send_response(501, $result) ;
                break;
            }
            $data->send_response(200,$result) ; 
        } else { 
            $result["error"] = "List of metricss asked but without specifying the right creteria" ;
            $data->send_response(501, $result) ;
            break ;
        }
    case 'metrics_grp': 
        if ( $LIST_ALL ) { 
            $result = $ganglia->get_all_metrics_group() ;
            $return_ob = array() ; 
            $return_ob["All"] = $result ; 
            $data->send_response(200,$return_ob) ;
            break; 
        }else if ( array_key_exists( "clusters" , $request_var ) ) {
            $result = $ganglia->get_metrics_group_from_clusters($request_var["clusters"]) ;
            if ( $result == -1 ) {
                $result = array() ;
                $result["error"] = $ganglia->getParseErr() ;
                $data->send_response(501, $result) ;
                break;
            }
            $data->send_response(200,$result) ; 
        } else { 
            $result["error"] = "List of metricss asked but without specifying the right creteria" ; 
            $data->send_response(501, $result) ;
            break ;
        }
}
    
?>

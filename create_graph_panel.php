<?php 
//    include_once("lib/validate_session.php") ; 
    include_once( "open_lib/class.TemplatePower.inc.php" );
    include ( "lib/parse_xml.php") ; 
    include( "lib/ganglia_config.php") ; 
    $xml_ob = new parse_xml () ; 
    $xml_ob->parse() ; 
    // Build a hash of query string , $_GET is not working with mutiple options 
    $a = explode ( '&'  , $_SERVER["QUERY_STRING"] ) ; 
    $query = array() ; 
    foreach ( $a as $q ) { 
        list($key, $value) = explode("=", $q);
        if ( array_key_exists( $key , $query ) ) { 
            array_push(  $query["$key"] , $value ) ; 
        } else { 
            $query["$key"] = array() ; 
            array_push(  $query["$key"] , $value ) ; 
        }
    }

    $config_ob = new ganglia_config() ; 
    $globals = $config_ob->global_config () ; 
    $API_URL = $_SERVER["SERVER_NAME"] .  "/" . $globals["api_url"] . "?"; 
    
    /* A. ) first determine which cluster are we graphing right now . 
    * if the cluster is "All" 
    ** Get list of all metrics If the server list is also all 
    ** Get list of all metrics if the servers list is not all by going though each server's metrics 
    
    * if the cluster is not "All"
    ** Take a particular server and get all metrics list of it 
    ** Get all metrics group name 
    ** Create graphs based on the grouping decided
    */ 
    // If metrics groups is "All" , Find all the metrics groups of this cluster 
    $cluster = array_pop ( $query["cluster"] ); 
    if ( preg_match("/^all$/i" , $cluster ) ) { 
        // Selection is of hosts 
        $url_grp = $API_URL . "list=metrics_grp" ; 
        $url_metrics = $API_URL . "list=metrics" ;
        $url_servers = $API_URL . "list=servers" ;
    } else {  
        $url_grp = $API_URL . "list=metrics_grp&clusters=" . $cluster ; 
        $url_metrics = $API_URL . "list=metrics&clusters=" . $cluster  ;
        $url_servers = $API_URL . "list=servers&clusters=". $cluster ; 
    }

    
    $ret_content = get_data($url_grp) ; 
    $json = json_decode($ret_content , true) ;
    $metrics_groups = $json["$cluster"] ;
    if ( ! preg_match("/^all$/i" , $query["metrics_group"][0] ) ) {
        $metrics_groups = $query["metrics_group"] ; 
    } 
    $ret_content = get_data($url_metrics) ; 
    $json = json_decode($ret_content , true) ;
    $metrics = $json["$cluster"] ;
    $ret_content = get_data($url_servers) ; 
    $json = json_decode($ret_content , true) ;
    $servers = array() ; 
    $servers_key = "" ; // list to be passed in GET request 
    if ( ( isset($query["servers"]) ) &&  ( in_array( "All" , $query["servers"] ) ) )  { 
        $servers = $json["$cluster"] ;
    } else if ( ! isset($query["servers"]) )  { 
        $servers = $json["$cluster"] ;  
    } else { 
        $servers =  $query["servers"] ; 
    }
       
    // We have list of servers , metrics , metrics group , cluster to be graphed .  
    $panel_tpl = init_template("templates/default/panel.tpl") ; 
    $i = 0 ; 
    $metric = "all" ; 
    if ( isset ( $query["metrics"]  ) && ( ! preg_match( "/^all$/i" , $query["metrics"][0] ) ) ) { 
        $metric = $query["metrics"][0] ; 
        if (  preg_match("/^all$/i" , $query["metrics_group"][0] ) ) { 
            $metrics_groups = array() ; 
            array_push ( $metrics_groups , "$metric" ) ; 
        } 
    } 
    // Generate options query 
    $config = $config_ob->all_config() ; 
    $option = ''; 
    foreach ( $config["graph_defaults"] as $name => $value ) { 
        if ( isset ( $query[$name] ) ) { 
            $option .= '&' . $name . "=" . $query[$name][0] ; 
        } else if ( $name != "vertical-label" ) { 
            $option .= '&' . $name . "=" . $value ;
        } 
    } 
    foreach ( $metrics_groups as $metric_grp ) {    
        if ( ! preg_match("/system|core/i" , $metric_grp) ) { 
            $i += 1; 
            $id_group = $metric_grp ; 
            $id_group = preg_replace( '/[+ ]/' , '_' , $id_group ) ;  // Dirty hack , jquery form plugin send + in query instead of %20 
            $panel_tpl->newBlock("group") ; 
            $panel_tpl->assign("group" , $id_group ) ; 
            $panel_tpl->newBlock("ajax_req") ; 
            $panel_tpl->assign("group" , $id_group ) ; 
            $panel_tpl->assign("group_un" , $metric_grp ) ; 
            $panel_tpl->assign("servers" , implode(',' , $servers ) ) ; 
            $panel_tpl->assign("metric" , $metric ) ; 
            $panel_tpl->assign("cluster" , $cluster ) ; 
            $panel_tpl->assign("options" , $option ) ;
        }
    }  
    $panel_tpl->printToScreen() ; 
    /* gets the data from a URL */
    function init_template( $file ) { 
        $tpl = new TemplatePower("$file") ; 
        $tpl->prepare() ; 
        return $tpl ; 
    }
    
    function get_data($url)
    {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, false );
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    } 
?>

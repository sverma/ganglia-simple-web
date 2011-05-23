<?php 

//backend code for CLI
include("../lib/parse_xml.php") ; 
$xml_ob = new parse_xml() ; 
$xml_ob->parse() ; 
// Ok i have parsed the XML 
$valid_commands = array ( 
    "list" => array( "servers" , "metrics" , "metric_grps" , "clusters" ) , 
    "find" => array( "servers" , "metrics" , "metric_grps" , "clusters" ) , 
    "create" => array( "graph" ) , 
) ; 

$defaults = array ( 
    "duration" => "hour" , 
    "graph_size" => "small" ,
    "graph_style" => "LINE1" , 
) ; 

$graph_servers = array() ; 
$graph_metrics = array() ; 
$graph_url = "http://metrics.directi.com/graphs.php?graph_interval=" . $defaults['duration'] . "&graph_size=" . $defaults["graph_size"] . "&graph_style=" . $defaults["graph_style"] ; 

// Now process the POST variables 
if ( ! isset( $_POST["query"] ) ) { 
    echo "Error: Enter a command<hr>"  ; 
} else { 
    $query = $_POST["query"] ; 
    $args = preg_split ( "/(\s+)/" , $query ) ; 
    array_walk($args , 'trim_value' ) ; 
    find_command ( $args ) ; 
}
function generate_graph_urls () { 
    $graph_metrics = return_graph_metrics() ; 
    $graph_servers = return_graph_servers() ; 
    global $graph_url ; 
    $url_arg = "" ; 
    foreach ( $graph_metrics as $metric ) { 
        $servers = implode ( ',' , $graph_servers ) ; 
        $url_arg = "&servers=$servers&metrics=$metric" ; 
        $url = $graph_url . $url_arg ; 
        echo "<img src=\"$url\"/>"; 
    } 
}
        


function find_command ( $args ) { 
    $command = $args[0] ; 
    array_shift($args ) ; 
    $graph_args = implode(' ' , $args ) ; 
    switch ( $command ) { 
    case "list" : 
        show_list($args) ; 
        break ; 
    case "find": 
        list_filter($args) ; 
        break ; 
    case "graph" : 
        create_graph($graph_args) ; 
        break ; 
    case "help" : 
        show_help($args) ; 
        break ; 
    default : 
        echo "<hr> Error $command is not a command <hr> " ;    
    } 
} 
function  create_graph ( $args ) { 
    /* Main graph creation function 
    Rules  : 
    create <[server_regex]>([server modifiers]) <[metric_regex]>[(metric modifiers)] [duration] [type] [style] [size] > 
    */
    // Crate a utility parse function  inspiration rrdtool code :-) I'm a bit weak at logic and its 4AM right now 
    $arg_arr = parse_graph_argument( $args ) ; 
    $server_mod = $arg_arr[0] ; 
    $list_arg = explode ( ' ' , $server_mod ) ;     
    find_command($list_arg) ; 
    $metric_arg = $arg_arr[1] ; 
    $list_arg = explode ( ' ' , $metric_arg ) ; 
    find_command($list_arg ) ; 
    global $defaults ; 
    if ( isset ( $arg_arr[2] ) ) { 
        $metric_duration = $arg_arr[2] ; 
        $defaults["duration"] = $metric_duration ; 
    } 
    if ( isset( $arg_arr[3] ) ) { 
        $graph_style = $arg_arr[3] ; 
        $defaults["graph_style"] = $graph_style ; 
    }
    if ( isset ( $arg_arr[4] ) ) { 
        $graph_size = $arg_arr[3] ; 
        $defaults["graph_size"] = $graph_size ; 
    }
    
    generate_graph_urls () ; // BIG TODO 
}
function debug ( $data ) { 
    echo "<hr> <center> debug </center>" ; 
    print_r($data) ; 
    echo "<hr>"; 
}
function parse_graph_argument ( $args ) { 
    $ch_arr = str_split($args) ; 
    $match = false ; 
    $all = array () ; 
    $temp = array () ; 
    $word = "" ; 
    foreach ( $ch_arr as $ch ) { 
        /* // debug code 
        echo "ch: $ch , temp: " ; 
        print_r($temp) ; 
        echo "<br>" ; // debug code */ 
        if ( $ch == '(' ) { 
            if ( ! empty ( $temp ) ) { 
                $word = implode ( '' , $temp ) ;
                array_push ( $all , $word ) ; 
                $temp = array() ;
            } 
            $match = true ; 
            continue ; 
        } 
        if  ( ( $match ) && ( $ch == ' ' ) ) { 
            array_push ( $temp , $ch ) ; 
            $word = implode ( '' , $temp ) ; 
            $temp = array() ; 
            array_push ( $temp , $word ) ; 
            continue ; 
        } 
            
        if ( (  $ch != ' ' ) && (  $ch != ')' ) ) { 
            array_push($temp , $ch ) ; 
            continue ; 
        } 
        if  ( ( ( $match ) && ( $ch == ')' ) ) || ( ( ! $match ) && ( $ch == ' ' ) ) )  { 
            $word = implode ( '' , $temp )  ; 
            if ( $word ) { // this is bug * :-( i'm tired to fix this 
                array_push ( $all , $word ) ; 
            }
            $temp = array () ; 
            $match = false ; 
            continue ; 
        } 
    }   
    if ( ! empty ( $temp ) ) {  
        $word = implode ( '' , $temp ) ; 
        array_push ( $all , $word ) ; 
    } 
    return ( $all ) ; 
} 
            
    

function trim_value( &$value )  {
    $value = trim($value) ; 
}
function list_filter ( $args ) { 
    if ( empty ( $args )  ) { 
        help_filter() ; 
    } 
    $type = $args[0] ; 
    if ( empty ( $args ) ) { 
        help_filter() ; 
    } 
    global $valid_commands ; 
    if ( in_array ( $type , $valid_commands["find"] ) ) { 
        filter_items($type , $args ) ; 
    } else { 
        $err = "find action specified with wrong type $type" ; 
        not_valid_request ($err )  ; 
    } 
}

function filter_items ( $type , $args ) { 
    // This is a bit complex code  
    if ( empty ( $args ) ) { // todo 
    } else { 
        array_shift($args ) ; 
    }
    switch ( $type ) { 
        case "servers" : 
        // filter servers according to the given filter parameters 
            filter_servers ( $args ) ; 
            break ; 
        case "metrics" : 
        // filter metrics accoding to the given filter 
            filter_metrics ( $args );  
            break ; 
        case "metric_grps" : 
        // filter groups 
        break ; 
        default : 
            break ; 
    }
}

function filter_servers ( $args ) { 
    // server filter  function 
    $servers = array () ; 
    global $xml_ob ; 
    
    foreach ( $args as $filter ) { 
        if ( ! empty ( $filter ) ) { 
            // match the regex 
            $result = match_right_filter($filter ) ; 
            if ( ! $result ) { 
                // some thng is wrong , do error handling here 
            } 
            // else proceed now 
            $modifier = $result[0] ; 
            $value = $result[1]; 
            switch ( $modifier ) { 
                case "cluster": // got a cluster filter 
                $all_clusters = $xml_ob->get_all_clusters() ;
                $clusters = preg_grep ( "/$value/" , $all_clusters) ; 
                $get_servers = $xml_ob->get_servers_from_clusters($clusters) ; 
                foreach ( $get_servers as $cluster => $server_list )  { 
                    $servers = array_merge( $server_list) ; 
                }
                append_server_list($servers); 
                break ; 
            }
        } 
        
    } 
}

function match_right_filter ( $filter )  { 
    preg_match ( "/([\w_]+)=\"(.+)\"/" , $filter , $matches ) ; 
    $result = array () ; 
    if (( ! isset( $matches[1] )) || ( ! isset ( $matches[2] ) ) ) { 
        return false ; 
    } else { 
        array_push ( $result , $matches[1] ) ; 
        array_push ( $result , $matches[2] ) ; 
    } 
    return $result ; 
} 
function filter_metrics ( $args ) { 
    // metrics filter function 
    $metrics  = array () ; 
    global $xml_ob ; 
    foreach ( $args as $filter ) { 
        if ( ! empty( $filter ) ) { 
        $result = match_right_filter ( $filter ) ; 
        if ( ! $result ) { 
            // error todo 
        } 
        $modifier = $result[0] ; 
        $value = $result[1] ; 
        switch ( $modifier ) { 
            case "group": // got a metric group 
            $all_groups = $xml_ob->get_all_metrics_group() ; 
            $sel_groups = preg_grep ( "/$value/" , $all_groups ) ; 
            $sel_metrics = $xml_ob->get_metrics_from_groups($sel_groups) ; 
            foreach ( $sel_metrics as $group => $metric_arr ) { 
                $metrics = array_merge($metric_arr) ; 
            } 
            append_metric_list($metrics); 
        }
    } 
} 
    
}

function filter_groups ( $args ) { 
    // group  filter function 
}

function not_valid_request ( $err ) { 
    echo "<hr> $err <hr> " ; 
} 
function help_filter () { 
    echo " <hr> The valid  commands for filter " ; 
} 

function show_list ( $args ) { 
    if ( empty ( $args ) ) { 
        help_list() ; 
    } 
    $type = $args[0] ; 
    if ( !empty ( $args )  ) { 
        array_shift($args) ;
    } // I have got a modifier 
    global $valid_commands ; 
    if ( in_array ( $type , $valid_commands["list"] )  ) { 
        list_items( $type , $args ) ; 
    } else { 
        echo "<hr> $type is not correct type for list <hr> " ; 
        help_list() ; 
    } 
} 

function help_list ( ) { 
    echo "<br> valid types for list are : " ; 
    echo '"servers" , "metrics" , "metric_grps" , "clusters"<hr>' ; 
} 

function list_items ( $type , $args ) { 
    global $xml_ob ; 
    $modifier = "" ; 
    if ( ! empty ( $args ) ) { 
        // I have got a modifier a regex apply preg_grep function on it 
        $modifier = array_shift ( $args ) ; 
    }  
    switch ( $type ) { 
        case "servers": 
            $servers  = $xml_ob->get_all_servers() ; 
            $servers = create_content($servers,$modifier) ; 
            append_server_list($servers) ; 
            break ; 
        case "metrics": 
            $metrics = $xml_ob->get_all_metrics() ; 
            $metrics = create_content($metrics, $modifier) ; 
            append_metric_list($metrics); 
            break ; 
        case "clusters": 
            $clusters = $xml_ob->get_all_clusters() ; 
            $clusters = create_content($clusters,$modifier) ; 
            Get_Array_Keys_UL($groups);
            break ; 
        case "metric_grps": 
            $groups = $xml_ob->get_all_metrics_group() ; 
            $groups = create_content($groups,$modifier) ; 
            Get_Array_Keys_UL($groups); 
            break ; 
        default : 
            help_list() ; 
            break ; 
    } 
}  
function create_content ( $data , $modifier ) { 
    if ( ! empty ( $modifier ) )  { 
        $data = preg_grep ( "/$modifier/" , $data ) ; 
    } 
    return ($data) ; 
} 
function Get_Array_Keys_UL($array) {
     $recursion=__FUNCTION__;
     if ((empty($array) ) || ( ! is_array($array) ) )   return '';
     $out='<ul>'."\n";
     foreach ($array as $key => $elem) 
       $out .= '<li>'.$elem.$recursion($elem).'</li>'."\n";
     $out .= '</ul>'."\n";  
     echo $out ; 
}
function append_server_list ( $servers ) { 
/// accepts a array and generate global list of servers to be graphed 
    global $graph_servers ; 
    $graph_servers = array_merge($servers) ; 
    return true ; 
}

function append_metric_list ( $metrics ) {
// accepts a array of metrics and generate global list metrics to be graphed 
    global $graph_metrics ; 
    $graph_metrics = array_merge($metrics) ; 
    return true ; 
} 
function return_graph_servers ( ) { 
    global $graph_servers ; 
    return ($graph_servers) ; 
}
function return_graph_metrics () { 
    global $graph_metrics ; 
    return ($graph_metrics) ; 
}
?>

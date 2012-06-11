<?php 

//backend code for CLI
include_once( "open_lib/class.TemplatePower.inc.php" );
include("lib/parse_xml.php") ; 
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
    "split" => true , 
) ; 
$show = true ; 
$graph_servers = array() ; 
$graph_metrics = array() ; 

$views_file = "views.txt" ; 

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
    global $defaults ; 
    $graph_url = "graphs.php?graph_interval=" . $defaults['duration'] . "&graph_size=" . $defaults["graph_size"] . "&graph_style=" . $defaults["graph_style"] ; 
    $graph_metrics = return_graph_metrics() ; 
    $graph_servers = return_graph_servers() ; 
    $url_arg = "" ; 
    echo "<div style='float:left;'> <h2> servers </h2> <p style='float:left;'>"; 
    foreach ( $graph_servers as $index=>$server ) { 
        echo $index+1 . ". $server &nbsp" ; 
    } 
    echo "</p></div>" ; 
    echo "<div style='float:left;'> <h2> Metrics </h2> <p style='float:left;'>"; 
    foreach ( $graph_metrics as $index=>$metric ) { 
        echo $index+1 . ". $metric &nbsp" ; 
    } 
    echo "</p></div> <divi style='float: right;'>" ; 
    if ($defaults["split"] ) {  
        foreach ( $graph_metrics as $metric ) { 
            $servers = implode ( ',' , $graph_servers ) ; 
            $url_arg = "&servers=$servers&metrics=$metric" ; 
            $url = $graph_url . $url_arg ;
            $url = preg_replace("/\s/" , "%20" , $url ) ; 
            echo "<img src=\"$url\"/>"; 
        } 
    } else { 
        foreach ( $graph_servers as $server ) { 
            $metrics = implode (',' , $graph_metrics ) ; 
            $url_arg = "&metrics=$metrics&servers=$server" ; 
            $url = $graph_url . $url_arg ;
            $url = preg_replace("/\s/" , "%20" , $url ) ; 
            echo "<img src=\"$url\"/>" ;
        }
    }
    echo "</div>"; 
}
        


/**
 *  Reads the comomand list  
 */
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
    case "views" :
        view_command($args) ; 
        break ; 
    case "help": 
        help_command () ; 
        break ; 
    default : 
        echo "<hr> Error $command is not a command <hr> " ;    
    } 
} 
/**
 *  parse_options parses a string for options , accepts two paraments $strig and $count 
 *  Ex. for string "name='blah blah' size='LARGE'" , it will parse this string and return a hash of options 
 *  Number of options to be stored in hash is determined by $count if $count is 0 then all options  
 */
function parse_options( $string , $count ) { 
    $ch_arr = str_split($string) ; 
    $options = array() ; 
    $option = array() ; 
    $value = array () ; 
    $esc_flag = false ; 
    $flag = false ; 
    foreach ( $ch_arr as $ch ) { // look for = 
        if ( ( ! $flag ) && ( ! (  $ch == '=' ) ) )  { 
            array_push($option, $ch) ; 
        } else if ( $ch == '=' ) { // bucket full 
            $flag = true ; 
            $tmp_option = implode ('' , $option ) ; 
            $option = array(); 
            $options["$tmp_option"] = "" ; 
            continue ; 
        }else if ( ( $flag ) ) { 
            if (  ( ( $ch =='"' ) || ( $ch == "'" ) )  && ( ! $esc_flag ) )  { 
                $esc_flag = true ; 
                continue ; 
            } 
            if (  ( ! $esc_flag ) &&  ( ! ( $ch == ' ' ) ) ) { 
                array_push ( $value , $ch ) ; 
                continue ; 
            } 
            if ( ( $esc_flag ) && ( ( $ch =='"' ) || ( $ch == "'" ) ) ) { 
                $esc_flag = false ; 
            } else if ( ( $esc_flag ) && ( ! ( ( $ch =='"' ) || ( $ch == "'" ) ) ) ) { 
                array_push ( $value , $ch ) ;
                continue ; 
            } 
            if ( $flag ) { 
                $tmp_value = implode ( '' , $value ) ; 
                $options["$tmp_option"] = $tmp_value ; 
                $value = array() ; 
                $flag = false ; 
            } 
        } 
    } 
    return ( $options ) ; 
} 


function view_command ( $args ) { 
    $command = $args[0] ; 
    array_shift($args ) ; 
    $views_args = implode(' ' , $args ) ; 
    switch ( $command ) { 
    case "list" : 
        list_views () ; 
        break ; 
    case "save" : 
        save_command ( $views_args) ; 
        break ; 
    case "load" : 
        load_view($views_args) ; 
        break;
    case "del" : 
        del_view($views_args ) ; 
        break ;
    default: 
        echo "<p> Error view command doesn't recognise $command action , Please consult the manual </p>"  ; 
        exit () ; 
    } 
} 
/**
 *  save_command functions 
 *  @$args : is the string which is passed to save command 
 */
function save_command ( $args ) { 
    // Check if the name argument is supplied to the save command 
    $options = array() ; 
    $command = "" ; 
    if ( preg_match ( "/(.+?)\s+\(\s?(.+)\)/" , $args , $matches ) ) { 
        $options = parse_options ( $matches[1] , 0) ; 
        $command = $matches[2]; 
    } else { 
        echo "<br> <p> save command syntax is save name=<NAME> <options> (Graphing|Selection command) </p> "; 
        exit() ; 
    } 
    if ( array_key_exists ( "name" , $options ) ) { 
        $view_name = $options["name"] ;
        save( $view_name, $command ) ; 
    } else { 
        echo  "<p> Error.!! save command given without name of the view to be saved </p> " ; 
    }

    $find_arg = preg_split ( "/(\s+)/" , $command ) ; 
    array_walk($find_arg , 'trim_value' ) ; 
    find_command ( $find_arg ) ; 
}
/**
 * read_views_file 
 * reads the views file and return a array indexed with id of view , name and command as values  
 * @param mixed $file 
 * @access public
 * @return array
 */
function read_views_file ( $file ) {  
    $fr = fopen ($file , "r" ) ; 
    if ( ! $fr ) { 
        echo "Error! couldn't load the views , probably some internal error or no views saved yet , Read Manual <br> " ; 
    } else { 
        // Got the file , read it 
        while ( ( $buffer = fgets ( $fr ) ) != false ) { 
            $line= explode ( ":" , $buffer )  ;
            $view_id = $line[0]; 
            $view_name = $line[1]; 
            $view_arg  = $line[2]; 
            $views[$view_id] = array() ; 
            $views["$view_id"]["name"] = $view_name ; 
            $views["$view_id"]["arg"] = $view_arg ; 
        } 
        if ( $views ) { 
            return $views ; 
        } else { 
            echo "Error! couldn't load the views , probably some internal error or no views saved yet , Read Manual <br> " ;
        } 
    }
    fclose ( $fr ) ; 
} 


function write_to_views ( $fh , $line ) { 
    fwrite ( $fh , $line ) ; 
    fwrite ($fh , "\n" ) ; 
} 

/**
 *  Save function : It saves the arguement list for a command to be viewed later  
 */
function save ( $name , $args ) { 
    global $views_file;
    $fr = fopen ( $views_file , 'a+' ) ; 
    $exists = false ; // flag to ceck if the view already exist or not 
    if ( !$fr ) { 
        echo "Error! while saving graph :" ; 
        exit () ; 
    }
    $views = read_views_file($views_file ) ; 
    if ( sizeof ( $views )   == 0 )  { // first entry in views 
        $line = "0" . ":" . "$name" . ":" . "$arg" ; 
        write_to_views($fr , $line ) ; 
    }else { 
        foreach ( $views as $view ) { 
            $c_view = $view["name"] ; 
            if ( strcmp($c_view , $name ) == 0 ) {  
                $exists = true ; 
                break ; 
            } 
        } 
    } 
    $size = sizeof ($views ) ; 
    if ( $exists ) { 
        echo "Error.. !! view \"$name\" already exists in the view database , try something else <br> " ; 
        exit() ; 
    } else { 
        $line = "$size" . ":" . "$name" . ":" . "$args" ; 
        write_to_views($fr , $line ) ; 
        fclose ( $fr ) ; 
        echo "<p> \"$view\" view saved </p> " ; 
    } 
}

function list_views ( ) { 
    global $views_file ; 
    $views = array() ; 
    $views = read_views_file($views_file) ; 
    if ( count($views) == 0 ) { 
        echo "Error ! No views saved yet , please create and save a view first , consult manual for details " ; 
    } 
    
    $tpl = new TemplatePower("templates/cli/list_views.tpl") ; 
    $tpl->prepare() ; 
    foreach ($views as $index => $view ) { 
        $tpl->newBlock("view") ; 
        $tpl->assign("id" , $index );  
        $tpl->assign("name" , $view["name"] ) ; 
        $tpl->assign("command" , $view["arg"]) ; 
    } 
    $tpl->printToScreen() ; 
} 

function del_view ( $args ) {
    $id = $args ; 
    if ( preg_match( "/^\d+$/"  , $id ) ) { 
        global $views_file ; 
        $views = read_views_file($views_file) ; 
        if ( array_key_exists ( $id  , $views ) ) { 
            /**
             *  This needs to be implemented 
             */
            echo "Voila , Work in progress .. !! " ; 
        } else { 
            echo "Error..!! \"$id\" doesn't seems to be a valid view Id" ; 
        } 
    } else { 
        echo "Error.!! View to be loaded should be referenced by its ID , refer manual " ; 
    }
}


/**
 *  
 *  Function to load a particular view referenced by ID 
 */
function load_view ( $args ) { 
    $id = $args ; 
    if ( preg_match( "/^\d+$/"  , $id ) ) { 
        global $views_file ; 
        $views = read_views_file($views_file) ; 
        if ( array_key_exists ( $id  , $views ) ) { 
            $command = $views["$id"]["arg"] ; 
            $graph_arg = preg_split ( "/(\s+)/" , $command ) ; 
            array_walk($graph_arg, 'trim_value' ) ; 
            find_command($graph_arg) ; 
        } else { 
            echo "Error..!! \"$id\" doesn't seems to be a valid view Id" ; 
        } 
    } else { 
        echo "Error.!! View to be loaded should be referenced by its ID , refer manual " ; 
    } 

}
             
function help_command() { 
/**
 * show help for the command  
 */
    echo "<p>Valid commands are list , find , graph and help </p>" ; 
    /**
     * This help string shoudl be seperated out from the code later  
     */
    $help_text = <<<EOF
        <div style="none">
        <h2> LIST : </h2>
        <p> list command displays your ganglia elements such as servers , metrics  or metric groups , It can can filter yoru result based ona regex match </p>
        <h3> Examples : 
        <ol> Listing all servers 
        <li>  list servers </li> </ol>
        <li> <a href="http://c/display/duSysAd/Ganglia+CLI">  LINK HERE FOR CR HELP PAGE  </a> </li>
        </div>
EOF;
    echo "$help_text"; 
}
function  create_graph ( $args ) { 
    global $show ; 
    $show = false ; 
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
        $graph_size = $arg_arr[4] ; 
        $defaults["graph_size"] = $graph_size ; 
    }
    if ( isset ( $arg_arr[5] ) ) { 
        $split = $arg_arr[5] ; 
        if ( preg_match ("/no/i" , $split )  ) { 
            $defaults["split"] = false ; 
        } 
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
    global $show ; 
    foreach ( $args as $filter ) { 
        if ( ! empty ( $filter ) ) { // match the regex 
            $result = match_right_filter($filter ) ;
            if ( ! $result ) { // some thng is wrong , do error handling here 
            } else { //proceed now 
                $modifier = $result[0] ;
                $value = $result[1];
                switch ( $modifier ) { 
                    case "clusters": // got a cluster filter 
                        $all_clusters = $xml_ob->get_all_clusters() ;
                        $clusters = preg_grep ( "/$value/" , $all_clusters) ;
                        $get_servers = $xml_ob->get_servers_from_clusters($clusters) ;
                        foreach ( $get_servers as $cluster => $server_list )  { 
                            $servers = array_merge( $server_list , $servers) ;
                        }
                        append_server_list($servers);
                        if ( $show ) { 
                            Get_Array_Keys_UL($servers);
                        } 
                    break ;
                    default: 
                        help_filter("servers" , $modifier) ; 
                    break ; 
                    /**
                     * Only clusters filter is supported as of now , later we will add                       other filters as well  
                     */
                }

            } 
        }
    }
}

function match_right_filter ( $filter )  { 
    preg_match ( "/([\w_]+)=[\'|\"](.+)[\'|\"]/" , $filter , $matches ) ; 
    $result = array () ; 
    if (( ! isset( $matches[1] )) || ( ! isset ( $matches[2] ) ) ) { 
        return false ; 
    } else { 
        array_push ( $result , $matches[1] ) ; 
        array_push ( $result , $matches[2] ) ; 
    } 
    return $result ; 
} 
/**
 *  filter metrics : function to apply filter for metrics element 
 *  Ex: find metrics servers="mss" 
 *  function is called with args as 'servers="mss"' 
 */
function filter_metrics ( $args ) { 
    // metrics filter function 
    $metrics  = array () ; 
    global $show ; 
    global $xml_ob ; 
    foreach ( $args as $filter ) { 
        
        if ( ! empty( $filter ) ) { 
            $result = match_right_filter ( $filter ) ; 
            if ( ! $result ) { 
                // error todo 
            } 
            $modifier = $result[0] ; $value = $result[1] ; 
            $l_metrics = array() ; 
            $met_table = array () ; 
            switch ( $modifier ) { 
                case "groups": // got a metric group 
                    $all_groups = $xml_ob->get_all_metrics_group() ; 
                    $sel_groups = preg_grep ( "/$value/" , $all_groups ) ; 
                    $sel_metrics = $xml_ob->get_metrics_from_groups($sel_groups) ; 
                    foreach ( $sel_metrics as $group => $metric_arr ) { 
                        $metrics = array_merge($metrics , $metric_arr) ; 
                    } 
                    append_metric_list($metrics); 
                    if ( $show ) {
                        Get_Array_Keys_UL($metrics);
                    }
                break ; 
                case "servers": // Got a server group 
                    $all_servers = $xml_ob->get_all_servers() ; 
                    $sel_servers = preg_grep ( "/$value/" , $all_servers ) ; 
                    $sel_metrics = $xml_ob->get_metrics_from_servers($sel_servers) ; 
                    $tpl = new TemplatePower("templates/cli/temp.tpl") ; 
                    $tpl->prepare() ; 
                    foreach ( $sel_metrics as $server => $metrics_arr ) { 
                    #    echo "$server_name <hr>"; 
                        $metrics = array() ; 
                        $metrics = array_merge($metrics_arr["non_indexed_metrics"]); 
                        $n_index_met = array_keys($metrics_arr["indexed_metrics"]) ; 
                        $metrics = array_merge( $metrics , $n_index_met) ; 
                        $met_table["$server"]  = $metrics ; 
                        if ( empty ( $l_metrics ) ) { 
                            $l_metrics = $metrics ; // to get the intersection 
                        } 
                        append_metric_list($metrics); 
                        $l_metrics = array_intersect ( $metrics , $l_metrics ) ; 
                    }
                    foreach ( $met_table as $server => $metrics ) { 
                        $tpl->newBlock("server") ;  
                        $server_name = (string) $server ; 
                        $tpl->assign("server" , $server_name ) ; 
                        $tpl->assign("all_cnt" , count ( $metrics ) ) ; 
                        $tpl->assign("common_cnt" , count($l_metrics) ) ; 
                        $dif_metrics = array_diff ( $metrics , $l_metrics )   ;
                        $tpl->assign("specific_cnt" , count($dif_metrics) ) ; 
                        usort($metrics , "cmp") ; 
                        usort($l_metrics , "cmp") ; 
                        // first print the common metrics 
                        foreach ( $l_metrics as $metric ) { 
                            $tpl->newBlock("common_metrics") ; 
                            $tpl->assign("c_metric" , $metric ) ; 
                        } 
                        foreach ( $dif_metrics as $metric ) {
                            $metric_name = (string) $metric ; 
                    #        echo "$metric_name" ; 
                            $tpl->newBlock("metric") ; 
                            $tpl->assign("metric" , $metric_name ) ; 
                        } 
                    } 
                    if ( $show ) { 
                        $tpl->printToScreen() ; 
                    }
                break;
                case "clusters": 
                    $all_clusters = $xml_ob->get_all_clusters() ; 
                    $sel_clusters = preg_grep("/$value/" , $all_clusters ) ; 
                    $sel_servers = array() ; 
                    foreach ( $sel_clusters as $cluster ) { 
                        $servers = $xml_ob->get_servers_from_clusters($cluster); 
                        $sel_servers = array_merge($sel_servers,$servers["$cluster"]) ; 
                    } 
                    $servers = implode ( "|" , $sel_servers ) ; 
                    $n_args = array() ; 
                    $servers = "servers=\"" . $servers . '"' ; 
                    array_push($n_args , $servers ) ; 
                    filter_metrics($n_args) ; 
                    
                 break; 
            }
        } 
    } 
}
function cmp($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}
function filter_groups ( $args ) { 
    // group  filter function 
}

function not_valid_request ( $err ) { 
    echo "<hr> $err <hr> " ; 
} 
function help_filter ($filter , $modifier) { 
    echo " $modifier is not a valid modifier for $fliter" ; 
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
    global $show ; 
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
            if ( $show ) { 
                Get_Array_Keys_UL($servers);
            } 
            break ; 
        case "metrics": 
            $metrics = $xml_ob->get_all_metrics() ; 
            $metrics = create_content($metrics, $modifier) ; 
            append_metric_list($metrics); 
            if ( $show ) { 
                Get_Array_Keys_UL($metrics);
            } 
            break ; 
        case "clusters": 
            $clusters = $xml_ob->get_all_clusters() ; 
            $clusters = create_content($clusters,$modifier) ; 
            Get_Array_Keys_UL($clusters);
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
    $graph_servers = array_merge($graph_servers , $servers) ; 
    return true ; 
}

function append_metric_list ( $metrics ) {
// accepts a array of metrics and generate global list metrics to be graphed 
    global $graph_metrics ; 
    foreach ( $metrics as $metric ) { 
        if ( ! in_array ( $metric , $graph_metrics ) ) {
            array_push($graph_metrics , $metric ) ; 
        }
    }
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

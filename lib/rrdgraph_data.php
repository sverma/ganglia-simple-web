<?php 
include ("ganglia_config.php") ; 
class RRD_rpn { 
}
class RRD_data { 
    protected $def = array () ; 
    //protected $vdef = new RRD_rpn; 
    protected $cdef = array () ; 
    protected $def_count ; 
    public function __construct (  ) {
         $this->def_count = 0 ; 
        
    }
    public function add_ds ( $defname , $rrdfile , $cf ) { 
        if ( file_exists($rrdfile) ) { 
            $tmp = $this->def_count ;
             $this->def[$tmp] = array () ;
             $this->def[$tmp]["def"]  = $defname ;
             $this->def[$tmp]["rrdfile"] = $rrdfile ; 
             $this->def[$tmp]["cf"] = $cf ; 
            $this->def_count++ ; 
        }
    }


}

class RRD_graph extends RRD_data { 
    protected $graph_prop ; 
    protected $graph_file ; 
    protected $def_el ; 
    private $main_config  ; 
    public $constant_color ; 
    
    public function __construct (  ) { 
        parent::__construct() ; 
        $this->graph_prop = array () ; 
        $config_ob = new ganglia_config() ; 
        $this->main_config = $config_ob->all_config() ; 
        
    }
    public function set_properties ($prop ) { 
        foreach ( $prop as $key => $value ) { 
            $this->graph_prop["$key"] = $value ; 
        }
    }
    public function create_graph () { 
        $tmp = "" ;
        $tmp2 = ""; 
        $tmp3 = '' ; 
        foreach ( $this->graph_prop as $key => $value ) { 
            if ( ( $key != "graph_type" )  && ( $key != "pvalue" ) && ( $key != "graph_style" ) ) { 
                $tmp .= '--' . $key . " \"$value\"" . ' ' ;
            }
        }
        $this->def_el .= $tmp ;
        $tmp = '' ; 
        $color_list = array () ;
        if ( $this->def_count == 1 ) { 
            array_push ( $color_list , $this->main_config["metric_color"]["default_color"] ) ; // If there is only one server color scheme should be unique  
        } else { 
            $color_list = $this->get_random_colors ( $this->def_count ) ;   
        }
        for ( $index =0 ; $index < $this->def_count ; $index++ ) { 
            $def = $this->def[$index]["def"] . $index ; 
            $tmp .= 'DEF' . ':'  . $def . '='  .'"' . $this->def[$index]["rrdfile"] . '"'  . ':' . $this->def[$index]["def"] . ':' . $this->def[$index]["cf"]; 
            if ( $this->graph_prop["graph_type"] ) { 
                $reduce = $this->graph_prop["graph_type"] ; 
                if ( $reduce == "PERCENT" ) { 
                    $pvalue = $this->graph_prop["pvalue"] ;
                    $tmp_string = ":reduce=$reduce:pvalue=$pvalue" ; 
                    $tmp = $tmp . $tmp_string . ' ' ; 
                } else { 
                    // $tmp_string = ":reduce=$reduce" ; rrdtool patch still needs to be done on serenity 
                    $tmp_string = "" ;
                    $tmp = $tmp . $tmp_string . ' ' ;
               }
            }
            $type = "" ; 
            if ( isset($this->graph_prop["graph_style"]) ) {   
                if ( $this->graph_prop["graph_style"] == "STACK" ) { 
                    $type = "AREA" ; 
                    $align = ":STACK "; 
                } else { 
                    $type = $this->graph_prop["graph_style"] ; 
                    $align = " "; 
                } 
            } else { 
                $type = "LINE1" ;
                $align = " ";  
            } 
            $tmp .= ' ' ; 
            $tmp_str  = $this->def[$index]["rrdfile"] ; 
            $matches = "" ; 
            preg_match('/.*\/(.+)\/(.+).rrd/' , $tmp_str , $matches) ; 
            $host = $matches[1]; 
            $metric = $matches[2] ; 
            $color = "" ; 
            if ( $this->def_count == 1 ) { 
                $color = $this->main_config["metric_color"]["default_color"]  ;
            }else { // There are more than one graph item , first check whether a migle metric or not  
                    $color = $this->get_random_colors( $host ) ;   
            } 
                $tmp2 .= "$type" . ':' . '"' . $def . '"'   . $color . ':' . '"' . $matches[1] . '  ' . $matches[2] . '\n"' . "$align" ;  
                $tmp3 .= "COMMENT:" . '"' . $matches[1] . '-' . $matches[2] . '\l" ' ; 
               
                $tmp3 .= "VDEF:${def}_last=$def,LAST VDEF:${def}_min=$def,MINIMUM VDEF:${def}_avg=$def,AVERAGE VDEF:${def}_max=$def,MAXIMUM GPRINT:'${def}_last':'Now\:%7.2lf%s' GPRINT:'${def}_min':'Min\:%7.2lf%s' GPRINT:'${def}_avg':'Avg\:%7.2lf%s' GPRINT:'${def}_max':'Max\:%7.2lf%s\l' TEXTALIGN:left  " ; 
        } 
            $this->def_el .= $tmp ;
            $this->def_el .= "$tmp2" ;  
            $this->def_el .= "$tmp3" ;  
        //system("echo $this->def_el >> /tmp/dump " ) ; 
    }
    
    protected function get_random_colors ( $server ) { 
        // TODO build rendom colors based on http://www.utexas.edu/learn/html/colors.html
        if ( isset($this->constant_color ) ) { 
            $color_map_f = "/tmp/color_list" ; 
            $found = false ; 
            $fhandle = "" ; 
            if ( file_exists("$color_map_f") )  { // we have a file with host to static color mapping 
                $color_map = file("$color_map_f", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); 
                foreach ( $color_map as $color ) { 
                    $map = explode ( ":" , $color ) ; 
                    if ( $map[0] == "$server" ) { 
                        $found = true ; 
                        return $map[1] ; 
                    } 
                } 
            } 
            if ( ! $found ) { 
                $fhandle = fopen ( "$color_map_f" , "a" ) ; 
            } 
        } 
        $colors = array( "#000000","#000033","#000066","#000099","#0000CC","#0000FF","#003300","#003333","#003366","#003399","#0033CC","#0033FF","#006600","#006633","#006666","#006699","#0066CC","#0066FF","#009900","#009933","#009966","#009999","#0099CC","#0099FF","#00C000","#00CC00","#00CC33","#00CC66","#00CC99","#00CCCC","#00CCFF","#00FF00","#00FF33","#00FF66","#00FF99","#00FFCC","#00FFFF","#330000","#330033","#330066","#330099","#3300CC","#3300FF","#333300","#333333","#333366","#333399","#3333CC","#3333FF","#336600","#336633","#336666","#336699","#3366CC","#3366FF","#339900","#339933","#339966","#339999","#3399CC","#3399FF","#33CC00","#33CC33","#33CC66","#33CC99","#33CCCC","#33CCFF","#33FF00","#33FF33","#33FF66","#33FF99","#33FFCC","#33FFFF","#660000","#660033","#660066","#660099","#6600CC","#6600FF","#663300","#663333","#663366","#663399","#6633CC","#6633FF","#666600","#666633","#666666","#666699","#6666CC","#6666FF","#669900","#669933","#669966","#669999","#6699CC","#6699FF","#66CC00","#66CC33","#66CC66","#66CC99","#66CCCC","#66CCFF","#66FF00","#66FF33","#66FF66","#66FF99","#66FFCC","#66FFFF","#990000","#990033","#990066","#990099","#9900CC","#9900FF","#993300","#993333","#993366","#993399","#9933CC","#9933FF","#996600","#996633","#996666","#996699","#9966CC","#9966FF","#999900","#999933","#999966","#999999","#9999CC","#9999FF","#99CC00","#99CC33","#99CC66","#99CC99","#99CCCC","#99CCFF","#99FF00","#99FF33","#99FF66","#99FF99","#99FFCC","#99FFFF","#CC0000","#CC0033","#CC0066","#CC0099","#CC00CC","#CC00FF","#CC3300","#CC3333","#CC3366","#CC3399","#CC33CC","#CC33FF","#CC6600","#CC6633","#CC6666","#CC6699","#CC66CC","#CC66FF","#CC9900","#CC9933","#CC9966","#CC9999","#CC99CC","#CC99FF","#CCCC00","#CCCC33","#CCCC66","#CCCC99","#CCCCCC","#CCCCFF","#CCFF00","#CCFF33","#CCFF66","#CCFF99","#CCFFCC","#CCFFFF","#FF0000","#FF0033","#FF0066","#FF0099","#FF00CC","#FF00FF","#FF3300","#FF3333","#FF3366","#FF3399","#FF33CC","#FF33FF","#FF6600","#FF6633","#FF6666","#FF6699","#FF66CC","#FF66FF","#FF9900","#FF9933","#FF9966","#FF9999","#FF99CC","#FF99FF","#FFCC00","#FFCC33","#FFCC66","#FFCC99","#FFCCCC","#FFCCFF","#FFFF00","#FFFF33","#FFFF66","#FFFF99","#FFFFCC","#FFFFFF" ) ;  
        $total_colors = sizeof($colors) ; 
        $rand = rand(0,$total_colors-1) ;
        if ( isset($this->constant_color )  )  {
            $string = "$server:$colors[$rand]\n";  
            fwrite($fhandle , $string );
            fclose($fhandle) ; 
        } 
        return $colors[$rand] ; 
    }
    public function get_rrd_cmd_arg ( ) { 
        return  "$this->def_el" ; 
    }
    // the above display_out isn't working 
}
?>

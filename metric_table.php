<?php
include_once( "open_lib/class.TemplatePower.inc.php" );
include ("lib/parse_xml.php") ;
$xml_ob = new parse_xml() ;
$xml_ob->parse() ;
$metric_groups = $xml_ob->get_all_metrics_group() ; 

$result  = $xml_ob->get_metrics_from_groups($metric_groups); 
$tpl = new TemplatePower("templates/default/metric_drop_down.tpl") ;
    $tpl->prepare() ;
foreach ( $result as $group => $metrics ) { 
    if ( ! preg_match( "/Disk-Module/" , $group ) ) { 
        foreach ( $metrics as $metric ) { 
            $metric_detail = $xml_ob->get_metric_details($metric) ; 
            $tpl->newBlock("metric") ;
            $tpl->assign("metric" , $metric ) ;
            $tpl->assign("metric_grp" , $group ) ;
            if ( isset ( $metric_detail["$metric"]["TITLE"] ) ) { 
                $tpl->assign("title" , $metric_detail["$metric"]["TITLE"] ) ;
            } else { 
                $tpl->assign("title" , "no title" ) ; 
            } 
            if ( isset ( $metric_detail["$metric"]["DESC"] ) ) { 
                $tpl->assign("desc" , $metric_detail["$metric"]["DESC"] ) ;
            } else { 
                $tpl->assign("desc" , "no description" ) ; 
            } 
            
        }
    }
}
$tpl->printToScreen() ; 
?>
           
    
 



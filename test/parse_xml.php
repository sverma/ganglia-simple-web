<? 
include("../lib/parse_xml.php") ; 
$xml_ob = new parse_xml () ; 
$xml_ob->debug_host( "inbound-us1.mailhostbox.com" )  ; 
$xml_ob->parse() ; 

/*$data = $xml_ob->get_all_metrics() ;
$data = $xml_ob->get_servers_from_clusters("profile.pw-bll") ;
$data = $xml_ob->get_servers_from_clusters("profile.pw-bll") ;
$data = $xml_ob->get_servers_from_metrics("idgmerr-ps") ;
$data = $xml_ob->get_metrics_from_clusters("pwmail-mss") ;
$data = $xml_ob->get_all_servers() ;
$data = $xml_ob->get_cluster_from_servername("mss-us11.mailhostbox.com") ;
$data = $xml_ob->get_metrics_group_from_clusters("pwmail-mss") ;*/
#$data = $xml_ob->get_metrics_from_groups("block_device_stats") ;
//$data = $xml_ob->get_metric_indexes_server("rd_sec-ps" , "mss-us11.mailhostbox.com" ) ;
$data = $xml_ob->get_metrics_from_servers("inbound-us1.mailhostbox.com") ;
if ( $data  == -1 )  { 
    $err = $xml_ob->getParseErr() ; 
    echo $err ; 
} else { 
    print_r($data) ; 
}  

?>

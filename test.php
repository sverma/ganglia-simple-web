<?
include("lib/parse_xml.php") ;
$xml_ob = new parse_xml() ;
$xml_ob->parse() ;
$metrics = $xml_ob->get_servers_from_clusters("hosting.pw-cpanel-vps") ;
$metrics_servers = $xml_ob->get_metrics_from_servers($metrics["hosting.pw-cpanel-vps"]) ; 
var_dump($metrics_servers); 

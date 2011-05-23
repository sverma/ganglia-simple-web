<?php
include ( "lib/find_rrd_paths.php" ) ;
$all = new all_metrics() ;
$all->add_metric("Postfix Maia - Time before queue manager") ; 
$all->add_metric("Postfix Maia - Time before queue manager") ; 
$all->add_metric("Postfix Maia - Time before queue manager") ; 
$all->add_metric("proc_total") ; 
$all->add_cluster("supersite-bigrock") ; 
$all->add_cluster("profile.pw-bll") ; 
$all->add_server("bll-us1") ; 
print_r( $all->create_paths() ) ; 


<?
$command = '/usr/bin/rrdtool graph - --width 400 --height 600 --end now --start -3660 DEF:sum0=/var/lib/ganglia/rrds/pwmail-outbound/outbound-us1.mailhostbox.com/cpu_user.rrd:sum:AVERAGE LINE1:"sum0"#6699CC:"outbound-us1.mailhostbox.com cpu_user\n" COMMENT:"outbound-us1.mailhostbox.com-cpu_user\l" VDEF:sum0_last=sum0,LAST VDEF:sum0_min=sum0,MINIMUM VDEF:sum0_avg=sum0,AVERAGE VDEF:sum0_max=sum0,MAXIMUM GPRINT:\'sum0_last\':\'Now\:%7.2lf%s\' GPRINT:\'sum0_min\':\'Min\:%7.2lf%s\' GPRINT:\'sum0_avg\':\'Avg\:%7.2lf%s\' GPRINT:\'sum0_max\':\'Max\:%7.2lf%s\l\'';
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");   // Date in the past
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header ("Cache-Control: no-cache, must-revalidate");   // HTTP/1.1
header ("Pragma: no-cache");                     // HTTP/1.0
header ( "Content-type: image/png") ; 

passthru($command) ; 
?>

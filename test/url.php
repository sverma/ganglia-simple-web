<?
$test = array("test1" , "test2");
$serialized = rawurlencode(serialize($test));
echo "<a href=receive_array.php?testvar=".$serialized.">Test</a>";
?>

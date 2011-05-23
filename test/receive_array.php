<?
if ( ( isset($_GET['testvar']) ) && ( is_scalar ( $_GET['testvar']) )  )  { 
    $testvar = unserialize(rawurldecode($_GET['testvar']));
}
if ( ! isset( $testvar )  ) { 
    $testvar = $_GET['testvar']; 
} 
echo "<pre>";
print_r($_GET['testvar']) ; 

echo '<HR>'; 
print_r($testvar);

echo "</pre>";
?>

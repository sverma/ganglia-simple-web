<html>
<head>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script src="js/jquery.ingrid.js"> </script>
<link rel="stylesheet" href="css/ingrid.css" type="text/css" media="screen" />

</style>
<script type="text/javascript">
function handle_group( ) { 
    
} 
$(document).ready(function() { 
        $("#table1").ingrid({ 
            url: 'remote.html',
            height: 350
        });
}); 
</script>
<head>
<body>
<center>
<table id="table1">
 <thead>
  <tr>
   <th>Metric Group</th>
   <th>Metric </th>
   <th> Metric Description </th>
  </tr>
 </thead>
 <tbody>
<!-- START BLOCK : metric -->
  <tr>
   <td>{metric_grp}</td>
   <td>{metric}</td>
<td> {desc} </td>
  </tr>
<!-- START BLOCK : metric -->
 </tbody>
</table>
</center>
</body>
</html>


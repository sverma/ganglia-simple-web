<script src="js/jquery.ingrid.js"> </script>
<link rel="stylesheet" href="css/ingrid.css" type="text/css" media="screen" />

</style>
<script type="text/javascript">
function handle_group( ) { 
    
} 
$(document).ready(function() { 
        $("#table1").ingrid({ 
            height: 350
        });
}); 
</script>
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


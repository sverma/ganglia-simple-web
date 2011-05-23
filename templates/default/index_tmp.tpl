<!DOCTYPE html>
<html>
<head>
  <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
  <link href="css/jquery.ui.button.css" rel="stylesheet" type="text/css"/>
  <script src="js/menu.js" > </script>
  <script src="js/form_submit.js" > </script>
  <script src="js/jquery.loading.1.6.4.min.js" > </script>
  <script src="js/jquery.form.js" > </script>
  
  <script>
  $(document).ready(function() {
    $("#tabs").tabs();
  });
function toggleLayer( whichLayer )
{
  var elem, vis;
  if( document.getElementById ) // this is the way the standards work
    elem = document.getElementById( whichLayer );
  else if( document.all ) // this is the way old msie versions work
      elem = document.all[whichLayer];
  else if( document.layers ) // this is the way nn4 works
    elem = document.layers[whichLayer];
  vis = elem.style;
  // if the style.display value is blank we try to figure it out here
  if(vis.display==''&&elem.offsetWidth!=undefined&&elem.offsetHeight!=undefined)
    vis.display = (elem.offsetWidth!=0&&elem.offsetHeight!=0)?'block':'none';
  vis.display = (vis.display==''||vis.display=='block')?'none':'block';
}
  </script>
<style type="text/css" > 
th { text-align: center; font-weight: bold }
th { vertical-align: baseline }
td { vertical-align: middle; 
     border-style: hidden; 
}
table { 
     caption-side: top ; 
     
} 
caption { 
    color: blue; 
} 
label { 
    color : black ; 
} 
label,caption { 
font-weight: bold;
display: block;
width: 150px;
float: center;
}
</style>
</head>
<body style="font-size:62.5%;">
<div id="tabs">
    <div id="user_section" style="text-align: right; font-size:82.5%; color:#FF0000" >
    <p style="text-align: right;"> Welcome , {user} &nbsp , Please file bugs at automation@sysrt.directi.com 
    <a href="destroy.php">  LOGOUT  </a> 
    </p>
    </div>
    <ul>
        <li><a href="#fragment-1"><span>Graphs</span></a></li>
        <li><a href="#fragment-2"><span>Alerts</span></a></li>
        <li><a href="metric_table.php"><span> Metric Details</span></a></li>
    </ul>
    <div id="fragment-1">
    <form id="graphit" name="input" action="../create_graph_panel.php" method="get">
        <table>
        <tbody>
        <tr>
        <td>
            <table > 
            <tr> <td>
             <label> Project  </label>
                <select id="cluster"  name="cluster" autofocus>
                </select>
            </td> <tr>
            <td> <label for="servers" > Servers </label>
                <select id="servers" name="servers" multiple="multiple" >
                </select>
            </td>
            </tr> </tbody> 
            </table>
        </td> <td> 
        <table  > <tr> 
        <td> <label> Metrics Group </label>
            <select id="metrics_group" name="metrics_group" >
            </select>
        </td> </tr>
        <tr>
        <td> <label> Metrics </label>
            <select id="metrics" name="metrics" >
            <option value="All" > All </option>
            </select>
        </td>
        </tr>
        </table> </td> <td> <table >
        <tr> 
        <td> <label> Graph Type </label>
            <select id="graph_type" name="graph_type">
            <option value="average"> Average </option>
            <option value="minimum"> Min </option>
            <option value="maximum"> Max </option>
            <option value="percentile"> Percentile </option>
        </td>
        <td >
        <label> Percentile Value: </label> <input type="text" id="percentile_val" name="percentile_val" value="90" /> 
        </td> </tr>  
        <tr> 
        <td>
            <label> Graph Interval </label>
            <select id="graph_interval" name="graph_interval">
            <option value="hour"> Hourly </option>
            <option value="day"> Daily </option>
            <option value="week"> Weekly </option>
            <option value="month"> Monthly </option>
            <option value="Yearly"> Yearly </option>
        </td>
        <td>
            <label> Graph Size </label>
            <select id="graph_size" name="graph_size">
            <option value="small"> Small </option>
            <option value="medium"> Medium </option>
            <option value="large"> Large </option>
        </td>
        </tr> 
        </table>    
        <td> <table> <tr> 
         <td>
            <label> Graph STYLE </label>
            <select id="graph_style" name="graph_style">
            <option value="LINE1"> Line graph </option>
            <option value="STACK"> STACK graph </option>
            <option value="AREA"> Area graph </option>
        </td> </tr> <tr>
        <td>
            <input type="submit" value="Submit" autofocus/>
        </td>
        </tr>    </table> </td>
        </tbody>
        </table> 
    </form>
    <div id="grapharea">
    </div> 
    </div>
    <div id="fragment-2">
    </div>
    <div id="fragment-3">
    </div>
</div>
</body>
</html>

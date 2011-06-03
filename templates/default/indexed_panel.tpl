<!-- START BLOCK : group -->
<center> {group} : {o_metric} </center>
<div style="background-color:#336699;padding:1px;text-align:center;color:#ffffff;size-indent:10px;text-transform:uppercase;" ONMOUSEDOWN="javascript:toggleLayer('{group}{index}');" TITLE="Toggle {group} metrics group on/off" id="{group}{index}_panel" > </div>
<div id="{group}{index}" >

<style type="text/css">
  .toggler { width: 500px; height: 200px; }
  #button { padding: .15em 1em; text-decoration: none; }
  #effect { width: 240px; height: 135px; padding: 0.4em; position: relative; }
  #effect h3 { margin: 0; padding: 0.4em; text-align: center; }
</style>

<table>
<tbody>
<tr style="float: left;">
<!-- START BLOCK : index -->
<td style="float: left;">
<p style="text-align: center;"> {metric} </p>
<img src="/graphs.php?metrics={metric}&servers={servers}&cluster={cluster}{options}" alt={metric} /> </li>
</td>
<!-- END BLOCK : index -->
</tr>
</tbody>
</table>


</div>
<!-- END BLOCK : group -->


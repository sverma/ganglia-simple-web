<SCRIPT TYPE="text/javascript"><!--
// Script taken from: http://www.netlobo.com/div_hiding.html
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
--></SCRIPT>
<SCRIPT TYPE="text/javascript"><!--
  $(document).ready(function() {
    $("#tabs").tabs();
<!-- START BLOCK : ajax_req -->
    $('#{group}_panel').html('{group}_panel <br> <img src="../images/spinner.gif" alt="Wait" /> Loading ....');
    $.get("inner_panel.php?servers={servers}&metrics_group={group_un}&metric={metric}&cluster={cluster}{options}" , function(data) { 
        $('#{group}').html(data);
        $("#{group}_panel").html('"{group}" <a href="/inner_panel.php?servers={servers}&metrics_group={group_un}&metric={metric}&cluster={cluster}{options}" target="_blank"> <div style="text-align:right;"> Detach Panel  </div>') ; 
    });
<!-- END BLOCK : ajax_req --> 
  });
--></SCRIPT>
<style type="text/css">
  .toggler { width: 500px; height: 200px; }
  #button { padding: .15em 1em; text-decoration: none; }
  #effect { width: 240px; height: 135px; padding: 0.4em; position: relative; }
  #effect h3 { margin: 0; padding: 0.4em; text-align: center; }

</style>
<BR>
<BR>
<HR style="width=1px">
<!-- START BLOCK : group -->
<div style="background-color:#336699;padding:10px;text-align:center;color:#ffffff;size-indent:10px;text-transform:uppercase;" ONMOUSEDOWN="javascript:toggleLayer('{group}');" TITLE="Toggle {group} metrics group on/off" id="{group}_panel" >
{group} 
</div>
<div id="{group}" style="padding:10px;">
{group} metrics will come here 
</div>
<HR>
<!-- END BLOCK : group -->

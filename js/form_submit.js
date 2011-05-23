$(document).ready(function() { 
    $('#graphit').ajaxForm( { target: '#grapharea' , 
        beforeSubmit: showRequest, 
    }) ; 
}) ; 

function showRequest( formData , jqform , options ) { 
    $('#grapharea').html('<div style="text-align:center;"> <hr> <br> <img src="../images/spinner.gif" alt="Wait" /> Loading .... </div>');
}

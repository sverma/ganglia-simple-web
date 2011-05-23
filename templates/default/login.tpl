<html>
<head>
<title> Directi Monitoring Login Page </title>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script>
$(document).ready( function () { 
$("#login_form").submit(function()
{
        //remove all the class add the messagebox classes and start fading
        $("#msgbox").removeClass().addClass('messagebox').text('Validating....').fadeIn(1000);
        //check the username exists or not from ajax
        $.post("../lib/ajax_login.php",{ user_name:$('#username').val(),password:$('#password').val(),rand:Math.random() } ,function(data)
        {
          if(data=='yes') //if correct login detail
          {
                $("#msgbox").fadeTo(200,0.1,function()  //start fading the messagebox
                {
                  //add message and change the class of the box and start fading
                  $(this).html('Logging in.....').addClass('messageboxok').fadeTo(900,1,
                  function()
                  {
                     //redirect to secure page
                     document.location='index.php';
                  });
                });
          }
          else if ( data == "no" ) 
          {
                $("#msgbox").fadeTo(200,0.1,function() //start fading the messagebox
                {
                  //add message and change the class of the box and start fading
                  $(this).html('Your login detail is wrong ... Use username and password of your chat client for directi.com').addClass('messageboxerror').fadeTo(900,1);
                });
          } else {
                $("#msgbox").fadeTo(200,0.1,function() //start fading the messagebox
                { 
                    $(this).html(data).addClass('messageboxerror').fadeTo(900,1);
                }) ;
            } 
       });
       return false;//not to post the  form physically
    })
    $("#password").blur(function()
    {
            $("#login_form").trigger('submit');
    });
}); 

</script>
<style>
.messagebox{
 position:absolute;
 width:100px;
 margin-left:30px;
 border:1px solid #c93;
 background:#ffc;
 padding:3px;
}
.messageboxok{
 position:absolute;
 width:auto;
 margin-left:30px;
 border:1px solid #349534;
 background:#C9FFCA;
 padding:3px;
 font-weight:bold;
 color:#008000;
}
.messageboxerror{
 position:absolute;
 width:auto;
 margin-left:30px;
 border:1px solid #CC0000;
 background:#F7CBCA;
 padding:3px;
 font-weight:bold;
 color:#CC0000;
}
</style>
</head>
<body>
<center>
<table>
<thead>
</thead>
<tbody>
    <tr>
        <td> 
            <img src="http://www.thesuperest.com/_img/_heroes/s53_metrics.jpg"> 
        </td>
        <td>
            <center>
                <center> <img src="https://support.directi.com/support/images/directi--logo--whitebg.gif"/> </center>
                <center> <h2 style="color:blue;"> Please enter your chat.pw credentials to Login </h2> </center>
                <form method=" post" =""="" action="" id="login_form" enctype="multipart/form-data"></form>
                    <table>
                        <tbody>
                            <tr>
                            <td> 
                                <p style="text-align: center;">User Name : </p> </td>
                            <td> <input name="username" id="username" value="" maxlength="35" type="text"> </td> 
                            </tr>
                            <tr> 
                            <td> <p style="text-align: center;"> Password : </p> </td>

                            <td> <input name="password" id="password" value="" maxlength="25" type="password"> </td>
                            </tr> <tr> <td> </td> <td> </td>
                             <td colspan=2  > <input name="Submit" id="submit" value="Login" type="submit"> </td> 
                             <td><span id="msgbox" style="display:none"></span> </td> </tr> </center>
                        </tbody>
                    </table>
                </form>
            </center>
        </td>
    </tr>
</tbody>
</table>
</center>

</body>

</html>

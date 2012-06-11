$(document).ready(function() { 
    $.getJSON('api/webservice.php?list=clusters' , function(data) {  
        var items = [] ;
        items.push('<option value="' + "All" + '">' + "All" + '</option>' ) ;
        $.each(data, function(key, val) { 
            items.push('<option value="' + val + '">' + val + '</option>' ) ;
        }); 
        $("#cluster").html(items.join('')); 
        
    });
    $('#cluster').change(function() {
        var str ="" ; 
        $("#cluster option:selected").each( function () { 
            str += $(this).text() ; 
        });
        var url = "" ; 
        if ( str == "All" )  { 
            url = 'api/webservice.php?list=servers' ;
        } else { 
            url = 'api/webservice.php?list=servers&clusters=' + str ;
        }
        $.getJSON( url , function(data) {
            var items = [] ;
            $.each(data, function(key, val) {
                var len = val.length ; 
                items.push('<option value="' + "All" + '">' + "All" + '</option>' ) ;
                for ( var i = 0 ; i < len ; i++ ) { 
                    items.push('<option value="' + val[i] + '">' + val[i] + '</option>' ) ;
                }
            });
            $("#servers").html(items.join(''));
        });
        var url = "" ; 
        if ( str == "All" )  { 
            url = 'api/webservice.php?list=metrics_grp' ;
        } else { 
            url = 'api/webservice.php?list=metrics_grp&clusters=' + str ;
        }

        $.getJSON( url , function(data) {
            var items = [] ;
            $.each(data, function(key, val) {
                var len = val.length ; 
                items.push('<option value="' + "All" + '">' + "All" + '</option>' ) ;
                for ( var i = 0 ; i < len ; i++ ) { 
                    if ( ! val[i].match(/system|boot|core/) ) { 
                        items.push('<option value="' + val[i] + '">' + val[i] + '</option>' ) ;
                    }
                }
            });
            $("#metrics_group").html(items.join(''));
        }); 
        var url = "" ; 
        if ( str == "All" )  { 
            url = 'api/webservice.php?list=metrics' ;
        } else { 
            url = 'api/webservice.php?list=metrics&clusters=' + str ;
        }

        $.getJSON( url , function(data) {
            var items = [] ;
            $.each(data, function(key, val) {
                var len = val.length ; 
                items.push('<option value="' + "All" + '">' + "All" + '</option>' ) ;
                for ( var i = 0 ; i < len ; i++ ) { 
                    items.push('<option value="' + val[i] + '">' + val[i] + '</option>' ) ;
                }
            });
            $("#metrics").html(items.join(''));
         });
    });
    $('#metrics_group').change(function() { 
        var str ="" ;
        $("#metrics_group option:selected").each( function () {
            str += $(this).text() ;
        });
        var url = "" ; 
        if ( str == "All" )  { 
            url = 'api/webservice.php?list=metrics' ;
        } else { 
            url = 'api/webservice.php?list=metrics&metrics_grp=' + str ;
        }
        $.getJSON( url , function(data) {
            var items = [] ;
            $.each(data, function(key, val) {
                var len = val.length ; 
                items.push('<option value="' + "All" + '">' + "All" + '</option>' ) ;
                for ( var i = 0 ; i < len ; i++ ) { 
                    items.push('<option value="' + val[i] + '">' + val[i] + '</option>' ) ;
                }
            });
            $("#metrics").html(items.join(''));
        });
    }); 
});
                        

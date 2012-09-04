<?php 
function parse_time_str($s) 
{
    $tokens = str_split($s);
    $c = count($tokens);
    $stack = array(); 
    for ( $i=0;$i<$c;$i++ ) { 
        switch($tokens[$i]) {
            case '(' : 
                $stack[] = $tokens[$i];
                break;
            case ')' : 
                $r = array_pop($stack);
                array_pop($stack);
                $stack[] = $r;
                break;
            case 'c' : 
                $time = time();
                $stack[] = $time;
                break;
            default : 
                if ( preg_match('/\-|\+/' , $tokens[$i] ) ) { 
                    $op = $tokens[$i++];
                    if ( count($stack) >= 1  ) {
                        $a = array_pop($stack);
                    }else { 
                        $a = time(); 
                    }
                    $b = $tokens[$i];
                    $mul = 1 ; 
                    if ( preg_match('/\d{1}/', $b) ) { 
                        $mul = $b; 
                        $i++;
                        $b = $tokens[$i]; 
                    }
                    switch($b) { 
                        case 'm': 
                            $r = 60;
                            break;
                        case 'h': 
                            $r = 60*60;
                            break;
                        case 'd': 
                            $r = 60*60*24;
                            break;
                        case 'w' : 
                            $r = 60*60*24*7;
                            break;
                        case 'M' : 
                            $r = 60*60*24*30;
                            break;
                        case 'Y' : 
                            $r = 60*60*24*365;
                            break;
                        default: 
                            $r = 60*60; 
                            break;
                    }
                    $r = $mul * $r; 
                    switch ($op) { 
                        case '-': 
                            $stack[] = $a-$r;
                            break;
                        case '+':
                            $stack[] = $a+$r;
                            break;
                         default: 
                            break;
                    }
                }
            }    
        }   
    if ( count($stack) == 1 ) { 
        return $stack[0]; 
    } else { 
#Fill up error information here
        return -1;
    }
}


function test_parse_time() 
{
    $in = array('(c-d)+h+d-h' , 'c' , 'c-m' , '(c-1w)' , '(c-c)' , 'c-d' , '-d' , '-2h' , '-3h' , '-4h' , 'c-w' , '-w' ); 
    for($i=0;$i<count($in);$i++){
        $time = parse_time_str($in[$i]); 
        if ( $time != -1 ) { 
            echo "PASS: $time ( $in[$i] )\n"; 
        }else { 
            echo "FAIL: $in[$i] \n"; 
        }
            
    }
}
                     
test_parse_time();         

?>



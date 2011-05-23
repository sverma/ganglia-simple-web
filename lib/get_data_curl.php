<?
class get_data_curl { 
    protected $url ; 
    private $timeout ; 
    public function __construct () { 
        $this->url = "" ; 
        $this->timeout = 5 ; 
    } 
    public function get_data ( $url ) { 
        $this->url = $url ; 
        $ch  = curl_init () ;
        curl_setopt($ch,CURLOPT_URL,$this->url);
        curl_setopt($ch, CURLOPT_HEADER, false );
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$this->timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
        
}
?>

<?php
	
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Customer;
use JWTAuth;
use Auth;
use App\PrestaShopWebservice;
define('_PS_MYSQL_REAL_ESCAPE_STRING_', function_exists('mysql_real_escape_string'));define('_PS_MAGIC_QUOTES_GPC_', get_magic_quotes_gpc());
/*** Convert \n to 
* @param string $string String to transform* @return string New string*/function nl2br2($string){return str_replace(array("\r\n", "\r", "\n"), '
', $string);}/* Sanitize data which will be injected into SQL query** @param string $string SQL data which will be injected into SQL query* @param boolean $htmlOK Does data contain HTML code ? (optional)* @return string Sanitized data*/function pSQL($string, $htmlOK = false){    if (_PS_MAGIC_QUOTES_GPC_)        $string = stripslashes($string);    if (!is_numeric($string))    {    $con=mysqli_connect("localhost","root","","handecraft");       $string = _PS_MYSQL_REAL_ESCAPE_STRING_ ? mysqli_real_escape_string($con,$string) : addslashes($string);        if (!$htmlOK)            $string = strip_tags(nl2br2($string));   mysqli_close($con); }     return $string;}/*** Encrypt password** @param object $object Object to display*/function encrypt($passwd){    return md5(pSQL($passwd));}/*Ngk2AO1iQSjZDC9MbUpEYmn0Za13swCcJHO7Bek3UL0MrjKZFizE9Rxy -> Defined in /config/settings.inc.php _COOKIE_KEY_ constant*/
class AuthController extends Controller
{	
	 
	protected function err(){
		return '<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"><errors><error><message><![CDATA[Error. You are not authenticated to access this url .]]></message></error></errors></prestashop>';
	}

	protected function err1(){
		return '<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"><errors><error><message><![CDATA[Error. ';
	}

	protected function err2(){
		return ' .]]></message></error></errors></prestashop>';
	}

	protected function str1(){
		return '<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink">';
	}

	protected function str2(){
		return '</prestashop>';
	}

    public function login(Request $request) {
     	$zuzu= encrypt("EZqgFyDAUujZwGGeT0iBPM4kX2e1WY9hU0mWV49wKbjv2OGOmAaaI5iC".$request->get('passwd'));
        $user=Customer::where('email', '=', $request->get('email'))->where('passwd', '=', substr($zuzu,stripos($zuzu,' ')))->first();
        if($user){
        $token = JWTAuth::fromUser($user);
        // all good so return the token
        return response($this->str1().'<token><![CDATA['.$token.']]></token>'.$this->str2())->header('Content-Type', 'text/xml');
		}
		return response($this->err1().'invalid_credentials'.$this->err2(),401)->header('Content-Type', 'text/xml');
        // all good so return the token
        
    }

  	public function logout(Request $request) {
        JWTAuth::invalidate($request->input('token'));
        return response($this->str1().'<status><![CDATA[Logged Out]]></status>'.$this->str2())->header('Content-Type', 'text/xml');
    }

    public function getCustomers(Request $request){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'customers';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else
 		{	$user = JWTAuth::parseToken()->authenticate();
 			$opt['id'] = $user->id_customer;
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

    public function editCustomers(Request $request){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
    	$user = JWTAuth::parseToken()->authenticate();
 		$opt['resource'] = 'customers';
 		$opt['id'] = $user->id_customer;
 		$xml = $webService->get($opt);
		$resources = $xml->children()->children();
		foreach ($resources as $nodeKey => $node) {
    		$resources->$nodeKey = $_POST[$nodeKey];
		}
		$opt['putXml'] = $xml->asXML();
		$xml = $webService->edit($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
    }

    public function postCustomers(Request $request){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'customers';
 		$opt['schema'] = 'blank';
 		$xml = $webService->get($opt);
		$resources = $xml->children()->children();
		foreach ($resources as $nodeKey => $node) {
    		$resources->$nodeKey = $_POST[$nodeKey];
		}
		unset($opt['schema']);
		$opt['postXml'] = $xml->asXML();
		$xml = $webService->add($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
    }

    public function deleteCustomers(Request $request){
    	try {
    // Create an instance
    $webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
    $user = JWTAuth::parseToken()->authenticate();
    $opt['resource'] = 'customers';            // Resource to use
    $opt['id'] = $user->id_customer;                             // ID to use
    $webService->delete($opt);                 // Delete
    // If we can see this message, that means we have not left the try block
    return response($this->str1().'<message><![CDATA['.'Customer '.$opt['id'].' successfully deleted!'.']]></message>'.$this->str2())->header('Content-Type', 'text/xml');
		}
		catch (PrestaShopWebserviceException $ex) {
   			 $trace = $ex->getTrace();                // Retrieve all info on this error
   			 $errorCode = $trace[0]['args'][0];       // Retrieve error code
    		if ($errorCode == 401)
    			return response($this->err1().'Bad auth key'.$this->err2())->header('Content-Type', 'text/xml');
    		else
    			return response($this->err1().$ex->getMessage().$this->err2())->header('Content-Type', 'text/xml');
    // Display error message{color}
		}
    }

    public function getProducts(Request $request,$id=null){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'products';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else{
 		$opt['id']=$id;
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

    public function getProduct_options(Request $request,$id=null){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'product_options';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else{
 		$opt['id']=$id;
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

    public function getProduct_option_values(Request $request,$id=null){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'product_option_values';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else{
 		$opt['id']=$id;
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

        public function getProduct_features(Request $request,$id=null){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'product_features';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else{
 		$opt['id']=$id;
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

        public function getProduct_feature_values(Request $request,$id=null){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'product_feature_values';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else{
 		$opt['id']=$id;
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

    public function getCombinations(Request $request,$id=null){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'combinations';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else{
 		$opt['id']=$id;
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

    public function getAddresses(Request $request,$id=null){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'addresses';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else
 		{	
 			$user = JWTAuth::parseToken()->authenticate();
 			if($id==null){
 			$opt['filter[id_customer]'] = '['.$user->id_customer.']';
 			}
 			else{
 			$opt['filter[id_customer]'] = '['.$user->id_customer.']';
 			$opt['filter[id]'] = '['.$id.']';
 			$xml = $webService->get($opt);
 			$temp=$xml->children()->children();
 			if(!$temp){
 					return response($this->err())->header('Content-Type', 'text/xml');
 				}
 			else{	
 					unset($opt['filter[id_customer]']);
 					unset($opt['filter[id_customer]']);
 					$opt['id'] = $id;
 				}
 			}
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

    public function editAddresses(Request $request,$id){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
    	$user = JWTAuth::parseToken()->authenticate();
 		$opt['resource'] = 'addresses';
 		$opt['filter[id_customer]'] = '[1]';
 			$opt['filter[id]'] = '['.$id.']';
 			$xml = $webService->get($opt);
 			$temp=$xml->children()->children();
 			if(!$temp){
 					return response($this->err())->header('Content-Type', 'text/xml');
 				}
 			else{	
 					unset($opt['filter[id_customer]']);
 					unset($opt['filter[id_customer]']);
 					$opt['id'] = $id;
 				}
 		$xml = $webService->get($opt);
		$resources = $xml->children()->children();
		foreach ($resources as $nodeKey => $node) {
    		$resources->$nodeKey = $_POST[$nodeKey];
		}
		$opt['putXml'] = $xml->asXML();
		$xml = $webService->edit($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
    }

    public function postAddresses(Request $request){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
    	$user = JWTAuth::parseToken()->authenticate();
 		$opt['resource'] = 'addresses';
 		$opt['schema'] = 'blank';
 		$xml = $webService->get($opt);
		$resources = $xml->children()->children();
		foreach ($resources as $nodeKey => $node) {
    		$resources->$nodeKey = $_POST[$nodeKey];
		}
		unset($opt['schema']);
		$resources->id_customer = $user->id_customer;
		$opt['postXml'] = $xml->asXML();
		$xml = $webService->add($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
    }

    public function deleteAddresses(Request $request,$id){
    	try {
    // Create an instance
    $webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
    $user = JWTAuth::parseToken()->authenticate();
    $opt['resource'] = 'addresses';    
    $opt['filter[id_customer]'] = '[1]';
 			$opt['filter[id]'] = '['.$id.']';
 			$xml = $webService->get($opt);
 			$temp=$xml->children()->children();
 			if(!$temp){
 					return response($this->err())->header('Content-Type', 'text/xml');
 				}
 			else{	
 					unset($opt['filter[id_customer]']);
 					unset($opt['filter[id_customer]']);
 					$opt['id'] = $id;
 					$webService->delete($opt);                 // Delete
   					// If we can see this message, that means we have not left the try block
   					return response($this->str1().'<message><![CDATA['.'Address'.$opt['id'].' successfully deleted!'.']]></message>'.$this->str2())->header('Content-Type', 'text/xml');
 				}        // Resource to use
    
		}
		catch (PrestaShopWebserviceException $ex) {
   			 $trace = $ex->getTrace();                // Retrieve all info on this error
   			 $errorCode = $trace[0]['args'][0];       // Retrieve error code
    		if ($errorCode == 401)
        		return response($this->err1().'Bad auth key'.$this->err2())->header('Content-Type', 'text/xml');  
    		else
    			return response($this->err1().$ex->getMessage().$this->err2())->header('Content-Type', 'text/xml');
    // Display error message{color}
		}
    }

    public function getCarts(Request $request,$id=null){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'carts';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else
 		{	
 			$user = JWTAuth::parseToken()->authenticate();
 			if($id==null){
 			$opt['filter[id_customer]'] = '['.$user->id_customer.']';
 			}
 			else{
 			$opt['filter[id_customer]'] = '['.$user->id_customer.']';
 			$opt['filter[id]'] = '['.$id.']';
 			$xml = $webService->get($opt);
 			$temp=$xml->children()->children();
 			if(!$temp){
 					return response($this->err())->header('Content-Type', 'text/xml');
 				}
 			else{	
 					unset($opt['filter[id_customer]']);
 					unset($opt['filter[id_customer]']);
 					$opt['id'] = $id;
 				}
 			}
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

    public function editCarts(Request $request,$id){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
    	$user = JWTAuth::parseToken()->authenticate();
 		$opt['resource'] = 'carts';
 		$opt['filter[id_customer]'] = '['.$user->id_customer.']';
 			$opt['filter[id]'] = '['.$id.']';
 			$xml = $webService->get($opt);
 			$temp=$xml->children()->children();
 			if(!$temp){
 					return response($this->err())->header('Content-Type', 'text/xml');
 				}
 			else{	
 					unset($opt['filter[id_customer]']);
 					unset($opt['filter[id_customer]']);
 					$opt['id'] = $id;
 				}
 		$xml = $webService->get($opt);
		$resources = $xml->children()->children();
		foreach ($resources as $nodeKey => $node) {
    		$resources->$nodeKey = $_POST[$nodeKey];
		}
		$opt['putXml'] = $xml->asXML();
		$xml = $webService->edit($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
    }

    public function postCarts(Request $request){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
    	$user = JWTAuth::parseToken()->authenticate();
 		$opt['resource'] = 'carts';
 		$opt['schema'] = 'blank';
 		$xml = $webService->get($opt);
		$resources = $xml->children()->children();
		foreach ($resources as $nodeKey => $node) {
    		$resources->$nodeKey = $_POST[$nodeKey];
		}
		unset($opt['schema']);
		$resources->id_customer = $user->id_customer;
		$opt['postXml'] = $xml->asXML();
		$xml = $webService->add($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
    }

    public function deleteCarts(Request $request,$id){
    	try {
    // Create an instance
    $webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
    $user = JWTAuth::parseToken()->authenticate();
    $opt['resource'] = 'carts';    
    $opt['filter[id_customer]'] = '['.$user->id_customer.']';
 			$opt['filter[id]'] = '['.$id.']';
 			$xml = $webService->get($opt);
 			$temp=$xml->children()->children();
 			if(!$temp){
 					return response($this->err())->header('Content-Type', 'text/xml');
 				}
 			else{	
 					unset($opt['filter[id_customer]']);
 					unset($opt['filter[id_customer]']);
 					$opt['id'] = $id;
 					$webService->delete($opt);                 // Delete
   					// If we can see this message, that means we have not left the try block
   					return response($this->str1().'<message><![CDATA['.'Cart'.$opt['id'].' successfully deleted!'.']]></message>'.$this->str2())->header('Content-Type', 'text/xml');
 				}        // Resource to use
    
		}
		catch (PrestaShopWebserviceException $ex) {
   			 $trace = $ex->getTrace();                // Retrieve all info on this error
   			 $errorCode = $trace[0]['args'][0];       // Retrieve error code
    		if ($errorCode == 401)
				return response($this->err1().'Bad auth key'.$this->err2())->header('Content-Type', 'text/xml');    
    		else
    			return response($this->err1().$ex->getMessage().$this->err2())->header('Content-Type', 'text/xml');
    // Display error message{color}
		}
    }

     public function getOrder_states(Request $request,$id=null){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'order_states';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else{
 		$opt['id']=$id;
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

     public function getOrders(Request $request,$id=null){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
 		$opt['resource'] = 'orders';
 		if(isset($_GET['schema'])){
 			$opt['schema']=$request->get('schema');
 		}
 		else
 		{	
 			$user = JWTAuth::parseToken()->authenticate();
 			if($id==null){
 			$opt['filter[id_customer]'] = '['.$user->id_customer.']';
 			}
 			else{
 			$opt['filter[id_customer]'] = '['.$user->id_customer.']';
 			$opt['filter[id]'] = '['.$id.']';
 			$xml = $webService->get($opt);
 			$temp=$xml->children()->children();
 			if(!$temp){
 					return response($this->err())->header('Content-Type', 'text/xml');
 				}
 			else{	
 					unset($opt['filter[id_customer]']);
 					unset($opt['filter[id_customer]']);
 					$opt['id'] = $id;
 				}
 			}
 		}
 		$xml = $webService->get($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
		//return response()->json(['response'=> $xml->asXML()]);
    }

     public function postOrders(Request $request){
    	$webService = new PrestaShopWebservice('http://localhost/prestashop', 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6', false);
    	$user = JWTAuth::parseToken()->authenticate();
 		$opt['resource'] = 'orders';
 		$opt['schema'] = 'blank';
 		$xml = $webService->get($opt);
		$resources = $xml->children()->children();
		foreach ($resources as $nodeKey => $node) {
    		$resources->$nodeKey = $_POST[$nodeKey];
		}
		unset($opt['schema']);
		$resources->id_customer = $user->id_customer;
		$opt['postXml'] = $xml->asXML();
		$xml = $webService->add($opt);
		return response($xml->asXML())->header('Content-Type', 'text/xml');
    }

    public function getImages(Request $request){
		$curlConfig = array(
			CURLOPT_HEADER => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLINFO_HEADER_OUT => TRUE,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD => 'F2HNYUKELWEXXJYD5R7VBXBYB8L4Q5R6'.':',
			CURLOPT_HTTPHEADER => array( 'Expect:' )
		);
		$ch = curl_init("http://localhost/prestashop/api/".substr($request->url(),34));
		curl_setopt_array($ch, $curlConfig);
		$response = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		curl_close($ch);
		$matches = $this->http_parse_headers($header);
		return response($body)->header('Content-Type',$matches['Content-Type']);
    }

    function http_parse_headers($raw_headers)
    {
        $headers = array();
        $key = ''; // [+]

        foreach(explode("\n", $raw_headers) as $i => $h)
        {
            $h = explode(':', $h, 2);

            if (isset($h[1]))
            {
                if (!isset($headers[$h[0]]))
                    $headers[$h[0]] = trim($h[1]);
                elseif (is_array($headers[$h[0]]))
                {
                    // $tmp = array_merge($headers[$h[0]], array(trim($h[1]))); // [-]
                    // $headers[$h[0]] = $tmp; // [-]
                    $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1]))); // [+]
                }
                else
                {
                    // $tmp = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [-]
                    // $headers[$h[0]] = $tmp; // [-]
                    $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [+]
                }

                $key = $h[0]; // [+]
            }
            else // [+]
            { // [+]
                if (substr($h[0], 0, 1) == "\t") // [+]
                    $headers[$key] .= "\r\n\t".trim($h[0]); // [+]
                elseif (!$key) // [+]
                    $headers[0] = trim($h[0]);trim($h[0]); // [+]
            } // [+]
        }

        return $headers;
    }

}

<?php   
	//URL of the Pydio start page.          
    $REDIRECTURL = "https://pydio.sztaki.hu";
    //Processing the parameters from Shibboleth.
    $eppn = $_SERVER['eppn'];
    $roles = array();
    if(isset($_SERVER['entitlement'])){
    $epes = $_SERVER['entitlement'];
    $entitlements = explode(';',$epes);
    $roles = array();
    foreach ($entitlements as $entitlement) {
    	$rr = explode(':',$entitlement);
    	$role = $rr[count($rr)-1];
    	array_push($roles, $role);
    }
    }
    $email = $_SERVER['mail'];
    $name = $_SERVER['displayName'];

	define("AJXP_EXEC", true);
	$glueCode = dirname(__FILE__)."/authentication.php";
	$secret = "aaa";

	// Initialize the "parameters holder"
	global $AJXP_GLUE_GLOBALS;
	$AJXP_GLUE_GLOBALS = array();
	$AJXP_GLUE_GLOBALS["secret"] = $secret;
	$AJXP_GLUE_GLOBALS["plugInAction"] = "login";
    $AJXP_GLUE_GLOBALS["login"] = array("name" => $eppn, "password" => "nincslokaljelszo","roles" => $roles, "fullname" => $name, "email" => $email, );
	// NOW call glueCode!
   	include($glueCode);
    header('Location: '.$REDIRECTURL);
?>

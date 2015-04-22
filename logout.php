<?php  
	$glueCode = dirname(__FILE__)."/authentication.php";
	$secret = "aaa";
	define('AJXP_EXEC', true);

	global $AJXP_GLUE_GLOBALS;
	$AJXP_GLUE_GLOBALS = array();
	$AJXP_GLUE_GLOBALS["secret"] = $secret;
	$AJXP_GLUE_GLOBALS["plugInAction"] = "logout";
   	include($glueCode);
   	global $LOGOUT_REDIRECT;
   	header('Location: '. $LOGOUT_REDIRECT);
?>
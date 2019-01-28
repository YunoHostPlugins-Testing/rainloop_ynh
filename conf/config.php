<?php

if (!function_exists('ereg')) {
   function ereg($pattern, $subject, &$matches = array())
   {
       return preg_match('/'.$pattern.'/', $subject, $matches);
   }
}

function arguments($argv) {
	$_ARG = array();
	foreach ($argv as $arg) {
		if (ereg('--([^=]+)=(.*)',$arg,$reg)) {
			$_ARG[$reg[1]] = $reg[2];
		} elseif(ereg('^-([a-zA-Z0-9])',$arg,$reg)) {
			$_ARG[$reg[1]] = 'true';
		} else {
			$_ARG['input'][]=$arg;
		}
	}
	return $_ARG;
}

// get args:
$args = arguments($argv);

$_ENV['RAINLOOP_INCLUDE_AS_API'] = true;
include $args['index'];

$oConfig = \RainLoop\Api::Config();
$oConfig->SetPassword($args['password']);
echo $oConfig->Save() ? 'Admin password updated' : 'Admin password not updated';

?>

<?php
/*
Content Pay by Zong
brought to you by : Zong - http://developer.zong.com
written by: Adrien Kolly (adrien DOT kolly AT echovox DOT com)

Copyright (C) 2008 developer.zong.com 

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


require_once('../../../wp-config.php' );
require_once('../../../wp-includes/wp-db.php' );
$contentpaybyzong_option = get_option('contentpaybyzong_options');

global $contentpaybyzong_option;

$apikey = urlencode($contentpaybyzong_option['apikey']);
$serviceId = urlencode($contentpaybyzong_option['serviceid']);

$url = "https://api.zong.com/zapi/v1/service/oneoff/?method=get&api_key=$apikey&service_id=$serviceId";
$session = curl_init($url);

curl_setopt($session, CURLOPT_URL,$url); 
curl_setopt($session, CURLOPT_RETURNTRANSFER,true); 
curl_setopt($session, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
curl_setopt($session, CURLOPT_POST, false);
curl_setopt($session, CURLOPT_HEADER, false); 
curl_setopt($session, CURLOPT_TIMEOUT,10); 
curl_setopt ($session, CURLOPT_FRESH_CONNECT, true);
curl_setopt ($session, CURLOPT_SSL_VERIFYPEER,false);
curl_setopt ($session, CURLOPT_SSL_VERIFYHOST,false);

$xml = curl_exec($session);
// Debug code
if($xml === false){
	// Show failure info.
	echo('cURL connection - <strong>ERROR</strong> - curl_exec() failed<br />');
	echo('Reason: '.curl_error($session).'<br />');
	$curlVersion = curl_version();
}else{
	echo('cURL connection - <strong>SUCCESS</strong><br />');
	if(function_exists('simplexml_load_string')){
		$serviceXML = simplexml_load_string($xml);
		echo('Load simpleXML - <strong>SUCCESS</strong><br />');
		if((string)$serviceXML['code']=='0'){
			echo('Parse response - <strong>SUCCESS</strong><br />');	
		}else{
			echo('Parse response - <strong>ERROR</strong> - Please configure your correctly your "Zong Developer Network Options".<br />');	
		}	
	}else{
		echo('Load simpleXML - <strong>FAILED</strong> - simpleXML is not available. Please upgrade your version of PHP<br />');
	}
}
curl_close($session);

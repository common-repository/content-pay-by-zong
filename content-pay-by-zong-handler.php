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

        header('Content-type: application/xml');
        require_once('../../../wp-config.php' );
        require_once('../../../wp-includes/wp-db.php' );
		$contentpaybyzong_option = get_option('contentpaybyzong_options');
		
function generatePassword($length=5,$alphanumeric=1) {  
	$password = '';
	if((string)$alphanumeric==(string)1){
		$vowels = 'aeuyAEUY';
		$consonants = 'bdghjmnpqrstvzBDGHJLMNPQRSTVWXZ23456789';
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++) {
			if ($alt == 1) {
				$password .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			} else {
				$password .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}
	}else{
		$stringchar = '1234567890';
		for ($i = 0; $i < $length; $i++) {
			$password .= $stringchar[(rand() % strlen($stringchar))];
		}
	}
	return $password;
}

function savePassword(){
	global $wpdb;
	$tablePassword = $wpdb->prefix."contentpaybyzong_passwords";
	$optionarray_def = get_option('contentpaybyzong_options');
	$numberOfDay = $optionarray_def['numberOfDay'];
	$passwordLength = (empty($optionarray_def['passwordLength'])) ? "8" : $optionarray_def['passwordLength']; 
	$alphanumeric = (empty($optionarray_def['alphanumeric'])) ? "0" : $optionarray_def['alphanumeric']; 

	$nextDay = time() + $numberOfDay*(24*60*60);
	// Doesn't allow to have twice the same password
	// But try 5 times before sending an error message and logging it
	$i=0;
	do{
		$password = generatePassword($passwordLength,$alphanumeric); 
		// BINARY  -> case sensitive
		$var = $wpdb->get_results("SELECT id FROM $tablePassword WHERE BINARY password = '$password'");
		$i++; 
	}while(!empty($var) && $i<5);
	
	if($i==5){
		$password = false;
		error_log('Content Pay by Zong - ERROR -'.strftime("%d %b %Y %H:%M:%S ").'-'.'Failed to generate a new password. msisdn was '.$_GET['msisdn']);
	}else{
		$date = date("Y-m-d H:i:s", $nextDay);
		if(!$wpdb->query("	INSERT INTO $tablePassword (id ,password ,end_date)
							VALUES (NULL , '$password', '$date')")){
			error_log('Content Pay by Zong - ERROR -'.strftime("%d %b %Y %H:%M:%S ").'-'.'Failed to add the password in the database. msisdn was '.$_GET['msisdn']);
		}
	}
	return $password;
}



if( urldecode((string)$_GET['secretKey']) == (string)$contentpaybyzong_option['secretKey'] ){
	if($password = savePassword()){
		$message = 'Your password: '.$password; 
	}else{
		$message = "Please contact the webmaster, can't create a new password.";
	}
}else{
	$message = "Please contact the webmaster, Secret Key doesn't match.";
	error_log('Content Pay by Zong - ERROR - '.strftime("%d %b %Y %H:%M:%S ").'-'.' Secret Key does not match');
	error_log('Content Pay by Zong - ERROR - Secret Key sended: '.$_GET['secretKey']);
	error_log('Content Pay by Zong - ERROR - Secret Key defined in Content Pay by Zong plugin: '.$contentpaybyzong_option['secretKey']);
}

echo '<?xml version="1.0" encoding="UTF-8" ?>
<oneoff_response fragmentable="false" reverse_charged="true">
<text_content>'.$message.'</text_content>
</oneoff_response>';
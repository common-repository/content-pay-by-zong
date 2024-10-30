<?php
/* 
Plugin Name: Content Pay by Zong
Plugin URI: http://developer.zong.com/forum/viewforum.php?f=7
Description: Content Pay by Zong allows you to monetize your blog content using Premium text messaging.
Version: 0.1.8
Author: Adrien Kolly
Mail: adrien.kolly@echovox.com
Author URI: http://developer.zong.com/forum/viewforum.php?f=7
*/ 

/*
Copyright (C) 2008 developer.zong.com (adrien DOT kolly AT echovox DOT com)

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

//----------------------------------------------------------------------------
//		SETUP FUNCTIONS & GLOBAL VARIABLES
//----------------------------------------------------------------------------

require_once(ABSPATH."wp-content/plugins/content-pay-by-zong/content-pay-by-zong-database.php");
register_activation_hook(__FILE__,'contentpaybyzong_install');
register_activation_hook(__FILE__,'contentpaybyzong_setup_options');

//Content Pay by Zong Options
$contentpaybyzong_option = get_option('contentpaybyzong_options');

//Detect WordPress version to add compatibility with 2.5 or higher
$wpversion_full = get_bloginfo('version');
$wpversion = preg_replace('/([0-9].[0-9])(.*)/', '$1', $wpversion_full); //Boil down version number to X.X

//----------------------------------------------------------------------------
//	Setup Default Settings
//----------------------------------------------------------------------------

function contentpaybyzong_setup_options()
{
	$contentpaybyzong_latestVersion = "0.1.8";
	$contentpaybyzong_option = get_option('contentpaybyzong_options');
	$contentpaybyzong_version = get_option('contentpaybyzong_version'); //Content Pay by Zong Version Number
	if((string)$contentpaybyzong_version != (string)$contentpaybyzong_latestVersion && !empty($contentpaybyzong_option)){
		$optionarray_def = array(
			'apikey'          => $contentpaybyzong_option['apikey'],
			'level'           => $contentpaybyzong_option['level'],
			'serviceid'       => $contentpaybyzong_option['serviceid'],
			'numberOfDay'     => $contentpaybyzong_option['numberOfDay'],
			'passwordLength'  => $contentpaybyzong_option['passwordLength'],
			'alphanumeric'    => $contentpaybyzong_option['alphanumeric'],
			'secretKey'       => '',
		);
		update_option("contentpaybyzong_options", $optionarray_def);
		update_option('contentpaybyzong_version',$contentpaybyzong_latestVersion); //Content Pay by Zong Version Number
		error_log('Content Pay by Zong -'.strftime("%d %b %Y %H:%M:%S ").'- Version updated to '.$contentpaybyzong_latestVersion.'. Old version: '.$contentpaybyzong_version);
	}else{
		// Setup Default Options Array
		$optionarray_def = array(
			'apikey'         => 'My API key',
			'level'          => '10',
			'serviceid'      => '0',
			'numberOfDay'    => '1',
			'passwordLength' => '5',
			'alphanumeric'   => '1',
			'secretKey'      => '',
		);
		add_option("contentpaybyzong_options", $optionarray_def);
		add_option("contentpaybyzong_version", $contentpaybyzong_latestVersion);
		error_log('Content Pay by Zong -'.strftime("%d %b %Y %H:%M:%S ").'- New version '.$contentpaybyzong_latestVersion.'. Old version: '.$contentpaybyzong_version);
	}
	
	// Set the error to true to check the config on install
	add_option("contentpaybyzong_error", 1);
	update_option("contentpaybyzong_error", 1);
	
}

//--------------------------------------------------------------------------
//	Plugin core
//--------------------------------------------------------------------------

if (get_option("contentpaybyzong_error")) {

	function contentpaybyzong_supportSimpleXML(){
		if(function_exists('simplexml_load_string')){
			return true;	
		}else{
			return false;
		}
	}
	/**
     * Does this fetcher support SSL URLs?
     *  @author JanRain, Inc. <openid@janrain.com>
     */
	function contentpaybyzong_supportsSSLl()
	    {
	        $v = curl_version();
	        if(is_array($v)) {
	            return in_array('https', $v['protocols']);
	        } elseif (is_string($v)) {
	            return preg_match('/OpenSSL/i', $v);
	        } else {
	            return 0;
	        }
		}
	
	if(!contentpaybyzong_supportsSSLl() || !contentpaybyzong_supportSimpleXML() || $wpversion < 2.5){
		function contentpaybyzong_adminNotice(){
			global $wpversion, $wpversion_full;
			$message = '<div id="contentpaybyzong-adminNotice" class="updated fade">
					<p>
						<strong>Content Pay by Zong</strong> notice: You have some configuration error.';
			if(!contentpaybyzong_supportSimpleXML()){
				$message .= '<br />SimpleXML is not supported, please upgrade PHP.';
			}			
			if(!contentpaybyzong_supportsSSLl()){
				$message .= '<br />Your cURL version do not support SSL URLs.';
			}
			if($wpversion < 2.5){
				$message .= '<br />Your are using Wordpress version: <strong>'. $wpversion_full .'</strong> and you need at least the version <strong>2.5.x</strong>';
			}
			$message .= '</p></div>';
			echo $message;
		}
		add_action('admin_notices', 'contentpaybyzong_adminNotice');
		update_option("contentpaybyzong_error", 1);
	}else{
		update_option("contentpaybyzong_error", 0);
	}
}

//--------------------------------------------------------------------------
//	Add Admin Page
//--------------------------------------------------------------------------

function contentpaybyzong_addpage()
{
	if (function_exists('add_options_page'))
	{
		add_options_page("contentpaybyzong", 'Content Pay by Zong', 8, basename(__FILE__), 'contentpaybyzong_addMenu');
	}
	if( function_exists( 'add_meta_box' )) {
		add_meta_box( 'contentpaybyzong','Content Pay by Zong', 'contentpaybyzong_addMetaBox','page','advanced');
		add_meta_box( 'contentpaybyzong','Content Pay by Zong', 'contentpaybyzong_addMetaBox','post','advanced');
	}
}
//----------------------------------------------------------------------------
//		PLUGIN FUNCTIONS
//----------------------------------------------------------------------------
function contentpaybyzong_zapi_serviceoneoffget(){
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
	curl_setopt($session, CURLOPT_TIMEOUT,20); 
	curl_setopt ($session, CURLOPT_FRESH_CONNECT, true);
	curl_setopt ($session, CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt ($session, CURLOPT_SSL_VERIFYHOST,false);
	
	$xml = curl_exec($session);

	// Debug code
	if($xml === false){
		// Show failure info.
		
		error_log('Content Pay by Zong - ERROR -'.strftime("%d %b %Y %H:%M:%S ").'- curl_exec() failed');
		error_log('Content Pay by Zong - ERROR - curl_errno() = ' . curl_errno($session));
		error_log('Content Pay by Zong - ERROR - curl_error() = ' . curl_error($session));
		$curlVersion = curl_version();
		error_log('Content Pay by Zong - ERROR - Curl Version: '.$curlVersion['version']);
		error_log('Content Pay by Zong - ERROR - LibZ Version: '.$curlVersion['libz_version']);
	}

	curl_close($session);
	return $xml;
}

function contentpaybyzong_getMarkets(){
	// contentpaybyzong_zapi_serviceoneoffget return false if the cURL failed
	$xml = contentpaybyzong_zapi_serviceoneoffget();
	if($xml==false){
		return array(array('error'=>2));
	}else{
		if(function_exists('simplexml_load_string')){
			$serviceXML = simplexml_load_string($xml);
			if((string)$serviceXML['code']=='0'){
				$mymarkets = array();
				foreach($serviceXML->service->markets->market as $market){
					if((string)$market['status'] == 'ACTIVE'){
						$mykeywords = array();
						foreach($market->keywords->keyword as $keyword){
							array_push($mykeywords, array(	  
								'name'		=>(string)$keyword['name'],
								'shortcode' =>(string)$keyword->shortcode['sc']
								));
						}
						array_push($mymarkets, array(
							'error'		=>0,
							'status'	=>(string)$market['status'],
							'language'	=>(string)$market['language'],
							'country'	=>(string)$market->shortcode['country'],
							'sc'		=>(string)$market->shortcode['sc'],
							'amount'	=>(string)$market->pricepoint['amount'],
							'currency'	=>(string)$market->pricepoint['currency'],
							'keywords'	=>$mykeywords
							));
					}
				}
				return $mymarkets;	
			}else{
				return array(array('error'=>1));
			}	
		}else{
			return array(array('error'=>3));
		}
	}
}

function is_contentpaybyzonged($postId){
	$protectedValues = get_post_custom_values('_contentpaybyzonged', $postId);
	if(!empty($protectedValues)){
		foreach ( $protectedValues as $protected ){
			$protected = $protected;
		}
	}else{
		$protected = '0';
	}
	
	if((string)$protected == '1'){
		$protected = true;
	}else{
		$protected = false;
	}
	return $protected;
}

function contentpaybyzong_allowDisplay($postId){
	global $wpdb, $userdata, $contentpaybyzong_option;
	
	// Check if the user is an admin or an allowed person
	if($userdata->user_level >= $contentpaybyzong_option['level']){
		$display = true;
	}else{
		if(is_contentpaybyzonged($postId)){
			$cookiePassword = $_COOKIE['contentpaybyzong-postpass_' . COOKIEHASH];
			$tablePassword = $wpdb->prefix."contentpaybyzong_passwords";
			$now = strftime("%Y-%m-%d %H:%M:%S");
			// BINARY -> case sensitive
			$var = $wpdb->query("SELECT * FROM $tablePassword WHERE BINARY password = '$cookiePassword' AND end_date >= '$now'");
			if((string)$var == '1'){
				$display = true;
			}else{
				$display = false;
			}
		}else{
			$display = true;
		}
	}
	return $display;
}

//----------------------------------------------------------------------------
//		ADMIN OPTION PAGE FUNCTIONS
//----------------------------------------------------------------------------

function contentpaybyzong_addMenu()
{
	global $wpdb, $wpversion;

	if (isset($_POST['submit']) ) {		
		// Options Array Update
		$passwordLength = ($_POST['passwordLength'] > 16 OR $_POST['passwordLength'] < 1) ? "16" : $_POST['passwordLength']; 
		$optionarray_update = array (
			'keyword' => $_POST['keyword'],
			'level' => $_POST['level'],
			'serviceid' => $_POST['serviceid'],
			'apikey' => $_POST['apikey'],
			'numberOfDay' => $_POST['numberOfDay'],
			'passwordLength' => $passwordLength,
			'alphanumeric' => $_POST['alphanumeric'],
			'secretKey' => $_POST['secretKey'],
		);
		update_option('contentpaybyzong_options', $optionarray_update);
	}
	
	// Get Options to have the updated one
	$contentpaybyzong_option = get_option("contentpaybyzong_options");
?>

	<div class="wrap">
	<h2>Content Pay by Zong Options</h2>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=' . basename(__FILE__); ?>&updated=true">
	<fieldset class="options" style="border: none">
	<p>
	<em>Content Pay by Zong</em> allows you to monetize your blog content using Premium text messaging.<br />
	You'll need an account on <a href="http://developer.zong.com">Zong Developer Network</a>
	</p>
	<h3>Zong Developer Network Options</h3>
	<table width="100%" <?php $wpversion >= 2.5 ? _e('class="form-table"') : _e('cellspacing="2" cellpadding="5" class="editform"'); ?> >
		<tr valign="top">
			<th scope="row">API Key</th> 
			<td colspan="2"><input type="text" name="apikey" value="<?php echo $contentpaybyzong_option['apikey']; ?>" size="35" /><br />
			<span style="color: #555; font-size: .85em;">Specify your API Key (an account on ZDN is needed)</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Service ID</th> 
			<td colspan="2"><input type="text" name="serviceid" value="<?php echo $contentpaybyzong_option['serviceid']; ?>" size="5" /><br />
			<span style="color: #555; font-size: .85em;">Specify the service (on ZDN) that you'll use to monetize your page.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><div id="contentpaybyzong_testconnection_title">Connection test</div></th> 
			<td colspan="2">
				<div id="contentpaybyzong_testconnection_result"></div>
			</td>
		</tr>
	</table>
	<h3>Content Pay by Zong Options</h3>
	<table width="100%" <?php $wpversion >= 2.5 ? _e('class="form-table"') : _e('cellspacing="2" cellpadding="5" class="editform"'); ?> >
		<tr valign="top">
			<th scope="row">Level</th> 
			
			<td colspan="2">
				<select id="level" name="level">
					<option value="10" <?php if($contentpaybyzong_option['level'] == '10'){echo 'selected="selected"';} ?>>Administrator</option>
					<option value="7" <?php if($contentpaybyzong_option['level'] == '7'){echo 'selected="selected"';} ?>>Editor</option>
					<option value="2" <?php if($contentpaybyzong_option['level'] == '2'){echo 'selected="selected"';} ?>>Author</option>
					<option value="1" <?php if($contentpaybyzong_option['level'] == '1'){echo 'selected="selected"';} ?>>Contributor</option>
					<option value="0" <?php if($contentpaybyzong_option['level'] == '0'){echo 'selected="selected"';} ?>>Subscriber</option>
				</select>
				<br />
				<span style="color: #555; font-size: .85em;">Specify the user's level which can view the articles without password</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Password duration</th> 
			<td colspan="2"><input type="text" name="numberOfDay" value="<?php echo $contentpaybyzong_option['numberOfDay']; ?>" size="5" /><br />
				<span style="color: #555; font-size: .85em;">Specify the number of day the pin code will be available.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Alphanumeric</th> 
			<td colspan="2"><input id="contentpaybyzong_protected" type="checkbox" <?php	if($contentpaybyzong_option['alphanumeric']==1){ echo "checked='checked'";} ?> value="1" name="alphanumeric"/><br />
				<span style="color: #555; font-size: .85em;">Check it if you want alphanumeric password or don't if you want only numeric password. </span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Password length</th> 
			<td colspan="2"><input type="text" name="passwordLength" value="<?php echo $contentpaybyzong_option['passwordLength']; ?>" size="5" maxlength="5" /><br />
				<span style="color: #555; font-size: .85em;">Specify the number of character the pin code will have. (max 16)</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Secret key</th> 
			<td colspan="2"><input type="text" name="secretKey" value="<?php echo $contentpaybyzong_option['secretKey']; ?>" size="32"  /><br />
				<span style="color: #555; font-size: .85em;">Specify a secret key to protect your handler</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Your handler url :</th> 
			<td colspan="2">
				<span style="font-weight:bold">
					<?php echo get_option('siteurl'); ?>/wp-content/plugins/content-pay-by-zong/content-pay-by-zong-handler.php?secretKey=<?php echo urlencode($contentpaybyzong_option['secretKey']); ?>
				</span><br />
				<span style="color: #555; font-size: .85em;">Copy and past this URL in your service's handler.</span>
				<?php if($contentpaybyzong_option['serviceid']!=0){
					echo '<span>
					<a href="http://developer.zong.com/services/edit/type/oneoff/id/'.$contentpaybyzong_option['serviceid'].'#handlerPage" target="_blank">Change it</a>
					</span>';}
				?>
			</td>
		</tr>
	</table>
	<script type="text/javascript" charset="utf-8">
		jQuery(document).ready(function(){
				jQuery.ajax({
				   type: "post",
				   url: <?php echo '"'. get_option('siteurl').'/wp-content/plugins/content-pay-by-zong/content-pay-by-zong-ajax-testconnection.php"' ?>,
				   cache: false,

				   success: function(html){
						 jQuery('#contentpaybyzong_testconnection_result').html(html);
					}
				});
		});
	</script>
	</fieldset>
	<p />
	<div class="submit">
		<input type="submit" name="submit" value="<?php _e('Update Options') ?> &raquo;" />
	</div>
	</form>
	
<?php
}
	
//----------------------------------------------------------------------------
//		 ACTIONS FUNCTION
//----------------------------------------------------------------------------

function contentpaybyzong_init() {
	if (function_exists('wp_enqueue_script')) {
		// Load jquery
		wp_enqueue_script('jquery');
	}
}

function contentpaybyzong_head() {
	//Load the css file
	echo '<link href="'. get_option('siteurl').'/wp-content/plugins/content-pay-by-zong/content-pay-by-zong_style.css" type="text/css" rel="stylesheet" media="screen" />';
}


function contentpaybyzong_savePost($postId){
	if($_POST['contentpaybyzong_protected'] == 1){
		$protected = '1';
	}else{
		$protected = '0';
	}
	add_post_meta($postId, '_contentpaybyzonged', $protected, true) or update_post_meta($postId, '_contentpaybyzonged', $protected);
}

function contentpaybyzong_addMetaBox(){
	global $post;
	?>
	<p>
		<label class="selectit" for="contentpaybyzong_status">
		<input id="contentpaybyzong_protected" type="checkbox" <?php	if(is_contentpaybyzonged($post->ID)){ echo "checked='checked'";} ?> value="1" name="contentpaybyzong_protected"/>
		Monetize this page.
		</label>
	</p>
	<?php 
}
//----------------------------------------------------------------------------
//		 FILTERS FUNCTION
//----------------------------------------------------------------------------

function contentpaybyzong_filterContent($content) {
	global $post, $contentpaybyzong_option;
	if(!contentpaybyzong_allowDisplay($post->ID)){
		// cached variable to avoid multi-call to ZAPI and call only if one or more post is protected
		$contentpaybyzong_myMarkets = wp_cache_get('markets');
		if($contentpaybyzong_myMarkets == false) {
			$contentpaybyzong_myMarkets = contentpaybyzong_getMarkets();
			wp_cache_set('markets', $contentpaybyzong_myMarkets);
		}
		if($contentpaybyzong_myMarkets[0]['error']==0){
			$content='
			<div class="clearer"></div>
			<div class="entry">
				<div class="contentpaybyzong_button" id="contentpaybyzong_button'.$post->ID.'">
				<img src="'.get_option('siteurl') . '/wp-content/plugins/content-pay-by-zong/img/zong-wpay-button.png" width="159" height="78" alt="Click me">
				</div>
				<table width="260px" cellspacing="4" cellpadding="0" id="contentpaybyzong'.$post->ID.'" class="contentpaybyzong">
					<tr>
						<td class="contentpaybyzong_flag" colspan="2" width="260px">';
					$i=0;
					foreach($contentpaybyzong_myMarkets as $myMarket){
						$content.='<img flagnum="'.$i.'" id="contentpaybyzong_country"'.$post->ID.$i.'" class="contentpaybyzong_flagimg'.
									$post->ID.'" src="'.get_option('siteurl') . '/wp-content/plugins/content-pay-by-zong/img/flags/'.
									strtolower($myMarket['country']).'.gif" width="26" height="17" alt="Flag">';
						$i++;
					}
					$content .='
						</td>
					</tr>
					<tr>
						<td class="contentpaybyzong_info" colspan="2">';
					$i=0;
					foreach($contentpaybyzong_myMarkets as $myMarket){
						$textSMS = "an SMS";
						if((string)$myMarket['country'] == 'US' || (string)$myMarket['country'] == 'UK'){
							$textSMS = "a text message";
						}
						$content.='<div class="contentpaybyzong_info'.$post->ID.'" id="contentpaybyzong_info'.$post->ID.$i.'">
							Send&nbsp;'.$textSMS.'&nbsp;containing<br />
							<b>'.$myMarket['keywords'][0]['name'].'</b> to <b>'.$myMarket['sc'].'</b>. 
							<br />You will instantly receive your access code
							<br />(1 SMS / '.$myMarket['currency'].' '.$myMarket['amount'].') for '.$contentpaybyzong_option['numberOfDay'].' day(s).
							</div>';
						$i++;
					}
					$content .='
							<div class="contentpaybyzong_instruction'.$post->ID.'">';
					$content .="Click your country's flag above.
							</div>
						</td>
					</tr>";
			$content.='<tr>
						<form method="post" action="'.get_option('siteurl') . '/wp-content/plugins/content-pay-by-zong/content-pay-by-zong-pass.php">
						<td class="contentpaybyzong_logo">
							<img src="'.get_option('siteurl') . '/wp-content/plugins/content-pay-by-zong/img/zong.gif" width="41" height="12" alt="Zong Developer Network">
						</td>
						<td class="contentpaybyzong_input">
							<b>code:</b> <input class="contentpaybyzong_codeinput" id="contentpaybyzong_pwbox-'.$post->ID.'" type="password" size="8" name="contentpaybyzong_post_password"/>
							<input class="contentpaybyzong_go" type="image" value="Send" src="'.get_option('siteurl') . '/wp-content/plugins/content-pay-by-zong/img/go.gif" name="Submit"/>
						</td>
						</form>
					</tr>
				</table>
			</div>
			<script type="text/javascript" charset="utf-8">
				jQuery(document).ready(function(){
					jQuery("div.contentpaybyzong_info'.$post->ID.'").css("display","none");
					jQuery("div.contentpaybyzong_instruction").css("display","none");
					jQuery("#contentpaybyzong_button'.$post->ID.'").click(function () {
						jQuery("#contentpaybyzong_button'.$post->ID.'").css("display","none");
						jQuery("#contentpaybyzong'.$post->ID.'").css("display","block");
					});
					
					jQuery("img.contentpaybyzong_flagimg'.$post->ID.'").click(function () {
						jQuery("div.contentpaybyzong_info'.$post->ID.'").css("display","none");
						jQuery("div.contentpaybyzong_instruction'.$post->ID.'").css("display","none");
						jQuery("#contentpaybyzong_info'.$post->ID.'"+jQuery(this).attr("flagnum")).css("display","block");
					});
				});
			</script>';
		}else if($contentpaybyzong_myMarkets[0]['error']==1){
			$content = '<div class="entry"" style="color:#990000"><p>Please configure Content Pay by Zong in the admin dashboard</p></div>';
		}else if($contentpaybyzong_myMarkets[0]['error']==2){
			$content = '<div class="entry"" style="color:#990000"><p>This protected content is temporarily unavailable.</p></div>';
		}else if($contentpaybyzong_myMarkets[0]['error']==3){
			$content = '<div class="entry"" style="color:#990000"><p>Can not protect this page - You need at least PHP 5.0 or SimpleXML</p></div>'.$content;
		}else{
			$content = '<div class="entry"" style="color:#990000"><p>Error - Please configure Content Pay by Zong in the admin dashboard</p></div>';
		}
	}
	return $content;
}

function contentpaybyzong_filterCommentMetaData($content) {
	global $post;
	if(!contentpaybyzong_allowDisplay($post->ID)){
		return '</a>Enter your password to view comments';	
	}else{
		return $content;
	}
	
}
function contentpaybyzong_filterComment($content){
	global $post;
	if(!contentpaybyzong_allowDisplay($post->ID)){
		return '';	
	}else{
		return $content;
	}
}
function contentpaybyzong_hideCommentForm($id){

	if(!contentpaybyzong_allowDisplay($id)){
		echo "<script type='text/javascript' charset='utf-8'>
			jQuery(document).ready(function(){
				jQuery('#respond').remove();
				jQuery('#commentform').remove();
				jQuery('.nocomments').remove();
			});
		</script>";
		
	}
}

//----------------------------------------------------------------------------
//		WORDPRESS ACTIONS 
//----------------------------------------------------------------------------

add_action('init', 'contentpaybyzong_init');
add_action('wp_head', 'contentpaybyzong_head');
add_action('save_post', 'contentpaybyzong_savePost');
add_action('admin_menu', 'contentpaybyzong_addPage');

//----------------------------------------------------------------------------
//		WORDPRESS FILTERS 
//----------------------------------------------------------------------------

add_action('comment_form','contentpaybyzong_hideCommentForm');	
add_filter('the_content','contentpaybyzong_filterContent');	 
add_filter('comments_number','contentpaybyzong_filterCommentMetaData');
add_filter('comments_array','contentpaybyzong_filterComment');

?>

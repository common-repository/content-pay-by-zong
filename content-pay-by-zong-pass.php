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

require( dirname(__FILE__) . '../../../../wp-config.php');

if ( get_magic_quotes_gpc() )
    $_POST['contentpaybyzong_post_password'] = stripslashes($_POST['contentpaybyzong_post_password']);

$optionarray_def = get_option('contentpaybyzong_options');
$numberOfDay = $optionarray_def['numberOfDay'];
$nextDay = time() + $numberOfDay*(24*60*60);
setcookie('contentpaybyzong-postpass_' . COOKIEHASH, $_POST['contentpaybyzong_post_password'], $nextDay, COOKIEPATH);

wp_safe_redirect(wp_get_referer());
?>
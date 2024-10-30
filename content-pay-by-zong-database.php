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


function contentpaybyzong_install () {
	global $wpdb;
	$contentpaybyzong_db_version = ".1";
	$table_name = $wpdb->prefix . "contentpaybyzong_passwords";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
		    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		    `password` VARCHAR( 16 ) NOT NULL,
		    `end_date` DATETIME NOT NULL,
		     UNIQUE KEY id (id)
		    );";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql);
		add_option("contentpaybyzong_db_version", $contentpaybyzong_db_version);
	}	
}

<?php
/*
Copyright 2010 Blindside Networks

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Versions:
   1.0  --  Initial version written by Sebastian Schneider
                   (email : seb DOT sschneider [ a t ] g m ail DOT com)
   1.1  --  Updated by Omar Shammas 
                    (email : omar DOT shammas [a t ] g m ail DOT com)

*/

//================================================================================
//------------------Required Libraries and Global Variables-----------------------
//================================================================================
require('bbb_api.php');
session_start();

$url_name = 'mt_bbb_url';
$salt_name = 'mt_salt';
$meetingID_name = 'meetingID';

//================================================================================
//------------------------------------Main----------------------------------------
//================================================================================
echo '<?xml version="1.0"?>'."\r\n";

//Retrieves the bigbluebutton url, and salt from the seesion
$salt_val = $_SESSION[$salt_name];
$url_val = $_SESSION[$url_name];
$meetingID = $_GET[$meetingID_name];

//Calls getMeetingXML and returns returns the result
echo BigBlueButton::getMeetingXML( $meetingID, $url_val, $salt_val );
?>
<?php
/*
Copyright 2012 Blindside Networks

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
   1.0  --  Updated by Jesus Federico 
                    (email : federico DOT jesus [a t ] g m ail DOT com)

*/

//================================================================================
//------------------Required Libraries and Global Variables-----------------------
//================================================================================
require('bbb_api.php');
session_start();

$url_name = 'mt_bbb_url';
$salt_name = 'mt_salt';
$recordingID_name = 'recordingID';

//================================================================================
//------------------------------------Main----------------------------------------
//================================================================================
header('Content-Type: text/plain; charset=utf-8');
//Retrieves the bigbluebutton url, and salt from the seesion
if ( isset($_SESSION[$salt_name]) && isset($_SESSION[$url_name]) && isset($_GET[$meetingID_name]) ){
    $salt_val = $_SESSION[$salt_name];
    $url_val = $_SESSION[$url_name];
    $meetingID = $_GET[$meetingID_name];
    
    //Calls getMeetingXML and returns returns the result
    echo BigBlueButton::getMeetingXML( $meetingID, $url_val, $salt_val );
    
} else {
    echo 'false';
}

?>
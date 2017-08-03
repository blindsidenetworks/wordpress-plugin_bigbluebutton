var pluginbaseurl = bbbScript.pluginsUrl;//this variable is passed through the php file by   wp_localize_script
var meetingdetails;
var slug;
var bbbpinginterval ='';


//================================================================================
//--------------------------------- Rooms----------------------------------
//================================================================================
//
jQuery(function($){
//one id for both?
	$("#bbbRooms").change(function(){
			bigbluebutton_set_meeting_slug(this);
	});

	bigbluebutton_set_meeting_slug('input#hiddenInputSingle');

  //sets the slug
	function bigbluebutton_set_meeting_slug(hiddeninput){
		slug = $(hiddeninput).val();
	}

});


/**
* Joins/Views the meeting/room.
*
* @param  join join or view the room
* @param  usersignedin
* @param  passwordrequired
* @param  page
*/
function bigbluebutton_join_meeting(join, usersignedin, passwordrequired, page){
		var name = '';
		var password = '';

		//clean this up
		if(page == "true")
		{
			if(usersignedin == "false"){
				name = prompt("Please enter your name: ", "Enter name here");
				if (name === null) { //hit cancel
        	return;
    		}
			}

			if(passwordrequired == "true"){
				password = prompt("Please enter the password of the meeting: ", "Enter password here");
				if (password === null) { //hit cancel
        	return;
    		}
			}

		}else{
			if(usersignedin == "false"){
				jQuery(function($) {
						name = $('input#displayname').val();
				});
			}

			if(passwordrequired == "true"){
				jQuery(function($) {
					password = $('input#roompw').val();
				});
			}
		}

		meetingdetails = '&slug=' + slug + '&join=' + join + '&password=' + password + '&name=' + name;

		jQuery.ajax({
			type: "POST",
			url : pluginbaseurl+'/broker.php?action=join',
			async : true,
			data: meetingdetails,
			dataType : "text",
			success : function(data){
				if(isurl(data)){
					window.open(data);
					jQuery("div#bbb-error-container").text('');
				}
				else if(data == 'wait') {
					var pollingimgpath = pluginbaseurl+'/img/polling.gif';
					jQuery("div#bbb-join-container").append
					("<center>Welcome to "+ slug +"!<br /><br /> \
					 The session has not been started yet.<br /><br />\
					 <center><img src="+ pollingimgpath +"\ /></center>\
					 (Your browser will automatically refresh and join the meeting when it starts.)</center>");
					jQuery("form#room").hide();
					jQuery("input.bbb-shortcode-selector").hide();
					jQuery("div#bbb-error-container").text('');
			    bbbpinginterval = setInterval("bigbluebutton_ping()", 5000);
				}
				else{
					jQuery("div#bbb-error-container").text(data);
				}
			},
			error : function() {
				console.error("Ajax was not successful: JOIN");
			}
		});
 }

/**
* This function is pinged every 5 seconds to see if the meeting is running
**/
function bigbluebutton_ping() {
 	 jQuery.ajax({
	   type: "POST",
		 url : pluginbaseurl + '/broker.php?action=ping',
		 async : true,
		 data: meetingdetails,
		 dataType : "text",
		 success : function(data){
		 if(isurl(data)){
			  clearInterval(bbbpinginterval);
				jQuery("div#bbb-join-container").remove();
			  window.open(data);
			}
		 },
		 error : function() {
		 	console.error("Ajax was not successful: PING");
		 }
 	 });
 }



//================================================================================
//--------------------------------- Recordings----------------------------------
//================================================================================
//
/**
* Action call for recordings.
*
* @param  action publish/unpublish
* @param  recordingid recording id or the specific recording

*/
function bigbluebutton_action_call(action, recordingid) {
	action = (typeof action == 'undefined') ? 'publish' : action;
	if (action == 'publish' || (action == 'delete' && confirm("Are you sure to delete this recording?"))) {
		if (action == 'publish') {
			 var actionbarpublish;
			 jQuery(function($) {
					actionbarpublish = $('a#actionbar-publish-a-'+ recordingid);
				});
			if (actionbarpublish) {
				var actionbarimg ;
				jQuery(function($) {
 						actionbarimg = $('img#actionbar-publish-img-'+ recordingid);
 				});
				if (actionbarpublish.attr('title') == 'Hide' ) {
						action = 'unpublish';
						actionbarpublish.attr('title', 'Show') ;
						actionbarimg.attr('src', pluginbaseurl + '/img/show.gif') ;
				} else {
						action = 'publish';
						actionbarpublish.attr('title', 'Hide') ;
					  actionbarimg.attr('src', pluginbaseurl + '/img/hide.gif') ;
				}
			}
		} else {
			 jQuery(function($) {
					 $('tr#actionbar-tr-'+ recordingid).remove();
			 });
		}
		var actionurl = pluginbaseurl + "/broker.php?action=" + action + "&recordingID=" + recordingid;
		jQuery.ajax({
			url : actionurl,
			async : false,
			success : function(){
			},
			error : function(xmlHttpRequest) {
					console.debug(xmlHttpRequest);
			}
		 });
	}
}

//================================================================================
//--------------------------------- Helpful functions----------------------------------
//================================================================================
//

/**
* Detecting weather the passed string is a URL
*
* @param s String thats passed to see if its a URL
* https://stackoverflow.com/questions/1701898/how-to-detect-whether-a-string-is-in-url-format-using-javascript
**/
 function isurl(s) {
   var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
   return regexp.test(s);
}

<html>
<head><title>Penguincon 2012</title>
<style>
html, body {
	background-color: #232323;
	font-family: Courier New;
	font-size: 11pt;
	color: #FFFFFF;
}

</style>
</head>
<body>
<div align='center' style='text-align:center;'>
	<a href='http://penguincon12.eventbrite.com/' target='_top' style='text-decoration:none;'><img src='images/placeholder_2012.jpg' alt='' width='800'></a><br><br>
	<a href='http://penguincon12.eventbrite.com/' target='_top' style='color:#ffffff;'>Register for Penguincon 2012</a>
</div>
</body>
</html>
<?php
exit;

require "config.php";
$swankyCutOff = "2011-09-24 00:00:00";
$registrationCutOff = "2011-10-16 00:00:00";
$eventEndsAt = "2011-10-16 15:00:00";
$swankyAvailable = (time() <= strtotime($swankyCutOff));
$registrationAvailable = (time() <= strtotime($registrationCutOff));
$eventOver = (time() >= strtotime($eventEndsAt));
$logType = 'visit';
$logNote = '';
if (!isset($_COOKIE['visited_penguincon'])) {
	$logType = 'first_visit';
} else if(!empty($_GET['ajax_submit'])){
	$logType = 'registration_submit';
	$logNote = (count($_POST)) ? print_r($_POST,1) : 'No post received';
} else if(!empty($_GET['ajax_edit'])){
	$logType = 'registration_edit_submission';
	$logNote = (count($_POST)) ? print_r($_POST,1) : 'No post received';
} else if(!empty($_GET['pg']) && $_GET['pg'] == 'paypal_complete'){
	$logType = 'paypal_complete';
	$logNote = '';
} else if(!empty($_GET['pg']) && $_GET['pg'] == 'edit_registration'){
	$logType = 'registration_edit_form';
	$logNote = '';
}
$sponsors = array();
$sponsors[] = array(
'name'=>'A & A Comics Inc, Cleveland OH',
'url'=>'http://maps.google.com/maps/place?q=a+and+a+comics,+cleveland&cid=2756904084066102062',
'image'=>'AndAComics.jpg',
'image_width'=>'220',
'image_height'=>'187',
'columns'=>1
);
$sponsors[] = array(
'name'=>'Conquistador Games',
'url'=>'http://www.conquistadorgames.com/',
'image'=>'ConquistadorGames.jpg',
'image_width'=>'263',
'image_height'=>'187',
'columns'=>1
);
$sponsors[] = array(
'name'=>'RageQuit Relief',
'url'=>'http://www.ragequitrelief.com/',
'image'=>'RageQuitRelief.jpg',
'image_width'=>'226',
'image_height'=>'187',
'columns'=>1
);
$sponsors[] = array(
'name'=>'Monster Energy',
'url'=>'http://www.monsterenergy.com/',
'image'=>'MonsterEnergy.jpg',
'image_width'=>'187',
'image_height'=>'187',
'columns'=>1
);

$sponsors[] = array(
'name'=>'Bawls',
'url'=>'http://www.bawls.com/',
'image'=>'Bawls.jpg',
'image_width'=>'291',
'image_height'=>'187',
'columns'=>1
);

$sponsors[] = array(
'name'=>'World Wine & Liqour',
'url'=>'http://www.worldwinesohio.com/',
'image'=>'WorldWineAndLiquor.jpg',
'image_width'=>'190',
'image_height'=>'187',
'columns'=>1
);
$sponsors[] = array(
'name'=>'Frogpants Studios',
'url'=>'http://www.frogpants.com/',
'image'=>'FrogpantsStudios.jpg',
'image_width'=>'592',
'image_height'=>'187',
'columns'=>3
);

db_Connect('index.php');
$newLog = array('view_time'=>'NOW()','action'=>$logType,'note'=>$logNote,'user_agent'=>$_SERVER['HTTP_USER_AGENT'],'ip_v4_address'=>CONNECTING_IP_ADDRESS);
$query = "INSERT INTO logs ".db_ConvertArrayToInsertForm($newLog);
db_Query($query,'Log Query');
db_DisconnectAll();

$hotelURL = "http://www.ichotelsgroup.com/redirect?path=rates&checkInDate=13&checkInMonthYear=092011&checkOutDate=16&checkOutMonthYear=092011&brandCode=hi&hotelCode=clewl&GPC=GAM&_IATAno=99801505";

if(!empty($_GET['ajax_submit']) && count($_POST)){
	$responseText = "error";
	$problemFields = array();
	if(!empty($_POST['rsvp_full_name']) && strlen($_POST['rsvp_full_name']) < 5){
		$problemFields[] = 'rsvp_full_name';
	}
	if(!empty($_POST['rsvp_forum_name']) && strlen($_POST['rsvp_forum_name']) < 3){
		$problemFields[] = 'rsvp_forum_name';
	}
	if(!valid_email($_POST['rsvp_email'])){
		$problemFields[] = 'rsvp_email';
	}
	if(!empty($_POST['rsvp_coming_from']) && strlen($_POST['rsvp_coming_from']) < 2){
		$problemFields[] = 'rsvp_coming_from';
	}
	if(count($problemFields)){
		print implode("|",$problemFields);
	} else {
		$formData = array();
		foreach($_POST as $key=>$value){
			$formData[$key] = trim(stripslashes($value));
		}
		
		db_Connect('index.php');
		
		//check for existing email address / forum name
		$existingFound = false;
		$query = "SELECT COUNT(*) AS num_found FROM registrants
		WHERE event_id = '1' AND email = '".addslashes($formData["rsvp_email"])."'
		AND valid_registration = '1'";
		$data = db_NextRecord(db_Query($query,'Q2'));
		if($data['num_found'] > 0){
			$existingFound = true;
		}

		if($existingFound){
			$responseText = "exists";
		} else {
			$passwordGenerator = new PasswordGenerator();
			$newQueryArray = array(
			"event_id"=>1,//can be changed for different penguincons
			"full_name"=>$formData["rsvp_full_name"],
			"forum_name"=>$formData["rsvp_forum_name"],
			"email"=>$formData["rsvp_email"],
			"coming_from"=>$formData["rsvp_coming_from"],
			"bringing_pc"=>($formData["rsvp_bringing_pc"]=='yes')?1:0,
			"bringing_general"=>$formData["rsvp_bringing_general"],
			"reserving_swanky"=>($formData["rsvp_swanky"]=='yes')?1:0,
			"ip_address"=>CONNECTING_IP_ADDRESS,
			"auth_code"=>$passwordGenerator->generateAlphaNumericPassword(40),
			"creation_date"=>"NOW()",
			);
			
			$query = "INSERT INTO registrants ".db_ConvertArrayToInsertForm($newQueryArray);
			$result = db_Query($query,'Q2');
			$insertID = 0;
			if($result){
				$responseText = "saved|";
				$responseText .= ($formData["rsvp_swanky"]=='yes') ? "CBTVMVRDJ6NB2" : "BXA7QRDQE4498";
				
				$insertID = db_InsertID();
			}
			if($insertID && SMTP_AVAILABLE === true){
				$typeOfReservation = ($formData["rsvp_swanky"] == "yes") ? "Swanky" : "Normal";
				$costOfReservation = ($formData["rsvp_swanky"] == "yes") ? '$60' : '$35';
				$pcBringing = ($formData["rsvp_bringing_pc"] == "yes") ? "will" : "will not";
				$generalBringing = "<p>They aren't even bringing anything else with them!</p>";
				if(!empty($formData["rsvp_bringing_general"])){
					$generalBringing = ($formData["rsvp_bringing_pc"] == "yes") ? "<p>They also plan to bring the following:" : "<p>They do plan to bring the following, though:";
					$generalBringing .= "<ul><li>".implode("</li><li>",explode("\n",$formData["rsvp_bringing_general"]))."</li></ul></p>";	
				}
				if(TEST_SITE === false){
					$emailTo = array("Email"=>"deyermand@gmail.com","Name"=>"Brian DeyErmand");
					$bccTo = array("Email"=>"mills.joshua@gmail.com","Name"=>"Josh Mills");
				} else {
					$emailTo = array("Email"=>"mills.joshua@gmail.com","Name"=>"Josh Mills");
					$bccTo = "";
				}
				
				$emailFrom = array("Email"=>"info@penguincom.com","Name"=>"PenguinCon.com");
				
				$subject = "New Registration for PenguinCon 2011 [{$typeOfReservation}]";
				$message = "<div style='font-size:10pt;font-family:Arial;'>
				<p>You've just snagged another <strong>{$typeOfReservation}</strong> reservation for PenguinCon 2011!</p>
				<p>It's <strong>{$formData["rsvp_forum_name"]}</strong> from the forums, aka <strong>{$formData["rsvp_full_name"]}</strong></p>
				<p>If you need to get ahold of them, <strong>{$formData["rsvp_email"]}</strong> is where you'll find them.</p>
				<p>They <strong>{$pcBringing}</strong> be bringing their PC.
				{$generalBringing}
				</div>";
				SendEmail($emailTo,$emailFrom,$subject,$message,true,false,"",$bccTo);
				
				//send an email to the new registrant
				$query = "SELECT * FROM registrants WHERE id = '$insertID' LIMIT 1";
				$registrationData = db_NextRecord(db_Query($query,''));
				
				$editRegistrationLink = "http://{$_SERVER['HTTP_HOST']}/index.php?pg=edit_registration&email=".urlencode($registrationData["email"])."&auth=".$registrationData['auth_code']."&conf=".strtotime($registrationData['creation_date']);
				
				$generalBringing = "<p>You aren't even bringing anything else with you!</p>";
				if(!empty($formData["rsvp_bringing_general"])){
					$generalBringing = ($formData["rsvp_bringing_pc"] == "yes") ? "<p>You also plan to bring the following:" : "<p>You do plan to bring the following, though:";
					$generalBringing .= "<ul><li>".implode("</li><li>",explode("\n",$formData["rsvp_bringing_general"]))."</li></ul></p>";	
				}
				if(TEST_SITE === false){
					$emailTo = array("Email"=>$formData["rsvp_email"],"Name"=>$formData["rsvp_full_name"]);
					$bccTo = array("Email"=>"mills.joshua@gmail.com","Name"=>"Josh Mills");
				} else {
					$emailTo = array("Email"=>$formData["rsvp_email"],"Name"=>$formData["rsvp_full_name"]);
					$bccTo = "";
				}
				$emailFrom = array("Email"=>"info@penguincom.com","Name"=>"PenguinCon.com");
				
				$subject = "Your Registration for PenguinCon 2011";
				$message = "<div style='font-size:10pt;font-family:Arial;'>
				<p>This email is to let you know that you've just begun a <strong>{$typeOfReservation}</strong> reservation for PenguinCon 2011!</p>
				<p>Until you've paid your <strong>{$costOfReservation}</strong> fee, this email means zilch, zip, nada.  You'll get a follow-up email when your payment has been validated by management.</p>
				<p>You said you were <strong>{$formData["rsvp_forum_name"]}</strong> from the forums, aka <strong>{$formData["rsvp_full_name"]}</strong></p>
				<p>You <strong>{$pcBringing}</strong> be bringing your PC.
				{$generalBringing}
				<p>If you need to update your registration information or check your registration status, <a href=\"{$editRegistrationLink}\" target=\"_blank\">click here</a>.</p>
				<p><strong>And don't forget to <a href=\"{$hotelURL}\" target=\"_blank\" title=\"Reserve Your Hotel Online!\">reserve your hotel room!</a></strong></p>
				</div>";
				SendEmail($emailTo,$emailFrom,$subject,$message,true,false,"",$bccTo);				
			}
		}
		db_Disconnect();
		print $responseText;
	}
	exit;
}
if(!empty($_GET['ajax_edit']) && count($_POST)){
	$responseText = "error";
	$problemFields = array();
	if(!empty($_POST['edit_rsvp_full_name']) && strlen($_POST['edit_rsvp_full_name']) < 5){
		$problemFields[] = 'edit_rsvp_full_name';
	}
	if(!empty($_POST['edit_rsvp_forum_name']) && strlen($_POST['edit_rsvp_forum_name']) < 3){
		$problemFields[] = 'edit_rsvp_forum_name';
	}
	if(!empty($_POST['edit_rsvp_coming_from']) && strlen($_POST['edit_rsvp_coming_from']) < 2){
		$problemFields[] = 'edit_rsvp_coming_from';
	}
	if(count($problemFields)){
		print implode("|",$problemFields);
	} else {
		$formData = array();
		foreach($_POST as $key=>$value){
			$formData[$key] = trim(stripslashes($value));
		}
		db_Connect('index.php');

		//check for existing email address / forum name
		$existingFound = false;
		$query = "SELECT id FROM registrants WHERE email = '".db_EscapeString($formData['edit_rsvp_email'])."'
		AND auth_code = '".db_EscapeString($formData['edit_rsvp_auth'])."'
		AND creation_date = '".date('Y-m-d H:i:s',$formData['edit_rsvp_conf'])."'
		ORDER BY id DESC LIMIT 1";
		
		$existingID = 0;
		$result = db_Query($query,'Q3n');
		if(db_NumRows($result) > 0){
			$data = db_NextRecord($result);
			$existingID = $data['id'];
		}

		if(!$existingID){
			$responseText = "missing";
		} else {
			//$existingID
			$editQueryArray = array(
			"full_name"=>$formData["edit_rsvp_full_name"],
			"forum_name"=>$formData["edit_rsvp_forum_name"],
			"coming_from"=>$formData["edit_rsvp_coming_from"],
			"bringing_pc"=>($formData["edit_rsvp_bringing_pc"]=='yes')?1:0,
			"bringing_general"=>$formData["edit_rsvp_bringing_general"],
			);
			
			$query = "UPDATE registrants ".db_ConvertArrayToUpdateForm($editQueryArray)." WHERE id = '{$existingID}' LIMIT 1";
			$result = db_Query($query,'Q3m');
			$responseText = 'saved';
		}
		db_Disconnect();
		print $responseText;
	}
	exit;
}

if (!isset($_COOKIE['visited_penguincon'])) {
	$serverName = ($_SERVER['SERVER_NAME'] == 'localhost') ? '' : $_SERVER['SERVER_NAME'];
	setcookie("visited_penguincon", 1, strtotime('+1 month'), "", $serverName);
	
	$splashDisplay = 'block';
	$contentDisplay = 'none';
} else if(!empty($_GET['splash'])){
	$splashDisplay = 'block';
	$contentDisplay = 'none';
} else {
	$splashDisplay = 'none';
	$contentDisplay = 'block';
}

$validPageDivs = array('welcome'=>1,'venue'=>1,'rsvp'=>1,'contact'=>1,'events'=>1,'byopc'=>1,'paypal_complete'=>1,'edit_registration'=>1,'sponsors'=>1);
$pageDivToDisplay = 'welcome';
if(!empty($_GET['pg']) && isset($validPageDivs[$_GET['pg']])){
	$pageDivToDisplay = $_GET['pg'];
}
// && !empty($_GET['auth'])  && !empty($_GET['conf']) &&
if($pageDivToDisplay == 'edit_registration' && !empty($_GET['email'])){
	db_ConnectIfNeeded();
	$query = "SELECT * FROM registrants WHERE email = '".db_EscapeString($_GET['email'])."'
	AND auth_code = '".db_EscapeString($_GET['auth'])."'
	AND creation_date = '".date('Y-m-d H:i:s',$_GET['conf'])."'
	ORDER BY id DESC LIMIT 1";
	$result = db_Query($query,'Q3x');
	if(db_NumRows($result)){
		$registrationData = db_NextRecord($result);
		foreach($registrationData as $key=>$value){
			$registrationData[$key] = stripslashes($value);
		}
	} else {
		$pageDivToDisplay = 'welcome';	
	}
	db_Disconnect();
} else if($pageDivToDisplay == 'edit_registration'){
	$pageDivToDisplay = 'welcome';
}

function loadTwitterWidget(){
	require_once "twitter/twitterstatus.php";
	$t = new TwitterStatus('penguincon', 3);
	//cache for 15 minutes
	$t->CacheFor = 15*60;
	//$t->DateFormat = 'g:ia j F Y'; // 1:15pm 27 January 2011
	$t->WidgetTemplate =  
    '<div class="twitterDiv"><br />
    <table width="250" cellspacing="0">
    <tr><td align="left"><span class="smallTitleText">Recently, on Twitter...</span></td></tr>
    {TWEETS}
    <tr><td align="right"><a href="http://twitter.com/#!/PenguinCon" target="_blank" style="color:black;">view all tweets</a></td></tr>
    </table>
    </div>
    <script type="text/javascript">
    	var spot = 0;
    	$(".twitterUpdateCell").each(function(){
    		$(this).fadeIn(1000);
    	});
    </script>';
	$t->TweetTemplate =  
    '<tr><td valign="middle"><div class="twitterUpdateCell">
    	<div class="twitterUpdateStatus">{text}</div>
    	<div align="right" class="twitterUpdateTime">{created_at}</div>
    	</div>
    </td>
    </tr>'; 
    print $t->Render();
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>PenguinCon</title>
<script type="text/javascript" src="jquery-1.5.2.min.js"></script>
<script type="text/javascript" src="jquery-ui-1.8.12.custom.min.js"></script>
<link rel="stylesheet" href="style.css" type="text/css" media="screen"/> 
<link rel="stylesheet" href="twitter-widget.css" type="text/css" media="screen"/> 
</head>
<body>

<script type="text/javascript">
var pageDivs = new Object();
function slidePage(name){
	<?php foreach($validPageDivs as $pageDivName=>$pageDivValid){
		if($pageDivValid){
			print "\n\tpageDivs.{$pageDivName} = 0;";
		}
	}
	print "\n";
	?>
	if (pageDivs[name] != undefined){
		pageDivs[name] = 1;
		
		for(page in pageDivs) {  // print out the bands with descriptions
	  		//alert(pageDivs[page] + " == " + page);
	  		if(pageDivs[page] && !$('#'+page).is(':visible')){
				$('#'+page).slideDown(1000);
			} else if(!pageDivs[page] && $('#'+page).is(':visible')){
				$('#'+page).slideUp(1000);
			}
		}
	}
}
function validateRegistrationForm(){
	var valid = true;
	if(jQuery('#rsvp_full_name').val().length < 5){
		valid = false;
		jQuery("#rsvp_full_name_required").show().effect("highlight", {"color":"yellow"}, 3000);
	} else {
		jQuery("#rsvp_full_name_required").hide()
	}
	if(jQuery('#rsvp_forum_name').val().length < 3){
		valid = false;
		jQuery("#rsvp_forum_name_required").show().effect("highlight", {"color":"yellow"}, 3000);
	} else {
		jQuery("#rsvp_forum_name_required").hide()
	}
	if((jQuery('#rsvp_email').val().length <= 7 || jQuery('#rsvp_email').val().indexOf('@') == -1 || jQuery('#rsvp_email').val().indexOf('.') == -1)){
		valid = false;
		jQuery("#rsvp_email_required").show().effect("highlight", {"color":"yellow"}, 3000);
	} else {
		jQuery("#rsvp_email_required").hide()
	}
	if(jQuery('#rsvp_coming_from').val().length < 2){
		valid = false;
		jQuery("#rsvp_coming_from_required").show().effect("highlight", {"color":"yellow"}, 3000);
	} else {
		jQuery("#rsvp_coming_from_required").hide()
	}
	return valid;
}
function submitRegistrationForm(){
	if(validateRegistrationForm()){
		var str = $(".rsvpFormItem").serialize();
		jQuery.ajax({
			url: "index.php?ajax_submit=1",
			global: false,
			type: "POST",
			data: str,
			async:false,
			beforeSend: function(){
				jQuery("#paypalButtonImage1").hide();
				jQuery("#paypalSpinnerImage").show();
			},
			complete: function(){
				//alert("done!")
			},
			success: function(responseText){
				if(responseText.indexOf('saved') == 0){
					//alert("Saved!"+responseText);
					if(jQuery('#paypalForm')){
						var responseItems = responseText.split('|');
						var paypalButtonID = responseItems[1];
						jQuery('#hosted_button_id').val(paypalButtonID);
						jQuery('#paypalForm').submit();
					}
				} else if(responseText == 'exists'){
					alert("That email address is already registered for this event.\n\nPlease contact us if this is an error.");
				} else {
					var responseItems = new Array();
					if(responseText.indexOf('|') != -1){
						responseItems = responseText.split('|');
					} else {
						responseItems = [responseText];
					}
					if(responseItems.length){
						for(var i=0;i<responseItems.length;i++){
							if(jQuery("#"+responseItems[i]).length){
								jQuery("#"+responseItems[i]+"_required").show().effect("highlight", {"color":"yellow"}, 3000);
							}
						}
					}
				}
			},
			error: function(){
				alert("There was an error submitting your request - please try again shortly.");
			}
		});
	}
}
function updateDisplayedPackagePrice(){
	if(jQuery("#rsvp_swanky_yes").is(':checked')){
		jQuery('#rsvp_selected_package_cost').html("$60");
		jQuery('#hosted_button_id').val("CBTVMVRDJ6NB2");
	} else {
		jQuery('#rsvp_selected_package_cost').html("$35");
		jQuery('#hosted_button_id').val("BXA7QRDQE4498");
	}
}

function submitRegistrationEditForm(){
	if(validateRegistrationEditForm()){
		//edit_rsvp_email edit_rsvp_auth edit_rsvp_conf edit_rsvp_full_name edit_rsvp_forum_name edit_rsvp_coming_from
		var str = $(".edit_rsvpFormItem").serialize();
		jQuery.ajax({
			url: "index.php?ajax_edit=1",
			global: false,
			type: "POST",
			data: str,
			async:false,
			complete: function(){
				//alert("done!")
			},
			success: function(responseText){
				if(responseText.indexOf('saved') == 0){
					location.reload();
				} else if(responseText == 'missing'){
					alert("That email address is not registered for this event.\n\nPlease contact us if this is an error.");
				} else {
					var responseItems = new Array();
					if(responseText.indexOf('|') != -1){
						responseItems = responseText.split('|');
					} else {
						responseItems = [responseText];
					}
					if(responseItems.length){
						for(var i=0;i<responseItems.length;i++){
							if(jQuery("#"+responseItems[i]).length){
								jQuery("#"+responseItems[i]+"_required").show().effect("highlight", {"color":"yellow"}, 3000);
							}
						}
					}
				}
			},
			error: function(){
				alert("There was an error submitting your request - please try again shortly.");
			}
		});
	}
}
function validateRegistrationEditForm(){
	var valid = true;
	if(jQuery('#edit_rsvp_full_name').val().length < 5){
		valid = false;
		jQuery("#edit_rsvp_full_name_required").show().effect("highlight", {"color":"yellow"}, 3000);
	} else {
		jQuery("#edit_rsvp_full_name_required").hide()
	}
	if(jQuery('#edit_rsvp_forum_name').val().length < 3){
		valid = false;
		jQuery("#edit_rsvp_forum_name_required").show().effect("highlight", {"color":"yellow"}, 3000);
	} else {
		jQuery("#edit_rsvp_forum_name_required").hide()
	}
	if(jQuery('#edit_rsvp_coming_from').val().length < 2){
		valid = false;
		jQuery("#edit_rsvp_coming_from_required").show().effect("highlight", {"color":"yellow"}, 3000);
	} else {
		jQuery("#edit_rsvp_coming_from_required").hide()
	}
	return valid;
}
jQuery(function(){
	var splashDelayTime = 1000;
	var splashFadeTime = 7000;
	if($('#splash').is(':visible')){
		$("#splash").delay(splashDelayTime).fadeOut(splashFadeTime);
	}
	if(!$('#pageContent').is(':visible')){
		$("#pageContent").delay(splashDelayTime+splashFadeTime).fadeIn(2000);
	}
	updateDisplayedPackagePrice();
});
</script>
<div id='splash' align='center' style="display:<?=$splashDisplay;?>;" ><img src="images/penguincon_2011_banner.jpg" alt=""></div>
<div id='pageContent' align="center" style="display:<?=$contentDisplay;?>;" >
<a href="index.php" target="_top"><img src="images/banner_1.jpg" alt="" border="0"/></a><br/>
	<div id="topMenu" class="menuText">
		<table width="100%" cellpadding="2" cellspacing="0" style="padding-top:5px;" class="menuText">
			<tr>
				<td width="17%" align="center"><span class="menuLink" onclick="slidePage('welcome')">Welcome</span></td>
				<td width="17%" align="center"><span class="menuLink" onclick="slidePage('events')">Events</span></td>
				<td width="17%" align="center"><span class="menuLink" onclick="slidePage('sponsors')">Sponsors</span></td>
				<td width="17%" align="center"><span class="menuLink" onclick="slidePage('byopc')">BYOPC</span></td>
				<td width="16%" align="center"><span class="menuLink" onclick="slidePage('rsvp')">RSVP</span></td>
				<td width="16%" align="center"><span class="menuLink" onclick="slidePage('contact')">Contact</span></td>
			</tr>
		</table>
	</div>
	
	<div id='content'>
		<div id='welcome' class='pageDiv' style='display:<?=($pageDivToDisplay=='welcome')?'block':'none';?>;'>
			<!--<img src="images/banner_1.jpg" alt="" /><br/>-->
			<table width="100%" cellpadding="0" cellspacing="0" border="0">
			<tr>
			<td width="30%" align="center" valign="bottom" style='padding-top:8px;'><img src="images/penguin_light_short.png" alt="" /></td>
			<td width="70%" rowspan="2" valign="top">
			<p class="titleText">The Call Has Gone Out &mdash; Welcome to PenguinCon 2011!</p>
			<p>This October, every member of <a href="http://www.gamerswithjobs.com" target="_blank" title="GamersWithJobs.com">Gamers With Jobs</a> with the ability to drive, ride, fly or apparate is invited to descend upon Northeastern Ohio.  Last year was a surprise success, with 30+ people taking part in a day of console games, board games and sleeping on the floor. Not only was a great time had by all but, thanks to the unending generousity of the GWJ community, over $600 was raised for <a href="http://www.childsplaycharity.org/" target="_blank" title="ChildsPlayCharity.org">Child's Play</a>.  And they said it couldn't get better.</p>
			<p>Actually, no one said that, because of course it could get better, and PenCon11 is shaping up to be the Best. PenguinCon. Ever.  This year features an entire weekend full of your favorite games and gamers - even though you don't know any of their real names.  Don't worry, they'll have name tags! </p>
			<p><i>Awkwardness averted!</i></p>
			<p>Enter one of the official tournaments and gain glory, prestige and maybe prizes!  Play a late night <a href="http://boardgamegeek.com/boardgame/37111/battlestar-galactica" target="_blank" title="BoardgameGeek.com - Battlestar Galacta">Battlestar Galactica</a> game!  Bring your PC and show off your Hattes for Gentle Mannes!  Watch as Oilypenguin evolves from gruff event coordinator to pantless, huggy drunk!  It's super effective!  Head to the <span class="spanLink" onclick="slidePage('events');">Events page</span> to see what to expect (and what to bring).</p>
			<p>PenCon11 even has a new venue this year, the Holiday Inn &mdash; Cleveland West in tropical Westlake, Ohio.  No longer must we cower in the shadows, huddled shivering on the ground, for discounted rooms will be made available to those travelling from faraway lands.  Check out the venue section of the <span class="spanLink" onclick="slidePage('rsvp');">RSVP page</span> for more information and to make reservations.</p>
			<p>By now it should be fairly clear that this is something you'll want a piece of, so take a moment and visit to the <span class="spanLink" onclick="slidePage('rsvp');">RSVP page</span>. Right now.</p>
			<p class="titleText">~ The Management</strong></p>
			</td></tr>
			<tr>
			<td align="center" valign="top">
			<?php loadTwitterWidget(); ?>	
			</td></tr>
 			</table>
		</div>
		<div id='events' class='pageDiv' style='display:<?=($pageDivToDisplay=='events')?'block':'none';?>;'>
			<p class="titleText">Events</p>
<!--			<p>We'll be announcing some things soon, stay tuned</p>-->
			<p align="center"><!--title=PenguinCon%202011&amp;-->
				<iframe src="https://www.google.com/calendar/b/0/embed?showTitle=0&amp;showPrint=0&amp;showDate=0&amp;showNav=0&amp;showTabs=0&amp;showCalendars=0&amp;mode=AGENDA&amp;height=650&amp;wkst=1&amp;bgcolor=%23FFFFFF&amp;src=oilypenguin%40gmail.com&amp;color=%2323164E&amp;ctz=America%2FNew_York" style=" border-width:0 " width="850" height="650" frameborder="0" scrolling="no"></iframe>
			</p>
		</div>
		<div id='sponsors' class='pageDiv' style='display:<?=($pageDivToDisplay=='sponsors')?'block':'none';?>;'>
			<p class="titleText">Sponsors</p>
			<?
			$output = 0;
			$clearAt = 3;
			foreach($sponsors as $sponsor){
				$output += $sponsor['columns'];
				print "<div style='border:0px black solid;float:left;margin-top:5px;margin-bottom:5px;width:".(33*$sponsor['columns'])."%;' align='center'>
					<a href='{$sponsor['url']}' target='_blank'><img src='images/sponsors/{$sponsor['image']}' width='{$sponsor['image_width']}' height='{$sponsor['image_height']}' border='0' alt=\"{$sponsor['name']}\" title=\"{$sponsor['name']}\"></a>
				</div>";
				if($output == $clearAt){
					$output = 0;
					print "<div style='clear:both;'></div>";
				}
			}
			if($output){
				$output = 0;
				print "<div style='clear:both;'></div>";
			}
			?>
		</div>
		<div id='byopc' class='pageDiv' style='display:<?=($pageDivToDisplay=='byopc')?'block':'none';?>;'>
			<p class="titleText">Bring Your Own PC</p>
			<p>We didn't have PC gaming last year. This was a pretty big omission that should never have happened. I feel awful. Just awful. Mortified even.</p>
	
			<p>So we're going to rectify it this year.</p>
			
			<p>Bring your towers, bring your laptops. Hell, bring your servers. We're going to do all we can to have as many PCs as we can hooked up and shooting at one another.</p>
			
			<p><strong>List of Games for PC</strong> (Work in progress)
				<ul>
					<li>Battlefield: Bad Company 2</li>
					<li>Blood Bowl</li>
					<li>Company of Heroes</li>
					<li>Counter-strike: Source</li>
					<li>Dawn of War 2: Retribution</li>
					<li>League of Legends</li>
					<li>Left 4 Dead 2</li>
					<li>Minecraft =)</li>
					<li>Rainbow 6 Vegas 2</li>
					<li>Starcraft 2</li>
					<li>Team Fortress 2</li>
					<li>Tribes 2 - We don't need to understand it to enjoy it =)</li>
					<li>World of Warcraft - We'll have a lot of Blackhand Alliance members there. We can do some BGs or Dungeons.</li>
					<li>X-Wing vs. Tie Fighter - We have months to get this working! Dig out your discs and buy a stick!</li>
				</ul>
			</p>
			<p>Now, because space is limited, a few rules that will need to be followed:
				<ol>
					<li>Please bring only flatscreen monitors. I don't know how many of you still use CRTs but feel shame.</li>
					<li>HEADPHONES ONLY. Noise will be an issue. NO SPEAKERS. I will confiscate computer speakers for the weekend =) <-- that's one of those chilling smilies. Like Gary Oldman.</li>
					<li>Come prepared with a network cord and power strip. We might not need them, but better safe than sorry.</li>
					<li>If you can put stickers with your name on it before you get here, that will save staff a headache.</li>
				</ol>
			</p>
			<p>There will be a system in place to keep track of people's stuff. We want you to leave with the stuff you brought.</p>
		</div>
		<div id='venue' class='pageDiv' style='display:<?=($pageDivToDisplay=='venue')?'block':'none';?>;'>
			<p class='titleText'>Venue</p>
			<p><a href="http://www.westlakeholidayinn.com/index.html" target="_blank" title="Holiday Inn - Westlake">Holiday Inn &mdash; Westlake</a><br/>
			1100 Crocker Rd. <br/>
			Westlake, OH 44145<br/>
			<a href="http://maps.google.com/maps?q=1100+Crocker+Road+Westlake,+Ohio+44145&oi=gmail" target="_blank" title="Google Maps for Holiday Inn - Westlake">View on Google Maps</a></p>
			
			<p>Standard room (2 Queens or 1 King): $82 per night<br/>
			Include 2 breakfast buffets per night (an $18 value!)</p>
			 
			<p>Reservations by web: <a href="<?=$hotelURL;?>" target="_blank" title="www.holidayinn.com">Click here</a>.<br/>
			Reservations by phone: Call (440) 871-6000 and tell them "Gamers with Jobs" sent you.</p>
		</div>
		<div id='rsvp' class='pageDiv' style='display:<?=($pageDivToDisplay=='rsvp')?'block':'none';?>;'>
			<? if(!$eventOver){ ?>
			<div id='venueDiv'>
				<div class='titleText' align='center' style='margin-top:8px;'>Holiday Inn &mdash; Westlake</div>
<!--				<p align='center'><a href="http://www.westlakeholidayinn.com/index.html" target="_blank" title="Holiday Inn - Westlake"><img src="images/hotel_front.jpg" alt="Holiday Inn - Westlake" border="0"/></a></p>-->
				<p align='center'><img src="images/hotel_front.jpg" alt="Holiday Inn - Westlake" border="0"/></p>
				<p align='center'>
				1100 Crocker Rd. <br/>
				Westlake, OH 44145<br/>
				<a href="http://maps.google.com/maps?q=1100+Crocker+Road+Westlake,+Ohio+44145&oi=gmail" target="_blank" title="Google Maps for Holiday Inn - Westlake">View on Google Maps</a></p>
				<div class='smallTitleText' align='center'>Reservations</div>
				<p><strong>Online:</strong> <a href="<?=$hotelURL;?>" target="_blank" title="Reserve Online!">Click here</a>.<br/>
				<strong>Phone:</strong> Call (440) 871-6000<br/>&nbsp;&nbsp;and tell them "Gamers with Jobs"<br/>&nbsp;&nbsp;sent you.</p>
			</div>
			<p class="titleText">What's This Going To Cost Me?</p>
			<p>Aside from the hotel room (starting around $82 per night), $35 gets you in the door and we don't toss you back out. Plus, if you register within 30 days of RSVPs going live you get:
				<ul>
					<li>Invitation to a party with the staff on Thursday night. I can neither confirm nor deny swankiness for this party nor that we're paying for it. Wait... I can tell you that we're <strong>not</strong> paying for it=) Still, eat and drink with the staff at a surprise location. Mystery!</li>
					<li>Raffle tickets! How many? We don't know! Some! What's the raffle for? We don't know either! Be excited!</li>
					<li>Random bag of stuff! What's in it? Too many questions! Trust us, we have 5 months to figure it out!</li>
				</ul>
			</p>
			<p>$60 does everything as above if you register in the first 30 days and also...
				<ul>
					<li>Sweet-ass T-shirt! Yeah, we're going to have shirts. Will they be awesome? That's kind of up to you =) Details to follow.</li>
					<li>Your random bag of stuff will be better than the $35 one. We hope. Hey, we're trying here.</li>
				</ul>
			</p>
			<? if($registrationAvailable){ ?>
			<span class="titleText" style='margin-left:8px;'>Register Now!</span>
			<div align="left" style='margin-left:8px;'>
			<table width="600" cellpadding="2" cellspacing="0" border="0" id="rsvpForm">
				<tr>
					<td width="260">Your Full Name:&nbsp;</td>
					<td width="340"><input type="text" class="inputText rsvpFormItem" name="rsvp_full_name" id="rsvp_full_name" value="" />
					<span id="rsvp_full_name_required" class="required hidden">&nbsp;*Required&nbsp;</span></td>
				</tr>
				<tr>
					<td>Your Forum Name:&nbsp;</td>
					<td><input type="text" class="inputText rsvpFormItem" name="rsvp_forum_name" id="rsvp_forum_name" value="" />
					<span id="rsvp_forum_name_required" class="required hidden">&nbsp;*Required&nbsp;</span></td>
				</tr>
				<tr>
					<td>Your Email Address:&nbsp;</td>
					<td><input type="text" class="inputText rsvpFormItem" name="rsvp_email" id="rsvp_email" value="" />
					<span id="rsvp_email_required" class="required hidden">&nbsp;*Required&nbsp;</span></td>
				</tr>
				<tr>
					<td>Where are you coming from?&nbsp;</td>
					<td><input type="text" class="inputText rsvpFormItem" name="rsvp_coming_from" id="rsvp_coming_from" value="" />
					<span id="rsvp_coming_from_required" class="required hidden">&nbsp;*Required&nbsp;</span></td>
				</tr>
				<tr>
					<td>Do you plan to <span class="spanLink" onclick="slidePage('byopc')">Bring Your Own PC?</span>&nbsp;</td>
					<td>
					<input type="radio" class="inputCheck rsvpFormItem" name="rsvp_bringing_pc" id="rsvp_bringing_pc_no" value="yes" />
					Yes
					<input type="radio" class="inputCheck rsvpFormItem" name="rsvp_bringing_pc" id="rsvp_bringing_pc_yes" value="no" checked />
					No
					</td>
				</tr>
				<tr>
					<td valign="top">What I'm Bringing:&nbsp;<br/>&nbsp;&nbsp;<i>Please put items on separate lines</i></td>
					<td><textarea class="inputTextArea rsvpFormItem" name="rsvp_bringing_general" id="rsvp_bringing_general"></textarea></td>
				</tr>
				<tr>
					<td valign="top">Select your registration package:&nbsp;</td>
					<td>
						<input type="radio" class="inputCheck rsvpFormItem" name="rsvp_swanky" id="rsvp_swanky_no" value="no" onclick="updateDisplayedPackagePrice();" checked />Standard
						
						<input type="radio" class="inputCheck rsvpFormItem" name="rsvp_swanky" id="rsvp_swanky_yes" value="yes" onclick="updateDisplayedPackagePrice();" <?=(!$swankyAvailable)?"disabled":"";?>/>Swanky
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<form action="https://www.paypal.com/cgi-bin/webscr" method="post" id="paypalForm">
							<input type="hidden" name="cmd" value="_s-xclick" />
							<input type="hidden" name="hosted_button_id" id="hosted_button_id" value="CBTVMVRDJ6NB2" />
						</form>
						<div align="center" style='width:250px;'>
						<div id="rsvp_selected_package_cost" class="veryLargeText" style='width:147px;text-align:center;'>$35</div>
						<img src="images/ajax-loader.gif" id="paypalSpinnerImage" style="display:none;">
						<img src="https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_US/i/btn/btn_paynowCC_LG.gif" border="0" alt="PayPal - The safer, easier way to pay online!"
								style="cursor:pointer;" onclick="submitRegistrationForm();" id="paypalButtonImage1" />
						<img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_US/i/scr/pixel.gif" width="1" height="1" id="paypalButtonImage2" />
						</div>
					</td>
				</tr>
			</table>
			</div>
			<p class="smallTitleText">And don't forget to <a href="<?=$hotelURL;?>" target="_blank" title="Reserve Online!">reserve your hotel room!</a></p>
			<? } else { ?>
			<span class="titleText" style='margin-left:8px;'>Registration is closed!</span>
			<? } ?>
			<? } else { ?>
			<div style='height:50px;margin-top:25px;text-align:center;'>
				<span class="titleText" style='margin-left:8px;'>Event is over! See you again next year!</span>
			</div>
			<? } ?>
		</div>
		<div id='contact' class='pageDiv' style='display:<?=($pageDivToDisplay=='contact')?'block':'none';?>;'>
			<p>Info: oilypenguin [at] gmail [dawt] com</p>
			<p>Twitter: <a href="http://twitter.com/#!/PenguinCon" target="_blank" title="@PenguinCon">@PenguinCon</a></p>
			<p>GWJ planning thread: <a href="http://www.gamerswithjobs.com/node/107565" target="_blank" title="Penguincon 2011 Planning thread">Penguincon 2011 Planning thread</a></p>
		</div>
		<div id='paypal_complete' class='pageDiv' style='display:<?=($pageDivToDisplay=='paypal_complete')?'block':'none';?>;'>
			<p class="titleText">Thank you for your payment.</p>
			<p>Your transaction has been completed, and a receipt for your purchase has been emailed to you.</p>
			<p>You may log into your account at <a href="http://www.paypal.com/us" target="_blank" title="PayPal.com">www.paypal.com/us</a> to view details of this transaction.</p>
			<p class="smallTitleText">And don't forget to <a href="<?=$hotelURL;?>" target="_blank" title="Reserve Online!">reserve your hotel room!</a></p>
		</div>
		<div id='edit_registration' class='pageDiv' style='display:<?=($pageDivToDisplay=='edit_registration')?'block':'none';?>;'>
			<p class="titleText">Edit Your Registration Details</p>
			<div align="left" style='margin-left:8px;'>
			<input type="hidden" class="edit_rsvpFormItem" name="edit_rsvp_email" id="edit_rsvp_email" value="<?=$_GET['email'];?>" />
			<input type="hidden" class="edit_rsvpFormItem" name="edit_rsvp_auth" id="edit_rsvp_auth" value="<?=$_GET['auth'];?>" />
			<input type="hidden" class="edit_rsvpFormItem" name="edit_rsvp_conf" id="edit_rsvp_conf" value="<?=$_GET['conf'];?>" />
			<table width="600" cellpadding="2" cellspacing="0" border="0" id="edit_rsvpForm">
				<tr>
					<td width="260">Your Full Name:&nbsp;</td>
					<td width="340"><input type="text" class="inputText edit_rsvpFormItem" name="edit_rsvp_full_name" id="edit_rsvp_full_name" value="<?=$registrationData['full_name'];?>" />
					<span id="edit_rsvp_full_name_required" class="required hidden">&nbsp;*Required&nbsp;</span></td>
				</tr>
				<tr>
					<td>Your Forum Name:&nbsp;</td>
					<td><input type="text" class="inputText edit_rsvpFormItem" name="edit_rsvp_forum_name" id="edit_rsvp_forum_name" value="<?=$registrationData['forum_name'];?>" />
					<span id="edit_rsvp_forum_name_required" class="required hidden">&nbsp;*Required&nbsp;</span></td>
				</tr>
				<tr>
					<td>Your Email Address:&nbsp;</td>
					<td style='padding-left:8px;'><?=$registrationData['email'];?></td>
				</tr>
				<tr>
					<td>Where are you coming from?&nbsp;</td>
					<td><input type="text" class="inputText edit_rsvpFormItem" name="edit_rsvp_coming_from" id="edit_rsvp_coming_from" value="<?=$registrationData['coming_from'];?>" />
					<span id="edit_rsvp_coming_from_required" class="required hidden">&nbsp;*Required&nbsp;</span></td>
				</tr>
				<tr>
					<td>Do you plan to <span class="spanLink" onclick="slidePage('byopc')">Bring Your Own PC?</span>&nbsp;</td>
					<td>
					<input type="radio" class="inputCheck edit_rsvpFormItem" name="edit_rsvp_bringing_pc" id="edit_rsvp_bringing_pc_no" value="yes" <?=($registrationData['bringing_pc']==1)?"checked":"";?> />
					Yes
					<input type="radio" class="inputCheck edit_rsvpFormItem" name="edit_rsvp_bringing_pc" id="edit_rsvp_bringing_pc_yes" value="no" <?=($registrationData['bringing_pc']==0)?"checked":"";?> />
					No
					</td>
				</tr>
				<tr>
					<td valign="top">What I'm Bringing:&nbsp;<br/>&nbsp;&nbsp;<i>Please put items on separate lines</i></td>
					<td><textarea class="inputTextArea edit_rsvpFormItem" name="edit_rsvp_bringing_general" id="edit_rsvp_bringing_general"><?=$registrationData['bringing_general'];?></textarea></td>
				</tr>
				<tr>
					<td valign="top">Your registration package:&nbsp;</td>
					<td>
						<?=($registrationData['reserving_swanky']==0)?"Standard (\$35)":"Swanky (\$60)";?>
					</td>
				</tr>
				<tr>
					<td valign="top">Payment validated by management:&nbsp;</td>
					<td>
						<strong><?=($registrationData['valid_registration']==0)?"Not Yet":"Yes";?></strong>
					</td>
				</tr>
				<?php
				if($registrationData['valid_registration']==0){
					$paypalAmount = ($registrationData['reserving_swanky']) ? 60 : 35;
					$paypalButtonID = ($registrationData['reserving_swanky']) ? "CBTVMVRDJ6NB2" : "BXA7QRDQE4498";
					print '<tr>
					<td valign="top">&nbsp;</td>
					<td><div align="center" style="width:250px;">
						<div id="rsvp_selected_package_cost" class="veryLargeText" style="width:147px;text-align:center;">$'.$paypalAmount.'</div>
						<img src="images/ajax-loader.gif" id="paypalSpinnerImage" style="display:none;">
						<img src="https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_US/i/btn/btn_paynowCC_LG.gif" border="0" alt="PayPal - The safer, easier way to pay online!"
								style="cursor:pointer;" onclick="jQuery(\'#hosted_button_id\').val(\''.$paypalButtonID.'\');jQuery(\'#paypalForm\').submit();" />
						<img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_US/i/scr/pixel.gif" width="1" height="1" />
						</div>
						</td>
					</tr><tr><td>&nbsp;</td></tr>';
				}
				
				?>
				<tr>
					<td>&nbsp;</td>
					<td>
						<div align="center" style='width:250px;'>
						<input type="button" class="buttonText" value="Update My Registration Info" onclick="submitRegistrationEditForm()" />
						</div>
					</td>
				</tr>
			</table>
			</div>
		</div>
		
	</div>
	<div id="footerBar" class="footerText">
		<table width="100%" cellpadding="0" cellspacing="0" style="padding-top:5px;" class="footerText">
			<tr>
				<td width="100%" align="center"><span class="menuText"><!--Joshua Mills &mdash; -->Copyright <?=date('Y');?></span></td>
			</tr>
		</table>
	</div>	
</div>

</div>

</body>
</html>
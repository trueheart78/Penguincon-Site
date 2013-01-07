<?php
class RegistrationManager {
	private $scriptPage = "manage.php";
	private $scriptAction = "";
	private $scriptSubAction = "";
	private $ajaxCall = false;
	private $loggedIn = false;
	
	private $registrationID = 0;
	
	private $formData = array();
	
	private $username = "";
	private $password = "";
	private $invalidLoginAttempt = false;
	private $maxLoginAttempts = 3;
	private $restrictionTime = "+15 minutes";
	
	function __construct(){
		
		if(!empty($_POST['registration_id'])){
			$this->registrationID = $_POST['registration_id'];
		} else if(!empty($_GET['registration_id'])){
			$this->registrationID = $_GET['registration_id'];
		}
		
		if(!empty($_GET['action'])){
			$this->scriptAction = urldecode($_GET['action']);
		}
		if(!empty($_GET['sub_action'])){
			$this->scriptSubAction = urldecode($_GET['sub_action']);
		}
		if(!empty($_GET['no_headers'])){
			$this->ajaxCall = true;
		}
		$this->composeFormData();
		$this->verifyUserCredentials();
		
		if($this->loggedIn){
			$this->drawPageHeader();
			$this->director();
			$this->drawPageFooter();
		}
	}
	private function composeFormData(){
		if(count($_POST)){
			foreach($_POST as $key=>$val){
				$this->formData[$key] = stripslashes($val);
			}
		}
	}
	private function director(){
		switch($this->scriptAction){
			case "validate-login":
				$urlToRedirect = (!empty($_SESSION['png_stored_url'])) ? $_SESSION['png_stored_url'] : $this->composeURL();
				$this->redirectUser($urlToRedirect);
				break;
			case "logout":
				unset($_SESSION['png_un'],$_SESSION['png_pw']);
				$this->redirectUser($this->composeURL());
				break;
			case "welcome":
			default:
				print 'Welcome '.(($this->loggedIn)?' user':'poser').'<br/>';
				print $this->scriptAction;
				$this->drawRegistrationList();
				break;
		}
	}
	private function redirectUser($url){
		print "<script type='text/javascript'>document.location='{$url}';</script>";
	}
	private function verifyUserCredentials(){
		//if the restriction timer is not set or has passed...
		if(empty($_SESSION['png_res']) || $_SESSION['png_res'] > strtotime($this->restrictionTime)){
			//if resetting the invalid attempts...
			if($_SESSION['png_inv'] > $this->maxLoginAttempts){
				unset($_SESSION['png_inv'],$_SESSION['png_res']);
			}
			//check to see if the username and password are set
			if(!empty($_SESSION['png_un']) && !empty($_SESSION['png_pw'])){
				//if the username and password are valid...
				if($_SESSION['png_un'] == sha1($this->username) && $_SESSION['png_pw'] == sha1($this->password)){
					//valid logged in user
					$this->loggedIn = true;
				} else {
					//invalid credentials
				}
			}
			//if not logged in and hitting the validation page...
			if(!$this->loggedIn && $this->scriptAction == 'validate-login'){
				//if the form data has been passed...
				print 'bbb<br/>';
				if(!empty($this->formData['username']) && !empty($this->formData['password'])){
					//if they are valid, log the user in
					if($this->formData['username'] == $this->username && $this->formData['password'] == $this->password){
						$_SESSION['png_un'] = sha1($this->formData['username']);
						$_SESSION['png_pw'] = sha1($this->formData['password']);
						$this->loggedIn = true;
						print 'aces';
					}
				}
				if(!$this->loggedIn){
					//otherwise mark it as an invalid login
					$this->invalidLoginAttempt = true;
					//note the number of invalid attempts taken
					if(empty($_SESSION['png_inv'])){
						$_SESSION['png_inv'] = 1;
					} else {
						$_SESSION['png_inv']++;
					}
					//and if the invalid attempts are too high...
					if($_SESSION['png_inv'] > $this->maxLoginAttempts){
						//set the restricted time
						$_SESSION['png_res'] = time();
					}					
				}
			}
		}
		if(!$this->loggedIn){
			if(!$this->ajaxCall){
				$this->drawLoginPage();
			} else {
				print "[login_required]";
			}
		}
	}
	private function composeURL($action='',$subAction='',$registrationID=0,$noHeaders=0){
		$url = $this->scriptPage;
		$uriParts = array();
		if(!empty($action)){
			$uriParts[] = "action=".urlencode($action);
		}
		if(!empty($subAction)){
			$uriParts[] = "sub_action=".urlencode($subAction);
		}
		if(!empty($registrationID)){
			$uriParts[] = "registration_id=".$registrationID;
		}
		if(!empty($noHeaders)){
			$uriParts[] = "no_headers=".$noHeaders;
		}
		if(count($uriParts)){
			$url = $this->scriptPage.'?'.implode('&',$uriParts);
		}
		return $url;
	}
	private function drawLoginPage(){
		if($_SESSION['png_inv'] > $this->maxLoginAttempts){
			print "Invalid Login Attempt - Please Try Again in 15 Minutes<br/><br/>";
		} else if($this->invalidLoginAttempt){
			print "Invalid Login Attempt<br/><br/>";
		}
		print time()." vs ".strtotime($this->restrictionTime);
		print_r($_POST);
		print_r($_SESSION);
		print "<div align='center'><form action='".$this->composeURL('validate-login')."' method='post'>
		Username: <input type='text' class='login_input' name='username' /><br/>
		Password: <input type='password' class='login_input' name='password' /><br/>
		<br/>
		<input type='submit' value='Login' />
		</form></div>";
	}
	private function drawRegistrationList(){
		db_Connect("manage.php");
		$query = "SELECT * FROM registrants WHERE auth_code <> '' ORDER BY id";
		$result = db_Query($query,"Q1");
		if(db_NumRows($result)){
			$siteBaseUrl = ($_SERVER['HTTP_HOST'] == 'localhost') ? "{$_SERVER['HTTP_HOST']}/penguincon/site" : $_SERVER['HTTP_HOST'];
			while(($registrationData = db_NextRecord($result)) != false){
				$editRegistrationLink = "http://{$siteBaseUrl}/index.php?pg=edit_registration&email=".urlencode($registrationData["email"])."&auth=".$registrationData['auth_code']."&conf=".strtotime($registrationData['creation_date']);
				$swankyCode = ($registrationData["reserving_swanky"]) ? "<b>Swanky</b>" : "<b>Normal</b>";
				print "<a href='{$editRegistrationLink}' target='_blank'>Registration Link for {$registrationData['full_name']} {$registrationData['id']}</a> ".$swankyCode."<br/>";
			}
		}
		db_DisconnectAll();
	}
	private function validateRegistration(){
		
	}
	private function sendPaymentRequest(){
		
	}
	private function loadRegistrationInformation(){
		
	}
	private function resendEmail(){
		
	}
	private function removeRegistration(){
		
	}
	private function addRegistration(){
		
	}
	private function drawPageHeader(){
		if(!$this->ajaxCall){
			print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
			<title>PenguinCon Registration Manager</title>
			<script type="text/javascript" src="jquery-1.5.2.min.js"></script>
			<script type="text/javascript" src="jquery-ui-1.8.12.custom.min.js"></script>
			<link rel="stylesheet" href="manager_style.css" type="text/css" media="screen"/> 
			</head>
			<body><div align="center"><div class="pageDiv">';
		}
	}
	
	private function drawPageFooter(){
		if(!$this->ajaxCall){
			print "</div>\n</div>\n</body>\n</html>";	
		}
	}
}

?>
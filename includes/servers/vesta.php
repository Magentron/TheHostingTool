<?php

/**
 * TheHostingTool :: Kloxo Server Class
 * Created by Liam Demafelix (c) 2012
 * http://www.pinoytechie.net/
 * Kloxo Class Version 1.2.2 - March 13, 2012
**/

class vesta {
	
	public $name = "VestaCP";
	public $hash = false;
	
	private $port = 8083;
	
	private $server;
	
    // Valid username regex
    private static $validUsernameRegex = '/^[a-zA-Z0-9][a-zA-Z0-9_.-]{0,28}[a-zA-Z0-9]$/';

    public function __construct($serverId = null) {
        if(!is_null($serverId)) {
            $this->server = (int)$serverId;
        }
    }

	private function serverDetails($server) {
		global $db;
		global $main;
		$query = $db->query("SELECT * FROM `<PRE>servers` WHERE `id` = '{$db->strip($server)}'");
		if($db->num_rows($query) == 0) {
			$array['Error'] = "That server doesn't exist!";
			$array['Server ID'] = $id;
			$main->error($array);
			return;	
		}
		else {
			return $db->fetch_array($query);
		}
	}
	
	private function remote($action) {
	
		/**
		 * NOTE: $action should be an array, and NOT a string, as compared to other methods.
		 */
		 
		$data = $this->serverDetails($this->server);
		
		$action["user"] = $data['user'];
		$action["password"] = $data['accesshash'];
		
		$action = http_build_query($action);
		
		$ip = gethostbyname($data['host']);
		
		$url = "https://{$data['host']}:{$this->port}/api/";
		
		// Connect
		/** VestaCP uses POST data to interact with the API. **/
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $action);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		
		if($data == 0)
			return true;
		else
			return false;
		
		
    }
	
	 public function GenUsername() {
                $t = rand(5,8);
                for ($digit = 0; $digit < $t; $digit++) {
                        $r = rand(0,1);
                        $c = ($r==0)? rand(65,90) : rand(97,122);
                        $user .= chr($c);
                }
                return $user;
        }
       
        public function GenPassword() {
                for ($digit = 0; $digit < 5; $digit++) {
                        $r = rand(0,1);
                        $c = ($r==0)? rand(65,90) : rand(97,122);
                        $passwd .= chr($c);
                }
                return $passwd;
        }
		
	public function signup($server, $reseller, $user = '', $email = '', $pass = '') {
                global $main;
                global $db;
				
				
                if ($user == '') { $user = $main->getvar['username']; }
                if ($email == '') { $email = $main->getvar['email']; }
                if ($pass == '') { $pass = $main->getvar['password']; }
                $this->server = $server;
                $data = $this->serverDetails($this->server);
                $ip = gethostbyname($data['host']);
				
				/**
				 * Cleanup what we need
				**/
				$user = trim(stripslashes($main->getvar['username']));
				$email = trim(stripslashes($main->getvar['email']));
				$pass = trim(stripslashes($main->getvar['password']));
				$package = $main->getvar['fplan'];
				$package = str_replace(" ", "", $package);
				
				// Array for creating a user
				$wwwacctNew = array(
					"returncode"	=>		"yes",
					"cmd"			=>		"v-add-user",
					"arg1"			=>		$user,
					"arg2"			=>		$pass,
					"arg3"			=>		$email,
					"arg4"			=>		$package,
					"arg5"			=>		$main->getvar['firstname'],
					"arg6"			=>		$main->getvar['lastname'],
				);
				
				//$postdata1 = http_build_query($wwwacctNew);
				if(!$this->remote($wwwacctNew)) {
					// Creating the user failed. Return an error.
					echo "Unable to create the user in the server. Please inform your system administrator.";
					return false;
				}
				
				// If we reached this point, that means we weren't returned and the user was created. Now, let's add the domain to the new user
				$domainAdd = array(
					"returncode"	=>		"yes",
					"cmd"			=>		"v-add-domain",
					"arg1"			=>		$user,
					"arg2"			=>		$main->getvar['fdom'],
				);
				
				//$postdata2 = http_build_query($domainAdd);
				if(!$this->remote($domainAdd)) {
					// The user was created but the domain wasn't. Tell that to the user.
					echo "The user was created but the domain failed to register. Please login to the control panel and add the domain manually.";
					return false;
				}
				
				// If we reached this point, the signup process was successful.
				return true;
                
        }
		
		public function suspend($user, $server, $reason = false) {
                $this->server = $server;
				
				$postvars = array(
					"returncode"		=>		"yes",
					"cmd"				=>		"v-suspend-user",
					"arg1"				=>		$user,
				);
				
				return $this->remote($postvars);
        }
		
        public function unsuspend($user, $server) {
                $this->server = $server;
				
				$postvars = array(
					"returncode"		=>		"yes",
					"cmd"				=>		"v-unsuspend-user",
					"arg1"				=>		$user,
				);
				
				return $this->remote($postvars);
        }
		
        public function terminate($user, $server) {
                $this->server = $server;
                
				// Prepare POST query
				$delAcct = array(
					"user"			=>		$data['user'],
					"password"		=>		$data['pass'],
					"returncode"	=>		"yes",
					"cmd"			=>		"v-delete-user",
					"arg1"			=>		$user,
				);
				//$postdata = http_build_query($delAcct);

				
                return $this->remote($delAcct); 
        }
		
		public function testConnection($serverId = null) {
			echo "This feature is currently unsupported for VestaCP.";
			return false;
		}

		public function checkUsername($username)
		{
				// We're not going to check with the actual server because that's an expensive (time) operation
				if(!preg_match(self::$validUsernameRegex, $username)) {
					return "Username must be alphanumeric (incl. '-', '.' or '_'), start and end with a letter or number, and between 2 and 30 characters.";
				}
				return true;
		}
		
		public function changePwd($acct, $newpwd, $server)
		{
				# v-change-user-password USER PASSWORD
                $this->server = $server;
                
				// Prepare POST query
				$chnageAcct = array(
					"returncode"	=>		"yes",
					"cmd"			=>		"v-change-user-password",
					"arg1"			=>		$acct,
					"arg2"			=>		$newpwd,
				);
				//$postdata = http_build_query($delAcct);

				
                return $this->remote($delAcct); 
		}
}

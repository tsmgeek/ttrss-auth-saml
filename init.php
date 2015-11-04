<?php
/** 
 * Tiny Tiny RSS plugin for SAML authentication (uses Onelogin PHP-Saml)
 * @author tsmgeek (tsmgeek@gmail.com)
 * @copyright GPL2
 * @version 0.1
 */
class Auth_Saml extends Plugin implements IAuthModule {

	private $link;
	private $host;
	private $base;
	private $logClass;

	function about() {
		return array(0.1,
				"Authenticates against a SAML server (configured in config.php)",
				"tsmgeek",
				true);
	}

	function init($host) {
		$this->link = $host->get_link();
		$this->host = $host;
		$this->base = new Auth_Base($this->link);

		$host->add_hook($host::HOOK_AUTH_USER, $this);
		require_once('./saml/_toolkit_loader.php');
	}

	private function _log($msg, $level = E_USER_NOTICE,$file='',$line='',$context='') {
		$loggerFunction = Logger::get();
		if (is_object($loggerFunction)) {
			$loggerFunction->log_error($level, $msg,$file,$line,$context);
		} else {
			trigger_error($msg, $level);
		}

	}

	/**
	 * Logs login attempts
	 * @param string $username Given username that attempts to log in to TTRSS
	 * @param string $result "Logging message for type of result. (Success / Fail)"
	 * @return boolean
	 * @deprecated
	 * 
	 * Now that _log support syslog and log levels and graceful fallback user.  
	 */
	private function _logAttempt($username, $result) {


		return trigger_error('TT-RSS Login Attempt: user '.(string)$username.
				' attempted to login ('.(string)$result.') from '.(string)$ip,
				E_USER_NOTICE
				);	
	}

	/**
	 * Finds client's IP address
	 * @return string
	 */
	private function _getClientIP () {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			//check ip from share internet

			$ip=$_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			//to check ip is pass from proxy
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else {
			$ip=$_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

	private function _getTempDir () {
		if (!sys_get_temp_dir()) {
			$tmpFile=tempnam();
			$tmpDir=dirname($tmpFile);
			unlink($tmpFile);
			unset($tmpFile);
			return $tmpDir;
		} else {
			return sys_get_temp_dir();
		}
	}

	/**
	 * Main Authentication method
	 * Required for plugin interface 
	 * @param unknown $login  User's username
	 * @param unknown $password User's password
	 * @return boolean
	 */
	function authenticate($login, $password) {
		if ($login && $password) {
			if($samlData){
				return $this->base->auto_create_user($login);
			} else{
				$this->_log('SAML authentication failed');
				return FALSE;
			} 
		}
		return false;
	}

	/**
	 * Returns plugin API version
	 * Required for plugin interface
	 * @return number
	 */
	function api_version() {
		return 2;
	}

}


<?php
/** 
 * Tiny Tiny RSS plugin for SAML authentication (uses Onelogin PHP-Saml)
 * @author tsmgeek (tsmgeek@gmail.com)
 * @copyright GPL2
 * @version 0.1
 */
/**
  Following code needs to be added into /include/login_form.php under the Login button

                        <?php if (strpos(PLUGINS, "auth_saml") !== FALSE) {
                                echo PluginHost::getInstance()->get_plugin('auth_saml')->hook_login_button();
                        }?>

 */
class Auth_Saml extends Plugin implements IHandler {
	private $link;
	private $host;
	private $base;
	private $logClass;
	private $auth;

	function about() {
		return array(0.1,
				"Authenticates against a SAML server (configured in config.php)",
				"tsmgeek",
				true,
				"https://github.com/tsmgeek/ttrss-auth-saml");
	}

        function csrf_ignore($method) {
                return true;
        }

        function before($method) {
		if(!$this->auth) return false;
                $auth=$this->auth;
		if ($method==='sso') {
			$auth->login();
			return true;
		} else if ($method==='sso2') {
			$returnTo = $spBaseUrl.'/demo1/attrs.php';
			$auth->login($returnTo);
		} else if ($method==='slo') {
			$returnTo = null;
			$paramters = array();
			$nameId = null;
			$sessionIndex = null;
			if (isset($_SESSION['samlNameId'])) {
			$nameId = $_SESSION['samlNameId'];
			}
			if (isset($_SESSION['samlSessionIndex'])) {
				$sessionIndex = $_SESSION['samlSessionIndex'];
			}
			$auth->logout($returnTo, $paramters, $nameId, $sessionIndex);
		} else if ($method==='acs') {
			$auth->processResponse();
			$errors = $auth->getErrors();
			if (!empty($errors)) {
				print_r('<p>'.implode(', ', $errors).'</p>');
			}
			if (!$auth->isAuthenticated()) {
				$this->_log('SAML authentication failed');
				return false;
			}

			$user_id = $this->base->auto_create_user($auth->getNameId());
			if($user_id){
                                @session_start();

                	        $_SESSION['samlUserdata'] = $auth->getAttributes();
        	                $_SESSION['samlNameId'] = $auth->getNameId();
	                        $_SESSION['samlSessionIndex'] = $auth->getSessionIndex();

                                $_SESSION["uid"] = $user_id;
                                $_SESSION["version"] = VERSION_STATIC;

                                $result = db_query("SELECT login,access_level,pwd_hash FROM ttrss_users
                                        WHERE id = '$user_id'");

                                $_SESSION["name"] = db_fetch_result($result, 0, "login");
                                $_SESSION["access_level"] = db_fetch_result($result, 0, "access_level");
                                $_SESSION["csrf_token"] = uniqid_short();

                                db_query("UPDATE ttrss_users SET last_login = NOW() WHERE id = " .
                                        $_SESSION["uid"]);

                                $_SESSION["ip_address"] = $_SERVER["REMOTE_ADDR"];
                                $_SESSION["user_agent"] = sha1($_SERVER['HTTP_USER_AGENT']);
                                $_SESSION["pwd_hash"] = db_fetch_result($result, 0, "pwd_hash");

                                $_SESSION["last_version_check"] = time();
				$_SESSION["hide_logout"] = true;

                                initialize_user_prefs($_SESSION["uid"]);
				$auth->redirectTo('/');
				return true;
			}
			return false;
		} else if ($method==='sls') {
			$auth->processSLO();
			$errors = $auth->getErrors();
			if (empty($errors)) {
				logout_user();
				$auth->redirectTo('/');
			} else {
				print_r('<p>'.implode(', ', $errors).'</p>');
			}
		}

                return false;
        }

        function after() {
                return true;
        }


	function hook_action_item(){
		if(isset($_SESSION['samlUserdata'])){
			return '<div dojoType="dijit.MenuItem" onclick="gotoLogoutSSO()">Logout SSO</div><div dojoType="dijit.MenuItem" onclick="quickMenuGo(\'qmcLogout\')">'.__('Logout (only TTRSS)').'</div>';
			
		}
	}

	function hook_login_button(){
		return '<button onclick="document.location.href=\'backend.php?op=saml&subop=sso\';" dojoType="dijit.form.Button">'. __("SSO").'</button>';
	}

        function get_js() {
                return file_get_contents(dirname(__FILE__) . "/init.js");
        }

	function init($host) {
		$this->link = $host->get_link();
		$this->host = $host;
		$this->base = new Auth_Base($this->link);
		$host->add_handler('saml','*',$this);
		$host->add_hook($host::HOOK_ACTION_ITEM, $this);
		require_once(dirname(dirname(__FILE__)).'/auth_saml/saml/_toolkit_loader.php');
		if(file_exists(dirname(dirname(__FILE__)).'/auth_saml/settings.php')){
			require_once(dirname(dirname(__FILE__)).'/auth_saml/settings.php');
			$this->auth = new OneLogin_Saml2_Auth($samlConfig);
		}
		return true;
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


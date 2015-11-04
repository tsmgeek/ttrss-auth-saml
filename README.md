# ttrss-auth-saml
Tiny Tiny RSS SAML Auth Plugin

SAML Login auth plugin using onelogin/php-saml library and tested against simplesamlphp IdP.

onelogin/php-saml - https://github.com/onelogin/php-saml

tsmgeek/ttrss-auth-saml - https://github.com/tsmgeek/ttrss-auth-saml

You need to create a settings.php file in the plugin directory, you can find settings on the onelogin/php-saml page.
Currently it uses the userid supplied back in the saml response and not any additional data.
You will need to modify the /includes/login_form.php page to add in the following code below the 'Log in' button as there are no hooks for me to do this currently.


    <?php if (strpos(PLUGINS, "auth_saml") !== FALSE) {
      echo PluginHost::getInstance()->get_plugin('auth_saml')->hook_login_button();
    }?>


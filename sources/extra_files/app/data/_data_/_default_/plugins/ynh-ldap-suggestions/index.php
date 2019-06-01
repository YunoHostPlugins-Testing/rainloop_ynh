<?php
class YnhLdapSuggestionsPlugin extends \RainLoop\Plugins\AbstractPlugin {
	public function Init() {
		$this->addHook ( 'main.fabrica', 'MainFabrica' );
	}
	
	/**
	 * test if ldap is supported (but with ynh it nead ;) )
	 *
	 * @return string
	 */
	public function Supported() {
		if (! \function_exists ( 'ldap_connect' )) {
			return 'The LDAP PHP exention must be installed to use this plugin';
		}
		return '';
	}
	
	/**
	 *
	 * @param string $sName        	
	 * @param mixed $mResult        	
	 */
	public function MainFabrica($sName, &$mResult) {
		if ($sName === 'suggestions') {
			include_once __DIR__ . '/YnhLdapSuggestions.php';
			
			if (! \is_array ( $mResult )) {
				$mResult = array ();
			}
			
			$oProvider = new YnhLdapSuggestions ();
			$mResult [] = $oProvider;
		}
	}
}
?>

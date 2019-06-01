<?php
class YnhLoginMappingPlugin extends \RainLoop\Plugins\AbstractPlugin {

	public function Init() {
		$this->addHook ( 'filter.login-credentials', 'FilterLoginСredentials' );
	}
	
	/**
	 *
	 * @param string $sEmail        	
	 * @param string $sLogin        	
	 * @param string $sPassword        	
	 *
	 * @throws \RainLoop\Exceptions\ClientException
	 */
	public function FilterLoginСredentials(&$sEmail, &$sLogin, &$sPassword) {
		$this->Manager()->Actions()->Logger()->Write('YnhLoginMappingPlugin::FilterLoginСredentials IN => '.$sEmail.'/'.$sLogin, \MailSo\Log\Enumerations\Type::INFO);
		
		// connection au ldap ynh... en local
		$cnx = ldap_connect (); // single connection
		ldap_set_option($cnx, LDAP_OPT_PROTOCOL_VERSION, 3);
		
		if (! $cnx) {
			$this->Manager()->Actions()->Logger()->Write('YnhLoginMappingPlugin: Could not connect to LDAP server', \MailSo\Log\Enumerations\Type::ERROR);
			return;
		}
		
		if (! ldap_bind ( $cnx )) {
			// bizard... ca renvoie false.... mais ca marche....
			$this->Manager()->Actions()->Logger()->Write('LdapLoginMappingPlugin: Could not bind to LDAP server', \MailSo\Log\Enumerations\Type::ERROR);
			// return;
		}
		
		// Voir d'abord si le mail est dans un domaine de ynh
		// pour ca il faut d'abord recuperer la liste des domaines...
		$dn = "ou=domains,dc=yunohost,dc=org";
		
		// on veut tous les virtualdomain... pour un mail donné ??? verifier top...
		$filter = "(objectClass=mailDomain)";
		$attrs = array( 'virtualdomain');
		
		// OK un petit recherche sur les domaines
		$sr = ldap_search( $cnx, $dn, $filter, $attrs );
		$domains = ldap_get_entries($cnx, $sr);
		$this->Manager()->Actions()->Logger()->Write('YnhLoginMappingPlugin: ldap_search(domains) => '.var_export($domains, true ), \MailSo\Log\Enumerations\Type::INFO, 'LDAP');
		
		$is_ynh = false;
		
		// @ verifier avec la log du dessus
		for($i = 0; $i < $domains['count']; $i ++) {
			$domain = '@' . $domains[$i]['virtualdomain'][0];
			$this->Manager()->Actions()->Logger()->Write('YnhLoginMappingPlugin: domains['.$i.'] => '.$domain, \MailSo\Log\Enumerations\Type::INFO, 'LDAP');
			if ($this->endsWith($sEmail, $domain)) {
				$is_ynh = true;
				$this->Manager()->Actions()->Logger()->Write('YnhLoginMappingPlugin:    => OK', \MailSo\Log\Enumerations\Type::INFO, 'LDAP');
			}
		}
		
		// si le mail n'est pas l'un des domaines de ynh ca ne nous conserne pas... on sort!
		if (! $is_ynh) {
			return;
		}
		
		// on veut mail et uid... pour un mail donné
		$dn = "ou=users,dc=yunohost,dc=org";
		$filter = "(&(objectClass=inetOrgPerson)(mail=$sEmail))";
		$attrs = array('uid', 'mail');
		
		// OK un petit recherche sur les mails...
		$sr = ldap_search($cnx, $dn, $filter, $attrs);
		if (!$sr) {
			$this->Manager()->Actions()->Logger()->Write('YnhLoginMappingPlugin: search on LDAP server', \MailSo\Log\Enumerations\Type::ERROR);
			return;
		}
		$result = ldap_get_entries ( $cnx, $sr );
		$this->Manager ()->Actions ()->Logger ()->Write ( 'YnhLoginMappingPlugin: ldap_search(users) => ' . var_export ( $result, true ), \MailSo\Log\Enumerations\Type::INFO, 'LDAP' );
		
		// OK si on a un resultat on recupere l'identifiant de l'utilisateur...
		if (($result['count'] > 0) && ($result[0]['uid']['count'] > 0)) {
			$sLogin = $result[0]['uid'][0];
		} else {
			$this->Manager()->Actions()->Logger()->Write('YnhLoginMappingPlugin: user not found', \MailSo\Log\Enumerations\Type::ERROR);
		}
		
		$this->Manager()->Actions()->Logger()->Write('YnhLoginMappingPlugin::FilterLoginСredentials OUT => '.$sEmail.'/'.$sLogin, \MailSo\Log\Enumerations\Type::INFO);
	}
	private function beginsWith($str, $sub) {
		return (substr ( $str, 0, strlen ( $sub ) ) === $sub);
	}
	private function endsWith($str, $sub) {
		return (substr ( $str, strlen ( $str ) - strlen ( $sub ) ) === $sub);
	}
}

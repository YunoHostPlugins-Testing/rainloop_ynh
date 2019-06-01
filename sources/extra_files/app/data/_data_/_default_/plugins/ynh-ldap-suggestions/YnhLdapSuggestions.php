<?php
class YnhLdapSuggestions implements \RainLoop\Providers\Suggestions\ISuggestions {
	
	/**
	 *
	 * @var \MailSo\Log\Logger
	 */
	private $oLogger = null;
	
	/**
	 *
	 * @param \RainLoop\Model\Account $oAccount        	
	 * @param string $sQuery        	
	 * @param int $iLimit
	 *        	= 20
	 *        	
	 * @return array
	 */
	public function Process($oAccount, $sQuery, $iLimit = 20) {
		$sQuery = \trim ( $sQuery );
		
		if ('' === $sQuery || ! $oAccount) {
			return array ();
		}
		
		$aResult = $this->ldapSearch ( $oAccount, $sQuery, $iLimit );
		
		$aResult = \RainLoop\Utils::RemoveSuggestionDuplicates ( $aResult );
		if ($iLimit < \count ( $aResult )) {
			$aResult = \array_slice ( $aResult, 0, $iLimit );
		}
		
		return $aResult;
	}
	
	/**
	 *
	 * OK search in ldap.
	 * match email or displayName...
	 *
	 * @param \RainLoop\Model\Account $oAccount        	
	 * @param string $sQuery        	
	 *
	 * @return array
	 */
	private function ldapSearch($oAccount, $sQuery, $iLimit) {
		$sSearchEscaped = $this->escape ( $sQuery );
		
		$aResult = array ();
		$oCon = @\ldap_connect ();
		if (! $oCon) {
			$this->oLogger->Write ( 'YnhLdapSuggestions: Could not connect to LDAP server', \MailSo\Log\Enumerations\Type::ERROR );
			return $aResult;
		}
		
		@\ldap_set_option ( $oCon, LDAP_OPT_PROTOCOL_VERSION, 3 );
		
		if (! ldap_bind ( $oCon )) {
			// bizard... ca renvoie false.... mais ca marche....
			// $this->logLdapError ( $oCon, 'ldap_bind' );
			// $this->Manager()->Actions()->Logger()->Write('YnhLdapAdressbooks: Could not bind to LDAP server', \MailSo\Log\Enumerations\Type::ERROR);
			// return $aResult;
		}
		
		$this->oLogger->Write ( 'YnhLdapSuggestions: connected to LDAP', \MailSo\Log\Enumerations\Type::INFO, 'LDAP' );
		
		$sSearchDn = 'dc=yunohost,dc=org';
		
		// on veut chercher parmis mail et nom utilisateur...
		$sFilter = '(&(objectClass=inetOrgPerson)';
		$sFilter .= '(|';
		$sFilter .= '(mail=*' . $sSearchEscaped . '*)';
		$sFilter .= '(displayName=*' . $sSearchEscaped . '*)';
		$sFilter .= '))';
		
		$aItems = array (
				'mail',
				'displayName' 
		);
		
		$this->oLogger->Write ( 'YnhLdapSuggestions: ldap_search : ' . $sSearchDn . ' / ' . $sFilter, \MailSo\Log\Enumerations\Type::INFO, 'LDAP' );
		
		$oS = @\ldap_search ( $oCon, $sSearchDn, $sFilter, $aItems, 0, $iLimit, $iLimit );
		if ($oS) {
			$aEntries = @\ldap_get_entries ( $oCon, $oS );
			$this->oLogger->Write ( 'YnhLdapSuggestions: ldap_search => ' . var_export ( $aEntries ), \MailSo\Log\Enumerations\Type::INFO, 'LDAP' );
			
			if (is_array ( $aEntries )) {
				if (isset ( $aEntries ['count'] )) {
					unset ( $aEntries ['count'] );
				}
				
				foreach ( $aEntries as $aItem ) {
					$sName = \trim ( $aItem ['displayname'] [0] );
					if (isset ( $aItem ['mail'] ['count'] )) {
						unset ( $aItem ['mail'] ['count'] );
					}
					foreach ( $aItem ['mail'] as $sEmail ) {
						$sEmail = \trim ( $sEmail );
						if (! empty ( $sEmail )) {
							$aResult [] = array (
									$sEmail,
									$sName 
							);
						}
					}
				}
			} else {
				$this->logLdapError ( $oCon, 'ldap_get_entries' );
			}
		} else {
			$this->logLdapError ( $oCon, 'ldap_search' );
		}
		
		return $aResult;
	}
	
	/**
	 *
	 * @param string $sStr        	
	 *
	 * @return string
	 */
	private function escape($sStr) {
		$aNewChars = array ();
		$aChars = array (
				'\\',
				'*',
				' (',
				')',
				\chr ( 0 ) 
		);
		
		foreach ( $aChars as $iIndex => $sValue ) {
			$aNewChars [$iIndex] = '\\' . \str_pad ( \dechex ( \ord ( $sValue ) ), 2, '0' );
		}
		return \str_replace ( $aChars, $aNewChars, $sStr );
	}
	
	/**
	 *
	 * @param mixed $oCon        	
	 * @param string $sCmd        	
	 *
	 * @return string
	 */
	private function logLdapError($oCon, $sCmd) {
		if ($this->oLogger) {
			$sError = $oCon ? @\ldap_error ( $oCon ) : '';
			$iErrno = $oCon ? @\ldap_errno ( $oCon ) : 0;
			
			$this->oLogger->Write ( $sCmd . ' error: ' . $sError . ' (' . $iErrno . ')', \MailSo\Log\Enumerations\Type::WARNING, 'LDAP' );
		}
	}
	
	/**
	 *
	 * @param \MailSo\Log\Logger $oLogger        	
	 *
	 * @return \LdapContactsSuggestions
	 */
	/**
	 *
	 * @param \MailSo\Log\Logger $oLogger        	
	 */
	public function SetLogger($oLogger) {
		$this->oLogger = $oLogger instanceof \MailSo\Log\Logger ? $oLogger : null;
	}
}
?>

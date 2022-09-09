<?php
namespace HROC\Saml2;

use OneLogin\Saml2\Auth as OneLoginAuth;
use OneLogin\Saml2\LogoutResponse;
use OneLogin\Saml2\LogoutRequest;
use OneLogin\Saml2\Constants;
use OneLogin\Saml2\Utils;
use OneLogin\Saml2\Error;

class ExtendedOneLoginAuth extends OneLoginAuth {
	/**
	 * Process the SAML Logout Response / Logout Request sent by the IdP.
	 * Overrides OneLogin\Saml2\Auth to handle both HTTP_POST IDP response instead of HTTP_REDIRECT (GET)
	 *
	 * @param bool        $keepLocalSession             When false will destroy the local session, otherwise will keep it
	 * @param string|null $requestId                    The ID of the LogoutRequest sent by this SP to the IdP
	 * @param bool        $retrieveParametersFromServer True if we want to use parameters from $_SERVER to validate the signature
	 * @param callable    $cbDeleteSession              Callback to be executed to delete session
	 * @param bool        $stay                         True if we want to stay (returns the url string) False to redirect
	 *
	 * @return string|null
	 *
	 * @throws Error
	 */
	public function processSLO($keepLocalSession = false, $requestId = null, $retrieveParametersFromServer = false, $cbDeleteSession = null, $stay = false)
	{
		// can't access _errors so just ignoring it and throwing exceptions instead
		// $this->_errors = array();
		// $this->_lastError = $this->_lastErrorException = null;

		# Saml response (HTTP_REDIRECT or HTTP_POST)
		if (isset($_GET['SAMLResponse']) || isset($_POST['SAMLResponse'])) {
			if (isset($_GET['SAMLResponse'])) {
				$logoutResponse = new LogoutResponse($this->getSettings(), $_GET['SAMLResponse']);
			} else {
				$logoutResponse = new LogoutResponse($this->getSettings(), $_POST['SAMLResponse']);
			}
			$this->_lastResponse = $logoutResponse->getXML();
			if (!$logoutResponse->isValid($requestId, $retrieveParametersFromServer)) {
				// $this->_errors[] = 'invalid_logout_response';
				// $this->_lastErrorException = $logoutResponse->getErrorException();
				// $this->_lastError = $logoutResponse->getError();

				// since we can't access $this->_errors (private) throw and error instead
				throw new Error(
					'SAML Invalid logout response.',
					Error::SAML_LOGOUTRESPONSE_INVALID
				);

			} else if ($logoutResponse->getStatus() !== Constants::STATUS_SUCCESS) {
				// $this->_errors[] = 'logout_not_success';

				// since we can't access $this->_errors (private) throw and error instead
				throw new Error(
					'SAML Failed logout status.',
					Error::SAML_LOGOUTRESPONSE_INVALID		// no code for failed logout so have to use this
				);
			} else {
				$this->_lastMessageId = $logoutResponse->id;
				if (!$keepLocalSession) {
					if ($cbDeleteSession === null) {
						Utils::deleteLocalSession();
					} else {
						call_user_func($cbDeleteSession);
					}
				}
			}

		# Saml request (HTTP_REDIRECT only)
		} else if (isset($_GET['SAMLRequest'])) {
			$logoutRequest = new LogoutRequest($this->getSettings(), $_GET['SAMLRequest']);
			$this->_lastRequest = $logoutRequest->getXML();
			if (!$logoutRequest->isValid($retrieveParametersFromServer)) {
				// $this->_errors[] = 'invalid_logout_request';
				// $this->_lastErrorException = $logoutRequest->getErrorException();
				// $this->_lastError = $logoutRequest->getError();

				// since we can't access $this->_errors (private) throw and error instead
				throw new Error(
					'SAML Invalid logout request.',
					Error::SAML_LOGOUTRESPONSE_INVALID		// no code for invalid logout request so have to use this
				);
			} else {
				if (!$keepLocalSession) {
					if ($cbDeleteSession === null) {
						Utils::deleteLocalSession();
					} else {
						call_user_func($cbDeleteSession);
					}
				}
				$inResponseTo = $logoutRequest->id;
				$this->_lastMessageId = $logoutRequest->id;
				$responseBuilder = new LogoutResponse($this->getSettings());
				$responseBuilder->build($inResponseTo);
				$this->_lastResponse = $responseBuilder->getXML();

				$logoutResponse = $responseBuilder->getResponse();

				$parameters = array('SAMLResponse' => $logoutResponse);
				if (isset($_GET['RelayState'])) {
					$parameters['RelayState'] = $_GET['RelayState'];
				}

				$security = $this->getSettings()->getSecurityData();
				if (isset($security['logoutResponseSigned']) && $security['logoutResponseSigned']) {
					$signature = $this->buildResponseSignature($logoutResponse, isset($parameters['RelayState'])? $parameters['RelayState']: null, $security['signatureAlgorithm']);
					$parameters['SigAlg'] = $security['signatureAlgorithm'];
					$parameters['Signature'] = $signature;
				}

				return $this->redirectTo($this->getSLOResponseUrl(), $parameters, $stay);
			}
		} else {
			// cant access _errors so have to rely on exception
			//$this->_errors[] = 'invalid_binding';
			throw new Error(
				'SAML LogoutRequest/LogoutResponse not found.',
				Error::SAML_LOGOUTMESSAGE_NOT_FOUND
			);
		}
	}
}

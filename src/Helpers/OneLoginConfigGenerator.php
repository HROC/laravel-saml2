<?php
declare(strict_types=1);
namespace Mkhyman\Saml2\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;

use Mkhyman\Saml2\Models\Tenant;

class OneLoginConfigGenerator {
	private array $samlConfig;
	private Tenant $tenant;

	public function __construct(array $samlConfig, Tenant $tenant) {
		$this->samlConfig = $samlConfig;
		$this->tenant = $tenant;
	}

	public function generateOneLoginConfig() {
		$oneLoginConfig = $this->config;

		$this->applyConfigDefaultValues($oneLoginConfig);

		$oneLoginConfig['idp'] = [
			'entityId' => $this->tenant->idp_entity_id,
			'singleSignOnService' => ['url' => $this->tenant->idp_login_url],
			'singleLogoutService' => ['url' => $this->tenant->idp_logout_url],
			'x509cert' => $this->tenant->idp_x509_cert
		];

		return $oneLoginConfig;
	}

	/**
	 * Set default config values if they weren't set.
	 *
	 * @param array $config
	 *
	 * @return void
	 */
	protected function applyConfigDefaultValues(array &$config) {
		foreach ($this->configDefaultValues() as $key => $default) {
			if(!Arr::get($config, $key)) {
				Arr::set($config, $key, $default);
			}
		}
	}

	/**
	 * Configuration default values that must be replaced with custom ones.
	 *
	 * @return array
	 */
	protected function configDefaultValues() {
		return [
			'sp.entityId' => URL::route('saml.metadata', ['key' => $this->tenant->key]),
			'sp.assertionConsumerService.url' => URL::route('saml.acs', ['key' => $this->tenant->key]),
			'sp.singleLogoutService.url' => URL::route('saml.sls', ['key' => $this->tenant->key])
		];
	}

	/**
	 * Resolve the Name ID Format prefix.
	 *
	 * @param string $format
	 *
	 * @return string
	 */
	protected function resolveNameIdFormatPrefix(string $format): string {
		switch ($format) {
			case 'emailAddress':
			case 'X509SubjectName':
			case 'WindowsDomainQualifiedName':
			case 'unspecified':
				return 'urn:oasis:names:tc:SAML:1.1:nameid-format:' . $format;
			default:
				return 'urn:oasis:names:tc:SAML:2.0:nameid-format:'. $format;
		}
	}
}
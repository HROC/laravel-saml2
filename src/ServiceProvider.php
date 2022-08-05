<?php

namespace Mkhyman\Saml2;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

use Mkhyman\Saml2\Auth as MkhymanAuth;
use Mkhyman\Saml2\ExtendedOneLoginAuth;
use Onelogin\Saml2\Auth as OneLoginAuth;
use OneLogin\Saml2\Utils as OneLoginUtils;
use Mkhyman\Saml2\Models\Tenant;

use Mkhyman\Saml2\Helpers\OneLoginConfigGenerator;

/**
 * Class ServiceProvider
 *
 * @package Mkhyman\Saml2
 */
class ServiceProvider extends IlluminateServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		$this->bootMiddleware();
		$this->bootRoutes();
		$this->bootPublishes();
		$this->bootCommands();
		$this->loadMigrations();
		$this->createBindings();
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return [];
	}

	/**
	 * Create singleton bindings
	 *
	 * @return void
	 */
	protected function createBindings() {
		$this->app->singleton(ExtendedOneLoginAuth::class, function($app) {
			
		});

		$this->app->singleton(MkhymanAuth::class, function($app) {
			return new MkhymanAuth($app->make(ExtendedOneLoginAuth::class), $this->getSessionTenant());
		});

		$this->app->singleton(OneLoginAuth::class, function($app) {
			if (config('saml2.proxyVars', false)) {
				OneLoginUtils::setProxyVars(true);
			}

			$configGenerator = new OneLoginConfigGenerator(config('saml2'), $this->getSessionTenant());
			$oneLoginConfig = $configGenerator->generateOneLoginConfig();

			return new ExtendedOneLoginAuth($oneLoginConfig);
		});
	}

	/**
	 * Bootstrap the routes.
	 *
	 * @return void
	 */
	protected function bootRoutes() {
		if($this->app['config']['saml2.useRoutes'] == true) {
			include __DIR__ . '/Http/routes.php';
		}
	}

	/**
	 * Bootstrap the publishable files.
	 *
	 * @return void
	 */
	protected function bootPublishes() {
		$this->publishes([
			__DIR__ . '/../config/saml2.php' => config_path('saml2.php'),
		]);
	}

	/**
	 * Bootstrap the console commands.
	 *
	 * @return void
	 */
	protected function bootCommands() {
		$this->commands([
			\Mkhyman\Saml2\Commands\CreateTenant::class,
			\Mkhyman\Saml2\Commands\UpdateTenant::class,
			\Mkhyman\Saml2\Commands\DeleteTenant::class,
			\Mkhyman\Saml2\Commands\RestoreTenant::class,
			\Mkhyman\Saml2\Commands\ListTenants::class,
			\Mkhyman\Saml2\Commands\TenantCredentials::class
		]);
	}

	/**
	 * Bootstrap the console commands.
	 *
	 * @return void
	 */
	protected function bootMiddleware() {
		$this->app['router']->aliasMiddleware('saml2.resolveTenant', \Mkhyman\Saml2\Http\Middleware\ResolveTenant::class);
	}

	/**
	 * Load the package migrations.
	 *
	 * @return void
	 */
	protected function loadMigrations() {
		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
	}
	
	protected function getSessionTenant() {
		$key = session('saml2.tenant.uuid');
		return $this->getTenant($key);
	}

	protected function getTenant($key) {
		if(!$tenant = Tenant::where('key', '=', $key)->first()) {
			throw new \Exception('Missing SSO tenant key.');
		}
	}
}

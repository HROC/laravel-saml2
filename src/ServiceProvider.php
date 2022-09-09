<?php

namespace HROC\Saml2;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

use HROC\Saml2\Auth as HROCAuth;
use HROC\Saml2\ExtendedOneLoginAuth;
use Onelogin\Saml2\Auth as OneLoginAuth;
use OneLogin\Saml2\Utils as OneLoginUtils;
use HROC\Saml2\Repositories\TenantRepository;
use HROC\Saml2\Models\Tenant;

use HROC\Saml2\Helpers\OneLoginConfigGenerator;

/**
 * Class ServiceProvider
 *
 * @package HROC\Saml2
 */
class ServiceProvider extends IlluminateServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	private TenantRepository $tenantRepo;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		$this->tenantRepo = new TenantRepository();

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
			if (config('saml2.proxyVars', false)) {
				OneLoginUtils::setProxyVars(true);
			}

			$configGenerator = new OneLoginConfigGenerator(config('saml2'), $this->getSessionTenant());
			$oneLoginConfig = $configGenerator->generateOneLoginConfig();
			
			return new ExtendedOneLoginAuth($oneLoginConfig);
		});

		$this->app->singleton(HROCAuth::class, function($app) {
			return new HROCAuth($app->make(ExtendedOneLoginAuth::class), $this->getSessionTenant());
		});

		$this->app->singleton(OneLoginAuth::class, function($app) {
			return $app->make(ExtendedOneLoginAuth::class);
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
			\HROC\Saml2\Commands\CreateTenant::class,
			\HROC\Saml2\Commands\UpdateTenant::class,
			\HROC\Saml2\Commands\DeleteTenant::class,
			\HROC\Saml2\Commands\RestoreTenant::class,
			\HROC\Saml2\Commands\ListTenants::class,
			\HROC\Saml2\Commands\TenantCredentials::class
		]);
	}

	/**
	 * Bootstrap the console commands.
	 *
	 * @return void
	 */
	protected function bootMiddleware() {
		$router = $this->app->make('router');
		$router->aliasMiddleware('saml2.resolveTenant', \HROC\Saml2\Http\Middleware\ResolveTenant::class);
	}

	/**
	 * Load the package migrations.
	 *
	 * @return void
	 */
	protected function loadMigrations() {
		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
	}
	
	protected function getSessionTenant() : ?Tenant {
		if(!$id = session('saml2.tenant.id')) {
			throw new \Exception('Unable to retrieve SSO tenant from session, missing tenant id.');
		}
		return $this->tenantRepo->findById($id);
	}
}

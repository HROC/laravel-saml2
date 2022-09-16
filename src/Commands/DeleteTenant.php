<?php

namespace Mkhyman\Saml2\Commands;

use Mkhyman\Saml2\Repositories\TenantRepository;
use Mkhyman\Saml2\Helpers\ParserHelper;

/**
 * Class DeleteTenant
 *
 * @package Mkhyman\Saml2\Commands
 */
class DeleteTenant extends \Illuminate\Console\Command
{
    use RendersTenants;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saml2:delete-tenant {tenant}
                            { --safe : Safe deletion }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a tenant by ID, key or UUID';

    /**
     * @var TenantRepository
     */
    protected $tenants;

    /**
     * DeleteTenant constructor.
     *
     * @param TenantRepository $tenants
     */
    public function __construct(TenantRepository $tenants)
    {
        $this->tenants = $tenants;

        if (isset($this->signature)) {
            $this->configureUsingFluentDefinition();
        } else {
            parent::__construct($this->name);
        }
    }

    /**
     * Configure the console command using a fluent definition.
     *
     * @return void
     */
    protected function configureUsingFluentDefinition()
    {
        $parseResponse = ParserHelper::parse($this->signature);

        $name = $parseResponse[0];
        $arguments = $parseResponse[1];
        $options = $parseResponse[2];

        parent::__construct($this->name = $name);

        // After parsing the signature we will spin through the arguments and options
        // and set them on this command. These will already be changed into proper
        // instances of these "InputArgument" and "InputOption" Symfony classes.
        $this->getDefinition()->addArguments($arguments);
        $this->getDefinition()->addOptions($options);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $tenants = $this->tenants->matchAnyIdentifier($this->argument('tenant'), false);

        if($tenants->isEmpty()) {
            $this->error('Cannot find a matching tenant by "' . $this->argument('tenant') . '" identifier');
            return;
        }

        $this->renderTenants($tenants, 'Found tenant(s)');

        if($tenants->count() > 1) {
            $deletingId = $this->ask('We have found several tenants, which one would you like to delete? (enter its ID)');
        }
        else {
            $deletingId = $tenants->first()->id;
        }

        $tenant = $tenants->firstWhere('id', $deletingId);

        if($this->option('safe')) {
            $tenant->delete();

            $this->info('The tenant #' . $deletingId . ' safely deleted. To restore it, run:');
            $this->output->block('php artisan saml2:restore-tenant ' . $deletingId);

            return;
        }

        if(!$this->confirm('Would you like to forcely delete the tenant #' . $deletingId . '? It cannot be reverted.')) {
            return;
        }

        $tenant->forceDelete();

        $this->info('The tenant #' . $deletingId . ' safely deleted.');
    }
}
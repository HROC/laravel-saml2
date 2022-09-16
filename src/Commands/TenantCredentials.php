<?php

namespace Mkhyman\Saml2\Commands;

use Mkhyman\Saml2\Helpers\ConsoleHelper;
use Mkhyman\Saml2\Repositories\TenantRepository;
use Mkhyman\Saml2\Helpers\ParserHelper;

/**
 * Class TenantCredentials
 *
 * @package Mkhyman\Saml2\Commands
 */
class TenantCredentials extends \Illuminate\Console\Command
{
    use RendersTenants;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saml2:tenant-credentials {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List tenant credentials for IdP';

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
        if(!$tenant = $this->tenants->findById($this->argument('id'))) {
            $this->error('Cannot find a tenant #' . $this->argument('id'));
            return;
        }

        $this->renderTenants($tenant, 'The tenant model');
        $this->renderTenantCredentials($tenant);

        $this->output->newLine();
    }
}
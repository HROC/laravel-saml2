<?php

namespace Mkhyman\Saml2\Commands;

use Mkhyman\Saml2\Helpers\ConsoleHelper;
use Mkhyman\Saml2\Helpers\ParserHelper;
use Mkhyman\Saml2\Repositories\TenantRepository;

/**
 * Class UpdateTenant
 *
 * @package Mkhyman\Saml2\Commands
 */
class UpdateTenant extends \Illuminate\Console\Command
{
    use RendersTenants, ValidatesInput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saml2:update-tenant {id}
                            { --k|key= : A tenant custom key }
                            { --entityId= : IdP Issuer URL }
                            { --loginUrl= : IdP Sign on URL }
                            { --logoutUrl= : IdP Logout URL }
                            { --relayStateUrl= : Redirection URL after successful login }
                            { --nameIdFormat= : Name ID Format ("persistent" by default) }
                            { --x509cert= : x509 certificate (base64) }
                            { --metadata= : A custom metadata }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update a Tenant entity (relying identity provider)';

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

        $tenant->update(array_filter([
            'key' => $this->option('key'),
            'idp_entity_id' => $this->option('entityId'),
            'idp_login_url' => $this->option('loginUrl'),
            'idp_logout_url' => $this->option('logoutUrl'),
            'idp_x509_cert' => $this->option('x509cert'),
            'relay_state_url' => $this->option('relayStateUrl'),
            'name_id_format' => $this->resolveNameIdFormat(),
            'metadata' => ConsoleHelper::stringToArray($this->option('metadata'))
        ]));

        if(!$tenant->save()) {
            $this->error('Tenant cannot be saved.');
            return;
        }

        $this->info("The tenant #{$tenant->id} ({$tenant->uuid}) was successfully updated.");

        $this->renderTenantCredentials($tenant);

        $this->output->newLine();
    }
}
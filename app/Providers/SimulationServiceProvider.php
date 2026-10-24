<?php


namespace App\Providers;

use App\Integrations\Banks\Contracts\BankClientInterface;
use App\Repositories\Contracts\BankOfferRepositoryInterface;
use App\Repositories\Contracts\SimulationRepositoryInterface;
use App\Repositories\Eloquent\BankOfferRepository;
use App\Repositories\Eloquent\SimulationRepository;
use App\Services\BankIntegrationService;
use Illuminate\Support\ServiceProvider;

/**
 * Registra no contêiner as dependências do módulo de simulação:
 * repositórios e clientes bancários.
 */
class SimulationServiceProvider extends ServiceProvider
{
    /**
     * Liga interfaces às implementações e monta a lista de clientes bancários.
     */
    public function register(): void
    {
        $this->app->bind(SimulationRepositoryInterface::class, SimulationRepository::class);
        $this->app->bind(BankOfferRepositoryInterface::class, BankOfferRepository::class);

        $this->app->when(BankIntegrationService::class)
            ->needs('$clients')
            ->give(fn() => $this->makeBankClients());
    }

    /**
     * Instancia um cliente para cada banco habilitado em config/banks.php.
     * Para adicionar um banco, basta incluí-lo na configuração.
     *
     * @return BankClientInterface[]
     */
    private function makeBankClients(): array
    {
        return collect(config('banks.banks'))
            ->filter(fn(array $config) => $config['enabled'])
            ->map(fn(array $config, string $key) => new $config['client']($key, $config))
            ->values()
            ->all();
    }
}
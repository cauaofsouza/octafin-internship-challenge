<?php


namespace App\Repositories\Contracts;

use App\DTOs\BankOfferDTO;
use App\Models\Simulation;

/**
 * Acesso a dados das ofertas bancárias.
 */
interface BankOfferRepositoryInterface
{
    /**
     * Grava as ofertas de uma simulação (uma linha por banco).
     *
     * @param Simulation $simulation Simulação dona das ofertas.
     * @param BankOfferDTO[] $offers Ofertas já padronizadas.
     * @param array $requestPayload Payload mascarado enviado aos bancos, para auditoria.
     */
    public function storeMany(Simulation $simulation, array $offers, array $requestPayload): void;
}
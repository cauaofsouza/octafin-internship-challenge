<?php


namespace App\Enums;

/**
 * Situação de uma simulação.
 */
enum SimulationStatus: string
{
    case Pending = 'pending';     // criada, ainda consultando os bancos
    case Processed = 'processed'; // pelo menos um banco deu resposta definitiva
    case Failed = 'failed';       // todos os bancos falharam (erro ou timeout)
}
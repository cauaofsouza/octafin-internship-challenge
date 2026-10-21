<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Clt = 'clt';
    case PublicServant = 'public_servant';
    case InssPensioner = 'inss_pensioner';
}

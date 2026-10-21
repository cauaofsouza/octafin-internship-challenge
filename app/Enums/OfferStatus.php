<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Error = 'error';
    case Timeout = 'timeout';
}

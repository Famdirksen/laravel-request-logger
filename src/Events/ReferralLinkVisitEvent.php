<?php

namespace Famdirksen\LaravelRequestLogger\Events;

use Famdirksen\LaravelRequestLogger\Models\ReferralAccount;

class ReferralLinkVisitEvent
{
    public $referralAccount;

    public function __construct(ReferralAccount $referralAccount)
    {
        $this->referralAccount = $referralAccount;
    }
}

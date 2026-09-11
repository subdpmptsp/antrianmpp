<?php

namespace App\Services;

use App\Contracts\CitizenIdentityVerifier;

class DisabledCitizenIdentityVerifier implements CitizenIdentityVerifier
{
    public function verify(string $nik): array
    {
        return ['available' => false, 'verified' => false, 'name' => null, 'message' => 'Integrasi Dukcapil belum diaktifkan.'];
    }
}

<?php

namespace App\Services;

use App\Contracts\CitizenIdentityVerifier;

class DisabledCitizenIdentityVerifier implements CitizenIdentityVerifier
{
    public function verify(string $nik, string $submittedName): array
    {
        return [
            'available' => false,
            'verified' => false,
            'reference' => null,
            'message' => 'Integrasi Dukcapil belum diaktifkan.',
        ];
    }
}

<?php

namespace App\Contracts;

interface CitizenIdentityVerifier
{
    /** @return array{available:bool,verified:bool,name:?string,message:string} */
    public function verify(string $nik): array;
}

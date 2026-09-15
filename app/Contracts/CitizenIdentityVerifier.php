<?php

namespace App\Contracts;

interface CitizenIdentityVerifier
{
    /**
     * Bandingkan NIK dan nama di backend. Kontrak sengaja tidak mengembalikan
     * nama resmi agar data Dukcapil tidak dapat dipakai sebagai layanan lookup.
     *
     * @return array{available:bool,verified:bool,reference:?string,message:string}
     */
    public function verify(string $nik, string $submittedName): array;
}

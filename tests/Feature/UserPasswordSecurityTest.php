<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserPasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_plain_password_column_is_not_present(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'plain_password'));
        $this->assertNotContains('plain_password', (new User())->getFillable());
    }

    public function test_password_is_hashed_and_hidden_from_serialization(): void
    {
        $user = User::factory()->create(['password' => 'rahasia-testing']);

        $this->assertTrue(Hash::check('rahasia-testing', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('plain_password', $user->toArray());
    }
}

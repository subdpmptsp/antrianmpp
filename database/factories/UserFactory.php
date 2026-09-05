<?php

namespace Database\Factories;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Operator pada sistem nyata selalu harus memiliki loket utama. Buatkan
     * hierarki uji minimal ketika test hanya meminta operator tanpa fixture
     * loket secara eksplisit. Test yang sengaja menguji data rusak memakai
     * query builder langsung agar tidak melewati aturan model produksi.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (User $user): void {
            if ($user->role !== User::ROLE_OPERATOR || ! $user->is_active || $user->counter_id) {
                return;
            }

            $suffix = fake()->unique()->numerify('####');
            $instansi = Instansi::query()->create([
                'nama_instansi' => 'Instansi Uji Operator '.$suffix,
                'zone' => 'ZONA 1',
                'is_active' => true,
                'is_archived' => false,
            ]);
            $service = Service::query()->create([
                'instansi_id' => $instansi->instansi_id,
                'name' => 'Layanan Uji Operator '.$suffix,
                'prefix' => 'T'.$suffix,
                'padding' => 2,
                'is_active' => true,
                'is_archived' => false,
                'is_accepting_queues' => true,
            ]);
            $counter = Counter::query()->create([
                'instansi_id' => $instansi->instansi_id,
                'service_id' => $service->id,
                'code_loket' => 'T'.$suffix,
                'is_active' => true,
                'is_archived' => false,
            ]);

            $user->counter_id = $counter->id;
            $user->service_id = $service->id;
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

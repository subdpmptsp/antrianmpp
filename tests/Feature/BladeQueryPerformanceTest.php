<?php

namespace Tests\Feature;

use App\Filament\Pages\DashboardKiosk;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BladeQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_render_query_count_does_not_grow_per_counter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        foreach (range(1, 12) as $index) {
            $counter = Counter::query()->create([
                'name' => "LOKET {$index}",
                'is_active' => true,
            ]);
            $service = Service::query()->create([
                'name' => "LAYANAN {$index}",
                'prefix' => "Q{$index}",
                'padding' => 3,
                'counter_id' => $counter->id,
                'is_active' => true,
            ]);
            $counter->update(['service_id' => $service->id]);
            Queue::query()->create([
                'service_id' => $service->id,
                'number' => "Q{$index}-001",
                'status' => Queue::STATUS_WAITING,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $data = (new DashboardKiosk())->getViewData();
        view('filament.pages.dashboard-kiosk', $data)->render();
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(8, $queryCount, "Dashboard executed {$queryCount} queries.");
    }

    public function test_blade_templates_do_not_contain_model_queries(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views')),
        );

        foreach ($files as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $path = $file->getPathname();
            $contents = file_get_contents($path);
            $this->assertStringNotContainsString('\\App\\Models\\', $contents, $path);
        }
    }
}

<?php

namespace Tests\Unit;

use App\Models\User;
use Filament\Panel;
use PHPUnit\Framework\TestCase;

class UserPanelAccessTest extends TestCase
{
    public function test_only_admin_and_operator_can_access_filament_panel(): void
    {
        $panel = Panel::make();

        $this->assertTrue((new User(['role' => 'admin']))->canAccessPanel($panel));
        $this->assertTrue((new User(['role' => 'operator']))->canAccessPanel($panel));
        $this->assertFalse((new User(['role' => 'kiosk']))->canAccessPanel($panel));
        $this->assertFalse((new User(['role' => 'tv']))->canAccessPanel($panel));
        $this->assertFalse((new User(['role' => 'unknown']))->canAccessPanel($panel));
    }
}

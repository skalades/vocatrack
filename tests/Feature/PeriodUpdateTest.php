<?php

namespace Tests\Feature;

use App\Models\PklPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected PklPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Negeri 1 Test',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '021-123456',
            'email' => 'admin@smktest.sch.id',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Test',
            'email' => 'admin@smktest.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'PKL Gelombang 1 TP 2026/2027',
            'start_date' => '2026-09-30',
            'end_date' => '2027-03-30',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_period_via_canonical_route(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put("/admin-sekolah/periods/{$this->period->id}", [
                'name' => 'PKL Eksplorasi Industri Pertambangan & Geologi TP 2026/2027',
                'start_date' => '2026-09-30',
                'end_date' => '2027-03-30',
                'is_active' => '1',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('pkl_periods', [
            'id' => $this->period->id,
            'name' => 'PKL Eksplorasi Industri Pertambangan & Geologi TP 2026/2027',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_period_via_backward_compatibility_alias(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put("/adminsekolah/periods/{$this->period->id}", [
                'name' => 'PKL Periode Alias Updated',
                'start_date' => '2026-10-01',
                'end_date' => '2027-04-01',
                'is_active' => '1',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('pkl_periods', [
            'id' => $this->period->id,
            'name' => 'PKL Periode Alias Updated',
            'start_date' => '2026-10-01 00:00:00',
        ]);
    }

    public function test_settings_page_does_not_contain_broken_unhyphenated_period_action(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin-sekolah/settings');

        $response->assertOk();
        $response->assertDontSee('/adminsekolah/periods/${data.id}', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\PklPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PeriodWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_pkl_period(): void
    {
        $school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Testing',
            'email' => 'smk@test.com',
            'address' => 'Jl. Test No. 1',
            'phone' => '021-123456',
            'is_active' => true,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        // Create
        $response = $this->actingAs($admin)->post(route('adminsekolah.periods.store'), [
            'name' => 'PKL Gelombang 2 TP 2026/2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => '1',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('pkl_periods', [
            'school_id' => $school->id,
            'name' => 'PKL Gelombang 2 TP 2026/2027',
            'is_active' => true,
        ]);

        $period = PklPeriod::where('name', 'PKL Gelombang 2 TP 2026/2027')->first();

        // Update
        $updateResponse = $this->actingAs($admin)->put(route('adminsekolah.periods.update', $period), [
            'name' => 'PKL Gelombang 2 Revisi',
            'start_date' => '2027-02-01',
            'end_date' => '2027-07-31',
            'is_active' => '1',
        ]);

        $updateResponse->assertSessionHas('success');
        $this->assertDatabaseHas('pkl_periods', [
            'id' => $period->id,
            'name' => 'PKL Gelombang 2 Revisi',
        ]);

        // Toggle Active
        $toggleResponse = $this->actingAs($admin)->post(route('adminsekolah.periods.toggle-active', $period));
        $toggleResponse->assertSessionHas('success');
        $this->assertFalse($period->fresh()->is_active);
    }
}

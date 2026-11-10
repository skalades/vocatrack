<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_scope_isolates_data_between_schools(): void
    {
        // 1. Create School A
        $schoolA = School::create([
            'npsn' => '11111111',
            'name' => 'SMK Negeri 1 Jakarta',
            'address' => 'Jakarta',
            'phone' => '021-1111',
            'email' => 'smk1@test.com',
            'is_active' => true,
        ]);

        // 2. Create School B
        $schoolB = School::create([
            'npsn' => '22222222',
            'name' => 'SMK Negeri 2 Bandung',
            'address' => 'Bandung',
            'phone' => '022-2222',
            'email' => 'smk2@test.com',
            'is_active' => true,
        ]);

        // Create Company in School A
        Company::create([
            'school_id' => $schoolA->id,
            'name' => 'PT Mitra School A',
            'address' => 'Jakarta',
            'contact_person' => 'PIC A',
            'phone' => '0811',
            'work_days' => '5_days',
        ]);

        // Create Company in School B
        Company::create([
            'school_id' => $schoolB->id,
            'name' => 'PT Mitra School B',
            'address' => 'Bandung',
            'contact_person' => 'PIC B',
            'phone' => '0822',
            'work_days' => '5_days',
        ]);

        // User of School A
        $userA = User::create([
            'school_id' => $schoolA->id,
            'name' => 'Guru School A',
            'email' => 'guruA@test.com',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        // Act as User A
        $this->actingAs($userA);

        // Assert query to Company only returns School A companies
        $companies = Company::all();
        $this->assertCount(1, $companies);
        $this->assertEquals('PT Mitra School A', $companies->first()->name);
    }

    public function test_inactive_school_blocks_user_access(): void
    {
        $inactiveSchool = School::create([
            'npsn' => '33333333',
            'name' => 'SMK Non Aktif',
            'address' => 'Depok',
            'phone' => '021-3333',
            'email' => 'inactive@test.com',
            'is_active' => false,
        ]);

        $user = User::create([
            'school_id' => $inactiveSchool->id,
            'name' => 'Guru Inactive',
            'email' => 'guru_inactive@test.com',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'guru_inactive@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }
}

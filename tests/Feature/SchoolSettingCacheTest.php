<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SchoolSettingCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_settings_are_cached_separately_and_invalidated_after_updates(): void
    {
        $schoolA = School::create([
            'npsn' => '55550001', 'name' => 'Sekolah A', 'address' => 'Alamat', 'phone' => '021', 'email' => 'a@test.sch.id', 'is_active' => true,
        ]);
        $schoolB = School::create([
            'npsn' => '55550002', 'name' => 'Sekolah B', 'address' => 'Alamat', 'phone' => '022', 'email' => 'b@test.sch.id', 'is_active' => true,
        ]);

        SchoolSetting::create(['school_id' => $schoolA->id, 'key' => 'checkin_late', 'value' => '08:15:00']);
        SchoolSetting::create(['school_id' => $schoolB->id, 'key' => 'checkin_late', 'value' => '07:45:00']);

        $this->assertSame('08:15:00', $schoolA->getSetting('checkin_late'));
        $this->assertSame('07:45:00', $schoolB->getSetting('checkin_late'));

        SchoolSetting::where('school_id', $schoolA->id)->where('key', 'checkin_late')->update(['value' => '08:30:00']);
        SchoolSetting::findOrFail(SchoolSetting::where('school_id', $schoolA->id)->value('id'))->update(['value' => '08:30:00']);

        $this->assertSame('08:30:00', $schoolA->getSetting('checkin_late'));
        $this->assertSame('07:45:00', $schoolB->getSetting('checkin_late'));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}

<?php

namespace App\Services;

class DeviceService
{
    /**
     * Resolve layout for teacher portal:
     * - Checks explicit ?view=mobile or ?view=desktop
     * - Checks session('teacher_view_mode')
     * - Auto-detects mobile user agent
     * - Falls back to desktop 'layouts.app'
     */
    public static function teacherLayout(): string
    {
        $request = request();
        if (!$request) {
            return 'layouts.app';
        }

        // 1. Explicit query parameter override (persisted in session)
        $queryMode = $request->query('view');
        if ($queryMode === 'mobile') {
            session(['teacher_view_mode' => 'mobile']);
            return 'layouts.teacher-mobile';
        }
        if ($queryMode === 'desktop') {
            session(['teacher_view_mode' => 'desktop']);
            return 'layouts.app';
        }

        // 2. Persisted session override
        $sessionMode = session('teacher_view_mode');
        if ($sessionMode === 'mobile') {
            return 'layouts.teacher-mobile';
        }
        if ($sessionMode === 'desktop') {
            return 'layouts.app';
        }

        // 3. Auto-detect mobile User-Agent
        $ua = $request->userAgent() ?? '';
        if (preg_match('/(android|iphone|ipad|ipod|mobile|phone|blackberry|iemobile|opera mini)/i', $ua)) {
            return 'layouts.teacher-mobile';
        }

        // 4. Default to standard desktop layout
        return 'layouts.app';
    }

    /**
     * Determine if current request is in mobile teacher view mode.
     */
    public static function isTeacherMobile(): bool
    {
        return static::teacherLayout() === 'layouts.teacher-mobile';
    }
}

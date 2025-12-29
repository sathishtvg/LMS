<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettings
{
    public function get(string $key, $default = null)
    {
        $all = Cache::remember('system_settings_all', 300, function () {
            return SystemSetting::query()->get(['key','value_json'])->keyBy('key');
        });

        return $all[$key]->value_json ?? $default;
    }

    public function put(string $key, array $value, int $updatedBy): void
    {
        SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value_json' => $value, 'updated_by' => $updatedBy]
        );
        $this->forgetCache();
    }

    public function forgetCache(): void
    {
        Cache::forget('system_settings_all');
    }
}

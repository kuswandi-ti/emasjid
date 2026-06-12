<?php

namespace App\Repositories;

use App\Contracts\Repositories\PlatformSettingRepositoryInterface;
use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformSettingRepository implements PlatformSettingRepositoryInterface
{
    private const CACHE_KEY = 'platform_settings';
    private const CACHE_TTL = 3600; // 1 hour

    public function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY . '_all', self::CACHE_TTL, function () {
            return PlatformSetting::all();
        });
    }

    public function get(string $key): ?PlatformSetting
    {
        // Do not cache the Eloquent object — database cache driver serializes it,
        // causing __PHP_Incomplete_Class on deserialization before autoload runs.
        return PlatformSetting::where('key', $key)->first();
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_KEY . "_value_{$key}";

        $value = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = PlatformSetting::where('key', $key)->first();
            // Store a sentinel so we can distinguish "not found" from null
            return $setting ? $setting->value : '__NOT_FOUND__';
        });

        return $value === '__NOT_FOUND__' ? $default : $value;
    }

    public function set(string $key, mixed $value, ?string $description = null): PlatformSetting
    {
        $setting = PlatformSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );

        $this->clearCache($key);

        return $setting;
    }

    public function update(string $key, mixed $value): bool
    {
        $result = PlatformSetting::where('key', $key)->update(['value' => $value]);

        if ($result) {
            $this->clearCache($key);
        }

        return $result;
    }

    public function delete(string $key): bool
    {
        $result = PlatformSetting::where('key', $key)->delete();

        if ($result) {
            $this->clearCache($key);
        }

        return $result;
    }

    public function exists(string $key): bool
    {
        return PlatformSetting::where('key', $key)->exists();
    }

    public function getMany(array $keys): Collection
    {
        return PlatformSetting::whereIn('key', $keys)->get();
    }

    public function setMany(array $settings): bool
    {
        return DB::transaction(function () use ($settings) {
            foreach ($settings as $key => $value) {
                $description = is_array($value) ? ($value['description'] ?? null) : null;
                $settingValue = is_array($value) ? ($value['value'] ?? $value) : $value;

                $this->set($key, $settingValue, $description);
            }

            return true;
        });
    }

    public function getAllAsKeyValue(): array
    {
        return Cache::remember(self::CACHE_KEY . '_key_value', self::CACHE_TTL, function () {
            return PlatformSetting::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear cache for a specific key or all settings.
     */
    private function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget(self::CACHE_KEY . "_{$key}");
            Cache::forget(self::CACHE_KEY . "_value_{$key}");
        }

        Cache::forget(self::CACHE_KEY . '_all');
        Cache::forget(self::CACHE_KEY . '_key_value');
    }
}

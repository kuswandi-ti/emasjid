<?php

namespace App\Contracts\Repositories;

use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Collection;

interface PlatformSettingRepositoryInterface
{
    /**
     * Get all platform settings.
     */
    public function all(): Collection;

    /**
     * Get a platform setting by key.
     */
    public function get(string $key): ?PlatformSetting;

    /**
     * Get the value of a setting by key.
     */
    public function getValue(string $key, mixed $default = null): mixed;

    /**
     * Set a platform setting value (create or update).
     */
    public function set(string $key, mixed $value, ?string $description = null): PlatformSetting;

    /**
     * Update an existing setting.
     */
    public function update(string $key, mixed $value): bool;

    /**
     * Delete a setting.
     */
    public function delete(string $key): bool;

    /**
     * Check if a setting exists.
     */
    public function exists(string $key): bool;

    /**
     * Get multiple settings by keys.
     */
    public function getMany(array $keys): Collection;

    /**
     * Set multiple settings at once.
     */
    public function setMany(array $settings): bool;

    /**
     * Get all settings as key-value pairs.
     */
    public function getAllAsKeyValue(): array;
}

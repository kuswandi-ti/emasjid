<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface UserRepositoryInterface
{
    /**
     * Semua users dengan role super-admin (untuk DataTable query builder).
     */
    public function ownersQueryBuilder(): Builder;

    /**
     * Cari user berdasarkan ID, termasuk soft-deleted jika perlu.
     */
    public function find(int $id): ?User;

    /**
     * Cek apakah email sudah dipakai user lain (exclude $exceptId).
     */
    public function existsByEmail(string $email, ?int $exceptId = null): bool;

    /**
     * Buat user baru.
     */
    public function create(array $data): User;

    /**
     * Update user berdasarkan ID.
     */
    public function update(int $id, array $data): bool;

    /**
     * Soft delete user berdasarkan ID.
     */
    public function delete(int $id): bool;
}

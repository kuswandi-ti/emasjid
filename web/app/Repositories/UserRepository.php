<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Query builder untuk semua users dengan role super-admin.
     * Digunakan oleh Yajra DataTables (server-side processing).
     */
    public function ownersQueryBuilder(): Builder
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->select(['users.id', 'users.name', 'users.email', 'users.created_at']);
    }

    /**
     * Cari user berdasarkan ID.
     */
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Cek apakah email sudah dipakai user lain.
     * Jika $exceptId diberikan, user dengan ID tersebut dikecualikan dari pengecekan.
     */
    public function existsByEmail(string $email, ?int $exceptId = null): bool
    {
        return User::where('email', $email)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    /**
     * Buat user baru.
     * Password harus sudah di-hash sebelum dikirim ke sini (oleh Service layer).
     */
    public function create(array $data): User
    {
        return User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);
    }

    /**
     * Update user berdasarkan ID.
     */
    public function update(int $id, array $data): bool
    {
        return (bool) User::where('id', $id)->update($data);
    }

    /**
     * Soft delete user berdasarkan ID.
     */
    public function delete(int $id): bool
    {
        return (bool) User::where('id', $id)->delete();
    }
}

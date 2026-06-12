<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepo
    ) {}

    /**
     * Mengembalikan query builder untuk Yajra DataTable.
     */
    public function getOwnerQueryBuilder(): Builder
    {
        return $this->userRepo->ownersQueryBuilder();
    }

    /**
     * Membuat akun owner baru dan menetapkan role super-admin.
     *
     * @throws \InvalidArgumentException jika email sudah terdaftar
     */
    public function createOwner(array $data): User
    {
        if ($this->userRepo->existsByEmail($data['email'])) {
            throw new \InvalidArgumentException('Email sudah terdaftar.');
        }

        $user = $this->userRepo->create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * Memperbarui akun owner.
     * Jika password tidak diisi (null/empty string), password lama dipertahankan.
     *
     * @throws \DomainException          jika mencoba mengedit akun sendiri
     * @throws \InvalidArgumentException jika email sudah dipakai user lain
     */
    public function updateOwner(User $user, array $data, int $currentUserId): bool
    {
        if ($user->id === $currentUserId) {
            throw new \DomainException('Anda tidak dapat mengedit akun Anda sendiri.');
        }

        if ($this->userRepo->existsByEmail($data['email'], $user->id)) {
            throw new \InvalidArgumentException('Email sudah terdaftar.');
        }

        $updateData = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        return $this->userRepo->update($user->id, $updateData);
    }

    /**
     * Menghapus akun owner beserta role-nya.
     *
     * @throws \DomainException jika mencoba menghapus akun sendiri
     */
    public function deleteOwner(User $user, int $currentUserId): bool
    {
        if ($user->id === $currentUserId) {
            throw new \DomainException('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->removeRole('super-admin');

        return $this->userRepo->delete($user->id);
    }
}

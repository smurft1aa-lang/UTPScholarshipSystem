<?php
declare(strict_types=1);
namespace UTP\Contracts;

/**
 * Contract for user authentication operations.
 */
interface AuthenticatesUsers
{
    /**
     * @return array{success: bool, error?: string, user_id?: int}
     */
    public function registerUser(string $fullName, string $email, string $password, string $icNumber, string $phone): array;

    /**
     * @return array{success: bool, error?: string, role?: string}
     */
    public function loginUser(string $email, string $password): array;

    /**
     * @return array{id: int, role: string, full_name: string}|null
     */
    public function getCurrentUser(): ?array;
}

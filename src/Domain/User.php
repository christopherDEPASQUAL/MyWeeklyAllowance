<?php

declare(strict_types=1);

namespace App\Domain;

use InvalidArgumentException;

final class User
{
    private string $role;
    private string $email;
    private string $name;
    private string $passwordHash;
    private ?int $parentId;

    public function __construct(string $role, string $email, string $name, string $passwordHash, ?int $parentId)
    {
        $this->role = strtoupper($role);
        $this->validateRole($this->role, $parentId);
        $this->validateRequiredStrings($email, $name, $passwordHash);

        $this->email = $email;
        $this->name = $name;
        $this->passwordHash = $passwordHash;
        $this->parentId = $parentId;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function parentId(): ?int
    {
        return $this->parentId;
    }

    public function isParent(): bool
    {
        return $this->role === 'PARENT';
    }

    public function isChild(): bool
    {
        return $this->role === 'CHILD';
    }

    private function validateRole(string $role, ?int $parentId): void
    {
        if (!in_array($role, ['PARENT', 'CHILD'], true)) {
            throw new InvalidArgumentException('Invalid role');
        }

        if ($role === 'CHILD' && $parentId === null) {
            throw new InvalidArgumentException('Child must have a parentId');
        }

        if ($role === 'PARENT' && $parentId !== null) {
            throw new InvalidArgumentException('Parent cannot have a parentId');
        }
    }

    private function validateRequiredStrings(string $email, string $name, string $passwordHash): void
    {
        if ($email === '' || $name === '' || $passwordHash === '') {
            throw new InvalidArgumentException('Email, name and password hash are required');
        }
    }
}

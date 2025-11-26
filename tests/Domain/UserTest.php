<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testParentHasNoParentIdAndFlags(): void
    {
        $user = new User(
            role: 'PARENT',
            email: 'parent@example.com',
            name: 'Alice Parent',
            passwordHash: 'hashed',
            parentId: null
        );

        $this->assertSame('PARENT', $user->role());
        $this->assertNull($user->parentId());
        $this->assertSame('parent@example.com', $user->email());
        $this->assertSame('Alice Parent', $user->name());
        $this->assertSame('hashed', $user->passwordHash());
        $this->assertTrue($user->isParent());
        $this->assertFalse($user->isChild());
    }

    public function testChildRequiresParentIdAndFlags(): void
    {
        $user = new User(
            role: 'CHILD',
            email: 'child@example.com',
            name: 'Bob Child',
            passwordHash: 'hashed2',
            parentId: 99
        );

        $this->assertSame('CHILD', $user->role());
        $this->assertSame(99, $user->parentId());
        $this->assertTrue($user->isChild());
        $this->assertFalse($user->isParent());
    }

    public function testInvalidRoleThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new User(
            role: 'ADMIN',
            email: 'a@example.com',
            name: 'Nope',
            passwordHash: 'hash',
            parentId: null
        );
    }

    public function testChildWithoutParentIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new User(
            role: 'CHILD',
            email: 'child@example.com',
            name: 'Orphan',
            passwordHash: 'hash',
            parentId: null
        );
    }

    public function testParentWithParentIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new User(
            role: 'PARENT',
            email: 'parent@example.com',
            name: 'HasParent',
            passwordHash: 'hash',
            parentId: 1
        );
    }

    public function testEmailNamePasswordAreRequired(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new User(
            role: 'PARENT',
            email: '',
            name: '',
            passwordHash: '',
            parentId: null
        );
    }
}

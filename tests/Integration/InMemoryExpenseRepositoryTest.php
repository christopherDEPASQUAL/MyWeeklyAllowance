<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\Persistence\InMemory\InMemoryExpenseRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InMemoryExpenseRepositoryTest extends TestCase
{
    public function testSavePersistsAndAllReturnsChronologically(): void
    {
        $repository = new InMemoryExpenseRepository();
        $date = new DateTimeImmutable('2024-06-05');

        $repository->save(weekId: 1, category: 'food', amount: 5.5, date: $date, description: 'burger');
        $repository->save(weekId: 1, category: 'movies', amount: 8.0, date: $date->modify('+1 day'), description: null);

        $all = $repository->all();
        $this->assertCount(2, $all);
        $this->assertSame('food', $all[0]['category']);
        $this->assertSame('burger', $all[0]['description']);
        $this->assertSame('movies', $all[1]['category']);
        $this->assertNull($all[1]['description']);
    }
}

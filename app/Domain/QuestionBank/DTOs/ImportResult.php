<?php

namespace App\Domain\QuestionBank\DTOs;

final readonly class ImportResult
{
    public function __construct(
        public int $imported_count,
        public int $total_points,
        public int $starting_order,
    ) {}

    public function wasSuccessful(): bool
    {
        return $this->imported_count > 0;
    }
}

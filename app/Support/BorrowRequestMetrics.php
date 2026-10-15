<?php

namespace App\Support;

class BorrowRequestMetrics
{
    private bool $active = false;

    private int $queries = 0;

    private float $queryTime = 0;

    public function start(): void
    {
        $this->active = true;
        $this->queries = 0;
        $this->queryTime = 0;
    }

    public function recordQuery(float $time): void
    {
        if ($this->active) {
            $this->queries++;
            $this->queryTime += $time;
        }
    }

    public function finish(): array
    {
        $this->active = false;

        return ['query_count' => $this->queries, 'query_time_ms' => round($this->queryTime, 2)];
    }
}

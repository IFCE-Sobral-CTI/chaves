<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;

class SearchResult
{
    public static function make(LengthAwarePaginator $paginator, string $collection, ?string $term): array
    {
        return [
            $collection => $paginator,
            'count' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'termSearch' => $term,
        ];
    }
}

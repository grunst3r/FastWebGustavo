<?php

namespace App\Services;

/**
 * Paginacion generica para arrays y para Query/Eloquent Builder.
 *
 * Uso:
 *   $page = PaginationService::paginate($users, 15);
 *   $page['data']; $page['total']; $page['last_page'];
 */
class PaginationService
{
    public static function paginate($items, int $perPage = 15, ?int $page = null): array
    {
        $perPage = max(1, $perPage);
        $page = $page ?? (int) ($_GET['page'] ?? 1);
        $page = max(1, $page);

        if (is_array($items)) {
            $total = count($items);
            $slice = array_slice($items, ($page - 1) * $perPage, $perPage);
        } else {
            // Query Builder o Eloquent Builder.
            $total = (clone $items)->count();
            $slice = (clone $items)->forPage($page, $perPage)->get();
        }

        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'data' => $slice,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $total ? (($page - 1) * $perPage) + 1 : 0,
            'to' => min($page * $perPage, $total),
            'has_more' => $page < $lastPage,
        ];
    }
}

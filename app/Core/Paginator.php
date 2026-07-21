<?php

namespace App\Core;

class Paginator
{
    /**
     * Calcula os dados de paginacao.
     *
     * @param int $total       Total de registros
     * @param int $perPage     Registros por pagina
     * @param int $currentPage Pagina atual (1-based)
     * @return array
     */
    public static function paginate(int $total, int $perPage, int $currentPage): array
    {
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;

        return [
            'total'       => $total,
            'perPage'     => $perPage,
            'currentPage' => $currentPage,
            'totalPages'  => $totalPages,
            'offset'      => $offset,
        ];
    }
}

<?php

namespace App\Service;

class PaginationService
{
    public function paginate(int $total = 0, int $PAGE = 1, int $LIMIT = 10): array
    {
        $MAX_PAGE = 1;
        $OFFSET = 0;

        while ($total > $LIMIT) {
            ++$MAX_PAGE;
            $total -= $LIMIT;
        }
        $PAGE = $PAGE > $MAX_PAGE ? $MAX_PAGE : $PAGE;

        if ($PAGE > 1) {
            $OFFSET = ($PAGE - 1) * $LIMIT;
        }

        return [
            [
                'prev' => $PAGE > 1 ? $PAGE - 1 : false,
                'current' => $PAGE,
                'next' => $PAGE < $MAX_PAGE ? $PAGE + 1 : false,
            ],
            [
                'offset' => $OFFSET,
                'limit' => $LIMIT,
            ],
        ];
    }
}

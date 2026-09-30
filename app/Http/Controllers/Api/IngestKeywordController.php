<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives discovered keyword candidates from the external research step
 * (Search Console, Reddit, etc). Dedupes on `term`.
 */
class IngestKeywordController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keywords' => ['required', 'array', 'min:1', 'max:200'],
            'keywords.*.term' => ['required', 'string', 'max:255'],
            'keywords.*.source' => ['nullable', 'string', 'max:100'],
            'keywords.*.search_volume' => ['nullable', 'integer', 'min:0'],
            'keywords.*.competition' => ['nullable', 'string', 'max:50'],
        ]);

        $created = 0;
        foreach ($data['keywords'] as $row) {
            $keyword = Keyword::firstOrCreate(
                ['term' => $row['term']],
                [
                    'source' => $row['source'] ?? 'ai_pipeline',
                    'search_volume' => $row['search_volume'] ?? null,
                    'competition' => $row['competition'] ?? null,
                ]
            );
            if ($keyword->wasRecentlyCreated) {
                $created++;
            }
        }

        return response()->json(['received' => count($data['keywords']), 'created' => $created]);
    }

    public function index(): JsonResponse
    {
        return response()->json(
            Keyword::where('status', Keyword::STATUS_NEW)->orderByDesc('search_volume')->limit(100)->get()
        );
    }
}

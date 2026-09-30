<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Keyword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives AI-drafted articles from the external content pipeline.
 * Lands as status=draft by default — a human reviews and publishes
 * from /admin before anything goes live (Google scaled-content-abuse
 * guard). The caller may explicitly pass `publish: true` to skip the
 * review step for a trusted, already-reviewed batch; this still
 * requires the same bearer token as everything else on this endpoint.
 */
class IngestArticleController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:articles,slug'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'keyword_id' => ['nullable', 'exists:keywords,id'],
            'keyword_term' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'in:'.implode(',', Article::CATEGORIES)],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
            'publish' => ['nullable', 'boolean'],
        ]);

        if (empty($data['keyword_id']) && ! empty($data['keyword_term'])) {
            $data['keyword_id'] = Keyword::firstOrCreate(
                ['term' => $data['keyword_term']],
                ['source' => 'ai_pipeline']
            )->id;
        }
        unset($data['keyword_term']);

        $publish = (bool) ($data['publish'] ?? false);
        unset($data['publish']);

        $data['slug'] = $data['slug'] ?? Article::makeUniqueSlug($data['title']);
        $data['status'] = $publish ? Article::STATUS_PUBLISHED : Article::STATUS_DRAFT;
        $data['generated_by'] = 'ai';
        if ($publish) {
            $data['published_at'] = now();
        }

        $article = Article::create($data);

        return response()->json(['id' => $article->id, 'slug' => $article->slug, 'status' => $article->status], 201);
    }

    /**
     * Published articles missing a cover image — used to back-fill images
     * on articles that were created without one.
     */
    public function missingCover(): JsonResponse
    {
        $articles = Article::published()
            ->whereNull('cover_image_url')
            ->orderByDesc('published_at')
            ->get(['id', 'slug', 'title', 'category', 'excerpt']);

        return response()->json($articles);
    }

    public function update(Request $request, Article $article): JsonResponse
    {
        $data = $request->validate([
            'cover_image_url' => ['required', 'url', 'max:2048'],
        ]);

        $article->update($data);

        return response()->json(['id' => $article->id, 'slug' => $article->slug, 'cover_image_url' => $article->cover_image_url]);
    }
}

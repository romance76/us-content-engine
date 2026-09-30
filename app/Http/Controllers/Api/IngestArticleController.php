<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Keyword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives AI-drafted articles from the external content pipeline.
 * Always lands as status=draft — a human must review and publish
 * from /admin before anything goes live (Google scaled-content-abuse guard).
 */
class IngestArticleController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:articles,slug'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'keyword_id' => ['nullable', 'exists:keywords,id'],
            'keyword_term' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
        ]);

        if (empty($data['keyword_id']) && ! empty($data['keyword_term'])) {
            $data['keyword_id'] = Keyword::firstOrCreate(
                ['term' => $data['keyword_term']],
                ['source' => 'ai_pipeline']
            )->id;
        }
        unset($data['keyword_term']);

        $data['slug'] = $data['slug'] ?? Article::makeUniqueSlug($data['title']);
        $data['status'] = Article::STATUS_DRAFT;
        $data['generated_by'] = 'ai';

        $article = Article::create($data);

        return response()->json(['id' => $article->id, 'slug' => $article->slug, 'status' => $article->status], 201);
    }
}

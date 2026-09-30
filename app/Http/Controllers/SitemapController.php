<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $articles = Article::published()->orderByDesc('published_at')->get(['slug', 'updated_at']);

        $xml = view('sitemap', compact('articles'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}

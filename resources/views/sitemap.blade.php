<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
    </url>
@foreach ($articles as $article)
    <url>
        <loc>{{ route('articles.show', $article) }}</loc>
        <lastmod>{{ $article->updated_at->toAtomString() }}</lastmod>
    </url>
@endforeach
</urlset>

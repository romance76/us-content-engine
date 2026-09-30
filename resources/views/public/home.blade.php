@extends('public.layout')

@section('title', config('app.name').' — '.config('app.tagline', 'Latest articles'))
@section('meta_description', config('app.tagline', ''))

@section('content')
    <div class="space-y-10">
        @forelse ($articles as $article)
            <article class="border-b border-gray-100 pb-8">
                <a href="{{ route('articles.show', $article) }}" class="block group">
                    <h2 class="text-2xl font-semibold group-hover:underline">{{ $article->title }}</h2>
                    @if ($article->excerpt)
                        <p class="mt-2 text-gray-600">{{ $article->excerpt }}</p>
                    @endif
                    <time class="mt-3 block text-sm text-gray-400" datetime="{{ $article->published_at?->toIso8601String() }}">
                        {{ $article->published_at?->format('M j, Y') }}
                    </time>
                </a>
            </article>
        @empty
            <p class="text-gray-400">No articles published yet.</p>
        @endforelse
    </div>

    <div class="mt-10">{{ $articles->links() }}</div>
@endsection

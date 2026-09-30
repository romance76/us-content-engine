<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $article->exists ? 'Edit article' : 'New article' }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif

        @if ($article->exists)
            <div class="flex items-center gap-3 bg-white p-4 rounded-lg shadow-sm">
                <span class="text-sm text-gray-500">
                    Status: <strong>{{ $article->status }}</strong>
                    @if ($article->keyword) · from keyword "{{ $article->keyword->term }}" @endif
                </span>
                <div class="ml-auto flex gap-2">
                    @if ($article->status !== 'published')
                        <form method="POST" action="{{ route('admin.articles.publish', $article) }}">
                            @csrf
                            <button class="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700">Publish</button>
                        </form>
                    @else
                        <a href="{{ route('articles.show', $article) }}" target="_blank" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-sm rounded hover:bg-gray-200">View live</a>
                        <form method="POST" action="{{ route('admin.articles.unpublish', $article) }}">
                            @csrf
                            <button class="px-3 py-1.5 bg-yellow-100 text-yellow-800 text-sm rounded hover:bg-yellow-200">Unpublish</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Delete this article?')">
                        @csrf
                        @method('DELETE')
                        <button class="px-3 py-1.5 bg-red-50 text-red-700 text-sm rounded hover:bg-red-100">Delete</button>
                    </form>
                </div>
            </div>
        @endif

        <form method="POST"
              action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
              class="bg-white p-6 rounded-lg shadow-sm space-y-5">
            @csrf
            @if ($article->exists) @method('PUT') @endif

            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" class="mt-1 block w-full" value="{{ old('title', $article->title) }}" required />
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="slug" value="Slug (leave blank to auto-generate)" />
                <x-text-input id="slug" name="slug" class="mt-1 block w-full" value="{{ old('slug', $article->slug) }}" />
                <x-input-error :messages="$errors->get('slug')" class="mt-1" />
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md">
                        @foreach (['draft', 'in_review', 'published'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $article->status) === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="category" value="Category" />
                    <select id="category" name="category" class="mt-1 block w-full border-gray-300 rounded-md">
                        <option value="">—</option>
                        @foreach (\App\Models\Article::CATEGORIES as $c)
                            <option value="{{ $c }}" @selected(old('category', $article->category) === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="keyword_id" value="Source keyword" />
                    <select id="keyword_id" name="keyword_id" class="mt-1 block w-full border-gray-300 rounded-md">
                        <option value="">—</option>
                        @foreach ($keywords as $k)
                            <option value="{{ $k->id }}" @selected(old('keyword_id', $article->keyword_id) == $k->id)>{{ $k->term }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <x-input-label for="excerpt" value="Excerpt" />
                <textarea id="excerpt" name="excerpt" rows="2" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('excerpt', $article->excerpt) }}</textarea>
            </div>

            <div>
                <x-input-label for="body" value="Body (HTML)" />
                <textarea id="body" name="body" rows="20" class="mt-1 block w-full border-gray-300 rounded-md font-mono text-sm" required>{{ old('body', $article->body) }}</textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-1" />
            </div>

            <fieldset class="border rounded-md p-4 space-y-4">
                <legend class="text-sm font-medium text-gray-600 px-1">SEO</legend>
                <div>
                    <x-input-label for="meta_title" value="Meta title" />
                    <x-text-input id="meta_title" name="meta_title" class="mt-1 block w-full" value="{{ old('meta_title', $article->meta_title) }}" />
                </div>
                <div>
                    <x-input-label for="meta_description" value="Meta description" />
                    <textarea id="meta_description" name="meta_description" rows="2" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('meta_description', $article->meta_description) }}</textarea>
                </div>
                <div>
                    <x-input-label for="cover_image_url" value="Cover image URL" />
                    <x-text-input id="cover_image_url" name="cover_image_url" class="mt-1 block w-full" value="{{ old('cover_image_url', $article->cover_image_url) }}" />
                </div>
            </fieldset>

            <div class="flex justify-end">
                <x-primary-button>{{ $article->exists ? 'Save' : 'Create' }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

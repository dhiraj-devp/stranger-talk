@extends('layouts.admin')

@section('content')
    @php $audit = session('audit', $audit ?? []); @endphp
    <h1>{{ $content->exists ? 'Edit content' : 'New content' }}</h1>
    @foreach ($audit as $item)
        <p class="flash">{{ strtoupper($item['level']) }} — {{ $item['message'] }}</p>
    @endforeach
    @if ($errors->any())
        <div class="panel">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    @if ($preview)
        <section class="panel serp">
            <h2>Search preview</h2>
            <p class="serp-title">{{ $preview->title }}</p>
            <p class="serp-url">{{ $preview->canonical }}</p>
            <p>{{ $preview->description }}</p>
            <p class="lead">Title {{ mb_strlen($preview->title) }} characters · Description {{ mb_strlen($preview->description) }} characters · {{ $preview->robots }}</p>
            <h2>Social preview</h2>
            @if ($preview->ogImage)<p>Image: {{ $preview->ogImage }}</p>@endif
            <p>{{ $preview->ogTitle }}</p>
            <p>{{ $preview->ogDescription }}</p>
        </section>
    @endif
    <form class="panel stack" method="POST" action="{{ $content->exists ? route('admin.content.update', $content) : route('admin.content.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($content->exists) @method('PUT') @endif
        <label>Type
            <select name="type">
                @foreach (['page', 'post', 'guide', 'faq', 'glossary'] as $type)
                    <option value="{{ $type }}" @selected(old('type', $content->type) === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </label>
        <label>Title<input name="title" value="{{ old('title', $content->title) }}" required></label>
        <label>Slug<input name="slug" value="{{ old('slug', $content->slug) }}" required></label>
        <label>Excerpt<textarea name="excerpt">{{ old('excerpt', $content->excerpt) }}</textarea></label>
        <label>Body<textarea name="body" rows="14" required>{{ old('body', $content->body) }}</textarea></label>
        <p class="lead">Paragraphs are separated by a blank line. A line starting with ## becomes a heading. FAQ pages use Q: and A:. Glossary lines use Term | Definition. HTML is not rendered.</p>
        <label>Featured image<input type="file" name="featured_image" accept="image/png,image/jpeg,image/webp,image/gif"></label>
        <label>Image alt<input name="image_alt" value="{{ old('image_alt', $content->image_alt) }}"></label>
        <label>Author
            <select name="author_id">
                <option value="">Organization</option>
                @foreach ($authors as $author)
                    <option value="{{ $author->id }}" @selected((string) old('author_id', $content->author_id) === (string) $author->id)>{{ $author->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Category
            <select name="category_id">
                <option value="">None</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $content->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <fieldset>
            <legend>Tags</legend>
            @foreach ($tags as $tag)
                <label class="check"><input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked(in_array($tag->id, old('tags', $content->exists ? $content->tags->pluck('id')->all() : [])))>{{ $tag->name }}</label>
            @endforeach
        </fieldset>
        <label>Status
            <select name="status">
                <option value="draft" @selected(old('status', $content->status) === 'draft')>draft</option>
                <option value="published" @selected(old('status', $content->status) === 'published')>published</option>
            </select>
        </label>
        <h2>SEO</h2>
        <p class="lead">Aim for a specific title around 50–60 characters and a description around 140–160. Do not repeat a keyword list.</p>
        <label>SEO title<input name="seo_title" value="{{ old('seo_title', $content->seo_title) }}" maxlength="70"></label>
        <label>Meta description<textarea name="seo_description" maxlength="200">{{ old('seo_description', $content->seo_description) }}</textarea></label>
        <label>Canonical<input name="canonical" value="{{ old('canonical', $content->canonical) }}" placeholder="Leave empty to use the page URL"></label>
        <label class="check"><input type="checkbox" name="robots_index" value="1" @checked(old('robots_index', $content->robots_index))> Index</label>
        <label class="check"><input type="checkbox" name="robots_follow" value="1" @checked(old('robots_follow', $content->robots_follow))> Follow</label>
        <label>OG title<input name="og_title" value="{{ old('og_title', $content->og_title) }}"></label>
        <label>OG description<textarea name="og_description">{{ old('og_description', $content->og_description) }}</textarea></label>
        <label>X title<input name="twitter_title" value="{{ old('twitter_title', $content->twitter_title) }}"></label>
        <label>X description<textarea name="twitter_description">{{ old('twitter_description', $content->twitter_description) }}</textarea></label>
        <label>Topic<input name="focus_topic" value="{{ old('focus_topic', $content->focus_topic) }}"></label>
        <label>Other topics<input name="secondary_topics" value="{{ old('secondary_topics', $content->secondary_topics) }}"></label>
        <label>Breadcrumb title<input name="breadcrumb_title" value="{{ old('breadcrumb_title', $content->breadcrumb_title) }}"></label>
        <label>Schema
            <select name="schema_type">
                <option value="">Automatic</option>
                @foreach (['WebPage', 'Article', 'FAQPage'] as $type)
                    <option value="{{ $type }}" @selected(old('schema_type', $content->schema_type) === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </label>
        <label>Custom JSON-LD<textarea name="json_ld">{{ old('json_ld', $content->json_ld) }}</textarea></label>
        <label class="check"><input type="checkbox" name="featured" value="1" @checked(old('featured', $content->featured))> Feature in related modules</label>
        <fieldset>
            <legend>Related pages</legend>
            @foreach ($others as $other)
                <label class="check"><input type="checkbox" name="related_ids[]" value="{{ $other->id }}" @checked(in_array($other->id, old('related_ids', $content->related_ids ?? [])))>{{ $other->title }}</label>
            @endforeach
        </fieldset>
        <button type="submit">Save</button>
    </form>
@endsection

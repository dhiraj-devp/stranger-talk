@extends('layouts.admin')

@section('content')
    <h1>Content</h1>
    <p class="lead"><a href="{{ route('admin.content.create') }}">New page</a></p>
    <section class="panel">
        <table>
            <thead><tr><th>Title</th><th>Type</th><th>URL</th><th>Status</th><th>Index</th></tr></thead>
            <tbody>
            @foreach ($contents as $item)
                <tr>
                    <td><a href="{{ route('admin.content.edit', $item) }}">{{ $item->title }}</a></td>
                    <td>{{ $item->type }}</td>
                    <td>{{ $item->publicPath() }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->robots_index ? 'index' : 'noindex' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="pager">{{ $contents->links() }}</div>
    </section>
    <div class="split">
        <form class="panel stack" method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            <h2>Category</h2>
            <label>Name<input name="name" required></label>
            <label>Slug<input name="slug" required></label>
            <label>Description<textarea name="description"></textarea></label>
            <button type="submit">Add category</button>
            @foreach ($categories as $category)<p>{{ $category->name }}</p>@endforeach
        </form>
        <form class="panel stack" method="POST" action="{{ route('admin.tags.store') }}">
            @csrf
            <h2>Tag</h2>
            <label>Name<input name="name" required></label>
            <label>Slug<input name="slug" required></label>
            <button type="submit">Add tag</button>
            @foreach ($tags as $tag)<p>{{ $tag->name }}</p>@endforeach
        </form>
    </div>
    <form class="panel stack" method="POST" action="{{ route('admin.authors.store') }}" enctype="multipart/form-data">
        @csrf
        <h2>Author</h2>
        <p class="lead">Add a byline only for a real person. Published pages otherwise credit {{ $brand->site_name }}.</p>
        <label>Name<input name="name" required></label>
        <label>Slug<input name="slug" required></label>
        <label>Bio<textarea name="bio"></textarea></label>
        <label>Photo<input type="file" name="image" accept="image/png,image/jpeg,image/webp"></label>
        <label>Photo alt<input name="image_alt"></label>
        <button type="submit">Add author</button>
        @foreach ($authors as $author)<p>{{ $author->name }}</p>@endforeach
    </form>
@endsection

@extends('layouts.admin')

@section('content')
    <h1>Media</h1>
    <form class="panel stack" method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">
        @csrf
        <label>Image<input type="file" name="file" accept="image/png,image/jpeg,image/webp,image/gif" required></label>
        <label>Alt text<input name="alt" maxlength="160"></label>
        <button type="submit">Upload</button>
    </form>
    <section class="panel">
        <table>
            <thead><tr><th>Preview</th><th>Alt</th><th>Size</th><th></th></tr></thead>
            <tbody>
            @foreach ($media as $asset)
                <tr>
                    <td><img src="{{ $asset->url() }}" alt="{{ $asset->alt }}" width="96" height="64" loading="lazy" decoding="async"></td>
                    <td>{{ $asset->alt ?: 'Missing alt' }}</td>
                    <td>{{ $asset->width }}×{{ $asset->height }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.media.destroy', $asset) }}">@csrf @method('DELETE')<button type="submit" class="danger">Delete</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="pager">{{ $media->links() }}</div>
    </section>
@endsection

@extends('layouts.admin')

@section('content')
    <h1>Redirects</h1>
    <p class="lead">Use 301 when a public URL has moved, 302 for a temporary move, and 410 when a page is gone. Destinations stay on this site.</p>
    <form class="panel stack" method="POST" action="{{ route('admin.redirects.store') }}">
        @csrf
        <label>From<input name="from_path" placeholder="/old-page" required></label>
        <label>To<input name="to_path" placeholder="/new-page"></label>
        <label>Status
            <select name="status_code">
                <option value="301">301</option>
                <option value="302">302</option>
                <option value="410">410</option>
            </select>
        </label>
        <label>Notes<input name="notes"></label>
        <label class="check"><input type="checkbox" name="enabled" value="1" checked> Enabled</label>
        <button type="submit">Save redirect</button>
    </form>
    @if ($errors->any())<div class="panel">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <section class="panel">
        <table>
            <thead><tr><th>From</th><th>To</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($redirects as $redirect)
                <tr>
                    <td>{{ $redirect->from_path }}</td>
                    <td>{{ $redirect->to_path ?: '—' }}</td>
                    <td>{{ $redirect->status_code }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.redirects.destroy', $redirect) }}">@csrf @method('DELETE')<button class="danger" type="submit">Delete</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endsection

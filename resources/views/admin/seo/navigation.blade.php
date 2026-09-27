@extends('layouts.admin')

@section('content')
    <h1>Navigation</h1>
    <p class="lead">Header and footer links. Order is the sort number. The sign-in button stays on the public header.</p>
    <form class="panel stack" method="POST" action="{{ route('admin.navigation.store') }}">
        @csrf
        <label>Location
            <select name="location"><option value="header">Header</option><option value="footer">Footer</option></select>
        </label>
        <label>Label<input name="label" required></label>
        <label>URL<input name="url" placeholder="/about or #safety" required></label>
        <label>Group<input name="group_label"></label>
        <label>Sort<input name="sort_order" type="number" min="0" value="0"></label>
        <button type="submit">Add link</button>
    </form>
    <section class="panel">
        <table>
            <thead><tr><th>Place</th><th>Label</th><th>URL</th><th>Sort</th><th></th></tr></thead>
            <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->location }}</td>
                    <td>{{ $item->label }}</td>
                    <td>{{ $item->url }}</td>
                    <td>{{ $item->sort_order }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.navigation.destroy', $item) }}">@csrf @method('DELETE')<button class="danger" type="submit">Remove</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endsection

<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Services\SeoBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function __construct(private SeoBuilder $seo) {}

    public function blog(Request $request): View|RedirectResponse
    {
        return $this->collection($request, 'post', 'Blog', 'Notes on how Koko Meet works and how to handle a random video chat.', '/blog');
    }

    public function guides(Request $request): View|RedirectResponse
    {
        return $this->collection($request, 'guide', 'Guides', 'Short guides for using Koko Meet, from the first minute of a call to leaving one.', '/guides');
    }

    public function post(string $slug): View
    {
        return $this->entry('post', $slug);
    }

    public function guide(string $slug): View
    {
        return $this->entry('guide', $slug);
    }

    public function entry(string $type, string $slug): View
    {
        $content = Content::query()->public()->where('type', $type)->where('slug', $slug)->firstOrFail();

        return $this->show($content);
    }

    public function page(string $slug): View
    {
        abort_if(in_array($slug, Content::RESERVED, true), 404);
        $content = Content::query()->public()->whereIn('type', ['page', 'faq', 'glossary'])->where('slug', $slug)->firstOrFail();

        return $this->show($content);
    }

    private function collection(Request $request, string $type, string $name, string $description, string $path): View|RedirectResponse
    {
        if ((string) $request->query('page') === '1') {
            return redirect($path);
        }
        $items = Content::query()->public()->where('type', $type)->latest('published_at')->paginate(10)->withQueryString();
        $page = max(1, (int) $request->query('page', 1));
        $canonical = $page > 1 ? $path.'?page='.$page : $path;

        return view('content.index', [
            'name' => $name,
            'description' => $description,
            'items' => $items,
            'seo' => $this->seo->collection(
                $name,
                $description,
                $canonical,
                $items->previousPageUrl(),
                $items->nextPageUrl(),
            ),
        ]);
    }

    private function show(Content $content): View
    {
        $content->load(['author', 'category', 'tags']);

        return view('content.show', [
            'content' => $content,
            'related' => $content->relatedItems(),
            'seo' => $this->seo->content($content),
        ]);
    }
}

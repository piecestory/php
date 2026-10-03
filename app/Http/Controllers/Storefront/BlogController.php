<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Content\Models\Post;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class BlogController extends Controller
{
    private const int PER_PAGE = 9;

    public function index(): View
    {
        return view('storefront.blog.index', [
            'posts' => Post::query()->published()->with('media')->latest('published_at')->paginate(self::PER_PAGE),
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->isPublished(), 404);

        $post->load(['media', 'author']);

        return view('storefront.blog.show', [
            'post' => $post,
            'more' => Post::query()->published()->whereKeyNot($post->id)->with('media')->latest('published_at')->limit(3)->get(),
        ]);
    }
}

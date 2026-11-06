<?php

namespace App\Http\Controllers;

use App\Content\ContentRepository;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __construct(private ContentRepository $content) {}

    public function home(): View
    {
        return view('pages.home', ['openCase' => null]);
    }

    public function case(string $slug): View
    {
        $case = $this->content->findCase($slug) ?? abort(404);

        return view('pages.home', ['openCase' => $case]);
    }

    public function events(): View
    {
        return view('pages.events', ['openEvent' => null]);
    }
}

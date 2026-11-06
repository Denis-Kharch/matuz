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
}

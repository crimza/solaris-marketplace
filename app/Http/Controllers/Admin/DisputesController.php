<?php

namespace App\Http\Controllers\Admin;

use App\Dispute;
use App\Http\Requests\Disputes\DisputeFilterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisputesController extends AdminController
{
    public function index(Request $request)
    {
        if(!policy(Dispute::class)->index($request->user())) {
            abort(403);
        }

        $category = $request->get('category', self::ADMIN_CATEGORY_DISPUTES);

        $data = [
            'title' => $this->admin_categories->get($category, self::ADMIN_CATEGORY_DISPUTES),
            'cats' => $this->admin_categories,
            'category' => $category,
            'disputes' => Dispute::orderBy('moderator')->paginate(self::PER_PAGE)->withPath($category),
            'moderators' => Dispute::select('moderator')->where('moderator', '!=', '')->groupBy('moderator')->get(),
        ];

        return view('admin.index', $data);
    }

    public function filter(DisputeFilterRequest $request)
    {
        if(!policy(Dispute::class)->index($request->user())) {
            abort(403);
        }

        $category = $request->get('category', self::ADMIN_CATEGORY_DISPUTES);
        $disputes = Dispute::orderBy('moderator');

        if($request->status) {
            $disputes->where('status', $request->status);
        }

        if($request->moderator) {
            if($request->moderator === 'undefined') {
                $disputes->where('moderator', '');
            } elseif($request->moderator !== 'all') {
                $disputes->where('moderator', $request->moderator);
            }
        }

        $data = [
            'title' => $this->admin_categories->get($category, self::ADMIN_CATEGORY_DISPUTES),
            'cats' => $this->admin_categories,
            'category' => $category,
            'disputes' => $disputes->paginate(self::PER_PAGE)->withPath($category),
            'moderators' => Dispute::select('moderator')->where('moderator', '!=', '')->groupBy('moderator')->get(),
        ];

        return view('admin.index', $data);
    }

}
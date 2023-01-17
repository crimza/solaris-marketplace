<?php

namespace App\Http\Controllers\Admin;

use App\AdvStats;
use Illuminate\Http\Request;

class AdvStatsController extends AdminController
{
    public function index(Request $request)
    {
        if(!policy(AdvStats::class)->index($request->user())) {
            abort(403);
        }

        $category = $request->get('category', self::ADMIN_CATEGORY_ADVSTATS);

        $data = [
            'title' => $this->admin_categories->get($category, self::ADMIN_CATEGORY_ADVSTATS),
            'cats' => $this->admin_categories,
            'category' => $category,
            'advstats' => AdvStats::paginate(self::PER_PAGE)->withPath($category)
        ];

        return view('admin.index', $data);
    }

    public function store(Request $request)
    {
        if(!policy(AdvStats::class)->create($request->user())) {
            abort(403);
        }

        $this->validate($request, ['title' => 'required|max:255']);
        AdvStats::create(['title' => $request->title]);
        return back()->with('flash_success', 'Успешно добавлено');
    }

    public function edit(Request $request, $id)
    {
        if(!policy(AdvStats::class)->update($request->user())) {
            abort(403);
        }

        $stats = AdvStats::findOrFail($id);
        $data = [
            'title' => 'Редактирование статистики по рекламе',
            'stats' => $stats,
            'cats' => $this->admin_categories,
            'category' => self::ADMIN_CATEGORY_ADVSTATS
        ];

        return view('admin.edit', $data);
    }

    public function update(Request $request, $id)
    {
        if(!policy(AdvStats::class)->update($request->user())) {
            abort(403);
        }

        $this->validate($request, ['title' => 'required|max:255']);

        $stats = AdvStats::findOrFail($id);
        $stats->title = $request->title;
        $stats->save();

        return redirect()->route('admin_advstats')->with('flash_success', 'Успешно отредактировано');
    }

    public function destroy(Request $request, $id)
    {
        if(!policy(AdvStats::class)->destroy($request->user())) {
            abort(403);
        }

        AdvStats::findOrFail($id)->delete();
        return back()->with('flash_success', 'Успешно удалено');
    }
}
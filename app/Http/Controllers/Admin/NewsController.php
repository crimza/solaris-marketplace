<?php

namespace App\Http\Controllers\Admin;

use App\News;
use Illuminate\Http\Request;

class NewsController extends AdminController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if(!policy(News::class)->index($request->user())) {
            abort(403);
        }

        $category = $request->get('category', self::ADMIN_CATEGORY_NEWS);
        $title = $this->admin_categories->get($category, self::ADMIN_CATEGORY_NEWS);
        $data = ['title' => $title, 'cats' => $this->getAdminCategories($request->user()), 'category' => $category];
        $data['news'] = News::orderBy('id', 'DESC')->paginate(self::PER_PAGE);
        $data['news']->withPath($category);

        return view('admin.index', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function edit(Request $request)
    {
        if(!policy(News::class)->update($request->user())) {
            abort(403);
        }

        $news_id = $request->get('id');
        $news = (new News)->find($news_id);

        if ($news_id && $news) {
            $data = [
                'title' => __('admin.News editing'),
                'news' => $news,
                'cats' => $this->getAdminCategories($request->user()),
                'category' => self::ADMIN_CATEGORY_NEWS
            ];

            return view('admin.edit', $data);
        } else {
            return redirect('/admin/news');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        if(!policy(News::class)->update($request->user())) {
            abort(403);
        }

        $id = $request->get('id');
        $news = (new News)->find($id);

        if ($id && $news) {
            $this->validate($request, ['title' => 'max:255',]);
            $postData = collect($request->except('_token'));

            foreach ($postData as $k => $v) $news->$k = $v;

            $news->save();

            return redirect('/admin/news/edit?id=' . $news->id, 303)->with('flash_success', __('admin.News successfully updated'));
        } else {
            return redirect('/admin/news');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        if(!policy(News::class)->create($request->user())) {
            abort(403);
        }

        $data = [
            'title' => __('admin.Adding news short'),
            'cats' => $this->getAdminCategories($request->user()),
            'category' => $request->get('category', self::ADMIN_CATEGORY_NEWS),
            'news' => new News
        ];

        return view('admin.components.news.add', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if(!policy(News::class)->create($request->user())) {
            abort(403);
        }

        $this->validate($request, ['title' => 'max:255',]);
        $news = new News;
        $postData = collect($request->except('_token'));

        foreach ($postData as $k => $v) $news->$k = $v;

        $news->save();

        return redirect('/admin/news/edit?id=' . $news->id, 303)->with('flash_success', __('admin.News successfully added'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request)
    {
        if(!policy(News::class)->destroy($request->user())) {
            abort(403);
        }

        $id = $request->get('id');
        $news = (new News)->find($id);

        if (!$id || !$news) return redirect('/admin/news');

        if (News::destroy($id)) {
            $what_removed = __('admin.News deleted');
            $flash_type = 'flash_success';
        } else {
            $what_removed = __('admin.Failed to delete news');
            $flash_type = 'flash_warning';
        }

        return redirect('/admin/news', 303)->with($flash_type, $what_removed);
    }
}

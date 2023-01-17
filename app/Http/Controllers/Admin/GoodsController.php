<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\City;
use App\Good;
use App\Shop;
use Illuminate\Http\Request;

class GoodsController extends AdminController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if(!policy(Good::class)->index($request->user())) {
            abort(403);
        }

        $category = $request->get('category', self::ADMIN_CATEGORY_GOODS);
        $title = $this->admin_categories->get($category, self::ADMIN_CATEGORY_GOODS);
        $data = ['title' => $title, 'cats' => $this->getAdminCategories($request->user()), 'category' => $category];
        $data['goods'] = Good::paginate(self::PER_PAGE);
        $data['goods']->withPath($category);

        return view('admin.index', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function edit(Request $request)
    {
        if(!policy(Good::class)->update($request->user())) {
            abort(403);
        }

        $goodId = $request->get('id');
        $good = (new Good)->find($goodId);

        if ($goodId && $good) {
            $data = [
                'title' => __('admin.Editing goods'),
                'cats' => $this->getAdminCategories($request->user()),
                'category' => $request->get('category', self::ADMIN_CATEGORY_GOODS),
                'good' => $good,
                'cities' => City::all(),
                'shops' => Shop::all(),
                'categories_main' => Category::main(),
                'categories_children' => Category::allChildren()
            ];

            return view('admin.edit', $data);
        } else {
            return redirect('/admin/goods');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        if(!policy(Good::class)->update($request->user())) {
            abort(403);
        }

        $id = $request->get('id');
        $good = (new Good)->find($id);

        if ($id && $good) {
            $this->validate($request, ['title' => 'max:255',]);
            $postData = collect($request->except('_token'));

            foreach ($postData as $k => $v) $good->$k = $v;

            $good->has_quests = $postData->has('has_quests');
            $good->has_ready_quests = $postData->has('has_ready_quests');
            $good->save();

            return redirect('/admin/goods/edit?id=' . $good->id, 303)->with('flash_success', __('admin.Goods successfully updated'));
        } else {
            return redirect('/admin/goods');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        if(!policy(Good::class)->create($request->user())) {
            abort(403);
        }

        $data = [
            'title' => __('admin.Adding goods short'),
            'cats' => $this->getAdminCategories($request->user()),
            'category' => $request->get('category', self::ADMIN_CATEGORY_GOODS),
            'good' => new Good,
            'cities' => City::all(),
            'shops' => Shop::all(),
            'categories_main' => Category::main(),
            'categories_children' => Category::allChildren()
        ];

        return view('admin.components.goods.add', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if(!policy(Good::class)->create($request->user())) {
            abort(403);
        }

        $this->validate($request, [
            'title' => 'max:255',
        ]);

        $good = new Good;
        $postData = collect($request->except('_token'));

        foreach ($postData as $k => $v) $good->$k = $v;

        $good->has_quests = $postData->has('has_quests');
        $good->has_ready_quests = $postData->has('has_ready_quests');
        $good->image_url = !$good->image_url ? '' : $good->image_url;
        $good->save();

        return redirect('/admin/goods/edit?id=' . $good->id, 303)->with('flash_success', __('admin.Goods successfully added'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request)
    {
        if(!policy(Good::class)->delete($request->user())) {
            abort(403);
        }

        $id = $request->get('id');
        $good = (new Good)->find($id);

        if (!$id || !$good) return redirect('/admin/goods');

        if (Good::destroy($id)) {
            $what_removed = __('admin.Goods deleted');
            $flash_type = 'flash_success';
        } else {
            $what_removed = 'Failed to delete goods.';
            $flash_type = 'flash_warning';
        }

        return redirect('/admin/goods', 303)->with($flash_type, $what_removed);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function view(Request $request)
    {
        if(!policy(Good::class)->view($request->user())) {
            abort(403);
        }

        $goodId = $request->get('id');
        $good = (new Good)->find($goodId);

        if ($goodId && $good) {
            $data = [
                'title' => __('admin.Viewing goods'),
                'cats' => $this->getAdminCategories($request->user()),
                'category' => $request->get('category', self::ADMIN_CATEGORY_GOODS),
                'good' => $good,
                'cities' => City::all(),
                'shops' => Shop::all(),
                'categories_main' => Category::main(),
                'categories_children' => Category::allChildren()
            ];

            return view('admin.view', $data);
        } else {
            return redirect('/admin/goods');
        }
    }
}

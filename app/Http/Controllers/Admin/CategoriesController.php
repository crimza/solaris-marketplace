<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use Illuminate\Http\Request;

class CategoriesController extends AdminController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if(!policy(Category::class)->index($request->user())) {
            abort(403);
        }

        $category = $request->get('category', self::ADMIN_CATEGORY_CATEGORIES);
        $title = $this->admin_categories->get($category, self::ADMIN_CATEGORY_CATEGORIES);
        $data = ['title' => $title, 'cats' => $this->getAdminCategories($request->user()), 'category' => $category];
        $data['categories_main'] = Category::mainNoCache();
        $data['categories_children'] = Category::allChildrenNoCache();

        return view('admin.index', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function edit(Request $request)
    {
        if(!policy(Category::class)->update($request->user())) {
            abort(403);
        }

        $category_id = $request->get('id');
        $category = (new Category)->find($category_id);

        if ($category_id && $category) {
            $data = [
                'title' => __('admin.Editing category'),
                'edit_category' => $category,
                'cats' => $this->getAdminCategories($request->user()),
                'category' => self::ADMIN_CATEGORY_CATEGORIES,
                'categories_main' => Category::main()
            ];

            return view('admin.edit', $data);
        } else {
            return redirect('/admin/categories');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        if(!policy(Category::class)->update($request->user())) {
            abort(403);
        }

        $id = $request->get('id');
        $category = (new Category)->find($id);

        if ($id && $category) {
            $this->validate($request, ['title' => 'max:255',]);
            $postData = collect($request->except('_token'));

            foreach ($postData as $k => $v) $category->$k = $v;

            $category->parent_id = $postData->has('parent_id') ? $postData->get('parent_id') : null;
            $category->save();

            Category::clearCache();

            return redirect('/admin/categories/edit?id=' . $category->id, 303)->with('flash_success', __('admin.Category successfully updated'));
        } else {
            return redirect('/admin/categories');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        if(!policy(Category::class)->create($request->user())) {
            abort(403);
        }

        $data = [
            'title' => __('admin.Adding category short'),
            'cats' => $this->getAdminCategories($request->user()),
            'category' => $request->get('category', self::ADMIN_CATEGORY_CATEGORIES),
            'edit_category' => new Category,
            'categories_main' => Category::main()
        ];

        return view('admin.components.categories.add', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if(!policy(Category::class)->create($request->user())) {
            abort(403);
        }

        $this->validate($request, ['title' => 'max:255',]);
        $add_category = new Category;
        $postData = collect($request->except('_token'));

        foreach ($postData as $k => $v) $add_category->$k = $v;

        $add_category->parent_id = $postData->has('parent_id') ? $postData->get('parent_id') : null;
        $add_category->save();

        Category::clearCache();

        return redirect('/admin/categories/edit?id=' . $add_category->id, 303)->with('flash_success', __('admin.Category successfully added'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request)
    {
        if(!policy(Category::class)->destroy($request->user())) {
            abort(403);
        }

        $id = $request->get('id');
        $category = (new Category)->find($id);

        if (!$id || !$category) return redirect('/admin/categories');

        if (Category::destroy($id)) {
            $what_removed = __('admin.Category deleted');
            $flash_type = 'flash_success';
        } else {
            $what_removed = __('admin.Failed to delete category');
            $flash_type = 'flash_warning';
        }

        Category::clearCache();
        return redirect('/admin/categories', 303)->with($flash_type, $what_removed);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function view(Request $request)
    {
        if(!policy(Category::class)->view($request->user())) {
            abort(403);
        }

        $category_id = $request->get('id');
        $category = (new Category)->find($category_id);

        if ($category_id && $category) {
            $data = [
                'title' => __('admin.Viewing category'),
                'edit_category' => $category,
                'cats' => $this->getAdminCategories($request->user()),
                'category' => self::ADMIN_CATEGORY_CATEGORIES,
                'categories_main' => Category::main()
            ];

            return view('admin.view', $data);
        } else {
            return redirect('/admin/categories');
        }
    }
}

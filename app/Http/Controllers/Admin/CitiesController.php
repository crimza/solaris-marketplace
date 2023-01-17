<?php

namespace App\Http\Controllers\Admin;

use App\City;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CitiesController extends AdminController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if(!policy(City::class)->index($request->user())) {
            abort(403);
        }

        $category = $request->get('category', self::ADMIN_CATEGORY_CITIES);
        $title = $this->admin_categories->get($category, self::ADMIN_CATEGORY_CITIES);
        $data = ['title' => $title, 'cats' => $this->getAdminCategories($request->user()), 'category' => $category];
        $data['cities'] = (new City)->paginate(self::PER_PAGE);
        $data['cities']->withPath($category);

        return view('admin.index', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function edit(Request $request)
    {
        if(!policy(City::class)->update($request->user())) {
            abort(403);
        }

        $city_id = $request->get('id');
        $city = (new City)->find($city_id);

        if ($city_id && $city) {
            $data = [
                'title' => __('admin.Editing city'),
                'city' => $city,
                'cats' => $this->getAdminCategories($request->user()),
                'category' => self::ADMIN_CATEGORY_CITIES
            ];

            return view('admin.edit', $data);
        } else {
            return redirect('/admin/cities');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        if(!policy(City::class)->update($request->user())) {
            abort(403);
        }

        $id = $request->get('id');
        $city = (new City)->find($id);

        if ($id && $city) {
            $this->validate($request, ['title' => 'max:255',]);
            $postData = collect($request->except('_token'));

            foreach ($postData as $k => $v) $city->$k = $v;

            $city->save();

            return redirect('/admin/cities/edit?id=' . $city->id, 303)->with('flash_success', __('admin.City successfully updated'));
        } else {
            return redirect('/admin/cities');
        }
    }

    /**
     * @param Request $request
     * @return Factory|Application|View
     */
    public function create(Request $request)
    {
        if(!policy(City::class)->create($request->user())) {
            abort(403);
        }

        $data = [
            'title' => __('admin.Adding city short'),
            'cats' => $this->getAdminCategories($request->user()),
            'category' => $request->get('category', self::ADMIN_CATEGORY_CITIES),
            'city' => new City
        ];

        return view('admin.components.cities.add', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if(!policy(City::class)->create($request->user())) {
            abort(403);
        }

        $this->validate($request, ['title' => 'max:255',]);
        $city = new City;
        $postData = collect($request->except('_token'));

        foreach ($postData as $k => $v) $city->$k = $v;

        $city->save();

        return redirect('/admin/cities/edit?id=' . $city->id, 303)->with('flash_success', __('admin.City successfully added'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request)
    {
        if(!policy(City::class)->destroy($request->user())) {
            abort(403);
        }

        $id = $request->get('id');
        $city = (new City)->find($id);

        if (!$id || !$city) return redirect('/admin/cities');

        if (City::destroy($id)) {
            $what_removed = __('admin.City deleted');
            $flash_type = 'flash_success';
        } else {
            $what_removed = __('admin.Failed to delete city');
            $flash_type = 'flash_warning';
        }

        return redirect('/admin/cities', 303)->with($flash_type, $what_removed);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function view(Request $request)
    {
        if(!policy(City::class)->view($request->user())) {
            abort(403);
        }

        $city_id = $request->get('id');
        $city = (new City)->find($city_id);

        if ($city_id && $city) {
            $data = [
                'title' => __('admin.Viewing city'),
                'city' => $city,
                'cats' => $this->getAdminCategories($request->user()),
                'category' => self::ADMIN_CATEGORY_CITIES
            ];

            return view('admin.view', $data);
        } else {
            return redirect('/admin/cities');
        }
    }
}

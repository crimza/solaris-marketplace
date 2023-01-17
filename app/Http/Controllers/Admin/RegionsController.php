<?php

namespace App\Http\Controllers\Admin;

use App\City;
use App\Region;
use Illuminate\Http\Request;

class RegionsController extends AdminController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        //$this->authorize('view-regions');
        $category = $request->get('category', self::ADMIN_CATEGORY_REGIONS);
        $title = $this->admin_categories->get($category, self::ADMIN_CATEGORY_REGIONS);
        $data = ['title' => $title, 'cats' => $this->getAdminCategories($request->user()), 'category' => $category];
        $data['regions'] = (new Region)->paginate(self::PER_PAGE);
        $data['regions']->withPath($category);
        $data['cities'] = City::all();

        return view('admin.index', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function getEdit(Request $request)
    {
        //$this->authorize('update-regions');

        $region_id = $request->get('id');
        $region = (new Region)->find($region_id);

        if ($region_id && $region) {
            $data = [
                'title' => __('admin.Editing region'),
                'region' => $region,
                'cities' => City::all(),
                'cats' => $this->getAdminCategories($request->user()),
                'category' => self::ADMIN_CATEGORY_REGIONS
            ];

            return view('admin.edit', $data);
        } else {
            return redirect('/admin/regions');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postUpdate(Request $request)
    {
        //$this->authorize('update-regions');

        $id = $request->get('id');
        $region = (new Region)->find($id);

        if ($id && $region) {
            $this->validate($request, ['title' => 'max:255',]);
            $postData = collect($request->except('_token'));

            foreach ($postData as $k => $v) $region->$k = $v;

            $region->save();

            return redirect('/admin/edit_regions?id=' . $region->id, 303)->with('flash_success', __('admin.Region successfully updated'));
        } else {
            return redirect('/admin/regions');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getCreate(Request $request)
    {
        //$this->authorize('create-regions');

        $data = [
            'title' => __('admin.Adding region short'),
            'cats' => $this->getAdminCategories($request->user()),
            'category' => $request->get('category', self::ADMIN_CATEGORY_REGIONS),
            'region' => new Region,
            'cities' => City::all()
        ];

        return view('admin.components.regions.add', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postStore(Request $request)
    {
        //$this->authorize('update-regions');

        $this->validate($request, ['title' => 'max:255',]);
        $region = new Region();
        $postData = collect($request->except('_token'));

        foreach ($postData as $k => $v) $region->$k = $v;

        $region->save();

        return redirect('/admin/edit_regions?id=' . $region->id, 303)->with('flash_success', __('admin.Region successfully added'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getDestroy(Request $request)
    {
        //$this->authorize('destroy-regions');

        $id = $request->get('id');
        $region = (new Region)->find($id);

        if (!$id || !$region) return redirect('/admin/regions');

        if (Region::destroy($id)) {
            $what_removed = __('admin.Region deleted');
            $flash_type = 'flash_success';
        } else {
            $what_removed = 'Failed to delete region.';
            $flash_type = 'flash_warning';
        }

        return redirect('/admin/regions', 303)->with($flash_type, $what_removed);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function getView(Request $request)
    {
        //$this->authorize('view-regions');

        $region_id = $request->get('id');
        $region = (new Region)->find($region_id);

        if ($region_id && $region) {
            $data = [
                'title' => __('admin.Viewing region'),
                'region' => $region,
                'cities' => City::all(),
                'cats' => $this->getAdminCategories($request->user()),
                'category' => self::ADMIN_CATEGORY_REGIONS
            ];

            return view('admin.view', $data);
        } else {
            return redirect('/admin/regions');
        }
    }
}

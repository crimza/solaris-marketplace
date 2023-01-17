<?php

namespace App\Http\Controllers\Admin;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsersController extends AdminController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        if(!policy(User::class)->index($request->user())) {
            abort(403);
        }

        $category = $request->get('category', self::ADMIN_CATEGORY_USERS);
        $title = $this->admin_categories->get($category, self::ADMIN_CATEGORY_USERS);
        $data = ['title' => $title, 'cats' => $this->getAdminCategories($request->user()), 'category' => $category];
        $data['users'] = User::applySearchFilters($request)->paginate(self::PER_PAGE);
        $data['self'] = $request->user();
        $data['action'] = $request->user()->hasRoles() ? 'edit' : 'view';

        return view('admin.index', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function getEdit(Request $request)
    {
        if(!policy(User::class)->update($request->user())) {
            abort(403);
        }

        $user_id = $request->get('id');
        $user = (new User)->find($user_id);

        if ($user_id && $user) {
            $data = [
                'title' => __('admin.Editing user'),
                'user' => $user,
                'cats' => $this->getAdminCategories($request->user()),
                'category' => self::ADMIN_CATEGORY_USERS
            ];

            return view('admin.edit', $data);
        } else {
            return redirect('/admin/users');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postUpdate(Request $request)
    {
        if(!policy(User::class)->update($request->user())) {
            abort(403);
        }

        $id = $request->get('id');
        $user = (new User)->find($id);

        if ($id && $user) {
            $this->validate($request, ['username' => 'max:255',]);
            $old_password = $user->getAuthPassword();
            $user->password = $old_password;
            $postData = collect($request->except('_token'));

            if (isset($postData['password'])) {
                if (!empty($postData['password'])) {
                    $user->password = bcrypt($postData['password']);
                }
                unset($postData['password']);
            }

            foreach ($postData as $k => $v) $user->$k = $v;

            $user->save();

            return redirect('/admin/users/edit?id=' . $user->id, 303)->with('flash_success', __('admin.User successfully updated'));
        } else {
            return redirect('/admin/users');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getCreate(Request $request)
    {
        if(!policy(User::class)->create($request->user())) {
            abort(403);
        }

        $data = [
            'title' => __('admin.Adding user short'),
            'cats' => $this->getAdminCategories($request->user()),
            'category' => $request->get('category', self::ADMIN_CATEGORY_USERS),
            'user' => new User
        ];

        return view('admin.components.users.add', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postStore(Request $request)
    {
        if(!policy(User::class)->create($request->user())) {
            abort(403);
        }

        $this->validate($request, ['title' => 'max:255',]);
        $user = new User;
        $postData = collect($request->except('_token'));

        foreach ($postData as $k => $v) $user->$k = $v;

        $user->password = bcrypt($postData['password']);
        $user->save();

        return redirect('/admin/users/edit?id=' . $user->id, 303)->with('flash_success', __('admin.User successfully added'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getDestroy(Request $request)
    {
        if(!policy(User::class)->destroy($request->user())) {
            abort(403);
        }

        $id = $request->get('id');
        $user = (new User)->find($id);

        if (!$id || !$user) return redirect('/admin/users');

        if (User::destroy($id)) {
            $what_removed = __('admin.User deleted');
            $flash_type = 'flash_success';
        } else {
            $what_removed = __('admin.Failed to delete user');
            $flash_type = 'flash_warning';
        }

        return redirect('/admin/users', 303)->with($flash_type, $what_removed);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function getView(Request $request)
    {
        if(!policy(User::class)->view($request->user())) {
            abort(403);
        }

        $user_id = $request->get('id');
        $user = (new User)->find($user_id);

        if ($user_id && $user) {
            $data = [
                'title' => __('admin.Viewing user'),
                'user' => $user,
                'cats' => $this->getAdminCategories($request->user()),
                'category' => self::ADMIN_CATEGORY_USERS
            ];

            return view('admin.view', $data);
        } else {
            return redirect('/admin/users');
        }
    }
}

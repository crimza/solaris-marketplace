<?php

namespace App\Http\Controllers\Admin;

use App\Role;
use App\RoleUser;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RolesController extends AdminController
{
    public function view(Request $request)
    {
        $data = [
            'title' => __('admin.Editing user'),
            'cats' => $this->getAdminCategories($request->user()),
            'category' => self::ADMIN_CATEGORY_USERS,
            'act' => 'roles.edit',
        ];

        if(!policy(User::class)->update($request->user())) {
            abort(403);
        }

        $data['user'] = User::with(['roles'])->findOrFail($request->get('id'));
        return view('admin.edit', $data);
    }

    public function store(Request $request)
    {
        if(!policy(User::class)->update($request->user())) {
            abort(403);
        }

        $user = User::with(['roles'])->findOrFail($request->get('id'));
        $roleTypeId = $request->get('role_type_id');

        if($user->roles->contains(Role::Admin)) {
            return redirect()->back()->with('flash_danger', 'Пользователю запрещено изменение ролей.');
        }

        if(!Role::getAllRoles()->contains($roleTypeId)) {
            return redirect()->back()->with('flash_warning', 'Роль с таким id не найдена.');
        }

        if($user->roles->contains($roleTypeId)) {
            return redirect()->back()->with('flash_warning', 'Роль с таким id уже задана для этого пользователя.');
        }

        RoleUser::create(['user_id' => $user->id, 'role_id' => $roleTypeId]);
        return redirect('/admin/users/roles/view?id=' . $user->id)->with('flash_success', 'Роль &laquo;' . Role::getName($roleTypeId) . '&raquo; добавлена успешно.');
    }

    public function destroy(Request $request)
    {
        if(!policy(User::class)->destroy($request->user())) {
            abort(403);
        }

        $user = User::with(['roles'])->findOrFail($request->get('user_id'));
        $roleTypeId = $request->get('role_type_id');

        if($user->roles->contains(Role::Admin)) {
            return redirect()->back()->with('flash_error', 'Пользователю запрещено изменение ролей.');
        }

        if(!Role::getAllRoles()->contains($roleTypeId)) {
            return redirect()->back()->with('flash_warning', 'Роль с таким id не найдена.');
        }

        if($user->roles->contains($roleTypeId)) {
            RoleUser::where('user_id', '=', $user->id)->where('role_id', '=', $roleTypeId)->delete();
            User::resetAllCache($user);
        }

        return redirect()->back()->with('flash_success', 'Роль &laquo;' . Role::getName($roleTypeId) . '&raquo; удалена.');
    }
}

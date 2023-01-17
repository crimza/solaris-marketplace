<?php

namespace App;

use Illuminate\Support\Collection;

class Role extends Model
{
    public $table = 'roles';
    public $timestamps = false;
    public $fillable = ['id', 'name'];

    public const User = 1;
    public const JuniorModerator = 2;
    public const SeniorModerator = 3;
    public const Admin = 4;
    public const SecurityService = 5;
    public const Banned = 6;

    public const UserRoleName = '(Пользователь)';
    public const JunModerRoleName = '(Модератор)';
    public const SenModerRoleName = '(Старший модератор)';
    public const AdminRoleName = '(Администратор)';
    public const SecurityServiceRoleName = 'Служба безопасности';
    public const BannedRoleName = '(Забаненный)';

    public const UserRoleDescription = 'Покупатель';
    public const JunModerDescription = 'Модератор (пользователи, тикеты)';
    public const SenModerDescription = 'Старший модератор (пользователи, тикеты, статистика, магазины)';
    public const AdminDescription = 'Администратор (все разделы)';
    public const SecurityServiceDescription = 'Служба безопасности (все разделы)';
    public const BannedDescription = 'Памятник';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    /*public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }*/

    /**
     * @param int $role
     * @return null|string
     */
    public function style(): ?string
    {
        $theme = config('role_colors.theme');
        return config("role_colors.$theme.$this->id") ?? '';
    }

    /**
     * @param int $roleId
     * @return string
     */
    public static function getName(int $roleId): ?string
    {
        switch ($roleId) {
            case self::JuniorModerator:
                return self::JunModerRoleName;
            case self::SeniorModerator:
                return self::SenModerRoleName;
            case self::SecurityService:
                return self::SecurityServiceRoleName;
            case self::Admin:
                return self::AdminRoleName;
            case self::Banned:
                return self::BannedRoleName;
        }

        return self::UserRoleName;
    }

    /**
     * @return Collection
     */
    public static function getAllRoles(): \Illuminate\Support\Collection
    {
        return collect([
            self::User,
            self::JuniorModerator,
            self::SeniorModerator,
            self::SecurityService,
            self::Admin,
            self::Banned
        ]);
    }

    /**
     * @param $roleId
     * @return string|null
     */
    public static function getDescription($roleId): ?string
    {
        switch ($roleId) {
            case self::JuniorModerator:
                return self::JunModerDescription;
            case self::SeniorModerator:
                return self::SenModerDescription;
            case self::SecurityService:
                return self::SecurityServiceDescription;
            case self::Admin:
                return self::AdminDescription;
            case self::Banned:
                return self::BannedDescription;
        }

        return self::UserRoleDescription;
    }
}

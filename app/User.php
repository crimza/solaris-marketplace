<?php

namespace App;

use App\Services\CryptoPaymentPlatformService;
use App\Traits\DateTimeSerializer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * App\User
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string|null $totp_key
 * @property string|null $contacts_other
 * @property string|null $contacts_jabber
 * @property string|null $contacts_telegram
 * @property string $role
 * @property int $wallet_id
 * @property int $active
 * @property int $buy_count
 * @property float $buy_sum
 * @property string|null $remember_token
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @method static Builder|User whereActive($value)
 * @method static Builder|User whereBuyCount($value)
 * @method static Builder|User whereBuySum($value)
 * @method static Builder|User whereContactsJabber($value)
 * @method static Builder|User whereContactsOther($value)
 * @method static Builder|User whereContactsTelegram($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereRole($value)
 * @method static Builder|User whereTotpKey($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @method static Builder|User whereUsername($value)
 * @mixin \Eloquent
 * @property string|null $news_last_read
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @method static Builder|User whereNewsLastRead($value)
 * @property string|null $admin_role_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tickets\Ticket[] $tickets
 * @method static Builder|User whereAdminRoleType($value)
 * @property string|null $notification_last_read_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereNotificationLastReadAt($value)
 * @method static applySearchFilters(\Illuminate\Http\Request $request)
 */
class User extends Authenticatable
{
    use DateTimeSerializer;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'password', 'role', 'news_last_read', 'wallet_id'
    ];

    protected $dateFormat = 'Y-m-d H:i:sO';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    private ?CryptoPaymentPlatformService $cppService = null;

    /**
     * @return int
     */
    public function activeOrdersCount()
    {
        return $this->orders()->where('status', '!=', Order::STATUS_FINISHED)->count();
    }

    public function orders()
    {
        return $this->hasMany('App\Order', 'user_id', 'id');
    }

    public function externalExchanges()
    {
        return $this->hasMany('App\ExternalExchange', 'user_id', 'id');
    }

    public function unreadNewsCount()
    {
        return News::where('created_at', '>=', $this->news_last_read ?: 0)->count();
    }

    /**
     * @return string
     */
    public function getPublicDecoratedName(): string
    {
        $publicName = $this->getPublicName();

        if(!$this->isAdmin()) {
            return $publicName;
        }

        return Cache::store('redis')->remember("public-decorated-name-$this->id", 3600, function() use($publicName) {
            $mainRole = $this->roles->first();

            return "<b class=\"{$mainRole->style()}\">$publicName</b>";
        });
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        if(!$this->isAdmin()) {
            return e($this->username);
        }

        return Cache::store('redis')->remember("public-name-$this->id", 3600, function () {
            $mainRole = $this->roles->first();

            switch ($mainRole->id) {
                case Role::JuniorModerator:
                    return e($this->username.' '.Role::JunModerRoleName);
                case Role::SeniorModerator:
                    return e($this->username.' '.Role::SenModerRoleName);
                case Role::Admin:
                    return e($this->username.' '.Role::AdminRoleName);
                case Role::SecurityService:
                    return Role::SecurityServiceRoleName;
            }

            return e('Пришелец');
        });
    }

    public function avatar()
    {
        if ($this->isAdmin()) {
            return '/assets/img/logo.svg';
        }

        // TODO
        return noavatar();
    }

    /**
     * returns true if role is admin
     * @return bool
     */
    public function isAdmin(): bool
    {
        $roles = $this->roles;

        if($roles->count() < 1) {
            return false;
        }

        return Cache::store('redis')->remember("is-admin-$this->id", 3600, function () use($roles) {
            return (bool) $roles->filter(function ($role) {
                return in_array($role->id, [
                    Role::Admin,
                    Role::SecurityService,
                    Role::SeniorModerator,
                    Role::JuniorModerator]);
            })->count();
        });
    }

    public function isRoleAdmin(): bool
    {
        $roles = $this->roles;

        if($roles->count() < 1) {
            return false;
        }

        foreach ($roles as $role) {
            if($role->id === Role::Admin) {
                return true;
            }
        }

        return false;
    }

    public function tickets(): HasMany
    {
        return $this->hasMany('App\Models\Tickets\Ticket', 'user_id', 'id');
    }

    public function unreadNotifications()
    {
        $query = Notification::where(function ($query) {
            return $query->whereNull('actual_until')->orWhere('actual_until', '>=', Carbon::now());
        });
        if ($this->notification_last_read_at) {
            $query = $query->where('created_at', '>=', $this->notification_last_read_at);
        }
        return $query->get();
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, "role_user", "user_id", "role_id");
    }

    public function scopeApplySearchFilters(\Illuminate\Database\Eloquent\Builder $users, Request $request)
    {
        if (!empty($username = $request->get('username'))) {
            $users = $users->filterUsername($username);
        }

        if (!empty($roleTypeId = $request->get('role_type_id'))) {
            $users = $users->filterRoleTypeId($roleTypeId);
        }

        return $users;
    }

    /**
     * Init services for CPP
     *
     * @return CryptoPaymentPlatformService
     */
    public function initCryptoPaymentService(): CryptoPaymentPlatformService
    {
        if(!$this->cppService) {
            $this->cppService = new CryptoPaymentPlatformService($this);
        }

        return $this->cppService;
    }

    public function scopeFilterUsername($query, $username)
    {
        return $query->where('username', 'LIKE', '%' . $username . '%');
    }

    public function scopeFilterRoleTypeId($query, $roleTypeId) {
        if($roleTypeId == Role::User) {
            return $query->whereDoesntHave('roles');
        }

        return $query->whereHas('roles', function ($query) use ($roleTypeId) {
            $query->where('role_id', '=', $roleTypeId);
        });
    }

    public static function resetPublicCacheNames()
    {
        User::get()->each(function ($user) {
            User::resetPublicCacheName($user);
        });
    }

    public static function resetPublicCacheName(User $user)
    {
        Cache::forget("public-decorated-name-$user->id");
        Cache::forget("public-name-$user->id");
    }

    public static function resetIsAdminsCache()
    {
        User::get()->each(function ($user) {
            User::resetIsAdminCache($user);
        });
    }

    public static function resetIsAdminCache(User $user)
    {
        Cache::forget("is-admin-$user->id");
    }

    public static function resetPolicyCategoriesCache()
    {
        User::get()->each(function ($user) {
            User::resetPolicyCategoryCache($user);
        });
    }

    public static function resetPolicyCategoryCache(User $user)
    {
        Cache::forget("policy-category-index-$user->id");
        Cache::forget("policy-category-view-$user->id");
        Cache::forget("policy-category-create-$user->id");
        Cache::forget("policy-category-update-$user->id");
        Cache::forget("policy-category-destroy-$user->id");
    }

    public static function resetPolicyCitiesCache()
    {
        User::get()->each(function ($user) {
            User::resetPolicyCityCache($user);
        });
    }

    public static function resetPolicyCityCache(User $user)
    {
        Cache::forget("policy-city-index-$user->id");
        Cache::forget("policy-city-view-$user->id");
        Cache::forget("policy-city-create-$user->id");
        Cache::forget("policy-city-update-$user->id");
        Cache::forget("policy-city-destroy-$user->id");
    }

    public static function resetPolicyGoodsAllCache()
    {
        User::get()->each(function ($user) {
            User::resetPolicyGoodsCache($user);
        });
    }

    public static function resetPolicyGoodsCache(User $user)
    {
        Cache::forget("policy-good-index-$user->id");
        Cache::forget("policy-good-view-$user->id");
        Cache::forget("policy-good-create-$user->id");
        Cache::forget("policy-good-update-$user->id");
        Cache::forget("policy-good-destroy-$user->id");
    }

    public static function resetPolicyNewsAllCache()
    {
        User::get()->each(function ($user) {
            User::resetPolicyNewsCache($user);
        });
    }

    public static function resetPolicyNewsCache(User $user)
    {
        Cache::forget("policy-news-index-$user->id");
        Cache::forget("policy-news-view-$user->id");
        Cache::forget("policy-news-create-$user->id");
        Cache::forget("policy-news-update-$user->id");
        Cache::forget("policy-news-destroy-$user->id");
    }

    public static function resetPolicyRegionsCache()
    {
        User::get()->each(function ($user) {
            User::resetPolicyRegionCache($user);
        });
    }

    public static function resetPolicyRegionCache(User $user)
    {
        Cache::forget("policy-region-index-$user->id");
        Cache::forget("policy-region-view-$user->id");
        Cache::forget("policy-region-create-$user->id");
        Cache::forget("policy-region-update-$user->id");
        Cache::forget("policy-region-destroy-$user->id");
    }

    public static function resetPolicyShopsCache()
    {
        User::get()->each(function ($user) {
            User::resetPolicyShopCache($user);
        });
    }

    public static function resetPolicyShopCache(User $user)
    {
        Cache::forget("policy-shop-index-$user->id");
        Cache::forget("policy-shop-view-$user->id");
        Cache::forget("policy-shop-update-$user->id");
        Cache::forget("policy-shop-destroy-$user->id");
    }

    public static function resetPolicyStatsCache()
    {
        User::get()->each(function ($user) {
            User::resetPolicyStatCache($user);
        });
    }

    public static function resetPolicyStatCache(User $user)
    {
        Cache::forget("policy-stats-index-$user->id");
    }

    public static function resetPolicyTicketsCache()
    {
        User::get()->each(function ($user) {
            User::resetPolicyTicketCache($user);
        });
    }

    public static function resetPolicyTicketCache(User $user)
    {
        Cache::forget("policy-stats-index-$user->id");
        Cache::forget("policy-shop-destroy-$user->id");
    }

    public static function resetPolicyUsersCache()
    {
        User::get()->each(function ($user) {
            User::resetPolicyUserCache($user);
        });
    }

    public static function resetPolicyUserCache(User $user)
    {
        Cache::forget("policy-user-index-$user->id");
        Cache::forget("policy-user-view-$user->id");
        Cache::forget("policy-user-create-$user->id");
        Cache::forget("policy-user-update-$user->id");
        Cache::forget("policy-user-destroy-$user->id");
    }

    public static function resetAllCache(User $user)
    {
        self::resetPublicCacheName($user);
        self::resetIsAdminCache($user);
        self::resetPolicyCategoryCache($user);
        self::resetPolicyCityCache($user);
        self::resetPolicyGoodsCache($user);
        self::resetPolicyNewsCache($user);
        self::resetPolicyRegionCache($user);
        self::resetPolicyShopCache($user);
        self::resetPolicyStatCache($user);
        self::resetPolicyTicketCache($user);
        self::resetPolicyUserCache($user);
    }
}

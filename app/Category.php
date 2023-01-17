<?php

namespace App;

use Cache;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

/**
 * App\Category
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string $title
 * @property integer $priority
 * @method static Builder|Category whereId($value)
 * @method static Builder|Category whereParentId($value)
 * @method static Builder|Category whereTitle($value)
 * @method static Builder|Category wherePriority($value)
 * @mixin \Eloquent
 */
class Category extends Model
{
    /** @var Collection */
    private static $_categories = null;
    /** @var Collection */
    private static $_parents = null;
    /** @var Collection */
    private static $_childrens = null;
    public $timestamps = false;
    public $fillable = [
        'parent_id', 'title', 'priority'
    ];
    protected $table = 'categories';
    protected $primaryKey = 'id';

    /**
     * Return main categories
     * @return Category[]|Collection
     */
    public static function main()
    {
        self::group();
        return self::$_parents;
    }

    private static function group()
    {
        if (self::$_categories == null || self::$_parents == null || self::$_childrens == null) {
            self::$_categories = Cache::remember('categories', 60, function () {
                return Category::get();
            });

            self::$_parents = (clone self::$_categories)->filter(function ($item) {
                return $item->parent_id === NULL;
            });

            self::$_childrens = (clone self::$_categories)->filter(function ($item) {
                return $item->parent_id !== NULL;
            });
        }
    }

    /**
     * @return Category[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function mainNoCache()
    {
        $all = (new Category)->get();
        return (clone $all)->filter(function ($item) {
            return $item->parent_id === NULL;
        });
    }

    /**
     * @return Category[]|Collection
     */
    public static function allChildren()
    {
        self::group();
        return self::$_childrens;
    }

    public static function allChildrenNoCache()
    {
        $all = (new Category)->get();
        return (clone $all)->filter(function ($item) {
            return $item->parent_id !== NULL;
        });
    }

    /**
     *
     */
    public static function clearCache()
    {
        Cache::forget('categories');
        self::$_categories = null;
        self::$_parents = null;
        self::$_childrens = null;
        self::group();
    }

    /**
     * Returns children categories
     * @throws \Exception
     */
    public function children()
    {
        self::group();
        if (!$this->isMain()) {
            throw new Exception('This category has no children.');
        }

        return (clone self::$_categories)->where('parent_id', $this->id);
    }

    /**
     * Return true if current category is one of main categories
     * @return bool
     */
    public function isMain()
    {
        return $this->parent_id === NULL;
    }

    /**
     * @return Category
     */
    public function parent()
    {
        self::group();
        return self::getById($this->parent_id)->first();
    }

    public static function getById($categoryId)
    {
        self::group();
        if (is_array($categoryId)) {
            return (clone self::$_categories)->whereIn('id', $categoryId);
        }

        return (clone self::$_categories)->where('id', $categoryId);
    }
}

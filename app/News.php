<?php
/**
 * File: News.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App;


use Auth;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\News
 *
 * @property int $id
 * @property string $title
 * @property string $text
 * @property string $author
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static Builder|News whereAuthor($value)
 * @method static Builder|News whereCreatedAt($value)
 * @method static Builder|News whereId($value)
 * @method static Builder|News whereText($value)
 * @method static Builder|News whereTitle($value)
 * @method static Builder|News whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class News extends Model
{
    protected $table = 'news';
    protected $primaryKey = 'id';

    protected $fillable = [
        'title', 'text', 'author'
    ];

    public function isUnread()
    {
        if (!Auth::check() || !Auth::user()->news_last_read) {
            return FALSE;
        }

        return Auth::user()->news_last_read->lt($this->created_at);
    }
}
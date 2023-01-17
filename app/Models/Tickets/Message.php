<?php

namespace App\Models\Tickets;

use App\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\Tickets\Message
 *
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property string $text
 * @property \Carbon\Carbon $created_at
 * @property-read \App\User $author
 * @method static Builder|Message whereCreatedAt($value)
 * @method static Builder|Message whereId($value)
 * @method static Builder|Message whereText($value)
 * @method static Builder|Message whereTicketId($value)
 * @method static Builder|Message whereUserId($value)
 * @mixin \Eloquent
 */
class Message extends Model
{
    protected $table = 'ticket_messages';
    protected $fillable = [
        'ticket_id', 'user_id', 'text'
    ];

    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function canDelete(): bool
    {
        return $this->author->isAdmin();
    }

    public function files()
    {
        return $this->hasMany('App\Models\Tickets\File', 'message_id', 'id');
    }
}

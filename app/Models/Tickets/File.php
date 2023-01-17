<?php

namespace App\Models\Tickets;

use App\Model;

class File extends Model
{
    protected $table = 'ticket_files';
    protected $fillable = [
        'user_id', 'ticket_id', 'message_id', 'url', 'created_at'
    ];

    public function thumbnail()
    {
        $path = dirname($this->url);
        $name = basename($this->url);

        return $path . '/thumbs/' . $name;
    }
}

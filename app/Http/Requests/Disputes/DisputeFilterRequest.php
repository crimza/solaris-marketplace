<?php

namespace App\Http\Requests\Disputes;

use App\Http\Requests\Request;

/**
 * @property string $status
 * @property string $moderator
 */
class DisputeFilterRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => 'nullable|in:opened,closed',
            'moderator' => 'nullable|string'
        ];
    }
}

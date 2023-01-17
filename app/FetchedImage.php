<?php
/**
 * File: FetchedImage.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App;

use Illuminate\Database\Eloquent\Builder;

/**
 * App\FetchedImage
 *
 * @property int $id
 * @property string $app_id
 * @property string $remote_url
 * @property string $local_url
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static Builder|FetchedImage whereAppId($value)
 * @method static Builder|FetchedImage whereCreatedAt($value)
 * @method static Builder|FetchedImage whereId($value)
 * @method static Builder|FetchedImage whereLocalUrl($value)
 * @method static Builder|FetchedImage whereRemoteUrl($value)
 * @method static Builder|FetchedImage whereUpdatedAt($value)
 * @method static create(array $array)
 * @mixin \Eloquent
 */
class FetchedImage extends Model
{
    protected $table = 'fetched_images';
    protected $primaryKey = 'id';

    protected $fillable = [
        'app_id', 'remote_url', 'local_url'
    ];
}
<?php
/**
 * File: FetchingImages.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages;


trait FetchingImagesTrait
{
    public function remoteImageURL()
    {
        return $this->{($this->remoteImageURLColumn())};
    }

    public function remoteImageURLColumn()
    {
        return $this->remoteImageURLColumn;
    }

    public function localImageURL()
    {
        return $this->{($this->localImageURLColumn())};
    }

    public function localImageURLColumn()
    {
        return $this->localImageURLColumn;
    }

    public function localImageCached()
    {
        return $this->{($this->localImageCachedColumn())};
    }

    public function localImageCachedColumn()
    {
        return $this->localImageCachedColumn;
    }

    public function lanImageURL($ip, $port)
    {
        $remote_url = $this->remoteImageURL();

        try {
            $url = parse_url($remote_url);

            if ($url && $ip && $port) {
                return "http://${ip}:${port}${url['path']}";
            }
        } catch (\Exception $e) {
            // stub
        }

        return $remote_url;
    }
}
<?php

namespace App\Traits;

trait HasProfileImage
{
    public function getProfileImageUrlAttribute()
    {
        if ($this->profile_image && file_exists(storage_path('app/public/profiles/' . $this->profile_image))) {
            return asset('storage/profiles/' . $this->profile_image);
        }
        return asset('storage/profiles/default.png');
    }
}

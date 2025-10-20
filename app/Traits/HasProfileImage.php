<?php

namespace App\Traits;

trait HasProfileImage
{
   public function getProfileImageUrlAttribute()
{
    return $this->profile_image && $this->profile_image !== 'default.png'
        ? \Illuminate\Support\Facades\Storage::url('profiles/' . $this->profile_image)
        : asset('profiles/default.png');
}
}
    
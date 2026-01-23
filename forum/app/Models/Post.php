<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'theme_id',
        'replied_to_id',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    public function parent()
    {
        return $this->belongsTo(Post::class, 'replied_to_id');
    }

    public function replies()
    {
        return $this->hasMany(Post::class, 'replied_to_id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }
}

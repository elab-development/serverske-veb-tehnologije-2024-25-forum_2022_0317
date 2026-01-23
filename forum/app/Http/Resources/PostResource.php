<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'  => $this->id,
            'title'  => $this->title,
            'content' => $this->content,
            'author' => new UserResource($this->whenLoaded('author')),
            'theme' => new ThemeResource($this->whenLoaded('theme')),
            'parent' => new PostResource($this->whenLoaded('parent')),
            'replies' => PostResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->whenCounted('replies'),
            'likes_count' => $this->whenCounted('likes'),
        ];
    }
}

<?php

namespace App;

use Cohensive\Embed\Facades\Embed;
use Illuminate\Database\Eloquent\Model;


class Post extends Model
{
    public function user(){
        return $this->belongsTo('App\User');
    }
    public function tag(){
        return $this->belongsToMany('App\Tag');
    }
	protected $fillable = [
		'title', 'content', 'image', 'description', 'user_id'
	];
    public function getVideoHtmlAttribute()
    {
        $embed = Embed::make($this->video)->parseUrl();

        if (!$embed)
            return '';

        $embed->setAttribute(['width' => 350]);

        return $embed->getHtml();
    }
}

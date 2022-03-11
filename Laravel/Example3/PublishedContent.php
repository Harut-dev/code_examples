<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublishedContent extends Model
{
    use SoftDeletes;
    protected $table = 'published_content';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];

    //protected $fillable = ["category_id","updated_by","created_by"];

    public function craft()
    {
        return $this->hasOne('App\Models\Craft', 'craft_id', 'id');
    }

    public function category()
    {
        return $this->hasOne('App\Models\Categories', 'id', 'category_id');
    }

    public static function add($craft, $content, $type){

        if($craft && $content){
            $item = PublishedContent::where('content_id', $content->id)->first();
            if(!$item){
                $item = new PublishedContent();
                $item->category_id = $craft->category_id;
                $item->craft_id = $craft->id;
                $item->content_id = $content->id;
                $item->facebook_share = 0;
                $item->facebook_post = 0;
                $item->youtube_share = 0;
                $item->blog_share = 0;
                $item->linkedin_share = 0;
                $item->twitter_share = 0;
                $item->instagram_share = 0;
                $item->podcast_share = 0;
                $item->custome_share = 0;
                $item->wordpress_share = 0;
                $item->total_publish_count = 0;
            }

            $item->{$type} = $item->{$type} + 1;
            $item->total_publish_count = $item->total_publish_count + 1;
            $item->save();
        }

    }

}

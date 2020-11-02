<?php

namespace Armincms\NovaComment\Snail;

use Armincms\Snail\Http\Requests\SnailRequest;
use Illuminate\Http\Request;
use Armincms\Snail\Properties\{ID, Text, Boolean}; 

class Comment extends Schema
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Laravelista\Comments\Comment::class;  

    /**
     * The relationships that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $with = [
        'commenter'
    ];

    /**
     * Get the properties displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function properties(Request $request)
    {
        return [
            ID::make(),

            Text::make('Comment', function($resource) {
                return (string) \Illuminate\Mail\Markdown::parse($resource->comment);
            }), 

            Text::make('User', function($resource) {
                return is_null($resource->commenter) ? $resource->guest_name : $resource->commenter->fullname();
            }), 
        ];
    }   

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Armincms\Snail\Http\Requests\SnailRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(SnailRequest $request, $query)
    {
        return $query->whereApproved(1)->whereNull('child_id');
    }
}

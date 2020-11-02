<?php

namespace Armincms\NovaComment\Nova\Metrics;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Nova;
use Armincms\NovaComment\Comment;

class CreatedReplies extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $model = config('comments.model');

        return $this->count($request, $model::whereHas('parent'), 'commenter_type')
                    ->label(function($label) {
                        switch ($label) {
                            case \Armincms\Nova\User::$model:
                                return __('Users');
                                break;

                            case \Armincms\Nova\Admin::$model:
                                return __('Admins');
                                break;
                            
                            default:
                                return __('Guest');
                                break;
                        } 
                    })
                    ->colors(['#1693EB', '#098F56', '#9C6ADE']);
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'created-replies';
    }
}

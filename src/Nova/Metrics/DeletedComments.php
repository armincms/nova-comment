<?php

namespace Armincms\NovaComment\Nova\Metrics;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Nova;
use Armincms\NovaComment\Comment;

class DeletedComments extends Partition
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

        return $this->count($request, $model::query()->onlyTrashed(), 'approved')
                    ->label(function($label) {  
                        return $label ? __('Approved') : __('Spam');
                    })
                    ->colors(['#F5573B', '#8FC15D']);
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
        return 'deleted-comments';
    }
}

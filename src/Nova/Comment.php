<?php

namespace Armincms\NovaComment\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova; 
use Laravel\Nova\Panel; 
use Laravel\Nova\Fields\{ID, Text, Boolean, Markdown, MorphTo, MorphMany}; 
use Laravel\Nova\Resource; 

class Comment extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Armincms\NovaComment\Models\Comment::class; 

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'comment';

    /**
     * The number of resources to show per page via relationships.
     *
     * @var int
     */
    public static $perPageViaRelationship = 25;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'comment'
    ]; 

    /**
     * The relationships that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $with = ['commenter', 'commentable', 'children'];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),  

            $this->when($request->viaRelationship(), function() use ($request) {  
                return MorphTo::make(__('Commenting To'), 'commentable') 
                        ->readonly($request->isMethod('get'))
                        ->types([$request->viaResource()])
                        ->hideFromIndex();
            }), 

            $this->when($this->commentable && $request->isCreateOrAttachRequest(), function() use ($request) { 

                return MorphTo::make(__('Commented On'), 'commentable')  
                        ->types([Nova::resourceForModel($this->commentable)])
                        ->display(function($resource) {
                            return $resource::singularLabel().':'.PHP_EOL.$resource->title();
                        })
                        ->withMeta([
                            'resourceLabel' => __('Commented On')
                        ]);
            }),  

            Boolean::make(__('Approved'), 'approved')->withMeta([
                'value' => $this->approved ?? 1
            ])->required()->sortable(),

            Markdown::make(__('Comment'), 'comment')
                ->alwaysShow()
                ->required()
                ->rules('required'),

            Text::make(__('Comment'), 'comment', function($value) { 
                return (string) \Illuminate\Mail\Markdown::parse($value);
            })->onlyOnIndex()->asHtml(),


           MorphTo::make(__('User'), 'commenter')->types([
                    \Armincms\Nova\User::class,
                    \Armincms\Nova\Admin::class,
                ])  
                ->nullable() 
                ->exceptOnForms()
                ->showOnCreating(! $request->isMethod('get'))
                ->hideFromIndex($this->isGuest($request))
                ->hideFromDetail($this->isGuest($request))
                ->fillUsing(function($request, $model) { 
                    $model::saved(function($model) use ($request) {
                        ! is_null($model->commenter) 
                            || $model->commenter()->associate($request->user())->save();
                    });  
                }),

           Text::make(__('User'), function() {
                return implode(':', array_filter([$this->guest_name, $this->guest_email])) ?: null; 
            }) 
                ->hideFromIndex(! $this->isGuest($request))
                ->hideFromDetail(! $this->isGuest($request)),

            MorphMany::make(__('Replies'), 'children', static::class),
        ];
    } 

    public function isGuest(Request $request)
    {
        return is_null($this->commenter); 
    } 

    /**
     * Determine if the current user can create new resources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function authorizedToCreate(Request $request)
    { 
        if(app(NovaRequest::class)->viaRelationship()) {  
            return $request->newViaResource()->authorizedToAdd($request, static::newModel());
        }   

        return ! $request->isMethod('get') && parent::authorizedToCreate($request);
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [
            new Metrics\CreatedComments,
            new Metrics\CreatedReplies,
            new Metrics\DeletedComments,
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            (new Actions\Approve)
                ->showOnTableRow() 
                ->canSee(function($request) { 
                    if($comment = $request->findModelQuery()->first()) {
                        return ! $comment->approved;
                    }

                    return true;
                }),

            (new Actions\Reject)
                ->showOnTableRow()
                ->canSee(function($request) { 
                    if($comment = $request->findModelQuery()->first()) {
                        return $comment->approved;
                    }

                    return true;
                })
                ->availableForEntireResource()
        ];
    } 

    /**
     * Return the location to redirect the user after creation.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Resource  $resource
     * @return string
     */
    public static function redirectAfterCreate(NovaRequest $request, $resource)
    {
        return '/resources/'.($request->viaRelationship() 
                    ? $request->get('viaResource').'/'.$request->get('viaResourceId') 
                    : static::uriKey());
    }

    /**
     * Return the location to redirect the user after update.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Resource  $resource
     * @return string
     */
    public static function redirectAfterUpdate(NovaRequest $request, $resource)
    { 
        return $request->viaRelationship() 
                    ? '/resources/'. $request->get('viaResource').'/'.$request->get('viaResourceId')
                    : parent::redirectAfterUpdate($request, $resource);
    }
}

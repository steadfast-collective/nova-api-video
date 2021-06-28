<?php

namespace SteadfastCollective\NovaApiVideo;

use Laravel\Nova\Fields\AcceptsTypes;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use SteadfastCollective\ApiVideo\Facades\ApiVideo;

class NovaApiVideo extends Field
{
    use AcceptsTypes;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-api-video';

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var bool
     */
    public $showOnIndex = false;

    /**
     * The callback that should be used to determine the file's storage name.
     *
     * @var callable|null
     */
    public $storeAsCallback;

    /**
     * The column where the file's original name should be stored.
     *
     * @var string
     */
    public $originalNameColumn;

    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string  $attribute
     * @param  callable|null  $storageCallback
     * @return void
     */
    public function __construct($name, $attribute = null, $storageCallback = null)
    {
        parent::__construct($name, $attribute);

        $this->withApiVideoUrl();

        $this->prepareStorageCallback($storageCallback);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return mixed
     */
    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if (is_null(optional($request->input('videoFile'))[$requestAttribute])) {
            return;
        }

        $result = call_user_func(
            $this->storageCallback,
            $request,
            $model,
            $attribute,
            $requestAttribute,
        );

        if ($result === true) {
            return;
        }

        if ($result instanceof Closure) {
            return $result;
        }

        if (! is_array($result)) {
            return $model->{$attribute} = $result;
        }

        foreach ($result as $key => $value) {
            $model->{$key} = $value;
        }
    }

    protected function prepareStorageCallback($storageCallback)
    {
        $this->storageCallback = $storageCallback ?? function ($request, $model, $attribute, $requestAttribute) {
            return $this->mergeExtraStorageColumns($request, [
                $this->attribute => $this->makePrivate($request, $requestAttribute),
                'file_name'      => $request->input('videoFile')[$requestAttribute]['file_name'],
                'file_type'      => $request->input('videoFile')[$requestAttribute]['file_type'],
                'size'           => $request->input('videoFile')[$requestAttribute]['size'],
                'video_duration' => $request->input('videoFile')[$requestAttribute]['video_duration'],
                'api_video_id'   => $request->input('videoFile')[$requestAttribute]['api_video_id'],
            ]);
        };
    }

    /**
     * Make video private in api.video.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $requestAttribute
     * @return string
     */
    protected function makePrivate($request, $requestAttribute)
    {
        return with($request->input('videoFile')[$requestAttribute]['api_video_id'], function ($apiVideoId) use ($request) {
            $apiVideo = ApiVideo::updateVideo($apiVideoId, [
                'public'     => false,
                'mp4Support' => false,
            ]);

            return $apiVideoId;
        });
    }

    /**
     * Merge the specified extra file information columns into the storable attributes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $attributes
     * @return array
     */
    protected function mergeExtraStorageColumns($request, array $attributes)
    {
        if ($this->originalNameColumn) {
            $attributes[$this->originalNameColumn] = $request->input($this->attribute);
        }

        return $attributes;
    }

    /**
     * Specify the callback that should be used to determine the file's storage name.
     *
     * @param  callable  $storeAsCallback
     * @return $this
     */
    public function storeAs(callable $storeAsCallback)
    {
        $this->storeAsCallback = $storeAsCallback;

        return $this;
    }

    /**
     * Specify the column where the file's original name should be stored.
     *
     * @param  string  $column
     * @return $this
     */
    public function storeOriginalName($column)
    {
        $this->originalNameColumn = $column;

        return $this;
    }

    public function withApiVideoUrl()
    {
        $token =  ApiVideo::getDelegateToken();

        return $this->withMeta([
            'api_video_url' => config('api-video.url').'/upload?token='.$token,
        ]);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), [
            'acceptedTypes' => $this->acceptedTypes,
        ]);
    }
}

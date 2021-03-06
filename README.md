[![Latest Version on Packagist](https://img.shields.io/packagist/v/steadfastcollective/nova-api-video.svg?style=flat-square)](https://packagist.org/packages/steadfastcollective/nova-api-video)
[![Total Downloads](https://img.shields.io/packagist/dt/steadfastcollective/nova-api-video.svg?style=flat-square)](https://packagist.org/packages/steadfastcollective/nova-api-video)

## Introduction

Api.video upload field for [Laravel Nova](https://nova.laravel.com/). It allows uploading videos (supports large files) to [api.video](https://api.video/).

## Installation

You can install the package via composer:

```bash
composer require steadfastcollective/nova-api-video
```

## Usage

```php
use SteadfastCollective\NovaApiVideo;

NovaApiVideo::make('Api Video Id', 'api_video_id')
    ->rules('required')
    ->acceptedTypes('video/*');
```

### Store additional attributes

You can call `withAdditionalAttributes()` to save additional attributes to the database. These include 'file_name', 'file_type', 'size' and 'video_duration'.

```php
use SteadfastCollective\NovaApiVideo;

NovaApiVideo::make('Api Video Id', 'api_video_id')
    ->rules('required')
    ->acceptedTypes('video/*')
    ->withAdditionalAttributes();
```

You can also specify which additional attributes you want to save by passing the field names as a parameter.

```php
use SteadfastCollective\NovaApiVideo;

NovaApiVideo::make('Api Video Id', 'api_video_id')
    ->rules('required')
    ->acceptedTypes('video/*')
    ->withAdditionalAttributes('file_name', 'file_type');
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email dev@steadfastcollective.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

# Laravel Cursor Paginator

[![Build Status](https://travis-ci.org/amrnn90/laravel-cursor-paginator.svg?branch=master)](https://travis-ci.org/amrnn90/laravel-cursor-paginator)

Easily have cursor based pagination in your Eloquent models and query builders, read [this article](https://use-the-index-luke.com/sql/partial-results/fetch-next-page) to understand the benefits of cursor pagination and the problems it attempts to solve.

There's another [cursor-pagination](https://github.com/juampi92/cursor-pagination) package but unfortunately it doesn't support retrieving previous pages or multi-column ordering, that's why I decided to create this one.

### Features

- Automatically paginates based on columns ordering.
- Supports multi-column ordering which makes it easy to have a deterministic row sequence.
- Detects if the model has date casts.
- Mutiple cursor directions:
  - **before**: returns the items before the cursor.
  - **before_i**: returns the items before the cursor (including the item at the cursor).
  - **after**: returns the items after the cursor.
  - **after_i**: returns the items after the cursor (including the item at the cursor).

## Installation

First install the package via composer:

```sh
composer require amrnn/laravel-cursor-paginator
```

You can optionally publish the config file:

```sh
php artisan vendor:publish --provider="Amrnn\CursorPaginator\PaginatorServiceProvider"
```

### Register service provider

The package automatically registers itself, but if you need to you can add the service provider manually.

```php
// config/app.php

'providers' => [
    // ...
    Amrnn\CursorPaginator\PaginatorServiceProvider::class,
];
```

## Usage

This package provides a `myCursorPaginate()` method that you can invoke on your Eloquent models or query builders:

```php
Route::get('/posts', function() {
    return Post::select('id')->orderBy('id', 'desc')->myCursorPaginate(5);
})
```

which will return something like this:

```js
{
    /**
    * the result items
    */
    "data": [{"id": 10},{"id": 9},{"id": 8},{"id": 7},{"id": 6}],

    /**
    * number of items per page
    */
    "per_page": 5,

    /**
    * total items in result set for your query
    */
    "total": 10,

    /**
    * the following boundary item if you continue to paginate in this direction
    */
    "next_item": { "id": 5 },

    /**
    * navigation urls, you can change the cursor names in the url query string by
    * editing the (directions) array in config file.
    */
    "first_page_url": "http://localhost:8000/posts?after_i=10",
    "last_page_url": "http://localhost:8000/posts?before_i=1",
    "next_page_url": "http://localhost:8000/posts?after=6",
    "prev_page_url": "http://localhost:8000/posts?before=10",

    /*
    * these provide the cursor data structures.
    * they are given in case you want to construct the url manually,
    * but usually you will  just use the urls shown above.
    */
    "current_page": {...},
    "first_page": {...},
    "last_page": {...},
    "next_page": {...},
    "previous_page": {...},

    /*
    * determine if there are more next/previous items
    */
    "has_next": true,
    "has_previous": false,

}
```

### Options

You can pass an optional first argument to `paginateCursor()` to specify the number of items per page (if left empty a default value from config file is used):

```php
// will return 10 items per page
Post::orderBy('id')->myCursorPaginate(10);
```

The package should automatically determine date casts by inspecting your model. However, if you're invoking the pagination on a plain query builder then you may need to pass a second argument which tells it about the date casts:

```php
// no need to specify date casts here
Post::orderBy('created_at')->myCursorPaginate(10);

// must tell a plain query builder about the dates
DB::table('posts')
    ->orderBy('created_at')
    ->myCursorPaginate(10, ['dates' => ['created_at']]);
```

### Multiple Columns

You can order by multiple columns and pagination should work as expected:

```php
Post::orderBy('created_at')->orderBy('id')->myCursorPaginate();

Post::orderBy('created_at', 'desc')->orderBy('id', 'desc'')->myCursorPaginate();
```

> It's not recommended to mix directions (asc, desc) when ordering by multiple columns. Doing that would make using table indexes hard for your database.

### Caveats

All the columns that you're ordering by must also appear in your select statement, for example the following won't work:

```php
Post::select('id')->orderBy('created_at')->myCursorPaginate();
```

You have to do any of the following instead:

```php
Post::select('id', 'created_at')->orderBy('created_at')->myCursorPaginate();

// or

Post::orderBy('created_at')->myCursorPaginate()

```

## Config

```php
return [
    /**
     *
     * Cursor direction names
     *
     * these appear in the url query string, change their mappings if you need to.
     * for example if you change:
     *
     * 'before' => 'b'
     *
     * then your urls might look like:
     * http://localhost:8000/b=10 instead of http://localhost:8000/before=10
     */
    'directions' => [
        'before' => 'before',
        'before_i' => 'before_i',
        'after' => 'after',
        'after_i' => 'after_i',
    ],

    /**
     * Whether to encode url query.
     *
     * If set to true then your urls might look like:
     * http://localhost:8000/cursor=eyJhZnRlciI6M30 instead of http://localhost:8000/after=3
     */
    'encode_cursor' => false,

    /**
     * Cursor url query name to use when `encode_cursor` set to is `true`.
     *
     * for example if you change:
     * 'encoded_cursor_name' => 'page-id'
     *
     * then your urls might look like:
     * http://localhost:8000/page-id=eyJhZnRlciI6M30 instead of http://localhost:8000/cursor=eyJhZnRlciI6M30
     */
    'encoded_cursor_name' => 'cursor',

    /**
     * Default number of items per page.
     *
     * This can be overridden by passing a first argument to the `myCursorPaginate()` method.
     */
    'per_page' => 10,
];
```

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

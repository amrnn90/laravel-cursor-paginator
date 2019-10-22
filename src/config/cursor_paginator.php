<?php

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
     * Default number of items per page.
     * 
     * This can be overridden by passing a first argument to the `cursorPaginate()` method.
     */
    'per_page' => 10,

    /**
     * Whether to hash url query.
     *
     * If set to true then your urls might look like:
     * http://localhost:8000/cursor=eyJhZnRlciI6M30 instead of http://localhost:8000/after=3
     */
    'hash_cursor' => false,

    /**
     * Cursor url query name to use when `hash_cursor` set to is `true`.
     *
     * for example if you change:
     * 'hash_cursor_name' => 'page-id'
     *
     * then your urls might look like:
     * http://localhost:8000/page-id=eyJhZnRlciI6M30 instead of http://localhost:8000/cursor=eyJhZnRlciI6M30
     */
    'hash_cursor_name' => 'cursor',
];

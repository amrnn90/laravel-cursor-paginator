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
     * This can be overriden by passing a first argument to the `cursorPaginate()` method.
     */
    'per_page' => 10
];

<?php
/*
 * This is where configuration information is stored about the framework. We can add extra options such as the PDO error mode,
 * PDO timeout, or any other attributes that may be useful.
 */
return [
    'database' => [
        'name'          => 'singing_contest',
        'username'      => 'root',
        'password'      => '',
        'connection'    => 'mysql:host=127.0.0.1',
        'options'       => [
            PDO::ATTR_ERRMODE   => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_CASE      => PDO::CASE_NATURAL
        ]
    ],
    'options' => [
        'debug'         => true,
        'production'    => false,
        'array_routing' => false
    ],
    'singing_contest_options' => [
        'contestant_number'     => 10,
        'contest_judges_number' => 3,
        'number_of_rounds'      => 6,
        'min_multiple'          => 0,
        'max_multiple'          => 10,
        'decimal_multiple'      => 1,
        'jugdes_total'          => 5,
        'genre_min_streanght'   => 1,
        'genre_max_streanght'   => 10,
    ]
]
?>

<?php

// path to the original file
$file = __DIR__ . '/backup-prod.json';

// read json string file
$json = file_get_contents($file);

// parse it to an array
$books = json_decode($json);

// filter the list where the network id is not 25 (i.e. eCampusOntario) and reset the array indexes
$filtered_books = array_values(
    array_filter($books, fn ($book) => $book->networkId !== 25)
);

// store contents into a new file
file_put_contents(
    __DIR__ . '/output.json', 
    json_encode(
        $filtered_books, 
        JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE
    )
);
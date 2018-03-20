<?php

// Append content to the end of the cover page book header block.
add_action( 'pb_book_cover_after_book_header', function() {
	printf( '<p>%s</p>', 'Content goes here.' );
} );

// Append content to the end of the cover page table of contents block.
add_action( 'pb_book_cover_after_toc', function() {
	printf( '<p>%s</p>', 'Content goes here.' );
} );

// Append content to the end of the cover page book information block.
add_action( 'pb_book_cover_after_book_info', function() {
	printf( '<p>%s</p>', 'Content goes here.' );
} );

// Append content to the end of the cover page metadata block.
add_action( 'pb_book_cover_after_metadata', function() {
	printf( '<p>%s</p>', 'Content goes here.' );
} );

// Insert content before the cover page footer.
add_action( 'pb_book_cover_before_footer', function() {
	printf( '<p>%s</p>', 'Content goes here.' );
} );

// Insert content before a single (front matter, part, chapter, back matter) page footer.
add_action( 'pb_book_content_before_footer', function() {
	printf( '<p>%s</p>', 'Content goes here.' );
} );

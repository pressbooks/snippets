<?php

// https://discourse.pressbooks.org/t/theme-book-sugestions/391
// Put this at the very bottom of pressbooks.php, refresh the book.
if ( \Pressbooks\Book::isBook() ) {
	add_action( 'init', function () {
		\Pressbooks\Taxonomy::init()->insertTerms();
	} );
}
// Did it insert the licenses? If yes, then remove that line of code.

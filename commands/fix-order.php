<?php

use Pressbooks\Book;

class Ordering
{

    public static function init()
    {
        WP_CLI::add_command('books fix-order', __CLASS__);
    }

    /**
     * Sync book menu order.
     *
     * ## OPTIONS
     *
     * <book_id>
     * : The ID of the book to process.
     *
     * [--dry-run]
     * : Perform a dry run without making any changes.
     *
     * ## EXAMPLES
     *
     * wp books fix-order 123
     * wp books fix-order 123 --dry-run
     */
    public function __invoke($args, $assoc_args): void
    {
        $book_id = $args[0];
        $dry_run = isset($assoc_args['dry-run']);

        if (!is_numeric($book_id)) {
            WP_CLI::error("Please provide a valid book ID.");
        }

        global $wpdb;

        if (!get_site($book_id)) {
            WP_CLI::error("Book ID {$book_id} is not a book.");
        }

        switch_to_blog($book_id);

        collect(Book::getBookStructure())->each(function ($group, $key) use ($wpdb, $dry_run) {
            if ($key === 'front-matter' || $key === 'back-matter') {
                collect($group)->each(function ($item, $index) use ($wpdb, $dry_run) {
                    $newMenuOrder = $index + 1;
                    if ($dry_run) {
                        WP_CLI::log("Would update post ID {$item['ID']} to menu_order {$newMenuOrder}");
                    } else {
                        $wpdb->update(
                            $wpdb->posts,
                            ['menu_order' => $newMenuOrder],
                            ['ID' => $item['ID']]
                        );
                    }
                });
                return;
            }

            if ($key === 'part') {
                collect($group)->each(function ($item, $index) use ($wpdb, $dry_run) {
                    $newMenuOrder = $index + 1;
                    if ($dry_run) {
                        WP_CLI::log("Would update post ID {$item['ID']} to menu_order {$newMenuOrder}");
                    } else {
                        $wpdb->update(
                            $wpdb->posts,
                            ['menu_order' => $newMenuOrder],
                            ['ID' => $item['ID']]
                        );
                    }

                    collect($item['chapters'])->each(function ($child, $childIndex) use ($wpdb, $dry_run) {
                        $newMenuOrder = $childIndex + 1;
                        if ($dry_run) {
                            WP_CLI::log("Would update chapter ID {$child['ID']} to menu_order {$newMenuOrder}");
                        } else {
                            $wpdb->update(
                                $wpdb->posts,
                                ['menu_order' => $newMenuOrder],
                                ['ID' => $child['ID']]
                            );
                        }
                    });
                });
            }
        });

        restore_current_blog();

        if ($dry_run) {
            WP_CLI::success("Dry run complete. No changes were made.");
        } else {
            WP_CLI::success("Menu orders updated successfully.");
        }
    }
}

Ordering::init();
$arguments = ['books', 'fix-order'];

if (isset($args[0])) {
    $arguments[] = $args[0];
}

WP_CLI::run_command($arguments, isset($args[1]) && $args[1] === 'dry-run' ? ['dry-run' => true] : []);

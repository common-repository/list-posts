=== list-posts WordPress Plugin ===
Contributors: magblogapi
Tags: list posts, latest news, combined page, editable archive page
Requires at least: 2.6
Tested up to: 3.0.3
Stable tag: 1.1.1

This is a a plugin that lists the latest posts on any page (or post). It does not use an iframe.
It is extremely simple, and honors permissions and password-protected posts.

== Description ==

This allows you to create "hybrid" pages, with a fixed page content, as well as a list of the latest posts.
This is very, very basic. I want to get fancier in the near future, but this gives me what I need for now.
I created this, because I need a static front page with stable text, but I also want a roll of the latest
dynamic content, displayed below the static text.

== Installation ==

1. Upload the `list-posts` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<!--LIST_POSTS-->` or `[[LIST_POSTS]]` in the HTML view of a page. It will be replaced by the plugin.
1. As of version 1.0, you can specify two items of metadata (Custom Fields) for a given page. This will control what is displayed in the plugin.
1. As of version 1.1, more metadata has been added (Item >= C). You can now specify how many posts are shown (-1 is all of them), and you can now filter for series (from the excellent organize-series plugin).

A. `list_posts_include`
This is a comma-separated list of integers (category IDs), category slugs, or tags; each of which is a tag, category ID or slug. Any posts that claim this category in their taxonomy will be included, unless their category is in `list_posts_exclude`. If this custom field is defined, then *ONLY* those categories will be displayed. If it is not specified, then all categories will be displayed.

B. `list_posts_exclude`
This is a comma-separated list of integers (category IDs), category slugs, or tags; each of which is a tag, category ID or slug.  Any posts that claim this category/tag in their taxonomy will be excluded. Exclude always overrides include. If you exclude a parent category, you cannot include children categories.

C. `list_posts_count`
A simple integer. If set to 0, then all posts in the filter specified will be displayed (watch out -this could be a lot). If not specified, then the default ('num_posts' option, set to 5 for now).

D. `list_posts_series`
This is a comma-separated list of integers or series slugs, each of which is an organize-series plugin series ID or slug. Any posts that are in this series will be included, unless their category is in `list_posts_exclude`. If this custom field is defined, then *ONLY* those series will be displayed. If this field is specified, then list_posts_include is ignored. If the organize-series plugin is not installed and activated, then this will be ignored.

== Changelog ==
1.1.1 -December 22, 2010
	There was a bug in the post count custom field ('list_posts_count'). This has been fixed.
	
1.1 -December 21, 2010
	Added tags to the include and exclude lists. Also added the ability for individual pages to specify a series (organize-series) and a post count.
	
1.0.3 -July 29, 2010
	Now automatically split long posts with no "more" tag.
	
1.0.2 -July 25, 2010
	Fixed an issue where the filter interferes with other content filters.
	
1.0.1 -July 25, 2010
	Fixed an issue where a trailing &gt;\p&lt; element was left behind.
	
== TO DO ==

1. Add an admin page

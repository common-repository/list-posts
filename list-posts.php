<?php
/*
Plugin Name: list-posts
Version: 1.1.1
Plugin URI: http://www.magshare.org
Description: Lists new posts in a manner similar to category listings. Add &lt;!&#45;&#45;LIST_POSTS&#45;&#45;&gt; or [[LIST_POSTS]] to a page or a post to generate the form. This is an extremely simple plugin.
Author: MAGSHARE
Author URI: http://www.magshare.org
*/

class list_posts	// This class is completely static, which means it's basically just a namespace.
{
	static $options_name = 'ListPostsOptions';
		
	static function lp_get_options ( )
	{
		// These are the default options.
		$new_options = array (	'fixed' => array (			// These are strings which are pretty much "fixed." They aren't given to the admin screen.
									'posted' => 'Posted',
									'on' => 'on',
									'in' => 'in',
									'read_post_full' => 'Read This Entire Post',
									'read_post' => 'Read This Post'
									),
								'user_selected' => array (	// These are designed to be changed in a future admin screen.
									// The general heading for the posts.
									'heading_title' => 'Latest Posts',
									// If the post is password-protected, its content is replaced by a password message. This is the text for that message.
									'password_message' => 'This post is password protected. To view it, please enter its password below:',
									// If the post has extended text, then this is used as the link text.
									'read_more' => 'Read More...',
									// This is the number of posts to list.
									'num_posts' => 5	// Default is 5.
									)
								);
		
		// This won't do anything until I write an admin screen.
		$old_lp_ptions = get_option ( self::$options_name );
		
		if ( is_array ( $old_lp_ptions ) && count ( $old_lp_ptions ) )
			{
			foreach ( $old_lp_ptions as $key => $value )
				{
				$new_options[$key] = $value;
				}
			}
		
		// These are generated per page, based on the metadata in the custom fields.
		$text = get_post_meta ( get_the_ID(), 'list_posts_count', true );
		if ( $text )
			{
			$new_options['user_selected']['num_posts'] = intval ( $text );
			}
		
		// If there are categories that the page wishes excluded, they are listed here.
		$text = get_post_meta ( get_the_ID(), 'list_posts_exclude', true );
		if ( $text )
			{
			$text_ar = explode ( ",", $text );
			if ( is_array ( $text_ar ) && count ( $text_ar ) )
				{
				foreach ( $text_ar as $id )
					{
					$id = trim ( $id );
					$new_options['exclude_list'][] = $id;
					}
				}
			}
		
		// This allows the page to specify a series, either by slug or by ID. Specifying these causes list_posts_include to be ignored.
		$text = get_post_meta ( get_the_ID(), 'list_posts_include_series', true );
		if ( $text )
			{
			$text_ar = explode ( ",", $text );
			if ( is_array ( $text_ar ) && count ( $text_ar ) )
				{
				foreach ( $text_ar as $id )
					{
					$id = trim ( $id );
					$new_options['series_list'][] = $id;
					}
				}
			}
		
		// If we are only including certain categories or tags, list them here. If there are series requested, these are ignored.
		$text = get_post_meta ( get_the_ID(), 'list_posts_include', true );
		if ( $text )
			{
			$text_ar = explode ( ",", $text );
			if ( is_array ( $text_ar ) && count ( $text_ar ) )
				{
				foreach ( $text_ar as $id )
					{
					$id = trim ( $id );
					$new_options['include_list'][] = $id;
					}
				}
			}
	
		return $new_options;
	}

	static function lp_get_the_password_form($id)
	{
		$options = self::lp_get_options ( );
		$id = "password_$id";
		$output = '<form action="'.get_option('siteurl').'/wp-pass.php" method="post">
			<p>'.__($options['user_selected']['password_message']).'</p>
			<p><label for="'.__($id).'">' . __("Password:") . '</label> <input name="post_password" id="'.__($id).'" type="password" size="20" /> <input type="submit" name="Submit" value="' . __("Submit") . '" /></p>
			</form>';
		return $output;
	}
	
	static function lp_display_list ( $in_content )
	{
		if ( preg_match ( "/\[\[\s?LIST_POSTS\s?\]\]/", $in_content ) || preg_match ( "/\<\!\-\-\s?LIST_POSTS\s?\-\-\>/", $in_content ) )
			{
			$options = self::lp_get_options ( );
			$posts = get_posts(array('numberposts' => -1));
			
			$display = '';
			$alt = 0;
			$counter = 0;

			foreach ( $posts as $mypost )
				{
				$should_list = false;
				$cats = wp_get_post_categories ( $mypost->ID );
				$tags = wp_get_post_tags ( $mypost->ID );
					
				if ( function_exists ( taxonomy_exists ) && taxonomy_exists ( 'series' ) && function_exists ( get_series_ID ) && is_array ( $options['series_list'] ) && count ( $options['series_list'] ) )
					{
					$terms = wp_get_post_terms ( $mypost->ID, 'series' );
					if ( is_array ( $terms ) && count ( $terms ) )
						{
						foreach ( $options['series_list'] as $series_term )
							{
							$ser_ID = get_series_ID ( $series_term );
							if ( $ser_ID )
								{
								$series_term = $ser_ID;
								}
							
							if ( intval ( $series_term ) == intval ( $terms[0]->term_id ) )
								{
								$should_list = true;
								break;
								}
							}
						}
					}
				else
					{
					// We start off optimistic. Show the post if it, or one of its ancestors, shows up in our include list.
					if ( (is_array ( $cats ) && count ( $cats )) || (is_array ( $tags ) && count ( $tags )) )
						{
						// Series list has priority over categories and tags. The organize-series plugin must be installed and activated.
						if ( is_array ( $options['include_list'] ) && count ( $options['include_list'] ) )
							{
							foreach ( $options['include_list'] as $cat )
								{
								$category_data = get_category ( $cat );
								
								if ( !$category_data )
									{
									$category_data = get_category_by_slug ( $cat );
									}

								if ( $category_data )
									{
									$cat = intval ( $category_data->term_id );
									}
								
								foreach ( $cats as $post_cat )
									{
									$post_cat = intval ( $post_cat );

									if ( cat_is_ancestor_of ( $cat, $post_cat ) || ($cat == $post_cat) )
										{
										$should_list = true;
										}
									}
								
								foreach ( $tags as $post_tag )
									{
									if ( strtolower ( trim ( $post_tag->name ) ) ==  strtolower ( trim ( $cat ) ) )
										{
										$should_list = true;
										}
									}
								}
							}
						else	// Show all cats.
							{
							$should_list = true;
							}
						}
					}

				// If any of the post's categories or tags appear in the exclude list, it does not get shown. This happens, even for series.
				if ( is_array ( $options['exclude_list'] ) && count ( $options['exclude_list'] ) )
					{
					foreach ( $options['exclude_list'] as $cat )
						{
						$category_data = get_category ( $cat );
						
						if ( !$category_data )
							{
							$category_data = get_category_by_slug ( $cat );
							}

						if ( $category_data )
							{
							$cat = intval ( $category_data->term_id );
							}
						
						foreach ( $cats as $post_cat )
							{
							$post_cat = intval ( $post_cat );
							
							// categories can be ancestors of this category.
							if ( cat_is_ancestor_of ( $cat, $post_cat ) || ($cat == $post_cat) )
								{
								$should_list = false;
								}
							}
						
						foreach ( $tags as $post_tag )
							{
							if ( strtolower ( trim ( $post_tag->name ) ) ==  strtolower ( trim ( $cat ) ) )
								{
								$should_list = false;
								}
							}
						}
					}
				
				if ( $should_list )	// Only display the properly vetted posts.
					{
					$counter++;
					$url = get_permalink( $mypost->ID );
					$title = htmlspecialchars( $mypost->post_title );
					$meta = '';
					$header_link = true;
					foreach ( $cats as $cat )
						{
						if ( $meta )
							{
							$meta .= ", ";
							}
						$meta .= '<a class="listposts_cat_link" href="'.get_category_link($cat).'" title="'.sprintf(__("View all posts in %s"), get_the_category_by_ID($cat)).'">'.get_the_category_by_ID($cat).'</a>';
						}
					$meta = ' '.__($options['fixed']['on']).' <a class="listposts_date_link" title="View all posts made on '.date ( "l, F jS, Y", strtotime ( $mypost->post_date) ).'" href="'.date ( "?\m=Ymd", strtotime ( $mypost->post_date) ).'">'.date ( "l, F jS, Y", strtotime ( $mypost->post_date) )."</a> ".__($options['fixed']['in'])." $meta";
					$content = $mypost->post_content;
					if ( !empty($mypost->post_password) )
						{ // if there's a password
						if ( stripslashes($_COOKIE['wp-postpass_'.COOKIEHASH]) != $mypost->post_password )
							{	// and it doesn't match the cookie
							$content = self::lp_get_the_password_form($mypost->ID);
							$header_link = false;
							}
						}

					$content = get_extended ( $content );
					
					if ( !$content['extended'] && (strlen ( $content['main'] ) > 256) )
						{
						$stpos = strpos ( $content['main'], "</p>" );
						
						if ( !$stpos )
							{
							$stpos = strpos ( $content['main'], 10 );
							
							if ( $stpos )
								{
								$stpos++;
								}
							}
						else
							{
							$stpos += 4;
							}
						
						if ( !$stpos )
							{
							$stpos = strpos ( $content['main'], 13 );
							
							if ( $stpos )
								{
								$stpos++;
								}
							}
						
						if ( !$stpos )
							{
							$stpos = 256;
							$stpos += intval ( strpos ( $content['main'], 32 ) );
							}
						
						if ( $stpos )
							{
							$content['extended'] = substr ( $content['main'], $stpos );
							$content['main'] = substr ( $content['main'], 0, $stpos );
							}
						}
					
					$display .= '<div class="listposts_post listposts_alt_'.intval($alt).'">';
						$alt = ($alt == 0) ? 1 : 0;
						$rp_t = 'read_post';
						if ( $content['extended'] )
							{
							$rp_t .= '_full';
							}
						$display .= '<div class="listposts_post_title" title="'.__($options['fixed'][$rp_t]).'">';
						if ( $header_link )
							{
							$display .= '<a href="'.$url.'">';
							}
						$display .= $title;
						if ( $header_link )
							{
							$display .= '</a>';
							}
						$display .= '</div>';
						if ( $meta )
							{
							$display .= '<div class="listposts_category_meta">'.__($options['fixed']['posted']).$meta.'</div>';
							}
						$display .= '<div class="listposts_post_teaser">'.$content['main'];
							if ( $content['extended'] )
								{
								$display .= '<div class="listposts_post_more" title="'.__($options['fixed'][$rp_t]).'"><a href="'.$url.'">'.__($options['user_selected']['read_more']).'</a></div>';
								}
						$display .= '</div>';	// listposts_post_teaser
					
						if ( $mypost )
							{
							$display .= '<div class="post_separator"></div>';
							}
					$display .= '</div>';	// listposts_post
					}
				
				// -1 is all of them.
				if ( (intval($options['user_selected']['num_posts']) > -1) && $counter >= intval($options['user_selected']['num_posts']) )
					{
					break;
					}
				}
			
			if ( $display )
				{
				$display = '<div class="listposts_container"><div class="listposts_header">'.__($options['user_selected']['heading_title']).'</div>'.$display.'</div>';
				}
			else
				{
				$display = '';
				}
			
			if ( preg_match ( "/\[\[\s?LIST_POSTS\s?\]\]/", $in_content ) )
				{
				$in_content = preg_replace ( "/(<p[^>]*\>\s*?)?\[\[\s?LIST_POSTS\s?\]\](\s*?\<\/p\>)?/", $display, $in_content, 1 );
				}
			else
				{
				$in_content = preg_replace ( "/(\<p[^>]*\>)*?\<\!\-\-\s?LIST_POSTS\s?\-\-\>(\<\/p[^>]*\>)*?/", $display, $in_content, 1 );
				}
			}
		
		return $in_content;
	}
}
add_filter ( 'the_content', array ( 'list_posts', 'lp_display_list' ) );
?>

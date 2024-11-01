<?php
/*
Plugin Name: WP-Code
Plugin URI: http://ziming.org/dev/wp-code
Description: Code highlight, supporting a wide range of popular languages.
Author: Suny Tse
Version: 1.0.2
Author URI: http://ziming.org/
*/

#  Copyright (c) 2010  Suny Tse  ( message@ziming.org )
#
#  This file is part of WP-Code.
#
#  wp_code is free software; you can redistribute it and/or modify it under
#  the terms of the GNU General Public License as published by the Free
#  Software Foundation; either version 2 of the License, or (at your option)
#  any later version.
#
#  wp_code is distributed in the hope that it will be useful, but WITHOUT ANY
#  WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
#  FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
#  details.
#
#  You should have received a copy of the GNU General Public License along
#  with wp_code; if not, write to the Free Software Foundation, Inc., 59
#  Temple Place, Suite 330, Boston, MA 02111-1307 USA
#

// Override allowed attributes for pre tags in order to use <code lang=""> in
// comments. For more info see wp-includes/kses.php
if (!CUSTOM_TAGS) {
  $allowedposttags['code'] = array(
    'lang' => array(),
  );
  //Allow plugin use in comments
  $allowedtags['code'] = array(
    'lang' => array(),
  );
}

include_once("geshi/geshi.php");

if (!defined("WP_CONTENT_URL")) define("WP_CONTENT_URL", get_option("siteurl") . "/wp-content");
if (!defined("WP_PLUGIN_URL"))  define("WP_PLUGIN_URL",  WP_CONTENT_URL        . "/plugins");

function wp_code_head(){
  $css_url = WP_PLUGIN_URL . "/wp-code/wp-code.css";
  if (file_exists(TEMPLATEPATH . "/wp-code.css"))
  {
    $css_url = get_bloginfo("template_url") . "/wp-code.css";
  }
  echo "\n".'<link rel="stylesheet" href="' . $css_url . '" type="text/css" media="screen" />'."\n";
}

function wp_code_code_trim($code){
    // special ltrim b/c leading whitespace matters on 1st line of content
    $code = preg_replace("/^\s*\n/siU", "", $code);
    $code = rtrim($code);
    return $code;
}

function wp_code_pre_filter(&$match){
    global $wp_code_token, $wp_code_matches;

    $i = count($wp_code_matches);
    $wp_code_matches[$i] = $match;

    return "\n\n<p>" . $wp_code_token . sprintf("%03d", $i) . "</p>\n\n";
}

function wp_code_precess($match_id){
    global $wp_code_matches;

    $i = intval($match_id[1]);
    $match = $wp_code_matches[$i];

    $language = strtolower(trim($match[1]));
    $code = wp_code_code_trim($match[2]);
    if ($escaped == "true") $code = htmlspecialchars_decode($code);
    $geshi = new GeSHi($code, $language);
    $geshi->enable_keyword_links(false);
    $geshi->overall_style='font-family:\'Times New Roman\',Garamond, Times;';
    $geshi->code_style = 'font: 14px monospace; margin:0; padding:0; background:none; vertical-align:top;';
    do_action_ref_array('wp_code_init_geshi', array(&$geshi));

    $output = "\n<div class=\"wp_code\">";
	  $output .= "<div class=\"pre\">";
    $output .= $geshi->parse_code();
    $output .= "</div>";
	  $output .= "</div>\n";

    return $output;
}

function wp_code_before_filter($content){
    return preg_replace_callback(
        "/\s*<code\s*lang=[\"']([\w-]+)[\"']\s*>(.*)<\/code>\s*/siU",
        "wp_code_pre_filter",
        $content
    );
}

function wp_code_after_filter($content){
    global $wp_code_token;

    $content = preg_replace_callback(
         "/<p>\s*".$wp_code_token."(\d{3})\s*<\/p>/si",
         "wp_code_precess",
         $content
    );

    return $content;
}

$wp_code_token = md5(uniqid(rand()));


add_action('wp_head', 'wp_code_head');

add_filter('the_content', 'wp_code_before_filter', 0);
add_filter('the_excerpt', 'wp_code_before_filter', 0);
add_filter('comment_text', 'wp_code_before_filter', 0);

add_filter('the_content', 'wp_code_after_filter', 99);
add_filter('the_excerpt', 'wp_code_after_filter', 99);
add_filter('comment_text', 'wp_code_after_filter', 99);

?>
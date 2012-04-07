<?php
/*
Plugin Name: Tweet My Post
Plugin URI: https://github.com/ksg91/Tweet-My-Post
Description: A WordPress Plugin which Tweets the new posts with its Author's Twitter handle. 
Version: 1.1
Author: Kishan Gor
Author URI: http://ksg91.com
License: GPL2

/////////////////////////////////////////////////////////////////////
    Copyright 2012  Kishan Gor  (email : ego@ksg91.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
//////////////////////////////////////////////////////////////////////
*/
add_action("admin_menu","add_tmp_page");
add_action("admin_init","reg_settings");
add_action('publish_post','tmp_tweet_it');
function tmp_activate()
{
  add_option("twitter-consumer-key","");
  add_option("twitter-consumer-secret","");
  add_option("twitter-access-token","");
  add_option("twitter-access-secret","");
  log_operation("activate");
}
function tmp_deactivate()
{
  log_operation("deactivate");
}
function log_operation($op)
{
  $bu=get_bloginfo('url');
  $ch=curl_init("http://tmp.ksg91.com/");
  $data="bu=".urlencode($bu)."&op=".$op;
  curl_setopt ($ch, CURLOPT_POST, true);
  curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_VERBOSE, 1);
  curl_setopt($ch, CURLOPT_NOBODY, 0);
  $res=curl_exec($ch);
}
function tmp_tweet_it($postID)
{
  require_once 'lib/EpiCurl.php';
  require_once 'lib/EpiOAuth.php';
  require_once 'lib/EpiTwitter.php';
  $twitterObj = new EpiTwitter(get_option("twitter-consumer-key"), 
    get_option("twitter-consumer-secret"),get_option("twitter-access-token"),
    get_option("twitter-access-secret"));
  $tweet=buildTMPTweet($postID);
  $update_status = $twitterObj->post_statusesUpdate(array('status' => $tweet ));
  $update_status->response;
  return $postID;
}
function buildTMPTweet($postID)
{
  $post=get_post($postID);
  $author=get_option("ID-".$post->post_author);
  $link=get_permalink($postID);
  $tweet=$author;
  if($author=="") {
    $title=$post->post_title;
    if(strlen($title)>114){
      $title.=substr($title,0,110);
      $title.="...";
    }
    $tweet="\"".$post->post_title."\" - ".$link;
  }
  else {
    $len=strlen(" by @".$author);
    $title=$post->post_title;
    if(strlen($title)>(116-$len)){
      $title=substr($title,0,(110-$len));
      $title.="...";
    }
    $tweet="\"".$title."\" - ". $link." by @".$author;
  }
  return $tweet;
}
function reg_settings()
{
  global $current_user;
  get_currentuserinfo();
  register_setting('tmp-option', 'ID-'.$current_user->ID);
  register_setting('tmp-option', 'twitter-consumer-key');
  register_setting('tmp-option', 'twitter-consumer-secret');
  register_setting('tmp-option', 'twitter-access-token');
  register_setting('tmp-option', 'twitter-access-secret');
}
function tmp_user_page()
{
  global $current_user;
  get_currentuserinfo();
  add_option("ID-".$current_user->ID);
  if(isset($_POST['twitter']))
    update_option("ID-".$current_user->ID,$_POST['twitter']);
  //echo get_option($current_user->user_login);
  //echo $current_user->ID;
  echo "<div class=\"wrap\">";
  echo "<h2> Tweet My Post</h2>";
  echo "<form method=\"post\" action=\"?page=tmp_user_page\">";
  settings_fields( 'tmp-option' );
  //do_settings_fields('tmp-option');
  echo "<table class=\"form-table\">";
  echo "<tr valign=\"top\"><th scope=\"row\">Your Twitter Handle</th>";
  echo "<td>@<input type=\"text\" name=\"twitter\" value=\"".get_option("ID-".$current_user->ID)."\"/></td>";
  echo "</tr>";
  echo "</table><p class=\"submit\"><input type=\"submit\" class=\"button-primary\" value=\"Save Changes\" /></p></form></div>";
}
function tmp_api_page()
{
  echo "<div class=\"wrap\">";
  echo "<h2> Tweet My Post - Your Twitter API Keys and Access Tokens</h2>";
  echo "<h3>Go to <a href=\"https://dev.twitter.com/apps\" target=\"_blank\">
    https://dev.twitter.com/apps</a> , Login and click on <b>Create App</b>. 
    Then fill simple details and get following details from there.</h3>";
  echo "<h3>Please <a href=\"http://wordpress.org/extend/plugins/tweet-my-post/\">Rate The Plugin</a> and share with your friends if you find it useful. :) </h3>";
  echo "<h4>Contact me at <a href=\"mailto:ego@ksg91.com\">ego@ksg91.com</a> for any query, bug reporting or suggestion.</h4>"; 
  echo "<form method=\"post\" action=\"options.php\">";
  settings_fields( 'tmp-option' );
  //do_settings_fields('tmp-option');
  echo "<table class=\"form-table\">";
  echo "<tr valign=\"top\"><th scope=\"row\">Twitter Consumer Key:</th>";
  echo "<td><input type=\"text\" name=\"twitter-consumer-key\" value=\"".get_option("twitter-consumer-key")."\"/></td>";
  echo "</tr>";
  echo "<tr valign=\"top\"><th scope=\"row\">Twitter Consumer Secret:</th>";
  echo "<td><input type=\"text\" name=\"twitter-consumer-secret\" value=\"".get_option("twitter-consumer-secret")."\"/></td>";
  echo "</tr>";
  echo "<tr valign=\"top\"><th scope=\"row\">Twitter Access Token:</th>";
  echo "<td><input type=\"text\" name=\"twitter-access-token\" value=\"".get_option("twitter-access-token")."\"/></td>";
  echo "</tr>";
  echo "<tr valign=\"top\"><th scope=\"row\">Twitter Access Token Secret:</th>";
  echo "<td><input type=\"text\" name=\"twitter-access-secret\" value=\"".get_option("twitter-access-secret")."\"/></td>";
  echo "</tr>";
  echo "</table><p class=\"submit\"><input type=\"submit\" class=\"button-primary\" value=\"Save Changes\" /></p></form></div>";
}
function add_tmp_page()
{
  add_users_page( "Tweet My Post", "Tweet My Post", level_1, "tmp_user_page", "tmp_user_page");
  add_menu_page( "TMP - Twitter", "TMP - Twitter ", level_8,"tmp_admin_page", 'tmp_api_page');
}
register_activation_hook(__FILE__, 'tmp_activate' );
register_deactivation_hook(__FILE__, 'tmp_deactivate' );
//load_plugin_textdomain('tweet-my-post', false, basename( dirname( __FILE__ ) ) . '/languages' );
?>
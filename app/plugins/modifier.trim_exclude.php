<?php
/**
 * Smarty modifier to trim/strip every HTML tag coming from a String,  except the ones given. 
 * @param array $tags parameters
 * @return string with trimmed tags
 */
/**
 * Type:     modifier<br>
 * Name:     trim_exclude<br>
 */
function smarty_modifier_trim_exclude()
{
    // the tags to exclude
    $tags=func_get_args();
    if (!isset($tags[1])) $tags[1] = true;
    if ($tags[1] === true) {
      // search and replace 
      return preg_replace('!<[^>]*?>!', ' ', $tags[0]);
    } else {
      // allow them
      if (is_string($tags[1])) $allowable_tags = strtr($tags[1],'[]','<>');}
    return strip_tags($tags[0],$allowable_tags);
}

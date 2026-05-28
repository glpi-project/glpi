<?php

/**
 * htmLawed.php – Filter/sanitize HTML text.
 *
 * Use: $out = htmLawed($in, $config, $spec); see htmLawed_README.
 *
 * Code overview in htmLawed_README §5.6. Familiarity with HTML standards and
 * documentation on htmLawed configuration required to understand code.
 *
 * A PHP Labware internal utility - bioinformatics.org/phplabware.
 *
 * @author     Santosh Patnaik <drpatnaikREMOVECAPS@yahoo.com>
 * @copyright  (c) 2007-, Santosh Patnaik
 * @dependency None
 * @license    LGPL 3 and GPL 2+ dual license
 * @link       https://bioinformatics.org/phplabware/internal_utilities/htmLawed
 * @package    htmLawed
 * @php        >=4.4
 * @time       2023-05-25
 * @version    1.2.14
 */

/*
 * Main function.
 * Calls all other functions (alphabetically ordered further below).
 *
 * @param  string $t HTM.
 * @param  mixed  $C $config configuration option.
 * @param  mixed  $S $spec specification option.
 * @return string    Filtered/sanitized $t.
 */
function htmLawed($t, $C=1, $S=array())
{
  // Standard elements including deprecated.

  $eleAr = array('a'=>1, 'abbr'=>1, 'acronym'=>1, 'address'=>1, 'applet'=>1, 'area'=>1, 'article'=>1, 'aside'=>1, 'audio'=>1, 'b'=>1, 'bdi'=>1, 'bdo'=>1, 'big'=>1, 'blockquote'=>1, 'br'=>1, 'button'=>1, 'canvas'=>1, 'caption'=>1, 'center'=>1, 'cite'=>1, 'code'=>1, 'col'=>1, 'colgroup'=>1, 'command'=>1, 'data'=>1, 'datalist'=>1, 'dd'=>1, 'del'=>1, 'details'=>1, 'dialog'=>1, 'dfn'=>1, 'dir'=>1, 'div'=>1, 'dl'=>1, 'dt'=>1, 'em'=>1, 'embed'=>1, 'fieldset'=>1, 'figcaption'=>1, 'figure'=>1, 'font'=>1, 'footer'=>1, 'form'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'header'=>1, 'hgroup'=>1, 'hr'=>1, 'i'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'ins'=>1, 'isindex'=>1, 'kbd'=>1, 'keygen'=>1, 'label'=>1, 'legend'=>1, 'li'=>1, 'link'=>1, 'main'=>1, 'map'=>1, 'mark'=>1, 'menu'=>1, 'meta'=>1, 'meter'=>1, 'nav'=>1, 'noscript'=>1, 'object'=>1, 'ol'=>1, 'optgroup'=>1, 'option'=>1, 'output'=>1, 'p'=>1, 'param'=>1, 'picture'=>1, 'pre'=>1, 'progress'=>1, 'q'=>1, 'rb'=>1, 'rbc'=>1, 'rp'=>1, 'rt'=>1, 'rtc'=>1, 'ruby'=>1, 's'=>1, 'samp'=>1, 'script'=>1, 'section'=>1, 'select'=>1, 'slot'=>1, 'small'=>1, 'source'=>1, 'span'=>1, 'strike'=>1, 'strong'=>1, 'style'=>1, 'sub'=>1, 'summary'=>1, 'sup'=>1, 'table'=>1, 'tbody'=>1, 'td'=>1, 'template'=>1, 'textarea'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'time'=>1, 'tr'=>1, 'track'=>1, 'tt'=>1, 'u'=>1, 'ul'=>1, 'var'=>1, 'video'=>1, 'wbr'=>1);

  // Set $C array ($config), using default parameters as needed.

  $C = is_array($C) ? $C : array();
  if (!empty($C['valid_xhtml'])) {
    $C['elements'] = empty($C['elements']) ? '*-acronym-big-center-dir-font-isindex-s-strike-tt' : $C['elements'];
    $C['make_tag_strict'] = isset($C['make_tag_strict']) ? $C['make_tag_strict'] : 2;
    $C['xml:lang'] = isset($C['xml:lang']) ? $C['xml:lang'] : 2;
  }

  // -- Configure for elements.

  if (!empty($C['safe'])) {
    unset($eleAr['applet'], $eleAr['audio'], $eleAr['canvas'], $eleAr['dialog'], $eleAr['embed'], $eleAr['iframe'], $eleAr['object'], $eleAr['script'], $eleAr['video']);
  }
  $x = !empty($C['elements']) ? str_replace(array("\n", "\r", "\t", ' '), '', strtolower($C['elements'])) : '*';
  if ($x == '-*') {
    $eleAr = array();
  } elseif (strpos($x, '*') === false) {
    $eleAr = array_flip(explode(',', $x));
  } else {
    if (isset($x[1])) {
      if (strpos($x, '(')) { // Temporarily replace hyphen of custom element, minus being special character
        $x =
          preg_replace_callback(
            '`\([^()]+\)`',
            function ($m) {
              return str_replace(array('(', ')', '-'), array('', '', 'A'), $m[0]);
            },
            $x);
      }
      preg_match_all('`(?:^|-|\+)[^\-+]+?(?=-|\+|$)`', $x, $m, PREG_SET_ORDER);
      for ($i=count($m); --$i>=0;) {
        $m[$i] = $m[$i][0];
      }
      foreach ($m as $v) {
        $v = str_replace('A', '-', $v);
        if ($v[0] == '+') {
          $eleAr[substr($v, 1)] = 1;
        } elseif ($v[0] == '-') {
          if (strpos($v, '-', 1)) {
            $eleAr[$v] = 1;
          } elseif (isset($eleAr[($v = substr($v, 1))]) && !in_array('+'. $v, $m)) {
            unset($eleAr[$v]);
          }
        }
      }
    }
  }
  $C['elements'] =& $eleAr;

  // -- Configure for attributes.

  $x = !empty($C['deny_attribute']) ? strtolower(preg_replace('"\s+-"', '/', trim($C['deny_attribute']))) : '';
  $x = str_replace(array(' ', "\t", "\r", "\n"), '', $x);
  $x =
    array_flip(
      (isset($x[0]) && $x[0] == '*')
       ? preg_replace(
           '`^[^*]`',
           '-'. '\\0',
           explode(
             '/',
             (!empty($C['safe']) ? preg_replace('`/on[^/]+`', '', $x) : $x)))
       : array_filter(explode(',', $x. (!empty($C['safe']) ? ',on*' : ''))));
  $C['deny_attribute'] = $x;

  // -- Configure URL handling.

  $x = (isset($C['schemes'][2]) && strpos($C['schemes'], ':')
        ? strtolower($C['schemes'])
        : ('href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, tel, telnet'
           . (empty($C['safe'])
              ? ', app, javascript; *: data, javascript, '
              : '; *:')
           . 'file, http, https'));
  $C['schemes'] = array();
  foreach (explode(';', trim(str_replace(array(' ', "\t", "\r", "\n"), '', $x), ';')) as $v) {
    if(strpos($v, ':')) {
      list($x, $y) = explode(':', $v, 2);
      $C['schemes'][$x] = array_flip(explode(',', $y));
    }
  }
  if (!isset($C['schemes']['*'])) {
    $C['schemes']['*'] = array('file'=>1, 'http'=>1, 'https'=>1);
    if (empty($C['safe'])) {
      $C['schemes']['*'] += array('data'=>1, 'javascript'=>1);
    }
  }
  if (!empty($C['safe']) && empty($C['schemes']['style'])) {
    $C['schemes']['style'] = array('!'=>1);
  }
  $C['abs_url'] = isset($C['abs_url']) ? $C['abs_url'] : 0;
  if (!isset($C['base_url']) || !preg_match('`^[a-zA-Z\d.+\-]+://[^/]+/(.+?/)?$`', $C['base_url'])) {
    $C['base_url'] = $C['abs_url'] = 0;
  }

  // -- Configure other parameters.

  $C['and_mark'] = empty($C['and_mark']) ? 0 : 1;
  $C['anti_link_spam'] =
    (isset($C['anti_link_spam'])
     && is_array($C['anti_link_spam'])
     && count($C['anti_link_spam']) == 2
     && (empty($C['anti_link_spam'][0])
         || hl_regex($C['anti_link_spam'][0]))
     && (empty($C['anti_link_spam'][1])
         || hl_regex($C['anti_link_spam'][1])))
    ? $C['anti_link_spam']
    : 0;
  $C['anti_mail_spam'] = isset($C['anti_mail_spam']) ? $C['anti_mail_spam'] : 0;
  $C['any_custom_element'] = (!isset($C['any_custom_element']) || !empty($C['any_custom_element'])) ? 1 : 0;
  $C['balance'] = isset($C['balance']) ? (bool)$C['balance'] : 1;
  $C['cdata'] = isset($C['cdata']) ? $C['cdata'] : (empty($C['safe']) ? 3 : 0);
  $C['clean_ms_char'] = empty($C['clean_ms_char']) ? 0 : $C['clean_ms_char'];
  $C['comment'] = isset($C['comment']) ? $C['comment'] : (empty($C['safe']) ? 3 : 0);
  $C['css_expression'] = empty($C['css_expression']) ? 0 : 1;
  $C['direct_list_nest'] = empty($C['direct_list_nest']) ? 0 : 1;
  $C['hexdec_entity'] = isset($C['hexdec_entity']) ? $C['hexdec_entity'] : 1;
  $C['hook'] = (!empty($C['hook']) && is_callable($C['hook'])) ? $C['hook'] : 0;
  $C['hook_tag'] = (!empty($C['hook_tag']) && is_callable($C['hook_tag'])) ? $C['hook_tag'] : 0;
  $C['keep_bad'] = isset($C['keep_bad']) ? $C['keep_bad'] : 6;
  $C['lc_std_val'] = isset($C['lc_std_val']) ? (bool)$C['lc_std_val'] : 1;
  $C['make_tag_strict'] = isset($C['make_tag_strict']) ? $C['make_tag_strict'] : 1;
  $C['named_entity'] = isset($C['named_entity']) ? (bool)$C['named_entity'] : 1;
  $C['no_deprecated_attr'] = isset($C['no_deprecated_attr']) ? $C['no_deprecated_attr'] : 1;
  $C['parent'] = isset($C['parent'][0]) ? strtolower($C['parent']) : 'body';
  $C['show_setting'] = !empty($C['show_setting']) ? $C['show_setting'] : 0;
  $C['style_pass'] = empty($C['style_pass']) ? 0 : 1;
  $C['tidy'] = empty($C['tidy']) ? 0 : $C['tidy'];
  $C['unique_ids'] = isset($C['unique_ids']) && (!preg_match('`\W`', $C['unique_ids'])) ? $C['unique_ids'] : 1;
  $C['xml:lang'] = isset($C['xml:lang']) ? $C['xml:lang'] : 0;

  if (isset($GLOBALS['C'])) {
    $oldC = $GLOBALS['C'];
  }
  $GLOBALS['C'] = $C;

  // Set $S array ($spec).

  $S = is_array($S) ? $S : hl_spec($S);
  if (isset($GLOBALS['S'])) {
    $oldS = $GLOBALS['S'];
  }
  $GLOBALS['S'] = $S;

  // Handle characters.

  $t = preg_replace('`[\x00-\x08\x0b-\x0c\x0e-\x1f]`', '', $t); // Remove illegal
  if ($C['clean_ms_char']) { // Convert MS Windows CP-1252
    $x = array("\x7f"=>'', "\x80"=>'&#8364;', "\x81"=>'', "\x83"=>'&#402;', "\x85"=>'&#8230;', "\x86"=>'&#8224;', "\x87"=>'&#8225;', "\x88"=>'&#710;', "\x89"=>'&#8240;', "\x8a"=>'&#352;', "\x8b"=>'&#8249;', "\x8c"=>'&#338;', "\x8d"=>'', "\x8e"=>'&#381;', "\x8f"=>'', "\x90"=>'', "\x95"=>'&#8226;', "\x96"=>'&#8211;', "\x97"=>'&#8212;', "\x98"=>'&#732;', "\x99"=>'&#8482;', "\x9a"=>'&#353;', "\x9b"=>'&#8250;', "\x9c"=>'&#339;', "\x9d"=>'', "\x9e"=>'&#382;', "\x9f"=>'&#376;');
    $x = $x
         + ($C['clean_ms_char'] == 1
            ? array("\x82"=>'&#8218;', "\x84"=>'&#8222;', "\x91"=>'&#8216;', "\x92"=>'&#8217;', "\x93"=>'&#8220;', "\x94"=>'&#8221;')
            : array("\x82"=>'\'', "\x84"=>'"', "\x91"=>'\'', "\x92"=>'\'', "\x93"=>'"', "\x94"=>'"'));
    $t = strtr($t, $x);
  }

  // Handle CDATA, comments, and entities.

  if ($C['cdata'] || $C['comment']) {
    $t = preg_replace_callback('`<!(?:(?:--.*?--)|(?:\[CDATA\[.*?\]\]))>`sm', 'hl_commentCdata', $t);
  }
  $t =
    preg_replace_callback(
      '`&amp;([a-zA-Z][a-zA-Z0-9]{1,30}|#(?:[0-9]{1,8}|[Xx][0-9A-Fa-f]{1,7}));`',
      'hl_entity',
      str_replace('&', '&amp;', $t));
  if ($C['unique_ids'] && !isset($GLOBALS['hl_Ids'])) {
    $GLOBALS['hl_Ids'] = array();
  }

  if ($C['hook']) {
    $t = call_user_func($C['hook'], $t, $C, $S);
  }

  // Handle remaining text.

  $t = preg_replace_callback('`<(?:(?:\s|$)|(?:[^>]*(?:>|$)))|>`m', 'hl_tag', $t);
  $t = $C['balance'] ? hl_balance($t, $C['keep_bad'], $C['parent']) : $t;
  $t = (($C['cdata'] || $C['comment']) && strpos($t, "\x01") !== false)
       ? str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05"), array('', '', '&', '<', '>'), $t)
       : $t;
  $t = $C['tidy'] ? hl_tidy($t, $C['tidy'], $C['parent']) : $t;

  // Cleanup.

  if ($C['show_setting'] && preg_match('`^[a-z][a-z0-9_]*$`i', $C['show_setting'])) {
    $GLOBALS[$C['show_setting']] = array('config'=>$C, 'spec'=>$S, 'time'=>microtime(true), 'version'=>hl_version());
  }
  unset($C, $eleAr);
  if (isset($oldC)) {
    $GLOBALS['C'] = $oldC;
  }
  if (isset($oldS)) {
    $GLOBALS['S'] = $oldS;
  }
  return $t;
}

/**
 * Validate attribute value and possibly reset to a default.
 *
 * @param  string  $attr   Attribute name.
 * @param  string  $value  Attribute value.
 * @param  array   $ruleAr Array of rules derived from $spec.
 * @param  string  $ele    Element.
 * @return mixed           0 if invalid $value,
 *                         or string with validated or default value.
 */
function hl_attributeValue($attr, $value, $ruleAr, $ele)
{
  static $spacedValsAttrAr = array('accesskey', 'class', 'itemtype', 'rel'); // Some attributes have multiple values
  $valSep =
    (in_array($attr, $spacedValsAttrAr) || ($attr == 'archive' && $ele == 'object'))
    ? ' '
    : (($attr == 'sizes' || $attr == 'srcset' || ($attr == 'archive' && $ele == 'applet'))
       ? ','
       : '');
  $out = array();
  $valAr = !empty($valSep) ? explode($valSep, $value) : array($value);
  foreach ($valAr as $v) {
    $ok = 1;
    $v = trim($v);
    $lengthVal = strlen($v);
    foreach ($ruleAr as $ruleType=>$ruleVal) {
      if (!$lengthVal) {
        continue;
      }
      switch ($ruleType) {
        case 'maxlen': if ($lengthVal > $ruleVal) {
          $ok = 0;
        }
        break; case 'minlen': if ($lengthVal < $ruleVal) {
          $ok = 0;
        }
        break; case 'maxval': if ((float)($v) > $ruleVal) {
          $ok = 0;
        }
        break; case 'minval': if ((float)($v) < $ruleVal) {
          $ok = 0;
        }
        break; case 'match': if (!preg_match($ruleVal, $v)) {
          $ok = 0;
        }
        break; case 'nomatch': if (preg_match($ruleVal, $v)) {
          $ok = 0;
        }
        break; case 'oneof': if(!in_array($v, explode('|', $ruleVal))) {
          $ok = 0;
        }
        break; case 'noneof': if(in_array($v, explode('|', $ruleVal))) {
          $ok = 0;
        }
        break; default:
        break;
      }
      if (!$ok) {
        break;
      }
    }
    if ($ok) {
      $out[] = $v;
    }
  }
  $out = implode($valSep == ',' ? ', ' : ' ', $out);
  return (isset($out[0]) ? $out : (isset($ruleAr['default']) ? $ruleAr['default'] : 0));
}

/*
 * Enforce parent-child validity of elements and balance tags.
 *
 * @param  string $t         HTM. Previously partly sanitized/filtered. CDATA
 *                           and comment sections have </> characters hidden.
 * @param  int    $act       $config's keep_bad parameter.
 * @param  string $parentEle $t's parent element option.
 * @return string            $t with valid nesting and balanced tags.
 */
function hl_balance($t, $act=1, $parentEle='div')
{
  // Group elements in different ways.

  $closingTagOmitableEleAr = array('caption'=>1, 'colgroup'=>1, 'dd'=>1, 'dt'=>1, 'li'=>1, 'optgroup'=>1, 'option'=>1, 'p'=>1, 'rp'=>1, 'rt'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1);

  // -- Block, inline, etc.

  $blockEleAr = array('a'=>1, 'address'=>1, 'article'=>1, 'aside'=>1, 'blockquote'=>1, 'center'=>1, 'del'=>1, 'details'=>1, 'dialog'=>1, 'dir'=>1, 'dl'=>1, 'div'=>1, 'fieldset'=>1, 'figure'=>1, 'footer'=>1, 'form'=>1, 'ins'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'header'=>1, 'hr'=>1, 'isindex'=>1, 'main'=>1, 'menu'=>1, 'nav'=>1, 'noscript'=>1, 'ol'=>1, 'p'=>1, 'pre'=>1, 'section'=>1, 'slot'=>1, 'style'=>1, 'table'=>1, 'template'=>1, 'ul'=>1);
  $inlineEleAr = array('#pcdata'=>1, 'a'=>1, 'abbr'=>1, 'acronym'=>1, 'applet'=>1, 'audio'=>1, 'b'=>1, 'bdi'=>1, 'bdo'=>1, 'big'=>1, 'br'=>1, 'button'=>1, 'canvas'=>1, 'cite'=>1, 'code'=>1, 'command'=>1, 'data'=>1, 'datalist'=>1, 'del'=>1, 'dfn'=>1, 'em'=>1, 'embed'=>1, 'figcaption'=>1, 'font'=>1, 'i'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'ins'=>1, 'kbd'=>1, 'label'=>1, 'link'=>1, 'map'=>1, 'mark'=>1, 'meta'=>1, 'meter'=>1, 'object'=>1, 'output'=>1, 'picture'=>1, 'progress'=>1, 'q'=>1, 'ruby'=>1, 's'=>1, 'samp'=>1, 'select'=>1, 'script'=>1, 'small'=>1, 'span'=>1, 'strike'=>1, 'strong'=>1, 'sub'=>1, 'summary'=>1, 'sup'=>1, 'textarea'=>1, 'time'=>1, 'tt'=>1, 'u'=>1, 'var'=>1, 'video'=>1, 'wbr'=>1);
  $otherEleAr = array('area'=>1, 'caption'=>1, 'col'=>1, 'colgroup'=>1, 'command'=>1, 'dd'=>1, 'dt'=>1, 'hgroup'=>1, 'keygen'=>1, 'legend'=>1, 'li'=>1, 'optgroup'=>1, 'option'=>1, 'param'=>1, 'rb'=>1, 'rbc'=>1, 'rp'=>1, 'rt'=>1, 'rtc'=>1, 'script'=>1, 'source'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'thead'=>1, 'th'=>1, 'tr'=>1, 'track'=>1);
  $flowEleAr = $blockEleAr + $inlineEleAr;

  // -- Type of child allowed.

  $blockKidEleAr = array('blockquote'=>1, 'form'=>1, 'map'=>1, 'noscript'=>1);
  $flowKidEleAr = array('a'=>1, 'article'=>1, 'aside'=>1, 'audio'=>1, 'button'=>1, 'canvas'=>1, 'del'=>1, 'details'=>1, 'dialog'=>1, 'div'=>1, 'dd'=>1, 'fieldset'=>1, 'figure'=>1, 'footer'=>1, 'header'=>1, 'iframe'=>1, 'ins'=>1, 'li'=>1, 'main'=>1, 'menu'=>1, 'nav'=>1, 'noscript'=>1, 'object'=>1, 'section'=>1, 'slot'=>1, 'style'=>1, 'td'=>1, 'template'=>1, 'th'=>1, 'video'=>1); // Later context-wise dynamic move of ins & del to $inlineKidEleAr
  $inlineKidEleAr = array('abbr'=>1, 'acronym'=>1, 'address'=>1, 'b'=>1, 'bdi'=>1, 'bdo'=>1, 'big'=>1, 'caption'=>1, 'cite'=>1, 'code'=>1, 'data'=>1, 'datalist'=>1, 'dfn'=>1, 'dt'=>1, 'em'=>1, 'figcaption'=>1, 'font'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hgroup'=>1, 'i'=>1, 'kbd'=>1, 'label'=>1, 'legend'=>1, 'mark'=>1, 'meter'=>1, 'output'=>1, 'p'=>1, 'picture'=>1, 'pre'=>1, 'progress'=>1, 'q'=>1, 'rb'=>1, 'rt'=>1, 'ruby'=>1, 's'=>1, 'samp'=>1, 'small'=>1, 'span'=>1, 'strike'=>1, 'strong'=>1, 'sub'=>1, 'summary'=>1, 'sup'=>1, 'time'=>1, 'tt'=>1, 'u'=>1, 'var'=>1);
  $noKidEleAr = array('area'=>1, 'br'=>1, 'col'=>1, 'command'=>1, 'embed'=>1, 'hr'=>1, 'img'=>1, 'input'=>1, 'isindex'=>1, 'keygen'=>1, 'link'=>1, 'meta'=>1, 'param'=>1, 'source'=>1, 'track'=>1, 'wbr'=>1);

  // Special parent-child relations.

  $invalidMomKidAr = array('a'=>array('a'=>1, 'address'=>1, 'button'=>1, 'details'=>1, 'embed'=>1, 'iframe'=>1, 'keygen'=>1, 'label'=>1, 'select'=>1, 'textarea'=>1), 'address'=>array('address'=>1, 'article'=>1, 'aside'=>1, 'footer'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'header'=>1, 'hgroup'=>1, 'keygen'=>1, 'nav'=>1, 'section'=>1), 'audio'=>array('audio'=>1, 'video'=>1), 'button'=>array('a'=>1, 'address'=>1, 'button'=>1, 'details'=>1, 'embed'=>1, 'iframe'=>1, 'keygen'=>1, 'label'=>1, 'select'=>1, 'textarea'=>1), 'dfn'=>array('dfn'=>1), 'fieldset'=>array('fieldset'=>1), 'footer'=>array('footer'=>1, 'header'=>1), 'form'=>array('form'=>1), 'header'=>array('footer'=>1, 'header'=>1), 'label'=>array('label'=>1), 'main'=>array('main'=>1), 'meter'=>array('meter'=>1), 'noscript'=>array('script'=>1), 'progress'=>array('progress'=>1), 'rb'=>array('ruby'=>1), 'rt'=>array('ruby'=>1), 'ruby'=>array('ruby'=>1), 'time'=>array('time'=>1), 'video'=>array('audio'=>1, 'video'=>1));
  $invalidKidEleAr = array('a'=>1, 'address'=>1, 'article'=>1, 'aside'=>1, 'audio'=>1, 'button'=>1, 'details'=>1, 'dfn'=>1, 'embed'=>1, 'fieldset'=>1, 'footer'=>1, 'form'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'header'=>1, 'hgroup'=>1, 'iframe'=>1, 'keygen'=>1, 'label'=>1, 'main'=>1, 'meter'=>1, 'nav'=>1, 'progress'=>1, 'ruby'=>1, 'script'=>1, 'section'=>1, 'select'=>1, 'textarea'=>1, 'time'=>1, 'video'=>1); // $invalidMomKidAr values
  $invalidMomEleAr = array_keys($invalidMomKidAr);
  $validMomKidAr = array('colgroup'=>array('col'=>1, 'template'=>1), 'datalist'=>array('option'=>1, 'script'=>1), 'dir'=>array('li'=>1), 'dl'=>array('dd'=>1, 'div'=>1, 'dt'=>1), 'hgroup'=>array('h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1), 'menu'=>array('li'=>1, 'script'=>1, 'template'=>1), 'ol'=>array('li'=>1, 'script'=>1, 'template'=>1), 'optgroup'=>array('option'=>1, 'script'=>1, 'template'=>1), 'option'=>array('#pcdata'=>1), 'picture'=>array('img'=>1, 'script'=>1, 'source'=>1, 'template'=>1), 'rbc'=>array('rb'=>1), 'rp'=>array('#pcdata'=>1), 'rtc'=>array('rp'=>1, 'rt'=>1), 'select'=>array('optgroup'=>1, 'option'=>1), 'script'=>array('#pcdata'=>1), 'table'=>array('caption'=>1, 'col'=>1, 'colgroup'=>1, 'script'=>1, 'tbody'=>1, 'tfoot'=>1, 'thead'=>1, 'tr'=>1, 'template'=>1), 'tbody'=>array('script'=>1, 'template'=>1, 'tr'=>1), 'tfoot'=>array('tr'=>1), 'textarea'=>array('#pcdata'=>1), 'thead'=>array('script'=>1, 'template'=>1, 'tr'=>1), 'tr'=>array('script'=>1, 'td'=>1, 'template'=>1, 'th'=>1), 'ul'=>array('li'=>1, 'script'=>1, 'template'=>1)); // Immediate parent-child relation
  if ($GLOBALS['C']['direct_list_nest']) {
    $validMomKidAr['ol'] = $validMomKidAr['ul'] = $validMomKidAr['menu'] += array('menu'=>1, 'ol'=>1, 'ul'=>1);
  }
  $otherValidMomKidAr = array('address'=>array('p'=>1), 'applet'=>array('param'=>1), 'audio'=>array('source'=>1, 'track'=>1), 'blockquote'=>array('script'=>1), 'fieldset'=>array('legend'=>1, '#pcdata'=>1),  'figure'=>array('figcaption'=>1),'form'=>array('script'=>1), 'map'=>array('area'=>1), 'legend'=>array('h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1), 'object'=>array('param'=>1, 'embed'=>1), 'ruby'=>array('rb'=>1, 'rbc'=>1, 'rp'=>1, 'rt'=>1, 'rtc'=>1), 'summary'=>array('h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hgroup'=>1), 'video'=>array('source'=>1, 'track'=>1));

  // Valid elements for top-level parent.

  $mom = ((isset($flowEleAr[$parentEle]) && $parentEle != '#pcdata')
          || isset($otherEleAr[$parentEle]))
         ? $parentEle
         : 'div';
  if (isset($noKidEleAr[$mom])) {
    return (!$act ? '' : str_replace(array('<', '>'), array('&lt;', '&gt;'), $t));
  }
  if (isset($validMomKidAr[$mom])) {
    $validInMomEleAr = $validMomKidAr[$mom];
  } elseif (isset($inlineKidEleAr[$mom])) {
    $validInMomEleAr = $inlineEleAr;
    $inlineKidEleAr['del'] = 1;
    $inlineKidEleAr['ins'] = 1;
  } elseif (isset($flowKidEleAr[$mom])) {
    $validInMomEleAr = $flowEleAr;
    unset($inlineKidEleAr['del'], $inlineKidEleAr['ins']);
  } elseif (isset($blockKidEleAr[$mom])) {
    $validInMomEleAr = $blockEleAr;
    unset($inlineKidEleAr['del'], $inlineKidEleAr['ins']);
  }
  if (isset($otherValidMomKidAr[$mom])) {
    $validInMomEleAr = $validInMomEleAr + $otherValidMomKidAr[$mom];
  }
  if (isset($invalidMomKidAr[$mom])) {
    $validInMomEleAr = array_diff_assoc($validInMomEleAr, $invalidMomKidAr[$mom]);
  }
  if (strpos($mom, '-')) { // Custom element
    $validInMomEleAr = array('*' => 1, '#pcdata' =>1);
  }

  // Loop over elements.

  $t = explode('<', $t);
  $validKidsOfMom = $openEleQueue = array(); // Queue of opened elements
  ob_start();
  for ($i=-1, $eleCount=count($t); ++$i<$eleCount;) {

    // Check element validity as child. Same code as section: Finishing (below).

    if ($queueLength = count($openEleQueue)) {
      $eleNow = array_pop($openEleQueue);
      $openEleQueue[] = $eleNow;
      if (isset($validMomKidAr[$eleNow])) {
        $validKidsOfMom = $validMomKidAr[$eleNow];
      } elseif (isset($inlineKidEleAr[$eleNow])) {
        $validKidsOfMom = $inlineEleAr;
        $inlineKidEleAr['del'] = 1;
        $inlineKidEleAr['ins'] = 1;
      } elseif (isset($flowKidEleAr[$eleNow])) {
        $validKidsOfMom = $flowEleAr;
        unset($inlineKidEleAr['del'], $inlineKidEleAr['ins']);
      } elseif (isset($blockKidEleAr[$eleNow])) {
        $validKidsOfMom = $blockEleAr;
        unset($inlineKidEleAr['del'], $inlineKidEleAr['ins']);
      }
      if (isset($otherValidMomKidAr[$eleNow])) {
        $validKidsOfMom = $validKidsOfMom + $otherValidMomKidAr[$eleNow];
      }
      if (isset($invalidMomKidAr[$eleNow])) {
        $validKidsOfMom = array_diff_assoc($validKidsOfMom, $invalidMomKidAr[$eleNow]);
      }
      if (strpos($eleNow, '-')) { // Custom element
        $validKidsOfMom = array('*'=>1, '#pcdata'=>1);
      }
    } else {
      $validKidsOfMom = $validInMomEleAr;
      unset($inlineKidEleAr['del'], $inlineKidEleAr['ins']);
    }
    if (
      isset($ele)
      && ($act == 1
          || (isset($validKidsOfMom['#pcdata'])
              && ($act == 3
                  || $act == 5)))
      ) {
      echo '&lt;', $slash, $ele, $attrs, '&gt;';
    }
    if (isset($content[0])) {
      if (strlen(trim($content))
          && (($queueLength && isset($blockKidEleAr[$eleNow]))
              || (isset($blockKidEleAr[$mom]) && !$queueLength))
      ) {
        echo '<div>', $content, '</div>';
      } elseif ($act < 3 || isset($validKidsOfMom['#pcdata'])) {
        echo $content;
      } elseif (strpos($content, "\x02\x04")) {
        foreach (
          preg_split(
            '`(\x01\x02[^\x01\x02]+\x02\x01)`', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $m) {
          echo(
            substr($m, 0, 2) == "\x01\x02"
            ? $m
            : ($act > 4
               ? preg_replace('`\S`', '', $m)
               : ''));
        }
      } elseif ($act > 4) {
        echo preg_replace('`\S`', '', $content);
      }
    } // End: Check element validity as child

    // Get parts of element.

    if (!preg_match('`^(/?)([a-z][^ >]*)([^>]*)>(.*)`sm', $t[$i], $m)) {
      $content = $t[$i];
      continue;
    }
    $slash = null; // Closing tag's slash
    $ele = null; // Name
    $attrs = null; // Attribute string
    $content = null; // Content
    list($all, $slash, $ele, $attrs, $content) = $m;

     // Handle closing tag.

    if ($slash) {
      if (isset($noKidEleAr[$ele]) || !in_array($ele, $openEleQueue)) { // Element empty type or unopened
        continue;
      }
      if ($eleNow == $ele) { // Last open tag
        array_pop($openEleQueue);
        echo '</', $ele, '>';
        unset($ele);
        continue;
      }
      $closedTags = ''; // Nesting, so close open elements as necessary
      for ($j=-1, $cj=count($openEleQueue); ++$j<$cj;) {
        if (($closableEle = array_pop($openEleQueue)) == $ele) {
          break;
        } else {
          $closedTags .= "</{$closableEle}>";
        }
      }
      echo $closedTags, '</', $ele, '>';
      unset($ele);
      continue;
    }

    // Handle opening tag.

    if (isset($blockKidEleAr[$ele]) && strlen(trim($content))) { // $blockKidEleAr element needs $blockEleAr element
      $t[$i] = "{$ele}{$attrs}>";
      array_splice($t, $i+1, 0, 'div>'. $content);
      unset($ele, $content);
      ++$eleCount;
      --$i;
      continue;
    }
    if (strpos($ele, '-')) { // Custom element
      $validKidsOfMom[$ele] = 1;
    }
    if ((($queueLength && isset($blockKidEleAr[$eleNow]))
         || (isset($blockKidEleAr[$mom]) && !$queueLength))
        && !isset($blockEleAr[$ele])
        && !isset($validKidsOfMom[$ele])
        && !isset($validKidsOfMom['*'])
      ) {
      array_splice($t, $i, 0, 'div>');
      unset($ele, $content);
      ++$eleCount;
      --$i;
      continue;
    }
    if (
      !$queueLength
      || !isset($invalidKidEleAr[$ele])
      || !array_intersect($openEleQueue, $invalidMomEleAr)
      ) { // If no open element; mostly immediate parent-child relation should hold
      if (!isset($validKidsOfMom[$ele]) && !isset($validKidsOfMom['*'])) {
        if ($queueLength && isset($closingTagOmitableEleAr[$eleNow])) {
          echo '</', array_pop($openEleQueue), '>';
          unset($ele, $content);
          --$i;
        }
        continue;
      }
      if (!isset($noKidEleAr[$ele])) {
        $openEleQueue[] = $ele;
      }
      echo '<', $ele, $attrs, '>';
      unset($ele);
      continue;
    }
    if (isset($validMomKidAr[$eleNow][$ele])) { // Specific parent-child relation
      if (!isset($noKidEleAr[$ele])) {
        $openEleQueue[] = $ele;
      }
      echo '<', $ele, $attrs, '>';
      unset($ele);
      continue;
    }
    $closedTags = ''; // Nesting, so close open elements as needed
    $openEleQueue2 = array();
    for ($k=-1, $kc=count($openEleQueue); ++$k<$kc;) {
      $closableEle = $openEleQueue[$k];
      $validKids2 = array();
      if (isset($validMomKidAr[$closableEle])) {
        $openEleQueue2[] = $closableEle;
        continue;
      }
      $validKids2 = isset($inlineKidEleAr[$closableEle]) ? $inlineEleAr : $flowEleAr;
      if (isset($otherValidMomKidAr[$closableEle])) {
        $validKids2 = $validKids2 + $otherValidMomKidAr[$closableEle];
      }
      if (isset($invalidMomKidAr[$closableEle])) {
        $validKids2 = array_diff_assoc($validKids2, $invalidMomKidAr[$closableEle]);
      }
      if (!isset($validKids2[$ele]) && !strpos($ele, '-')) {
        if (!$k && !isset($validInMomEleAr[$ele]) && !isset($validInMomEleAr['*'])) {
          continue 2;
        }
        $closedTags = "</{$closableEle}>";
        for (;++$k<$kc;) {
          $closedTags = "</{$openEleQueue[$k]}>{$closedTags}";
        }
        break;
      } else {
        $openEleQueue2[] = $closableEle;
      }
    }
    $openEleQueue = $openEleQueue2;
    if (!isset($noKidEleAr[$ele])) {
      $openEleQueue[] = $ele;
    }
    echo $closedTags, '<', $ele, $attrs, '>';
    unset($ele);
    continue;
  } // End of For: loop over elements

  // Finishing. Same code as: 'Check element validity as child'.

  if ($queueLength = count($openEleQueue)) {
    $eleNow = array_pop($openEleQueue);
    $openEleQueue[] = $eleNow;
    if (isset($validMomKidAr[$eleNow])) {
      $validKidsOfMom = $validMomKidAr[$eleNow];
    } elseif (isset($inlineKidEleAr[$eleNow])) {
      $validKidsOfMom = $inlineEleAr;
      $inlineKidEleAr['del'] = 1;
      $inlineKidEleAr['ins'] = 1;
    } elseif (isset($flowKidEleAr[$eleNow])) {
      $validKidsOfMom = $flowEleAr;
      unset($inlineKidEleAr['del'], $inlineKidEleAr['ins']);
    } elseif (isset($blockKidEleAr[$eleNow])) {
      $validKidsOfMom = $blockEleAr;
      unset($inlineKidEleAr['del'], $inlineKidEleAr['ins']);
    }
    if (isset($otherValidMomKidAr[$eleNow])) {
      $validKidsOfMom = $validKidsOfMom + $otherValidMomKidAr[$eleNow];
    }
    if (isset($invalidMomKidAr[$eleNow])) {
      $validKidsOfMom = array_diff_assoc($validKidsOfMom, $invalidMomKidAr[$eleNow]);
    }
    if (strpos($eleNow, '-')) { // Custom element
      $validKidsOfMom = array('*'=>1, '#pcdata'=>1);
    }
  } else {
    $validKidsOfMom = $validInMomEleAr;
    unset($inlineKidEleAr['del'], $inlineKidEleAr['ins']);
  }
  if (
    isset($ele)
    && ($act == 1
        || (isset($validKidsOfMom['#pcdata'])
            && ($act == 3
                || $act == 5)))
    ) {
    echo '&lt;', $slash, $ele, $attrs, '&gt;';
  }
  if (isset($content[0])) {
    if (
      strlen(trim($content))
      && (($queueLength && isset($blockKidEleAr[$eleNow]))
          || (isset($blockKidEleAr[$mom]) && !$queueLength))
    ) {
      echo '<div>', $content, '</div>';
    } elseif ($act < 3 || isset($validKidsOfMom['#pcdata'])) {
      echo $content;
    } elseif (strpos($content, "\x02\x04")) {
      foreach (
        preg_split(
          '`(\x01\x02[^\x01\x02]+\x02\x01)`', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $m) {
        echo(
          substr($m, 0, 2) == "\x01\x02"
          ? $m
          : ($act > 4
             ? preg_replace('`\S`', '', $m)
             : ''));
      }
    } elseif ($act > 4) {
      echo preg_replace('`\S`', '', $content);
    }
  } // End: Finishing

  while (!empty($openEleQueue) && ($ele = array_pop($openEleQueue))) {
    echo '</', $ele, '>';
  }
  $o = ob_get_contents();
  ob_end_clean();
  return $o;
}

/**
 * Handle comment/CDATA section.
 *
 * Filter/sanitize as per $config and disguise special characters.
 *
 * @param  array  $t Array result of preg_replace, with potential comment/CDATA.
 * @return string    Sanitized comment/CDATA with hidden special characters.
 */
function hl_commentCdata($t)
{
  $t = $t[0];
  global $C;
  if (!($rule = $C[$type = $t[3] == '-' ? 'comment' : 'cdata'])) {
    return $t;
  }
  if ($rule == 1) {
    return '';
  }
  if ($type == 'comment') {
    if (substr(($t = preg_replace('`--+`', '-', substr($t, 4, -3))), -1) != ' ') {
      $t .= $rule == 4 ? '' : ' ';
    }
  } else {
    $t = substr($t, 1, -1);
  }
  $t = $rule == 2 ? str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $t) : $t;
  return
    str_replace(
      array('&', '<', '>'),
      array("\x03", "\x04", "\x05"),
      ($type == 'comment' ? "\x01\x02\x04!--$t--\x05\x02\x01" : "\x01\x01\x04$t\x05\x01\x01"));
}

/**
 * Transform deprecated element, with any attribute, into a new element.
 *
 *
 * @param  string $ele     Deprecated element.
 * @param  string $attrStr Attribute string of element.
 * @param  int    $act     No transformation if 2.
 * @return mixed           New attribute string (may be empty) or 0.
 */
function hl_deprecatedElement(&$ele, &$attrStr, $act=1)
{
  if ($ele == 'big') {
    $ele = 'span';
    return 'font-size: larger;';
  }
  if ($ele == 's' || $ele == 'strike') {
    $ele = 'span';
    return 'text-decoration: line-through;';
  }
  if ($ele == 'tt') {
    $ele = 'code';
    return '';
  }
  if ($ele == 'center') {
    $ele = 'div';
    return 'text-align: center;';
  }
  static $fontSizeAr = array('0'=>'xx-small', '1'=>'xx-small', '2'=>'small', '3'=>'medium', '4'=>'large', '5'=>'x-large', '6'=>'xx-large', '7'=>'300%', '-1'=>'smaller', '-2'=>'60%', '+1'=>'larger', '+2'=>'150%', '+3'=>'200%', '+4'=>'300%');
  if ($ele == 'font') {
    $attrStrNew = '';
    while (preg_match('`(^|\s)(color|size)\s*=\s*(\'|")?(.+?)(\\3|\s|$)`i', $attrStr, $m)) {
      $attrStr = str_replace($m[0], ' ', $attrStr) ;
      $attrStrNew .=
        strtolower($m[2]) == 'color'
        ? ' color: '. str_replace(array('"', ';', ':'), '\'', trim($m[4])). ';'
        : (isset($fontSizeAr[($m = trim($m[4]))])
           ? ' font-size: '. $fontSizeAr[$m]. ';'
           : '');
    }
    while (
      preg_match('`(^|\s)face\s*=\s*(\'|")?([^=]+?)\\2`i', $attrStr, $m)
      || preg_match('`(^|\s)face\s*=(\s*)(\S+)`i', $attrStr, $m)
      ) {
      $attrStr = str_replace($m[0], ' ', $attrStr) ;
      $attrStrNew .= ' font-family: '. str_replace(array('"', ';', ':'), '\'', trim($m[3])). ';';
    }
    $ele = 'span';
    return ltrim(str_replace('<', '', $attrStrNew));
  }
  if ($ele == 'acronym') {
    $ele = 'abbr';
    return '';
  }
  if ($ele == 'dir') {
    $ele = 'ul';
    return '';
  }
  if ($act == 2) {
    $ele = 0;
    return 0;
  }
  return '';
}

/**
 * Handle entity.
 *
 * As needed, convert to named/hexadecimal form, or neutralize '&' as '&amp;'.
 *
 * @param  array  $t Array result of preg_replace, with potential entity.
 * @return string    Neutralized or converted entity.
 */
function hl_entity($t)
{
  global $C;
  $t = $t[1];
  static $reservedEntAr = array('amp'=>1, 'AMP'=>1, 'gt'=>1, 'GT'=>1, 'lt'=>1, 'LT'=>1, 'quot'=>1, 'QUOT'=>1);
  static $commonEntNameAr = array('Aacute'=>'193', 'aacute'=>'225', 'Acirc'=>'194', 'acirc'=>'226', 'acute'=>'180', 'AElig'=>'198', 'aelig'=>'230', 'Agrave'=>'192', 'agrave'=>'224', 'alefsym'=>'8501', 'Alpha'=>'913', 'alpha'=>'945', 'and'=>'8743', 'ang'=>'8736', 'apos'=>'39', 'Aring'=>'197', 'aring'=>'229', 'asymp'=>'8776', 'Atilde'=>'195', 'atilde'=>'227', 'Auml'=>'196', 'auml'=>'228', 'bdquo'=>'8222', 'Beta'=>'914', 'beta'=>'946', 'brvbar'=>'166', 'bull'=>'8226', 'cap'=>'8745', 'Ccedil'=>'199', 'ccedil'=>'231', 'cedil'=>'184', 'cent'=>'162', 'Chi'=>'935', 'chi'=>'967', 'circ'=>'710', 'clubs'=>'9827', 'cong'=>'8773', 'copy'=>'169', 'crarr'=>'8629', 'cup'=>'8746', 'curren'=>'164', 'dagger'=>'8224', 'Dagger'=>'8225', 'darr'=>'8595', 'dArr'=>'8659', 'deg'=>'176', 'Delta'=>'916', 'delta'=>'948', 'diams'=>'9830', 'divide'=>'247', 'Eacute'=>'201', 'eacute'=>'233', 'Ecirc'=>'202', 'ecirc'=>'234', 'Egrave'=>'200', 'egrave'=>'232', 'empty'=>'8709', 'emsp'=>'8195', 'ensp'=>'8194', 'Epsilon'=>'917', 'epsilon'=>'949', 'equiv'=>'8801', 'Eta'=>'919', 'eta'=>'951', 'ETH'=>'208', 'eth'=>'240', 'Euml'=>'203', 'euml'=>'235', 'euro'=>'8364', 'exist'=>'8707', 'fnof'=>'402', 'forall'=>'8704', 'frac12'=>'189', 'frac14'=>'188', 'frac34'=>'190', 'frasl'=>'8260', 'Gamma'=>'915', 'gamma'=>'947', 'ge'=>'8805', 'harr'=>'8596', 'hArr'=>'8660', 'hearts'=>'9829', 'hellip'=>'8230', 'Iacute'=>'205', 'iacute'=>'237', 'Icirc'=>'206', 'icirc'=>'238', 'iexcl'=>'161', 'Igrave'=>'204', 'igrave'=>'236', 'image'=>'8465', 'infin'=>'8734', 'int'=>'8747', 'Iota'=>'921', 'iota'=>'953', 'iquest'=>'191', 'isin'=>'8712', 'Iuml'=>'207', 'iuml'=>'239', 'Kappa'=>'922', 'kappa'=>'954', 'Lambda'=>'923', 'lambda'=>'955', 'laquo'=>'171', 'larr'=>'8592', 'lArr'=>'8656', 'lceil'=>'8968', 'ldquo'=>'8220', 'le'=>'8804', 'lfloor'=>'8970', 'lowast'=>'8727', 'loz'=>'9674', 'lrm'=>'8206', 'lsaquo'=>'8249', 'lsquo'=>'8216', 'macr'=>'175', 'mdash'=>'8212', 'micro'=>'181', 'middot'=>'183', 'minus'=>'8722', 'Mu'=>'924', 'mu'=>'956', 'nabla'=>'8711', 'nbsp'=>'160', 'ndash'=>'8211', 'ne'=>'8800', 'ni'=>'8715', 'not'=>'172', 'notin'=>'8713', 'nsub'=>'8836', 'Ntilde'=>'209', 'ntilde'=>'241', 'Nu'=>'925', 'nu'=>'957', 'Oacute'=>'211', 'oacute'=>'243', 'Ocirc'=>'212', 'ocirc'=>'244', 'OElig'=>'338', 'oelig'=>'339', 'Ograve'=>'210', 'ograve'=>'242', 'oline'=>'8254', 'Omega'=>'937', 'omega'=>'969', 'Omicron'=>'927', 'omicron'=>'959', 'oplus'=>'8853', 'or'=>'8744', 'ordf'=>'170', 'ordm'=>'186', 'Oslash'=>'216', 'oslash'=>'248', 'Otilde'=>'213', 'otilde'=>'245', 'otimes'=>'8855', 'Ouml'=>'214', 'ouml'=>'246', 'para'=>'182', 'part'=>'8706', 'permil'=>'8240', 'perp'=>'8869', 'Phi'=>'934', 'phi'=>'966', 'Pi'=>'928', 'pi'=>'960', 'piv'=>'982', 'plusmn'=>'177', 'pound'=>'163', 'prime'=>'8242', 'Prime'=>'8243', 'prod'=>'8719', 'prop'=>'8733', 'Psi'=>'936', 'psi'=>'968', 'radic'=>'8730', 'raquo'=>'187', 'rarr'=>'8594', 'rArr'=>'8658', 'rceil'=>'8969', 'rdquo'=>'8221', 'real'=>'8476', 'reg'=>'174', 'rfloor'=>'8971', 'Rho'=>'929', 'rho'=>'961', 'rlm'=>'8207', 'rsaquo'=>'8250', 'rsquo'=>'8217', 'sbquo'=>'8218', 'Scaron'=>'352', 'scaron'=>'353', 'sdot'=>'8901', 'sect'=>'167', 'shy'=>'173', 'Sigma'=>'931', 'sigma'=>'963', 'sigmaf'=>'962', 'sim'=>'8764', 'spades'=>'9824', 'sub'=>'8834', 'sube'=>'8838', 'sum'=>'8721', 'sup'=>'8835', 'sup1'=>'185', 'sup2'=>'178', 'sup3'=>'179', 'supe'=>'8839', 'szlig'=>'223', 'Tau'=>'932', 'tau'=>'964', 'there4'=>'8756', 'Theta'=>'920', 'theta'=>'952', 'thetasym'=>'977', 'thinsp'=>'8201', 'THORN'=>'222', 'thorn'=>'254', 'tilde'=>'732', 'times'=>'215', 'trade'=>'8482', 'Uacute'=>'218', 'uacute'=>'250', 'uarr'=>'8593', 'uArr'=>'8657', 'Ucirc'=>'219', 'ucirc'=>'251', 'Ugrave'=>'217', 'ugrave'=>'249', 'uml'=>'168', 'upsih'=>'978', 'Upsilon'=>'933', 'upsilon'=>'965', 'Uuml'=>'220', 'uuml'=>'252', 'weierp'=>'8472', 'Xi'=>'926', 'xi'=>'958', 'Yacute'=>'221', 'yacute'=>'253', 'yen'=>'165', 'yuml'=>'255', 'Yuml'=>'376', 'Zeta'=>'918', 'zeta'=>'950', 'zwj'=>'8205', 'zwnj'=>'8204');
  static $rareEntNameAr = array('Abreve'=>'258', 'abreve'=>'259', 'ac'=>'8766', 'acd'=>'8767', 'Acy'=>'1040', 'acy'=>'1072', 'af'=>'8289', 'Afr'=>'120068', 'afr'=>'120094', 'aleph'=>'8501', 'Amacr'=>'256', 'amacr'=>'257', 'amalg'=>'10815', 'And'=>'10835', 'andand'=>'10837', 'andd'=>'10844', 'andslope'=>'10840', 'andv'=>'10842', 'ange'=>'10660', 'angle'=>'8736', 'angmsd'=>'8737', 'angmsdaa'=>'10664', 'angmsdab'=>'10665', 'angmsdac'=>'10666', 'angmsdad'=>'10667', 'angmsdae'=>'10668', 'angmsdaf'=>'10669', 'angmsdag'=>'10670', 'angmsdah'=>'10671', 'angrt'=>'8735', 'angrtvb'=>'8894', 'angrtvbd'=>'10653', 'angsph'=>'8738', 'angst'=>'197', 'angzarr'=>'9084', 'Aogon'=>'260', 'aogon'=>'261', 'Aopf'=>'120120', 'aopf'=>'120146', 'ap'=>'8776', 'apacir'=>'10863', 'apE'=>'10864', 'ape'=>'8778', 'apid'=>'8779', 'ApplyFunction'=>'8289', 'approx'=>'8776', 'approxeq'=>'8778', 'Ascr'=>'119964', 'ascr'=>'119990', 'Assign'=>'8788', 'ast'=>'42', 'asympeq'=>'8781', 'awconint'=>'8755', 'awint'=>'10769', 'backcong'=>'8780', 'backepsilon'=>'1014', 'backprime'=>'8245', 'backsim'=>'8765', 'backsimeq'=>'8909', 'Backslash'=>'8726', 'Barv'=>'10983', 'barvee'=>'8893', 'barwed'=>'8965', 'Barwed'=>'8966', 'barwedge'=>'8965', 'bbrk'=>'9141', 'bbrktbrk'=>'9142', 'bcong'=>'8780', 'Bcy'=>'1041', 'bcy'=>'1073', 'becaus'=>'8757', 'because'=>'8757', 'Because'=>'8757', 'bemptyv'=>'10672', 'bepsi'=>'1014', 'bernou'=>'8492', 'Bernoullis'=>'8492', 'beth'=>'8502', 'between'=>'8812', 'Bfr'=>'120069', 'bfr'=>'120095', 'bigcap'=>'8898', 'bigcirc'=>'9711', 'bigcup'=>'8899', 'bigodot'=>'10752', 'bigoplus'=>'10753', 'bigotimes'=>'10754', 'bigsqcup'=>'10758', 'bigstar'=>'9733', 'bigtriangledown'=>'9661', 'bigtriangleup'=>'9651', 'biguplus'=>'10756', 'bigvee'=>'8897', 'bigwedge'=>'8896', 'bkarow'=>'10509', 'blacklozenge'=>'10731', 'blacksquare'=>'9642', 'blacktriangle'=>'9652', 'blacktriangledown'=>'9662', 'blacktriangleleft'=>'9666', 'blacktriangleright'=>'9656', 'blank'=>'9251', 'blk12'=>'9618', 'blk14'=>'9617', 'blk34'=>'9619', 'block'=>'9608', 'bNot'=>'10989', 'bnot'=>'8976', 'Bopf'=>'120121', 'bopf'=>'120147', 'bot'=>'8869', 'bottom'=>'8869', 'bowtie'=>'8904', 'boxbox'=>'10697', 'boxdl'=>'9488', 'boxdL'=>'9557', 'boxDl'=>'9558', 'boxDL'=>'9559', 'boxdr'=>'9484', 'boxdR'=>'9554', 'boxDr'=>'9555', 'boxDR'=>'9556', 'boxh'=>'9472', 'boxH'=>'9552', 'boxhd'=>'9516', 'boxHd'=>'9572', 'boxhD'=>'9573', 'boxHD'=>'9574', 'boxhu'=>'9524', 'boxHu'=>'9575', 'boxhU'=>'9576', 'boxHU'=>'9577', 'boxminus'=>'8863', 'boxplus'=>'8862', 'boxtimes'=>'8864', 'boxul'=>'9496', 'boxuL'=>'9563', 'boxUl'=>'9564', 'boxUL'=>'9565', 'boxur'=>'9492', 'boxuR'=>'9560', 'boxUr'=>'9561', 'boxUR'=>'9562', 'boxv'=>'9474', 'boxV'=>'9553', 'boxvh'=>'9532', 'boxvH'=>'9578', 'boxVh'=>'9579', 'boxVH'=>'9580', 'boxvl'=>'9508', 'boxvL'=>'9569', 'boxVl'=>'9570', 'boxVL'=>'9571', 'boxvr'=>'9500', 'boxvR'=>'9566', 'boxVr'=>'9567', 'boxVR'=>'9568', 'bprime'=>'8245', 'breve'=>'728', 'Breve'=>'728', 'bscr'=>'119991', 'Bscr'=>'8492', 'bsemi'=>'8271', 'bsim'=>'8765', 'bsime'=>'8909', 'bsol'=>'92', 'bsolb'=>'10693', 'bsolhsub'=>'10184', 'bullet'=>'8226', 'bump'=>'8782', 'bumpE'=>'10926', 'bumpe'=>'8783', 'Bumpeq'=>'8782', 'bumpeq'=>'8783', 'Cacute'=>'262', 'cacute'=>'263', 'Cap'=>'8914', 'capand'=>'10820', 'capbrcup'=>'10825', 'capcap'=>'10827', 'capcup'=>'10823', 'capdot'=>'10816', 'CapitalDifferentialD'=>'8517', 'caret'=>'8257', 'caron'=>'711', 'Cayleys'=>'8493', 'ccaps'=>'10829', 'Ccaron'=>'268', 'ccaron'=>'269', 'Ccirc'=>'264', 'ccirc'=>'265', 'Cconint'=>'8752', 'ccups'=>'10828', 'ccupssm'=>'10832', 'Cdot'=>'266', 'cdot'=>'267', 'Cedilla'=>'184', 'cemptyv'=>'10674', 'centerdot'=>'183', 'CenterDot'=>'183', 'cfr'=>'120096', 'Cfr'=>'8493', 'CHcy'=>'1063', 'chcy'=>'1095', 'check'=>'10003', 'checkmark'=>'10003', 'cir'=>'9675', 'circeq'=>'8791', 'circlearrowleft'=>'8634', 'circlearrowright'=>'8635', 'circledast'=>'8859', 'circledcirc'=>'8858', 'circleddash'=>'8861', 'CircleDot'=>'8857', 'circledR'=>'174', 'circledS'=>'9416', 'CircleMinus'=>'8854', 'CirclePlus'=>'8853', 'CircleTimes'=>'8855', 'cirE'=>'10691', 'cire'=>'8791', 'cirfnint'=>'10768', 'cirmid'=>'10991', 'cirscir'=>'10690', 'ClockwiseContourIntegral'=>'8754', 'CloseCurlyDoubleQuote'=>'8221', 'CloseCurlyQuote'=>'8217', 'clubsuit'=>'9827', 'colon'=>'58', 'Colon'=>'8759', 'Colone'=>'10868', 'colone'=>'8788', 'coloneq'=>'8788', 'comma'=>'44', 'commat'=>'64', 'comp'=>'8705', 'compfn'=>'8728', 'complement'=>'8705', 'complexes'=>'8450', 'congdot'=>'10861', 'Congruent'=>'8801', 'conint'=>'8750', 'Conint'=>'8751', 'ContourIntegral'=>'8750', 'copf'=>'120148', 'Copf'=>'8450', 'coprod'=>'8720', 'Coproduct'=>'8720', 'COPY'=>'169', 'copysr'=>'8471', 'CounterClockwiseContourIntegral'=>'8755', 'cross'=>'10007', 'Cross'=>'10799', 'Cscr'=>'119966', 'cscr'=>'119992', 'csub'=>'10959', 'csube'=>'10961', 'csup'=>'10960', 'csupe'=>'10962', 'ctdot'=>'8943', 'cudarrl'=>'10552', 'cudarrr'=>'10549', 'cuepr'=>'8926', 'cuesc'=>'8927', 'cularr'=>'8630', 'cularrp'=>'10557', 'Cup'=>'8915', 'cupbrcap'=>'10824', 'cupcap'=>'10822', 'CupCap'=>'8781', 'cupcup'=>'10826', 'cupdot'=>'8845', 'cupor'=>'10821', 'curarr'=>'8631', 'curarrm'=>'10556', 'curlyeqprec'=>'8926', 'curlyeqsucc'=>'8927', 'curlyvee'=>'8910', 'curlywedge'=>'8911', 'curvearrowleft'=>'8630', 'curvearrowright'=>'8631', 'cuvee'=>'8910', 'cuwed'=>'8911', 'cwconint'=>'8754', 'cwint'=>'8753', 'cylcty'=>'9005', 'daleth'=>'8504', 'Darr'=>'8609', 'dash'=>'8208', 'Dashv'=>'10980', 'dashv'=>'8867', 'dbkarow'=>'10511', 'dblac'=>'733', 'Dcaron'=>'270', 'dcaron'=>'271', 'Dcy'=>'1044', 'dcy'=>'1076', 'DD'=>'8517', 'dd'=>'8518', 'ddagger'=>'8225', 'ddarr'=>'8650', 'DDotrahd'=>'10513', 'ddotseq'=>'10871', 'Del'=>'8711', 'demptyv'=>'10673', 'dfisht'=>'10623', 'Dfr'=>'120071', 'dfr'=>'120097', 'dHar'=>'10597', 'dharl'=>'8643', 'dharr'=>'8642', 'DiacriticalAcute'=>'180', 'DiacriticalDot'=>'729', 'DiacriticalDoubleAcute'=>'733', 'DiacriticalGrave'=>'96', 'DiacriticalTilde'=>'732', 'diam'=>'8900', 'diamond'=>'8900', 'Diamond'=>'8900', 'diamondsuit'=>'9830', 'die'=>'168', 'DifferentialD'=>'8518', 'digamma'=>'989', 'disin'=>'8946', 'div'=>'247', 'divideontimes'=>'8903', 'divonx'=>'8903', 'DJcy'=>'1026', 'djcy'=>'1106', 'dlcorn'=>'8990', 'dlcrop'=>'8973', 'dollar'=>'36', 'Dopf'=>'120123', 'dopf'=>'120149', 'Dot'=>'168', 'dot'=>'729', 'DotDot'=>'8412', 'doteq'=>'8784', 'doteqdot'=>'8785', 'DotEqual'=>'8784', 'dotminus'=>'8760', 'dotplus'=>'8724', 'dotsquare'=>'8865', 'doublebarwedge'=>'8966', 'DoubleContourIntegral'=>'8751', 'DoubleDot'=>'168', 'DoubleDownArrow'=>'8659', 'DoubleLeftArrow'=>'8656', 'DoubleLeftRightArrow'=>'8660', 'DoubleLeftTee'=>'10980', 'DoubleLongLeftArrow'=>'10232', 'DoubleLongLeftRightArrow'=>'10234', 'DoubleLongRightArrow'=>'10233', 'DoubleRightArrow'=>'8658', 'DoubleRightTee'=>'8872', 'DoubleUpArrow'=>'8657', 'DoubleUpDownArrow'=>'8661', 'DoubleVerticalBar'=>'8741', 'downarrow'=>'8595', 'DownArrow'=>'8595', 'Downarrow'=>'8659', 'DownArrowBar'=>'10515', 'DownArrowUpArrow'=>'8693', 'DownBreve'=>'785', 'downdownarrows'=>'8650', 'downharpoonleft'=>'8643', 'downharpoonright'=>'8642', 'DownLeftRightVector'=>'10576', 'DownLeftTeeVector'=>'10590', 'DownLeftVector'=>'8637', 'DownLeftVectorBar'=>'10582', 'DownRightTeeVector'=>'10591', 'DownRightVector'=>'8641', 'DownRightVectorBar'=>'10583', 'DownTee'=>'8868', 'DownTeeArrow'=>'8615', 'drbkarow'=>'10512', 'drcorn'=>'8991', 'drcrop'=>'8972', 'Dscr'=>'119967', 'dscr'=>'119993', 'DScy'=>'1029', 'dscy'=>'1109', 'dsol'=>'10742', 'Dstrok'=>'272', 'dstrok'=>'273', 'dtdot'=>'8945', 'dtri'=>'9663', 'dtrif'=>'9662', 'duarr'=>'8693', 'duhar'=>'10607', 'dwangle'=>'10662', 'DZcy'=>'1039', 'dzcy'=>'1119', 'dzigrarr'=>'10239', 'easter'=>'10862', 'Ecaron'=>'282', 'ecaron'=>'283', 'ecir'=>'8790', 'ecolon'=>'8789', 'Ecy'=>'1069', 'ecy'=>'1101', 'eDDot'=>'10871', 'Edot'=>'278', 'edot'=>'279', 'eDot'=>'8785', 'ee'=>'8519', 'efDot'=>'8786', 'Efr'=>'120072', 'efr'=>'120098', 'eg'=>'10906', 'egs'=>'10902', 'egsdot'=>'10904', 'el'=>'10905', 'Element'=>'8712', 'elinters'=>'9191', 'ell'=>'8467', 'els'=>'10901', 'elsdot'=>'10903', 'Emacr'=>'274', 'emacr'=>'275', 'emptyset'=>'8709', 'EmptySmallSquare'=>'9723', 'emptyv'=>'8709', 'EmptyVerySmallSquare'=>'9643', 'emsp13'=>'8196', 'emsp14'=>'8197', 'ENG'=>'330', 'eng'=>'331', 'Eogon'=>'280', 'eogon'=>'281', 'Eopf'=>'120124', 'eopf'=>'120150', 'epar'=>'8917', 'eparsl'=>'10723', 'eplus'=>'10865', 'epsi'=>'949', 'epsiv'=>'1013', 'eqcirc'=>'8790', 'eqcolon'=>'8789', 'eqsim'=>'8770', 'eqslantgtr'=>'10902', 'eqslantless'=>'10901', 'Equal'=>'10869', 'equals'=>'61', 'EqualTilde'=>'8770', 'equest'=>'8799', 'Equilibrium'=>'8652', 'equivDD'=>'10872', 'eqvparsl'=>'10725', 'erarr'=>'10609', 'erDot'=>'8787', 'escr'=>'8495', 'Escr'=>'8496', 'esdot'=>'8784', 'Esim'=>'10867', 'esim'=>'8770', 'excl'=>'33', 'Exists'=>'8707', 'expectation'=>'8496', 'exponentiale'=>'8519', 'ExponentialE'=>'8519', 'fallingdotseq'=>'8786', 'Fcy'=>'1060', 'fcy'=>'1092', 'female'=>'9792', 'ffilig'=>'64259', 'fflig'=>'64256', 'ffllig'=>'64260', 'Ffr'=>'120073', 'ffr'=>'120099', 'filig'=>'64257', 'FilledSmallSquare'=>'9724', 'FilledVerySmallSquare'=>'9642', 'flat'=>'9837', 'fllig'=>'64258', 'fltns'=>'9649', 'Fopf'=>'120125', 'fopf'=>'120151', 'ForAll'=>'8704', 'fork'=>'8916', 'forkv'=>'10969', 'Fouriertrf'=>'8497', 'fpartint'=>'10765', 'frac13'=>'8531', 'frac15'=>'8533', 'frac16'=>'8537', 'frac18'=>'8539', 'frac23'=>'8532', 'frac25'=>'8534', 'frac35'=>'8535', 'frac38'=>'8540', 'frac45'=>'8536', 'frac56'=>'8538', 'frac58'=>'8541', 'frac78'=>'8542', 'frown'=>'8994', 'fscr'=>'119995', 'Fscr'=>'8497', 'gacute'=>'501', 'Gammad'=>'988', 'gammad'=>'989', 'gap'=>'10886', 'Gbreve'=>'286', 'gbreve'=>'287', 'Gcedil'=>'290', 'Gcirc'=>'284', 'gcirc'=>'285', 'Gcy'=>'1043', 'gcy'=>'1075', 'Gdot'=>'288', 'gdot'=>'289', 'gE'=>'8807', 'gEl'=>'10892', 'gel'=>'8923', 'geq'=>'8805', 'geqq'=>'8807', 'geqslant'=>'10878', 'ges'=>'10878', 'gescc'=>'10921', 'gesdot'=>'10880', 'gesdoto'=>'10882', 'gesdotol'=>'10884', 'gesles'=>'10900', 'Gfr'=>'120074', 'gfr'=>'120100', 'gg'=>'8811', 'Gg'=>'8921', 'ggg'=>'8921', 'gimel'=>'8503', 'GJcy'=>'1027', 'gjcy'=>'1107', 'gl'=>'8823', 'gla'=>'10917', 'glE'=>'10898', 'glj'=>'10916', 'gnap'=>'10890', 'gnapprox'=>'10890', 'gne'=>'10888', 'gnE'=>'8809', 'gneq'=>'10888', 'gneqq'=>'8809', 'gnsim'=>'8935', 'Gopf'=>'120126', 'gopf'=>'120152', 'grave'=>'96', 'GreaterEqual'=>'8805', 'GreaterEqualLess'=>'8923', 'GreaterFullEqual'=>'8807', 'GreaterGreater'=>'10914', 'GreaterLess'=>'8823', 'GreaterSlantEqual'=>'10878', 'GreaterTilde'=>'8819', 'Gscr'=>'119970', 'gscr'=>'8458', 'gsim'=>'8819', 'gsime'=>'10894', 'gsiml'=>'10896', 'Gt'=>'8811', 'gtcc'=>'10919', 'gtcir'=>'10874', 'gtdot'=>'8919', 'gtlPar'=>'10645', 'gtquest'=>'10876', 'gtrapprox'=>'10886', 'gtrarr'=>'10616', 'gtrdot'=>'8919', 'gtreqless'=>'8923', 'gtreqqless'=>'10892', 'gtrless'=>'8823', 'gtrsim'=>'8819', 'Hacek'=>'711', 'hairsp'=>'8202', 'half'=>'189', 'hamilt'=>'8459', 'HARDcy'=>'1066', 'hardcy'=>'1098', 'harrcir'=>'10568', 'harrw'=>'8621', 'Hat'=>'94', 'hbar'=>'8463', 'Hcirc'=>'292', 'hcirc'=>'293', 'heartsuit'=>'9829', 'hercon'=>'8889', 'hfr'=>'120101', 'Hfr'=>'8460', 'HilbertSpace'=>'8459', 'hksearow'=>'10533', 'hkswarow'=>'10534', 'hoarr'=>'8703', 'homtht'=>'8763', 'hookleftarrow'=>'8617', 'hookrightarrow'=>'8618', 'hopf'=>'120153', 'Hopf'=>'8461', 'horbar'=>'8213', 'HorizontalLine'=>'9472', 'hscr'=>'119997', 'Hscr'=>'8459', 'hslash'=>'8463', 'Hstrok'=>'294', 'hstrok'=>'295', 'HumpDownHump'=>'8782', 'HumpEqual'=>'8783', 'hybull'=>'8259', 'hyphen'=>'8208', 'ic'=>'8291', 'Icy'=>'1048', 'icy'=>'1080', 'Idot'=>'304', 'IEcy'=>'1045', 'iecy'=>'1077', 'iff'=>'8660', 'ifr'=>'120102', 'Ifr'=>'8465', 'ii'=>'8520', 'iiiint'=>'10764', 'iiint'=>'8749', 'iinfin'=>'10716', 'iiota'=>'8489', 'IJlig'=>'306', 'ijlig'=>'307', 'Im'=>'8465', 'Imacr'=>'298', 'imacr'=>'299', 'ImaginaryI'=>'8520', 'imagline'=>'8464', 'imagpart'=>'8465', 'imath'=>'305', 'imof'=>'8887', 'imped'=>'437', 'Implies'=>'8658', 'in'=>'8712', 'incare'=>'8453', 'infintie'=>'10717', 'inodot'=>'305', 'Int'=>'8748', 'intcal'=>'8890', 'integers'=>'8484', 'Integral'=>'8747', 'intercal'=>'8890', 'Intersection'=>'8898', 'intlarhk'=>'10775', 'intprod'=>'10812', 'InvisibleComma'=>'8291', 'InvisibleTimes'=>'8290', 'IOcy'=>'1025', 'iocy'=>'1105', 'Iogon'=>'302', 'iogon'=>'303', 'Iopf'=>'120128', 'iopf'=>'120154', 'iprod'=>'10812', 'iscr'=>'119998', 'Iscr'=>'8464', 'isindot'=>'8949', 'isinE'=>'8953', 'isins'=>'8948', 'isinsv'=>'8947', 'isinv'=>'8712', 'it'=>'8290', 'Itilde'=>'296', 'itilde'=>'297', 'Iukcy'=>'1030', 'iukcy'=>'1110', 'Jcirc'=>'308', 'jcirc'=>'309', 'Jcy'=>'1049', 'jcy'=>'1081', 'Jfr'=>'120077', 'jfr'=>'120103', 'jmath'=>'567', 'Jopf'=>'120129', 'jopf'=>'120155', 'Jscr'=>'119973', 'jscr'=>'119999', 'Jsercy'=>'1032', 'jsercy'=>'1112', 'Jukcy'=>'1028', 'jukcy'=>'1108', 'kappav'=>'1008', 'Kcedil'=>'310', 'kcedil'=>'311', 'Kcy'=>'1050', 'kcy'=>'1082', 'Kfr'=>'120078', 'kfr'=>'120104', 'kgreen'=>'312', 'KHcy'=>'1061', 'khcy'=>'1093', 'KJcy'=>'1036', 'kjcy'=>'1116', 'Kopf'=>'120130', 'kopf'=>'120156', 'Kscr'=>'119974', 'kscr'=>'120000', 'lAarr'=>'8666', 'Lacute'=>'313', 'lacute'=>'314', 'laemptyv'=>'10676', 'lagran'=>'8466', 'lang'=>'10216', 'Lang'=>'10218', 'langd'=>'10641', 'langle'=>'10216', 'lap'=>'10885', 'Laplacetrf'=>'8466', 'Larr'=>'8606', 'larrb'=>'8676', 'larrbfs'=>'10527', 'larrfs'=>'10525', 'larrhk'=>'8617', 'larrlp'=>'8619', 'larrpl'=>'10553', 'larrsim'=>'10611', 'larrtl'=>'8610', 'lat'=>'10923', 'latail'=>'10521', 'lAtail'=>'10523', 'late'=>'10925', 'lbarr'=>'10508', 'lBarr'=>'10510', 'lbbrk'=>'10098', 'lbrace'=>'123', 'lbrack'=>'91', 'lbrke'=>'10635', 'lbrksld'=>'10639', 'lbrkslu'=>'10637', 'Lcaron'=>'317', 'lcaron'=>'318', 'Lcedil'=>'315', 'lcedil'=>'316', 'lcub'=>'123', 'Lcy'=>'1051', 'lcy'=>'1083', 'ldca'=>'10550', 'ldquor'=>'8222', 'ldrdhar'=>'10599', 'ldrushar'=>'10571', 'ldsh'=>'8626', 'lE'=>'8806', 'LeftAngleBracket'=>'10216', 'leftarrow'=>'8592', 'LeftArrow'=>'8592', 'Leftarrow'=>'8656', 'LeftArrowBar'=>'8676', 'LeftArrowRightArrow'=>'8646', 'leftarrowtail'=>'8610', 'LeftCeiling'=>'8968', 'LeftDoubleBracket'=>'10214', 'LeftDownTeeVector'=>'10593', 'LeftDownVector'=>'8643', 'LeftDownVectorBar'=>'10585', 'LeftFloor'=>'8970', 'leftharpoondown'=>'8637', 'leftharpoonup'=>'8636', 'leftleftarrows'=>'8647', 'leftrightarrow'=>'8596', 'LeftRightArrow'=>'8596', 'Leftrightarrow'=>'8660', 'leftrightarrows'=>'8646', 'leftrightharpoons'=>'8651', 'leftrightsquigarrow'=>'8621', 'LeftRightVector'=>'10574', 'LeftTee'=>'8867', 'LeftTeeArrow'=>'8612', 'LeftTeeVector'=>'10586', 'leftthreetimes'=>'8907', 'LeftTriangle'=>'8882', 'LeftTriangleBar'=>'10703', 'LeftTriangleEqual'=>'8884', 'LeftUpDownVector'=>'10577', 'LeftUpTeeVector'=>'10592', 'LeftUpVector'=>'8639', 'LeftUpVectorBar'=>'10584', 'LeftVector'=>'8636', 'LeftVectorBar'=>'10578', 'lEg'=>'10891', 'leg'=>'8922', 'leq'=>'8804', 'leqq'=>'8806', 'leqslant'=>'10877', 'les'=>'10877', 'lescc'=>'10920', 'lesdot'=>'10879', 'lesdoto'=>'10881', 'lesdotor'=>'10883', 'lesges'=>'10899', 'lessapprox'=>'10885', 'lessdot'=>'8918', 'lesseqgtr'=>'8922', 'lesseqqgtr'=>'10891', 'LessEqualGreater'=>'8922', 'LessFullEqual'=>'8806', 'LessGreater'=>'8822', 'lessgtr'=>'8822', 'LessLess'=>'10913', 'lesssim'=>'8818', 'LessSlantEqual'=>'10877', 'LessTilde'=>'8818', 'lfisht'=>'10620', 'Lfr'=>'120079', 'lfr'=>'120105', 'lg'=>'8822', 'lgE'=>'10897', 'lHar'=>'10594', 'lhard'=>'8637', 'lharu'=>'8636', 'lharul'=>'10602', 'lhblk'=>'9604', 'LJcy'=>'1033', 'ljcy'=>'1113', 'll'=>'8810', 'Ll'=>'8920', 'llarr'=>'8647', 'llcorner'=>'8990', 'Lleftarrow'=>'8666', 'llhard'=>'10603', 'lltri'=>'9722', 'Lmidot'=>'319', 'lmidot'=>'320', 'lmoust'=>'9136', 'lmoustache'=>'9136', 'lnap'=>'10889', 'lnapprox'=>'10889', 'lne'=>'10887', 'lnE'=>'8808', 'lneq'=>'10887', 'lneqq'=>'8808', 'lnsim'=>'8934', 'loang'=>'10220', 'loarr'=>'8701', 'lobrk'=>'10214', 'longleftarrow'=>'10229', 'LongLeftArrow'=>'10229', 'Longleftarrow'=>'10232', 'longleftrightarrow'=>'10231', 'LongLeftRightArrow'=>'10231', 'Longleftrightarrow'=>'10234', 'longmapsto'=>'10236', 'longrightarrow'=>'10230', 'LongRightArrow'=>'10230', 'Longrightarrow'=>'10233', 'looparrowleft'=>'8619', 'looparrowright'=>'8620', 'lopar'=>'10629', 'Lopf'=>'120131', 'lopf'=>'120157', 'loplus'=>'10797', 'lotimes'=>'10804', 'lowbar'=>'95', 'LowerLeftArrow'=>'8601', 'LowerRightArrow'=>'8600', 'lozenge'=>'9674', 'lozf'=>'10731', 'lpar'=>'40', 'lparlt'=>'10643', 'lrarr'=>'8646', 'lrcorner'=>'8991', 'lrhar'=>'8651', 'lrhard'=>'10605', 'lrtri'=>'8895', 'lscr'=>'120001', 'Lscr'=>'8466', 'lsh'=>'8624', 'Lsh'=>'8624', 'lsim'=>'8818', 'lsime'=>'10893', 'lsimg'=>'10895', 'lsqb'=>'91', 'lsquor'=>'8218', 'Lstrok'=>'321', 'lstrok'=>'322', 'Lt'=>'8810', 'ltcc'=>'10918', 'ltcir'=>'10873', 'ltdot'=>'8918', 'lthree'=>'8907', 'ltimes'=>'8905', 'ltlarr'=>'10614', 'ltquest'=>'10875', 'ltri'=>'9667', 'ltrie'=>'8884', 'ltrif'=>'9666', 'ltrPar'=>'10646', 'lurdshar'=>'10570', 'luruhar'=>'10598', 'male'=>'9794', 'malt'=>'10016', 'maltese'=>'10016', 'Map'=>'10501', 'map'=>'8614', 'mapsto'=>'8614', 'mapstodown'=>'8615', 'mapstoleft'=>'8612', 'mapstoup'=>'8613', 'marker'=>'9646', 'mcomma'=>'10793', 'Mcy'=>'1052', 'mcy'=>'1084', 'mDDot'=>'8762', 'measuredangle'=>'8737', 'MediumSpace'=>'8287', 'Mellintrf'=>'8499', 'Mfr'=>'120080', 'mfr'=>'120106', 'mho'=>'8487', 'mid'=>'8739', 'midast'=>'42', 'midcir'=>'10992', 'minusb'=>'8863', 'minusd'=>'8760', 'minusdu'=>'10794', 'MinusPlus'=>'8723', 'mlcp'=>'10971', 'mldr'=>'8230', 'mnplus'=>'8723', 'models'=>'8871', 'Mopf'=>'120132', 'mopf'=>'120158', 'mp'=>'8723', 'mscr'=>'120002', 'Mscr'=>'8499', 'mstpos'=>'8766', 'multimap'=>'8888', 'mumap'=>'8888', 'Nacute'=>'323', 'nacute'=>'324', 'nap'=>'8777', 'napos'=>'329', 'napprox'=>'8777', 'natur'=>'9838', 'natural'=>'9838', 'naturals'=>'8469', 'ncap'=>'10819', 'Ncaron'=>'327', 'ncaron'=>'328', 'Ncedil'=>'325', 'ncedil'=>'326', 'ncong'=>'8775', 'ncup'=>'10818', 'Ncy'=>'1053', 'ncy'=>'1085', 'nearhk'=>'10532', 'nearr'=>'8599', 'neArr'=>'8663', 'nearrow'=>'8599', 'NegativeMediumSpace'=>'8203', 'NegativeThickSpace'=>'8203', 'NegativeThinSpace'=>'8203', 'NegativeVeryThinSpace'=>'8203', 'nequiv'=>'8802', 'nesear'=>'10536', 'NestedGreaterGreater'=>'8811', 'NestedLessLess'=>'8810', 'NewLine'=>'10', 'nexist'=>'8708', 'nexists'=>'8708', 'Nfr'=>'120081', 'nfr'=>'120107', 'nge'=>'8817', 'ngeq'=>'8817', 'ngsim'=>'8821', 'ngt'=>'8815', 'ngtr'=>'8815', 'nharr'=>'8622', 'nhArr'=>'8654', 'nhpar'=>'10994', 'nis'=>'8956', 'nisd'=>'8954', 'niv'=>'8715', 'NJcy'=>'1034', 'njcy'=>'1114', 'nlarr'=>'8602', 'nlArr'=>'8653', 'nldr'=>'8229', 'nle'=>'8816', 'nleftarrow'=>'8602', 'nLeftarrow'=>'8653', 'nleftrightarrow'=>'8622', 'nLeftrightarrow'=>'8654', 'nleq'=>'8816', 'nless'=>'8814', 'nlsim'=>'8820', 'nlt'=>'8814', 'nltri'=>'8938', 'nltrie'=>'8940', 'nmid'=>'8740', 'NoBreak'=>'8288', 'NonBreakingSpace'=>'160', 'nopf'=>'120159', 'Nopf'=>'8469', 'Not'=>'10988', 'NotCongruent'=>'8802', 'NotCupCap'=>'8813', 'NotDoubleVerticalBar'=>'8742', 'NotElement'=>'8713', 'NotEqual'=>'8800', 'NotExists'=>'8708', 'NotGreater'=>'8815', 'NotGreaterEqual'=>'8817', 'NotGreaterLess'=>'8825', 'NotGreaterTilde'=>'8821', 'notinva'=>'8713', 'notinvb'=>'8951', 'notinvc'=>'8950', 'NotLeftTriangle'=>'8938', 'NotLeftTriangleEqual'=>'8940', 'NotLess'=>'8814', 'NotLessEqual'=>'8816', 'NotLessGreater'=>'8824', 'NotLessTilde'=>'8820', 'notni'=>'8716', 'notniva'=>'8716', 'notnivb'=>'8958', 'notnivc'=>'8957', 'NotPrecedes'=>'8832', 'NotPrecedesSlantEqual'=>'8928', 'NotReverseElement'=>'8716', 'NotRightTriangle'=>'8939', 'NotRightTriangleEqual'=>'8941', 'NotSquareSubsetEqual'=>'8930', 'NotSquareSupersetEqual'=>'8931', 'NotSubsetEqual'=>'8840', 'NotSucceeds'=>'8833', 'NotSucceedsSlantEqual'=>'8929', 'NotSupersetEqual'=>'8841', 'NotTilde'=>'8769', 'NotTildeEqual'=>'8772', 'NotTildeFullEqual'=>'8775', 'NotTildeTilde'=>'8777', 'NotVerticalBar'=>'8740', 'npar'=>'8742', 'nparallel'=>'8742', 'npolint'=>'10772', 'npr'=>'8832', 'nprcue'=>'8928', 'nprec'=>'8832', 'nrarr'=>'8603', 'nrArr'=>'8655', 'nrightarrow'=>'8603', 'nRightarrow'=>'8655', 'nrtri'=>'8939', 'nrtrie'=>'8941', 'nsc'=>'8833', 'nsccue'=>'8929', 'Nscr'=>'119977', 'nscr'=>'120003', 'nshortmid'=>'8740', 'nshortparallel'=>'8742', 'nsim'=>'8769', 'nsime'=>'8772', 'nsimeq'=>'8772', 'nsmid'=>'8740', 'nspar'=>'8742', 'nsqsube'=>'8930', 'nsqsupe'=>'8931', 'nsube'=>'8840', 'nsubseteq'=>'8840', 'nsucc'=>'8833', 'nsup'=>'8837', 'nsupe'=>'8841', 'nsupseteq'=>'8841', 'ntgl'=>'8825', 'ntlg'=>'8824', 'ntriangleleft'=>'8938', 'ntrianglelefteq'=>'8940', 'ntriangleright'=>'8939', 'ntrianglerighteq'=>'8941', 'num'=>'35', 'numero'=>'8470', 'numsp'=>'8199', 'nvdash'=>'8876', 'nvDash'=>'8877', 'nVdash'=>'8878', 'nVDash'=>'8879', 'nvHarr'=>'10500', 'nvinfin'=>'10718', 'nvlArr'=>'10498', 'nvrArr'=>'10499', 'nwarhk'=>'10531', 'nwarr'=>'8598', 'nwArr'=>'8662', 'nwarrow'=>'8598', 'nwnear'=>'10535', 'oast'=>'8859', 'ocir'=>'8858', 'Ocy'=>'1054', 'ocy'=>'1086', 'odash'=>'8861', 'Odblac'=>'336', 'odblac'=>'337', 'odiv'=>'10808', 'odot'=>'8857', 'odsold'=>'10684', 'ofcir'=>'10687', 'Ofr'=>'120082', 'ofr'=>'120108', 'ogon'=>'731', 'ogt'=>'10689', 'ohbar'=>'10677', 'ohm'=>'937', 'oint'=>'8750', 'olarr'=>'8634', 'olcir'=>'10686', 'olcross'=>'10683', 'olt'=>'10688', 'Omacr'=>'332', 'omacr'=>'333', 'omid'=>'10678', 'ominus'=>'8854', 'Oopf'=>'120134', 'oopf'=>'120160', 'opar'=>'10679', 'OpenCurlyDoubleQuote'=>'8220', 'OpenCurlyQuote'=>'8216', 'operp'=>'10681', 'Or'=>'10836', 'orarr'=>'8635', 'ord'=>'10845', 'order'=>'8500', 'orderof'=>'8500', 'origof'=>'8886', 'oror'=>'10838', 'orslope'=>'10839', 'orv'=>'10843', 'oS'=>'9416', 'Oscr'=>'119978', 'oscr'=>'8500', 'osol'=>'8856', 'Otimes'=>'10807', 'otimesas'=>'10806', 'ovbar'=>'9021', 'OverBar'=>'8254', 'OverBrace'=>'9182', 'OverBracket'=>'9140', 'OverParenthesis'=>'9180', 'par'=>'8741', 'parallel'=>'8741', 'parsim'=>'10995', 'parsl'=>'11005', 'PartialD'=>'8706', 'Pcy'=>'1055', 'pcy'=>'1087', 'percnt'=>'37', 'period'=>'46', 'pertenk'=>'8241', 'Pfr'=>'120083', 'pfr'=>'120109', 'phiv'=>'981', 'phmmat'=>'8499', 'phone'=>'9742', 'pitchfork'=>'8916', 'planck'=>'8463', 'planckh'=>'8462', 'plankv'=>'8463', 'plus'=>'43', 'plusacir'=>'10787', 'plusb'=>'8862', 'pluscir'=>'10786', 'plusdo'=>'8724', 'plusdu'=>'10789', 'pluse'=>'10866', 'PlusMinus'=>'177', 'plussim'=>'10790', 'plustwo'=>'10791', 'pm'=>'177', 'Poincareplane'=>'8460', 'pointint'=>'10773', 'popf'=>'120161', 'Popf'=>'8473', 'Pr'=>'10939', 'pr'=>'8826', 'prap'=>'10935', 'prcue'=>'8828', 'pre'=>'10927', 'prE'=>'10931', 'prec'=>'8826', 'precapprox'=>'10935', 'preccurlyeq'=>'8828', 'Precedes'=>'8826', 'PrecedesEqual'=>'10927', 'PrecedesSlantEqual'=>'8828', 'PrecedesTilde'=>'8830', 'preceq'=>'10927', 'precnapprox'=>'10937', 'precneqq'=>'10933', 'precnsim'=>'8936', 'precsim'=>'8830', 'primes'=>'8473', 'prnap'=>'10937', 'prnE'=>'10933', 'prnsim'=>'8936', 'Product'=>'8719', 'profalar'=>'9006', 'profline'=>'8978', 'profsurf'=>'8979', 'Proportion'=>'8759', 'Proportional'=>'8733', 'propto'=>'8733', 'prsim'=>'8830', 'prurel'=>'8880', 'Pscr'=>'119979', 'pscr'=>'120005', 'puncsp'=>'8200', 'Qfr'=>'120084', 'qfr'=>'120110', 'qint'=>'10764', 'qopf'=>'120162', 'Qopf'=>'8474', 'qprime'=>'8279', 'Qscr'=>'119980', 'qscr'=>'120006', 'quaternions'=>'8461', 'quatint'=>'10774', 'quest'=>'63', 'questeq'=>'8799', 'rAarr'=>'8667', 'Racute'=>'340', 'racute'=>'341', 'raemptyv'=>'10675', 'rang'=>'10217', 'Rang'=>'10219', 'rangd'=>'10642', 'range'=>'10661', 'rangle'=>'10217', 'Rarr'=>'8608', 'rarrap'=>'10613', 'rarrb'=>'8677', 'rarrbfs'=>'10528', 'rarrc'=>'10547', 'rarrfs'=>'10526', 'rarrhk'=>'8618', 'rarrlp'=>'8620', 'rarrpl'=>'10565', 'rarrsim'=>'10612', 'Rarrtl'=>'10518', 'rarrtl'=>'8611', 'rarrw'=>'8605', 'ratail'=>'10522', 'rAtail'=>'10524', 'ratio'=>'8758', 'rationals'=>'8474', 'rbarr'=>'10509', 'rBarr'=>'10511', 'RBarr'=>'10512', 'rbbrk'=>'10099', 'rbrace'=>'125', 'rbrack'=>'93', 'rbrke'=>'10636', 'rbrksld'=>'10638', 'rbrkslu'=>'10640', 'Rcaron'=>'344', 'rcaron'=>'345', 'Rcedil'=>'342', 'rcedil'=>'343', 'rcub'=>'125', 'Rcy'=>'1056', 'rcy'=>'1088', 'rdca'=>'10551', 'rdldhar'=>'10601', 'rdquor'=>'8221', 'rdsh'=>'8627', 'Re'=>'8476', 'realine'=>'8475', 'realpart'=>'8476', 'reals'=>'8477', 'rect'=>'9645', 'REG'=>'174', 'ReverseElement'=>'8715', 'ReverseEquilibrium'=>'8651', 'ReverseUpEquilibrium'=>'10607', 'rfisht'=>'10621', 'rfr'=>'120111', 'Rfr'=>'8476', 'rHar'=>'10596', 'rhard'=>'8641', 'rharu'=>'8640', 'rharul'=>'10604', 'rhov'=>'1009', 'RightAngleBracket'=>'10217', 'rightarrow'=>'8594', 'RightArrow'=>'8594', 'Rightarrow'=>'8658', 'RightArrowBar'=>'8677', 'RightArrowLeftArrow'=>'8644', 'rightarrowtail'=>'8611', 'RightCeiling'=>'8969', 'RightDoubleBracket'=>'10215', 'RightDownTeeVector'=>'10589', 'RightDownVector'=>'8642', 'RightDownVectorBar'=>'10581', 'RightFloor'=>'8971', 'rightharpoondown'=>'8641', 'rightharpoonup'=>'8640', 'rightleftarrows'=>'8644', 'rightleftharpoons'=>'8652', 'rightrightarrows'=>'8649', 'rightsquigarrow'=>'8605', 'RightTee'=>'8866', 'RightTeeArrow'=>'8614', 'RightTeeVector'=>'10587', 'rightthreetimes'=>'8908', 'RightTriangle'=>'8883', 'RightTriangleBar'=>'10704', 'RightTriangleEqual'=>'8885', 'RightUpDownVector'=>'10575', 'RightUpTeeVector'=>'10588', 'RightUpVector'=>'8638', 'RightUpVectorBar'=>'10580', 'RightVector'=>'8640', 'RightVectorBar'=>'10579', 'ring'=>'730', 'risingdotseq'=>'8787', 'rlarr'=>'8644', 'rlhar'=>'8652', 'rmoust'=>'9137', 'rmoustache'=>'9137', 'rnmid'=>'10990', 'roang'=>'10221', 'roarr'=>'8702', 'robrk'=>'10215', 'ropar'=>'10630', 'ropf'=>'120163', 'Ropf'=>'8477', 'roplus'=>'10798', 'rotimes'=>'10805', 'RoundImplies'=>'10608', 'rpar'=>'41', 'rpargt'=>'10644', 'rppolint'=>'10770', 'rrarr'=>'8649', 'Rrightarrow'=>'8667', 'rscr'=>'120007', 'Rscr'=>'8475', 'rsh'=>'8625', 'Rsh'=>'8625', 'rsqb'=>'93', 'rsquor'=>'8217', 'rthree'=>'8908', 'rtimes'=>'8906', 'rtri'=>'9657', 'rtrie'=>'8885', 'rtrif'=>'9656', 'rtriltri'=>'10702', 'RuleDelayed'=>'10740', 'ruluhar'=>'10600', 'rx'=>'8478', 'Sacute'=>'346', 'sacute'=>'347', 'Sc'=>'10940', 'sc'=>'8827', 'scap'=>'10936', 'sccue'=>'8829', 'sce'=>'10928', 'scE'=>'10932', 'Scedil'=>'350', 'scedil'=>'351', 'Scirc'=>'348', 'scirc'=>'349', 'scnap'=>'10938', 'scnE'=>'10934', 'scnsim'=>'8937', 'scpolint'=>'10771', 'scsim'=>'8831', 'Scy'=>'1057', 'scy'=>'1089', 'sdotb'=>'8865', 'sdote'=>'10854', 'searhk'=>'10533', 'searr'=>'8600', 'seArr'=>'8664', 'searrow'=>'8600', 'semi'=>'59', 'seswar'=>'10537', 'setminus'=>'8726', 'setmn'=>'8726', 'sext'=>'10038', 'Sfr'=>'120086', 'sfr'=>'120112', 'sfrown'=>'8994', 'sharp'=>'9839', 'SHCHcy'=>'1065', 'shchcy'=>'1097', 'SHcy'=>'1064', 'shcy'=>'1096', 'ShortDownArrow'=>'8595', 'ShortLeftArrow'=>'8592', 'shortmid'=>'8739', 'shortparallel'=>'8741', 'ShortRightArrow'=>'8594', 'ShortUpArrow'=>'8593', 'sigmav'=>'962', 'simdot'=>'10858', 'sime'=>'8771', 'simeq'=>'8771', 'simg'=>'10910', 'simgE'=>'10912', 'siml'=>'10909', 'simlE'=>'10911', 'simne'=>'8774', 'simplus'=>'10788', 'simrarr'=>'10610', 'slarr'=>'8592', 'SmallCircle'=>'8728', 'smallsetminus'=>'8726', 'smashp'=>'10803', 'smeparsl'=>'10724', 'smid'=>'8739', 'smile'=>'8995', 'smt'=>'10922', 'smte'=>'10924', 'SOFTcy'=>'1068', 'softcy'=>'1100', 'sol'=>'47', 'solb'=>'10692', 'solbar'=>'9023', 'Sopf'=>'120138', 'sopf'=>'120164', 'spadesuit'=>'9824', 'spar'=>'8741', 'sqcap'=>'8851', 'sqcup'=>'8852', 'Sqrt'=>'8730', 'sqsub'=>'8847', 'sqsube'=>'8849', 'sqsubset'=>'8847', 'sqsubseteq'=>'8849', 'sqsup'=>'8848', 'sqsupe'=>'8850', 'sqsupset'=>'8848', 'sqsupseteq'=>'8850', 'squ'=>'9633', 'square'=>'9633', 'Square'=>'9633', 'SquareIntersection'=>'8851', 'SquareSubset'=>'8847', 'SquareSubsetEqual'=>'8849', 'SquareSuperset'=>'8848', 'SquareSupersetEqual'=>'8850', 'SquareUnion'=>'8852', 'squarf'=>'9642', 'squf'=>'9642', 'srarr'=>'8594', 'Sscr'=>'119982', 'sscr'=>'120008', 'ssetmn'=>'8726', 'ssmile'=>'8995', 'sstarf'=>'8902', 'Star'=>'8902', 'star'=>'9734', 'starf'=>'9733', 'straightepsilon'=>'1013', 'straightphi'=>'981', 'strns'=>'175', 'Sub'=>'8912', 'subdot'=>'10941', 'subE'=>'10949', 'subedot'=>'10947', 'submult'=>'10945', 'subnE'=>'10955', 'subne'=>'8842', 'subplus'=>'10943', 'subrarr'=>'10617', 'subset'=>'8834', 'Subset'=>'8912', 'subseteq'=>'8838', 'subseteqq'=>'10949', 'SubsetEqual'=>'8838', 'subsetneq'=>'8842', 'subsetneqq'=>'10955', 'subsim'=>'10951', 'subsub'=>'10965', 'subsup'=>'10963', 'succ'=>'8827', 'succapprox'=>'10936', 'succcurlyeq'=>'8829', 'Succeeds'=>'8827', 'SucceedsEqual'=>'10928', 'SucceedsSlantEqual'=>'8829', 'SucceedsTilde'=>'8831', 'succeq'=>'10928', 'succnapprox'=>'10938', 'succneqq'=>'10934', 'succnsim'=>'8937', 'succsim'=>'8831', 'SuchThat'=>'8715', 'Sum'=>'8721', 'sung'=>'9834', 'Sup'=>'8913', 'supdot'=>'10942', 'supdsub'=>'10968', 'supE'=>'10950', 'supedot'=>'10948', 'Superset'=>'8835', 'SupersetEqual'=>'8839', 'suphsol'=>'10185', 'suphsub'=>'10967', 'suplarr'=>'10619', 'supmult'=>'10946', 'supnE'=>'10956', 'supne'=>'8843', 'supplus'=>'10944', 'supset'=>'8835', 'Supset'=>'8913', 'supseteq'=>'8839', 'supseteqq'=>'10950', 'supsetneq'=>'8843', 'supsetneqq'=>'10956', 'supsim'=>'10952', 'supsub'=>'10964', 'supsup'=>'10966', 'swarhk'=>'10534', 'swarr'=>'8601', 'swArr'=>'8665', 'swarrow'=>'8601', 'swnwar'=>'10538', 'Tab'=>'9', 'target'=>'8982', 'tbrk'=>'9140', 'Tcaron'=>'356', 'tcaron'=>'357', 'Tcedil'=>'354', 'tcedil'=>'355', 'Tcy'=>'1058', 'tcy'=>'1090', 'tdot'=>'8411', 'telrec'=>'8981', 'Tfr'=>'120087', 'tfr'=>'120113', 'therefore'=>'8756', 'Therefore'=>'8756', 'thetav'=>'977', 'thickapprox'=>'8776', 'thicksim'=>'8764', 'ThinSpace'=>'8201', 'thkap'=>'8776', 'thksim'=>'8764', 'Tilde'=>'8764', 'TildeEqual'=>'8771', 'TildeFullEqual'=>'8773', 'TildeTilde'=>'8776', 'timesb'=>'8864', 'timesbar'=>'10801', 'timesd'=>'10800', 'tint'=>'8749', 'toea'=>'10536', 'top'=>'8868', 'topbot'=>'9014', 'topcir'=>'10993', 'Topf'=>'120139', 'topf'=>'120165', 'topfork'=>'10970', 'tosa'=>'10537', 'tprime'=>'8244', 'TRADE'=>'8482', 'triangle'=>'9653', 'triangledown'=>'9663', 'triangleleft'=>'9667', 'trianglelefteq'=>'8884', 'triangleq'=>'8796', 'triangleright'=>'9657', 'trianglerighteq'=>'8885', 'tridot'=>'9708', 'trie'=>'8796', 'triminus'=>'10810', 'TripleDot'=>'8411', 'triplus'=>'10809', 'trisb'=>'10701', 'tritime'=>'10811', 'trpezium'=>'9186', 'Tscr'=>'119983', 'tscr'=>'120009', 'TScy'=>'1062', 'tscy'=>'1094', 'TSHcy'=>'1035', 'tshcy'=>'1115', 'Tstrok'=>'358', 'tstrok'=>'359', 'twixt'=>'8812', 'twoheadleftarrow'=>'8606', 'twoheadrightarrow'=>'8608', 'Uarr'=>'8607', 'Uarrocir'=>'10569', 'Ubrcy'=>'1038', 'ubrcy'=>'1118', 'Ubreve'=>'364', 'ubreve'=>'365', 'Ucy'=>'1059', 'ucy'=>'1091', 'udarr'=>'8645', 'Udblac'=>'368', 'udblac'=>'369', 'udhar'=>'10606', 'ufisht'=>'10622', 'Ufr'=>'120088', 'ufr'=>'120114', 'uHar'=>'10595', 'uharl'=>'8639', 'uharr'=>'8638', 'uhblk'=>'9600', 'ulcorn'=>'8988', 'ulcorner'=>'8988', 'ulcrop'=>'8975', 'ultri'=>'9720', 'Umacr'=>'362', 'umacr'=>'363', 'UnderBar'=>'95', 'UnderBrace'=>'9183', 'UnderBracket'=>'9141', 'UnderParenthesis'=>'9181', 'Union'=>'8899', 'UnionPlus'=>'8846', 'Uogon'=>'370', 'uogon'=>'371', 'Uopf'=>'120140', 'uopf'=>'120166', 'uparrow'=>'8593', 'UpArrow'=>'8593', 'Uparrow'=>'8657', 'UpArrowBar'=>'10514', 'UpArrowDownArrow'=>'8645', 'updownarrow'=>'8597', 'UpDownArrow'=>'8597', 'Updownarrow'=>'8661', 'UpEquilibrium'=>'10606', 'upharpoonleft'=>'8639', 'upharpoonright'=>'8638', 'uplus'=>'8846', 'UpperLeftArrow'=>'8598', 'UpperRightArrow'=>'8599', 'upsi'=>'965', 'Upsi'=>'978', 'UpTee'=>'8869', 'UpTeeArrow'=>'8613', 'upuparrows'=>'8648', 'urcorn'=>'8989', 'urcorner'=>'8989', 'urcrop'=>'8974', 'Uring'=>'366', 'uring'=>'367', 'urtri'=>'9721', 'Uscr'=>'119984', 'uscr'=>'120010', 'utdot'=>'8944', 'Utilde'=>'360', 'utilde'=>'361', 'utri'=>'9653', 'utrif'=>'9652', 'uuarr'=>'8648', 'uwangle'=>'10663', 'vangrt'=>'10652', 'varepsilon'=>'1013', 'varkappa'=>'1008', 'varnothing'=>'8709', 'varphi'=>'981', 'varpi'=>'982', 'varpropto'=>'8733', 'varr'=>'8597', 'vArr'=>'8661', 'varrho'=>'1009', 'varsigma'=>'962', 'vartheta'=>'977', 'vartriangleleft'=>'8882', 'vartriangleright'=>'8883', 'vBar'=>'10984', 'Vbar'=>'10987', 'vBarv'=>'10985', 'Vcy'=>'1042', 'vcy'=>'1074', 'vdash'=>'8866', 'vDash'=>'8872', 'Vdash'=>'8873', 'VDash'=>'8875', 'Vdashl'=>'10982', 'vee'=>'8744', 'Vee'=>'8897', 'veebar'=>'8891', 'veeeq'=>'8794', 'vellip'=>'8942', 'verbar'=>'124', 'Verbar'=>'8214', 'vert'=>'124', 'Vert'=>'8214', 'VerticalBar'=>'8739', 'VerticalLine'=>'124', 'VerticalSeparator'=>'10072', 'VerticalTilde'=>'8768', 'VeryThinSpace'=>'8202', 'Vfr'=>'120089', 'vfr'=>'120115', 'vltri'=>'8882', 'Vopf'=>'120141', 'vopf'=>'120167', 'vprop'=>'8733', 'vrtri'=>'8883', 'Vscr'=>'119985', 'vscr'=>'120011', 'Vvdash'=>'8874', 'vzigzag'=>'10650', 'Wcirc'=>'372', 'wcirc'=>'373', 'wedbar'=>'10847', 'wedge'=>'8743', 'Wedge'=>'8896', 'wedgeq'=>'8793', 'Wfr'=>'120090', 'wfr'=>'120116', 'Wopf'=>'120142', 'wopf'=>'120168', 'wp'=>'8472', 'wr'=>'8768', 'wreath'=>'8768', 'Wscr'=>'119986', 'wscr'=>'120012', 'xcap'=>'8898', 'xcirc'=>'9711', 'xcup'=>'8899', 'xdtri'=>'9661', 'Xfr'=>'120091', 'xfr'=>'120117', 'xharr'=>'10231', 'xhArr'=>'10234', 'xlarr'=>'10229', 'xlArr'=>'10232', 'xmap'=>'10236', 'xnis'=>'8955', 'xodot'=>'10752', 'Xopf'=>'120143', 'xopf'=>'120169', 'xoplus'=>'10753', 'xotime'=>'10754', 'xrarr'=>'10230', 'xrArr'=>'10233', 'Xscr'=>'119987', 'xscr'=>'120013', 'xsqcup'=>'10758', 'xuplus'=>'10756', 'xutri'=>'9651', 'xvee'=>'8897', 'xwedge'=>'8896', 'YAcy'=>'1071', 'yacy'=>'1103', 'Ycirc'=>'374', 'ycirc'=>'375', 'Ycy'=>'1067', 'ycy'=>'1099', 'Yfr'=>'120092', 'yfr'=>'120118', 'YIcy'=>'1031', 'yicy'=>'1111', 'Yopf'=>'120144', 'yopf'=>'120170', 'Yscr'=>'119988', 'yscr'=>'120014', 'YUcy'=>'1070', 'yucy'=>'1102', 'Zacute'=>'377', 'zacute'=>'378', 'Zcaron'=>'381', 'zcaron'=>'382', 'Zcy'=>'1047', 'zcy'=>'1079', 'Zdot'=>'379', 'zdot'=>'380', 'zeetrf'=>'8488', 'ZeroWidthSpace'=>'8203', 'zfr'=>'120119', 'Zfr'=>'8488', 'ZHcy'=>'1046', 'zhcy'=>'1078', 'zigrarr'=>'8669', 'zopf'=>'120171', 'Zopf'=>'8484', 'Zscr'=>'119989', 'zscr'=>'120015');
  if ($t[0] != '#') {
    return
      ($C['and_mark'] ? "\x06" : '&')
      . (isset($reservedEntAr[$t])
         ? $t
         : (isset($commonEntNameAr[$t])
            ? (!$C['named_entity']
               ? '#'. ($C['hexdec_entity'] > 1
                       ? 'x'. dechex($commonEntNameAr[$t])
                       : $commonEntNameAr[$t])
               : $t)
           : (isset($rareEntNameAr[$t])
              ? (!$C['named_entity']
                 ? '#'. ($C['hexdec_entity'] > 1
                         ? 'x'. dechex($rareEntNameAr[$t])
                         : $rareEntNameAr[$t])
                 : $t)
              : 'amp;'. $t)))
      . ';';
  }
  if (
    ($n = ctype_digit($t = substr($t, 1)) ? intval($t) : hexdec(substr($t, 1))) < 9
    || ($n > 13 && $n < 32)
    || $n == 11
    || $n == 12
    || ($n > 126 && $n < 160 && $n != 133)
    || ($n > 55295
        && ($n < 57344
            || ($n > 64975 && $n < 64992)
            || $n == 65534
            || $n == 65535
            || $n > 1114111))
    ) {
    return ($C['and_mark'] ? "\x06" : '&'). "amp;#{$t};";
  }
  return
    ($C['and_mark'] ? "\x06" : '&')
    . '#'
    . (((ctype_digit($t) && $C['hexdec_entity'] < 2)
        || !$C['hexdec_entity'])
       ? $n
       : 'x'. dechex($n))
    . ';';
}

/**
 * Check regex pattern for PHP error.
 *
 * @param  string $t Pattern including limiters/modifiers.
 * @return int       0 or 1 if pattern is invalid or valid, respectively.
 */
function hl_regex($t)
{
  if (empty($t) || !is_string($t)) {
    return 0;
  }
  if ($funcsExist = function_exists('error_clear_last') && function_exists('error_get_last')) {
    error_clear_last();
  } else {
    if ($valTrackErr = ini_get('track_errors')) {
      $valMsgErr = isset($php_errormsg) ? $php_errormsg : null;
    } else {
      ini_set('track_errors', '1');
    }
    unset($php_errormsg);
  }
  if (($valShowErr = ini_get('display_errors'))) {
    ini_set('display_errors', '0');
  }
  preg_match($t, '');
  if ($funcsExist) {
    $out = error_get_last() == null ? 1 : 0;
  } else {
    $out = isset($php_errormsg) ? 0 : 1;
    if ($valTrackErr) {
      $php_errormsg = isset($valMsgErr) ? $valMsgErr : null;
    } else {
      ini_set('track_errors', '0');
    }
  }
  if ($valShowErr) {
    ini_set('display_errors', '1');
  }
  return $out;
}

/**
 * Parse $spec htmLawed argument as array.
 *
 * @param  string $t Value of $spec.
 * @return array     Multidimensional array of form: tag -> attribute -> rule.
 */
function hl_spec($t)
{
  $out  = array();

  // Hide special characters used for rules.

  if (!function_exists('hl_aux1')) {
    function hl_aux1($x) {
      return
        substr(
          str_replace(
            array(";", "|", "~", " ", ",", "/", "(", ")", '`"'),
            array("\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", '"'),
            $x[0]),
          1, -1);
    }
  }
  $t =
    str_replace(
      array("\t", "\r", "\n", ' '),
      '',
      preg_replace_callback('/"(?>(`.|[^"])*)"/sm', 'hl_aux1', trim($t)));

  // Tag, attribute, and rule separators: semi-colon, comma, and slash respectively.

  for ($i = count(($t = explode(';', $t))); --$i>=0;) {
    $ele = $t[$i];
    if (
      empty($ele)
      || ($tagPos = strpos($ele, '=')) === false
      || !strlen(($tagSpec = substr($ele, $tagPos + 1)))
      ) {
      continue;
    }
    $ruleAr = $denyAttrAr = array();
    foreach (explode(',', $tagSpec) as $v) {
      if (!preg_match('`^(-?data-[^:=]+|[a-z:\-\*]+)(?:\((.*?)\))?`i', $v, $m)
          || preg_match('`^-?data-xml`i', $m[1])) {
        continue;
      }
      if (($attr = strtolower($m[1])) == '-*') {
        $denyAttrAr['*'] = 1;
        continue;
      }
      if ($attr[0] == '-') {
        $denyAttrAr[substr($attr, 1)] = 1;
        continue;
      }
      if (!isset($m[2])) {
        $ruleAr[$attr] = 1;
        continue;
      }
      foreach (explode('/', $m[2]) as $m) {
        if (empty($m)
            || ($rulePos = strpos($m, '=')) === 0
            || $rulePos < 5 // Shortest rule: oneof
          ) {
          $ruleAr[$attr] = 1;
          continue;
        }
        $rule = strtolower(substr($m, 0, $rulePos));
        $ruleAr[$attr][$rule] =
          str_replace(
            array("\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08"),
            array(";", "|", "~", " ", ",", "/", "(", ")"),
            substr($m, $rulePos + 1));
      }
      if (isset($ruleAr[$attr]['match']) && !hl_regex($ruleAr[$attr]['match'])) {
        unset($ruleAr[$attr]['match']);
      }
      if (isset($ruleAr[$attr]['nomatch']) && !hl_regex($ruleAr[$attr]['nomatch'])) {
        unset($ruleAr[$attr]['nomatch']);
      }
    }

    if (!count($ruleAr) && !count($denyAttrAr)) {
      continue;
    }
    foreach (explode(',', substr($ele, 0, $tagPos)) as $tag) {
      if (!strlen(($tag = strtolower($tag)))) {
        continue;
      }
      if (count($ruleAr)) {
        $out[$tag] = !isset($out[$tag]) ? $ruleAr : array_merge($out[$tag], $ruleAr);
      }
      if (count($denyAttrAr)) {
        $out[$tag]['deny'] = !isset($out[$tag]['deny']) ? $denyAttrAr : array_merge($out[$tag]['deny'], $denyAttrAr);
      }
    }
  }

  return $out;
}

/**
 * Handle tag text with </> limiters, and attributes in opening tags.
 *
 * @param  array   $t Array from preg_replace call.
 * @return string     Tag with any attribute,
 *                    or text with </> neutralized into entities, or empty.
 */
function hl_tag($t)
{
  $t = $t[0];
  global $C;

  // Check if </> character not in tag.

  if ($t == '< ') {
    return '&lt; ';
  }
  if ($t == '>') {
    return '&gt;';
  }
  if (!preg_match('`^<(/?)([a-zA-Z][^\s>]*)([^>]*?)\s?>$`m', $t, $m)) { // Get tag with element name and attributes
    return str_replace(array('<', '>'), array('&lt;', '&gt;'), $t);
  }

  // Check if element not permitted. Custom element names have certain requirements.

  $ele = rtrim(strtolower($m[2]), '/');
  static $invalidCustomEleAr = array('annotation-xml'=>1, 'color-profile'=>1, 'font-face'=>1, 'font-face-src'=>1, 'font-face-uri'=>1, 'font-face-format'=>1, 'font-face-name'=>1, 'missing-glyph'=>1);
  if (
    (!strpos($ele, '-')
     && !isset($C['elements'][$ele])) // Not custom element
    || (strpos($ele, '-')
        && (isset($C['elements']['-' . $ele])
            || (!$C['any_custom_element']
                && !isset($C['elements'][$ele]))
            || isset($invalidCustomEleAr[$ele])
            || preg_match(
                 '`[^-._0-9a-z\xb7\xc0-\xd6\xd8-\xf6\xf8-\x{2ff}'
                   . '\x{370}-\x{37d}\x{37f}-\x{1fff}\x{200c}-\x{200d}\x{2070}-\x{218f}'
                   . '\x{2c00}-\x{2fef}\x{3001}-\x{d7ff}\x{f900}-\x{fdcf}\x{fdf0}-\x{fffd}\x{10000}-\x{effff}]`u'
                 , $ele)))
     ) {
    return (($C['keep_bad']%2) ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $t) : '');
  }

  // Attribute string.

  $attrStr = str_replace(array("\n", "\r", "\t"), ' ', trim($m[3]));

  // Transform deprecated element.

  static $deprecatedEleAr = array('acronym'=>1, 'applet'=>1, 'big'=>1, 'center'=>1, 'dir'=>1, 'font'=>1, 'isindex'=>1, 's'=>1, 'strike'=>1, 'tt'=>1);
  if ($C['make_tag_strict'] && isset($deprecatedEleAr[$ele])) {
    $eleTransformed = hl_deprecatedElement($ele, $attrStr, $C['make_tag_strict']); // hl_deprecatedElement uses referencing
    if (!$ele) {
      return (($C['keep_bad'] % 2) ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $t) : '');
    }
  }

  // Handle closing tag.

  static $emptyEleAr = array('area'=>1, 'br'=>1, 'col'=>1, 'command'=>1, 'embed'=>1, 'hr'=>1, 'img'=>1, 'input'=>1, 'isindex'=>1, 'keygen'=>1, 'link'=>1, 'meta'=>1, 'param'=>1, 'source'=>1, 'track'=>1, 'wbr'=>1);
  if (!empty($m[1])) {
    return(
      !isset($emptyEleAr[$ele])
      ? (empty($C['hook_tag'])
         ? "</$ele>"
         : call_user_func($C['hook_tag'], $ele, 0))
      : ($C['keep_bad'] % 2
         ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $t)
         : ''));
  }

  // Handle opening tag.

  // -- Sets of possible attributes.

  // .. Element-specific non-global.

  static $attrEleAr = array('abbr'=>array('td'=>1, 'th'=>1), 'accept'=>array('form'=>1, 'input'=>1), 'accept-charset'=>array('form'=>1), 'action'=>array('form'=>1), 'align'=>array('applet'=>1, 'caption'=>1, 'col'=>1, 'colgroup'=>1, 'div'=>1, 'embed'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'legend'=>1, 'object'=>1, 'p'=>1, 'table'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'allowfullscreen'=>array('iframe'=>1), 'alt'=>array('applet'=>1, 'area'=>1, 'img'=>1, 'input'=>1), 'archive'=>array('applet'=>1, 'object'=>1), 'async'=>array('script'=>1), 'autocomplete'=>array('form'=>1, 'input'=>1), 'autofocus'=>array('button'=>1, 'input'=>1, 'keygen'=>1, 'select'=>1, 'textarea'=>1), 'autoplay'=>array('audio'=>1, 'video'=>1), 'axis'=>array('td'=>1, 'th'=>1), 'bgcolor'=>array('embed'=>1, 'table'=>1, 'td'=>1, 'th'=>1, 'tr'=>1), 'border'=>array('img'=>1, 'object'=>1, 'table'=>1), 'bordercolor'=>array('table'=>1, 'td'=>1, 'tr'=>1), 'cellpadding'=>array('table'=>1), 'cellspacing'=>array('table'=>1), 'challenge'=>array('keygen'=>1), 'char'=>array('col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'charoff'=>array('col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'charset'=>array('a'=>1, 'script'=>1), 'checked'=>array('command'=>1, 'input'=>1), 'cite'=>array('blockquote'=>1, 'del'=>1, 'ins'=>1, 'q'=>1), 'classid'=>array('object'=>1), 'clear'=>array('br'=>1), 'code'=>array('applet'=>1), 'codebase'=>array('applet'=>1, 'object'=>1), 'codetype'=>array('object'=>1), 'color'=>array('font'=>1), 'cols'=>array('textarea'=>1), 'colspan'=>array('td'=>1, 'th'=>1), 'compact'=>array('dir'=>1, 'dl'=>1, 'menu'=>1, 'ol'=>1, 'ul'=>1), 'content'=>array('meta'=>1), 'controls'=>array('audio'=>1, 'video'=>1), 'coords'=>array('a'=>1, 'area'=>1), 'crossorigin'=>array('img'=>1), 'data'=>array('object'=>1), 'datetime'=>array('del'=>1, 'ins'=>1, 'time'=>1), 'declare'=>array('object'=>1), 'default'=>array('track'=>1), 'defer'=>array('script'=>1), 'dirname'=>array('input'=>1, 'textarea'=>1), 'disabled'=>array('button'=>1, 'command'=>1, 'fieldset'=>1, 'input'=>1, 'keygen'=>1, 'optgroup'=>1, 'option'=>1, 'select'=>1, 'textarea'=>1), 'download'=>array('a'=>1), 'enctype'=>array('form'=>1), 'face'=>array('font'=>1), 'flashvars'=>array('embed'=>1), 'for'=>array('label'=>1, 'output'=>1), 'form'=>array('button'=>1, 'fieldset'=>1, 'input'=>1, 'keygen'=>1, 'label'=>1, 'object'=>1, 'output'=>1, 'select'=>1, 'textarea'=>1), 'formaction'=>array('button'=>1, 'input'=>1), 'formenctype'=>array('button'=>1, 'input'=>1), 'formmethod'=>array('button'=>1, 'input'=>1), 'formnovalidate'=>array('button'=>1, 'input'=>1), 'formtarget'=>array('button'=>1, 'input'=>1), 'frame'=>array('table'=>1), 'frameborder'=>array('iframe'=>1), 'headers'=>array('td'=>1, 'th'=>1), 'height'=>array('applet'=>1, 'canvas'=>1, 'embed'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'object'=>1, 'td'=>1, 'th'=>1, 'video'=>1), 'high'=>array('meter'=>1), 'href'=>array('a'=>1, 'area'=>1, 'link'=>1), 'hreflang'=>array('a'=>1, 'area'=>1, 'link'=>1), 'hspace'=>array('applet'=>1, 'embed'=>1, 'img'=>1, 'object'=>1), 'icon'=>array('command'=>1), 'ismap'=>array('img'=>1, 'input'=>1), 'keyparams'=>array('keygen'=>1), 'keytype'=>array('keygen'=>1), 'kind'=>array('track'=>1), 'label'=>array('command'=>1, 'menu'=>1, 'option'=>1, 'optgroup'=>1, 'track'=>1), 'language'=>array('script'=>1), 'list'=>array('input'=>1), 'longdesc'=>array('img'=>1, 'iframe'=>1), 'loop'=>array('audio'=>1, 'video'=>1), 'low'=>array('meter'=>1), 'marginheight'=>array('iframe'=>1), 'marginwidth'=>array('iframe'=>1), 'max'=>array('input'=>1, 'meter'=>1, 'progress'=>1), 'maxlength'=>array('input'=>1, 'textarea'=>1), 'media'=>array('a'=>1, 'area'=>1, 'link'=>1, 'source'=>1, 'style'=>1), 'mediagroup'=>array('audio'=>1, 'video'=>1), 'method'=>array('form'=>1), 'min'=>array('input'=>1, 'meter'=>1), 'model'=>array('embed'=>1), 'multiple'=>array('input'=>1, 'select'=>1), 'muted'=>array('audio'=>1, 'video'=>1), 'name'=>array('a'=>1, 'applet'=>1, 'button'=>1, 'embed'=>1, 'fieldset'=>1, 'form'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'keygen'=>1, 'map'=>1, 'object'=>1, 'output'=>1, 'param'=>1, 'select'=>1, 'slot'=>1, 'textarea'=>1), 'nohref'=>array('area'=>1), 'noshade'=>array('hr'=>1), 'novalidate'=>array('form'=>1), 'nowrap'=>array('td'=>1, 'th'=>1), 'object'=>array('applet'=>1), 'open'=>array('details'=>1, 'dialog'=>1), 'optimum'=>array('meter'=>1), 'pattern'=>array('input'=>1), 'ping'=>array('a'=>1, 'area'=>1), 'placeholder'=>array('input'=>1, 'textarea'=>1), 'pluginspage'=>array('embed'=>1), 'pluginurl'=>array('embed'=>1), 'poster'=>array('video'=>1), 'pqg'=>array('keygen'=>1), 'preload'=>array('audio'=>1, 'video'=>1), 'prompt'=>array('isindex'=>1), 'pubdate'=>array('time'=>1), 'radiogroup'=>array('command'=>1), 'readonly'=>array('input'=>1, 'textarea'=>1), 'rel'=>array('a'=>1, 'area'=>1, 'link'=>1), 'required'=>array('input'=>1, 'select'=>1, 'textarea'=>1), 'rev'=>array('a'=>1), 'reversed'=>array('ol'=>1), 'rows'=>array('textarea'=>1), 'rowspan'=>array('td'=>1, 'th'=>1), 'rules'=>array('table'=>1), 'sandbox'=>array('iframe'=>1), 'scope'=>array('td'=>1, 'th'=>1), 'scoped'=>array('style'=>1), 'scrolling'=>array('iframe'=>1), 'seamless'=>array('iframe'=>1), 'selected'=>array('option'=>1), 'shape'=>array('a'=>1, 'area'=>1), 'size'=>array('font'=>1, 'hr'=>1, 'input'=>1, 'select'=>1), 'sizes'=>array('img'=>1, 'link'=>1, 'source'=>1), 'span'=>array('col'=>1, 'colgroup'=>1), 'src'=>array('audio'=>1, 'embed'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'script'=>1, 'source'=>1, 'track'=>1, 'video'=>1), 'srcdoc'=>array('iframe'=>1), 'srclang'=>array('track'=>1), 'srcset'=>array('img'=>1, 'link'=>1, 'source'=>1), 'standby'=>array('object'=>1), 'start'=>array('ol'=>1), 'step'=>array('input'=>1), 'summary'=>array('table'=>1), 'target'=>array('a'=>1, 'area'=>1, 'form'=>1), 'type'=>array('a'=>1, 'area'=>1, 'button'=>1, 'command'=>1, 'embed'=>1, 'input'=>1, 'li'=>1, 'link'=>1, 'menu'=>1, 'object'=>1, 'ol'=>1, 'param'=>1, 'script'=>1, 'source'=>1, 'style'=>1, 'ul'=>1), 'typemustmatch'=>array('object'=>1), 'usemap'=>array('img'=>1, 'input'=>1, 'object'=>1), 'valign'=>array('col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'value'=>array('button'=>1, 'data'=>1, 'input'=>1, 'li'=>1, 'meter'=>1, 'option'=>1, 'param'=>1, 'progress'=>1), 'valuetype'=>array('param'=>1), 'vspace'=>array('applet'=>1, 'embed'=>1, 'img'=>1, 'object'=>1), 'width'=>array('applet'=>1, 'canvas'=>1, 'col'=>1, 'colgroup'=>1, 'embed'=>1, 'hr'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'object'=>1, 'pre'=>1, 'table'=>1, 'td'=>1, 'th'=>1, 'video'=>1), 'wmode'=>array('embed'=>1), 'wrap'=>array('textarea'=>1));

  // .. Empty.

  static $emptyAttrAr = array('allowfullscreen'=>1, 'checkbox'=>1, 'checked'=>1, 'command'=>1, 'compact'=>1, 'declare'=>1, 'defer'=>1, 'default'=>1, 'disabled'=>1, 'hidden'=>1, 'inert'=>1, 'ismap'=>1, 'itemscope'=>1, 'multiple'=>1, 'nohref'=>1, 'noresize'=>1, 'noshade'=>1, 'nowrap'=>1, 'open'=>1, 'radio'=>1, 'readonly'=>1, 'required'=>1, 'reversed'=>1, 'selected'=>1);

  // .. Global.

  static $globalAttrAr = array(

     // .... General.

    'accesskey'=>1, 'autocapitalize'=>1, 'autofocus'=>1, 'class'=>1, 'contenteditable'=>1, 'contextmenu'=>1, 'dir'=>1, 'draggable'=>1, 'dropzone'=>1, 'enterkeyhint'=>1, 'hidden'=>1, 'id'=>1, 'inert'=>1, 'inputmode'=>1, 'is'=>1, 'itemid'=>1, 'itemprop'=>1, 'itemref'=>1, 'itemscope'=>1, 'itemtype'=>1, 'lang'=>1, 'nonce'=>1, 'role'=>1, 'slot'=>1, 'spellcheck'=>1, 'style'=>1, 'tabindex'=>1, 'title'=>1, 'translate'=>1, 'xmlns'=>1, 'xml:base'=>1, 'xml:lang'=>1, 'xml:space'=>1,

    // .... Event.

    'onabort'=>1, 'onauxclick'=>1, 'onblur'=>1, 'oncancel'=>1, 'oncanplay'=>1, 'oncanplaythrough'=>1, 'onchange'=>1, 'onclick'=>1, 'onclose'=>1, 'oncontextlost'=>1, 'oncontextmenu'=>1, 'oncontextrestored'=>1, 'oncopy'=>1, 'oncuechange'=>1, 'oncut'=>1, 'ondblclick'=>1, 'ondrag'=>1, 'ondragend'=>1, 'ondragenter'=>1, 'ondragleave'=>1, 'ondragover'=>1, 'ondragstart'=>1, 'ondrop'=>1, 'ondurationchange'=>1, 'onemptied'=>1, 'onended'=>1, 'onerror'=>1, 'onfocus'=>1, 'onformchange'=>1, 'onformdata'=>1, 'onforminput'=>1, 'ongotpointercapture'=>1, 'oninput'=>1, 'oninvalid'=>1, 'onkeydown'=>1, 'onkeypress'=>1, 'onkeyup'=>1, 'onload'=>1, 'onloadeddata'=>1, 'onloadedmetadata'=>1, 'onloadend'=>1, 'onloadstart'=>1, 'onlostpointercapture'=>1, 'onmousedown'=>1, 'onmouseenter'=>1, 'onmouseleave'=>1, 'onmousemove'=>1, 'onmouseout'=>1, 'onmouseover'=>1, 'onmouseup'=>1, 'onmousewheel'=>1, 'onpaste'=>1, 'onpause'=>1, 'onplay'=>1, 'onplaying'=>1, 'onpointercancel'=>1, 'onpointerdown'=>1, 'onpointerenter'=>1, 'onpointerleave'=>1, 'onpointermove'=>1, 'onpointerout'=>1, 'onpointerover'=>1, 'onpointerup'=>1, 'onprogress'=>1, 'onratechange'=>1, 'onreadystatechange'=>1, 'onreset'=>1, 'onresize'=>1, 'onscroll'=>1, 'onsearch'=>1, 'onsecuritypolicyviolation'=>1, 'onseeked'=>1, 'onseeking'=>1, 'onselect'=>1, 'onshow'=>1, 'onslotchange'=>1, 'onstalled'=>1, 'onsubmit'=>1, 'onsuspend'=>1, 'ontimeupdate'=>1, 'ontoggle'=>1, 'ontouchcancel'=>1, 'ontouchend'=>1, 'ontouchmove'=>1, 'ontouchstart'=>1, 'onvolumechange'=>1, 'onwaiting'=>1, 'onwheel'=>1,

    // .... Aria.

    'aria-activedescendant'=>1, 'aria-atomic'=>1, 'aria-autocomplete'=>1, 'aria-braillelabel'=>1, 'aria-brailleroledescription'=>1, 'aria-busy'=>1, 'aria-checked'=>1, 'aria-colcount'=>1, 'aria-colindex'=>1, 'aria-colindextext'=>1, 'aria-colspan'=>1, 'aria-controls'=>1, 'aria-current'=>1, 'aria-describedby'=>1, 'aria-description'=>1, 'aria-details'=>1, 'aria-disabled'=>1, 'aria-dropeffect'=>1, 'aria-errormessage'=>1, 'aria-expanded'=>1, 'aria-flowto'=>1, 'aria-grabbed'=>1, 'aria-haspopup'=>1, 'aria-hidden'=>1, 'aria-invalid'=>1, 'aria-keyshortcuts'=>1, 'aria-label'=>1, 'aria-labelledby'=>1, 'aria-level'=>1, 'aria-live'=>1, 'aria-multiline'=>1, 'aria-multiselectable'=>1, 'aria-orientation'=>1, 'aria-owns'=>1, 'aria-placeholder'=>1, 'aria-posinset'=>1, 'aria-pressed'=>1, 'aria-readonly'=>1, 'aria-relevant'=>1, 'aria-required'=>1, 'aria-roledescription'=>1, 'aria-rowcount'=>1, 'aria-rowindex'=>1, 'aria-rowindextext'=>1, 'aria-rowspan'=>1, 'aria-selected'=>1, 'aria-setsize'=>1, 'aria-sort'=>1, 'aria-valuemax'=>1, 'aria-valuemin'=>1, 'aria-valuenow'=>1, 'aria-valuetext'=>1);

  static $urlAttrAr = array('action'=>1, 'archive'=>1, 'cite'=>1, 'classid'=>1, 'codebase'=>1, 'data'=>1, 'href'=>1, 'itemtype'=>1, 'longdesc'=>1, 'model'=>1, 'pluginspage'=>1, 'pluginurl'=>1, 'poster'=>1, 'src'=>1, 'srcset'=>1, 'usemap'=>1); // Excludes style and on*

  // .. Deprecated.

  $alterDeprecAttr = 0;
  if ($C['no_deprecated_attr']) {
    static $deprecAttrEleAr = array('align'=>array('caption'=>1, 'div'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'img'=>1, 'input'=>1, 'legend'=>1, 'object'=>1, 'p'=>1, 'table'=>1), 'bgcolor'=>array('table'=>1, 'td'=>1, 'th'=>1, 'tr'=>1), 'border'=>array('object'=>1), 'bordercolor'=>array('table'=>1, 'td'=>1, 'tr'=>1), 'cellspacing'=>array('table'=>1), 'clear'=>array('br'=>1), 'compact'=>array('dl'=>1, 'ol'=>1, 'ul'=>1), 'height'=>array('td'=>1, 'th'=>1), 'hspace'=>array('img'=>1, 'object'=>1), 'language'=>array('script'=>1), 'name'=>array('a'=>1, 'form'=>1, 'iframe'=>1, 'img'=>1, 'map'=>1), 'noshade'=>array('hr'=>1), 'nowrap'=>array('td'=>1, 'th'=>1), 'size'=>array('hr'=>1), 'vspace'=>array('img'=>1, 'object'=>1), 'width'=>array('hr'=>1, 'pre'=>1, 'table'=>1, 'td'=>1, 'th'=>1));
    static $deprecAttrPossibleEleAr = array('a'=>1, 'br'=>1, 'caption'=>1, 'div'=>1, 'dl'=>1, 'form'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'legend'=>1, 'map'=>1, 'object'=>1, 'ol'=>1, 'p'=>1, 'pre'=>1, 'script'=>1, 'table'=>1, 'td'=>1, 'th'=>1, 'tr'=>1, 'ul'=>1);
    $alterDeprecAttr = isset($deprecAttrPossibleEleAr[$ele]) ? 1 : 0;
  }

  // -- Standard attribute values that may need lowercasing.

  if ($C['lc_std_val']) {
    static $lCaseStdAttrValAr = array('all'=>1, 'auto'=>1, 'baseline'=>1, 'bottom'=>1, 'button'=>1, 'captions'=>1, 'center'=>1, 'chapters'=>1, 'char'=>1, 'checkbox'=>1, 'circle'=>1, 'col'=>1, 'colgroup'=>1, 'color'=>1, 'cols'=>1, 'data'=>1, 'date'=>1, 'datetime'=>1, 'datetime-local'=>1, 'default'=>1, 'descriptions'=>1, 'email'=>1, 'file'=>1, 'get'=>1, 'groups'=>1, 'hidden'=>1, 'image'=>1, 'justify'=>1, 'left'=>1, 'ltr'=>1, 'metadata'=>1, 'middle'=>1, 'month'=>1, 'none'=>1, 'number'=>1, 'object'=>1, 'password'=>1, 'poly'=>1, 'post'=>1, 'preserve'=>1, 'radio'=>1, 'range'=>1, 'rect'=>1, 'ref'=>1, 'reset'=>1, 'right'=>1, 'row'=>1, 'rowgroup'=>1, 'rows'=>1, 'rtl'=>1, 'search'=>1, 'submit'=>1, 'subtitles'=>1, 'tel'=>1, 'text'=>1, 'time'=>1, 'top'=>1, 'url'=>1, 'week'=>1);
    static $lCaseStdAttrValPossibleEleAr = array('a'=>1, 'area'=>1, 'bdo'=>1, 'button'=>1, 'col'=>1, 'fieldset'=>1, 'form'=>1, 'img'=>1, 'input'=>1, 'object'=>1, 'ol'=>1, 'optgroup'=>1, 'option'=>1, 'param'=>1, 'script'=>1, 'select'=>1, 'table'=>1, 'td'=>1, 'textarea'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1, 'track'=>1, 'xml:space'=>1);
    $lCaseStdAttrVal = isset($lCaseStdAttrValPossibleEleAr[$ele]) ? 1 : 0;
  }

  // -- Get attribute name-value pairs.

  if (strpos($attrStr, "\x01") !== false) { // Remove CDATA/comment
    $attrStr = preg_replace('`\x01[^\x01]*\x01`', '', $attrStr);
  }
  $attrStr = trim($attrStr, ' /');
  $attrAr = array();
  $state = 0;
  while (strlen($attrStr)) {
    $ok = 0; // For parsing errors, to deal with space, ", and ' characters
    switch ($state) {
      case 0: if (preg_match('`^[^=\s/\x7f-\x9f]+`', $attrStr, $m)) { // Name
        $attr = strtolower($m[0]);
        $ok = $state = 1;
        $attrStr = ltrim(substr_replace($attrStr, '', 0, strlen($m[0])));
      }
      break; case 1: if ($attrStr[0] == '=') {
        $ok = 1;
        $state = 2;
        $attrStr = ltrim($attrStr, '= ');
      } else { // No value
        $ok = 1;
        $state = 0;
        $attrStr = ltrim($attrStr);
        $attrAr[$attr] = '';
      }
      break; case 2: if (preg_match('`^((?:"[^"]*")|(?:\'[^\']*\')|(?:\s*[^\s"\']+))(.*)`', $attrStr, $m)) { // Value
        $attrStr = ltrim($m[2]);
        $m = $m[1];
        $ok = 1;
        $state = 0;
        $attrAr[$attr] =
          trim(
            str_replace('<', '&lt;',
              ($m[0] == '"' || $m[0] == '\'')
              ? substr($m, 1, -1)
              : $m));
      }
      break;
    }
    if (!$ok) {
      $attrStr = preg_replace('`^(?:"[^"]*("|$)|\'[^\']*(\'|$)|\S)*\s*`', '', $attrStr);
      $state = 0;
    }
  }
  if ($state == 1) {
    $attrAr[$attr] = '';
  }

  // -- Clean attributes.

  global $S;
  $eleSpec = isset($S[$ele]) ? $S[$ele] : array();
  $filtAttrAr = array(); // Finalized attributes
  $deniedAttrAr = $C['deny_attribute'];

  foreach ($attrAr as $attr=>$v) {

    // .. Check if attribute is permitted.

    if (

       // .... Valid attribute.

      ((isset($attrEleAr[$attr][$ele])
        || isset($globalAttrAr[$attr])
        || preg_match('`data-((?!xml)[^:]+$)`', $attr)
        || (strpos($ele, '-')
            && strpos($attr, 'data-xml') !== 0))

       // .... No denial through $spec.

       && (empty($eleSpec)
           || (!isset($eleSpec['deny'])
               || (!isset($eleSpec['deny']['*'])
                   && !isset($eleSpec['deny'][$attr])
                   && !isset($eleSpec['deny'][preg_replace('`^(on|aria|data).+`', '\\1', $attr). '*']))))

       // .... No denial through $config.

       && (empty($deniedAttrAr)
           || (isset($deniedAttrAr['*'])
               ? (isset($deniedAttrAr["-$attr"])
                  || isset($deniedAttrAr['-'. preg_replace('`^(on|aria|data)..+`', '\\1', $attr). '*']))
               : (!isset($deniedAttrAr[$attr])
                  && !isset($deniedAttrAr[preg_replace('`^(on|aria|data).+`', '\\1', $attr). '*'])))))

      // .... Permit if permission through $spec.

      || (!empty($eleSpec)
          && (isset($eleSpec[$attr])
              || (isset($globalAttrAr[$attr])
                  && isset($eleSpec[preg_replace('`^(on|aria|data).+`', '\\1', $attr). '*']))))
      ) {

      // .. Attribute with no value or standard value.

      if (isset($emptyAttrAr[$attr])) {
        $v = $attr;
      } elseif (
        !empty($lCaseStdAttrVal)  // ! Rather loose but should be ok
         && (($ele != 'button' || $ele != 'input')
             || $attr == 'type')
        ) {
        $v = (isset($lCaseStdAttrValAr[($vNew = strtolower($v))])) ? $vNew : $v;
      }

      // .. URLs and CSS expressions in style attribute.

      if ($attr == 'style' && !$C['style_pass']) {
        if (false !== strpos($v, '&#')) { // Change any entity to character
          static $entityAr = array('&#32;'=>' ', '&#x20;'=>' ', '&#58;'=>':', '&#x3a;'=>':', '&#34;'=>'"', '&#x22;'=>'"', '&#40;'=>'(', '&#x28;'=>'(', '&#41;'=>')', '&#x29;'=>')', '&#42;'=>'*', '&#x2a;'=>'*', '&#47;'=>'/', '&#x2f;'=>'/', '&#92;'=>'\\', '&#x5c;'=>'\\', '&#101;'=>'e', '&#69;'=>'e', '&#x45;'=>'e', '&#x65;'=>'e', '&#105;'=>'i', '&#73;'=>'i', '&#x49;'=>'i', '&#x69;'=>'i', '&#108;'=>'l', '&#76;'=>'l', '&#x4c;'=>'l', '&#x6c;'=>'l', '&#110;'=>'n', '&#78;'=>'n', '&#x4e;'=>'n', '&#x6e;'=>'n', '&#111;'=>'o', '&#79;'=>'o', '&#x4f;'=>'o', '&#x6f;'=>'o', '&#112;'=>'p', '&#80;'=>'p', '&#x50;'=>'p', '&#x70;'=>'p', '&#114;'=>'r', '&#82;'=>'r', '&#x52;'=>'r', '&#x72;'=>'r', '&#115;'=>'s', '&#83;'=>'s', '&#x53;'=>'s', '&#x73;'=>'s', '&#117;'=>'u', '&#85;'=>'u', '&#x55;'=>'u', '&#x75;'=>'u', '&#120;'=>'x', '&#88;'=>'x', '&#x58;'=>'x', '&#x78;'=>'x', '&#39;'=>"'", '&#x27;'=>"'");
          $v = strtr($v, $entityAr);
        }
        $v =
          preg_replace_callback(
            '`(url(?:\()(?: )*(?:\'|"|&(?:quot|apos);)?)(.+?)((?:\'|"|&(?:quot|apos);)?(?: )*(?:\)))`iS',
            'hl_url',
            $v);
        $v = !$C['css_expression']
             ? preg_replace('`expression`i', ' ', preg_replace('`\\\\\S|(/|(%2f))(\*|(%2a))`i', ' ', $v))
             : $v;

      // .. URLs in other attributes.

      } elseif (isset($urlAttrAr[$attr]) || (isset($globalAttrAr[$attr]) && strpos($attr, 'on') === 0)) {
        $v =
          str_replace("­", ' ',
            (strpos($v, '&') !== false  // ! Double-quoted character = soft-hyphen
             ? str_replace(array('&#xad;', '&#173;', '&shy;'), ' ', $v)
             : $v));
        if ($attr == 'srcset' || ($attr == 'archive' && $ele == 'applet')) {
          $vNew = '';
          foreach (explode(',', $v) as $k=>$x) {
            $x = explode(' ', ltrim($x), 2);
            $k = isset($x[1]) ? trim($x[1]) : '';
            $x = trim($x[0]);
            if (isset($x[0])) {
              $vNew .= hl_url($x, $attr). (empty($k) ? '' : ' '. $k). ', ';
            }
          }
          $v = trim($vNew, ', ');
        }
        if ($attr == 'itemtype' || ($attr == 'archive' && $ele == 'object')) {
          $vNew = '';
          foreach (explode(' ', $v) as $x) {
            if (isset($x[0])) {
              $vNew .= hl_url($x, $attr). ' ';
            }
          }
          $v = trim($vNew, ' ');
        } else {
          $v = hl_url($v, $attr);
        }

        // Anti-spam measure.

        if ($attr == 'href') {
          if ($C['anti_mail_spam'] && strpos($v, 'mailto:') === 0) {
            $v = str_replace('@', htmlspecialchars($C['anti_mail_spam']), $v);
          } elseif ($C['anti_link_spam']) {
            $x = $C['anti_link_spam'][1];
            if (!empty($x) && preg_match($x, $v)) {
              continue;
            }
            $x = $C['anti_link_spam'][0];
            if (!empty($x) && preg_match($x, $v)) {
              if (isset($filtAttrAr['rel'])) {
                if (!preg_match('`\bnofollow\b`i', $filtAttrAr['rel'])) {
                  $filtAttrAr['rel'] .= ' nofollow';
                }
              } elseif (isset($attrAr['rel'])) {
                if (!preg_match('`\bnofollow\b`i', $attrAr['rel'])) {
                  $addNofollow = 1;
                }
              } else {
                $filtAttrAr['rel'] = 'nofollow';
              }
            }
          }
        }
      }

      // .. Check attribute value against any $spec rule.

      if (isset($eleSpec[$attr])
          && is_array($eleSpec[$attr])
          && ($v = hl_attributeValue($attr, $v, $eleSpec[$attr], $ele)) === 0) {
        continue;
      }

      $filtAttrAr[$attr] = str_replace('"', '&quot;', $v);
    }
  }

  // -- Add nofollow.

  if (isset($addNofollow)) {
    $filtAttrAr['rel'] = isset($filtAttrAr['rel']) ? $filtAttrAr['rel']. ' nofollow' : 'nofollow';
  }

  // -- Add required attributes.

  static $requiredAttrAr = array('area'=>array('alt'=>'area'), 'bdo'=>array('dir'=>'ltr'), 'command'=>array('label'=>''), 'form'=>array('action'=>''), 'img'=>array('src'=>'', 'alt'=>'image'), 'map'=>array('name'=>''), 'optgroup'=>array('label'=>''), 'param'=>array('name'=>''), 'style'=>array('scoped'=>''), 'textarea'=>array('rows'=>'10', 'cols'=>'50'));
  if (isset($requiredAttrAr[$ele])) {
    foreach ($requiredAttrAr[$ele] as $k=>$v) {
      if (!isset($filtAttrAr[$k])) {
        $filtAttrAr[$k] = isset($v[0]) ? $v : $k;
      }
    }
  }

  // -- Transform deprecated attributes into CSS declarations in style attribute.

  if ($alterDeprecAttr) {
    $css = array();
    foreach ($filtAttrAr as $name=>$val) {
      if ($name == 'style' || !isset($deprecAttrEleAr[$name][$ele])) {
        continue;
      }
      $val = str_replace(array('\\', ':', ';', '&#'), '', $val);
      if ($name == 'align') {
        unset($filtAttrAr['align']);
        if ($ele == 'img' && ($val == 'left' || $val == 'right')) {
          $css[] = 'float: '. $val;
        } elseif (($ele == 'div' || $ele == 'table') && $val == 'center') {
          $css[] = 'margin: auto';
        } else {
          $css[] = 'text-align: '. $val;
        }
      } elseif ($name == 'bgcolor') {
        unset($filtAttrAr['bgcolor']);
        $css[] = 'background-color: '. $val;
      } elseif ($name == 'border') {
        unset($filtAttrAr['border']);
        $css[] = "border: {$val}px";
      } elseif ($name == 'bordercolor') {
        unset($filtAttrAr['bordercolor']);
        $css[] = 'border-color: '. $val;
      } elseif ($name == 'cellspacing') {
        unset($filtAttrAr['cellspacing']);
        $css[] = "border-spacing: {$val}px";
      } elseif ($name == 'clear') {
        unset($filtAttrAr['clear']);
        $css[] = 'clear: '. ($val != 'all' ? $val : 'both');
      } elseif ($name == 'compact') {
        unset($filtAttrAr['compact']);
        $css[] = 'font-size: 85%';
      } elseif ($name == 'height' || $name == 'width') {
        unset($filtAttrAr[$name]);
        $css[] =
          $name
          . ': '
          . ((isset($val[0]) && $val[0] != '*')
             ? $val. (ctype_digit($val) ? 'px' : '')
             : 'auto');
      } elseif ($name == 'hspace') {
        unset($filtAttrAr['hspace']);
        $css[] = "margin-left: {$val}px; margin-right: {$val}px";
      } elseif ($name == 'language' && !isset($filtAttrAr['type'])) {
        unset($filtAttrAr['language']);
        $filtAttrAr['type'] = 'text/'. strtolower($val);
      } elseif ($name == 'name') {
        if ($C['no_deprecated_attr'] == 2 || ($ele != 'a' && $ele != 'map')) {
          unset($filtAttrAr['name']);
        }
        if (!isset($filtAttrAr['id']) && !preg_match('`\W`', $val)) {
          $filtAttrAr['id'] = $val;
        }
      } elseif ($name == 'noshade') {
        unset($filtAttrAr['noshade']);
        $css[] = 'border-style: none; border: 0; background-color: gray; color: gray';
      } elseif ($name == 'nowrap') {
        unset($filtAttrAr['nowrap']);
        $css[] = 'white-space: nowrap';
      } elseif ($name == 'size') {
        unset($filtAttrAr['size']);
        $css[] = 'size: '. $val. 'px';
      } elseif ($name == 'vspace') {
        unset($filtAttrAr['vspace']);
        $css[] = "margin-top: {$val}px; margin-bottom: {$val}px";
      }
    }
    if (count($css)) {
      $css = implode('; ', $css);
      $filtAttrAr['style'] =
        isset($filtAttrAr['style'])
        ? rtrim($filtAttrAr['style'], ' ;'). '; '. $css. ';'
        : $css. ';';
    }
  }

  // -- Enforce unique id attribute values.

  if ($C['unique_ids'] && isset($filtAttrAr['id'])) {
    if (preg_match('`\s`', ($id = $filtAttrAr['id'])) || (isset($GLOBALS['hl_Ids'][$id]) && $C['unique_ids'] == 1)) {
      unset($filtAttrAr['id']);
    } else {
      while (isset($GLOBALS['hl_Ids'][$id])) {
        $id = $C['unique_ids']. $id;
      }
      $GLOBALS['hl_Ids'][($filtAttrAr['id'] = $id)] = 1;
    }
  }

  // -- Handle lang attributes.

  if ($C['xml:lang'] && isset($filtAttrAr['lang'])) {
    $filtAttrAr['xml:lang'] = isset($filtAttrAr['xml:lang']) ? $filtAttrAr['xml:lang'] : $filtAttrAr['lang'];
    if ($C['xml:lang'] == 2) {
      unset($filtAttrAr['lang']);
    }
  }

  // -- If transformed element, modify style attribute.

  if (!empty($eleTransformed)) {
    $filtAttrAr['style'] =
      isset($filtAttrAr['style'])
      ? rtrim($filtAttrAr['style'], ' ;'). '; '. $eleTransformed
      : $eleTransformed;
  }

  // -- Return opening tag with attributes.

  if (empty($C['hook_tag'])) {
    $attrStr = '';
    foreach ($filtAttrAr as $k=>$v) {
      $attrStr .= " {$k}=\"{$v}\"";
    }
    return "<{$ele}{$attrStr}". (isset($emptyEleAr[$ele]) ? ' /' : ''). '>';
  } else {
    return call_user_func($C['hook_tag'], $ele, $filtAttrAr);
  }
}

/**
 * Tidy/beautify HTM by adding newline and other spaces (padding),
 * or compact by removing unnecessary spaces.
 *
 * @param  string $t         HTM.
 * @param  mixed  $format    -1 (compact) or string (type of padding).
 * @param  string $parentEle Parent element of $t.
 * @return mixed             Transformed attribute string (may be empty) or 0.
 */
function hl_tidy($t, $format, $parentEle)
{
  if (strpos(' pre,script,textarea', "$parentEle,")) {
    return $t;
  }

  // Hide CDATA/comment.

  if (!function_exists('hl_aux2')) {
    function hl_aux2($x) {
      return
        $x[1]
        . str_replace(
            array("<", ">", "\n", "\r", "\t", ' '),
            array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"),
            $x[3])
        . $x[4];
    }
  }
  $t =
    preg_replace(
      array('`(<\w[^>]*(?<!/)>)\s+`', '`\s+`', '`(<\w[^>]*(?<!/)>) `'),
      array(' $1', ' ', '$1'),
      preg_replace_callback(
        array('`(<(!\[CDATA\[))(.+?)(\]\]>)`sm', '`(<(!--))(.+?)(-->)`sm', '`(<(pre|script|textarea)[^>]*?>)(.+?)(</\2>)`sm'),
        'hl_aux2',
        $t));

  if (($format = strtolower($format)) == -1) {
    return
      str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"), array('<', '>', "\n", "\r", "\t", ' '), $t);
  }
  $padChar = strpos(" $format", 't') ? "\t" : ' ';
  $padStr =
    preg_match('`\d`', $format, $m)
    ? str_repeat($padChar, intval($m[0]))
    : str_repeat($padChar, ($padChar == "\t" ? 1 : 2));
  $leadN = preg_match('`[ts]([1-9])`', $format, $m) ? intval($m[1]) : 0;

  // Group elements by line-break requirement.

  $postCloseEleAr = array('br'=>1); // After closing
  $preEleAr = array('button'=>1, 'command'=>1, 'input'=>1, 'option'=>1, 'param'=>1, 'track'=>1); // Before opening or closing
  $preOpenPostCloseEleAr = array('audio'=>1, 'canvas'=>1, 'caption'=>1, 'dd'=>1, 'dt'=>1, 'figcaption'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'isindex'=>1, 'label'=>1, 'legend'=>1, 'li'=>1, 'object'=>1, 'p'=>1, 'pre'=>1, 'style'=>1, 'summary'=>1, 'td'=>1, 'textarea'=>1, 'th'=>1, 'video'=>1); // Before opening and after closing
  $prePostEleAr = array('address'=>1, 'article'=>1, 'aside'=>1, 'blockquote'=>1, 'center'=>1, 'colgroup'=>1, 'datalist'=>1, 'details'=>1, 'dialog'=>1, 'dir'=>1, 'div'=>1, 'dl'=>1, 'fieldset'=>1, 'figure'=>1, 'footer'=>1, 'form'=>1, 'header'=>1, 'hgroup'=>1, 'hr'=>1, 'iframe'=>1, 'main'=>1, 'map'=>1, 'menu'=>1, 'nav'=>1, 'noscript'=>1, 'ol'=>1, 'optgroup'=>1, 'picture'=>1, 'rbc'=>1, 'rtc'=>1, 'ruby'=>1, 'script'=>1, 'section'=>1, 'select'=>1, 'table'=>1, 'tbody'=>1, 'template'=>1, 'tfoot'=>1, 'thead'=>1, 'tr'=>1, 'ul'=>1); // Before and after opening and closing

  $doPad = 1;
  $t = explode('<', $t);
  while ($doPad) {
    $n = $leadN;
    $eleAr = $t;
    ob_start();
    if (isset($prePostEleAr[$parentEle])) {
      echo str_repeat($padStr, ++$n);
    }
    echo ltrim(array_shift($eleAr));
    for ($i=-1, $j=count($eleAr); ++$i<$j;) {
      $rest = '';
      list($tag, $rest) = explode('>', $eleAr[$i]);
      $open = $tag[0] == '/' ? 0 : (substr($tag, -1) == '/' ? 1 : ($tag[0] != '!' ? 2 : -1));
      $ele = !$open ? ltrim($tag, '/') : ($open > 0 ? substr($tag, 0, strcspn($tag, ' ')) : 0);
      $tag = "<$tag>";
      if (isset($prePostEleAr[$ele])) {
        if (!$open) {
          if ($n) {
            echo "\n", str_repeat($padStr, --$n), "$tag\n", str_repeat($padStr, $n);
          } else {
            ++$leadN;
            ob_end_clean();
            continue 2;
          }
        } else {
          echo "\n", str_repeat($padStr, $n), "$tag\n", str_repeat($padStr, ($open != 1 ? ++$n : $n));
        }
        echo $rest;
        continue;
      }
      $pad = "\n". str_repeat($padStr, $n);
      if (isset($preOpenPostCloseEleAr[$ele])) {
        if (!$open) {
          echo $tag, $pad, $rest;
        } else {
          echo $pad, $tag, $rest;
        }
      } elseif (isset($preEleAr[$ele])) {
        echo $pad, $tag, $rest;
      } elseif (isset($postCloseEleAr[$ele])) {
        echo $tag, $pad, $rest;
      } elseif (!$ele) {
        echo $pad, $tag, $pad, $rest;
      } else {
        echo $tag, $rest;
      }
    }
    $doPad = 0;
  }
  $t = str_replace(array("\n ", " \n"), "\n", preg_replace('`[\n]\s*?[\n]+`', "\n", ob_get_contents()));
  ob_end_clean();
  if (($newline = strpos(" $format", 'r') ? (strpos(" $format", 'n') ? "\r\n" : "\r") : 0)) {
    $t = str_replace("\n", $newline, $t);
  }
  return str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"), array('<', '>', "\n", "\r", "\t", ' '), $t);
}

/**
 * Handle URL to convert to relative/absolute type,
 * block scheme, or add anti-spam text.
 *
 * @param  mixed  $url  URL string, or array with URL value (if $attr is null).
 * @param  mixed  $attr Attribute name string, or null (if $url is array).
 * @return string       With URL after any conversion/obfuscation.
 */
function hl_url($url, $attr=null)
{
  global $C;
  $preUrl = $postUrl = '';
  static $blocker = 'denied:';
  if ($attr == null) { // style attribute value
    $attr = 'style';
    $preUrl = $url[1];
    $postUrl = $url[3];
    $url = trim($url[2]);
  }
  $okSchemeAr = isset($C['schemes'][$attr]) ? $C['schemes'][$attr] : $C['schemes']['*'];
  if (isset($okSchemeAr['!']) && substr($url, 0, 7) != $blocker) {
    $url = "{$blocker}{$url}";
  }
  if (isset($okSchemeAr['*'])
      || !strcspn($url, '#?;')
      || substr($url, 0, strlen($blocker)) == $blocker
    ) {
    return "{$preUrl}{$url}{$postUrl}";
  }
  if (preg_match('`^([^:?[@!$()*,=/\'\]]+?)(:|&(#(58|x3a)|colon);|%3a|\\\\0{0,4}3a).`i', $url, $m)
      && !isset($okSchemeAr[strtolower($m[1])]) // Special crafting suggests malice
    ) {
    return "{$preUrl}{$blocker}{$url}{$postUrl}";
  }
  if ($C['abs_url']) {
    if ($C['abs_url'] == -1 && strpos($url, $C['base_url']) === 0) { // Make URL relative
      $url = substr($url, strlen($C['base_url']));
    } elseif (empty($m[1])) { // Make URL absolute
      if (substr($url, 0, 2) == '//') {
        $url = substr($C['base_url'], 0, strpos($C['base_url'], ':') + 1). $url;
      } elseif ($url[0] == '/') {
        $url = preg_replace('`(^.+?://[^/]+)(.*)`', '$1', $C['base_url']). $url;
      } elseif (strcspn($url, './')) {
        $url = $C['base_url']. $url;
      } else {
        preg_match('`^([a-zA-Z\d\-+.]+://[^/]+)(.*)`', $C['base_url'], $m);
        $url = preg_replace('`(?<=/)\./`', '', $m[2]. $url);
        while (preg_match('`(?<=/)([^/]{3,}|[^/.]+?|\.[^/.]|[^/.]\.)/\.\./`', $url)) {
          $url = preg_replace('`(?<=/)([^/]{3,}|[^/.]+?|\.[^/.]|[^/.]\.)/\.\./`', '', $url);
        }
        $url = $m[1]. $url;
      }
    }
  }
  return "{$preUrl}{$url}{$postUrl}";
}

/**
 * Report version.
 *
 * @return string Version.
 */
function hl_version()
{
  return '1.2.14';
}

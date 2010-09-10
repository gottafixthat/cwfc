<?php
/**
 * xmltools.php - A collection of XML tools.
 *
 **************************************************************************
 * Written by R. Marc Lewis, 
 *   Copyright 1998-2010, R. Marc Lewis (marc@CheetahIS.com)
 *   Copyright 2007-2010, Cheetah Information Systems Inc.
 *
 * Portions written by dudus@onet.pl
 *
 **************************************************************************
 *
 * This file is part of cwfc.
 *
 * cwfc is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * cwfc is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with cwfc.  If not, see <http://www.gnu.org/licenses/>.
 */


/**
  * Convert an XML document into a structure.
  *
  * Given a properly formed XML document, this function will convert
  * it into a multidimensional array.
  *
  * @author     dudus@onet.pl
  * @url        http://www.php.net/manual/en/function.xml-parse-into-struct.php
  */
function XMLtoArray($XML, $lowerKeys = false)
{
   $xml_parser = xml_parser_create();
   xml_parse_into_struct($xml_parser, $XML, $vals);
   xml_parser_free($xml_parser);
   // wyznaczamy tablice z powtarzajacymi sie tagami na tym samym poziomie
   $_tmp='';
   foreach ($vals as $xml_elem)
   {
       $x_tag=$xml_elem['tag'];
       $x_level=$xml_elem['level'];
       $x_type=$xml_elem['type'];
       if ($x_level!=1 && $x_type == 'close')
       {
           if (isset($multi_key[$x_tag][$x_level]))
               $multi_key[$x_tag][$x_level]=1;
           else
               $multi_key[$x_tag][$x_level]=0;
       }
       if ($x_level!=1 && $x_type == 'complete')
       {
           if ($_tmp==$x_tag)
               $multi_key[$x_tag][$x_level]=1;
           $_tmp=$x_tag;
       }
   }
   // jedziemy po tablicy
   foreach ($vals as $xml_elem)
   {
       $x_tag=$xml_elem['tag'];
       $x_level=$xml_elem['level'];
       $x_type=$xml_elem['type'];
       if ($x_type == 'open')
           $level[$x_level] = $x_tag;
       $start_level = 1;
       $php_stmt = '$xml_array';
       if ($x_type=='close' && $x_level!=1)
           $multi_key[$x_tag][$x_level]++;
       while($start_level < $x_level)
       {
             $php_stmt .= '[$level['.$start_level.']]';
             if (isset($multi_key[$level[$start_level]][$start_level]) && $multi_key[$level[$start_level]][$start_level])
                 $php_stmt .= '['.($multi_key[$level[$start_level]][$start_level]-1).']';
             $start_level++;
       }
       $add='';
       if (isset($multi_key[$x_tag][$x_level]) && $multi_key[$x_tag][$x_level] && ($x_type=='open' || $x_type=='complete'))
       {
           if (!isset($multi_key2[$x_tag][$x_level]))
               $multi_key2[$x_tag][$x_level]=0;
           else
               $multi_key2[$x_tag][$x_level]++;
             $add='['.$multi_key2[$x_tag][$x_level].']';
       }
       if (isset($xml_elem['value']) && trim($xml_elem['value'])!='' && !array_key_exists('attributes',$xml_elem))
       {
           if ($x_type == 'open')
               $php_stmt_main=$php_stmt.'[$x_type]'.$add.'[\'content\'] = $xml_elem[\'value\'];';
           else
               $php_stmt_main=$php_stmt.'[$x_tag]'.$add.' = $xml_elem[\'value\'];';
           eval($php_stmt_main);
       }
       if (array_key_exists('attributes',$xml_elem))
       {
           if (isset($xml_elem['value']))
           {
               $php_stmt_main=$php_stmt.'[$x_tag]'.$add.'[\'content\'] = $xml_elem[\'value\'];';
               eval($php_stmt_main);
           }
           foreach ($xml_elem['attributes'] as $key=>$value)
           {
               $php_stmt_att=$php_stmt.'[$x_tag]'.$add.'[$key] = $value;';
               eval($php_stmt_att);
           }
       }
   }
   if ($lowerKeys) $xml_array = keystolower($xml_array);
   return $xml_array;
}    // END XMLtoArray

function &last(&$array) {
if (!count($array)) return null;
end($array);
return $array[key($array)];
}

/**
 * myParseXML()
 *
 * Turns an XML entity into an array.
 * if $doAttr == true, then attributes will be added
 * as tags.  Not the best, but great for my purpose
 * and maintains compatibility.
 */
function myParseXML(&$vals, &$dom, &$lev, $doAttr = false) {
   do {
       $curr = current($vals);
       $lev = $curr['level'];
       switch ($curr['type']) {
           case 'open':
               if (isset($dom[$curr['tag']])) {
                   $tmp = $dom[$curr['tag']];
                   if (!isset($tmp['__multi']) || !$tmp['__multi'])
                       $dom[$curr['tag']] = array('__multi' => true, $tmp);
                   array_push($dom[$curr['tag']], array());
                   $new =& last($dom[$curr['tag']]);
               } else {
                   $dom[$curr['tag']] = array();
                   $new =& $dom[$curr['tag']];
               }
               if ($doAttr && isset($curr['attributes'])) {
                   foreach($curr['attributes'] as $k => $v) $new[$k] = $v;
               }
               next($vals);
               myParseXML($vals, $new, $lev, $doAttr);
               break;
           case 'cdata':
               break;
           case 'complete':
               if (!isset($dom[$curr['tag']]))
                   if (isset($curr['value'])) {
                       $dom[$curr['tag']] = $curr['value'];
                   } else {
                       //error_log("No value for tag '" . $curr['tag'] . "'");
                       $dom[$curr['tag']] = "";
                   }
               else {
                   //if (!isset($curr['attributes'])) $curr['attributes'] = array();
                   if (is_array($dom[$curr['tag']]))
                       array_push($dom[$curr['tag']] , $curr['value']);
                   else
                       if (!empty($curr['attributes'])) {
                           array_push($dom[$curr['tag']] = array($dom[$curr['tag']]) , $curr['value'], $curr['attributes']);
                       } else {
                           array_push($dom[$curr['tag']] = array($dom[$curr['tag']]) , $curr['value']);
                       }
               }
               if ($doAttr && isset($curr['attributes'])) {
                   foreach($curr['attributes'] as $k => $v) {
                       $dom[$k] = $v;
                   }
               }
               break;
           case 'close':
               return;
       }
   }
   while (next($vals)!==FALSE);
}

function keystolower($src)
{
    $src = array_change_key_case($src, CASE_LOWER);
    foreach($src as $key => $val) {
        if (is_array($val)) {
            //array_push($retVal, strtolower($key) => keystolower($val));
            $src[$key] = keystolower($val);
            //array_push($retVal, array($lowKey => keystolower($val)));
            //$retVal["$lowKey"] == array(keystolower($val));
        }
    }
    return $src;
}

function myRemoveMulti($xml)
{
    $retVal = array();
    foreach($xml as $key => $val) {
        if (is_array($val)) {
            $retVal[$key] = myRemoveMulti($val);
        } else {
            if (strcmp($key, "__multi")) $retVal[$key] = $val;
        }
    }
    return $retVal;
}

function MyXMLtoArray($XML, $toLower = false, $doAttr = false) {
       $xml_parser = xml_parser_create();
       xml_parse_into_struct($xml_parser, $XML, $vals);
       xml_parser_free($xml_parser);
       reset($vals);
       $dom = array(); $lev = 0;
       myParseXML($vals, $dom, $lev, $doAttr);
       $dom = myRemoveMulti($dom);
       if ($toLower) $dom = keystolower($dom);
       return $dom;
}


?>

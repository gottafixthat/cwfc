<?
/**
 * blayout.php - Block Layout class.
 *
 * This class enables PHP applications to embed a layout system
 * into themselves.  It uses the Smarty template engine to completely
 * seperate code from HTML and gives applications programmers a very
 * easy way to create layouts that are controlled by the templates.
 *
 * THERE SHOULD BE NO HTML OUPUT FROM THIS CLASS EXCEPT
 * WHAT WAS DEFINED IN THE TEMPLATES OR STYLE SHEET.
 *
 **************************************************************************
 * Written by R. Marc Lewis, 
 *   Copyright 1998-2010, R. Marc Lewis (marc@CheetahIS.com)
 *   Copyright 2007-2010, Cheetah Information Systems Inc.
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


// This file can't be loaded directly
if (eregi('blayout.php', $_SERVER['PHP_SELF'])) {
    die ("Nope.  This file can't be loaded directly.  In fact, it shouldn't even be in the document root.");
}

// Load the Smarty template class
require_once("smarty/Smarty.class.php");

// The BLayout class
class BLayout {

var $defaultPageTemplate;   // The default template file to use
var $templatePath;
var $vhostTemplatePath;
var $siteName;
var $pageTitle;
var $extraHeaders;
var $defaultStyleSheet   = "style.css";
var $defaultPageBlock    = "PageBlock";
var $compressOutput      = 0;

// Private variables
var $globalVars;

/*!
* Contains ALL of the data blocks that are to be rendered.  It is an
* array containing data block arrays.  Blocks are added to using the
* \ref addBlock command.
*
* \param Weight Before the page gets rendered, the blocks array gets sorted
* by the weight.  This allows blocks to be added out of order and displayed
* correctly.
* \param Zone Determines what zone the block will be rendered in.
* \param Title The title of the block
* \param Footer The footer of the block
* \param TFile The template file to use when parsing
* \param ExtraVars An array of additional variables to include when parsing the
* block.  This allows for infinate header/footer/navigation possibilities.
* \param Data The actual content of the block.
*/

var $blocks;

// zoneTemplates defines what template files are used for each zone
var $zoneTemplates = array(
        "block_header.tpl",
        "block_info.tpl",
        "block_content.tpl",
        "block_extra.tpl",
        "block_footer.tpl",
        "block_sechead.tpl"
        );

// zoneNames defines the Smarty arrays that will be assigned for each zone.
var $zoneBlocks = array(
        "HeaderBlocks",
        "InfoBlocks",
        "ContentBlocks",
        "ExtraBlocks",
        "FooterBlocks",
        "SecHeadBlocks",
        );
var $defaultZone = 2;       // Content Block

// Replacements that are done after template parsing
// Used for non-parsable things.
var $blockMatches;
var $blockReplacements;

// Constructor
function BLayout()
{
    // Setup our defaults (that can be overidden later)
    $this->templatePath = dirname(__FILE__);
    if (substr($this->templatePath, strlen($this->templatePath)-1,1) != "/") $this->templatePath .= "/";

    // Set up our arrays
    $this->globalVars       = array();
    $this->pregMatches      = array();
    $this->pregReplacements = array();
    $this->blocks           = array();
    $this->siteName         = "";
    $this->pageTitle        = "";
    $this->extraHeaders     = "";
    $this->vhostTemplatePath = "";
}

/*
** getTemplatePath - Returns the current template path.
*/
function getTemplatePath()
{
    return $this->templatePath;
}

/*
** setTemplatePath - Defines the template path to use.
**                   Returns 1 on success, 0 on failure.
*/

function setTemplatePath($newPath)
{
    $retVal = 1;        // Assume success.
    if (is_dir($newPath)) {
        //if (!is_file($newPath)) {
            $this->templatePath = $newPath;
            if (substr($this->templatePath, strlen($this->templatePath)-1,1) != "/") $this->templatePath .= "/";
            //error_log(__FILE__ . " BLayout::setTemplatePath - Template path set to '" . $this->templatePath . "'");
        //} else $retVal = 0;
    } else {
        error_log(__FILE__ . " BLayout::setTemplatePath - Template path '" . $newPath . "' not found");
        $retVal = 0;
    }

    return $retVal;
}

/*
** setVHostTemplatePath - Defines the virtual host template path to use.
**                        Returns 1 on success, 0 on failure.
*/

function setVHostTemplatePath($newPath)
{
    $retVal = 1;        // Assume success.
    if (is_dir($newPath)) {
        //if (!is_file($newPath)) {
            $this->vhostTemplatePath = $newPath;
            if (substr($this->vhostTemplatePath, strlen($this->vhostTemplatePath)-1,1) != "/") $this->vhostTemplatePath .= "/";
            //error_log(__FILE__ . " BLayout::setTemplatePath - Template path set to '" . $this->vhostTemplatePath . "'");
        //} else $retVal = 0;
    } else {
        error_log(__FILE__ . " BLayout::setVHostTemplatePath - Template path '" . $newPath . "' not found");
        $retVal = 0;
    }

    return $retVal;
}

/*
** setPageTitle - Sets the title for the page.
*/

function setPageTitle($newTitle)
{
    $this->pageTitle = $newTitle;
}

/*
** setExtraHeaders - Sets the "extra" headers for the page.
*/

function setExtraHeaders($newHeaders)
{
    if (strlen($this->extraHeaders)) $this->extraHeaders .= "\n";
    $this->extraHeaders .= $newHeaders;
}

/*
** setSiteName - Sets the site name which gets prepended to the page title.
*/

function setSiteName($newName)
{
    $this->siteName = $newName;
}


/*
** setPageLayout - Sets the title for the page.
*/

function setPageBlock($newBlock)
{
    $this->defaultPageBlock = $newBlock;
}

/*
** setTemplate - Defines the template, the style sheet, and the 
**               page block to use.  Only the template is required.
**               The file should be relative to the templatePath.
**               Returns 1 on success, 0 on failure.
*/

function setTemplate($templateFile, $styleSheet = "", $pageBlock = "")
{
    $retVal = 1;        // Assume success.
    
    // First, check for a VHost Template
    //error_log(__FILE__ . " Checking for a vhost template in " . $this->vhostTemplatePath);
    if (!empty($this->vhostTemplatePath) && file_exists($this->vhostTemplatePath . $templateFile) && is_readable($this->vhostTemplatePath . $templateFile)) {
        //error_log(__FILE__ . " Found a vhost template");
        $this->defaultPageTemplate = $this->vhostTemplatePath . $templateFile;
    } else {
        if (file_exists($this->templatePath . $templateFile)) {
            if (!is_readable($this->templatePath . $templateFile)) {
                error_log(__FILE__ . " BLayout::setTemplate - Unable to read template file '" . $this->templatePath . $templateFile . "'");
                $retVal = 0;
            } else {
                $this->defaultPageTemplate = $this->templatePath . $templateFile;
            }
        } else {
            error_log(__FILE__ . " BLayout::setTemplate - Template file '" . $this->templatePath . $templateFile . "' not found");
            $retVal = 0;
        }
    }

    // See if they specified a style sheet.
    // This should be relative to the document root.
    if (isset($styleSheet) && strlen($styleSheet)) {
        $this->defaultStyleSheet = $styleSheet;
        /*
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/" . $styleSheet)) {
                $this->defaultStyleSheet = $styleSheet;
            } else {
                error_log(__FILE__ . " BLayout::setTemplate - Style sheet '" . $_SERVER['DOCUMENT_ROOT'] . "/" . $styleSheet . "' not found");
                $retVal = 0;
            }
        }
        */
    }

    // Set the page block regardless.  We won't try to see if it exists
    // in the file.
    if (isset($pageBlock) && strlen($pageBlock)) {
        $this->defaultPageBlock = $pageBlock;
    }

    return $retVal;
}

/*
** setGlobal - Defines a "global" variable that will be set just before
**             the template is parsed.
*/

function setGlobal($key, $val)
{
    $this->globalVars[$key] = $val;
}

/*!\brief Adds a match and a replacement to our preg arrays.
*
* After each data block on a page is rendered, a preg_replace is done
* on the output.  This allows for what would normally be non parsable
* or data that shouldn't be parsed to be sent through the layout
* class.
* 
* A preg_replace_all will be performed so all occurrences of $match 
* will be replaced.
*
* \param match The pattern or string to search for.
* \param repl What it will be replaced with.
*/

function addPregMatch($match, $repl)
{
    $match = "/$match/s";
    array_push($this->pregMatches,      $match);
    array_push($this->pregReplacements, $repl);
}

/*! \brief Adds a content block to the page.
*
* Adds a content block to the \ref $blocks array.  When adding blocks,
* only weight, zone, title and content are required.  All other paramaters
* are optional.
*
* \param weight Before the page gets rendered, the blocks array gets sorted
* by the weight.  This allows blocks to be added out of order and displayed
* correctly.
* \param zone Determines what zone the block will be rendered in.
* \param title The title of the block.
* \param content The content of the block.
* \param Footer The footer of the block
* \param ExtraVars An array of additional variables to include when parsing the
* block.  This allows for infinate header/footer/navigation possibilities.
* \param tblock The template file to use for this block when parsing
*/

function addBlock($weight, $zone, $title, $content, $footer = "", $extraVars = array(""), $tfile = "")
{
    // Open the template file
    $myTemplate = $this->zoneTemplates[$zone];
    if (isset($tfile) && strlen($tfile)) $myTemplate = $tfile;
    $myFooter = "";
    if (isset($footer) && strlen($footer)) $myFooter = $footer;
    if (!isset($this->zoneTemplates[$zone])) $zone = $this->defaultZone;

    // Create the array item.
    $newBlock = array(
            "Weight"    => $weight,
            "Zone"      => $zone,
            "Title"     => $title,
            "Footer"    => $footer,
            "TFile"     => $myTemplate,
            "ExtraVars" => $extraVars,
            "Data"      => $content
            );

    array_push($this->blocks, $newBlock);
}

/*
** renderBlocks - Internal function.  Gets called by renderLayout to parse
**                and render all of our blocks.
*/

function multisort(&$array, $sortby, $order='desc')
{
    foreach($array as $val) {
        $sortray[] = $val[$sortby];
    }
    $c = $array;
    $const = $order == 'asc' ? SORT_ASC : SORT_DESC;
    $s = array_multisort($sortray, $const, SORT_NUMERIC);
    $array = $c;
    return $s;
}

function renderBlocks()
{
    global $app;

    // Ugly, but necessary because of how our array is structured.
    // Walk through the blocks and get the length of the Weight for all
    // Blocks.  We will then walk through it a second time and zero pad
    // them as strings.  Its not that bad, really.  Timing the zero padding
    // and the sort on 9 blocks on a dual PIII 700MHz with other processes
    // ended up with the whole set of two loops and the sort being 1/100th
    // of a second.  Thats including the time it took to write the log
    // entries and on a machine that is always doing other database things
    // as well.
    $weightLen = 0;
    for ($i = 0; $i < count($this->blocks); $i++) {
        $block = &$this->blocks[$i];
        if (strlen($block["Weight"]) > $weightLen) $weightLen = strlen($block["Weight"]);
    }
    for ($i = 0; $i < count($this->blocks); $i++) {
        $block = &$this->blocks[$i];
        $fmt = "%0" . $weightLen . "d";
        $block["Weight"] = sprintf($fmt, $block["Weight"]);
    }
    // Sort them.
    sort($this->blocks);
    reset($this->blocks);

    for ($i = 0; $i < count($this->blocks); $i++) {
        $dataBlock = &$this->blocks[$i];
        $tpl = new Smarty();
        // Check for a vhost match for this block
        $tplFile = $dataBlock["TFile"];
        if (!empty($this->vhostTemplatePath) && file_exists($this->vhostTemplatePath . $tplFile) && is_readable($this->vhostTemplatePath . $tplFile)) {
            $tpl->template_dir = $this->vhostTemplatePath;
            $tpl->compile_dir  = SMARTY_COMPILE_DIR;
            $tpl->cache_dir    = SMARTY_CACHE;
        } else {
            $tpl->template_dir = $this->templatePath;
            $tpl->compile_dir  = SMARTY_COMPILE_DIR;
            $tpl->cache_dir    = SMARTY_CACHE;
        }
        //$tpl->clear_all_assign();


        $tpl->assign("Title",      $dataBlock["Title"]);
        $tpl->assign("Content",    $dataBlock["Data"]);
        $tpl->assign("Footer",     $dataBlock["Footer"]);
        //if (is_array($extraVars)) 
        reset($dataBlock["ExtraVars"]);
        foreach ($dataBlock["ExtraVars"] as $key => $val) {
            $tpl->assign($key, $val);
        }
        reset($this->globalVars);
        foreach ($this->globalVars as $key => $val) {
            $tpl->assign($key, $val);
        }
        $parsedBlock = $tpl->fetch($dataBlock["TFile"]);

        // Do any preg_replacements on our parsed block
        if (count($this->pregMatches)) {
            $parsedBlock = preg_replace($this->pregMatches, $this->pregReplacements, $parsedBlock);
        }
        $dataBlock["Data"] = $parsedBlock;
    }
}

/*
** render - Takes all of our blocks and renders a page.
**          $block and $template is optional, if it they are not passed, 
**          the default block and template settings will be used.  If 
**          
**   Notes: If a template file is specified, it needs to be the full
**          path to the template file.
*/

function renderLayout($block = "", $template = "")
{
    global $HTTP_ACCEPT_ENCODING;
    $retStr = "";
    //echo "<pre>"; print_r($this->blocks); echo "</pre>";
    // Open the template file
    $myTemplate = $this->defaultPageTemplate;
    if (isset($template) && strlen($template)) $myTemplate = $template;
    $myBlock = $this->defaultPageBlock;
    if (isset($block) && strlen($block)) $myBlock = $block;

    $tpl = new Smarty();
    $tpl->template_dir = $this->templatePath;
    $tpl->compile_dir  = SMARTY_COMPILE_DIR;
    $tpl->cache_dir    = SMARTY_CACHE;

    // Do the blocks
    $this->renderBlocks();
    // Create our arrays.
    //error_log("Creating zone arrays...");
    $zones = array();
    foreach($this->zoneBlocks as $zoneName) {
        $zones[$zoneName] = array();
    }
    foreach($this->blocks as $dataBlock) {
        // Add each of the data blocks into their correct zone array
        array_push($zones[$this->zoneBlocks[$dataBlock["Zone"]]], $dataBlock);
    }
    
    // Now do the page itself.
    $tpl->assign("StyleSheet", $this->defaultStyleSheet);
    $title = "";
    if (strlen($this->siteName)) {
        $title .= $this->siteName . ": ";
    }
    if (strlen($this->pageTitle)) {
        $title .= $this->pageTitle;
    } else {
        $title .= "Untitled page";
    }
    $tpl->assign("PageTitle", $title);
    $tpl->assign("ExtraHeaders", $this->extraHeaders);
    reset($this->globalVars);
    foreach ($this->globalVars as $key => $val) {
        $tpl->assign($key, $val);
    }

    $i = 0;
    // Assign the zones to their Smarty variables as arrays
    foreach($this->zoneBlocks as $zoneName) {
        //error_log("Assigning zoneBlock '" . $this->zoneBlocks[$i] . "'");
        $tpl->assign($this->zoneBlocks[$i], $zones[$zoneName]);
        $i++;
    }
    // Last thing is to display it.
    $tpl->display($myTemplate);
    // Check to see if we should compress the output now.
    $doCompress = 0;
    $encoding = "gzip";
    $content  = ob_get_contents();
    /*
    if ($this->compressOutput) {
        $doCompress = 1;
        if (headers_sent()) {
            $this->writeLog("Headers already sent.  Output compression disabled.");
            $doCompress = 0;
        }
        if (eregi("gzip", $HTTP_ACCEPT_ENCODING)) $encoding = "gzip";
        else if (eregi("x-gzip", $HTTP_ACCEPT_ENCODING)) $encoding = "x-gzip";
        else $doCompress = 0;
    }
    */
    
    ob_end_clean();
    if ($doCompress) {
        $normsize = strlen($content);
        $gzipsize = $normsize;
        header("Content-Encoding: $encoding");
        $Crc = crc32($content);
        $content = gzcompress($content, 5);
        $gzipsize = strlen($content);
        $content = substr($content, 0, strlen($content) - 4);
        echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
        echo $content;
        $comprate = sprintf("%.2f", (float)($normsize/$gzipsize));
        $retStr .= "Compression rate $comprate:1";
    } else {
        echo $content;
        //$retStr .= "Output compression disabled.";
    }

    return $retStr;
}

// Utility functions.

/*
** createMenu - Creates a set of links suitable for display in a menu.
*/

function createMenu($links, $depth = 0, $linksep = "<br>", $indent = "&nbsp;", $target = "", $style = "")
{
    reset($links);
    $retStr = "";
    $workStr = "";
    foreach ($links as $desc => $url) {
        $workStr = "";
        // Check to see if we are forcing an indent using nbsp's, or spaces.
        // If so, get rid of them first and indent them elsewhere.
        while (!strcmp("&nbsp;", substr($desc, 0, 6))) {
            $desc = substr($desc, 6);
            $workStr .= $indent;
        }
        while (!strcmp(" ", substr($desc, 0, 1))) {
            $desc = substr($desc, 1);
            $workStr .= $indent;
        }

        // Are we indenting this one?
        if ($depth > 0) {
            for ($i = 0; $i < $depth; $i++) {
                $workStr .= $indent;
            }
        }
        $workStr .= "<a href=\"$url\"";
        // Add in the extra stuff if we have any.
        if (isset($target) && strlen($target)) {
            $workStr .= " target=\"$target\"";
        }
        if (isset($style) && strlen($style)) {
            $workStr .= " class=\"$style\"";
        }
        $workStr .= ">$desc</a>";

        if (strlen($retStr)) $retStr .= $linksep;
        $retStr .= $workStr;
    }

    return $retStr;
}

}  // BLayout class

?>

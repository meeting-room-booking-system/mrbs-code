/*
 * Cross-Browser Library of JavaScript Functions
 *
 * Inspired by the excellent xbDOM.js (and using it for debugging)
 *
 * $Id$
 */

var doAlert = false;
var xbDump = function(string, tag) {return;} // If indeed not defined, define as a dummy routine.

// var xbDump = function(string, tag) {alert(string);} // If indeed not defined, define as a dummy routine.

if (doAlert) alert("Started xbLib.js v4");
xbDump("Started xbLib.js v4");

/*****************************************************************************\
*		Part 1: Generic Cross-Browser Library routines		      *
\*****************************************************************************/

// Browser-independant background color management
function xblGetNodeBgColor(node)
    {
    if (!node) return null;
    xbDump("node.bgColor = " + (node.bgColor ? node.bgColor : "<undefined>"));
    if (node.style)
	{
        xbDump("node.style.getPropertyValue(\"background-color\") = " + (node.style.getPropertyValue ? ("\""+node.style.getPropertyValue("background-color")+"\"") : "<undefined>"));
        xbDump("node.style.getAttribute(\"backgroundColor\") = " + (node.style.getAttribute ? ("\""+node.style.getAttribute("backgroundColor")+"\"") : "<undefined>"));
        xbDump("node.style.backgroundColor = " + (node.style.backgroundColor ? node.style.backgroundColor : "<undefined>"));
	if (node.style.getPropertyValue)	// If DOM level 2 supported, the NS 6 way
            {
            return node.style.getPropertyValue("background-color");
            }
	if (node.style.getAttribute)		// If DOM level 2 supported, the IE 6 way
            {
            return node.style.getAttribute("backgroundColor");
            }
        return node.style.backgroundColor;	// Else DOM support is very limited.
	}
    // Else this browser is not DOM compliant. Try getting a classic attribute.
    return node.bgColor;
    }
function xblSetNodeBgColor(node, color)
    {
    if (!node) return;
    if (node.style)
	{
	if (node.style.setProperty)		// If DOM level 2 supported, the NS 6 way
            {
            node.style.setProperty("background-color", color, "");
	    return;
            }
	if (node.style.setAttribute)		// If DOM level 2 supported, the IE 6 way
            {
            node.style.setAttribute("backgroundColor", color);
	    return;
            }
	// Else this browser has very limited DOM support. Try setting the attribute directly.
        node.style.backgroundColor = color;	// Works on Opera 6
	return;
	}
    // Else this browser is not DOM compliant. Try setting a classic attribute.
    node.bgColor = color;
    }

// Browser-independant node tree traversal
function xblChildNodes(node)
    {
    if (!node) return null;
    if (node.childNodes) return node.childNodes;	// DOM-compliant browsers
    if (node.children) return node.children;		// Pre-DOM browsers like Opera 6
    return null;
    }
function xblFirstSibling(node)
    {
    if (!node) return null;
    var siblings = xblChildNodes(node.parentNode);
    if (!siblings) return null;
    return siblings[0];
    }
function xblLastSibling(node)
    {
    if (!node) return null;
    var siblings = xblChildNodes(node.parentNode);
    if (!siblings) return null;
    return siblings[siblings.length - 1];
    }

var xbGetElementById;
if (document.getElementById) // DOM level 2
    xblGetElementById = function(id) { return document.getElementById(id); };
else if (document.layers)	  // NS 4
    xblGetElementById = function(id) { return document.layers[id]; };
else if (document.all)		  // IE 4
    xblGetElementById = function(id) { return document.all[id]; };
else
    xblGetElementById = function(id) { return null; };

// Browser-independant style sheet rules scan.
function xbForEachCssRule(callback, ref)
    {
    if (document.styleSheets) for (var i=0; i<document.styleSheets.length; i++) 
        {
        var sheet = document.styleSheets.item(i);
        // xbDump("Style sheet " + i, "h3"); 
        // xbDumpProps(sheet);
        // If the browser is kind enough for having already split the CSS rules as specified by DOM... (NS6)
        if (sheet.cssRules) for (var j=0; j<sheet.cssRules.length; j++)
            {
            var rule = sheet.cssRules.item(j);
            // xbDump("Rule " + j, "h4");
            // xbDumpProps(rule);
            var result = callback(rule, ref);
            if (result) return result;
            }
        else if (sheet.cssText) // Else pass it the whole set at once (IE6)
            {
            // TO DO EVENTUALLY: Split the list into individual rules!
            var result = callback(sheet, ref);
            if (result) return result;
            }
        }
    return false;
    }

/*---------------------------------------------------------------------------*\
*									      *
|   Function:	    ForEachChild					      |
|									      |
|   Description:    Apply a method to each child node of an object.	      |
|									      |
|   Parameters:     Object obj          The object. Typically a DOM node.     |
|                   Function callback   Callback function.                    |
|                   Object ref          Reference object.                     |
|									      |
|   Returns:        The first non-null result reported by the callback.       |
|									      |
|   Support:        NS4           No. Returns null.                           |
|                   IE5+, NS6+    Yes.                                        |
|                   Opera 6       Yes.                                        |
|									      |
|   Notes:          The callback prototype is:                                |
|                   int callback(obj, ref);                                   |
|                   If the callback returns !null, the loop stops.            |
|									      |
|   History:                                                                  |
|									      |
|    2002/03/04 JFL Initial implementation.                                   |
|    2002/03/25 JFL Simplified the implementation.                            |
*									      *
\*---------------------------------------------------------------------------*/

function ForEachChild(obj, callback, ref)
  {
  if (!obj) return null;
  
  var children = null;
  if (obj.childNodes)           // DOM-compliant browsers
    children = obj.childNodes;
  else if (obj.children)        // Pre-DOM browsers like Opera 6
    children = obj.children;
  else
    return null;
    
  var nChildren = children.length;
  for (var i=0; i<nChildren; i++) 
    {
    var result = callback(children[i], ref);
    if (result) return result;
    }
  return null;
  }

/*---------------------------------------------------------------------------*\
*									      *
|   Function:       ForEachDescendant                                         |
|									      |
|   Description:    Apply a method to each descendant node of an object.      |
|									      |
|   Parameters:     Object obj          The object. Typically a DOM node.     |
|                   Function callback   Callback function.                    |
|                   Object ref          Reference object.                     |
|									      |
|   Returns:        The first non-null result reported by the callback.       |
|									      |
|   Support:        NS4           No. Returns null.                           |
|                   IE5+, NS6+    Yes.                                        |
|                   Opera 6       Yes.                                        |
|									      |
|   Notes:          The callback prototype is:                                |
|                   int callback(obj, ref);                                   |
|                   If the callback returns !null, the loop stops.            |
|									      |
|   History:                                                                  |
|									      |
|    2002/10/29 JFL Initial implementation.				      |
*									      *
\*---------------------------------------------------------------------------*/

function ForEachDescendantCB(obj, ref)
  {
  var result = ref.cb(obj, ref.rf);
  if (result) return result;
  return ForEachChild(obj, ForEachDescendantCB, ref);
  }

function ForEachDescendant(obj, callback, ref)
  {
  if (!obj) return null;
  var ref1 = {cb:callback, rf:ref};
  var result = ForEachChild(obj, ForEachDescendantCB, ref1);
  delete ref1;
  return result;
  }

/*****************************************************************************\
*            Part 2: MRBS-specific Active Cell Management routines            *
\*****************************************************************************/

// Define global variables that control the behaviour of the Active Cells.
// Set conservative defaults, to get the "classic" behaviour if JavaScript is half broken.

var useJS = false;	// If true, use JavaScript for cell user interface. If null, use a plain Anchor link.
var highlight_left_column = false;
var highlight_right_column = false;
var highlightColor = "#999999"; // Default highlight color, if we don't find the one in the CSS.
var statusBarMsg = "Click on the cell to make a reservation."; // Message to write on the status bar when activating a cell.

// Duplicate at JavaScript level the relevant PHP configuration variables.
var show_plus_link = true;
var highlight_method = "hybrid";

var GetNodeColorClass = function(node)
    {
    return node.className;
    }
var SetNodeColorClass = function(node, colorClass) 
    { 
    node.className = colorClass;  // Use the TD.highlight color from mrbs.css.
    }

// Helper routines for searching text in the TD.highlight CSS class.
function SearchTdHighlightText(ruleText, ref)	// Callback called by the CSS scan routine
    {
    xbDump("SearchTdHighlightText() called back");
    if (!ruleText) return null;
    ruleText = ruleText.toLowerCase();			// Make sure search is using a known case.
    var k = ruleText.indexOf("td.highlight");
    if (k == -1) return null;				// TD.highlight not found in this rule.
    k = ruleText.indexOf("background-color:", k) + 17;
    if (k == 16) return null;				// TD.highlight background-color not defined.
    while (ruleText.charAt(k) <= ' ') k += 1;		// Strip blanks before the color value.
    var l = ruleText.length;
    var m = ruleText.indexOf(";", k);			// Search the separator with the next attribute.
    if (m == -1) m = l;
    var n = ruleText.indexOf("}", k);			// Search the end of the rule.
    if (n == -1) n = l;
    if (m < n) n = m;					// n = index of the first of the above two.
    while (ruleText.charAt(n-1) <= ' ') n -= 1; 	// Strip blanks after the color value
    var color = ruleText.substr(k, n-k);
    xbDump("SearchTdHighlightText() found color = " + color);
    return color;
    }
function isAlphaNum(c)
    {
    return ("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789".indexOf(c) >= 0);
    }
function SearchTdHighlight(rule, ref)	// Callback called by the CSS scan routine
    {
    if (!rule) return null;
    if (rule.selectorText)		// DOM. NS6, Konqueror.
	{
	var selector = rule.selectorText.toLowerCase();
        var i = selector.indexOf("td.highlight");
        if (i == -1) return null;
        if (i > 0) return null;
        // var c = selector.charAt(i+12);
        // if ((!c) || isAlphaNum(c)) return null;
	if (!rule.style) return null;
	return xblGetNodeBgColor(rule);
        }
    if (rule.cssText)			// Alternative for IE6
        return SearchTdHighlightText(rule.cssText);
    return null;
    }

/*---------------------------------------------------------------------------*\
*									      *
|   Function:       InitActiveCell					      |
|									      |
|   Description:    Initialize the active cell management.		      |
|									      |
|   Parameters:     Boolean show	Whether to show the (+) link.	      |
|                   Boolean left	Whether to highlight the left column. |
|                   Boolean right	Whether to highlight the right column.|
|                   String method	One of "bgcolor", "class", "hybrid".  |
|                   String message      The message to put on the status bar. |
|									      |
|   Returns:        Nothing.						      |
|									      |
|   Support:        NS4           No. Returns null.                           |
|                   IE5+, NS6+    Yes.                                        |
|                   Opera 6       Yes.                                        |
|									      |
|   Notes:          This code implements 3 methods for highlighting cells:    |
|		    highlight_method="bgcolor"				      |
|			Dynamically changes the cell background color.	      |
|			Advantage: Works with most javascript-capable browsers.
|			Drawback: The color is hardwired in this module.(grey)|
|		    highlight_method="class"				      |
|			Highlights active cells by changing their color class.|
|			The highlight color is the background-color defined   |
|			 in class td.highlight in the CSS.		      |
|			Advantage: The class definition in the CSS can set    |
|			 anything, not just the color.			      |
|			Drawback: Slooow on Internet Explorer 6 on slow PCs.  |
|		    highlight_method="hybrid"				      |
|			Extracts the color from the CSS DOM if possible, and  |
|			 uses it it like in the bgcolor method.		      |
|			Advantage: Fast on all machines; color defined in CSS.|
|			Drawback: Not as powerful as the class method.	      |
|									      |
|   History:                                                                  |
|									      |
|    2004/03/01 JFL Initial implementation.				      |
*									      *
\*---------------------------------------------------------------------------*/

function InitActiveCell(show, left, right, method, message)
    {
    show_plus_link = show;
    highlight_method = method;
    highlight_left_column = left;
    highlight_right_column = right;
    statusBarMsg = message;

    xbDump("show_plus_link = " + show_plus_link);
    xbDump("highlight_method = " + highlight_method);
    xbDump("highlight_left_column = " + highlight_left_column);
    xbDump("highlight_right_column = " + highlight_right_column);
    xbDump("statusBarMsg = " + statusBarMsg);

    // Javascript feature detection: Check if a click handler can be called by the browser for a table.
    // For example Netscape 4 only supports onClick for forms.
    // For that, create a hidden table, and check if it has an onClick handler.
    // document.write("<table id=\"test_table\" onClick=\"return true\" border=0 style=\"display:none\"><tr><td id=\"test_td\" class=\"highlight\">&nbsp;</td></tr></table>\n");
    // Note: The previous line, also technically correct, silently breaks JavaScript on Netscape 4.
    //       (The processing of this file completes successfully, but the next script is not processed.)
    //       The next line, with the bare minimum content for our purpose, works on all browsers I've tested, included NS4.
    document.write("<table id=\"test_table\" onClick=\"return true\" border=0></table>\n");
    var test_table = xblGetElementById("test_table"); // Use this test table to detect the browser capabilities.
    if (test_table && test_table.onclick) useJS = true; // If the browser supports click event handlers on tables, then use JavaScript.

    xbDump("JavaScript feature detection: Table onClick supported = " + useJS);

    //----------------------------------------------------//

    //	Javascript feature detection: Check if the browser supports dynamically setting style properties.
    var useCssClass = ((highlight_method=="class") && test_table && test_table.style
                       && (test_table.style.setProperty || test_table.style.setAttribute) && true);
    if (useCssClass)			// DOM-compliant browsers
        GetNodeColorClass = function(node) { return node.className; }
    else					// Pre-DOM browsers like Opera 6
        GetNodeColorClass = function(node) { return xblGetNodeBgColor(node); } // Can't get class, so get color.

    xbDump("JavaScript feature detection: Table class setting supported = " + useCssClass);

    //----------------------------------------------------//

    // Now search in the CSS objects the background color of the TD.highlight class.
    // This is done as a performance optimization for IE6: Only change the TD background color, but not its class.
    highlightColor = null;
    if (highlight_method!="bgcolor") highlightColor = xbForEachCssRule(SearchTdHighlight, 0);
    if (!highlightColor)
        {
        highlightColor = "#999999";	// Set default for DOM-challenged browsers
        xbDump("Using defaut highlight color = " + highlightColor);
        }
    else
        {
        xbDump("Found CSS highlight color = " + highlightColor);
        }

    //----------------------------------------------------//

    // Finally combine the last 2 results to generate the SetNodeColorClass function.
    if (useCssClass)			 // DOM-compliant browsers
        SetNodeColorClass = function(node, colorClass) 
            { 
            xbDump("SetNodeColorClass(" + colorClass + ")");
            node.className = colorClass;  // Use the TD.highlight color from mrbs.css.
            }
    else				 // Pre-DOM browsers like Opera 6
        SetNodeColorClass = function(node, colorClass) 
            {
            xbDump("SetNodeColorClass(" + colorClass + ")");
            if (colorClass == "highlight") colorClass = highlightColor; // Cannot use the CSS color class. Use the color computed above.
            xblSetNodeBgColor(node, colorClass);
            }
    }

//----------------------------------------------------//

// Cell activation
function HighlightNode(node)	// Change one TD cell color class
    {
    node.oldColorClass = GetNodeColorClass(node);
    SetNodeColorClass(node, "highlight");
    }
function ActivateCell(cell)	// Activate the TD cell under the mouse, and optionally the corresponding hour cells on both sides of the table.
    {
    if (cell.isActive) return;	// Prevent problems with reentrancy. (It happens on slow systems)
    cell.isActive = true;
    if (statusBarMsg) window.status = statusBarMsg; // Write into the status bar.
    // First find the enclosing table data cell.
    for (var tdCell=cell.parentNode; tdCell; tdCell=tdCell.parentNode)
	{ if (tdCell.tagName == "TD") break; }
    if (!tdCell) return;
    HighlightNode(tdCell);
    if (highlight_left_column)
        {
        // Locate the head node for the current row.
        var leftMostCell = xblFirstSibling(tdCell);
        if (leftMostCell) HighlightNode(leftMostCell);
        }
    if (highlight_right_column)
        {
        // Locate the last node for the current row. (Only when configured to display times at right too.)
        var rightMostCell = xblLastSibling(tdCell);
        // Now work around a Netscape peculiarity: The #text object is a sibling and not a child of the TD!
        while (rightMostCell && (rightMostCell.tagName != "TD")) rightMostCell = rightMostCell.previousSibling;
        if (rightMostCell) HighlightNode(rightMostCell);
        }
    }
// Cell unactivation
function UnactivateCell(cell)
    {
    if (!cell.isActive) return; // Prevent problems with reentrancy.
    cell.isActive = null;
    window.status = "";		// Clear the status bar.
    // First find the enclosing table data cell.
    for (var tdCell=cell.parentNode; tdCell; tdCell=tdCell.parentNode)
	{ if (tdCell.tagName == "TD") break; }
    if (!tdCell) return;
    SetNodeColorClass(tdCell, tdCell.oldColorClass);
    if (highlight_left_column)
        {
        // Locate the head node for the current row.
        var leftMostCell= xblFirstSibling(tdCell);
        if (leftMostCell) SetNodeColorClass(leftMostCell, leftMostCell.oldColorClass);
        }
    if (highlight_right_column)
        {
        // Locate the last node for the current row. (Only when configured to display times at right too.)
        var rightMostCell = xblLastSibling(tdCell);
        // Now work around a NetScape peculiarity: The #text object is a sibling and not a child of the TD!
        while (rightMostCell && (rightMostCell.tagName != "TD")) rightMostCell = rightMostCell.previousSibling;
        if (rightMostCell) SetNodeColorClass(rightMostCell, rightMostCell.oldColorClass);
        }
    }

xbDump("Cell activation routines defined.");

//----------------------------------------------------//

// Cell click handling

// Callback used to find the A link inside the cell clicked.
function GotoLinkCB(node, ref)
{
    var tag = null;
    if (node.tagName) tag = node.tagName;		// DOM-compliant tag name.
    else if (node.nodeName) tag = node.nodeName;	// Implicit nodes, such as #text.
    if (tag && (tag.toUpperCase() == "A")) return node;
    return null;
}

// Handler for going to the period reservation edition page.
function GotoLink(node)
{
    xbDump("GotoLink()");
    link = ForEachDescendant(node, GotoLinkCB, null);
    if (link) window.location = link.href;
}

xbDump("Cell click handlers defined.");

//----------------------------------------------------//

// Cell content generation

function BeginActiveCell()
{
    if (useJS)
        {
        document.write("<table class=\"naked\" width=\"100%\" height=\"100%\" onMouseOver=\"ActivateCell(this)\" onMouseOut=\"UnactivateCell(this)\" onClick=\"GotoLink(this)\">\n<td class=\"naked\">\n");
	// Note: The &nbsp; below is necessary to fill-up the cell. Empty cells behave badly in some browsers.
        if (!show_plus_link) document.write("&nbsp;<div style=\"display:none\">\n"); // This will hide the (+) link.
        }
}

function EndActiveCell()
{
    if (useJS)
        {
        if (!show_plus_link) document.write("</div>");
        document.write("</td></table>\n");
        }
}

xbDump("Cell content generation routines defined.");

//----------------------------------------------------//

if (doAlert) alert("Ended xbLib.js");
xbDump("Ended xbLib.js.php");

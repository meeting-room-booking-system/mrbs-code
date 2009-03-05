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

if (doAlert)
{
  alert("Started xbLib.js v4");
}
xbDump("Started xbLib.js v4");

/*****************************************************************************\
* Part 1: Generic Cross-Browser Library routines                             *
\*****************************************************************************/

// Browser-independent background color management
function xblGetNodeBgColor(node)
{
  if (!node)
  {
    return null;
  }

  // Have to use currentStyle or ComputedStyle here because node.style will just give you
  // what's in the style attribute (ie <td style="xxx">), rather than what the actual style 
  // is computed from the cascade.    As there is no style attribute this does not work (ie 
  // will return null).    Although xblSetNodeBgColor() will cope with null values, we will
  // try and avoid them in the first place. 
  
  if (node.currentStyle)         // The IE way
  {
		return node.currentStyle.backgroundColor;
  }
  if (window.getComputedStyle)   // All other browsers
  {
    var compStyle = document.defaultView.getComputedStyle(node, "");
    return compStyle.getPropertyValue("background-color");
  }
  // Else this browser is not DOM compliant. Try getting a classic attribute.
  return node.bgColor;
}

function xblSetNodeBgColor(node, color)
{
  if (!node)
  {
    return;
  }
  if (node.style)
  {
    if (node.style.setProperty) // If DOM level 2 supported, the NS 6 way
    {
      if (color)
      {
        node.style.setProperty("background-color", color, "");
      }
      else
      // Need to cater for the case when color is null.    Although most browsers will do what you might expect
      // when you use setProperty or setAttribute with null, there are some, eg Konqueror, that don't.
      {
        node.style.removeProperty("background-color");
      }
      return;
    }
    if (node.style.setAttribute) // If DOM level 2 supported, the IE 6 way
    {
      if (color)
      {
        node.style.setAttribute("backgroundColor", color);
      }
      else      // see comment above
      {
        node.style.removeAttribute("backgroundColor");
      }
      return;
    }
    // Else this browser has very limited DOM support. Try setting the attribute directly.
    node.style.backgroundColor = color; // Works on Opera 6
    return;
  }
  // Else this browser is not DOM compliant. Try setting a classic attribute.
  node.bgColor = color;
}

// Browser-independant node tree traversal
function xblChildNodes(node)
{
  if (!node)
  {
    return null;
  }
  if (node.childNodes)
  {
    return node.childNodes; // DOM-compliant browsers
  }
  if (node.children)
  {
    return node.children; // Pre-DOM browsers like Opera 6
  }
  return null;
}

function xblFirstSibling(node)
{
  if (!node)
  {
    return null;
  }
  var siblings = xblChildNodes(node.parentNode);
  if (!siblings)
  {
    return null;
  }
  return siblings[0];
}

function xblLastSibling(node)
{
  if (!node)
  {
    return null;
  }
  var siblings = xblChildNodes(node.parentNode);
  if (!siblings)
  {
    return null;
  }
  return siblings[siblings.length - 1];
}

var xbGetElementById;
if (document.getElementById) // DOM level 2
{
  xblGetElementById = function(id) { return document.getElementById(id); };
}
else if (document.layers) // NS 4
{
  xblGetElementById = function(id) { return document.layers[id]; };
}
else if (document.all) // IE 4
{
  xblGetElementById = function(id) { return document.all[id]; };
}
else
{
  xblGetElementById = function(id) { return null; };
}

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
      if (result)
      {
        return result;
      }
    }
    else if (sheet.cssText) // Else pass it the whole set at once (IE6)
    {
      // TO DO EVENTUALLY: Split the list into individual rules!
      var result = callback(sheet, ref);
      if (result)
      {
        return result;
      }
    }
  }
  return false;
}

/*---------------------------------------------------------------------------*\
*                                                                             *
|   Function:       ForEachChild                                              |
|                                                                             |
|   Description:    Apply a method to each child node of an object.           |
|                                                                             |
|   Parameters:     Object obj          The object. Typically a DOM node.     |
|                   Function callback   Callback function.                    |
|                   Object ref          Reference object.                     |
|                                                                             |
|   Returns:        The first non-null result reported by the callback.       |
|                                                                             |
|   Support:        NS4           No. Returns null.                           |
|                   IE5+, NS6+    Yes.                                        |
|                   Opera 6       Yes.                                        |
|                                                                             |
|   Notes:          The callback prototype is:                                |
|                   int callback(obj, ref);                                   |
|                   If the callback returns !null, the loop stops.            |
|                                                                             |
|   History:                                                                  |
|                                                                             |
|    2002/03/04 JFL Initial implementation.                                   |
|    2002/03/25 JFL Simplified the implementation.                            |
*                                                                             *
\*---------------------------------------------------------------------------*/

function ForEachChild(obj, callback, ref)
{
  if (!obj)
  {
    return null;
  }
  
  var children = null;
  if (obj.childNodes)           // DOM-compliant browsers
  {
    children = obj.childNodes;
  }
  else if (obj.children)        // Pre-DOM browsers like Opera 6
  {
    children = obj.children;
  }
  else
  {
    return null;
  }
    
  var nChildren = children.length;
  for (var i=0; i<nChildren; i++) 
  {
    var result = callback(children[i], ref);
    if (result)
    {
      return result;
    }
  }
  return null;
}

/*---------------------------------------------------------------------------*\
*                                                                             *
|   Function:       ForEachDescendant                                         |
|                                                                             |
|   Description:    Apply a method to each descendant node of an object.      |
|                                                                             |
|   Parameters:     Object obj          The object. Typically a DOM node.     |
|                   Function callback   Callback function.                    |
|                   Object ref          Reference object.                     |
|                                                                             |
|   Returns:        The first non-null result reported by the callback.       |
|                                                                             |
|   Support:        NS4           No. Returns null.                           |
|                   IE5+, NS6+    Yes.                                        |
|                   Opera 6       Yes.                                        |
|                                                                             |
|   Notes:          The callback prototype is:                                |
|                   int callback(obj, ref);                                   |
|                   If the callback returns !null, the loop stops.            |
|                                                                             |
|   History:                                                                  |
|                                                                             |
|    2002/10/29 JFL Initial implementation.                                   |
*                                                                             *
\*---------------------------------------------------------------------------*/

function ForEachDescendantCB(obj, ref)
{
  var result = ref.cb(obj, ref.rf);
  if (result)
  {
    return result;
  }
  return ForEachChild(obj, ForEachDescendantCB, ref);
}

function ForEachDescendant(obj, callback, ref)
{
  if (!obj)
  {
    return null;
  }
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

var useJS = false; // If true, use JavaScript for cell user interface. If null, use a plain Anchor link.
var highlight_left_column = false;
var highlight_right_column = false;
var highlightColor = "#ffc0da"; // Default highlight color, if we don't find the one in the CSS.
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
  node.className = colorClass;  // Use the td.highlight color from mrbs.css.php.
}

// Helper routines for searching text in the td.highlight CSS class.
function SearchTdHighlightText(ruleText, ref) // Callback called by the CSS scan routine
{
  xbDump("SearchTdHighlightText() called back");
  if (!ruleText)
  {
    return null;
  }
  ruleText = ruleText.toLowerCase(); // Make sure search is using a known case.
  var k = ruleText.indexOf("td.highlight");
  if (k == -1)
  {
    return null; // td.highlight not found in this rule.
  }
  k = ruleText.indexOf("background-color:", k) + 17;
  if (k == 16)
  {
    return null; // td.highlight background-color not defined.
  }
  while (ruleText.charAt(k) <= ' ')
  {
    k += 1; // Strip blanks before the color value.
  }
  var l = ruleText.length;
  var m = ruleText.indexOf(";", k); // Search the separator with the next attribute.
  if (m == -1)
  {
    m = l;
  }
  var n = ruleText.indexOf("}", k); // Search the end of the rule.
  if (n == -1)
  {
    n = l;
  }
  if (m < n)
  {
    n = m; // n = index of the first of the above two.
  }
  while (ruleText.charAt(n-1) <= ' ')
  {
    n -= 1; // Strip blanks after the color value
  }
  var color = ruleText.substr(k, n-k);
  xbDump("SearchTdHighlightText() found color = " + color);
  return color;
}

function isAlphaNum(c)
{
  return ("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789".indexOf(c) >= 0);
}

// Callback called by the CSS scan routine
function SearchTdHighlight(rule, ref)
{
  if (!rule)
  {
    return null;
  }
  if (rule.selectorText) // DOM. NS6, Konqueror.
  {
    var selector = rule.selectorText.toLowerCase();
    var i = selector.indexOf("td.highlight");
    if (i == -1)
    {
      return null;
    }
    if (i > 0)
    {
      return null;
    }
    // var c = selector.charAt(i+12);
    // if ((!c) || isAlphaNum(c)) return null;
    if (!rule.style)
    {
      return null;
    }
    
    if (rule.style.getPropertyValue) // If DOM level 2 supported, the NS 6 way
    {
      return rule.style.getPropertyValue("background-color");
    }
    if (rule.style.getAttribute) // If DOM level 2 supported, the IE 6 way
    {
      return rule.style.getAttribute("backgroundColor");
    }
    return rule.style.backgroundColor; // Else DOM support is very limited.
  }
  
  if (rule.cssText) // Alternative for IE6
  {
    return SearchTdHighlightText(rule.cssText);
  }
  return null;
}

/*---------------------------------------------------------------------------*\
*                                                                             *
|   Function:       InitActiveCell                                            |
|                                                                             |
|   Description:    Initialize the active cell management.                    |
|                                                                             |
|   Parameters:     Boolean show        Whether to show the (+) link.         |
|                   Boolean left        Whether to highlight the left column. |
|                   Boolean right       Whether to highlight the right column.|
|                   String method       One of "bgcolor", "class", "hybrid".  |
|                   String message      The message to put on the status bar. |
|                                                                             |
|   Returns:        Nothing.                                                  |
|                                                                             |
|   Support:        NS4           No. Returns null.                           |
|                   IE5+, NS6+    Yes.                                        |
|                   Opera 6       Yes.                                        |
|                                                                             |
|   Notes:          For all browsers other than IE6 and below, highlighting   |
|                   is done using CSS, i.e. by using tr:hover and td:hover.   |
|                   But since IE6 and below do not support the hover pseudo-  |
|                   class on elements other than <a>, we have to use          |
|                   JavaScript for those browsers.    (Theoretically we       |
|                   would also have to for early versions of other browsers,  |
|                   but we are making the assumption that if people are       |
|                   using non-IE browsers, then they will have upgraded at    |
|                   least to a version that properly supports the :hover      |
|                   pseudo-class).                                            |
|                                                                             |
|                   For IE6 and below, this code implements 3 methods for     |
|                   highlighting cells, all using JavaScript:                 |
|                                                                             |
|                   highlight_method="bgcolor"                                |
|                       Dynamically changes the cell background color.        |
|                       Advantage: Works with most javascript-capable browsers.
|                       Drawback: The color is hardwired in this module.(grey)|
|                   highlight_method="class"                                  |
|                       Highlights active cells by changing their color class.|
|                       The highlight color is the background-color defined   |
|                        in class td.highlight in the CSS.                    |
|                       Advantage: The class definition in the CSS can set    |
|                        anything, not just the color.                        |
|                       Drawback: Slooow on Internet Explorer 6 on slow PCs.  |
|                   highlight_method="hybrid"                                 |
|                       Extracts the color from the CSS DOM if possible, and  |
|                        uses it it like in the bgcolor method.               |
|                       Advantage: Fast on all machines; color defined in CSS.|
|                       Drawback: Not as powerful as the class method.        |
|                                                                             |
|                   (Note that if you try and force newer browsers to use     |
|                   JavaScript highlighting, by forcing use_css_highlighting  |
|                   to be false, then this won't work unless you remove the   |
|                   CSS rules.    What can happen is that if the CSS :hover   |
|                   event gets triggered before the onMouseOver event, then   |
|                   the CSS will change the background colour, and when       |
|                   JavaScript eventually arrives and reads what it thinks is |
|                   the "old" background colour it actually reads the         |
|                   highlight colour that has just been set by CSS.  So the   |
|                   cell gets stuck at the highlight colour.)                 |
|                                                                             |
|   History:                                                                  |
|                                                                             |
|    2004/03/01 JFL Initial implementation.                                   |
*                                                                             *
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
  
  // Check to see whether we are using IE6 or below.    This is done by checking the
  // href string of the stylesheets that have been loaded (see style.inc for the titles).
  //
  // If we are using IE6 or below then the :hover pseudo-class
  // is not supported for elements other than <a> and we will have to use JavaScript highlighting
  // instead of CSS highlighting.    If we can't even read the href string, then it's a good bet that 
  // the :hover pseudo-class isn't supported either.
  var use_css_highlighting = true;
  if (document.styleSheets)
  {
    var nStyleSheets = document.styleSheets.length;
    for (var i=0; i < nStyleSheets; i++)
    {
      // check to see if the stylesheet is an 'ielte6' sheet;
      // if it is, then we can't use CSS highlighting
      if (document.styleSheets[i].href != null)  // will be null in the case of an embedded style sheet
      {
        if (document.styleSheets[i].href.search(/ielte6/i) != -1)
        {
          use_css_highlighting = false;
          break;
        }
      }
    }
  }
  else
  {
    use_css_highlighting = false;
  }
  
  // If we are to use CSS highlighting then redefine the begin/end active cell functions as empty 
  // functions, because we don't need them.
  if (use_css_highlighting)
  {
    BeginActiveCell = function() {};
    EndActiveCell   = function() {};
    return;
  }

  // Javascript feature detection: Check if a click handler can be called by the browser for a table.
  // For example Netscape 4 only supports onClick for forms.
  // For that, create a hidden table, and check if it has an onClick handler.
  // document.write("<table id=\"test_table\" onClick=\"return true\" border=0 style=\"display:none\"><tr><td id=\"test_td\" class=\"highlight\">&nbsp;</td></tr></table>\n");
  // Note: The previous line, also technically correct, silently breaks JavaScript on Netscape 4.
  //       (The processing of this file completes successfully, but the next script is not processed.)
  //       The next line, with the bare minimum content for our purpose, works on all browsers I've tested, included NS4.
  document.write("<table id=\"test_table\" onClick=\"return true\" border=0></table>\n");
  var test_table = xblGetElementById("test_table"); // Use this test table to detect the browser capabilities.
  if (test_table && test_table.onclick)
  {
    useJS = true; // If the browser supports click event handlers on tables, then use JavaScript.
  }

  xbDump("JavaScript feature detection: Table onClick supported = " + useJS);

  //----------------------------------------------------//

  // Javascript feature detection: Check if the browser supports dynamically setting style properties.
  var useCssClass = ((highlight_method=="class") && test_table && test_table.style
                     && (test_table.style.setProperty || test_table.style.setAttribute) && true);
  if (useCssClass)
  {
    // DOM-compliant browsers
    GetNodeColorClass = function(node) { return node.className; }
  }
  else
  {
    // Pre-DOM browsers like Opera 6
    GetNodeColorClass = function(node) { return xblGetNodeBgColor(node); } // Can't get class, so get color.
  }

  xbDump("JavaScript feature detection: Table class setting supported = " + useCssClass);

  //----------------------------------------------------//

  // Now search in the CSS objects the background color of the td.highlight class.
  // This is done as a performance optimization for IE6: Only change the td background color, but not its class.
  highlightColor = null;
  if (highlight_method!="bgcolor")
  {
    highlightColor = xbForEachCssRule(SearchTdHighlight, 0);
  }
  if (!highlightColor)
  {
    highlightColor = "#ffc0da"; // Set default for DOM-challenged browsers
    xbDump("Using defaut highlight color = " + highlightColor);
  }
  else
  {
    xbDump("Found CSS highlight color = " + highlightColor);
  }

  //----------------------------------------------------//

  // Finally combine the last 2 results to generate the SetNodeColorClass function.
  if (useCssClass)
  {
    // DOM-compliant browsers
    SetNodeColorClass = function(node, colorClass) 
      { 
        xbDump("SetNodeColorClass(" + colorClass + ")");
        node.className = colorClass;  // Use the td.highlight color from mrbs.css.php.
      }
  }
  else
  {
    // Pre-DOM browsers like Opera 6
    SetNodeColorClass = function(node, colorClass) 
      {
        xbDump("SetNodeColorClass(" + colorClass + ")");
        if (colorClass == "highlight")
        {
          colorClass = highlightColor; // Cannot use the CSS color class. Use the color computed above.
        }
        xblSetNodeBgColor(node, colorClass);
      }
  }
}

//----------------------------------------------------//

// Cell activation
function HighlightNode(node) // Change one td cell color class
{
  node.oldColorClass = GetNodeColorClass(node);
  SetNodeColorClass(node, "highlight");
}

// Activate the td cell under the mouse, and optionally the corresponding
// hour cells on both sides of the table.
function ActivateCell(cell)
{
  if (cell.isActive)
  {
    return; // Prevent problems with reentrancy. (It happens on slow systems)
  }
  cell.isActive = true;
  if (statusBarMsg)
  {
    window.status = statusBarMsg; // Write into the status bar.
  }
  // First find the enclosing table data cell.
  for (var tdCell=cell.parentNode; tdCell; tdCell=tdCell.parentNode)
  {
    if (tdCell.tagName == "TD")
    {
      break;
    }
  }
  if (!tdCell)
  {
    return;
  }
  HighlightNode(tdCell);
  if (highlight_left_column)
  {
    // Locate the head node for the current row.
    var leftMostCell = xblFirstSibling(tdCell);
    if (leftMostCell)
    {
      HighlightNode(leftMostCell);
    }
  }
  if (highlight_right_column)
  {
    // Locate the last node for the current row. (Only when configured to display times at right too.)
    var rightMostCell = xblLastSibling(tdCell);
    // Now work around a Netscape peculiarity: The #text object is a sibling and not a child of the TD!
    while (rightMostCell && (rightMostCell.tagName != "TD"))
    {
      rightMostCell = rightMostCell.previousSibling;
    }
    if (rightMostCell) HighlightNode(rightMostCell);
  }
}

// Cell unactivation
function UnactivateCell(cell)
{
  if (!cell.isActive)
  {
    return; // Prevent problems with reentrancy.
  }
  cell.isActive = null;
  window.status = ""; // Clear the status bar.
  // First find the enclosing table data cell.
  for (var tdCell=cell.parentNode; tdCell; tdCell=tdCell.parentNode)
  {
    if (tdCell.tagName == "TD")
    {
      break;
    }
  }
  if (!tdCell)
  {
    return;
  }
  SetNodeColorClass(tdCell, tdCell.oldColorClass);
  if (highlight_left_column)
  {
    // Locate the head node for the current row.
    var leftMostCell= xblFirstSibling(tdCell);
    if (leftMostCell)
    {
      SetNodeColorClass(leftMostCell, leftMostCell.oldColorClass);
    }
  }
  if (highlight_right_column)
  {
    // Locate the last node for the current row. (Only when configured to display times at right too.)
    var rightMostCell = xblLastSibling(tdCell);
    // Now work around a NetScape peculiarity: The #text object is a sibling and not a child of the TD!
    while (rightMostCell && (rightMostCell.tagName != "TD"))
    {
      rightMostCell = rightMostCell.previousSibling;
    }
    if (rightMostCell)
    {
      SetNodeColorClass(rightMostCell, rightMostCell.oldColorClass);
    }
  }
}

xbDump("Cell activation routines defined.");

//----------------------------------------------------//

// Cell click handling

// Callback used to find the A link inside the cell clicked.
function GotoLinkCB(node, ref)
{
  var tag = null;
  if (node.tagName)
  {
    tag = node.tagName; // DOM-compliant tag name.
  }
  else if (node.nodeName)
  {
    tag = node.nodeName; // Implicit nodes, such as #text.
  }
  if (tag && (tag.toUpperCase() == "A"))
  {
    return node;
  }
  return null;
}

// Handler for going to the period reservation edition page.
function GotoLink(node)
{
  xbDump("GotoLink()");
  link = ForEachDescendant(node, GotoLinkCB, null);
  if (link)
  {
    window.location = link.href;
  }
}

xbDump("Cell click handlers defined.");

//----------------------------------------------------//

// Cell content generation

function BeginActiveCell()
{
  if (useJS)
  {
    document.write("<table class=\"naked\" cellSpacing=\"0\" onMouseOver=\"ActivateCell(this)\" onMouseOut=\"UnactivateCell(this)\" onClick=\"GotoLink(this)\">\n<td class=\"naked\" style=\"border: 0\">\n");
    // Note: The &nbsp; below is necessary to fill-up the cell. Empty cells behave badly in some browsers.
    if (!show_plus_link)
    {
      document.write("&nbsp;<div style=\"display:none\">\n"); // This will hide the (+) link.
    }
  }
}

function EndActiveCell()
{
  if (useJS)
  {
    if (!show_plus_link)
    {
      document.write("</div>");
    }
    document.write("</td></table>\n");
  }
}

xbDump("Cell content generation routines defined.");

//----------------------------------------------------//

if (doAlert)
{
  alert("Ended xbLib.js");
}
xbDump("Ended xbLib.js.php");

# sterlingdesign/dom

## Functional Enhancements for PHP's Document Object Model (DOM)

### Overview

This package extends the existing PHP DOM with functionality that is missing from the
core library.  The most important and useful feature is the automation of the
use of XPath to select one or more nodes, set element or attribute values, and get
those element or attribute values.  The ElementNode is also extended in order
to automate the creation of children nodes, including shortcuts for some common
HTML/XHTML element nodes.

### Installation

`composer require sterlingdesign/DOM`

### Basic Usage

Here are a few examples of some features (see test/testdom.php):

    <?php
    require_once __DIR__ . "/../../../autoload.php";
    
    $oDoc = new \Sterling\DOM\Document();
    
    // selectSingleElement(string $strXPath, bool $bCreateIfNotPresent)
    //
    // $strXPath - The first parameter is the XPath to the element to get.
    // $bCreateIfNotPresent - The second parameter, when set to 'true',
    //   will create the element if it does not exist, if possible.
    //   Note: yes, you could give an XPath that doesn't evaluate to
    //   a specific element, but the function will return NULL.
    
    $oRoot = $oDoc->selectSingleElement("/root", true);
    
    // ElementNode::createChild(string $strFQN, string $strVal [,less common params]);
    //
    // creates an element node named "foo" under the $oRoot ElementNode,
    // and sets it's value to "bar 1"
    $oFoo1 = $oRoot->createChild("foo", "bar 1");
    // creates another child element of $oRoot:
    $oFoo2 = $oRoot->createChild("foo", "bar 2");
    
    // setValue(string|bool|int|float $val, string $strXPath = ".")
    //
    // The first parameter is the POD type value to set.
    // The second parameter is the XPath of the element to set the value.
    $oRoot->setValue("bar X", "hello");
    // If the first parameter is a bool, the bool is converted to
    // the string "true" if true, or "false" if false.  This is for
    // readability of the resulting XML.
    $oRoot->setValue(true, "world");
    // Numbers are saved by strval($num)
    $oRoot->setValue(3.14, "PiAproximation");
    
    // There are also specific functions for specific POD types for
    // getting and setting values: setIntValue, setFloatValue, setBoolValue,
    // and setStringValue
    $oBool = $oRoot->createChild("MyBool");
    $oBool->setBoolValue(false);
    
    // When retrieving values from a DOM, a return value of NULL
    // indicates that the element or attribute was not found:
    $valMyOtherBool = $oRoot->getBoolValue("MyOtherBool");
    if(is_null($valMyOtherBool))
      echo "my-other-bool Was Not Set!!" . PHP_EOL;
    
    // Most of the time, you don't want to have to deal with the
    // possiblity of a NULL return value, and you probably have
    // a DEFAULT value for those cases.  The second parameter
    // to any of the getXXXValue functions is a default
    // value to be returned if the node does not exist.
    // This way, the return value is guaranteed to be of the
    // expected type
    $valMyOtherBool = $oRoot->getBoolValue("MyOtherBool", false);
    echo "MyOtherBool is " . ($valMyOtherBool ? "true" : "false") . PHP_EOL;
    
    // You can create a deeper tree of elements:
    $oRoot->setValue("Deep Value", "my/deep/path");
    // and also set attribute values of elements
    $oRoot->setValue("Deep Attribute Value", "my/deep/path/@myAttribute");
    
    // If there are more than one sibling elements with the
    // same name, the retrieved element is the first
    // element in document order
    $oFoo1 = $oRoot->selectSingleElement("foo");
    if(is_object($oFoo1))
      echo $oFoo1->getStringValue() . PHP_EOL;
    else
      echo "There are no foo elements" . PHP_EOL;
    
    
    // You can get the value of XPath expressions
    echo "There are " . $oRoot->getStringValue("count(foo)") . " foo elements under the document root". PHP_EOL;
    // or use XPath to select a specific element
    $oFoo2 = $oRoot->selectSingleElement("foo[2]");
    if(is_object($oFoo2))
      echo "The value of the second foo element is: '{$oFoo2->getStringValue()}'" . PHP_EOL;
    else
      echo "The second foo element does not exists" . PHP_EOL;
    
    echo PHP_EOL . "*** DOCUMENT ***" . PHP_EOL;
    $oDoc->formatOutput = true;
    echo $oDoc->saveXML();
    
    
### Security Considerations

Most likely, these basic useage features are not included in the base libxml (or PHP\DOM) libraries
for this reason: if mis-used, these types of functions could lead to security issues.

For starters, it's important to understand how XPath works.  Don't put unreasonable xpath
statements in your code.

Secondly, don't allow user input into your XPath statements: always use hard coded or 
programmer generated XPath statements, never take from user input without extreem prejudace.

With a little common sense, we believe the value of these enhancements will become
apparent to anyone who hasn't already implemented something similar themselves

### Roadmap

This library has only been used by a limited number of developers so far,
so there very well could be bugs that we haven't encountered yet because our
useage patterns don't do things the way you might.  The primary focus for
the initial realease will be addressing any glaring problems.

There are a hand-full of known improvements that will hopefully be
implemented by the next major version release.

Beyond that, maintaining compatibility with libxml/libxsl will be the
main goal.  New Features/Breaking Changes will be possible but only
if they sound like good ideas.

#### THANKS FOR LOOKING AND MUCH SUCCESS TO YOU

##### Also thanks to the hundreds of men and women who have worked on these technologies to make it possible.  Will hopefully add some credits in the future, but the list will be long.

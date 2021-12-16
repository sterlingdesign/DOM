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


<?php
declare(strict_types=1);


namespace Sterling\DOM;


class Document extends \DOMDocument
{
use TEnhancements;
protected $m_arNsMap = array();

//-------------------------------------------------------------------------------------------------
public function __construct($version = '1.0', $encoding = 'UTF-8')
{
  parent::__construct($version, $encoding);
  $this->registerNodeClass("\DOMNode", "\Sterling\DOM\NodeEnhanced");
  $this->registerNodeClass("\DOMElement", "\Sterling\DOM\ElementNode");
  $this->registerNodeClass("\DOMAttr", "\Sterling\DOM\AttributeNode");
  $this->registerNodeClass("\DOMCdataSection", "\Sterling\DOM\CdataSectionNode");
  $this->registerNodeClass("\DOMComment", "\Sterling\DOM\CommentNode");
  $this->registerNodeClass("\DOMDocumentFragment", "\Sterling\DOM\DocumentFragment");
  $this->registerNodeClass("\DOMEntityReference", "\Sterling\DOM\EntityReferenceNode");
  $this->registerNodeClass("\DOMProcessingInstruction", "\Sterling\DOM\ProcessingInstructionNode");
  $this->registerNodeClass("\DOMText", "\Sterling\DOM\TextNode");
}
//-------------------------------------------------------------------------------------------------
public function createElement($name, $value = "") : ?ElementNode
{
  /** @var ElementNode $elem */
  $elem = parent::createElement($name, $value);
  if(is_bool($elem))
    $elem = null;
  return $elem;
}
//-------------------------------------------------------------------------------------------------
public function RegisterNs(string $prefix, string $uri)
{
  $this->m_arNsMap[$prefix] = $uri;
}
//-------------------------------------------------------------------------------------------------
public function GetRegisteredNsArray() : array
{
  return $this->m_arNsMap;
}
//-------------------------------------------------------------------------------------------------
public function LoadFromJson(string $json, string $rootName = "root") : bool
{
  $safeName = $this->MakeSafeName($rootName);
  if(strlen($safeName) == 0)
    {
    $this->loadXML("<InvalidRootName/>");
    $this->documentElement->setAttribute("name", $rootName);
    }
  else
    $this->loadXML("<" . $safeName . "/>");

  $oRootNode = $this->selectSingleElement("/*");
  $arr = null;
  try
    {
    $arr = json_decode($json, true, 128, JSON_BIGINT_AS_STRING|JSON_INVALID_UTF8_IGNORE|JSON_THROW_ON_ERROR);
    }
  catch(\Throwable $throwable)
    {
    LogError($throwable);
    return false;
    }

  if(!is_array($arr))
    return false;

  return $this->array2xml($arr, $oRootNode);
}
//-------------------------------------------------------------------------------------------------
protected function array2xml(array $array, ElementNode $oNode) : bool
{
  $bOk = true;
  try
    {
    foreach($array as $key => $value)
      {
      $safeName = $this->MakeSafeName(strval($key));
      if(strlen($safeName) == 0)
        {
        $oChild = $oNode->createChild("InvalidName");
        $oChild->setAttribute("name", htmlspecialchars(strval($key)));
        }
      else
        $oChild = $oNode->createChild($safeName);
      if(is_array($value))
        $bOk = $this->array2xml($value, $oChild);
      else if(is_null($value))
        $oChild->setAttribute("null", "true");
      else
        $oChild->setStringValue(strval($value));
      if(!$bOk)
        break;
      }
    }
  catch(\Throwable $throwable)
    {
    LogError($throwable);
    $bOk = false;
    }

  return $bOk;
}
//-------------------------------------------------------------------------------------------------
public function MakeSafeName(string $name) : string
{
  $name = str_replace(" ", "-", $name);
  $name = preg_replace("[^\w\d_-]", "", $name);
  // the first character of the name must be a word character, can't
  //  be a hiphen, underscore or digit
  while(strlen($name) && preg_match("/^[\d_-]/", $name))
    $name = substr($name, 1);
  return $name;
}
}
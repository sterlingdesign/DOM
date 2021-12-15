<?php
declare(strict_types=1);

namespace Sterling\DOM;

trait TEnhancements
{
//-----------------------------------------------------------------------------
public function setValue($val, string $path = ".")
{
  if(is_bool($val))
    $this->setBoolValue($val, $path);
  else if(is_int($val))
    $this->setIntValue($val, $path);
  else if(is_float($val))
    $this->setFloatValue($val, $path);
  else
    $this->setStringValue(strval($val), $path);
}
//-----------------------------------------------------------------------------
public function setStringValue(string $strVal, string $path = ".")
{
  $node = $this->selectSingleNode($path, true);
  if($node !== null)
    {
    $ownerDoc = ($node->nodeType == XML_DOCUMENT_NODE ? $node : $node->ownerDocument);
    // remove all text child nodes
    if($node->nodeType == XML_ATTRIBUTE_NODE)
      {
      $node->textContent = "";
      }
    else
      {
      $xpath = new \DOMXPath($ownerDoc);
      $oldTextVals = $xpath->query("text()", $node);
      if(false !== $oldTextVals)
        {
        for($i = 0; $i < $oldTextVals->length; $i++)
          $node->removeChild($oldTextVals->item($i));
        }
      };
    $newTextNode = $ownerDoc->createTextNode($strVal);
    $node->insertBefore($newTextNode, $node->firstChild);
    }
}
//-----------------------------------------------------------------------------
public function setBoolValue(bool $bVal, string $path = ".")
{
  $strVal = ($bVal ? "true" : "false");
  $this->setStringValue($strVal, $path);
}
//-----------------------------------------------------------------------------
public function setIntValue(int $iVal, string $path = ".")
{
  $this->setStringValue(strval($iVal), $path);
}
//-----------------------------------------------------------------------------
public function setFloatValue(float $dVal, string $path = ".")
{
  $this->setStringValue(strval($dVal), $path);
}
//-----------------------------------------------------------------------------
public function getStringValue(string $path = ".", ?string $default = null) : ?string
{
  $strVal = $default;
  $refNode = ($this->nodeType == XML_DOCUMENT_NODE ? null : $this);
  /** @var \Sterling\DOM\Document $ownerDoc */
  $ownerDoc = ($this->nodeType == XML_DOCUMENT_NODE ? $this : $this->ownerDocument);
  $oXpath = new \DOMXPath($ownerDoc);
  $arNsMap = $ownerDoc->GetRegisteredNsArray();
  foreach($arNsMap as $key=>$val)
    $oXpath->registerNamespace($key, $val);
  $result = $oXpath->evaluate($path, $refNode);
  if($result === false)
    {
    LogError("Failure of DOMXPath::evaluate" . libxml_get_last_error()->message);
    }
  else if(is_object($result))
    {
    if(get_class($result) == "DOMNodeList")
      {
      /** @var \DOMNodeList $result */
      if($result->length)
        $strVal = "";
      for($i = 0; $i < $result->length; $i++)
        $strVal .= $result->item($i)->textContent;
      }
    else
      LogError("Unexpected object returned from DOMXPath::evaluate: " . get_class($result));
    }
  else
    $strVal = strval($result);

  return $strVal;
}
//-----------------------------------------------------------------------------
public function getBoolValue(string $path = ".", ?bool $default = null) : ?bool
{
  $bVal = $default;
  $strVal = $this->getStringValue($path, (null === $default ? null : ($default ? "true" : "false")));
  if(null !== $strVal)
    {
    $strVal = strtolower($strVal);
    if("true" == $strVal || "yes" == $strVal || (is_numeric($strVal) && intval($strVal) != 0))
      $bVal = true;
    else
      $bVal = false;
    }
  return $bVal;
}
//-----------------------------------------------------------------------------
public function getIntValue(string $path = ".", ?int $default = null) : ?int
{
  $iVal = $default;
  $strVal = $this->getStringValue($path, (null === $default ? null : (strval($default))));
  if(null !== $strVal)
    {
    if(is_numeric($strVal))
      $iVal = intval($strVal);
    else
      LogError("Result of XPath query is not numeric: " . $path . " Evaluation Context: " . $this->getNodePath());
    }
  return $iVal;
}
//-----------------------------------------------------------------------------
public function getFloatValue(string $path = ".", ?float $default = null) : ?float
{
  $dVal = $default;
  $strVal = $this->getStringValue($path, (null === $default ? null : strval($default)));
  if(null != $strVal)
    {
    if(is_numeric($strVal))
      $dVal = floatval($strVal);
    else
      LogError("Result of XPath query is not numeric: " . $path . " Evaluation Context: " . $this->getNodePath());
    }
  return $dVal;
}
//-----------------------------------------------------------------------------
public function getChildrenAsAssociativeArray() : array
{

  $arVal = array();
  $Children = $this->selectNodes("./*");
  for($i = 0; $i < $Children->length; $i++)
    {
    $key = "";
    $val = "";
    /** @var \Sterling\DOM\NodeEnhanced $item */
    $item = $Children->item($i);
    $key = $item->nodeName;
    if($item->selectNodes("./*")->length > 0)
      $val = $item->getChildrenAsAssociativeArray();
    else
      $val = $item->textContent;
    if(isset($arVal[$key]))
      {
      $bNeedsRePush = false;
      if(!is_array($arVal[$key]))
        {
        $bNeedsRePush = true;
        }
      else
        {
        $arKeys = array_keys($arVal[$key]);
        foreach($arKeys as $lastKey)
          {
          if(!is_int($lastKey))
            {
            $bNeedsRePush = true;
            break;
            }
          }
        }
      if($bNeedsRePush)
        {
        $lastval = $arVal[$key];
        $arVal[$key] = array($lastval);
        }
      array_push($arVal[$key], $val);
      }
    else
      $arVal[$key] = $val;
    }

  return $arVal;
}
//-----------------------------------------------------------------------------
public function selectSingleElement(string $path, bool $bCreateIfNotPresent = false) : ?\Sterling\DOM\ElementNode
{
  /** @var \Sterling\DOM\ElementNode $node */
  $node = $this->selectSingleNode($path, $bCreateIfNotPresent);
  if(!is_null($node) && $node->nodeType === XML_ELEMENT_NODE)
    return $node;
  return null;
}
//-----------------------------------------------------------------------------
public function selectSingleNode(string $path, bool $bCreateIfNotPresent = false) : ?\DOMNode
{
  /** @var NodeEnhanced $node */
  $node = null;
  $refNode = ($this->nodeType == XML_DOCUMENT_NODE ? null : $this);
  /** @var Document $ownerDoc */
  $ownerDoc = ($this->IsDocumentNode() ? $this : $this->ownerDocument);
  $oXpath = new \DOMXPath($ownerDoc);
  $arNsMap = $ownerDoc->GetRegisteredNsArray();
  foreach($arNsMap as $key=>$val)
    $oXpath->registerNamespace($key, $val);
  $oNodeList = $oXpath->query($path, $refNode);
  if(false === $oNodeList)
    {
    LogError("Failure of DOMXPath::query using {$path}");
    }
  else if($oNodeList->length > 0)
    {
    $node = $oNodeList->item(0);
    }
  else if($bCreateIfNotPresent)
    {
    $arPath = explode('/', $path);
    $strLastStep = array_pop($arPath);
    if(count($arPath) > 1 || (count($arPath) == 1 && !empty($arPath[0])))
      $node = $this->selectSingleNode(implode('/',$arPath), true);
    else
      $node = $this;
    if(null != $node)
      $node = $node->appendNewStepNode($strLastStep);
    }

  return $node;
}
//-----------------------------------------------------------------------------
public function appendNewStepNode(string $strStep) : ?\DOMNode
{
  /** @var NodeEnhanced $node */
  $node = null;
  /** @var Document $ownerDoc */
  $ownerDoc = ($this->IsDocumentNode() ? $this : $this->ownerDocument);
  $stepInfo = new XpathStepInfo($strStep);
  if($stepInfo->hasQualifier())
    LogError("Can't auto-create node specified with a qualifier []");
  else
    {
    switch($stepInfo->getType())
      {
      case XpathStepInfo::STEP_TYPE_ELEMENT:
        {
        $oElem = $ownerDoc->createElement($stepInfo->getFQName());
        $node = $this->appendChild($oElem);
        break;
        }
      case XpathStepInfo::STEP_TYPE_ATTRIBUTE:
        {
        $oAttr = $ownerDoc->createAttribute($stepInfo->getFQName());
        $node = $this->appendChild($oAttr);
        break;
        }
      default:
        {
        LogError("Can't auto-create node specified by the step: " . $strStep);
        break;
        }
      }
    }
  return $node;
}
//-----------------------------------------------------------------------------
public function selectNodes(string $path, bool $bCreateIfNotPresent = false) : \DOMNodeList
{
  $refNode = ($this->nodeType == XML_DOCUMENT_NODE ? null : $this);
  /** @var \Sterling\DOM\Document $ownerDoc */
  $ownerDoc = ($this->IsDocumentNode() ? $this : $this->ownerDocument);
  $oXpath = new \DOMXPath($ownerDoc);
  $arNsMap = $ownerDoc->GetRegisteredNsArray();
  foreach($arNsMap as $key=>$val)
    $oXpath->registerNamespace($key, $val);
  $oNodeList = $oXpath->query($path, $refNode);
  if(false == $oNodeList)
    {
    LogError(libxml_get_last_error());
    $oNodeList = new \DOMNodeList();
    }
  return $oNodeList;
}
//-----------------------------------------------------------------------------
public function IsDocumentNode()
{
  switch($this->nodeType)
    {
    case XML_DOCUMENT_NODE:
    case XML_HTML_DOCUMENT_NODE:
      $bIs = true;
      break;
    default:
      $bIs = false;
      break;
    }
  return $bIs;
}
//-----------------------------------------------------------------------------
public function removeAllChildNodes()
{
  $oOldContent = $this->selectNodes("node()");
  foreach($oOldContent as $oldNode)
    $this->removeChild($oldNode);
}
}

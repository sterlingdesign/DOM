<?php
declare(strict_types=1);
namespace Sterling\DOM;

class ElementNode extends \DOMElement
{
use TEnhancements;
//-----------------------------------------------------------------------------
public function createChild(string $strFQName, string $strValue = "", bool $bInsertAsFirst = false, string $strNamespaceURI = null) : ?\Sterling\DOM\ElementNode
{
  $info = new XpathStepInfo($strFQName);
  if($info->getType() != XpathStepInfo::STEP_TYPE_ELEMENT)
    {
    LogError("Invalid Name for child element: " . $strFQName);
    return null;
    }

  /** @var \Sterling\DOM\ElementNode $child */
  $child = null;
  if(!empty($strNamespaceURI))
    {
    if(empty($info->getPrefix()))
      {
      if(!$this->isDefaultNamespace($strNamespaceURI))
        {
        $strPrefix = $this->lookupPrefix($strNamespaceURI);
        if(empty($strPrefix))
          {
          $strPrefix = generateRandomString(4, "abcdefghijklmnopqrstuvwxyz");
          while(!empty($this->lookupNamespaceUri($strPrefix)))
            $strPrefix = generateRandomString(5, "abcdefghijklmnopqrstuvwxyz");
          LogDebug("WARNING: generating namespace prefix for " . $strNamespaceURI);
          }
        $info->setPrefix($strPrefix);
        }
      }
    $child = $this->ownerDocument->createElementNS($strNamespaceURI, $info->getFQName());
    }
  else
    $child = $this->ownerDocument->createElement($info->getFQName());
  if(!is_null($child) && mb_strlen($strValue) > 0)
    $child->appendText($strValue);

  if($bInsertAsFirst)
    $child = $this->insertBefore($child, $this->firstChild);
  else
    $child = $this->appendChild($child);

  return $child;
}
//-----------------------------------------------------------------------------
public function renameNode(string $NewTagName) : ?\Sterling\DOM\ElementNode
{
  if($this->nodeType === XML_ELEMENT_NODE && $this->parentNode !== null && $this->ownerDocument !== null)
    {
    /** @var \Sterling\DOM\ElementNode $repl */
    $repl = $this->ownerDocument->createElement($NewTagName);
    if($repl)
      {
      $AttList = $this->selectNodes("@*");
      /** @var \Sterling\DOM\AttributeNode $att */
      foreach($AttList as $att)
        $repl->appendChild($att->cloneNode(true));
      $ChildList = $this->selectNodes("./node()");
      /** @var \Sterling\DOM\NodeEnhanced $child */
      foreach($ChildList as $child)
        $repl->appendChild($child->cloneNode(true));
      return $this->parentNode->replaceChild($repl, $this);
      }
    }
  return null;
}
//-----------------------------------------------------------------------------
public function appendText(string $strVal) : \Sterling\DOM\ElementNode
{
  if(!is_null($this->ownerDocument))
    $this->appendChild($this->ownerDocument->createTextNode($strVal));
  return $this;
}
//-----------------------------------------------------------------------------
public function appendEntityRef(string $strVal) : \Sterling\DOM\ElementNode
{
  if(empty($strVal))
    return $this;
  // workarround for numeric entities
  if(preg_match("/^[#]{1}[x]{0,1}[\d]{1,4}$/", $strVal))
    $this->appendXML("&" . $strVal . ";");
  else
    {
    $ref = $this->ownerDocument->createEntityReference($strVal);
    if(false !== $ref)
      $this->appendChild($ref);
    else if(libxml_get_last_error())
      LogDebug(libxml_get_last_error()->message);
    }

  return $this;
}
//-----------------------------------------------------------------------------
public function appendXML(string $strXml) : \Sterling\DOM\ElementNode
{
  if(empty($strXml))
    return $this;
  $frag = $this->ownerDocument->createDocumentFragment();
  if($frag->appendXML($strXml))
    $this->appendChild($frag);
  else if(libxml_get_last_error())
    LogDebug(libxml_get_last_error()->message);
  return $this;
}
//-------------------------------------------------------------------------------------------------
function AppendXhttpLink(string $strLabel, string $strHref, string $strClass = "", string $strId = "") : \Sterling\DOM\ElementNode
{
  $l = $this->createChild("a", $strLabel);
  $l->setAttribute("href", "javascript:void(0)");
  $l->setAttribute("onclick", "GetBodyContent('{$strHref}', '{$strId}')");
  if(!empty($strClass))
    $l->setAttribute("class", $strClass);
  return $l;
}
//-------------------------------------------------------------------------------------------------
function AppendALink(string $strLabel, string $strHref, string $strClass = "", string $strStyle = "") : \Sterling\DOM\ElementNode
{
  $l = $this->createChild("a", $strLabel);
  $l->setAttribute("href", $strHref);
  if(strlen($strClass))
    $l->setAttribute("class", $strClass);
  if(strlen($strStyle))
    $l->setAttribute("style", $strStyle);

  return $l;
}
//-------------------------------------------------------------------------------------------------
public function createDiv(?string $strClass = null, ?string $strStyle = null, string $strValue = "", bool $bInsertAsFirst = false, string $strNamespaceURI = null) : ?\Sterling\DOM\ElementNode
{
  $div = $this->createChild("div", $strValue, $bInsertAsFirst, $strNamespaceURI);
  if($div && !is_null($strClass) && strlen($strClass) > 0)
    $div->setAttribute("class", $strClass);
  if($div && !is_null($strStyle) && strlen($strStyle) > 0)
    $div->setAttribute("style", $strStyle);
  return $div;
}
}
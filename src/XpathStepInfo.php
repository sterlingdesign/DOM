<?php
declare(strict_types=1);

namespace Sterling\DOM;


class XpathStepInfo
{
var $m_strOriginalStep;
var $m_strNamespaceId;
var $m_strLocalName;
var $m_strQualifier;

const STEP_TYPE_ATTRIBUTE = 1;
const STEP_TYPE_ELEMENT = 2;
const STEP_TYPE_OTHER = 0;
//------------------------------------------------------------------------------------
public function __construct(string $step)
{
  $this->m_strOriginalStep = trim($step);
  $this->m_strNamespaceId = "";
  $this->m_strLocalName = "";
  $this->m_strQualifier = "";
  $this->m_iType = self::STEP_TYPE_OTHER;

  // examples of possible steps:
  //  element-name
  //  namespaceid:element-name
  //  @attr-name
  //  @namespaceid:attr-name
  //  element-name[qualifier]
  //  @attr-name[qualifier]

  //  anything else we won't handle here, like functions and
  //  NODE TESTS: node(), text(), child(), self() ...

  if(false === strpos($step, '(') && false === strpos($step, ')') && strlen($step) > 0)
    {
    if($step[0] == '@')
      {
      $this->m_iType = self::STEP_TYPE_ATTRIBUTE;
      $step = substr($step, 1);
      }
    else // TO DO: Should test the first character to make sure it is a valid start of name character
      $this->m_iType = self::STEP_TYPE_ELEMENT;

    $iQualifierStart = strpos($step, '[');
    if($iQualifierStart !== false)
      {
      $this->m_strQualifier = substr($step, $iQualifierStart);
      if($iQualifierStart > 0)
        $step = substr($step, 0, $iQualifierStart);
      else
        $step = "";
      }
    $iNsIdStart = strpos($step, ':');
    if($iNsIdStart !== false)
      {
      $this->m_strLocalName = substr($step, 0, $iNsIdStart);
      $this->m_strNamespaceId = substr($step, $iNsIdStart + 1);
      }
    else
      $this->m_strLocalName = $step;
    }
}
//------------------------------------------------------------------------------------
public function getType() : int
{
  return $this->m_iType;
}
//------------------------------------------------------------------------------------
public function getFQName() : string
{
  if(!empty($this->m_strNamespaceId))
    return trim($this->m_strNamespaceId) . ":" . trim($this->m_strLocalName);
  else
    return trim($this->m_strLocalName);
}
//------------------------------------------------------------------------------------
public function getPrefix() : string
{
  return $this->m_strNamespaceId;
}
//------------------------------------------------------------------------------------
public function setPrefix(string $strPrefix)
{
  $this->m_strNamespaceId = $strPrefix;
}
//------------------------------------------------------------------------------------
public function getQualifier() : string
{
  return $this->m_strQualifier;
}
//------------------------------------------------------------------------------------
public function hasQualifier() : bool
{
  return !empty($this->m_strQualifier);
}
}
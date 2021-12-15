<?php
declare(strict_types=1);
namespace Sterling\DOM;
use Psr\Http\Message\ResponseInterface;
use Sterling\ResourceMap;
use Sterling\Response\Normal;
use Sterling\RouteLoader;
use Sterling\Utility\MemoryLogStore;
use \DOMNode;

class GenericResponseDocument extends Document
{
  const FULL_RESPONSE_FALLBACK_XSL = "FullResponse.xsl";
  const XMLHTTP_RESPONSE_FALLBACK_XSL = "XMLHttpResponse.xsl";
  const DEBUGBAR_TRAIT_FALLBACK_NAME = "DebugBar";

  protected $m_arCurrentEndpointClass = array();
  protected $m_oRouteLoader = null;
  protected $m_oResourceMap = null;

//-------------------------------------------------------------------------------------------------
public function __construct($version = '1.0', $encoding = 'UTF-8')
{
  parent::__construct($version, $encoding);
  $oRoot = $this->appendChild($this->createElement("response"));
  $oRoot->createChild("HtmlHead");
  $oRoot->createChild("BodyContent");
  $this->SetHttpCode(HTTP_CODE_OK);
  $this->m_oRouteLoader = RouteLoader::getInstance();
  $this->m_arCurrentEndpointClass = $this->m_oRouteLoader->getTargetClassAsArray();
  $this->m_oResourceMap = ResourceMap::getInstance();

  $this->AddMenusAndResources();
}
//-------------------------------------------------------------------------------------------------
public function getBody() : ElementNode
{
  /** @var ElementNode $BodyContent */
  $BodyContent = $this->selectSingleNode("/response/BodyContent", true);
  return $BodyContent;
}
//-------------------------------------------------------------------------------------------------
public function getHead() : ElementNode
{
  /** @var ElementNode $HtmlHead */
  $HtmlHead = $this->selectSingleNode("/response/HtmlHead", true);
  return $HtmlHead;
}
//-------------------------------------------------------------------------------------------------
public function SetPageTitle(string $strTitle)
{
  $oTitle = $this->selectSingleElement("/response/HtmlHead/title", true);
  if($oTitle)
    $oTitle->setStringValue($strTitle);
}
//-------------------------------------------------------------------------------------------------
public function addHeadStylesheet(string $strCssPath, string $strLocalStyleDir = "")
{
  if(strlen($strCssPath) > 0)
    {
    $oHead = $this->getHead();
    $oCssLink = $oHead->createChild("link");
    $oCssLink->setAttribute("rel", "stylesheet");
    $oCssLink->setAttribute("href", $this->fixupLocalResourceVersion($strCssPath, $strLocalStyleDir));
    }
  else
    LogError("Empty strCssPath in addHeadStylesheet");
}
//-------------------------------------------------------------------------------------------------
public function addHeadScript(string $strScriptPath, string $strLocalScriptDir = "", ?array $arParams = null)
{
  if(strlen($strScriptPath)>0)
    {
    $oHead = $this->getHead();
    $oJs = $oHead->createChild("script");
    //if(substr($strScriptPath, -3) == ".js")
      //$oJs->setAttribute("type", "text/javascript");
    $oJs->setAttribute("src", $this->fixupLocalResourceVersion($strScriptPath, $strLocalScriptDir));
    if(!is_null($arParams))
      {
      foreach($arParams as $attribute=>$value)
        {
        $oJs->setAttribute(strval($attribute), strval($value));
        }
      }
    }
  else
    LogError("Empty strScriptPath in addHeadScript");
}
//-------------------------------------------------------------------------------------------------
protected function importRequiredResource(ElementNode $item)
{
  // TO DO: check for duplicate entries
  $oHtmlHead = $this->getHead();
  $item = $oHtmlHead->ownerDocument->importNode($item, true);
  $item = $oHtmlHead->appendChild($item);
  switch($item->localName)
    {
    case "link":
      {
      if($item->hasAttribute("href"))
        $item->setAttribute("href", $this->fixupLocalResourceVersion($item->getAttribute("href")));
      break;
      }
    case "script":
      {
      if($item->hasAttribute("src"))
        $item->setAttribute("src", $this->fixupLocalResourceVersion($item->getAttribute("src")));
      break;
      }
    default:
      {
      break;
      }
    }
}
//-------------------------------------------------------------------------------------------------
protected function fixupLocalResourceVersion(string $file, string $strFoundPath = "")
{
  $strFileOut = $file;
  // only change the signature if this is a local resource (starts with '/'), and it doesn't already have a query string attached
  try
    {
    if(strlen($file) > 0 && ($file[0] == '/') && (strpos($file, '?') === false))
      {
      if(strlen($strFoundPath) == 0)
        $strFoundPath = \Sterling\Endpoint\StaticResource::FindStaticResourceDirectory($file);
      if(!empty($strFoundPath))
        $strFileOut .= "?v=" . urlencode(strval(filemtime(sdMakeFilePath($strFoundPath, $file))));
      }
    }
  catch(\Throwable $throwable)
    {
    LogError($throwable);
    $strFileOut = $file;
    }

  return $strFileOut;
}
//-------------------------------------------------------------------------------------------------
public function getMainMenu() : ElementNode
{
  $oMainMenu = $this->selectSingleElement("/response/MainMenu", true);
  return $oMainMenu;
}
//-------------------------------------------------------------------------------------------------
public function addMainMenuItem(string $label, string $href = "", string $id = "", bool $bIsTarget = false, string $title = "") : ElementNode
{
  $oMain = $this->getMainMenu();
  return $this->appendMenuLinkItem($oMain, $label, $href, $id, $bIsTarget, $title);
}
//-------------------------------------------------------------------------------------------------
public function getApplicationMenu() : ElementNode
{
  $oMenu = $this->selectSingleElement("/response/ApplicationMenu", true);
  return $oMenu;
}
//-------------------------------------------------------------------------------------------------
public function addAppMenuItem(string $label, string $href, string $id, bool $bIsTarget, string $title = "") : ElementNode
{
  $oAppMenu = $this->getApplicationMenu();
  return $this->appendMenuLinkItem($oAppMenu, $label, $href, $id, $bIsTarget, $title);
}
//-------------------------------------------------------------------------------------------------
public function appendMenuLinkItem(ElementNode $oParent, string $label, string $href, string $id, bool $bIsTarget, string $title = "") : ElementNode
{
  $oLink = $oParent->createChild("Link");
  if(empty($label))
    $label = "Link";
  $oLink->setAttribute("label", $label);
  if(!empty($href))
    $oLink->setAttribute("href", $href);
  if(!empty($id))
    $oLink->setAttribute("id", $id);
  if($bIsTarget)
    $oLink->setAttribute("target", "true");
  if(!empty($title))
    $oLink->setAttribute("title", $title);
  return $oLink;
}
//-------------------------------------------------------------------------------------------------
public function getFooter() : ElementNode
{
  $oFooter = $this->selectSingleElement("/response/footer", true);
  return $oFooter;
}
//-------------------------------------------------------------------------------------------------
public function addXsltInclude(string $strXslFileBaseName)
{
  $root = $this->selectSingleElement("/response", true);
  if(!is_null($root))
    {
    $existing = $root->selectSingleElement("XsltInclude[text()='{$strXslFileBaseName}']");
    if(is_null($existing))
      $root->createChild("XsltInclude", $strXslFileBaseName);
    }
}
//-------------------------------------------------------------------------------------------------
public function getFullResponseStylesheetFile() : string
{
  $strXslFile = $this->m_oResourceMap->getFullResponseXsl($this->m_arCurrentEndpointClass);
  if(strlen($strXslFile) == 0)
    $strXslFile = self::FULL_RESPONSE_FALLBACK_XSL;
  $strFullPath = LocateXslFile($strXslFile);
  if( (strlen($strFullPath) == 0) && ($strXslFile !== self::FULL_RESPONSE_FALLBACK_XSL) )
    $strFullPath = LocateXslFile(self::FULL_RESPONSE_FALLBACK_XSL);
  return $strFullPath;
}
//-------------------------------------------------------------------------------------------------
public function getXMLHttpResponseStylesheetFile() : string
{
  $strXslFile = $this->m_oResourceMap->getXmlHttpResponseXsl($this->m_arCurrentEndpointClass);
  if(strlen($strXslFile) == 0)
    $strXslFile = self::XMLHTTP_RESPONSE_FALLBACK_XSL;
  $strFullPath = LocateXslFile($strXslFile);
  if( (strlen($strFullPath) == 0) && ($strXslFile !== self::XMLHTTP_RESPONSE_FALLBACK_XSL) )
    $strFullPath = LocateXslFile(self::XMLHTTP_RESPONSE_FALLBACK_XSL);
  return $strFullPath;
}
//-------------------------------------------------------------------------------------------------
public function getDebugBarTraitName() : string
{
  $strDebugBar = $this->m_oResourceMap->getDebugBarTraitName($this->m_arCurrentEndpointClass);
  if(strlen($strDebugBar) == 0)
    $strDebugBar = self::DEBUGBAR_TRAIT_FALLBACK_NAME;
  return $strDebugBar;
}
//-------------------------------------------------------------------------------------------------
protected function ensureTitle()
{
  $oTitle = $this->getHead()->selectSingleElement("title", true);
  if(strlen($oTitle->getStringValue(".", "")) == 0)
    $oTitle->setStringValue($this->m_oRouteLoader->getCurrentTargetTitle());
}
//-------------------------------------------------------------------------------------------------
public function SetHtml5Output()
{
  $this->SetOutputContentType("text/html", "<!DOCTYPE html>");
}
//-------------------------------------------------------------------------------------------------
public function SetOutputContentType(string $strContentType, string $strDoctype)
{
  $oRoot = $this->selectSingleElement("/response", true);
  $oRoot->setStringValue($strContentType, "OutputContentType");
  $oRoot->setStringValue($strDoctype, "OutputDoctype");
}
//-------------------------------------------------------------------------------------------------
public function GetContentType() : string
{
  $oContent = $this->selectSingleElement("/*/OutputContentType", false);
  if($oContent)
    return $oContent->getStringValue(".", "");
  else
    return "application/xhtml+xml";
}
//-------------------------------------------------------------------------------------------------
public function GetDocumentTypeString(string $strRootTag) : string
{
  $oDoctype = $this->selectSingleElement("/*/OutputDoctype", false);
  if($oDoctype)
    return $oDoctype->getStringValue(".", "");
  else
    return "<!DOCTYPE {$strRootTag} PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">";
}
//-------------------------------------------------------------------------------------------------
public function GetHttpCode() : int
{
  return $this->getIntValue("/*/@http-code", HTTP_CODE_OK);
}
//-------------------------------------------------------------------------------------------------
public function SetHttpCode(int $iCode)
{
  if($iCode >= 100 && $iCode < 600)
    $this->setIntValue($iCode, "/*/@http-code");
}
//-------------------------------------------------------------------------------------------------
public function TransformToResponse() : ResponseInterface
{
  if(null === $this->documentElement)
    {
    LogError("Document has no root element");
    $this->appendChild($this->createElement("response"));
    }

  $bIsXmlHttp = IsXMLHttpRequest();
  if(IsDebug())
    {
    LogDebug(($bIsXmlHttp ? "Generating XMLHttpResponse" : "Generating Full Response"));
    $this->AddDebugBar();
    }

  if($bIsXmlHttp)
    return $this->GenerateXMLHttpResponse();
  else
    return $this->GenerateFullResponse();
}
//-------------------------------------------------------------------------------------------------
public function GenerateXMLHttpResponse() : ResponseInterface
{
  $xsl = new Document();
  $xsl->strictErrorChecking = false;
  if(!$xsl->load($this->getXMLHttpResponseStylesheetFile()))
    return GenerateFallbackErrorResponse(HTTP_CODE_ERR_INTERNAL, libxml_get_errors(), __FILE__, __LINE__);

  $this->FixupXsltIncludes($xsl);

  $proc = new \XSLTProcessor();

  if(!$proc->importStylesheet($xsl))
    return GenerateFallbackErrorResponse(HTTP_CODE_ERR_INTERNAL, libxml_get_errors(), __FILE__, __LINE__);

  $strXml = $proc->transformToXml($this);
  if(!is_string($strXml))
    return GenerateFallbackErrorResponse(HTTP_CODE_ERR_INTERNAL, libxml_get_errors(), __FILE__, __LINE__);

  $response = new Normal($this->GetHttpCode());
  $response = $response->withHeader("Content-Type", "application/xhtml+xml; charset=UTF-8");
  $response->getBody()->write($strXml);

  // DEBUG:
  //file_put_contents(sdMakeFilePath(APP_TMP_PATH, "LastXMLHttpResponse.xml"), $strXml);

  return $response;

}
//-------------------------------------------------------------------------------------------------
public function GenerateFullResponse() : ResponseInterface
{
  if(class_exists("\\App\\ResponseDocumentFinalizer"))
    \App\ResponseDocumentFinalizer::getInstance()->FullResponseReady($this);
  $this->ensureTitle();

  // DEBUG:
  //$this->formatOutput = true;
  //file_put_contents(sdMakeFilePath(APP_TMP_DIR, "LastFullGenericResponseDocument.xml"), strval($this->saveXML()));

  $xsl = new Document();
  $xsl->strictErrorChecking = false;
  if(!$xsl->load($this->getFullResponseStylesheetFile()))
    return GenerateFallbackErrorResponse(HTTP_CODE_ERR_INTERNAL, libxml_get_errors(), __FILE__, __LINE__);

  $this->FixupXsltIncludes($xsl);

  $proc = new \XSLTProcessor();
  if(!$proc->importStylesheet($xsl))
    return GenerateFallbackErrorResponse(HTTP_CODE_ERR_INTERNAL, libxml_get_errors(), __FILE__, __LINE__);

  $outDoc = $proc->transformToDoc($this);
  if(false === $outDoc)
    return GenerateFallbackErrorResponse(HTTP_CODE_ERR_INTERNAL, libxml_get_errors(), __FILE__, __LINE__);

  $ContentType = $this->GetContentType();
  $strRootTag = "html";
  if($outDoc->documentElement)
    $strRootTag = $outDoc->documentElement->localName;
  $strDoctype = $this->GetDocumentTypeString($strRootTag);
  $outDoc->strictErrorChecking = false;
  $outDoc->formatOutput = true;
  if($outDoc->doctype)
    $outDoc->removeChild($outDoc->doctype);
  //$outDoc->normalizeDocument();
  $body = $strDoctype;
  if(strlen($strDoctype))
    $body .= "\n";

  switch($ContentType)
    {
    case "application/xhtml+xml":
      {
      $outDoc->documentElement->setAttribute("xml:lang", "en");
      $outDoc->documentElement->setAttribute("xmlns", XHTML_NAMESPACE_URI);
      // the noxmldecl directive does not work as of php 7.3x, so avoid by serializing node children
      foreach($outDoc->childNodes as $node)
        {
        // added  LIBXML_NOEMPTYTAG option for compatability testing
        //$body .= $outDoc->saveXML($node);
        $body .= $outDoc->saveXML($node, LIBXML_NOEMPTYTAG);
        $body .= "\n";
        }
      break;
      }
    case "text/html":
      {
      $outDoc->documentElement->setAttribute("lang", "en");
      $body .= $outDoc->saveHTML();
      break;
      }
    default:
      {
      break;
      }
    }

  $response = new Normal($this->GetHttpCode());
  $response = $response->withHeader("Content-Type", "{$ContentType}; charset=UTF-8");
  $response->getBody()->write($body);

  return $response;

}
//-------------------------------------------------------------------------------------------------
protected function AddMenusAndResources()
{
  if(!IsXMLHttpRequest())
    {
    $this->AddMainMenusFromRoutes();
    $this->AddApplicationGroupMenu();
    $arResourceNodes = $this->m_oResourceMap->GetRequiredResourceNodes($this->m_arCurrentEndpointClass);
    foreach($arResourceNodes as $requiredResource)
      $this->importRequiredResource($requiredResource);
    }
}
//-------------------------------------------------------------------------------------------------
protected function AddMainMenusFromRoutes()
{
  $arRouteNodes = $this->m_oRouteLoader->GetMainMenuRouteNodes();
  $oMainMenuNode = $this->getMainMenu();
  foreach($arRouteNodes as $node)
    {
    if(is_array($node) && count($node) > 0)
      {
      $oGroupParentMenu = $this->AddRouteNodeToMenu($node[0], $oMainMenuNode, false);
      for($i = 1; $i < count($node); $i++)
        $this->AddRouteNodeToMenu($node[$i], $oGroupParentMenu, false);
      }
    else if(is_object($node))
      {
      $this->AddRouteNodeToMenu($node, $oMainMenuNode, false);
      }
    }
}
//-------------------------------------------------------------------------------------------------
  protected function AddApplicationGroupMenu()
  {
    $oAppMenuRoot = $this->getApplicationMenu();
    $strCurrentTargetId = $this->m_oRouteLoader->GetCurrentTargetId();
    if(!is_object($oAppMenuRoot))
      return;
    /** @var ElementNode $oGroup */
    $oGroup = $this->m_oRouteLoader->GetApplicationRootNode($strCurrentTargetId);
    if($oGroup && !$oGroup->hasAttribute("noappmenu"))
      {
      $oAppMenuRoot->setAttribute("label", $oGroup->getAttribute("Name"));
      $oAppMenuRoot->setAttribute("id", $oGroup->getAttribute("id"));
      $this->AddGroupRoutesToAppMenu($oGroup, $oAppMenuRoot, $strCurrentTargetId);
      }
  }
//-------------------------------------------------------------------------------------------------
function AddGroupRoutesToAppMenu(ElementNode $oGroup, ElementNode $oMenuRoot, string $strCurrentTargetId)
{
  $MenuList = $oGroup->selectNodes("route[@menu='main' or @menu='app'] | Group[@menu='main' or @menu='app']");
  /** @var ElementNode $oRoute */
  for($i = 0; $i < $MenuList->length; $i++)
    {
    $oRoute = $MenuList->item($i);
    if($oRoute->localName == "Group" && $oRoute->selectNodes("./*[local-name()='route' or local-name()='Group'][@menu]")->length > 0)
      {
      $oSubMenu = $this->AddRouteNodeToMenu($oRoute, $oMenuRoot, false);
      $this->AddGroupRoutesToAppMenu($oRoute, $oSubMenu, $strCurrentTargetId);
      }
    else if($oRoute->localName === "route")
      {
      $bIsTarget = ($strCurrentTargetId == $oRoute->getAttribute("id"));
      $oLink = $this->AddRouteNodeToMenu($oRoute, $oMenuRoot, $bIsTarget);
      // The layout xsl needs a way to know which item is the current target to place the indicator next to.
      // Also, if this is a child of a submenu, the parent Links need to be flagged as being the target so
      // the layout can show the submenu expanded
      if($bIsTarget)
        {
        while($oLink && $oLink->localName == "Link")
          {
          $oLink->setAttribute("target", "true");
          $oLink = $oLink->parentNode;
          }
        }
      }
    }

}

//-------------------------------------------------------------------------------------------------
protected function AddRouteNodeToMenu(ElementNode $oRoute, ElementNode $oMenu, bool $bIsTarget) : ?ElementNode
{
  $label = $oRoute->getAttribute("Name");
  $title = $oRoute->getAttribute("title");
  $id = $oRoute->getAttribute("id");

  // This allows a Group consisting of nothing but Groups:
  // the target for any Group item is the first descendent 'route' node
  if($oRoute->localName == "Group")
    $oRoute = $oRoute->selectSingleElement(".//route");
  if(!is_object($oRoute))
    return null;

  $href = $oRoute->getStringValue("path");
  if(empty($label))
    {
    if($href === "/")
      $label = "Home";
    else
      $label = ucfirst(basename($href));
    if(empty($label))
      $label = ($oMenu->localName == "Link" ? "Menu" : "Link");
    }

  return $this->appendMenuLinkItem($oMenu, $label, $href, $id, $bIsTarget, $title);
}
//-------------------------------------------------------------------------------------------------
protected function FixupXsltIncludes(Document $xsl)
{
  $arIncludeXslFiles = array();

  $oIncludeNodes = $this->selectNodes("/*/XsltInclude"); // addXsltInclude()
  if( !is_null($oIncludeNodes) && ($oIncludeNodes->length > 0) )
    {
    for($i = 0; $i < $oIncludeNodes->length; $i++)
      {
      $strFileBaseName = $oIncludeNodes->item($i)->textContent;
      if(!empty($strFileBaseName))
        array_push($arIncludeXslFiles, $strFileBaseName);
      }
    }

  $prefix = $xsl->lookupPrefix(XSLT_NAMESPACE_URI);
  // It is possible that the prefix is empty (meaning everything without a prefix is in the xsl namespace),
  // but unlikely because that would cause other issues.  Will leave it as a possibility here, so don't
  // include the prefix separator if it is empty:
  if(strlen($prefix) > 0)
    $prefix .= ":";

  // For each of the /*/xsl:include nodes,  resolve their "href" attribute according to
  // stack rules of precedence: Site/Xsl, Domain/Xsl, Framework/Xsl
  $oIncludeNodes = $xsl->selectNodes("/*/{$prefix}include");
  /** @var ElementNode $oInclude */
  foreach($oIncludeNodes as $oInclude)
    {
    $strHref = LocateXslFile($oInclude->getAttribute("href"));
    if(!empty($strHref))
      $oInclude->setAttribute("href", $strHref);
    else
      LogWarning("Unable to locate XSL Include: {$oInclude->getAttribute("href")}");

    }

  $firstChild = $xsl->selectSingleElement("/*/*[1]");
  foreach($arIncludeXslFiles as $basename)
    {
    $href = LocateXslFile($basename);
    if(empty($href))
      LogWarning("Unable to locate XSL Include: {$basename}");
    else
      {
      $inc = $xsl->createElementNS(XSLT_NAMESPACE_URI, $prefix . "include");
      $inc->setAttribute("href", $href);
      $xsl->documentElement->insertBefore($inc, $firstChild);
      }
    }

}
//-------------------------------------------------------------------------------------------------
protected function AddDebugBar()
{
  $strDebugBarTrait = $this->getDebugBarTraitName();
  $this->addXsltInclude($strDebugBarTrait . ".xsl");
  $this->addHeadScript("/clientjs/{$strDebugBarTrait}.js");
  $this->addHeadStylesheet("/style/{$strDebugBarTrait}.css");

  $db = $this->selectSingleElement("/response/DebugBar", true);

  /** @var MemoryLogStore $memLog */
  $memLog = MemoryLogStore::getInstance();
  $this->MakeDebugTextbox($db, "ErrorInfo", print_r($memLog->GetErrorList(), true), $memLog->HasErrors());

  $debugInfo = print_r($memLog->GetDebugInfo(), true);
  $bufferedInfo = GetObContents();
  if(strlen($bufferedInfo))
    $debugInfo .= "\n\nBUFFERED INFO:\n\n" . $bufferedInfo;
  $this->MakeDebugTextbox($db, "DebugInfo", $debugInfo);

  $this->MakeDebugTextbox($db, "_COOKIE", print_r($_COOKIE, TRUE));
  if(session_status() == PHP_SESSION_ACTIVE)
    $this->MakeDebugTextbox($db, "_SESSION", print_r($_SESSION, true));
  else
    $this->MakeDebugTextbox($db, "_SESSION", "PHP SESSION NOT ACTIVE");

  $this->MakeDebugTextbox($db, "_SERVER", print_r($_SERVER, TRUE));

  $this->MakeDebugTextbox($db, "_GET", print_r($_GET, TRUE));
  $this->MakeDebugTextbox($db, "_POST", print_r($_POST, TRUE));
  $this->MakeDebugTextbox($db, "_FILES", print_r($_FILES, TRUE));

  // *** UNUSED / NOTES ***
  // We don't use _ENV
  //MakeDebugTextbox($db, "_ENV", print_r($_ENV, TRUE));
  // Trying to dump $GLOBALS this way can exhaust the available
  //   memory and trigger a failure (TheApp is in GLOBALS).  Should implement
  //   a special walker function that only checks the names and types stored in $GLOBALS
  //DON'T DO IT!!!: MakeDebugTextbox($db, "GLOBALS", print_r($GLOBALS, TRUE));
  // $_REQUEST is a repeat of _POST and/or _GET
  //MakeDebugTextbox($db, "_REQUEST", print_r($_REQUEST, TRUE));
  // headers don't make sense because they get generated from the ResponseInterface After This stage
  //MakeDebugTextbox($db, "headers_list", print_r(headers_list(), TRUE));
  // could be useful, but now we have a dedicated admin page that dumps ALL
  //  PHP configuration, if review is needed
  //MakeDebugTextbox($db, "ini_get_all", print_r(ini_get_all(), TRUE));
}
//-------------------------------------------------------------------------------------------------
protected function MakeDebugTextbox(DOMNode $db, string $strName, string $strText, bool $bInitialDisplay = false)
{
  $strSanitiezedText = sdSanitizeUTF8($strText);
  $item = $db->ownerDocument->createElement("DebugItem", $strSanitiezedText);
  $item->setAttribute("name", $strName);
  $item->setAttribute("display", ($bInitialDisplay ? "block" : "none"));
  $db->appendChild($item);
}

}
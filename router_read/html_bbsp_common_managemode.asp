function stManageFlag(ManageFlag)
{
	this.ManageFlag = ManageFlag;
}

var stManageFlaginfo = new stManageFlag(0);
stManageFlaginfo.ManageFlag = "0";
var CfgModeWord ='INATELKOM2'; 
var CmccRegflag = '0';
var apcmodefeature = '0';

function stFtFlag(HLJCT, SHCT, AHCT, GDCT, JSCT, SZCT, PCCWHK, MOBILY, TELMEX, CQCT, BJCU,JXCT,GSCT,QHCT,GZCT,JLCT,SDCT,HUNCT,PTVDFB,PTVDF, BJUNICOM, CUVOICE, COMMON, DT_HUNGARY)
{
	this.HLJCT = HLJCT;
	this.SHCT = SHCT;
	this.AHCT = AHCT;
	this.GDCT = GDCT;
	this.JSCT = JSCT;
	this.SZCT = SZCT;
	this.PCCWHK = PCCWHK;
	this.MOBILY = MOBILY;
	this.TELMEX = TELMEX;
	this.CQCT = CQCT;
	this.BJCU = BJCU;
	this.JXCT = JXCT;
	this.GSCT = GSCT;
	this.QHCT = QHCT;
	this.GZCT = GZCT;
	this.JLCT = JLCT;
	this.SDCT = SDCT;
	this.HUNCT = HUNCT;
	this.PTVDFB = PTVDFB;
	this.PTVDF = PTVDF;
	this.BJUNICOM = BJUNICOM;
	this.CUVOICE = CUVOICE;
	this.COMMON = COMMON;
	this.DT_HUNGARY = DT_HUNGARY;
}

var ManageCfgWord = new stFtFlag("0", "0", "0", "0", "0", "0", "0", "0", "0", "0","0","0","0","0","0","0","0","0","0","0","0","0","0", "0");
ManageCfgWord.HLJCT = "0";
ManageCfgWord.HUNCT = "0";
ManageCfgWord.GDCT = "0";
ManageCfgWord.SHCT = "0";
ManageCfgWord.AHCT = "0";
ManageCfgWord.JSCT = "0";
ManageCfgWord.SZCT = "0";
ManageCfgWord.PCCWHK = ((CfgModeWord.toUpperCase() == 'PCCWHK') || (CfgModeWord.toUpperCase() == 'PCCW3MAC') || (CfgModeWord.toUpperCase() == 'PCCW4MAC') || (CfgModeWord.toUpperCase() == 'PCCWSMART')) ? "1" : "0";
ManageCfgWord.MOBILY = "0";
ManageCfgWord.TELMEX = "0";
ManageCfgWord.CQCT = "0";
ManageCfgWord.BJCU = "0"; 
ManageCfgWord.GSCT = "0";
ManageCfgWord.QHCT = "0";
ManageCfgWord.GZCT = "0";
ManageCfgWord.JLCT = "0";
ManageCfgWord.JXCT = "0"; 
ManageCfgWord.SDCT = "0";
ManageCfgWord.PTVDFB = "0";
ManageCfgWord.PTVDF = "0";
ManageCfgWord.BJUNICOM = "0";
ManageCfgWord.CUVOICE = "0";
ManageCfgWord.COMMON = ('COMMON' == CfgModeWord.toUpperCase() || 'COMMON2' == CfgModeWord.toUpperCase())?"1" : "0";
ManageCfgWord.DT_HUNGARY = "0";
ManageCfgWord.TRUE = ('TRUE' == CfgModeWord.toUpperCase() || 'TRUEVIDEO' == CfgModeWord.toUpperCase() || 'TRUERG' == CfgModeWord.toUpperCase() || 'TRUEEPA' == CfgModeWord.toUpperCase())?"1" : "0";
ManageCfgWord.OSK = ('OSK' == CfgModeWord.toUpperCase() || 'OSK2' == CfgModeWord.toUpperCase())?"1" : "0";
ManageCfgWord.TELECENTRO = (CfgModeWord.toUpperCase() == "TELECENTRO")?"1" : "0";
ManageCfgWord.CABLEVISION = (CfgModeWord.toUpperCase() == "CABLEVISION2" || CfgModeWord.toUpperCase() == "CABLEVISION")?"1" : "0";

var DBAA1= "0";

function GetManageFlag()
{
    if (ManageCfgWord.GDCT == "1") {
        stManageFlaginfo.ManageFlag = 1;
    }
    return stManageFlaginfo;
}

function GetCfgMode()
{
    return ManageCfgWord;
}

var RunningMode = "0";

function GetRunningMode()
{
	return RunningMode;
}

var ProductName = 'HG8145V5';
function GetProductName()
{
    return ProductName;
}

var CurrentBin = 'COMMON';
var LanUpportFlag = "0";

function GetCurrentBin()
{
	return CurrentBin;
}

function isE8cAndCMCC()
{
    if('E8C' == CurrentBin.toUpperCase() || 'CMCC' == CurrentBin.toUpperCase())
    {
        return true;
    }
    else
    {
        return false;
    }
}

function IsE8cFrame()
{
    if('E8C' == CurrentBin.toUpperCase())
    {
        return true;
    }
    else
    {
        return false;
    }
}

function IsCmcc_rmsMode()
{
	var Custom_cmcc_rms =  '0';
	if ('1' == Custom_cmcc_rms )
	{
		return true;
	}
	else
	{
		return false;
	}	
}


function IsE8cFrameOrCMCC_RMS()
{

    if(('E8C' == CurrentBin.toUpperCase()) || (IsCmcc_rmsMode()))	
    {
        return true;
    }
	else
    {
        return false;
    }	
}

function IsLanUpport()
{
    if((LanUpportFlag == '1') || (CfgModeWord.toUpperCase() == "DMAROCINWI2WIFI")) {
        return true;
    }
    else
    {
        return false;
    }
}

function IsLanUpCanOper()
{
    if ((IsAdminUser() == false) && (CfgModeWord.toUpperCase() == "RDSAP")) {
        return false;
    }

 	if(('1' == LanUpportFlag) && ("1" != ManageCfgWord.BJUNICOM) && (apcmodefeature != "1") ||
        (CfgModeWord.toUpperCase() == "DMAROCINWI2WIFI")) {
        return true;
    }
    else
    {
        return false;
    }
}

function IsLanBJUNICOM()
{
 	if(('1' == LanUpportFlag) && ("1" == ManageCfgWord.BJUNICOM))
    {
        return true;
    }
    else
    {
        return false;
    }
}

function IsEnWebUserModifyWan() {
    if ((IsAdminUser() == false) && (CfgModeWord.toUpperCase() == "ROSUNION")) {
        return true;
    }

    return false;
}

function FeatureInfoClass(domain, RouteWanMulticastIPoE, RouteWanMulticastPPPoE, BridgeWanMulticast, WanMulticastProxy, LanSsidWanBind, IPv6, WanPriPolicy, dslite, LanPppWanBind, Wan, httpportmode, telportmode, dmzpri, WebCfgRgEnValid)
{
    this.domain = domain;
    this.RouteWanMulticastIPoE = RouteWanMulticastIPoE;
    this.RouteWanMulticastPPPoE = RouteWanMulticastPPPoE;
    this.BridgeWanMulticast = BridgeWanMulticast;
    this.WanMulticastProxy = WanMulticastProxy;
    this.LanSsidWanBind  = LanSsidWanBind;
    this.IPv6            = IPv6;
    this.WanPriPolicy    = WanPriPolicy;
    this.Dslite = dslite;
	this.LanPppWanBind  = LanPppWanBind;
	this.Wan  = Wan;
	this.httpportmode = httpportmode;
	this.telportmode  = telportmode;
	this.dmzpri       = dmzpri;
	this.WebCfgRgEnValid = WebCfgRgEnValid;
}

var MngtAhct = '0';
var RouteWanMax = '8';
var WanMax = '8';
var FeatureInfo = new FeatureInfoClass("", "1", "1", "1", "1", "1", "1", "1", "1", "1", "1","1", "0", "0", "0", "0");
FeatureInfo.WanMulticastProxy = "1";
FeatureInfo.RouteWanMulticastIPoE = "1";
FeatureInfo.RouteWanMulticastPPPoE = "1";
FeatureInfo.BridgeWanMulticast = "1";
FeatureInfo.LanSsidWanBind = "1";
FeatureInfo.IPv6 = "1";
FeatureInfo.WanPriPolicy = "1";
FeatureInfo.Dslite = "1";
FeatureInfo.LanPppWanBind = "1";
FeatureInfo.Wan = "1";
FeatureInfo.IPProtChk = "0";
FeatureInfo.httpportmode = "0";
FeatureInfo.telportmode  = "0";
FeatureInfo.dmzpri       = "0";
FeatureInfo.WebCfgRgEnValid = "0";

function GetFeatureInfo()
{
    return FeatureInfo;
}

function IsFeatureSupport(FeatureName)
{
    return FeatureInfo[FeatureName];
}

function GetRouteWanMax()
{
	return RouteWanMax;
}

function GetWanMax()
{
    return WanMax;
}

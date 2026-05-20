function() {
  return  [{name: "Home Page", id: "MainPage", deficon: "images/mainpagedef.jpg", clickicon: "images/mainpagepress.jpg", url: "CustomApp/mainpage.asp" , menutype: "" , component: "" },
{name: "One-Click Diagnosis", id: "OntCheck", deficon: "images/checkdef.jpg", clickicon: "images/checkpress.jpg", url: "html/ssmp/maintain/smartdiagnose.asp" , menutype: "" , component: "" },
{name: "System Information", id: "Systeminfo", deficon: "images/systemdef.jpg", clickicon: "images/systempress.jpg", url: "html/ssmp/deviceinfo/deviceinfo.asp" , menutype: "" , component: "" , subMenus: [
{name: "Device", id: "deviceinfo", deficon: "", clickicon: "", url: "html/ssmp/deviceinfo/deviceinfo.asp" , menutype: "" , component: "" },
{name: "WAN", id: "waninfo", deficon: "", clickicon: "", url: "html/bbsp/waninfo/waninfo.asp" , menutype: "" , component: "" },
{name: "Optical", id: "opticinfo", deficon: "", clickicon: "", url: "html/amp/opticinfo/opticinfo.asp" , menutype: "" , component: "" },
{name: "Service Provisioning Status", id: "bssinfo", deficon: "", clickicon: "", url: "html/ssmp/bss/bssinfo.asp" , menutype: "" , component: "" },
{name: "VoIP", id: "voipinfo", deficon: "", clickicon: "", url: "html/voip/status/voipmaintain.asp" , menutype: "" , component: "" },
{name: "Eth Port", id: "ethinfo", deficon: "", clickicon: "", url: "html/amp/ethinfo/ethinfo.asp" , menutype: "" , component: "" },
{name: "WLAN", id: "wlaninfo", deficon: "", clickicon: "", url: "html/amp/wlaninfo/wlaninfo.asp" , menutype: "" , component: "" },
{name: "Home Network", id: "wlancoverinfo", deficon: "", clickicon: "", url: "html/amp/wificoverinfo/wlancoverinfo.asp" , menutype: "" , component: "" }
] },
{name: "Advanced", id: "addconfig", deficon: "images/addvdef.jpg", clickicon: "images/addvpress.jpg", url: "" , menutype: "" , component: "" , subMenus: [
{name: "LAN", id: "lanconfig", deficon: "", clickicon: "", url: "html/bbsp/layer3/layer3.asp" , menutype: "" , component: "" , subMenus: [
{name: "Layer 2/3 Port", id: "lanportconfig", deficon: "", clickicon: "", url: "html/bbsp/layer3/layer3.asp" , menutype: "" , component: "" },
{name: "LAN Host", id: "lanhostconfig", deficon: "", clickicon: "", url: "html/bbsp/dhcp/dhcp.asp" , menutype: "" , component: "" },
{name: "DHCP Server", id: "landhcp", deficon: "", clickicon: "", url: "html/bbsp/dhcpservercfg/dhcp2.asp" , menutype: "" , component: "" },
{name: "DHCP Static IP", id: "landhcpstatic", deficon: "", clickicon: "", url: "html/bbsp/dhcpstatic/dhcpstatic.asp" , menutype: "" , component: "" },
{name: "DHCPv6 Server", id: "landhcpv6", deficon: "", clickicon: "", url: "html/bbsp/lanaddress/lanaddress.asp" , menutype: "" , component: "" },
{name: "DHCPv6 Static IP", id: "landhcpv6static", deficon: "", clickicon: "", url: "html/bbsp/dhcpstaticaddr/dhcpstaticaddress.asp" , menutype: "" , component: "" },
{name: "DHCPv6 Information", id: "landhcpv6info", deficon: "", clickicon: "", url: "html/bbsp/dhcpv6info/dhcpv6info.asp" , menutype: "" , component: "" },
{name: "IPv6 Port Service", id: "ipv6portservice", deficon: "", clickicon: "", url: "html/bbsp/portservice/ipv6portservice.asp" , menutype: "" , component: "" }
] },
{name: "Security", id: "securityconfig", deficon: "", clickicon: "", url: "html/bbsp/firewalllevel/firewalllevel.asp" , menutype: "" , component: "" , subMenus: [
{name: "Firewall Level", id: "ipv4firewalllevel", deficon: "", clickicon: "", url: "html/bbsp/firewalllevel/firewalllevel.asp" , menutype: "" , component: "" },
{name: "IPv4 Filtering", id: "ipincoming", deficon: "", clickicon: "", url: "html/bbsp/ipincoming/ipincoming.asp" , menutype: "" , component: "" },
{name: "MAC Filtering", id: "macfilter", deficon: "", clickicon: "", url: "html/bbsp/macfilter/macfilter.asp" , menutype: "" , component: "" },
{name: "Wi-Fi MAC Filtering", id: "wlanmacfilter", deficon: "", clickicon: "", url: "html/bbsp/wlanmacfilter/wlanmacfilter.asp" , menutype: "" , component: "" },
{name: "Parental Control", id: "parentalctrl", deficon: "", clickicon: "", url: "html/bbsp/parentalctrl/parentalctrlstatus.asp" , menutype: "" , component: "" },
{name: "DoS Configuration", id: "dos", deficon: "", clickicon: "", url: "html/bbsp/Dos/Dos.asp" , menutype: "" , component: "" },
{name: "Device Access Control", id: "ontaccess", deficon: "", clickicon: "", url: "html/bbsp/acl/aclsmart.asp" , menutype: "" , component: "" },
{name: "WAN Access Control", id: "wanacl", deficon: "", clickicon: "", url: "html/bbsp/wanacl/wanacl.asp" , menutype: "" , component: "" },
{name: "PSK Crack Defense", id: "pskprotect", deficon: "", clickicon: "", url: "/html/amp/wlanadv/wlanpskprotect.asp" , menutype: "" , component: "" }
] },
{name: "Route", id: "routeconfig", deficon: "", clickicon: "", url: "html/bbsp/route/route.asp" , menutype: "" , component: "" , subMenus: [
{name: "Default IPv4 Route", id: "ipv4defaultroute", deficon: "", clickicon: "", url: "html/bbsp/route/route.asp" , menutype: "" , component: "" },
{name: "IPv4 Static Route", id: "ipv4staticroute", deficon: "", clickicon: "", url: "html/bbsp/staticroute/staticroute.asp" , menutype: "" , component: "" },
{name: "IPv4 Dynamic Route", id: "ipv4dynamicroute", deficon: "", clickicon: "", url: "html/bbsp/dynamicroute/dynamicroute.asp" , menutype: "" , component: "" },
{name: "IPv4 VLAN Binding", id: "ipv4vlanbind", deficon: "", clickicon: "", url: "html/bbsp/vlanctc/vlanctc.asp" , menutype: "" , component: "" },
{name: "IPv4 Service Route", id: "ipv4serviceroute", deficon: "", clickicon: "", url: "html/bbsp/serviceroute/serviceroute.asp" , menutype: "" , component: "" },
{name: "IPv4 Routing Table", id: "ipv4routeinfo", deficon: "", clickicon: "", url: "html/bbsp/routeinfo/routeinfo.asp" , menutype: "" , component: "" },
{name: "Default IPv6 Route", id: "ipv6defaultroute", deficon: "", clickicon: "", url: "html/bbsp/ipv6defaultroute/defaultroute.asp" , menutype: "" , component: "" },
{name: "IPv6 Static Route", id: "ipv6staticroute", deficon: "", clickicon: "", url: "html/bbsp/ipv6staticroute/ipv6staticroute.asp" , menutype: "" , component: "" }
] },
{name: "Forward Rules", id: "forwardrules", deficon: "", clickicon: "", url: "html/bbsp/dmz/dmz.asp" , menutype: "" , component: "" , subMenus: [
{name: "DMZ Function", id: "dmz", deficon: "", clickicon: "", url: "html/bbsp/dmz/dmz.asp" , menutype: "" , component: "" },
{name: "IPv4 Port Mapping", id: "portmapping", deficon: "", clickicon: "", url: "html/bbsp/portmapping/portmapping.asp" , menutype: "" , component: "" },
{name: "Port Trigger", id: "porttrigger", deficon: "", clickicon: "", url: "html/bbsp/porttrigger/porttrigger.asp" , menutype: "" , component: "" }
] },
{name: "Application", id: "application", deficon: "", clickicon: "", url: "html/ssmp/usbftp/usbhost.asp" , menutype: "" , component: "" , subMenus: [
{name: "USB Application", id: "usbapplication", deficon: "", clickicon: "", url: "html/ssmp/usbftp/usbhost.asp" , menutype: "" , component: "" },
{name: "Time Setting", id: "sntpmngt", deficon: "", clickicon: "", url: "html/ssmp/sntp/sntp.asp" , menutype: "" , component: "" },
{name: "Media Sharing", id: "dlnashare", deficon: "", clickicon: "", url: "html/ssmp/dlna/dlna.asp" , menutype: "" , component: "" },
{name: "ALG", id: "alg", deficon: "", clickicon: "", url: "html/bbsp/alg/alg.asp" , menutype: "" , component: "" },
{name: "DDNS", id: "ddns", deficon: "", clickicon: "", url: "html/bbsp/ddns/ddns.asp" , menutype: "" , component: "" },
{name: "UPnP", id: "upnp", deficon: "", clickicon: "", url: "html/bbsp/upnp/upnp.asp" , menutype: "" , component: "" },
{name: "IGMP", id: "igmp", deficon: "", clickicon: "", url: "html/bbsp/igmp/igmp.asp" , menutype: "" , component: "" },
{name: "Intelligent Channel", id: "qossmart", deficon: "", clickicon: "", url: "html/bbsp/qossmart/qossmart.asp" , menutype: "" , component: "" },
{name: "ARP Ping", id: "arp", deficon: "", clickicon: "", url: "html/bbsp/arpping/arpping.asp" , menutype: "" , component: "" },
{name: "Static DNS", id: "dnsconfiguration", deficon: "", clickicon: "", url: "html/bbsp/dnsconfiguration/dnsconfigcommon.asp" , menutype: "" , component: "" },
{name: "DSCP-to-Pbit Mapping", id: "DSCPMapping", deficon: "", clickicon: "", url: "html/bbsp/dscptopbit/dscptopbit.asp" , menutype: "" , component: "" },
{name: "LAN Port Multi-service", id: "lanservicecfg", deficon: "", clickicon: "", url: "html/bbsp/lanservicecfg/lanservicecfg.asp" , menutype: "" , component: "" },
{name: "Certificate", id: "Certificate", deficon: "", clickicon: "", url: "html/ssmp/tr069/IpSec.asp" , menutype: "" , component: "" }
] },
{name: "WLAN", id: "wlanconfig", deficon: "", clickicon: "", url: "html/amp/wlanbasic/WlanBasic.asp?2G" , menutype: "" , component: "" , subMenus: [
{name: "2.4G Basic Network Settings", id: "wlan2basic", deficon: "", clickicon: "", url: "html/amp/wlanbasic/WlanBasic.asp?2G" , menutype: "" , component: "" },
{name: "2.4G Advanced Network Settings", id: "wlan2adv", deficon: "", clickicon: "", url: "html/amp/wlanadv/WlanAdvance.asp?2G" , menutype: "" , component: "" },
{name: "5G Basic Network Settings", id: "wlan5basic", deficon: "", clickicon: "", url: "html/amp/wlanbasic/WlanBasic.asp?5G" , menutype: "" , component: "" },
{name: "5G Advanced Network Settings", id: "wlan5adv", deficon: "", clickicon: "", url: "html/amp/wlanadv/WlanAdvance.asp?5G" , menutype: "" , component: "" },
{name: "Automatic Wi-Fi Shutdown", id: "wlanschedule", deficon: "", clickicon: "", url: "html/amp/wifische/WlanSchedule.asp" , menutype: "" , component: "" },
{name: "Wi-Fi Coverage", id: "wificover", deficon: "", clickicon: "", url: "html/amp/wificovercfg/wifiCover.asp" , menutype: "" , component: "" },
{name: "Multi-AP", id: "easymeshtopo", deficon: "", clickicon: "", url: "html/amp/wlaninfo/easymeshTopo.asp" , menutype: "" , component: "" }
] },
{name: "System Management", id: "systool", deficon: "", clickicon: "", url: "html/ssmp/accoutcfg/accountadmin.asp" , menutype: "" , component: "" , subMenus: [
{name: "Account Management", id: "userconfig", deficon: "", clickicon: "", url: "html/ssmp/accoutcfg/accountadmin.asp" , menutype: "" , component: "" },
{name: "Open Source Software Notice", id: "noticeinfo", deficon: "", clickicon: "", url: "html/ssmp/softnotice/opensfnotice.asp" , menutype: "" , component: "" },
{name: "ONT Authentication", id: "passwordcommon", deficon: "", clickicon: "", url: "html/amp/ontauth/passwordcommon.asp" , menutype: "" , component: "" },
{name: "Security Self-Check", id: "securitycheck", deficon: "", clickicon: "", url: "html/ssmp/securitycheck/securitycheck.asp" , menutype: "" , component: "" }
] },
{name: "Maintenance Diagnosis", id: "maintaininfo", deficon: "", clickicon: "", url: "html/ssmp/cfgfile/cfgfile.asp" , menutype: "" , component: "" , subMenus: [
{name: "Configuration File Management", id: "cfgconfig", deficon: "", clickicon: "", url: "html/ssmp/cfgfile/cfgfile.asp" , menutype: "" , component: "" },
{name: "Maintenance", id: "maintainconfig", deficon: "", clickicon: "", url: "html/bbsp/maintenance/diagnosecommon.asp" , menutype: "" , component: "" },
{name: "User Log", id: "userlog", deficon: "", clickicon: "", url: "html/ssmp/userlog/logview.asp" , menutype: "" , component: "" },
{name: "AP Log", id: "aplog", deficon: "", clickicon: "", url: "html/ssmp/aplog/aplogview.asp" , menutype: "" , component: "" },
{name: "Firewall Log", id: "firewalllog", deficon: "", clickicon: "", url: "html/bbsp/firewalllog/firewalllogview.asp" , menutype: "" , component: "" },
{name: "Debug Log", id: "debuglog", deficon: "", clickicon: "", url: "html/ssmp/debuglog/debuglogview.asp" , menutype: "" , component: "" },
{name: "Instrusion Detect Log", id: "instrusionlog", deficon: "", clickicon: "", url: "html/ssmp/instrusionlog/instrusionlog.asp" , menutype: "" , component: "" },
{name: "Intelligent Channel Statistics", id: "qossmartstatistics", deficon: "", clickicon: "", url: "html/bbsp/qossmartstatistics/qossmartstatistics.asp" , menutype: "" , component: "" },
{name: "Fault Info Collection", id: "collectconfig", deficon: "", clickicon: "", url: "html/ssmp/collect/collectInfo.asp" , menutype: "" , component: "" },
{name: "Remote Mirror", id: "remotepktmirror", deficon: "", clickicon: "", url: "html/bbsp/remotepktmirror/remotepktmirror.asp" , menutype: "" , component: "" },
{name: "Home Network Speedtest", id: "testspeed", deficon: "", clickicon: "", url: "html/ssmp/testspeed/testspeed.asp" , menutype: "" , component: "" },
{name: "Segment Speedtest", id: "sectionspeed", deficon: "", clickicon: "", url: "html/ssmp/Sectionspeed/Sectionspeed.asp" , menutype: "" , component: "" },
{name: "Indicator Status Management", id: "indicator", deficon: "", clickicon: "", url: "html/ssmp/ledcfg/ledcfg.asp" , menutype: "" , component: "" },
{name: "VoIP Statistics", id: "voipstatistic", deficon: "", clickicon: "", url: "html/voip/statistic/voipstatistic.asp" , menutype: "" , component: "" },
{name: "VoIP Diagnosis", id: "voipdiagnosis", deficon: "", clickicon: "", url: "html/voip/diagnose/voipdiagnose.asp" , menutype: "" , component: "" },
{name: "Reboot", id: "reboot", deficon: "", clickicon: "", url: "html/ssmp/reboot/reboot.asp" , menutype: "" , component: "" },
{name: "Mirror Port", id: "mirrorport", deficon: "", clickicon: "", url: "/html/ssmp/mirrorportcfg/mirrorportconfig.asp" , menutype: "" , component: "" }
] }
] }
];;
}
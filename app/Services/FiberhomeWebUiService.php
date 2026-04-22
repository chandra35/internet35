<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Fiberhome HG6145F WebUI scraper for WAN PPPoE configuration.
 *
 * Background: Fiberhome HG6145F firmware ignores TR-069 attempts to set the
 * WAN VLAN via Name="2_INTERNET_R_VID_<vlan>" — it rewrites the value back to
 * "1_INTERNET_R_VID_" (no VLAN). The only reliable way to set VLAN + dial
 * PPPoE on this hardware is through its admin WebUI (XHR API at /cgi-bin/ajax).
 *
 * Login flow (reverse-engineered from /js/login_inter.js + /js/xhr.js):
 *  1. GET  /cgi-bin/ajax?ajaxmethod=get_refresh_sessionid  -> {sessionid}
 *  2. GET  /cgi-bin/ajax?ajaxmethod=get_operator_test
 *  3. POST /cgi-bin/ajax  body=ajaxmethod=do_login&username=...&loginpd=<HEX>&port=0&sessionid=...
 *     where loginpd = strtoupper(bin2hex(AES-128-CBC(password, key=iv="opqrstuvwxyz{|}~")))
 *
 * WAN modify flow (reverse-engineered from /js/broadband_inter.js):
 *  POST /cgi-bin/ajax  body=ajaxmethod=wan_modify&wan_index=1&wan_session_index=1&...
 */
class FiberhomeWebUiService
{
    private string $ip;
    private string $adminUser;
    private string $adminPass;
    private string $cookieJar;
    private int $timeout;

    public function __construct(string $ip, string $adminUser, string $adminPass, int $timeout = 20)
    {
        $this->ip        = $ip;
        $this->adminUser = $adminUser;
        $this->adminPass = $adminPass;
        $this->timeout   = $timeout;
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'fh_cj_');
    }

    public function __destruct()
    {
        if (is_string($this->cookieJar) && file_exists($this->cookieJar)) {
            @unlink($this->cookieJar);
        }
    }

    /**
     * Configure the existing PPPoE WAN entry on the ONU with the given VLAN
     * + PPPoE credentials. If no PPPoE entry exists, create a new one.
     *
     * @param array $config ['vlan' => int, 'username' => string, 'password' => string,
     *                       'mtu' => int (default 1492), 'service_list' => string (default INTERNET')]
     * @return array ['success' => bool, 'message' => string, 'wan' => ?array]
     */
    public function configureWanPppoe(array $config): array
    {
        try {
            $vlan        = (int) ($config['vlan'] ?? 0);
            $pppUsername = (string) ($config['username'] ?? '');
            $pppPassword = (string) ($config['password'] ?? '');
            $mtu         = (int) ($config['mtu'] ?? 1492);
            $serviceList = (string) ($config['service_list'] ?? 'INTERNET');

            if ($vlan < 1 || $vlan > 4094) {
                return ['success' => false, 'message' => 'VLAN tidak valid (1-4094).'];
            }
            if ($pppUsername === '' || $pppPassword === '') {
                return ['success' => false, 'message' => 'Username/password PPPoE wajib diisi.'];
            }

            // 1. seed cookies (some firmware variants set initial cookie at /)
            $this->httpGet("http://{$this->ip}/");

            // 2. login
            if (!$this->login()) {
                return ['success' => false, 'message' => 'Login WebUI Fiberhome gagal (cek password admin).'];
            }

            // 3. find existing PPPoE WAN entry to modify (else add new)
            $wanList = $this->getAllWanInfo();
            if ($wanList === null) {
                return ['success' => false, 'message' => 'Gagal mengambil daftar WAN dari ONU.'];
            }

            $pppEntry = null;
            foreach ($wanList as $w) {
                $type = strtoupper((string) ($w['AddressingType'] ?? ''));
                if ($type === 'PPPOE') {
                    $pppEntry = $w;
                    break;
                }
            }

            // 4. build postdata for wan_modify (or wan_add_new)
            $sessionId = $this->getRefreshSessionId();
            $post = [
                'wan_iporppp_new'     => 2, // PPPoE
                'ConnectionType'      => 'PPPoE_Routed',
                'ServiceList'         => $serviceList,
                'IPMode'              => 1,
                'mtu'                 => $mtu,
                'VLANEnabled'         => 2,
                'vlanid'              => $vlan,
                'p8021'               => 0,
                'LanInterface'        => '',
                'AddressingType'      => 'PPPoE',
                'Username'            => $pppUsername,
                'WPd'                 => self::fhEncrypt($pppPassword),
                'ConnectionTrigger'   => 'AlwaysOn',
                'pppProxyEnable'      => 'NULL',
                'pppMAXUser'          => 'NULL',
                'pppToBridge'         => 'NULL',
                'NATEnabled'          => 1,
                'X_FH_AutoConnection' => 1,
                'Dslite_Enable'       => 0,
                'sessionid'           => $sessionId,
            ];

            if ($pppEntry !== null) {
                $action = 'wan_modify';
                $post['wan_index']         = (int) ($pppEntry['wan_index'] ?? 1);
                $post['wan_session_index'] = (int) ($pppEntry['wan_session_index'] ?? 1);
                $post['wan_iporppp_old']   = (int) ($pppEntry['iporppp'] ?? 2);
            } else {
                $action = 'wan_add_new';
            }

            $resp = $this->httpXhr($action, 'POST', $post);
            if (($resp['code'] ?? 0) !== 200) {
                return ['success' => false, 'message' => "wan_modify gagal: HTTP {$resp['code']}"];
            }

            $j = $this->parseJson($resp['body']);
            if (!is_array($j)) {
                return ['success' => false, 'message' => 'Respons WAN tidak valid dari ONU.'];
            }

            // 5. find updated PPPoE entry from response
            $updated = null;
            foreach (($j['wan'] ?? []) as $w) {
                if (strtoupper((string) ($w['AddressingType'] ?? '')) === 'PPPOE') {
                    $updated = $w;
                    break;
                }
            }

            return [
                'success' => true,
                'message' => 'Konfigurasi WAN PPPoE berhasil dikirim ke ONU Fiberhome (VLAN ' . $vlan . ').',
                'wan'     => $updated,
            ];
        } catch (Exception $e) {
            Log::error('FiberhomeWebUiService::configureWanPppoe error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Return the list of WAN entries from the ONU, or null on failure.
     */
    public function getAllWanInfo(): ?array
    {
        $r = $this->httpXhr('get_allwan_info', 'GET');
        if (($r['code'] ?? 0) !== 200) {
            return null;
        }
        $j = $this->parseJson($r['body']);
        return is_array($j) && isset($j['wan']) ? $j['wan'] : null;
    }

    private function login(): bool
    {
        $sid = $this->getRefreshSessionId();
        if ($sid === '') {
            return false;
        }
        // Some firmware tracks operator before login
        $this->httpXhr('get_operator_test', 'GET');

        $resp = $this->httpXhr('do_login', 'POST', [
            'username'  => $this->adminUser,
            'loginpd'   => self::fhEncrypt($this->adminPass),
            'port'      => 0,
            'sessionid' => $sid,
        ]);

        if (($resp['code'] ?? 0) !== 200) {
            return false;
        }
        $j = $this->parseJson($resp['body']);
        // login_result == 0 means success
        return is_array($j) && isset($j['login_result']) && (int) $j['login_result'] === 0;
    }

    private function getRefreshSessionId(): string
    {
        $r = $this->httpXhr('get_refresh_sessionid', 'GET');
        if (($r['code'] ?? 0) !== 200) {
            return '';
        }
        $j = $this->parseJson($r['body']);
        return is_array($j) && isset($j['sessionid']) ? (string) $j['sessionid'] : '';
    }

    private function httpXhr(string $method, string $httpMethod, array $params = []): array
    {
        $params['ajaxmethod'] = $method;
        $params['_']          = mt_rand() / mt_getrandmax();
        $body                 = http_build_query($params);
        $url                  = "http://{$this->ip}/cgi-bin/ajax";

        $ch = curl_init();
        $opts = [
            CURLOPT_URL            => $httpMethod === 'GET' ? "{$url}?{$body}" : $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $this->cookieJar,
            CURLOPT_COOKIEFILE     => $this->cookieJar,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'Referer: http://' . $this->ip . '/html/broadband_inter.html',
                'X-Requested-With: XMLHttpRequest',
            ],
        ];
        if ($httpMethod === 'POST') {
            $opts[CURLOPT_POST]              = true;
            $opts[CURLOPT_POSTFIELDS]        = $body;
            $opts[CURLOPT_HTTPHEADER][]      = 'Content-Type: application/x-www-form-urlencoded';
        }
        curl_setopt_array($ch, $opts);
        $respBody = curl_exec($ch);
        $info     = curl_getinfo($ch);
        curl_close($ch);

        return ['code' => $info['http_code'] ?? 0, 'body' => $respBody];
    }

    private function httpGet(string $url): void
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $this->cookieJar,
            CURLOPT_COOKIEFILE     => $this->cookieJar,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function parseJson(?string $body): ?array
    {
        if (!is_string($body)) {
            return null;
        }
        $marker = 'Content-type: application/json';
        if (($p = strpos($body, $marker)) !== false) {
            $body = substr($body, $p + strlen($marker));
        }
        $body = trim($body);
        $j = json_decode($body, true);
        return is_array($j) ? $j : null;
    }

    /**
     * Replicate the firmware's fhencrypt() (aes.js + util_functions.js):
     * AES-128-CBC, key = iv = "opqrstuvwxyz{|}~" (chars 111..126),
     * output = strtoupper(bin2hex(ciphertext)).
     */
    public static function fhEncrypt(string $data): string
    {
        $iv = '';
        for ($i = 0; $i < 16; $i++) {
            $iv .= chr($i + 111);
        }
        $cipher = openssl_encrypt($data, 'aes-128-cbc', $iv, OPENSSL_RAW_DATA, $iv);
        return strtoupper(bin2hex($cipher));
    }
}

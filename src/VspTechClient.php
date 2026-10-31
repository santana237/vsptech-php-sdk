<?php
/**
 * SDK PHP Officiel VSP TECH
 *
 * Client API léger et autonome pour intégrer les services de noms de domaine et d'hébergement
 * VSP TECH dans n'importe quel site ou application PHP (Laravel, Symfony, WordPress, script sur mesure).
 *
 * @version 1.0.0
 * @author VSP TECH <support@vsptech.host>
 * @link https://vsptech.host
 */

namespace VspTech;

class VspTechClient
{
    private string $apiKey;
    private string $apiUrl;
    private int $timeout;

    /**
     * @param string $apiKey Clé API Revendeur (vsprk_...)
     * @param string $apiUrl URL racine de l'API
     * @param int $timeout Délai d'expiration cURL en secondes
     */
    public function __construct(string $apiKey, string $apiUrl = 'https://vsptech.host/api/reseller/v1', int $timeout = 45)
    {
        $this->apiKey = trim($apiKey);
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Exécute une requête HTTP cURL vers l'API VSP TECH
     */
    private function request(string $endpoint, string $method = 'GET', array $data = []): array
    {
        $url = $this->apiUrl . '/' . ltrim($endpoint, '/');
        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
            'User-Agent: VspTech-PHP-SDK/1.0',
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $headers[] = 'Content-Type: application/json';
            }
        } elseif (!empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error) {
            return [
                'success' => false,
                'status'  => 0,
                'error'   => 'Erreur de connexion cURL : ' . $error,
            ];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'status'  => $httpCode,
                'error'   => 'Réponse serveur invalide (HTTP ' . $httpCode . ')',
                'raw'     => $response,
            ];
        }

        return $decoded;
    }

    // ==================================================================
    // Solde et Catalogue
    // ==================================================================

    /**
     * Consulte le solde du portefeuille revendeur et le palier de remise
     */
    public function getBalance(): array
    {
        return $this->request('balance');
    }

    /**
     * Liste des extensions (.CM, .COM...) avec prix coûtant revendeur et prix de vente
     */
    public function getTlds(bool $all = false): array
    {
        return $this->request('domains/tlds', 'GET', ['all' => $all ? 1 : 0]);
    }

    /**
     * Vérifie la disponibilité et le tarif d'un nom de domaine
     */
    public function checkDomain(string $domain): array
    {
        return $this->request('domains/check', 'GET', ['domain' => $domain]);
    }

    // ==================================================================
    // Noms de Domaine
    // ==================================================================

    /**
     * Commande un nom de domaine (enregistrement immédiat débité sur votre solde)
     */
    public function registerDomain(string $domain, int $years, array $owner, array $nameservers = [], string $customerRef = ''): array
    {
        $payload = [
            'action'       => 'register',
            'domain'       => $domain,
            'years'        => $years,
            'owner'        => $owner,
            'nameservers'  => $nameservers,
            'customer_ref' => $customerRef,
            'auto_renew'   => true,
        ];
        return $this->request('domains', 'POST', $payload);
    }

    /**
     * Lance le transfert d'un domaine vers VSP TECH avec son code EPP
     */
    public function transferDomain(string $domain, string $eppCode, string $customerRef = ''): array
    {
        $payload = [
            'action'       => 'transfer',
            'domain'       => $domain,
            'epp_code'     => $eppCode,
            'customer_ref' => $customerRef,
        ];
        return $this->request('domains', 'POST', $payload);
    }

    /**
     * Renouvelle un nom de domaine (payé instantanément avec le solde)
     */
    public function renewDomain(string $domain, int $years = 1): array
    {
        return $this->request('domains/renew', 'POST', [
            'domain' => $domain,
            'years'  => $years,
        ]);
    }

    /**
     * Consulte la fiche complète d'un nom de domaine (statut, dates, DNS, verrou)
     */
    public function getDomainInfo(string $domain): array
    {
        return $this->request('domains/info', 'GET', ['domain' => $domain]);
    }

    /**
     * Modifie les serveurs de noms (DNS) d'un domaine
     */
    public function setNameservers(string $domain, array $nameservers): array
    {
        return $this->request('domains/nameservers', 'POST', [
            'domain'      => $domain,
            'nameservers' => $nameservers,
        ]);
    }

    /**
     * Active ou désactive le verrou de transfert (Transfer Lock)
     */
    public function setDomainLock(string $domain, bool $locked): array
    {
        return $this->request('domains/lock', 'POST', [
            'domain' => $domain,
            'locked' => $locked,
        ]);
    }

    /**
     * Récupère le code de transfert EPP / AuthInfo d'un domaine
     */
    public function getEppCode(string $domain): array
    {
        return $this->request('domains/epp', 'GET', ['domain' => $domain]);
    }

    // ==================================================================
    // Hébergements Web (Hestia / cPanel)
    // ==================================================================

    /**
     * Liste des packs d'hébergement configurés par le revendeur
     */
    public function getHostingPacks(): array
    {
        return $this->request('packs');
    }

    /**
     * Crée un compte d'hébergement web pour un client final
     */
    public function createHostingAccount(string $domain, $packIdOrName, string $customerName, string $customerEmail, string $password = ''): array
    {
        $payload = [
            'domain'         => $domain,
            'customer_name'  => $customerName,
            'customer_email' => $customerEmail,
            'password'       => $password,
        ];

        if (is_numeric($packIdOrName)) {
            $payload['pack_id'] = (int) $packIdOrName;
        } else {
            $payload['pack_name'] = (string) $packIdOrName;
        }

        return $this->request('accounts', 'POST', $payload);
    }

    /**
     * Suspend un compte d'hébergement
     */
    public function suspendHostingAccount(string $domainOrId): array
    {
        return $this->request('accounts/' . urlencode($domainOrId) . '/suspend', 'POST');
    }

    /**
     * Réactive un compte d'hébergement suspendu
     */
    public function unsuspendHostingAccount(string $domainOrId): array
    {
        return $this->request('accounts/' . urlencode($domainOrId) . '/unsuspend', 'POST');
    }

    /**
     * Modifie le mot de passe SFTP/panneau d'un compte d'hébergement
     */
    public function resetHostingPassword(string $domainOrId, string $newPassword): array
    {
        return $this->request('accounts/' . urlencode($domainOrId) . '/password', 'POST', ['password' => $newPassword]);
    }

    /**
     * Résilie et supprime définitivement un compte d'hébergement
     */
    public function terminateHostingAccount(string $domainOrId): array
    {
        return $this->request('accounts/' . urlencode($domainOrId), 'DELETE');
    }
}

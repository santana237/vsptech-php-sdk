<?php
/**
 * Exemple d'utilisation du SDK PHP VSP TECH
 */

require_once __DIR__ . '/VspTechClient.php';

use VspTech\VspTechClient;

// Initialiser avec votre clé API Revendeur (vsprk_...)
$client = new VspTechClient('vsprk_votre_cle_api_ici');

// 1. Consulter le solde du compte
$balance = $client->getBalance();
print_r($balance);

// 2. Vérifier la disponibilité d'un domaine
$check = $client->checkDomain('mon-nouveau-projet.cm');
print_r($check);

// 3. Commander un domaine si disponible et solde suffisant
if (!empty($check['data']['available'])) {
    $order = $client->registerDomain(
        'mon-nouveau-projet.cm',
        1, // 1 an
        [
            'first_name' => 'Paul',
            'last_name'  => 'Biya',
            'company'    => 'Entreprise Camerounaise',
            'email'      => 'contact@mon-nouveau-projet.cm',
            'phone'      => '+237.690000000',
            'address'    => 'Boulevard du 20 Mai',
            'city'       => 'Yaoundé',
            'country'    => 'CM',
        ],
        ['ns1.vsptech.host', 'ns2.vsptech.host'],
        'REF-CLIENT-101'
    );
    print_r($order);
}

// 4. Créer un compte d'hébergement pour le client
$hosting = $client->createHostingAccount(
    'mon-nouveau-projet.cm',
    'Starter', // Nom du pack ou son ID
    'Paul Biya',
    'contact@mon-nouveau-projet.cm',
    'MotDePasseTresSecurise2026!'
);
print_r($hosting);

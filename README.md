# SDK PHP Officiel VSP TECH

Client PHP léger et moderne pour intégrer l'API Revendeur VSP TECH dans vos applications sans dépendance externe (cURL natif).

---

## Fonctionnalités
* **Gestion du solde** : Vérification en temps réel du crédit disponible.
* **Noms de domaine** : Recherche de disponibilité en temps réel, enregistrement instantané débité sur votre solde, renouvellement, changement de serveurs DNS, verrouillage, code EPP.
* **Hébergements web** : Création instantanée de comptes d'hébergement cPanel/Hestia pour vos clients, suspension, réactivation, changement de mot de passe, résiliation.

---

## Utilisation rapide

```php
require_once 'VspTechClient.php';

use VspTech\VspTechClient;

$client = new VspTechClient('vsprk_votre_cle_api');

// Vérifier la disponibilité
$result = $client->checkDomain('client.cm');
if ($result['data']['available']) {
    // Enregistrer le domaine
    $order = $client->registerDomain('client.cm', 1, [
        'first_name' => 'Jean',
        'last_name'  => 'Dupont',
        'email'      => 'jean@client.cm',
        'phone'      => '+237.690000000',
        'address'    => 'Rue Principale',
        'city'       => 'Douala',
        'country'    => 'CM'
    ]);
}
```

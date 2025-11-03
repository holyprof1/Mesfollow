<?php

namespace App\Service;

use Yabacon\Paystack;
use Exception;

class PaystackService
{
    protected $configData;
    protected $paystackAPI;

    public function __construct()
    {
        $this->configData = configItem();
        $cfg = $this->configData['payments']['gateway_configuration']['paystack']
             ?? ($this->configData['inoraPaymentConfig']['payments']['gateway_configuration']['paystack'] ?? []);

        if (!empty($cfg)) {
            $testMode = (!empty($cfg['testMode']) && ($cfg['testMode'] === true || $cfg['testMode'] === 'true'));
            $secret   = $testMode ? ($cfg['paystackTestingSecretKey'] ?? '') : ($cfg['paystackLiveSecretKey'] ?? '');
            if (!empty($secret) && is_string($secret) && substr($secret, 0, 3) !== 'sk_') {
                $secret = 'sk_' . $secret;
            }
            if (!empty($secret)) {
                $this->paystackAPI = new Paystack($secret);
            }
        }
    }

    /**
     * Verify a Paystack transaction safely.
     * Accepts any of: paystackReferenceId | reference | trxref.
     * Returns ['errorMessage' => '...'] if missing reference or on exception.
     */
    public function processPaystackRequest($request)
    {
        // normalize reference
        $ref = $request['paystackReferenceId']
            ?? $request['reference']
            ?? $request['trxref']
            ?? null;

        if (!$ref || !is_string($ref)) {
            return ['errorMessage' => 'missing_reference'];
        }

        if (!$this->paystackAPI) {
            return ['errorMessage' => 'missing_secret_key'];
        }

        try {
            $detail = $this->paystackAPI->transaction->verify(['reference' => trim($ref)]);
            return (array) $detail;
        } catch (Exception $e) {
            return ['errorMessage' => $e->getMessage()];
        }
    }
}

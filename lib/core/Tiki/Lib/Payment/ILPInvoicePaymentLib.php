<?php

namespace Tiki\Lib\Payment;

use TikiLib;
use Feedback;
use WikiSuite\ILP\ILPSPSPClientAdaptorInterface;
use WikiSuite\ILP\Providers\ILPInvoiceServerClient;

class ILPInvoicePaymentLib implements ILPPaymentAdapterInterface
{
    public function isEnabled(): bool
    {
        global $prefs;
        return ! empty($prefs['payment_ilp_base_url']) && ! empty($prefs['payment_ilp_token']);
    }

    public function getILPClient(): ILPSPSPClientAdaptorInterface
    {
        global $prefs;

        return new ILPInvoiceServerClient(
            $prefs['payment_ilp_base_url'],
            $prefs['payment_ilp_token'],
            $prefs['payment_ilp_ssl'] == 'y'
        );
    }

    public function createInvoice($tikiInvoiceId, $user, $amount, $extraFields = []): bool
    {
        global $prefs, $base_url;
        $baseUrl = $base_url ?? $prefs['fallbackBaseUrl'];
        $attributelib = TikiLib::lib('attribute');
        $ilpClient = $this->getILPClient();
        $ilpInvoiceURL = $ilpClient->createInvoice(
            $amount,
            $prefs['payment_currency'],
            $prefs['payment_ilp_scale'],
            trim($baseUrl, '/') . "/tiki-payment.php?callback=ilp"
        );
        if (! $ilpInvoiceURL) {
            Feedback::error(tr("Was not possible to create ILP pointer."));
            return false;
        }
        $ilpInvoiceId = $ilpClient->getPointerId($ilpInvoiceURL);
        $attributelib->set_attribute('payment', $tikiInvoiceId, 'payment_ilp.invoice.url', $ilpInvoiceURL);
        $attributelib->set_attribute('payment', $tikiInvoiceId, 'payment_ilp.invoice.user', $user);
        $attributelib->set_attribute('payment', $tikiInvoiceId, 'payment_ilp.invoice.id', $ilpInvoiceId);
        $attributelib->set_attribute('payment', $ilpInvoiceId, 'payment_ilp.pointer.invoice_id', $tikiInvoiceId);
        return true;
    }

    public function checkPayment($tikiInvoiceId): bool
    {
        $paymentlib = TikiLib::lib('payment');
        $attributelib = TikiLib::lib('attribute');
        $pointer = $attributelib->get_attribute('payment', $tikiInvoiceId, 'payment_ilp.invoice.url');
        $user = $attributelib->get_attribute('payment', $tikiInvoiceId, 'payment_ilp.invoice.user');
        if (! $pointer || ! $user) {
            return false;
        }
        $ilpClient = $this->getILPClient();

        $invoice = $ilpClient->getInvoice($pointer);
        if ($invoice->isPayed()) {
            $paymentlib->enter_payment(
                $tikiInvoiceId,
                $invoice->getAmount(),
                'user',
                [
                    'user' => $user
                ]
            );
            return true;
        }
        return false;
    }


    public function checkPaymentPointer($pointer): bool
    {
        $ilpClient = $this->getILPClient();
        $ilpInvoiceId = $ilpClient->getPointerId($pointer);
        $attributelib = TikiLib::lib('attribute');
        $invoiceId = $attributelib->get_attribute('payment', $ilpInvoiceId, 'payment_ilp.pointer.invoice_id');
        return $this->checkPayment($invoiceId);
    }

    public function getPointer($invoiceId): string
    {
        $attributelib = TikiLib::lib('attribute');
        return $attributelib->get_attribute('payment', $invoiceId, 'payment_ilp.invoice.url');
    }
}

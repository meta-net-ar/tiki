<?php

namespace Tiki\Lib\Payment;

use WikiSuite\ILP\Invoice;
use WikiSuite\ILP\ILPSPSPClientAdaptorInterface;

interface ILPPaymentAdapterInterface
{
    public function isEnabled(): bool;

    public function getILPClient(): ILPSPSPClientAdaptorInterface;

    public function createInvoice($tikiInvoiceId, $user, Invoice $invoice, $extraFields = []): bool;

    public function checkPayment($tikiInvoiceId): bool;

    public function getPointer($invoiceId): string;

    public function checkPaymentPointer($pointer): bool;
}

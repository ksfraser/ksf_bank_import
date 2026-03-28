<?php
namespace Ksfraser\FaBankImport\Import;

class ContactImportHelper
{
    /**
     * Attempt to derive or create a contact from a parser transaction object
     * and attach `contact_id` to the parser transaction ($t) if found.
     *
     * Returns the contact id on success, or null on any failure/no-op.
     */
    public static function attachContactIdFromParserTransaction($db, $smt, $t)
    {
        try {
            if (!isset($t->contact) || !is_object($t->contact)) {
                return null;
            }

            if (!class_exists('Ksfraser\\FaBankImport\\Services\\ContactService') || !class_exists('Ksfraser\\FaBankImport\\Services\\ContactDeduplicationService')) {
                return null;
            }

            $contactService = new \Ksfraser\FaBankImport\Services\ContactService($db);
            $dedupService = new \Ksfraser\FaBankImport\Services\ContactDeduplicationService($contactService);

            $transactionForContact = [
                'transactionTitle' => (string)($t->payee ?? $t->transactionTitle ?? $t->title ?? $t->memo ?? ''),
                'memo' => (string)($t->memo ?? ''),
                'transactionCode' => (string)($t->id ?? ''),
                'account' => (string)($smt->account ?? ''),
            ];

            $contactData = \Ksfraser\FaBankImport\Services\ContactDataFactory::buildFromTransaction($transactionForContact, 'S');

            if (!empty($t->contact->email)) {
                $contactData->email = trim((string)$t->contact->email);
            }
            if (!empty($t->contact->phone)) {
                $contactData->phone = trim((string)$t->contact->phone);
            }

            $contact = $dedupService->getOrCreateWithDeduplicate($contactData);
            if ($contact && !empty($contact->id)) {
                $t->contact_id = (int)$contact->id;
                return (int)$contact->id;
            }

            return null;
        } catch (\Throwable $e) {
            @error_log('ContactImportHelper: ' . $e->getMessage());
            return null;
        }
    }
}

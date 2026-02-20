<?php
namespace Ksfraser\FaBankImport\Service;

use Ksfraser\FaBankImport\Repository\DatabaseConfigRepository;
use Ksfraser\FA\Auth\UserSession;

class AccountMappingResolver
{
    /**
     * Resolves account mappings and persists associations.
     *
     * @param array $detected_account
     * @param array $resolved_bank_account
     * @param array $remember_mapping
     * @param array $multistatements
     * @param array $uploaded_file_ids
     * @param array $pending
     * @return array [detectedToAccountNumber, rememberedCount]
     */
    public static function resolve(
        array $detected_account,
        array $resolved_bank_account,
        array $remember_mapping,
        array $multistatements,
        array $uploaded_file_ids,
        array $pending
    ): array {
        $repo = new DatabaseConfigRepository();
        $username = UserSession::getCurrentUsername();
        $rememberedCount = 0;
        $detectedToAccountNumber = [];
        $metaByDetected = collect_detected_identity_meta($multistatements);

        foreach ($detected_account as $detKey => $detected) {
            $detected = (string)$detected;
            $selectedId = isset($resolved_bank_account[$detKey]) ? (int)$resolved_bank_account[$detKey] : 0;
            if ($selectedId <= 0) {
                continue;
            }
            $ba = get_bank_account($selectedId);
            if (!is_array($ba) || empty($ba['bank_account_number'])) {
                continue;
            }
            $detectedToAccountNumber[$detected] = $ba['bank_account_number'];

            if (!empty($remember_mapping[$detKey])) {
                if (isset($metaByDetected[$detected])) {
                    bi_bank_accounts_upsert((int)$selectedId, $metaByDetected[$detected]);
                }
                $key = DetectedAccountAssociationKey::forDetectedAccount($detected);
                $repo->set($key, (string)$selectedId, $username, 'Associate detected account to FA bank account');
                $rememberedCount++;
            }

            // Back-fill metadata in bi_uploaded_files if we have file IDs
            if (!empty($uploaded_file_ids)) {
                try {
                    $uploadService = FileUploadService::create();
                    foreach ($pending['unresolved'][$detected] ?? [] as $fileIndex) {
                        $fileId = $uploaded_file_ids[$fileIndex] ?? null;
                        if ($fileId !== null) {
                            $uploadService->updateBankAccountId((int)$fileId, (int)$selectedId);
                        }
                    }
                } catch (\Throwable $e) {
                    // Non-blocking metadata update
                }
            }
        }
        return [$detectedToAccountNumber, $rememberedCount];
    }
}

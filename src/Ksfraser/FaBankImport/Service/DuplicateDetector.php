<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :DuplicateDetector [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for DuplicateDetector.
 */
namespace Ksfraser\FaBankImport\Service;

use Ksfraser\FaBankImport\ValueObject\FileInfo;
use Ksfraser\FaBankImport\ValueObject\DuplicateResult;
use Ksfraser\FaBankImport\Repository\UploadedFileRepositoryInterface;
use Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface;

/**
 * Duplicate Detector Service
 * 
 * Detects duplicate file uploads using multiple verification methods:
 * 1. Filename match
 * 2. File size match
 * 3. MD5 hash verification (if files match by name/size)
 * 
 * Respects configuration settings:
 * - check_duplicates: Enable/disable duplicate checking
 * - duplicate_window_days: How far back to check
 * - duplicate_action: allow, warn, or block
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class DuplicateDetector
{
    /** @var UploadedFileRepositoryInterface */
    private  $fileRepository;
    //private UploadedFileRepositoryInterface $fileRepository;
    
    /** @var ConfigRepositoryInterface */
    private  $configRepository;
    //private ConfigRepositoryInterface $configRepository;
    
    /** @var FileStorageServiceInterface */
    private  $storageService;
    //private FileStorageServiceInterface $storageService;
    
    /**
     * Constructor - Dependency Injection
     * 
     * @param UploadedFileRepositoryInterface $fileRepository
     * @param ConfigRepositoryInterface $configRepository
     * @param FileStorageServiceInterface $storageService
     */
    public function __construct(
        UploadedFileRepositoryInterface $fileRepository,
        ConfigRepositoryInterface $configRepository,
        FileStorageServiceInterface $storageService
    ) {
        $this->fileRepository = $fileRepository;
        $this->configRepository = $configRepository;
        $this->storageService = $storageService;
    }
    
    /**
     * Detect if file is a duplicate
     * 
     * Process:
     * 1. Check if duplicate checking is enabled
     * 2. Find potential duplicates by filename + size
     * 3. Verify with MD5 hash comparison
     * 4. Return result based on duplicate_action config
     * 
     * @param FileInfo $fileInfo File information
     * @return DuplicateResult Result indicating duplicate status and action
     */
    public function detect(FileInfo $fileInfo): DuplicateResult
    {
        // Check if duplicate detection is enabled
        if (!$this->isDuplicateCheckingEnabled()) {
            return DuplicateResult::notDuplicate();
        }
        
        // Get time window for duplicate checking
        $windowDays = $this->getDuplicateWindowDays();
        
        // Find potential duplicate by filename and size
        $existingFile = $this->fileRepository->findDuplicate($fileInfo, $windowDays);
        
        if ($existingFile === null) {
            // No duplicate found
            return DuplicateResult::notDuplicate();
        }
        
        // Verify it's truly a duplicate using MD5 hash
        if (!$this->verifyDuplicateByHash($fileInfo, $existingFile->getFilePath())) {
            // Same name/size but different content - not a duplicate
            return DuplicateResult::notDuplicate();
        }
        
        // It's a duplicate - determine action based on config
        $action = $this->getDuplicateAction();
        
        switch ($action) {
            case 'allow':
                return DuplicateResult::allowDuplicate($existingFile);
                
            case 'warn':
                return DuplicateResult::warnDuplicate($existingFile);
                
            case 'block':
                return DuplicateResult::blockDuplicate($existingFile);
                
            default:
                // Default to warn if invalid config
                return DuplicateResult::warnDuplicate($existingFile);
        }
    }
    
    /**
     * Check if duplicate checking is enabled in config
     * 
     * @return bool
     */
    private function isDuplicateCheckingEnabled(): bool
    {
        return (bool) $this->configRepository->get('check_duplicates', true);
    }
    
    /**
     * Get duplicate detection time window in days
     * 
     * @return int Number of days
     */
    private function getDuplicateWindowDays(): int
    {
        return (int) $this->configRepository->get('duplicate_window_days', 90);
    }
    
    /**
     * Get duplicate action from config
     * 
     * @return string 'allow', 'warn', or 'block'
     */
    private function getDuplicateAction(): string
    {
        $action = $this->configRepository->get('duplicate_action', 'warn');
        
        // Validate action
        if (!in_array($action, ['allow', 'warn', 'block'], true)) {
            return 'warn'; // Default to warn if invalid
        }
        
        return $action;
    }
    
    /**
     * Verify files are identical using MD5 hash
     * 
     * Compares MD5 hash of new upload with existing file on disk
     * 
     * @param FileInfo $newFile New file being uploaded
     * @param string $existingFilePath Path to existing file on disk
     * @return bool True if files are identical
     */
    private function verifyDuplicateByHash(FileInfo $newFile, string $existingFilePath): bool
    {
        // Check if existing file still exists on disk
        if (!$this->storageService->exists($existingFilePath)) {
            // Existing file missing - metadata orphaned
            // Not a true duplicate since we can't verify
            return false;
        }
        
        // Get MD5 hash of new file
        $newHash = $newFile->getMd5Hash();
        
        // Get MD5 hash of existing file
        $existingHash = @md5_file($existingFilePath);
        
        if ($existingHash === false) {
            // Can't read existing file - treat as not duplicate
            return false;
        }
        
        // Compare hashes
        return $newHash === $existingHash;
    }
    
    /**
     * Check if specific file would be a duplicate
     * 
     * Convenience method for checking before actual upload
     * 
     * @param string $filename Original filename
     * @param int $fileSize File size in bytes
     * @return bool True if duplicate exists
     */
    public function isDuplicate(string $filename, int $fileSize): bool
    {
        if (!$this->isDuplicateCheckingEnabled()) {
            return false;
        }
        
        $windowDays = $this->getDuplicateWindowDays();
        
        // Create temporary FileInfo for checking
        // Note: We can't verify hash without actual file
        $tempFileInfo = new \Ksfraser\FaBankImport\ValueObject\FileInfo(
            $filename,
            '', // No temp path needed for this check
            $fileSize,
            'application/octet-stream'
        );
        
        $existingFile = $this->fileRepository->findDuplicate($tempFileInfo, $windowDays);
        
        return $existingFile !== null;
    }
    
    /**
     * Get duplicate statistics
     * 
     * @return array Statistics about duplicate detection
     */
    public function getStatistics(): array
    {
        return [
            'enabled' => $this->isDuplicateCheckingEnabled(),
            'window_days' => $this->getDuplicateWindowDays(),
            'action' => $this->getDuplicateAction(),
        ];
    }
}

<?php

namespace Ksfraser\FaBankImport\Service;

use Ksfraser\FaBankImport\ValueObject\FileInfo;
use Ksfraser\FaBankImport\ValueObject\UploadResult;
use Ksfraser\FaBankImport\Entity\UploadedFile;
use Ksfraser\FaBankImport\Repository\UploadedFileRepositoryInterface;
use Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface;
use Ksfraser\FaBankImport\Strategy\DuplicateStrategyFactory;

/**
 * File Upload Service
 * 
 * Main orchestrator for file upload process
 * Coordinates: duplicate detection, file storage, database persistence
 * 
 * Follows:
 * - Facade Pattern (simple interface for complex subsystem)
 * - Dependency Injection (all dependencies injected)
 * - Single Responsibility (orchestration only)
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class FileUploadService
{
    /** @var UploadedFileRepositoryInterface */
    private UploadedFileRepositoryInterface $fileRepository;
    
    /** @var FileStorageServiceInterface */
    private FileStorageServiceInterface $storageService;
    
    /** @var DuplicateDetector */
    private DuplicateDetector $duplicateDetector;
    
    /** @var ConfigRepositoryInterface */
    private ConfigRepositoryInterface $configRepository;
    
    /**
     * Constructor - Dependency Injection
     * 
     * @param UploadedFileRepositoryInterface $fileRepository
     * @param FileStorageServiceInterface $storageService
     * @param DuplicateDetector $duplicateDetector
     * @param ConfigRepositoryInterface $configRepository
     */
    public function __construct(
        UploadedFileRepositoryInterface $fileRepository,
        FileStorageServiceInterface $storageService,
        DuplicateDetector $duplicateDetector,
        ConfigRepositoryInterface $configRepository
    ) {
        $this->fileRepository = $fileRepository;
        $this->storageService = $storageService;
        $this->duplicateDetector = $duplicateDetector;
        $this->configRepository = $configRepository;
    }
    
    /**
     * Upload and process file
     * 
     * Process:
     * 1. Check for duplicates (unless forceUpload = true)
     * 2. Store file to disk
     * 3. Save metadata to database
     * 4. Return result
     * 
     * @param FileInfo $fileInfo File information from upload
     * @param string $parserType Parser type (qfx, mt940, csv, etc.)
     * @param int|null $bankAccountId Optional bank account ID
     * @param bool $forceUpload Force upload even if duplicate (override warn mode)
     * @param string|null $notes Optional notes about the upload
     * @return UploadResult Result of upload operation
     */
    public function upload(
        FileInfo $fileInfo,
        string $parserType,
        ?int $bankAccountId = null,
        bool $forceUpload = false,
        ?string $notes = null
    ): UploadResult {
        // Step 1: Check for duplicates (unless forced)
        if (!$forceUpload) {
            $duplicateResult = $this->duplicateDetector->detect($fileInfo);
            
            if ($duplicateResult->isDuplicate()) {
                // Get appropriate strategy based on config
                $action = $this->configRepository->get('duplicate_action', 'warn');
                $strategy = DuplicateStrategyFactory::create($action);
                
                // Handle duplicate according to strategy
                $handleResult = $strategy->handle($duplicateResult);
                
                // If strategy allows (action='allow'), reuse existing file
                if ($handleResult['action'] === 'reused') {
                    return UploadResult::reused(
                        $handleResult['existingFileId'],
                        $handleResult['message']
                    );
                }
                
                // Otherwise (warn or block), return appropriate result
                return UploadResult::duplicate(
                    $handleResult['message'],
                    $handleResult['allowForce']
                );
            }
        }
        
        // Step 2: Store file to disk
        try {
            $storageResult = $this->storageService->store($fileInfo, $parserType);
            $uniqueFilename = $storageResult['filename'];
            $filePath = $storageResult['path'];
        } catch (\RuntimeException $e) {
            return UploadResult::error('Failed to store file: ' . $e->getMessage());
        }
        
        // Step 3: Save metadata to database
        try {
            $uploadedFile = new UploadedFile(
                null, // ID will be set by repository
                $uniqueFilename,
                $fileInfo->getOriginalFilename(),
                $filePath,
                $fileInfo->getSize(),
                $fileInfo->getMimeType(),
                new \DateTime(),
                $this->getCurrentUsername(),
                $parserType,
                $bankAccountId,
                0, // statement_count starts at 0
                $notes
            );
            
            $fileId = $this->fileRepository->save($uploadedFile);
            
        } catch (\Exception $e) {
            // Rollback: delete file from disk since database save failed
            $this->storageService->delete($filePath);
            
            return UploadResult::error('Failed to save file metadata: ' . $e->getMessage());
        }
        
        // Step 4: Return success result
        return UploadResult::success(
            $fileId,
            $uniqueFilename,
            'File uploaded successfully.'
        );
    }
    
    /**
     * Link uploaded file to imported statements
     * 
     * Called after statements are successfully imported from file
     * 
     * @param int $fileId Uploaded file ID
     * @param array $statementIds Array of statement IDs
     * @return bool Success
     */
    public function linkToStatements(int $fileId, array $statementIds): bool
    {
        return $this->fileRepository->linkToStatements($fileId, $statementIds);
    }
    
    /**
     * Delete uploaded file (both disk and database)
     * 
     * @param int $fileId File ID
     * @return bool Success
     */
    public function delete(int $fileId): bool
    {
        // Get file metadata
        $file = $this->fileRepository->findById($fileId);
        
        if ($file === null) {
            return false;
        }
        
        // Delete from disk
        $diskDeleted = $this->storageService->delete($file->getFilePath());
        
        // Delete from database (even if disk delete failed - metadata cleanup)
        $dbDeleted = $this->fileRepository->delete($fileId);
        
        return $diskDeleted && $dbDeleted;
    }
    
    /**
     * Get uploaded file by ID
     * 
     * @param int $fileId File ID
     * @return UploadedFile|null
     */
    public function getFile(int $fileId): ?UploadedFile
    {
        return $this->fileRepository->findById($fileId);
    }
    
    /**
     * Get file contents for download
     * 
     * @param int $fileId File ID
     * @return string|null File contents or null if not found
     */
    public function getFileContents(int $fileId): ?string
    {
        $file = $this->fileRepository->findById($fileId);
        
        if ($file === null) {
            return null;
        }
        
        try {
            return $this->storageService->getContents($file->getFilePath());
        } catch (\RuntimeException $e) {
            return null;
        }
    }
    
    /**
     * List uploaded files with filters
     * 
     * @param array $filters Optional filters (user, date_from, date_to, parser_type)
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Array of UploadedFile entities
     */
    public function listFiles(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        return $this->fileRepository->findAll($filters, $limit, $offset);
    }
    
    /**
     * Get total file count
     * 
     * @param array $filters Optional filters
     * @return int Total count
     */
    public function countFiles(array $filters = []): int
    {
        return $this->fileRepository->count($filters);
    }
    
    /**
     * Get storage statistics
     * 
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        return $this->fileRepository->getStatistics();
    }
    
    /**
     * Get current username from FrontAccounting session
     * 
     * @return string Username
     */
    private function getCurrentUsername(): string
    {
        // FrontAccounting stores username in $_SESSION['wa_current_user']->username
        if (isset($_SESSION['wa_current_user'])) {
            return $_SESSION['wa_current_user']->username;
        }
        
        return 'unknown';
    }
    
    /**
     * Create service instance with dependencies (DI Factory Method)
     * 
     * @param UploadedFileRepositoryInterface|null $fileRepository
     * @param FileStorageServiceInterface|null $storageService
     * @param DuplicateDetector|null $duplicateDetector
     * @param ConfigRepositoryInterface|null $configRepository
     * @return self
     */
    public static function create(
        ?UploadedFileRepositoryInterface $fileRepository = null,
        ?FileStorageServiceInterface $storageService = null,
        ?DuplicateDetector $duplicateDetector = null,
        ?ConfigRepositoryInterface $configRepository = null
    ): self {
        // Use provided dependencies or create defaults
        $fileRepository = $fileRepository ?? new \Ksfraser\FaBankImport\Repository\DatabaseUploadedFileRepository();
        $storageService = $storageService ?? new FileStorageService();
        $configRepository = $configRepository ?? new \Ksfraser\FaBankImport\Repository\DatabaseConfigRepository();
        $duplicateDetector = $duplicateDetector ?? new DuplicateDetector(
            $fileRepository,
            $configRepository,
            $storageService
        );
        
        return new self(
            $fileRepository,
            $storageService,
            $duplicateDetector,
            $configRepository
        );
    }
}

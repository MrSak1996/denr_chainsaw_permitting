<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\ApplicantAttachments\AttachmentsModel;
use App\Models\Chainsaw\ChainsawInformation;

class GoogleDriveService
{
    public function storeAttachments($application_no,$request, $applicationId, $folderStructure, $filesToUpload)
    {
        $results = [];
        
        foreach ($filesToUpload as $input => $folderType) {
            try {
                if (!$request->hasFile($input)) {
                    $results[$input] = [
                        'error' => "No file provided for: {$input}"
                    ];
                    continue;
                }

                $file = $request->file($input);

                // 📁 Build folder path (e.g., CHAINSAW_PERMITTING/.../permit)
                $folderPath = "{$folderStructure}/{$folderType}";
                $this->ensureFolderExists($folderPath);

                
                $originalExt = $file->getClientOriginalExtension();
                $timestamp = now()->format('Ymd_His');
                $applicationNo = $application_no;

                if ($input === 'permit') {
                    $fileName = "permit_{$applicationNo}.{$originalExt}";
                } else if ($input === 'mayors') {
                    $fileName = "mayors_{$applicationNo}.{$originalExt}";
                } else if ($input === 'notarized') {
                    $fileName = "notarized_{$applicationNo}.{$originalExt}";
                } else if ($input === 'official') {
                    $fileName = "official_{$applicationNo}.{$originalExt}";
                } else if ($input === 'request') {
                    $fileName = "request_{$applicationNo}.{$originalExt}";
                } else if ($input === 'secretary') {
                    $fileName = "secretary_{$applicationNo}.{$originalExt}";
                } else {
                    $filePrefix = str_replace(' ', '_', strtolower($folderType));
                    $originalName = $file->getClientOriginalName();
                    $fileName = "{$filePrefix}";
                }

                // Final file path
                $filePath = "{$folderPath}/{$fileName}";


                // 🚀 Upload to Google Drive
                Log::info("Uploading file: {$fileName} to: {$filePath}");
                $fileId = $this->uploadToDriveAndGetFileId($file, $filePath);
                if (!$fileId) {
                    throw new \Exception("Unable to retrieve file ID for: {$fileName}");
                }

                $fileUrl = "https://drive.google.com/file/d/{$fileId}/preview";

                // 💾 Save attachment record
                $uploadedFile = AttachmentsModel::create([
                    'application_id' => $applicationId,
                    'file_id' => $fileId,
                    'file_name' => $fileName,
                    'file_url' => $fileUrl,
                ]);

                // 🛠 Optional: Associate with ChainsawInfo (confirm logic)
                $chainsaw = ChainsawInformation::where('application_id', $applicationId)->first();
                if ($chainsaw) {
                    $chainsaw->update([
                        'application_attachment_id' => $uploadedFile->id,
                    ]);
                }

                // ✅ Collect result
                $results[$input] = [
                    'file_id' => $fileId,
                    'file_name' => $fileName,
                    'file_url' => $fileUrl,
                    'db_record' => $uploadedFile,
                    'chainsaw_info' => $chainsaw,
                ];
            } catch (\Exception $e) {
                Log::error("Attachment upload error", [
                    'input' => $input,
                    'error' => $e->getMessage()
                ]);

                $results[$input] = [
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Files processed.',
            'results' => $results,
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    private function uploadToDriveAndGetFileId($file, $filePath)
    {
        // Upload the file
        Storage::disk('google')->write($filePath, file_get_contents($file));

        // Give Google Drive a moment to sync
        usleep(500000); // 0.5 seconds

        // Search for the uploaded file in Google Drive
        $pathParts = explode('/', $filePath);
        array_pop($pathParts); // remove file name
        $folderPath = implode('/', $pathParts);

        $files = Storage::disk('google')->listContents($folderPath, true);

        $fileMeta = collect($files)->first(function ($f) use ($filePath) {
            return isset($f['path']) && $f['path'] === $filePath;
        });

        return $fileMeta['extraMetadata']['id'] ?? null;
    }

    private function ensureFolderExists($folderPath)
    {
        try {
            // Remove trailing slash if present
            $folderPath = rtrim($folderPath, '/');
            $parentDir = dirname($folderPath);
            $dirName = basename($folderPath);

            $contents = Storage::disk('google')->listContents($parentDir === '.' ? '' : $parentDir, false);

            $folder = collect($contents)->first(function ($item) use ($dirName) {
                return $item['type'] === 'dir' && $item['basename'] === $dirName;
            });

            if (!$folder) {
                Storage::disk('google')->makeDirectory($folderPath);
                Log::info("Created folder structure: {$folderPath}");
                print_r("Created folder structure: {$folderPath}");
                // Wait for Google Drive to register the new folder
                sleep(5); // increase to 2-3 seconds if needed
            }
        } catch (\Exception $e) {
            Log::warning("Could not create/verify folder {$folderPath}: " . $e->getMessage());
        }
    }

    // =====================
    //
    //
    //
    // ======================

    /**
     * Replace the attachment file on Google Drive.
     * - Deletes old file (if found by file_id)
     * - Uploads the new file into the same folder as the old file (or falls back to app folder)
     */
    public function replaceAttachment($folderPath, $oldFileId, $newFile, $applicationNo, $filePrefix)
    {
        try {
            // ✅ Step 1. Delete old file if it exists
            $oldFilePath = $this->getDriveFilePath($oldFileId, $folderPath);

            if ($oldFilePath) {
                try {
                    Storage::disk('google')->delete($oldFilePath);
                    Log::info("Deleted old file from Google Drive: {$oldFilePath} (File ID: {$oldFileId})");
                } catch (\Exception $e) {
                    Log::warning("Could not delete old file (ID: {$oldFileId}): " . $e->getMessage());
                }
            } else {
                Log::warning("Old file not found in Google Drive for ID: {$oldFileId}");
            }

            // ✅ Step 2. Generate new file name
            $originalExt = strtolower($newFile->getClientOriginalExtension());
            $sanitizedApplicationNo = strtoupper($applicationNo);

            // Always final format: prefix_APPLICATIONNO.ext
            $fileName = "{$filePrefix}_{$sanitizedApplicationNo}.{$originalExt}";

            $newFilePath = trim($folderPath, '/') . '/' . $fileName;

            // ✅ Step 3. Ensure the folder exists
            $this->ensureFolderExists($folderPath);

            // ✅ Step 4. Upload new file
            Storage::disk('google')->write($newFilePath, file_get_contents($newFile));

            // Allow Google Drive to sync
            usleep(500000); // 0.5 seconds

            // ✅ Step 5. Retrieve metadata for uploaded file
            $files = Storage::disk('google')->listContents($folderPath, true);

            $fileMeta = collect($files)->first(function ($f) use ($fileName, $folderPath) {
                return isset($f['path']) && $f['path'] === trim($folderPath, '/') . '/' . $fileName;
            });

            if (!$fileMeta || !isset($fileMeta['extraMetadata']['id'])) {
                throw new \Exception("Failed to retrieve Google Drive file ID for {$fileName}");
            }

            $newFileId = $fileMeta['extraMetadata']['id'];
            $newFileUrl = "https://drive.google.com/file/d/{$newFileId}/preview";

            Log::info("File replaced successfully. ID: {$newFileId}, Name: {$fileName}");

            return [
                'status' => true,
                'file_id' => $newFileId,
                'file_name' => $fileName,
                'file_url' => $newFileUrl,
            ];

        } catch (\Exception $e) {
            Log::error("Error replacing attachment: " . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }










    /**
     * Get full Google Drive file path by fileId
     */
    private function getDriveFilePath($fileId, $folderPath)
    {
        try {
            // Get all files recursively in the folder
            $files = Storage::disk('google')->listContents($folderPath, true);

            if (empty($files)) {
                Log::warning("No files found in Google Drive folder: {$folderPath}");
                return null;
            }

            // Match file by its Google Drive ID
            $file = collect($files)->first(function ($f) use ($fileId) {
                return isset($f['extraMetadata']['id']) && $f['extraMetadata']['id'] === $fileId;
            });

            if ($file && isset($file['path'])) {
                // ✅ Extract the directory (remove filename)
                return dirname($file['path']);
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error finding Google Drive file path: " . $e->getMessage());
            return null;
        }
    }








    /**
     * THIS IS WORKING ON COMPANY_APPLY
     * Helper function to build Google Drive file path from ID
     */
    // private function getDriveFilePath($fileId, $folderPath)
    // {
    //     try {
    //         // Ensure full recursive search
    //         $files = Storage::disk('google')->listContents($folderPath, true);

    //         if (empty($files)) {
    //             Log::warning("No files found in Google Drive folder: {$folderPath}");
    //             return null;
    //         }

    //         // Find file by matching the file_id
    //         $file = collect($files)->first(function ($f) use ($fileId) {
    //             return isset($f['extraMetadata']['id']) && $f['extraMetadata']['id'] === $fileId;
    //         });

    //         if (!$file) {
    //             Log::warning("File with ID {$fileId} not found in folder: {$folderPath}");
    //             return null;
    //         }

    //         return $file['path'] ?? null;
    //     } catch (\Exception $e) {
    //         Log::error("Error finding Google Drive file path: " . $e->getMessage());
    //         return null;
    //     }
    // }


}

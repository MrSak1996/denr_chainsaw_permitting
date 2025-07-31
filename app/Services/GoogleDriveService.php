<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\ApplicantAttachments\AttachmentsModel;
use App\Models\Chainsaw\ChainsawInformation;

class GoogleDriveService
{


    public function storeAttachments($request, $applicationId, $folderStructure, $filesToUpload)
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

                // 📄 Generate filename
                if ($input === 'permit') {
                    $originalExt = $file->getClientOriginalExtension();
                    $timestamp = now()->format('Ymd_His');
                    $applicationNo = $request->input('application_no');
                    $fileName = "permit_{$applicationNo}_{$timestamp}.{$originalExt}";
                } else {
                    $filePrefix = str_replace(' ', '_', strtolower($folderType));
                    $fileName = $filePrefix . '_' . $file->getClientOriginalName();
                }

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
                    'file_id'        => $fileId,
                    'file_name'      => $fileName,
                    'file_url'       => $fileUrl,
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
                    'file_id'       => $fileId,
                    'file_name'     => $fileName,
                    'file_url'      => $fileUrl,
                    'db_record'     => $uploadedFile,
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
            'status'  => true,
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
}

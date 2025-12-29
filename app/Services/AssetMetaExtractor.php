<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class AssetMetaExtractor
{
    public function extract(string $assetType, UploadedFile $file, string $absPath): array
    {
        $meta = [
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
        ];

        if ($assetType === 'pdf') {
            $meta['pages'] = $this->pdfPages($absPath);
        }

        if ($assetType === 'video') {
            $meta['duration_sec'] = $this->videoDurationSec($absPath);
        }

        return $meta;
    }

    private function pdfPages(string $absPath): ?int
    {
        try {
            if (!class_exists(\Smalot\PdfParser\Parser::class)) return null;
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($absPath);
            $pages = $pdf->getPages();
            return is_array($pages) ? count($pages) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function videoDurationSec(string $absPath): ?int
    {
        try {
            if (!class_exists(\getID3::class)) return null;
            $getID3 = new \getID3();
            $info = $getID3->analyze($absPath);

            if (isset($info['playtime_seconds'])) {
                $sec = (int) floor((float) $info['playtime_seconds']);
                return $sec > 0 ? $sec : null;
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

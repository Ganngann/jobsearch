<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ResumeParserService
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }
    /**
     * Extrait le texte d'un fichier CV (PDF ou DOCX).
     */
    public function extractText(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        try {
            $text = '';
            if ($extension === 'pdf') {
                $text = $this->parsePdf($file->getRealPath());
                
                // Si le PDF est un scan (pas de texte extrait), on tente l'OCR
                if (empty(trim($text))) {
                    Log::info('PDF vide détecté, tentative OCR avec Gemini...');
                    $text = $this->gemini->ocr($file->getRealPath(), 'application/pdf');
                }
            } elseif ($extension === 'docx') {
                $text = $this->parseDocx($file->getRealPath());
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                Log::info('Image détectée, passage par Gemini OCR...');
                $text = $this->gemini->ocr($file->getRealPath(), $mimeType);
            } elseif ($extension === 'txt') {
                $text = file_get_contents($file->getRealPath());
            }

            return $text;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'extraction du texte du CV: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Utilise Smalot PDF Parser pour extraire le texte d'un PDF.
     */
    protected function parsePdf(string $path): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($path);
        return $pdf->getText();
    }

    /**
     * Extrait le texte d'un fichier .docx (XML compressé).
     */
    protected function parseDocx(string $path): string
    {
        $content = '';
        $zip = zip_open($path);

        if (!$zip || is_numeric($zip)) return '';

        while ($zip_entry = zip_read($zip)) {
            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

            if (zip_entry_name($zip_entry) != "word/document.xml") continue;

            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            zip_entry_close($zip_entry);
        }

        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\n", $content);

        return strip_tags($content);
    }
}

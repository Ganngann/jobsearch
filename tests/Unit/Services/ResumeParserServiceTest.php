<?php

namespace Tests\Unit\Services;

use App\Services\ResumeParserService;
use App\Services\GeminiService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class ResumeParserServiceTest extends TestCase
{
    public function test_extract_text_pdf_fallback_to_ocr()
    {
        // Mock GeminiService
        $geminiMock = Mockery::mock(GeminiService::class);

        $geminiMock->shouldReceive('ocr')
            ->once()
            ->with(Mockery::type('string'), 'application/pdf')
            ->andReturn('OCR Fallback Text');

        $geminiMock->shouldReceive('log')
            ->once()
            ->with('ocr');

        // Create a partial mock of ResumeParserService to mock the protected parsePdf method
        $service = Mockery::mock(ResumeParserService::class, [$geminiMock])->makePartial();
        $service->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('parsePdf')
            ->once()
            ->andReturn('   '); // Return empty or whitespace to trigger the fallback

        Log::shouldReceive('info')
            ->once()
            ->with('PDF vide détecté, tentative OCR avec Gemini...');

        // Create a fake PDF UploadedFile
        $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        $result = $service->extractText($file);

        $this->assertEquals('OCR Fallback Text', $result);
    }

    public function test_extract_text_pdf_success_no_fallback()
    {
        // Mock GeminiService
        $geminiMock = Mockery::mock(GeminiService::class);

        $geminiMock->shouldReceive('ocr')->never();
        $geminiMock->shouldReceive('log')->never();

        // Create a partial mock of ResumeParserService to mock the protected parsePdf method
        $service = Mockery::mock(ResumeParserService::class, [$geminiMock])->makePartial();
        $service->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('parsePdf')
            ->once()
            ->andReturn('Extracted text from PDF');

        // Create a fake PDF UploadedFile
        $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        $result = $service->extractText($file);

        $this->assertEquals('Extracted text from PDF', $result);
    }
}

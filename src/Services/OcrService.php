<?php

declare(strict_types=1);

namespace UTP\Services;

/**
 * OCR Service — Gemini 2.5 Flash API Integration
 *
 * Sends uploaded result slip images/PDFs to the Gemini API,
 * extracts text, and parses subject-grade pairs with automatic
 * subject name matching (Malay ↔ English).
 */
class OcrService
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=';
    private const MAX_FILE_SIZE = 5242880; // 5MB

    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: (getenv('GEMINI_API_KEY') ?: '');
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY is not configured.');
        }
    }

    /**
     * Subject name mapping: Malay → English system key.
     * Keys are lowercase for case-insensitive matching.
     *
     * @var array<string, string>
     */
    private static array $subjectAliases = [
        // ── Core SPM subjects (Malay names) ─────────────────────
        'bahasa melayu' => 'Bahasa Melayu',
        'bahasa malaysia' => 'Bahasa Melayu',
        'bm' => 'Bahasa Melayu',
        'bahasa inggeris' => 'English',
        'english' => 'English',
        'english language' => 'English Language',
        'bi' => 'English',
        'matematik' => 'Mathematics',
        'mathematics' => 'Mathematics',
        'math' => 'Mathematics',
        'maths' => 'Mathematics',
        'sejarah' => 'Sejarah',
        'history' => 'Sejarah',

        // ── Science subjects ────────────────────────────────────
        'matematik tambahan' => 'Additional Mathematics',
        'additional mathematics' => 'Additional Mathematics',
        'add math' => 'Additional Mathematics',
        'add maths' => 'Additional Mathematics',
        'additional math' => 'Additional Mathematics',
        'fizik' => 'Physics',
        'physics' => 'Physics',
        'kimia' => 'Chemistry',
        'chemistry' => 'Chemistry',
        'biologi' => 'Biology',
        'biology' => 'Biology',

        // ── Elective subjects ───────────────────────────────────
        'pendidikan islam' => 'Pendidikan Islam',
        'p. islam' => 'Pendidikan Islam',
        'pendidikan moral' => 'Pendidikan Moral',
        'p. moral' => 'Pendidikan Moral',
        'moral' => 'Pendidikan Moral',
        'prinsip perakaunan' => 'Prinsip Perakaunan',
        'principles of accounting' => 'Prinsip Perakaunan',
        'accounting' => 'Prinsip Perakaunan',
        'ekonomi' => 'Ekonomi',
        'economics' => 'Ekonomi',
        'perniagaan' => 'Perniagaan',
        'business' => 'Perniagaan',
        'commerce' => 'Perniagaan',
        'perdagangan' => 'Perniagaan',
        'sains komputer' => 'Sains Komputer',
        'computer science' => 'Sains Komputer',
        'grafik komunikasi teknikal' => 'Grafik Komunikasi Teknikal',
        'gkt' => 'Grafik Komunikasi Teknikal',
        'technical drawing' => 'Grafik Komunikasi Teknikal',
        'lukisan kejuruteraan' => 'Grafik Komunikasi Teknikal',
        'pendidikan seni visual' => 'Pendidikan Seni Visual',
        'seni visual' => 'Pendidikan Seni Visual',
        'reka cipta' => 'Reka Cipta',
        'sains' => 'Science',
        'science' => 'Science',
    ];

    /**
     * Language-related keywords.
     * If an unrecognised subject contains any of these, it is
     * classified as "Other Subject" (language). Otherwise it
     * is classified as "Other Non-Language Subject".
     */
    private static array $languageKeywords = [
        'bahasa', 'language', 'sastera', 'literature', 'tamil',
        'mandarin', 'arab', 'arabic', 'iban', 'kadazan', 'semai',
        'jepun', 'japanese', 'german', 'french', 'perancis',
        'korea', 'korean', 'punjabi', 'inggeris', 'english',
        'melayu', 'cina', 'chinese',
    ];

    /**
     * Valid grade values per qualification type.
     *
     * @var array<string, string[]>
     */
    private static array $validGrades = [
        'SPM' => ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'E', 'G'],
        'O-Level' => ['A*', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'U'],
        'IGCSE' => ['A*', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'U'],
    ];

    /**
     * Extract grades from an uploaded result slip file.
     *
     * @param string $filePath    Absolute path to the temp uploaded file
     * @param string $mimeType    MIME type of the file
     * @param string $qualType    Qualification type (SPM, O-Level, IGCSE)
     * @return array{grades: array, raw_text: string}
     */
    public function extractGrades(string $filePath, string $mimeType, string $qualType): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('Uploaded file not found.');
        }

        if (filesize($filePath) > self::MAX_FILE_SIZE) {
            throw new \RuntimeException('File size exceeds 1MB limit.');
        }

        Telemetry::startTimer('ocr_api_call');

        try {
            $rawText = $this->callOcrApi($filePath, $mimeType);
        } catch (\Exception $e) {
            Telemetry::trackEvent('OCR API Call Failed', ['error' => $e->getMessage()], 'ERROR');
            throw $e;
        }

        $apiTime = Telemetry::endTimer('ocr_api_call');
        Telemetry::trackEvent('OCR API Call Completed', [
            'time_ms' => $apiTime,
            'text_len' => strlen($rawText),
            'qual_type' => $qualType,
        ]);

        $grades = $this->parseGrades($rawText, $qualType);

        return [
            'grades' => $grades,
            'raw_text' => $rawText,
        ];
    }

    /**
     * Call the Gemini API and return the extracted text.
     *
     * @param string $filePath Absolute path to the file
     * @param string $mimeType MIME type
     * @return string Extracted text
     */
    private function callOcrApi(string $filePath, string $mimeType): string
    {
        $base64Data = base64_encode(file_get_contents($filePath));

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Extract the subjects and grades from this academic transcript. Return ONLY a valid JSON array of objects. Format: [{"subject": "Subject Name", "grade": "A+"}]'],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64Data
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $maxRetries = 3;
        $attempt = 0;
        $response = false;
        $httpCode = 0;
        $curlError = '';

        while ($attempt < $maxRetries) {
            $ch = curl_init();
            $curlOptions = [
                CURLOPT_URL => self::API_URL . $this->apiKey,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => true,
            ];

            if ($attempt === 0) {
                // Try forcing IPv4 first to avoid generic timeouts
                $curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }

            curl_setopt_array($ch, $curlOptions);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response !== false) {
                break; // Success!
            }

            // Retry for transient DNS/connection failures
            $attempt++;
            if ($attempt < $maxRetries) {
                sleep(1);
            }
        }

        if ($response === false) {
            throw new \RuntimeException('Gemini API request failed after ' . $maxRetries . ' attempts: ' . $curlError);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = $data['error']['message'] ?? 'Gemini API returned HTTP ' . $httpCode;
            throw new \RuntimeException('Gemini API error: ' . $errorMsg);
        }

        $fullText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty(trim($fullText))) {
            throw new \RuntimeException('Gemini could not extract any text from the document. Please try a clearer copy.');
        }

        return $fullText;
    }

    /**
     * Parse raw OCR text to extract subject-grade pairs.
     *
     * Handles various result slip formats:
     * - "Subject Name    A+"
     * - "Subject Name : A+"
     * - "FIZIK           A"
     * - Tabular formats with line-by-line entries
     *
     * @param string $rawText  OCR-extracted text
     * @param string $qualType Qualification type
     * @return array<int, array{subject: string, matched_key: string, grade: string, confidence: string}>
     */
    private function parseGrades(string $rawText, string $qualType): array
    {
        $validGrades = self::$validGrades[$qualType] ?? self::$validGrades['SPM'];

        $results = [];
        $seenSubjects = [];

        // Counters for sequential "Other Subject I/II/III" naming
        $otherCounters = [
            'language'     => 0, // Other Subject, Other Subject I, II ...
            'non_language' => 0, // Other Non-Language Subject, ... I, II ...
        ];

        // Extract JSON array from the response, ignoring any conversational text
        $jsonText = $rawText;
        if (preg_match('/\[.*\]/s', $rawText, $matches)) {
            $jsonText = $matches[0];
        }
        
        $extractedItems = json_decode(trim($jsonText), true);

        if (!is_array($extractedItems)) {
            return $results; // Fallback if AI failed to return JSON array
        }

        foreach ($extractedItems as $item) {
            if (!isset($item['subject']) || !isset($item['grade'])) {
                continue;
            }

            $rawSubject = trim((string) $item['subject']);
            $rawGrade = strtoupper(trim((string) $item['grade']));

            // Clean up subject name: remove leading numbers/bullets
            $rawSubject = preg_replace('/^[\d\.\)\]\-\s]+/', '', $rawSubject);
            $rawSubject = trim($rawSubject);

            if (empty($rawSubject) || strlen($rawSubject) < 2) {
                continue;
            }

            // Normalize the grade to exact valid format
            $grade = $this->normalizeGrade($rawGrade, $validGrades);
            if ($grade === null) {
                continue;
            }

            // Match subject to system's known subject list
            $matchResult = $this->matchSubject($rawSubject);

            // If no match found, auto-classify as Other Subject / Other Non-Language Subject
            if (empty($matchResult['matched_key'])) {
                $matchResult = $this->classifyAsOther($rawSubject, $otherCounters);
            }

            // Avoid duplicates
            $dedupeKey = strtolower($matchResult['matched_key'] ?: $rawSubject);
            if (isset($seenSubjects[$dedupeKey])) {
                continue;
            }
            $seenSubjects[$dedupeKey] = true;

            $results[] = [
                'subject' => $rawSubject,
                'matched_key' => $matchResult['matched_key'],
                'grade' => $grade,
                'confidence' => $matchResult['confidence'],
            ];
        }

        return $results;
    }

    /**
     * Match a raw OCR subject string to the system's known subject list.
     *
     * @param string $rawSubject Raw subject name from OCR
     * @return array{matched_key: string, confidence: string}
     */
    private function matchSubject(string $rawSubject): array
    {
        $normalized = strtolower(trim($rawSubject));

        // Remove common OCR noise characters
        $normalized = preg_replace('/[^a-z\s\.\']/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        // 1. Exact alias match
        if (isset(self::$subjectAliases[$normalized])) {
            return [
                'matched_key' => self::$subjectAliases[$normalized],
                'confidence' => 'high',
            ];
        }

        // 2. Partial/fuzzy match — check if any alias is contained in the subject string
        $bestMatch = null;
        $bestLen = 0;
        foreach (self::$subjectAliases as $alias => $systemKey) {
            // Check if the alias appears as a substring
            if (strpos($normalized, $alias) !== false && strlen($alias) > $bestLen) {
                $bestMatch = $systemKey;
                $bestLen = strlen($alias);
            }
            // Check if the subject appears as a substring of the alias
            if (strlen($normalized) >= 4 && strpos($alias, $normalized) !== false && strlen($alias) > $bestLen) {
                $bestMatch = $systemKey;
                $bestLen = strlen($alias);
            }
        }

        if ($bestMatch !== null) {
            return [
                'matched_key' => $bestMatch,
                'confidence' => 'medium',
            ];
        }

        // 3. Levenshtein distance for typo tolerance
        $bestDistance = PHP_INT_MAX;
        $bestLevenMatch = null;
        foreach (self::$subjectAliases as $alias => $systemKey) {
            if (abs(strlen($normalized) - strlen($alias)) > 5) {
                continue; // Skip if lengths are too different
            }
            $dist = levenshtein($normalized, $alias);
            if ($dist < $bestDistance && $dist <= 3) { // Max 3 character difference
                $bestDistance = $dist;
                $bestLevenMatch = $systemKey;
            }
        }

        if ($bestLevenMatch !== null) {
            return [
                'matched_key' => $bestLevenMatch,
                'confidence' => 'low',
            ];
        }

        // 4. No match found
        return [
            'matched_key' => '',
            'confidence' => 'none',
        ];
    }

    /**
     * Classify an unrecognised subject as "Other Subject" or
     * "Other Non-Language Subject" using sequential suffixes.
     *
     * @param  string $rawSubject      Original subject text from OCR
     * @param  array  &$otherCounters  Mutable counters for suffix tracking
     * @return array{matched_key: string, confidence: string}
     */
    private function classifyAsOther(string $rawSubject, array &$otherCounters): array
    {
        $normalized = strtolower($rawSubject);
        $isLanguage = false;

        foreach (self::$languageKeywords as $kw) {
            if (strpos($normalized, $kw) !== false) {
                $isLanguage = true;
                break;
            }
        }

        if ($isLanguage) {
            $idx = $otherCounters['language']++;
            $suffixes = ['', ' I', ' II', ' III', ' IV'];
            $key = 'Other Subject' . ($suffixes[$idx] ?? ' ' . ($idx + 1));
        } else {
            $idx = $otherCounters['non_language']++;
            $suffixes = ['', ' I', ' II', ' III', ' IV'];
            $key = 'Other Non-Language Subject' . ($suffixes[$idx] ?? ' ' . ($idx + 1));
        }

        return [
            'matched_key' => $key,
            'confidence'  => 'medium',
        ];
    }

    /**
     * Normalize a raw grade string to an exact valid grade.
     *
     * @param string   $rawGrade    Grade from OCR
     * @param string[] $validGrades Valid grade list for this qual type
     * @return string|null Normalized grade or null if invalid
     */
    private function normalizeGrade(string $rawGrade, array $validGrades): ?string
    {
        $rawGrade = strtoupper(trim($rawGrade));

        // Direct match
        foreach ($validGrades as $vg) {
            if (strtoupper($vg) === $rawGrade) {
                return $vg;
            }
        }

        // Common OCR misreads
        $corrections = [
            'A*' => 'A*',
            'A +' => 'A+',
            'A -' => 'A-',
            'B +' => 'B+',
            'B -' => 'B-',
            'C +' => 'C+',
        ];

        if (isset($corrections[$rawGrade])) {
            $corrected = $corrections[$rawGrade];
            if (in_array($corrected, $validGrades)) {
                return $corrected;
            }
        }

        return null;
    }

    /**
     * Check if a line is noise (headers, footers, page numbers, etc.)
     */
    private function isNoiseLine(string $line): bool
    {
        $line = strtolower(trim($line));

        // Skip very short lines
        if (strlen($line) < 3) {
            return true;
        }

        // Skip common header/footer patterns
        $noisePatterns = [
            'sijil pelajaran malaysia',
            'keputusan peperiksaan',
            'result slip',
            'examination result',
            'lembaga peperiksaan',
            'kementerian pendidikan',
            'ministry of education',
            'page',
            'halaman',
            'tarikh',
            'date',
            'nama calon',
            'candidate',
            'no. kad pengenalan',
            'identity card',
            'angka giliran',
            'index number',
            'pusat peperiksaan',
            'examination centre',
            'sekolah',
            'school',
            'tahun',
            'year',
            'gred',
            'grade',
        ];

        foreach ($noisePatterns as $pattern) {
            // Only treat as noise if the line is primarily this pattern
            // (not if it contains a grade after a subject name)
            if ($line === $pattern || strpos($line, $pattern) === 0) {
                return true;
            }
        }

        // Skip lines that are only numbers
        if (preg_match('/^[\d\s\.\-\/]+$/', $line)) {
            return true;
        }

        return false;
    }
}

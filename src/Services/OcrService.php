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
            throw new \RuntimeException('File size exceeds 5MB limit.');
        }

        Telemetry::startTimer('ocr_api_call');

        try {
            $rawText = $this->callOcrApi($filePath, $mimeType, $qualType);
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
     * @param string $filePath  Absolute path to the file
     * @param string $mimeType  MIME type
     * @param string $qualType  Qualification type (SPM, O-Level, IGCSE)
     * @return string Extracted text
     */
    private function callOcrApi(string $filePath, string $mimeType, string $qualType): string
    {
        $base64Data = base64_encode(file_get_contents($filePath));

        $validGrades = self::$validGrades[$qualType] ?? self::$validGrades['SPM'];
        $gradeListStr = implode(', ', $validGrades);

        $prompt = <<<PROMPT
You are an academic transcript OCR engine. Extract ALL subjects and their grades from this {$qualType} result slip image.

Qualification type: {$qualType}
Valid grades for this qualification (use ONLY these): {$gradeListStr}

Rules:
- Return ONLY a valid JSON array of objects. No explanation, no markdown fences.
- Format: [{"subject": "Subject Name", "grade": "A+"}]
- Use the exact subject name as printed (Malay or English).
- Ignore watermarks, stamps, headers, footers, candidate info, and school names.
- If a grade is unclear, pick the closest valid grade.

Examples:
SPM input: "MATEMATIK A+, FIZIK A, KIMIA B+" → [{"subject":"Matematik","grade":"A+"},{"subject":"Fizik","grade":"A"},{"subject":"Kimia","grade":"B+"}]
O-Level input: "Mathematics A, Physics B" → [{"subject":"Mathematics","grade":"A"},{"subject":"Physics","grade":"B"}]
PROMPT;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
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

        // Fallback: regex line-by-line parser if AI returned non-JSON prose
        if (!is_array($extractedItems)) {
            $extractedItems = $this->regexFallbackParse($rawText, $validGrades);
            Telemetry::trackEvent('OCR JSON Fallback Used', ['text_len' => strlen($rawText)], 'WARNING');
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
     * Regex fallback parser for when Gemini returns prose instead of JSON.
     * Scans line-by-line for "Subject ... Grade" patterns.
     *
     * @param  string   $rawText     Raw AI response text
     * @param  string[] $validGrades Valid grade values for matching
     * @return array<int, array{subject: string, grade: string}>
     */
    private function regexFallbackParse(string $rawText, array $validGrades): array
    {
        $items = [];
        $gradePattern = implode('|', array_map(function ($g) {
            return preg_quote($g, '/');
        }, $validGrades));

        $lines = preg_split('/[\r\n]+/', $rawText);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) < 4) {
                continue;
            }

            // Pattern: "Subject Name   A+" or "Subject Name : A+" or "Subject Name - A+"
            if (preg_match('/^(.+?)[\s:;\-–—]+(' . $gradePattern . ')\s*$/i', $line, $m)) {
                $subject = trim($m[1]);
                $subject = preg_replace('/^[\d\.\)\]\-\s]+/', '', $subject);
                $subject = rtrim($subject, ' :;-–—');
                if (strlen($subject) >= 2) {
                    $items[] = ['subject' => $subject, 'grade' => strtoupper(trim($m[2]))];
                }
            }
        }

        return $items;
    }
}

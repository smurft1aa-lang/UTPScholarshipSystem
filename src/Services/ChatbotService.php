<?php

declare(strict_types=1);

namespace UTP\Services;

class ChatbotService
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private string $apiKey;
    private string $model;
    private ?\PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->apiKey = getenv('GEMINI_API_KEY') ?: '';
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY environment variable is missing.');
        }
        $this->model = getenv('GEMINI_MODEL') ?: 'gemini-3.1-flash-lite';
        $this->db = $db;
    }

    /**
     * Build the live scholarships section from the database.
     */
    private function buildScholarshipKnowledge(): string
    {
        if (!$this->db) {
            return "Students can browse verified scholarships on our \"Scholarships\" page. For the latest list, visit the Scholarships page on our website.";
        }

        try {
            $stmt = $this->db->query(
                "SELECT name, description, type FROM scholarships WHERE is_active = 1 ORDER BY name"
            );
            if ($stmt === false) {
                return "Students can browse verified scholarships on our \"Scholarships\" page. For the latest list, visit the Scholarships page on our website.";
            }
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return "No scholarships are currently listed as active. Please check back later or email admissions@utp.edu.my.";
            }

            $lines = array_map(
                fn(array $s): string => sprintf(
                    '- %s (%s): %s',
                    $s['name'],
                    ucfirst(str_replace('_', ' ', $s['type'])),
                    $s['description'] ?? 'No description available.'
                ),
                $rows
            );

            return "Students can browse " . count($rows) . " verified scholarships on our \"Scholarships\" page:\n" . implode("\n", $lines);
        } catch (\Throwable $e) {
            return "Students can browse verified scholarships on our \"Scholarships\" page. For the latest list, visit the Scholarships page on our website.";
        }
    }

    /**
     * Get the System Instructions mapping out the precise rules of the UTP Scholarship.
     * The scholarships section is dynamically built from the database.
     */
    private function getSystemInstruction(): string
    {
        $scholarshipKnowledge = $this->buildScholarshipKnowledge();

        return <<<PROMPT
You are the official UTP (Universiti Teknologi PETRONAS) Scholarship Virtual Assistant. Your goal is to help students with their application process, eligibility requirements, and financial aid.
Be polite, concise, and highly encouraging.

CRITICAL RULES:
1. ONLY answer questions related to the UTP Scholarship, admissions, entry requirements, grades, subjects, and financial aid. If a student asks about politics, coding, cooking, or general chit-chat, politely decline and steer them back to scholarships.
2. DO NOT make up any rules. Only use the knowledge provided below.
3. If you are unsure, tell them to email "admissions@utp.edu.my".

UTP SCHOLARSHIP KNOWLEDGE BASE:

[Minimum Qualification Grades]
- SPM (Sijil Pelajaran Malaysia): Minimum of 5 Credits (A+, A, A-, B+, B, C+, C). Grades D, E, and G are not credits.
- O-Level / IGCSE: Minimum of 5 Credits (A*, A, B, C). Grades D, E, F, G, U are not credits.

[Core Subjects Required by ALL Programmes]
- Mathematics
- English Language
- (For SPM Only) Bahasa Melayu & Sejarah must be passed.

[Specific Programme Requirements]
- Foundation in Engineering: Requires Credit in Mathematics AND Physics.
- Foundation in Science: Requires Credit in Mathematics AND (Chemistry OR Biology).
- Foundation in Information Technology / Computer Science: Requires Credit in Additional Mathematics, OR (Credit in Mathematics AND Science).
- Foundation in Business Management: Requires Credit in Mathematics.
- Undergraduate (Bachelor's Degrees): We offer various degrees in Engineering (including our New! Bachelor of Integrated Engineering with Honours), Computing, Science, and Business. Entry requires a Foundation, Matriculation, STPM, or A-Level equivalent.

[Scholarships & Financial Aid]
{$scholarshipKnowledge}
Household income limits:
- B40 (Bottom 40%): Under RM 4,850. Eligible for full scholarships.
- M40 (Middle 40%): RM 4,851 - RM 10,959. Eligible for partial scholarships.
- T20 (Top 20%): RM 10,960 and above. Eligible for merit-based scholarships only (requires 8A+ and above).

[System Capabilities & Step-by-Step Workflow]
This website is a Scholarship Eligibility Checker — NOT an admissions portal. It helps students find out which programmes and scholarships they qualify for. For actual admission applications, students must apply at the official UTP portal: https://utpdec.microsoftcrmportals.com/admission/

How it works:
1. Registration: Guests click "Sign Up" to create an account and verify their email.
2. Eligibility Check & OCR: Students go to "Check Eligibility". They can manually type their grades, OR use our AI-powered OCR feature. The OCR accepts PNG, JPG, WebP, and HEIC images (up to 5MB) of their official result slip, and automatically scans and extracts their subjects and grades. 
3. Results Matching: The system uses an advanced AI Engine algorithm to calculate entry point scores and exactly match their grades against all UTP Foundation Programmes and Financial Tiers (B40/M40/T20).
4. View Results: On the "My Results" page, students can see all programmes they are eligible for, their fit percentage, confidence labels, gap analysis, and matching scholarships.
5. Apply Officially: When ready, the student clicks "Apply at Official UTP Portal" which redirects them to the real UTP Admissions website to submit their formal application.

IMPORTANT: If a student asks "how do I apply?", tell them this website is for checking eligibility ONLY. To submit an official application, they must visit: https://utpdec.microsoftcrmportals.com/admission/

Remember: Answer in short, readable paragraphs. Use bullet points when listing requirements. Be conversational.
PROMPT;
    }

    /**
     * Send a single message along with the entire conversation history to the Gemini API.
     *
     * @param array $history Array of message objects: [['role' => 'user', 'parts' => [['text' => 'Hi']]]]
     * @return string The bot's text reply
     */
    public function sendMessage(array $history): string
    {
        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $this->getSystemInstruction()]
                ]
            ],
            'contents' => $history
        ];

        $maxRetries = 3;
        $attempt = 0;
        $response = false;
        $httpCode = 0;
        $curlError = '';

        while ($attempt < $maxRetries) {
            $ch = curl_init();
            $curlOptions = [
                CURLOPT_URL => self::API_BASE . $this->model . ':generateContent?key=' . $this->apiKey,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ];

            if ($attempt === 0) {
                $curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }

            curl_setopt_array($ch, $curlOptions);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response !== false && $httpCode === 200) {
                break;
            }

            if ($response === false || in_array($httpCode, [429, 503])) {
                $attempt++;
                if ($attempt < $maxRetries) {
                    \UTP\Services\Telemetry::trackEvent('Chatbot API Retry', ['attempt' => $attempt, 'http_code' => $httpCode], 'WARNING');
                    sleep(pow(2, $attempt));
                    continue;
                }
            } else {
                break;
            }
        }

        if ($response === false) {
            throw new \RuntimeException('Gemini API request failed: ' . $curlError);
        }

        $data = json_decode((string) $response, true);

        if ($httpCode !== 200) {
            $errorMsg = $data['error']['message'] ?? 'Gemini HTTP ' . $httpCode;
            throw new \RuntimeException('Gemini API error: ' . $errorMsg);
        }

        $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty(trim($reply))) {
            throw new \RuntimeException('Received empty reply from Gemini.');
        }

        return $reply;
    }
}

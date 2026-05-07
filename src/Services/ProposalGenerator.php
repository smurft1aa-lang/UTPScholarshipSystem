<?php
declare(strict_types=1);

namespace UTP\Services;

class ProposalGenerator
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private string $apiKey;
    private string $model;
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
        $this->apiKey = getenv('GEMINI_API_KEY') ?: '';
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY environment variable is missing.');
        }
        $this->model = getenv('GEMINI_MODEL') ?: 'gemini-3.1-flash-lite-preview';
    }

    /**
     * Send a prompt to the Gemini API with retry logic (3 attempts, exponential backoff).
     */
    protected function callGemini(string $systemInstruction, string $prompt): string
    {
        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ]
        ];

        $apiUrl = self::API_BASE . $this->model . ':generateContent?key=' . $this->apiKey;
        $maxRetries = 3;
        $attempt = 0;
        $response = false;
        $httpCode = 0;
        $curlError = '';

        while ($attempt < $maxRetries) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response !== false && $httpCode === 200) {
                break;
            }

            // Retry on transient errors (rate limit, server error, network failure)
            if ($response === false || in_array($httpCode, [429, 503])) {
                $attempt++;
                if ($attempt < $maxRetries) {
                    Telemetry::trackEvent('ProposalGenerator API Retry', ['attempt' => $attempt, 'http_code' => $httpCode], 'WARNING');
                    sleep((int) pow(2, $attempt));
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
        return trim($reply);
    }

    /**
     * Generate a formal sponsorship proposal for a specific scholarship.
     */
    public function generateProposal(int $scholarshipId, int $userId): array
    {
        // 1. Fetch Scholarship Data
        $stmt = $this->db->prepare("SELECT * FROM scholarships WHERE id = ?");
        $stmt->execute([$scholarshipId]);
        $scholarship = $stmt->fetch();

        if (!$scholarship) {
            throw new \Exception("Scholarship not found.");
        }

        // 2. Fetch Eligible Students Count & Fit
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT er.application_id) as eligible_count, AVG(er.fit_percentage) as avg_fit
            FROM eligibility_results er
            JOIN scholarship_programme sp ON er.programme_id = sp.programme_id
            WHERE sp.scholarship_id = ? AND er.eligible = 1
        ");
        $stmt->execute([$scholarshipId]);
        $stats = $stmt->fetch();
        $eligibleCount = $stats['eligible_count'] ?? 0;
        $avgFit = round((float)($stats['avg_fit'] ?? 0), 1);

        // 2a. Fetch Breakdown of Eligible Students by Programme
        $stmt = $this->db->prepare("
            SELECT p.name, COUNT(DISTINCT er.application_id) as student_count
            FROM eligibility_results er
            JOIN programmes p ON er.programme_id = p.id
            JOIN scholarship_programme sp ON p.id = sp.programme_id
            WHERE sp.scholarship_id = ? AND er.eligible = 1
            GROUP BY p.id
        ");
        $stmt->execute([$scholarshipId]);
        $programmeBreakdown = $stmt->fetchAll();
        $breakdownText = "";
        $breakdownText = "";
        $barsHtml = "<div class='ai-stats-bars' style='margin-top: 16px; margin-bottom: 24px;'>";
        foreach ($programmeBreakdown as $pb) {
            $count = (int)$pb['student_count'];
            $pct = $eligibleCount > 0 ? round(($count / $eligibleCount) * 100) : 0;
            $breakdownText .= "- {$pb['name']}: {$count} eligible candidates\n";
            
            $barsHtml .= '<div style="margin-bottom: 12px;">';
            $barsHtml .= '<div style="margin-bottom: 4px; font-size:0.9rem; font-weight: 600;">' . htmlspecialchars($pb['name']) . ' (' . $count . ')</div>';
            $barsHtml .= '<div style="background: #e0e0e0; border-radius: 4px; height: 10px; width: 100%; overflow: hidden;">';
            $barsHtml .= '<div style="background: var(--utp-teal, #00A1B1); height: 100%; border-radius: 4px; width: ' . $pct . '%;"></div>';
            $barsHtml .= '</div></div>';
        }
        $barsHtml .= "</div>";

        // 2b. Fetch Average Fees for Covered Programmes
        $stmt = $this->db->prepare("
            SELECT AVG(p.foundation_fee) as avg_foundation_fee, AVG(p.undergraduate_fee) as avg_undergrad_fee
            FROM programmes p
            JOIN scholarship_programme sp ON p.id = sp.programme_id
            WHERE sp.scholarship_id = ?
        ");
        $stmt->execute([$scholarshipId]);
        $feeStats = $stmt->fetch();
        $avgFoundationFee = round((float)($feeStats['avg_foundation_fee'] ?? 0), 2);
        $avgUndergradFee = round((float)($feeStats['avg_undergrad_fee'] ?? 0), 2);

        // 3. Prepare Prompt
        $systemInstruction = "You are an expert grant writer and university admissions officer for Universiti Teknologi PETRONAS (UTP). Your goal is to write formal, highly professional sponsorship proposals backed by real data.";
        $prompt = <<<EOT
Generate a formal sponsorship proposal for the following scholarship:
Name: {$scholarship['name']}
Description: {$scholarship['description']}
Sponsor Budget Allocation: RM {$scholarship['budget_min']} to RM {$scholarship['budget_max']}

[DATA ANALYTICS: CANDIDATE POOL]
Total Eligible Candidates: {$eligibleCount}
Average AI-Calculated Fit Score: {$avgFit}%
Programme Distribution:
{$breakdownText}

[DATA ANALYTICS: FINANCIALS]
Average Foundation Programme Fee: RM {$avgFoundationFee} per student
Average Undergraduate Programme Fee: RM {$avgUndergradFee} per student

INSTRUCTIONS:
The proposal must be highly accurate and realistic, utilizing the exact numbers provided above. 
Do not invent broad analytics; explicitly use the student counts, programme distribution, fit scores, and average programme fees provided to justify the budget.
Calculate roughly how many students the budget can support based on the average fees, and state this in the financial overview.

**CRITICAL REQUIREMENT**: You MUST use Google Search to research the Sponsor organization (if applicable) to ensure all information is 100% factual and accurate. Avoid generic or fake information.

**CRITICAL INSTRUCTION**: For the "Candidate Demographics & Analytics" section, you MUST output the exact literal string `[INSERT_STATISTICS_BARS]` on its own line. The system will automatically inject the visual statistics bars there. Do not attempt to describe the programme distribution yourself, just put the placeholder.

The proposal should be formatted in clean HTML (using h2, h3, p, ul, li, div tags - NO markdown formatting like ```html, just the raw HTML).
It must include the following sections:
1. Executive Summary
2. Scholarship Objectives
3. Candidate Demographics & Analytics (Write a short intro paragraph about the eligible candidates and average fit score, then on a new line you MUST write exactly [INSERT_STATISTICS_BARS])
4. Financial Overview (incorporate the budget and explicitly justify it using the average programme fees and projected number of sponsored students)
5. Expected Outcomes & ROI for the Sponsor
EOT;

        $content = $this->callGemini($systemInstruction, $prompt);

        // Remove markdown code block syntax if the AI mistakenly includes it
        $content = preg_replace('/^```html\s*|\s*```$/i', '', $content);
        // Replace placeholder with actual HTML bars
        $content = str_replace('[INSERT_STATISTICS_BARS]', $barsHtml, $content);
        $content = trim($content);

        // 4. Save to Database
        $stmt = $this->db->prepare("
            INSERT INTO proposals (scholarship_id, title, content, generated_by)
            VALUES (?, ?, ?, ?)
        ");
        $title = "Sponsorship Proposal: " . $scholarship['name'];
        $stmt->execute([$scholarshipId, $title, $content, $userId]);
        $proposalId = (int)$this->db->lastInsertId();

        return [
            'id' => $proposalId,
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Generate narrative insights based on structured report data.
     */
    public function generateReportInsights(string $dateFrom, string $dateTo, array $reportData): string
    {
        $systemInstruction = "You are a Data Analyst for Universiti Teknologi PETRONAS (UTP). You analyze admissions and scholarship data and provide concise, actionable business insights.";
        $dataJson = json_encode($reportData);

        $prompt = <<<EOT
Analyze the following performance report data for the period {$dateFrom} to {$dateTo}.
Provide a brief narrative summary of the key trends, identifying top performing programmes, potential bottlenecks in the application process, and recommendations for the marketing and admissions teams.
Format the output in clean HTML (using h3, p, ul, li tags - NO markdown formatting). Keep it concise and actionable.

Data:
{$dataJson}
EOT;

        $content = $this->callGemini($systemInstruction, $prompt);
        return trim(preg_replace('/^```html\s*|\s*```$/i', '', $content));
    }

    /**
     * Generate marketing email/announcement templates.
     */
    public function generateMarketingTemplate(string $type, int $scholarshipId, string $targetAudience): string
    {
        $stmt = $this->db->prepare("SELECT * FROM scholarships WHERE id = ?");
        $stmt->execute([$scholarshipId]);
        $scholarship = $stmt->fetch();
        $schName = $scholarship ? $scholarship['name'] : 'UTP Scholarship';
        $schDesc = $scholarship ? $scholarship['description'] : '';
        $schDeadline = ($scholarship && !empty($scholarship['end_date'])) ? date('j F Y', strtotime($scholarship['end_date'])) : '30 June 2026';

        $systemUrl = getenv('APP_URL') ?: 'https://utp.edu.my';
        $portalUrl = $systemUrl . '/student/dashboard.php';

        $systemInstruction = "You are a Marketing Copywriter for Universiti Teknologi PETRONAS (UTP). Write professional, ready-to-send emails. Do NOT use any square-bracket placeholders. The ONLY placeholder you may use is exactly: [Student Name] — nothing else.";

        $prompt = <<<EOT
Write a ready-to-send marketing email for the following scenario:
Type of Content: {$type}
Target Audience: {$targetAudience}

REAL DATA TO USE (do NOT put these in brackets, use them directly):
- Scholarship Name: {$schName}
- Scholarship Description: {$schDesc}
- Application Deadline: {$schDeadline}
- Student Portal URL: {$portalUrl}
- University: Universiti Teknologi PETRONAS (UTP)
- Location: 32610 Seri Iskandar, Perak Darul Ridzuan, Malaysia

STRICT RULES:
1. Start the email with: "Dear [Student Name]," — this is the ONLY placeholder allowed.
2. For the scholarship name, write "{$schName}" directly — NOT "[Name of Scholarship]".
3. For the portal link, write "{$portalUrl}" directly — NOT "[Link to Portal]".
4. For the deadline, write "{$schDeadline}" directly — NOT "[Date]".
5. Do NOT use any other square-bracket placeholders anywhere in the email.
6. Format the output in clean HTML (using h3, p, ul, li, strong, em tags — NO markdown, no ```html).
7. Keep the tone highly encouraging, professional, and aligned with UTP's brand.
EOT;

        $content = $this->callGemini($systemInstruction, $prompt);
        return trim(preg_replace('/^```html\s*|\s*```$/i', '', $content));
    }
}

<?php

use PHPUnit\Framework\TestCase;
use UTP\Services\ProposalGenerator;

class ProposalGeneratorTest extends TestCase
{
    private \PDO $db;
    private ProposalGenerator $generator;

    protected function setUp(): void
    {
        $this->db = getDB();
        $this->db->beginTransaction();
        
        $this->db->exec("INSERT OR IGNORE INTO scholarships (id, name, description, budget_min, budget_max) VALUES (1, 'Test Scholarship', 'Desc', 1000, 5000)");
        $this->db->exec("INSERT OR IGNORE INTO programmes (id, name, category, foundation_fee, undergraduate_fee) VALUES (1, 'Engineering', 'Eng', 10000, 50000)");
        $this->db->exec("INSERT OR IGNORE INTO scholarship_programme (scholarship_id, programme_id) VALUES (1, 1)");
        $this->db->exec("INSERT OR IGNORE INTO eligibility_results (application_id, programme_id, eligible, fit_percentage) VALUES (1, 1, 1, 95.0)");
        
        putenv('GEMINI_API_KEY=test_key');
        $this->generator = new ProposalGenerator($this->db);
    }

    protected function tearDown(): void
    {
        $this->db->rollBack();
    }

    public function test_constructor_throws_on_missing_api_key()
    {
        putenv('GEMINI_API_KEY=');
        $this->expectException(\RuntimeException::class);
        new ProposalGenerator($this->db);
    }

    public function test_call_gemini_throws_on_bad_key()
    {
        $reflector = new \ReflectionClass(ProposalGenerator::class);
        $method = $reflector->getMethod('callGemini');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $method->invoke($this->generator, 'System', 'Prompt');
    }

    public function test_generate_proposal_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Scholarship not found');
        $this->generator->generateProposal(999, 1);
    }

    public function test_generate_report_insights_hits_exception()
    {
        $this->expectException(\RuntimeException::class);
        $this->generator->generateReportInsights('2026-01-01', '2026-01-31', ['data' => 123]);
    }

    public function test_generate_marketing_template_hits_exception()
    {
        $this->expectException(\RuntimeException::class);
        $this->generator->generateMarketingTemplate('email', 1, 'students');
    }
}

<?php

use PHPUnit\Framework\TestCase;
use UTP\Services\OcrService;

class OcrTest extends TestCase
{
    public function test_missing_api_key_throws_exception()
    {
        putenv('GEMINI_API_KEY=');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GEMINI_API_KEY is not configured.');
        new OcrService(null);
    }

    public function test_normalize_grade()
    {
        putenv('GEMINI_API_KEY=dummy');
        $service = new OcrService();
        $reflector = new \ReflectionClass(OcrService::class);
        $method = $reflector->getMethod('normalizeGrade');
        $method->setAccessible(true);

        $validGrades = ['A+', 'A', 'B', 'C'];
        
        $this->assertEquals('A+', $method->invoke($service, 'A+', $validGrades));
        $this->assertEquals('A+', $method->invoke($service, 'A +', $validGrades));
        $this->assertEquals('A+', $method->invoke($service, 'a+', $validGrades));
        $this->assertNull($method->invoke($service, 'Z', $validGrades));
    }

    public function test_match_subject()
    {
        putenv('GEMINI_API_KEY=dummy');
        $service = new OcrService();
        $reflector = new \ReflectionClass(OcrService::class);
        $method = $reflector->getMethod('matchSubject');
        $method->setAccessible(true);

        // Exact
        $res = $method->invoke($service, 'Matematik');
        $this->assertEquals('Mathematics', $res['matched_key']);
        $this->assertEquals('high', $res['confidence']);

        // Fuzzy/Levenshtein
        $res = $method->invoke($service, 'Mathmatics'); // Missing 'e'
        $this->assertEquals('Mathematics', $res['matched_key']);
        
        // Unknown
        $res = $method->invoke($service, 'Unknown Subject XYZ');
        $this->assertEquals('', $res['matched_key']);
        $this->assertEquals('none', $res['confidence']);
    }

    public function test_classify_as_other()
    {
        putenv('GEMINI_API_KEY=dummy');
        $service = new OcrService();
        $reflector = new \ReflectionClass(OcrService::class);
        $method = $reflector->getMethod('classifyAsOther');
        $method->setAccessible(true);

        $counters = ['language' => 0, 'non_language' => 0];

        // Language keyword
        $res = $method->invokeArgs($service, ['Bahasa Kadazan', &$counters]);
        $this->assertEquals('Other Subject', $res['matched_key']);
        $this->assertEquals(1, $counters['language']);

        // Non-language
        $res = $method->invokeArgs($service, ['Cooking', &$counters]);
        $this->assertEquals('Other Non-Language Subject', $res['matched_key']);
        $this->assertEquals(1, $counters['non_language']);
        
        // Non-language 2
        $res = $method->invokeArgs($service, ['Baking', &$counters]);
        $this->assertEquals('Other Non-Language Subject I', $res['matched_key']);
        $this->assertEquals(2, $counters['non_language']);
    }

    public function test_parse_grades_json()
    {
        putenv('GEMINI_API_KEY=dummy');
        $service = new OcrService();
        $reflector = new \ReflectionClass(OcrService::class);
        $method = $reflector->getMethod('parseGrades');
        $method->setAccessible(true);

        $json = '[{"subject": "Fizik", "grade": "A+"}, {"subject": "English", "grade": "B"}]';
        $res = $method->invoke($service, $json, 'SPM');
        
        $this->assertCount(2, $res);
        $this->assertEquals('Physics', $res[0]['matched_key']);
        $this->assertEquals('A+', $res[0]['grade']);
        $this->assertEquals('high', $res[0]['confidence']);
    }

    public function test_parse_grades_fallback()
    {
        putenv('GEMINI_API_KEY=dummy');
        $service = new OcrService();
        $reflector = new \ReflectionClass(OcrService::class);
        $method = $reflector->getMethod('parseGrades');
        $method->setAccessible(true);

        $text = "Here are your grades:\nMatematik A+\nKimia : B+";
        $res = $method->invoke($service, $text, 'SPM');
        
        $this->assertCount(2, $res);
        $this->assertEquals('Mathematics', $res[0]['matched_key']);
        $this->assertEquals('A+', $res[0]['grade']);
        $this->assertEquals('Chemistry', $res[1]['matched_key']);
        $this->assertEquals('B+', $res[1]['grade']);
    }
}
<?php

use PHPUnit\Framework\TestCase;
use UTP\Services\ChatbotService;

class ChatbotTest extends TestCase
{
    public function test_missing_api_key_throws_exception()
    {
        putenv('GEMINI_API_KEY=');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GEMINI_API_KEY environment variable is missing.');
        new ChatbotService();
    }

    public function test_send_message_with_invalid_key_throws_api_error()
    {
        putenv('GEMINI_API_KEY=invalid_test_key_123');
        $service = new ChatbotService();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gemini API error');
        
        // This will hit the network but fail quickly because of invalid key
        $service->sendMessage([['role' => 'user', 'parts' => [['text' => 'Hi']]]]);
    }

    public function test_get_system_instruction()
    {
        putenv('GEMINI_API_KEY=dummy');
        $service = new ChatbotService();
        $reflector = new \ReflectionClass(ChatbotService::class);
        $method = $reflector->getMethod('getSystemInstruction');
        $method->setAccessible(true);

        $instruction = $method->invoke($service);
        $this->assertStringContainsString('UTP (Universiti Teknologi PETRONAS)', $instruction);
        $this->assertStringContainsString('SPM (Sijil Pelajaran Malaysia)', $instruction);
        $this->assertStringContainsString('Mathematics', $instruction);
    }
}

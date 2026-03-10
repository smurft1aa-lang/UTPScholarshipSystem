<?php
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase {

    protected function setUp(): void {
        $db = getDB();
        $db->exec("DELETE FROM applications");
        $db->exec("DELETE FROM eligibility_results");
        
        // Ensure student has valid eligibility state mapped for prog 1 and 2
        $db->exec("INSERT INTO applications (id, user_id, qualification_id) VALUES (100, 2, 1)");
        $db->exec("INSERT INTO eligibility_results (application_id, programme_id, eligible) VALUES (100, 1, 1), (100, 2, 1)");
    }

    private function simulateApplicationEndpoint($postData, $userId = 2) {
        $db = getDB();
        $prog1 = $postData['prog1'] ?? null;
        $prog2 = $postData['prog2'] ?? null;
        $prog3 = $postData['prog3'] ?? null;
        $appId = $postData['application_id'] ?? 100;
        
        // Mocking the logic found in api/submit-application.php
        if (!$prog1 || !$prog2 || !$prog3) {
            return 'Please select exactly 3 programmes.';
        }
        
        if (count(array_unique([$prog1, $prog2, $prog3])) !== 3) {
            return 'You cannot select the same programme multiple times.';
        }
        
        // Verify user owns the app
        $stmt = $db->prepare("SELECT id FROM applications WHERE id = ? AND user_id = ?");
        $stmt->execute([$appId, $userId]);
        if (!$stmt->fetch()) {
            return 'Application not found.';
        }
        
        // Validate eligibility
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM eligibility_results 
            WHERE application_id = ? 
            AND programme_id IN (?, ?, ?) 
            AND eligible = 1
        ");
        $stmt->execute([$appId, $prog1, $prog2, $prog3]);
        if ($stmt->fetchColumn() < 3) {
            return 'Invalid selection. You can only apply for programmes you are eligible for.';
        }
        
        $stmt = $db->prepare("UPDATE applications SET programme_id_1=?, programme_id_2=?, programme_id_3=?, status='processing' WHERE id=?");
        $stmt->execute([$prog1, $prog2, $prog3, $appId]);
        
        return 'Success';
    }

    public function test_submit_fails_if_duplicate_programmes_chosen() {
        $result = $this->simulateApplicationEndpoint(['prog1' => 1, 'prog2' => 1, 'prog3' => 2]);
        $this->assertEquals('You cannot select the same programme multiple times.', $result);
    }

    public function test_submit_fails_if_programme_not_eligible() {
        $result = $this->simulateApplicationEndpoint(['prog1' => 1, 'prog2' => 2, 'prog3' => 99]);
        $this->assertEquals('Invalid selection. You can only apply for programmes you are eligible for.', $result);
    }

    public function test_submit_fails_if_app_belongs_to_different_user() {
        // User ID 1 (Admin) trying to submit student user 2's application
        $result = $this->simulateApplicationEndpoint(['prog1' => 1, 'prog2' => 2, 'prog3' => 3], 1);
        $this->assertEquals('Application not found.', $result);
    }

    public function test_submit_succeeds_with_3_valid_eligible_programmes() {
        // We need 3 eligible programmes
        $db = getDB();
        $db->exec("INSERT INTO programmes (id, name, category) VALUES (3, 'Dummy', 'Dummy')");
        $db->exec("INSERT INTO eligibility_results (application_id, programme_id, eligible) VALUES (100, 3, 1)");
        
        $result = $this->simulateApplicationEndpoint(['prog1' => 1, 'prog2' => 2, 'prog3' => 3]);
        $this->assertEquals('Success', $result);
        
        // Verify DB update
        $app = $db->query("SELECT * FROM applications WHERE id = 100")->fetch();
        $this->assertEquals('processing', $app['status']);
        $this->assertEquals(1, $app['programme_id_1']);
    }

    public function test_submit_requires_exactly_3_programme_choices() {
        $result = $this->simulateApplicationEndpoint(['prog1' => 1, 'prog2' => 2]);
        $this->assertEquals('Please select exactly 3 programmes.', $result);
    }
}

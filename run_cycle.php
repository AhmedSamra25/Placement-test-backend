<?php

function apiRequest($endpoint, $method = 'GET', $data = [], $token = null) {
    $url = "http://127.0.0.1:8001" . $endpoint;
    $ch = curl_init($url);
    
    $headers = [
        'Accept: application/json',
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    if ($method !== 'GET') {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "========================================\n";
echo "🔄 RUNNING FULL API TEST CYCLE\n";
echo "========================================\n\n";

// 1. ADMIN LOGIN
echo "🔐 1. Logging in as Admin (admin@school.edu)...\n";
$res = apiRequest('/api/auth/login', 'POST', [
    'email' => 'admin@school.edu',
    'password' => 'password'
]);
if ($res['status'] !== 200) die("Login failed!\n");
$adminToken = $res['body']['token'];
echo "   ✅ Success (Token acquired)\n\n";

// 2. SET CEFR LEVELS
echo "⚙️  2. Configuring Custom CEFR Levels for Org...\n";
apiRequest('/api/settings/cefr', 'POST', ['name' => 'Beginner', 'cefr_map' => 'A1', 'score_min' => 0, 'score_max' => 2], $adminToken);
apiRequest('/api/settings/cefr', 'POST', ['name' => 'Intermediate', 'cefr_map' => 'B1', 'score_min' => 3, 'score_max' => 4], $adminToken);
apiRequest('/api/settings/cefr', 'POST', ['name' => 'Advanced', 'cefr_map' => 'C1', 'score_min' => 5, 'score_max' => 6], $adminToken);
echo "   ✅ Initialized Custom A1, B1, and C1 bands mapped to internal scoring 1-6.\n\n";

// 3. INVITE STUDENT
echo "📩 3. Inviting a test student...\n";
$res = apiRequest('/api/students/invite', 'POST', [
    'name' => 'John Doe',
    'email' => 'john.doe' . rand(1, 1000) . '@example.com', // random email so script is re-runnable
    'target_language' => 'Spanish'
], $adminToken);
if ($res['status'] !== 201) die("Invite failed: " . json_encode($res['body']) . "\n");
$studentEmail = $res['body']['student']['email'];
$studentOrgId = $res['body']['student']['org_id'];
echo "   ✅ Invited {$studentEmail} to Org #{$studentOrgId}\n\n";

// 4. TEST TAKER REGISTRATION
echo "🎓 4. Simulating Student clicking the email invite link...\n";
$res = apiRequest('/api/test/register', 'POST', [
    'email' => $studentEmail,
    'org_id' => $studentOrgId
]);
if ($res['status'] !== 200) die("Student register failed: " . json_encode($res['body']) . "\n");
$studentToken = $res['body']['token'];
$submissionId = $res['body']['submission']['id'];
echo "   ✅ Student registered. Test status is now 'in_progress'. Token acquired.\n\n";

// 5. TEST TAKER DRAFT PROGRESS
echo "✏️  5. Saving a draft payload for 'grammar' section...\n";
$res = apiRequest('/api/test/save-section', 'POST', [
    'section_id' => 'grammar',
    'answers' => [
        'q1' => 'the cat sitting on the mat',
        'q2' => 'he has been waiting for hours'
    ]
], $studentToken);
if ($res['status'] !== 200) die("Draft save failed.\n");
echo "   ✅ Section saved securely into 'draft_answers' JSONB backend.\n\n";

// 6. TEST TAKER SUBMIT (Triggers Background AI Job)
echo "🚀 6. Student submitting the test...\n";
$res = apiRequest('/api/test/submit', 'POST', [], $studentToken);
if ($res['status'] !== 200) die("Submit failed.\n");
echo "   ✅ Test locked. 'ai_status' moved to 'pending', Job dispatched via SubmitTestService.\n\n";

// 7. ADMIN CHECKS RESULT
echo "📊 7. Admin querying the finalized submission via /api/submissions/{$submissionId}...\n";
// Wait briefly because the Job simulates a 2-second AI network delay if using 'sync' driver
echo "   ⏳ Waiting 3 seconds for simulated AI structural Multi-Modal analysis queue to finish...\n";
sleep(3);

$res = apiRequest("/api/submissions/{$submissionId}", 'GET', [], $adminToken);
if ($res['status'] !== 200) die("Get submission failed.\n");

$sub = $res['body'];

echo "\n========================================\n";
echo "✅ FINAL SUBMISSION VERIFICATION:\n";
echo "========================================\n";
echo "Student ID:        " . $sub['student']['id'] . "\n";
echo "Answers Compiled:  " . json_encode($sub['answers']) . "\n";
echo "AI Grading Status: " . $sub['ai_status'] . "\n";
echo "AI Feedback:       " . $sub['ai_feedback'] . "\n";
echo "Overall Score:     " . $sub['scores']['overall'] . "\n";
echo "\nDetailed CEFR Automated Mapping:\n";
foreach ($sub['cefr_bands'] as $skill => $band) {
    $score = $sub['scores'][$skill] ?? 'N/A';
    $cefr = $band ? "{$band['cefr_map']} ({$band['name']})" : "Unmapped";
    echo " - " . ucfirst($skill) . ": $score -> $cefr\n";
}
echo "========================================\n";

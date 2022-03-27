<?php

use App\Reporter;
use PHPUnit\Framework\TestCase;

class ReporterTest extends TestCase {
    public function testGetStudent() {
        $testStudents = [["id" => 1,  "firstName" => "test", "lastName" => "testing"]];
        $reporter = new Reporter($testStudents, [], [], []);
        $student = $reporter->getStudent("1");
        $this->assertEquals("test", $student["firstName"]);
    }

    public function testGetNoneExistentStudent() {
        $testStudents = [["id" => 1,  "firstName" => "test", "lastName" => "testing"]];
        $reporter = new Reporter($testStudents, [], [], []);
        $student = $reporter->getStudent("2");
        $this->assertEmpty($student);
    }

    public function testGetCompletedStudentResponseswithNoData() {
        $reporter = new Reporter([], [], [], []);
        $resps = $reporter->getCompletedStudentResponses("2");
        
        $this->assertEmpty($resps);
    }
    
    public function testGetCompletedStudentResponseswithData() {
        $reporter = new Reporter(
            [["id" => "student1", "firstName" => "test", "lastName" => "testing"]], 
            [$this->fakeStudentResponse()], 
            [],
            []
        );
        $resps = $reporter->getCompletedStudentResponses("student1");
        
        $this->assertNotEmpty($resps);
    }

    public function testGetFirstCompletedResponse() {
        $reporter = new Reporter(
            [["id" => "student1", "firstName" => "test", "lastName" => "testing"]], 
            [$this->fakeStudentResponse("16/12/2021 10:46:00"), $this->fakeStudentResponse()], 
            [],
            []
        );
        
        $resp = $reporter->getFirstCompletedResponse("student1");
        
        $this->assertNotEmpty($resp);
        $this->assertEquals("16/12/2019 10:46:00", $resp["completed"]);
        $this->assertEquals("student1", $resp["student"]["id"]);
    }
    
    public function testGetLastCompletedResponse() {
        $reporter = new Reporter(
            [["id" => "student1", "firstName" => "test", "lastName" => "testing"]], 
            [$this->fakeStudentResponse("16/12/2021 10:46:00"), $this->fakeStudentResponse()], 
            [],
            []
        );
        
        $resp = $reporter->getLastCompletedResponse("student1");
        
        $this->assertNotEmpty($resp);
        $this->assertEquals("16/12/2021 10:46:00", $resp["completed"]);
        $this->assertEquals("student1", $resp["student"]["id"]);
    }

    private function fakeStudentResponse($date = "16/12/2019 10:46:00") {
        return [
            "id" => "studentReponse1", 
            "assessmentId" => "assessment1", 
            "assigned" => "14/12/2019 10:31:00", 
            "started" => "16/12/2019 10:00:00", 
            "completed" => $date, 
            "student" => [
                "id" => "student1", 
                "yearLevel" => 3 
            ], 
            "responses" => [
                ["questionId" => "numeracy1", "response" => "option3"], 
                ["questionId" => "numeracy2", "response" => "option4"], 
                ["questionId" => "numeracy3", "response" => "option2"], 
                ["questionId" => "numeracy4", "response" => "option1"], 
                ["questionId" => "numeracy5", "response" => "option1"], 
                ["questionId" => "numeracy6", "response" => "option1"], 
                ["questionId" => "numeracy7", "response" => "option4"], 
                ["questionId" => "numeracy8", "response" => "option4"], 
                ["questionId" => "numeracy9", "response" => "option1"], 
                ["questionId" => "numeracy10", "response" => "option1"], 
                ["questionId" => "numeracy11", "response" => "option1"], 
                ["questionId" => "numeracy12", "response" => "option1"], 
                ["questionId" => "numeracy13", "response" => "option3" ], 
                ["questionId" => "numeracy14", "response" => "option2" ], 
                ["questionId" => "numeracy15",  "response" => "option1"], 
                ["questionId" => "numeracy16", "response" => "option1"] 
            ], 
            "results" => ["rawScore" => 6]
        ]; 
    }
}

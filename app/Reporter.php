<?php

namespace App;

use Symfony\Component\Console\Style\SymfonyStyle;
use DateTime;
use function array_key_exists;

class Reporter {
    
    protected array $students;
    protected array $studentResponses;
    protected array $questions;
    protected array $assessments;

    private array $student;
    private array $completedResponses;

    public function __construct(array $students, array $studentResponses, array $questions, array $assessments) {
        $this->students = $students;
        $this->studentResponses = $studentResponses;
        $this->questions = $questions;
        $this->assessments = $assessments;
    } 

    public function diagnosticReport(array $student, SymfonyStyle $io) {
        $resp = $this->getLastCompletedResponse($student["id"]);
        $assessment = $this->getAssesment($resp["assessmentId"]);

        $dateString = $this->formatDate($resp["completed"], "dS F Y h:i A");

        $io->text(
            sprintf(
                "%s %s recently completed %s assessment on %s", 
                $student["firstName"], 
                $student["lastName"], 
                $assessment["name"],
                $dateString, 
            )
        );
        
        $io->text(
            sprintf(
                "He got %d questions right out of %d. Details by strand given below:",
                $resp["results"]["rawScore"], 
                count($resp["responses"])
            )
        );
            
        $io->text("");
        
        $strandResults = $this->getStrandResults($student["id"], $resp);
        foreach ($strandResults as $k => $res) {
            $io->text(
                sprintf(
                    "%s: %d out of %d correct",
                    ucwords($k),
                    $res["count"],
                    $res["total"]
                )
            );
        }
    }

    public function progressReport(array $student, SymfonyStyle $io) {  
        $resps = $this->getCompletedStudentResponses($student["id"]);
        $resps = $this->groupResponsesByAssessment($resps);

        foreach($resps as $assessmentId => $resps) {
            $assessment = $this->getAssesment($assessmentId);
            
            $io->text(
                sprintf(
                    "%s %s has completed %s assessment %d times in total. Date and raw score given below:",
                    $student["firstName"], 
                    $student["lastName"], 
                    $assessment["name"],
                    count($resps),
                )
            );
            
            $io->text("");

            foreach($resps as $r) {
                $dateString = $this->formatDate($r["completed"], "dS F Y");
                
                $io->text(
                    sprintf(
                        "Date: %s, Raw Score: %d out of %d",
                        $dateString,
                        $r["results"]["rawScore"], 
                        count($r["responses"])
                    )
                );
            }
            
            $fr = reset($resps);
            $er = end($resps); 

            $io->text("");
            $io->text(
                sprintf(
                    "%s %s got %d more correct in the recent completed assessment than the oldest",
                    $student["firstName"], 
                    $student["lastName"],
                    $r["results"]["rawScore"] - $fr["results"]["rawScore"] 
                )
            );
        }
    }

    public function feedbackReport(array $student, SymfonyStyle $io) {
        $r = $this->getLastCompletedResponse($student["id"]);
        $assessment = $this->getAssesment($r["assessmentId"]);

        $dateString = $this->formatDate($r["completed"], "dS F Y h:i A"); 

        $io->text(
            sprintf(
                "%s %s recently completed %s assessment on %s",
                $student["firstName"], 
                $student["lastName"],   
                $assessment["name"],
                $dateString
            )
        );

        $io->text(
            sprintf(
                "He got %d questions right out of %d. Feedback for wrong answers given below",
                $r["results"]["rawScore"],
                count($r["responses"])
            )
        );

        $io->text("");

        $feedback = $this->buildFeedbackResults($student["id"], $r);
        foreach($feedback as $f) {
            $io->text(sprintf("Question: %s", $f["question"])); 
            $io->text(sprintf("Your answer: %s with value %s", $f["incorrect_label"], $f["incorrect_answer"]));
            $io->text(sprintf("Right answer: %s with value %s", $f["correct_label"], $f["correct_answer"]));
            $io->text(sprintf("Hint: %s", $f["hint"]));
            $io->text("");
        }
    }

    public function getStudent($id): array {
        if (!empty($this->student)) {
            return $this->student;
        }

        foreach ($this->students as $s) {
            if ($id == $s['id']) {
                $this->student = $s;
                return $s;
            }
        } 

        return [];
    }

    public function getCompletedStudentResponses($studentId): array {
        if (!empty($this->completedResponses)) {
            return $this->completedResponses;
        }

        $resps = [];
        foreach ($this->studentResponses as $r) {
            // TODO: add validation here to see if student exists in the json.
            if ($studentId == $r['student']['id']) {
                array_push($resps, $r);
            }
        }
        
        $resps = array_filter($resps, function($v) {
            return array_key_exists("completed", $v);
        });     
        
        // consideration: validate the datetime format instead of replacing the bad char.
        usort($resps, function($a, $b) {
           $aCompleted = str_replace("/", "-", $a["completed"]);
           $bCompleted = str_replace("/", "-", $b["completed"]);
           return (strtotime($aCompleted) < strtotime($bCompleted)) ? -1 : 1;
        });

        $this->completedResponses = $resps;

        return $resps;
    }

    public function getFirstCompletedResponse($studentId): array {
        $resps = $this->getCompletedStudentResponses($studentId);

        return reset($resps);
    }   
    
    public function getLastCompletedResponse($studentId): array {
        $resps = $this->getCompletedStudentResponses($studentId);

        return end($resps);
    }   
    
    private function getStrandResults($studentId, $response) {
        $strands = [];    
        foreach($response["responses"] as $r) {
            $q = $this->questions[$r["questionId"]];
            
            if (!array_key_exists($q["strand"], $strands)) {
                $strands[$q["strand"]] = ["count" => 0, "total" => 0];
            }

            $correct = $q["config"]["key"] == $r["response"];

            if ($correct) {
                 $strands[$q["strand"]]["count"]++;       
            }
            
            $strands[$q["strand"]]["total"]++;       
        }

        return $strands;    
    }

    private function groupResponsesByAssessment(array $resps): array {
        // key = assessmentId
        // value = ["responses"];
        $reports = [];

        foreach($resps as $r) {
            if (!array_key_exists($r["assessmentId"], $reports)) {
               $reports[$r["assessmentId"]] = [$r];
            } else {
                array_push($reports[$r["assessmentId"]], $r);
            }
        }

        return $reports; 
    }

    private function buildFeedbackResults($studentId, $response) {
        $questions = [];
        foreach($response["responses"] as $r) {
            $el = [];    
            $q = $this->questions[$r["questionId"]];
            
            $correct = $q["config"]["key"] == $r["response"];
            if ($correct) {
                continue;
            }

            foreach($q["config"]["options"] as $o) {
                if ($o["id"] == $q["config"]["key"]) {
                    $el["correct_answer"] = $o["value"];
                    $el["correct_label"] = $o["label"];
                }
                
                if ($o["id"] == $r["response"]) {
                    $el["incorrect_answer"] = $o["value"];
                    $el["incorrect_label"] = $o["label"];
                }
            }

            $el["hint"] = $q["config"]["hint"];
            $el["question"] = $q["stem"];
            
            array_push($questions, $el);
        }
    
        return $questions;
    }   

    // consideration: validate datetime.
    private function formatDate(string $timestamp, string $format): string {
        $date = str_replace("/", "-", $timestamp);
        $date = new DateTime($date);

        return $date->format($format);
    }

    private function getAssesment($id): array {
        foreach ($this->assessments as $a) {
            if ($id == $a['id']) {
                return $a;
            }
        } 

        return [];
    }
}

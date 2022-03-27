<?php

namespace App;

use App\Reporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

define("DIAGNOSTIC", 1);
define("PROGRESS", 2);
define("FEEDBACK", 3);

class ReportCommand extends Command {
    protected static $defaultName = 'generate';
    protected $students;
    protected $studentResponses;
    protected $questions;
    protected $assessments;

    protected function execute(InputInterface $input, OutputInterface $output): int {
    
        $io = new SymfonyStyle($input, $output);
        
        $studentId = $io->ask("Student ID");
        $reportType = (int) $io->ask("Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback)");


        // load data files.
        $this->loadDataFiles();

        $reporter = new Reporter($this->students, $this->studentResponses, $this->questions, $this->assessments);
        
        // validate student exsits
        $student = $reporter->getStudent($studentId);
        if (empty($student) || is_null($student)) {
            $io->error("invalid student id");
            return Command::INVALID;
        }

        switch ($reportType) {
            case DIAGNOSTIC:
                $reporter->diagnosticReport($student, $io);
                break;
            case PROGRESS:
                $reporter->progressReport($student, $io);
                break;  
            case FEEDBACK:
                $reporter->feedbackReport($student, $io);
                break;
            default:
                $io->error("Invalid report type. Supported types are 1 for Diagnostic, 2 for Progress, 3 for Feedback");
        }

        return Command::SUCCESS;
    }

    // loadDataFiles will load all the files in to memory. 
    // 
    // considerations to take would be how large can these files get? And putting in place some
    // sort of validation against the size and types.
    private function loadDataFiles() {
        $studentsData = file_get_contents("./data/students.json");
        $this->students = json_decode($studentsData, true);
            
        $assessments = file_get_contents("./data/assessments.json");
        $this->assessments = json_decode($assessments, true);

        $studentResponsesData = file_get_contents("./data/student-responses.json");
        $this->studentResponses = json_decode($studentResponsesData, true);

        $questionData = file_get_contents("./data/questions.json");
        $qs = json_decode($questionData, true);
        foreach ($qs as $q) {
            $this->questions[$q["id"]] = $q;
        }
    }
}




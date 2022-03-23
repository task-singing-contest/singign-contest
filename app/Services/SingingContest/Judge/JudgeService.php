<?php
declare(strict_types = 1);

namespace App\Services\SingingContest\Judge;

use App\Core\App;
use App\Models\ContestJudge;
use App\Models\Judge;
use App\Services\SingingContest\ContestService;

class JudgeService
{
    private $contestJudgesNumber;
    private $jugdesTotal;

    public function __construct()
    {
        $singingContestConfig = App::get('config')['singing_contest_options'];
        $this->contestJudgesNumber  = $singingContestConfig['contest_judges_number'];
        $this->jugdesTotal  = $singingContestConfig['jugdes_total'];
    }

    /**
     * @param int $createdContestId
     * @throws \Exception
     * choose 3 judges randomly out of total
     */
    public function chooseJudges(int $createdContestId): void
    {
        /**
         * get all judges
         */
        $judge = new Judge();
        $judges = $judge->all();

        /**
         * choose judges ramdomly,
         * until we have a unique array with count of three judges
         */
        $judgesChoosed = [];
        $judgesNumber = $this->contestJudgesNumber;
        while (count($judgesChoosed) < $judgesNumber) {
            $judgeChoosed = $judges[rand(0, $this->jugdesTotal - 1)]->name;
            if (!in_array($judgeChoosed, $judgesChoosed)) {
                $judgesChoosed[] = $judgeChoosed;
                $this->contestJudgesNumber--;
            }
        }

        /**
         * create data in db
         */
        $contestJudge = new ContestJudge();
        foreach ($judgesChoosed as $judge) {
            $contestJudge->add([
                'judge' => $judge,
                'contest_id' => $createdContestId
            ]);
        }
    }

    /**
     * @param float $score
     * @param int $isSick
     * @param string $roundGenre
     * @return int
     * @throws \Exception
     * calculate judge score
     */
    public function judgeCalculation(float $score, int $isSick, string $roundGenre): int
    {
        /**
         * get choosed contest judges
         */
        $contest    = new ContestService();
        $contestId  = $contest->getContestGoingOn();
        $contestJudge = new ContestJudge();
        $contestJudges = $contestJudge->where([['contest_id', '=', $contestId]])->get();

        $judgeScores = 0;
        foreach ($contestJudges as $contestJudge) {
            if ($contestJudge->judge == 'RandomJudge') {
                $judgeScores += $this->randomJudge();
            } elseif ($contestJudge->judge == 'HonestJudge') {
                $judgeScores += $this->honestJudge($score);
            } elseif ($contestJudge->judge == 'MeanJudge') {
                $judgeScores += $this->meanJudge($score);
            } elseif ($contestJudge->judge == 'RockJudge') {
                $judgeScores += $this->rockJudge($score, $roundGenre);
            } elseif ($contestJudge->judge == 'FriendlyJudge') {
                $judgeScores += $this->friendlyJudge($score, $isSick);
            }
        }

        return $judgeScores;
    }

    /**
     * @return int
     * This judge gives a random score out of 10, regardless of the calculated contestant score.
     */
    private function randomJudge(): int
    {
        return rand(1, 10);
    }

    /**
     * @param float $score
     * @return int
     * This judge converts the calculated contestant score.
     */
    private function honestJudge(float $score): int
    {
        if ($score > 0 && $score <= 10) {
            return 1;
        } elseif ($score > 10 && $score <= 20) {
            return 2;
        } elseif ($score > 20 && $score <= 30) {
            return 3;
        } elseif ($score > 30 && $score <= 40) {
            return 4;
        } elseif ($score > 40 && $score <= 50) {
            return 5;
        } elseif ($score > 50 && $score <= 60) {
            return 6;
        } elseif ($score > 60 && $score <= 70) {
            return 7;
        } elseif ($score > 70 && $score <= 80) {
            return 8;
        } elseif ($score > 80 && $score <= 90) {
            return 9;
        } elseif ($score > 90 && $score <= 100) {
            return 10;
        }
    }

    /**
     * @param float $score
     * @return int
     * This judge gives every contestant with a calculated contestant score less than 90.0 a judge score of 2.
     * Any contestant scoring 90.0 or more instead receives a 10.
     */
    private function meanJudge(float $score): int
    {
        if ($score >= 90) {
            return 10;
        }

        return 2;
    }

    /**
     * @param float $score
     * @param string $genre
     * @return int
     * This judge's favourite genre is Rock. For any other genre, the RockJudge gives a random integer score out of 10,
     * regardless of the calculated contestant score.
     * For the Rock genre, this judge gives a score based on the calculated contestant score - less than 50.0
     * results in a judge score of 5, 50.0 to 74.9 results in an 8, while 75 and above results in a 10.
     */
    private function rockJudge(float $score, string $genre): int
    {
        if ($genre != 'Rock') {
            return rand(1, 10);
        }

        if ($score < 50) {
            return 5;
        } elseif ($score >= 50 && $score < 75) {
            return 8;
        }

        return 10;
    }

    /**
     * @param float $score
     * @param int $isSick
     * @return int
     * This judge gives every contestant a score of 8
     * unless they have a calculated contestant score of less than or equal to 3.0,
     * in which case the FriendlyJudge gives a 7.
     * If the contestant is sick, the FriendlyJudge awards a bonus point, regardless of calculated contestant score.
     */
    private function friendlyJudge(float $score, int $isSick): int
    {
        $awardedSickPoint = $isSick ? 1 : 0;

        if ($score <= 3) {
            return 7 + $awardedSickPoint;
        }

        return 8 + $awardedSickPoint;
    }
}

?>
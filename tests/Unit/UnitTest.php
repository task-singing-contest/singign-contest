<?php

namespace tests\Unit;

use App\Models\Contest;
use App\Models\Contestant;
use App\Models\ContestJudge;
use App\Models\ContestRound;
use App\Models\Genre;
use App\Models\Judge;
use App\Models\RoundScore;
use App\Services\SingingContest\Contestant\ContestantService;
use App\Services\SingingContest\ContestService;
use App\Services\SingingContest\Genre\GenreService;
use App\Services\SingingContest\Round\RoundService;
use tests\TestCase;
use App\Core\{Router, Request, App};
use App\Models\User;
use App\Models\Project;

class UnitTest extends TestCase
{
    /**
     * @test
     */
    public function config_is_not_empty()
    {
        $this->assertNotEmpty(App::get('config'));
    }

    /**
     * @test
     */
    public function database_is_not_empty()
    {
        $this->assertNotEmpty(App::DB());
    }

    /**
     * @test
     */
    public function testCreateContest(): int
    {
        /**
         * if there is a contest going on,
         * update the remaining contest rounds first
         */
        $contestService = new ContestService();
        $contestId = $contestService->getContestGoingOn();
        if($contestId){
            $roundService = new RoundService();
            $contestRound = new ContestRound();

            $contestRoundData = $contestRound
                ->where(
                    [
                        ['contest_id', '=', $contestId],
                        ['finished', '=', 0]
                    ]
                )
                ->get();
            /**
             * update remaining rounds
             */
            for($y = 1; $y <= count($contestRoundData); $y++){
                $contestRoundIdUpdated = $roundService->updateCalculateRound();
                $this->assertIsInt((int)$contestRoundIdUpdated);
            }
        }

        /**
         * create contest
         */
        $contest = new Contest();
        $contestColumns = [
            'finished' => 0
        ];

        /**
         * insert data in db
         */
        $createdContest = $contest->add($contestColumns);
        $contestId = $createdContest->rows[0]->id;

        $this->assertNotEmpty($createdContest);
        return (int)$contestId;
    }

    /**
     * @depends testCreateContest
     */
    public function testCreateContestants(int $contestId): array
    {
        $contestant         = new Contestant();
        $genreService       = new GenreService();
        $contestantService  = new ContestantService();

        /**
         * get random contestants names from service
         */
        $registeredContestants = $contestantService->contestantGenerator(10);
        $this->assertNotEmpty($registeredContestants);
        $this->assertEquals(10, count($registeredContestants));

        /**
         * create data in db for every contestant
         */
        $insertedContestants = [];
        foreach ($registeredContestants as $key => $contestantName){
            /**
             * get genre strangth randomly for every contestant
             */
            $genresStreangthColumns = $genreService->getRandomGenresStreangth();
            $this->assertNotEmpty($genresStreangthColumns);
            $this->assertEquals(6, count($genresStreangthColumns));

            /**
             * create db columns
             */
            $contestantColumns = [
                'contest_id' => $contestId,
                'name'       => $contestantName,
                'score'      => 0
            ];

            /**
             * merge db columns array
             */
            $contestantColumns = array_merge($contestantColumns, $genresStreangthColumns);

            /**
             * create data in db
             */
            $insertedContestantData = $contestant->add($contestantColumns);
            $this->assertNotEmpty($insertedContestantData);

            /**
             * inserted data to return
             */
            $insertedContestants[$key]['id']     = $insertedContestantData->id;
            $insertedContestants[$key]['name']   = $contestantName;
        }

        $insertedDataContestants['contestId'] = $contestId;
        $insertedDataContestants['insertedContestants'] = $insertedContestants;

        return $insertedDataContestants;
    }

    /**
     * @depends testCreateContestants
     */
    public function testCreateRounds(array $insertedDataContestants): int
    {
        $roundScore = new RoundScore();
        $contestRount = new ContestRound();

        /**
         * get genres and change positions randomly
         */
        $genre = new Genre();
        $genres = $genre->all();
        $this->assertNotEmpty($genres);
        $this->assertEquals(6, count($genres));
        shuffle($genres);

        /**
         * create data round foreach round
         */
        $contestId = $insertedDataContestants['contestId'];
        for ($i = 0; $i < 6; $i++) {
            $roundData = array_merge(
                ['contest_round'    => $i + 1],
                ['round_genre'      => $genres[$i]->genre],
                ['contest_id'       => $contestId],
                ['finished'         => 0]
            );
            $roundData = $contestRount->add($roundData);
            $this->assertNotEmpty($roundData);

            /**
             * create data roundScore foreach contestant
             */
            foreach ($insertedDataContestants['insertedContestants'] as $insertedContestant) {
                $roundInsertedScore = $roundScore->add([
                    'contest_round'     => $i + 1,
                    'contest_id'        => $contestId,
                    'round_id'          => $roundData->id,
                    'contestant_id'     => $insertedContestant['id'],
                    'contestant_name'   => $insertedContestant['name'],
                    'contestant_score'  => 0,
                    'judge_score'       => 0,
                    'is_sick'           => 0
                ]);
                $this->assertNotEmpty($roundInsertedScore);
            }
        }

        return (int)$contestId;
    }

    /**
     * @depends testCreateRounds
     */
    public function testChooseJudges(int $contestId): int
    {
        /**
         * get all judges
         */
        $judge = new Judge();
        $judges = $judge->all();
        $this->assertNotEmpty($judges);
        $this->assertEquals(5, count($judges));

        /**
         * choose judges ramdomly,
         * until we have a unique array with count of three judges
         */
        $judgesChoosed = [];
        $contestJudgesNumber = 3;
        while (count($judgesChoosed) < 3) {
            $judgeChoosed = $judges[rand(0, 4)]->name;
            if (!in_array($judgeChoosed, $judgesChoosed)) {
                $judgesChoosed[] = $judgeChoosed;
                $contestJudgesNumber--;
            }
        }
        $this->assertNotEmpty($judgesChoosed);
        $this->assertEquals(3, count($judgesChoosed));

        /**
         * create data in db
         */
        $contestJudge = new ContestJudge();
        foreach ($judgesChoosed as $judge) {
            $contestJudge->add([
                'judge'         => $judge,
                'contest_id'    => $contestId
            ]);
            $this->assertNotEmpty($contestJudge);
        }

        return (int)$contestId;
    }

    /**
     * @depends testChooseJudges
     */
    public function testUpdateCalculatedRound($contestId): void
    {
        $roundService   = new RoundService();
        $contestRound   = new ContestRound();

        $contestRoundData = $contestRound
            ->where(
                [
                    ['contest_id', '=', $contestId],
                    ['finished', '=', 0]
                ]
            )
            ->get();
        /**
         * update remaining rounds
         */
        for($y = 1; $y <= count($contestRoundData); $y++){
            $contestRoundIdUpdated = $roundService->updateCalculateRound();
            $this->assertIsInt((int)$contestRoundIdUpdated);
        }
    }
}

?>
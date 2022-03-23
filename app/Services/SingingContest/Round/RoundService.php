<?php
declare(strict_types = 1);

namespace App\Services\SingingContest\Round;

use App\Core\App;
use App\Models\Contestant;
use App\Models\ContestRound;
use App\Models\Genre;
use App\Models\RoundScore;
use App\Services\SingingContest\Contestant\ContestantService;
use App\Services\SingingContest\ContestService;
use App\Services\SingingContest\Genre\GenreService;
use App\Services\SingingContest\Judge\JudgeService;

class RoundService
{
    private $numberOfRounds;
    private $contestRound;
    private $genre;

    public function __construct()
    {
        $this->numberOfRounds = App::get('config')['singing_contest_options']['number_of_rounds'];

        $this->contestRound = new ContestRound();
        $this->contestant   = new Contestant();
    }

    /**
     * @param int $createdContestId
     * @param array $insertedContestants
     * @throws \Exception
     * create round
     */
    public function createRounds(int $createdContestId, array $insertedContestants): void
    {
        $roundScore = new RoundScore();

        /**
         * get genres and change positions randomly
         */
        $genre = new Genre();
        $genres = $genre->all();
        shuffle($genres);

        /**
         * create data round foreach round
         */
        for ($i = 0; $i < $this->numberOfRounds; $i++) {
            $roundData = array_merge(
                ['contest_round'    => $i + 1],
                ['round_genre'      => $genres[$i]->genre],
                ['contest_id'       => $createdContestId],
                ['finished'         => 0]
            );
            $roundData = $this->contestRound->add($roundData);

            /**
             * create data roundScore foreach contestant
             */
            foreach ($insertedContestants as $insertedContestant) {
                $roundScore->add([
                    'contest_round'     => $i + 1,
                    'contest_id'        => $createdContestId,
                    'round_id'          => $roundData->id,
                    'contestant_id'     => $insertedContestant['id'],
                    'contestant_name'   => $insertedContestant['name'],
                    'contestant_score'  => 0,
                    'judge_score'       => 0,
                    'is_sick'           => 0
                ]);
            }
        }
    }

    /**
     * @param int $contestGoingOn
     * @param int $finished
     * @return array
     * @throws \Exception
     * get round data from where to start
     */
    public function getRoundData(int $contestGoingOn, int $finished = 0): array
    {
        /**
         * get data in wich round should start the contest,
         */
        $contestRoundGoingOn = $this->contestRound
            ->where(
                [
                    ['finished', '=', $finished],
                    ['contest_id', '=', $contestGoingOn]
                ],
                1
            )
            ->get();

        /**
         * collect contestant score and add to the roundData from where should start the contest
         */
        $contestantData = @$this->contestant->where([['contest_id', '=', $contestRoundGoingOn[0]->contest_id]]);

        $scoreCollection = [];
        foreach ($contestantData->rows as $key => $contestantScore) {
            $scoreCollection[$contestantScore->id]['name']      = $contestantScore->name;
            $scoreCollection[$contestantScore->id]['score']     = $contestantScore->score;
        }

        if ($contestRoundGoingOn) {
            $roundDataGoingOn = [
                'roundId'       => $contestRoundGoingOn[0]->id,
                'round'         => $contestRoundGoingOn[0]->contest_round,
                'contestId'     => $contestRoundGoingOn[0]->contest_id,
                'roundGenre'    => $contestRoundGoingOn[0]->round_genre,
                'contestScore'  => $scoreCollection
            ];
        } else {
            $roundDataGoingOn = [];
        }


        return $roundDataGoingOn;
    }

    /**
     * @return array
     * @throws \Exception
     * calculate round score
     */
    public function calculateRound(): array
    {
        $calculatedCollection = [];

        /**
         * get contestId
         */
        $contestService = new ContestService();
        $contestId = $contestService->getContestGoingOn();

        /**
         * get contestRound data
         */
        $contestRoundGoingOn = @$this->contestRound
            ->where(
                [
                    ['finished', '=', '0'],
                    ['contest_id', '=', $contestId]
                ],
                1
            )
            ->get();
        $roundId = $contestRoundGoingOn[0]->id;
        $contestRound = $contestRoundGoingOn[0]->contest_round;
        $roundGenre = $contestRoundGoingOn[0]->round_genre;

        /**
         * prepare contestRound data to the calculated collection
         */
        $calculatedCollection['round_id']       = $roundId;
        $calculatedCollection['contest_round']  = $contestRound;
        $calculatedCollection['round_genre']    = $roundGenre;
        $calculatedCollection['contest_id']     = $contestId;

        /**
         * get contestantsData
         */
        $contestantsData    = $this->contestant->where([['contest_id', '=', $contestId]])->get();

        foreach ($contestantsData as $contestantData) {
            /**
             * calculate score randomly based on genre
             * and keep track of the contestant_score and score that will be calculated from the judges
             */
            $genreService = new GenreService();
            $contestantData->contestantScore = $genreService->genreCalculation($contestantData);
            $score = $genreService->genreCalculation($contestantData);

            /**
             * if contestant is sick,
             * contestant score will be halved before the judges calculate
             */
            $contestantService = new ContestantService();
            $contestantIsSick = $contestantService->contestantIsSick();
            if($contestantIsSick){
                $score = round(($score / 2), 1);
            }

            /**
             * get contest judges and calculate judges score
             */
            $judgeService = new JudgeService();
            $judgeScores = $judgeService->judgeCalculation($score, $contestantIsSick, $roundGenre);

            /**
             * add judges score and calculated data to the calculated collection
             */
            $contestantData->judgeScore                 = $judgeScores;
            $contestantData->isSick                     = $contestantIsSick;
            $calculatedCollection['contestantData'][]   = $contestantData;
        }

        return $calculatedCollection;
    }

    /**
     * @return int
     * @throws \Exception
     * Update calculated round in db
     */
    public function updateCalculateRound(): int
    {
        /**
         * get calculated array score
         */
        $calculatedScore = $this->calculateRound();

        /**
         * update contest_rounds
         */
        App::DB()->updateWhere('contest_rounds', [
            'finished' => 1
        ],
            [
                ['id', '=', $calculatedScore['round_id']]
            ]
        );

        /**
         * update round_score
         */
        $totalContestansScoreCollection = [];
        foreach ($calculatedScore['contestantData'] as $contestantData){

            App::DB()->updateWhere('round_score', [
                'contestant_score'  => $contestantData->contestantScore,
                'judge_score'       => $contestantData->judgeScore,
                'is_sick'           => $contestantData->isSick
            ],
                [
                    ['round_id', '=', $calculatedScore['round_id']],
                    ['contestant_id', '=', $contestantData->id],
                ]
            );

            $score = $contestantData->score + $contestantData->judgeScore;
            $totalContestansScoreCollection[$contestantData->id] = $score;
            App::DB()->updateWhere('contestants', [
                'score'  => $score,
            ],
                [
                    ['id', '=', $contestantData->id],
                ]
            );
        }

        /**
         * if the contest is at the end, update also the contest table
         */
        if ($calculatedScore['contest_round'] == 6) {

            /**
             * update contest_rounds
             */
            $winners = $this->getWinners($totalContestansScoreCollection);
            foreach ($winners as $idWinner => $score) {
                App::DB()->updateWhere('contestants', [
                    'winner' => 1
                ],
                    [
                        ['id', '=', $idWinner]
                    ]
                );
            }

            /**
             * update contest_rounds
             */
            App::DB()->updateWhere('contests', [
                'finished' => 1
            ],
                [
                    ['id', '=', $calculatedScore['contest_id']]
                ]
            );
        }

        /**
         * return updated contest round id
         */
        return (int)$calculatedScore['contest_round'];
    }

    /**
     * @param array $contestansPoints
     * @return array
     * Update calculated round in db
     */
    public function getWinners(array $contestansPoints): array
    {
        $maxPoints = max($contestansPoints);

        $winners = [];
        foreach ($contestansPoints as $key => $contestansPoint) {
            if ($contestansPoint == $maxPoints) {
                $winners[$key] = $contestansPoint;
            }
        }

        return $winners;
    }
}

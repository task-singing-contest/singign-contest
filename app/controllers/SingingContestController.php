<?php
declare(strict_types = 1);

namespace App\Controllers;

use App\Core\App;
use App\Services\SingingContest\ContestService;
use App\Services\SingingContest\Round\RoundService;
use App\Services\SingingContest\ServiceManager;

class SingingContestController
{
    private $numberOfRounds;

    private $roundService;
    private $contestService;
    private $contestantService;
    private $serviceManager;


    public function __construct()
    {
        $this->numberOfRounds = App::get('config')['singing_contest_options']['number_of_rounds'];

        $this->roundService         = new RoundService();
        $this->contestService       = new ContestService();
        $this->serviceManager       = new ServiceManager();
    }

    /**
     * @return mixed
     * This function returns starting contest page.
     */
    public function indexAction()
    {
        return view('index');
    }

    /**
     * @return mixed|void
     * create the singing contest.
     */
    public function createAction()
    {
        /**
         * check if a contest is going on
         */
        $contestId = $this->contestService->getContestGoingOn();
        if($contestId){
            /**
             * get round data from where to start
             */
            $roundDataGoingOn = $this->roundService->getRoundData($contestId);
            return view('show', compact('roundDataGoingOn'));
        }

        /**
         * create contest data and go to rounds
         */
        $this->serviceManager->createContest();
        return redirect('show');
    }

    /**
     * @return mixed
     * round from where to start
     */
    public function showAction()
    {
        $contestId = $this->contestService->getContestGoingOn();
        $roundDataGoingOn = $this->roundService->getRoundData($contestId);
        if (!$roundDataGoingOn) {
            return view('index');
        }

        return view('show', compact('roundDataGoingOn'));
    }

    /**
     * @return mixed
     * get last contest data and return final_score
     */
    public function finalRoundAction()
    {
        $contestId = $this->contestService->getContestGoingOn(1);
        $roundDataGoingOn = $this->roundService->getRoundData($contestId, 1);
        return view('final_score', compact('roundDataGoingOn'));
    }

    /**
     * @return mixed
     * show best contestants history
     */
    public function historyAction()
    {
        $bestContestants = App::DB()->raw('SELECT name, score FROM contestants WHERE winner = "1" ORDER BY score DESC LIMIT 10');
        return view('history', compact('bestContestants'));
    }

    /**
     * calculate and update round score
     */
    public function roundsAction()
    {
        $updatedRound = $this->roundService->updateCalculateRound();

        if ($updatedRound == $this->numberOfRounds){
            return redirect('final-round');
        }

        return redirect('show');
    }
}

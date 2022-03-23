<?php
declare(strict_types = 1);

namespace App\Services\SingingContest;

use App\Models\Contest;

class ContestService{

    public $contest;

    public function __construct()
    {
        $this->contest = new Contest();
    }

    /**
     * create contest
     */
    public function createContest(): int
    {
        $contestColumns = [
            'finished' => 0
        ];

        /**
         * insert data in db
         */
        $createdContest = $this->contest->add($contestColumns);

        return (int)$createdContest->id;
    }

    /**
     * get contestId that is going on and not finished
     */
    public function getContestGoingOn(int $finished = 0): int
    {
        /**
         * get contestId
         */
        $contestGoingOn = $this->contest->where([['finished', '=', $finished]], 1)->get();

        if ($contestGoingOn) {
            return (int)$contestGoingOn[0]->id;
        }

        return 0;
    }
}

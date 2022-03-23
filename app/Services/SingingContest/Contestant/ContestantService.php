<?php
declare(strict_types = 1);

namespace App\Services\SingingContest\Contestant;

use App\Core\App;
use App\Models\Contestant;
use App\Services\SingingContest\Genre\GenreService;

class ContestantService{

    private $contestantNumber;

    public function __construct()
    {
        $this->contestantNumber = App::get('config')['singing_contest_options']['contestant_number'];
    }

    /**
     * @param int $contestantsNumber
     * @return array
     * @throws \Exception
     * get 10 random contesant names from api
     */
    public function contestantGenerator(int $contestantsNumber) : array
    {
        $randomContestants = [];

        while($contestantsNumber != 0){
            $contestantData = getApiRequest("https://randomuser.me/api/");
            if(!$contestantData){
                throw new \Exception("Error getting random names from api!");
            }

            $firstName = @$contestantData['results'][0]['name']['first'];
            $lastName = @$contestantData['results'][0]['name']['last'];
            if(!$firstName || !$lastName){
                throw new \Exception("Error retriving name from api!");
            }

            $contestantColumn = 'contestant_' . $contestantsNumber;
            $randomContestants[$contestantColumn] = $firstName . " " . $lastName;
            $contestantsNumber--;
        }

        return $randomContestants;
    }

    /**
     * @param int $createdContestId
     * @return array
     * @throws \Exception
     * create contestants
     */
    public function createContestants(int $createdContestId): array
    {
        $contestant     = new Contestant();
        $genreService   = new GenreService();

        /**
         * get random contestants names from service
         */
        $registeredContestants = $this->contestantGenerator($this->contestantNumber);

        /**
         * create data in db for every contestant
         */
        $insertedContestants = [];
        foreach ($registeredContestants as $key => $contestantName){
            /**
             * get genre strangth randomly for every contestant
             */
            $genresStreangthColumns = $genreService->getRandomGenresStreangth();

            /**
             * create db columns
             */
            $contestantColumns = [
                'contest_id' => $createdContestId,
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

            /**
             * inserted data to return
             */
            $insertedContestants[$key]['id']     = $insertedContestantData->id;
            $insertedContestants[$key]['name']   = $contestantName;
        }

        return $insertedContestants;
    }

    /**
     * @return int
     * There is a 5% chance that a contestant will become sick during any round.
     */
    public function contestantIsSick(): int
    {
        return rand(1, 20) == 1 ? 1 : 0;
    }
}

?>
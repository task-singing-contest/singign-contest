<?php
declare(strict_types = 1);

namespace App\Services\SingingContest\Genre;

use App\Core\App;
use App\Models\Contestant;
use App\Models\Genre;

class GenreService{

    private $genreMinStreanght;
    private $genreMaxStreanght;
    private $minMultiple;
    private $maxMultiple;
    private $decimalMultiple;

    public function __construct()
    {
        $singingContestConfig = App::get('config')['singing_contest_options'];
        $this->genreMinStreanght = $singingContestConfig['genre_min_streanght'];
        $this->genreMaxStreanght = $singingContestConfig['genre_max_streanght'];
        $this->minMultiple = $singingContestConfig['min_multiple'];
        $this->maxMultiple = $singingContestConfig['max_multiple'];
        $this->decimalMultiple = $singingContestConfig['decimal_multiple'];
    }

    /**
     * @return array
     * @throws \Exception
     * get random genre strangth
     */
    public function getRandomGenresStreangth(): array
    {
        $genre  = new Genre();
        $genres = $genre->all();

        /**
         * string to snack case
         */
        $genresStreangth = [];
        foreach ($genres as $genre) {
            $genreStreangth = $this->stringToSnakCase($genre->genre) . "_strength";
            $genresStreangth[$genreStreangth] = rand($this->genreMinStreanght, $this->genreMaxStreanght);
        }

        return $genresStreangth;
    }

    /**
     * @param string $string
     * @return string
     * convert string to snake
     */
    private function stringToSnakCase(string $string): string
    {
        return str_replace(" ", "_", strtolower($string));
    }

    /**
     * @param Contestant $contestantData
     * @return float
     * calculate genre score
     */
    public function genreCalculation(Contestant $contestantData): float
    {
        $score = 0;
        $score += $contestantData->rock_strength        * frand($this->minMultiple, $this->maxMultiple, $this->decimalMultiple);
        $score += $contestantData->country_strength     * frand($this->minMultiple, $this->maxMultiple, $this->decimalMultiple);
        $score += $contestantData->pop_strength         * frand($this->minMultiple, $this->maxMultiple, $this->decimalMultiple);
        $score += $contestantData->disco_strength       * frand($this->minMultiple, $this->maxMultiple, $this->decimalMultiple);
        $score += $contestantData->jazz_strength        * frand($this->minMultiple, $this->maxMultiple, $this->decimalMultiple);
        $score += $contestantData->the_blues_strength   * frand($this->minMultiple, $this->maxMultiple, $this->decimalMultiple);
        $score = round(($score / 6), 1);

        return (float)$score;
    }
}

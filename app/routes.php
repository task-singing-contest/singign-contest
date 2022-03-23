<?php
Use App\Core\App;

    $router->get('', 'SingingContestController@indexAction');
    $router->get('create', 'SingingContestController@createAction');
    $router->get('show', 'SingingContestController@showAction');
    $router->get('final-round', 'SingingContestController@finalRoundAction');
    $router->get('history', 'SingingContestController@historyAction');

    $router->post('rounds', 'SingingContestController@roundsAction');
    $router->post('users', 'UsersController@store');

?>

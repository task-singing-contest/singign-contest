<?php require ('partials/header.php'); ?>

<h1 style="margin-left: 20px;">Round <?=$roundDataGoingOn['round']?></h1>
<h1 style="margin-left: 20px;">Genre <?=$roundDataGoingOn['roundGenre']?></h1>

<form action="/rounds" method="post">
<?php if ($roundDataGoingOn['round'] == 6) { ?>
    <button class="btn-primary roundButton">Go To Final Round</button>
<?php } else {?>
    <button class="btn-primary roundButton">Go To Round <?=$roundDataGoingOn['round']+1?></button>
<?php } ?>
</form>

<table id="contestant">
    <tr>
        <th>Name</th>
        <th>Points</th>
    </tr>

<?php foreach ($roundDataGoingOn['contestScore'] as $key => $contestPoint){ ?>
    <tr id="<?=$key?>">
        <td><?=$contestPoint['name']?></td>
        <td><?=$contestPoint['score']?></td>
    </tr>
<?php } ?>
</table>

<?php require ('partials/footer.php'); ?>

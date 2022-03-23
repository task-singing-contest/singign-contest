<?php require ('partials/header.php'); ?>

<h1 style="margin-left: 20px;">Final Score</h1>

<form action="/create">
    <button class="btn-primary roundButton">Start a new Contest</button>
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

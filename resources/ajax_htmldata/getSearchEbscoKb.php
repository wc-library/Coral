<?php

    EbscoKbService::setSearch($_POST['search']);
	$params = EbscoKbService::getSearch();
	$page = $params['offset'];
	$recordsPerPage = $params['count'];

    //determine starting rec - keeping this based on 0 to make the math easier, we'll add 1 to the display only
    //page will remain based at 1
    if ($page == '1'){
        $startingRecNumber = 0;
    }else{
        $startingRecNumber = ($page * $recordsPerPage) - $recordsPerPage;
    }

    $ebscoKb = new EbscoKbService();
    echo var_dump($params);
    $ebscoKb->createQuery($params);
    $results = $ebscoKb->execute();
?>

<?php if(!empty($results->Errors)): ?>
    <p style="margin-bottom: 2em;"><i>Sorry, there was an error with your query.</i></p>
    <?php foreach($results->Errors as $error): ?>
        <br /><br />
        <ul>
            <li><?php echo $error->Message ?></li>
        </ul>
    <?php endforeach; ?>
<?php else: ?>

<?php

    echo '<pre>';
    echo print_r($results);
    echo '</pre>';
?>

<?php endif; ?>




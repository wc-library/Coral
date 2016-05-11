<?php
function yield_test_results($test_results, $completion)
{
	header('Content-type: application/json');
	$test_results->completion = $completion*100;
	echo json_encode($test_results);
	exit();
}

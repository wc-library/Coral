<?php
function yield_test_results_and_exit($test_results, $completed_tests, $completion)
{
	header('Content-type: application/json');
	$test_results->completion = $completion*100;
	$test_results->completed_tests = $completed_tests;
	echo json_encode($test_results);
	exit();
}

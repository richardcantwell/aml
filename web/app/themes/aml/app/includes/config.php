<?php
namespace Custom\Config;


function get_config() {
	$data = [
		'course_details' => [
			'code',
			'level',
			'title',
		],
		'fee_comp' => [
			'student_contribution',
			'covered_under_ffi',
			'levy',
			'tuition',
			'total',
		],
		'fee_options' => [
			'non-grantholder',
			'susi_grantholder_100',
			'susi_grantholder_50',
			'free_fee_ineligible',
			'non_eu',
			'pg_susi_grantholder_2000',
			'pg_susi_grantholder_full',
		],
		'student_details' => [
			'course',
		],
	];
	return $data;
}
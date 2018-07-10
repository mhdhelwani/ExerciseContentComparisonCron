<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Interface ilExerciseContentComparisonCronLogWriter
 */
interface ilExerciseContentComparisonCronLogWriter
{
	/**
	 * @param array $message
	 * @return void
	 */
	public function write(array $message);

	/**
	 * @return void
	 */
	public function shutdown();
}
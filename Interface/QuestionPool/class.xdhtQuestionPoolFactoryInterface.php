<?php
/**
 * Class xdhtQuestionPoolFactoryInterface
 *
 * @author: Benjamin Seglias   <bs@studer-raimann.ch>
 */

interface xdhtQuestionPoolFactoryInterface {

	/**
	 * @param integer $id
	 *
	 * @return array with object data
	 */
	public function getQuestionPoolObjectById($id);

	/**
	 *
	 * @description Returns question pool objects
	 *
	 * @return array of question pool objects
	 */
	public function getQuestionPools();

	/**
	 *
	 * @description Returns question pool ids
	 *
	 * @return array of question pool ids
	 */
	public function getQuestionPoolIds();

	/**
	 *
	 * @description  Returns an array which can be used to show options in select input gui
	 *
	 * @return array consisting of ref_id as key and title as options text
	 */
	public function getSelectOptionsArray();

}
<?php

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");
include_once("./Services/Tracking/interfaces/interface.ilLPStatusPlugin.php");

/**
 * Class ilObjDhbwTraining
 *
 * @author: Benjamin Seglias   <bs@studer-raimann.ch>
 */

class ilObjDhbwTraining extends ilObjectPlugin  implements ilLPStatusPluginInterface {


	/**
	 * Constructor
	 *
	 * @access        public
	 *
	 * @param int $a_ref_id
	 */
	public function __construct($a_ref_id = 0) {
		parent::__construct($a_ref_id);
	}


	protected function initType() {
		$this->setType(ilDhbwTrainingPlugin::PLUGIN_PREFIX);
	}


	public function doCreate() {
		parent::doCreate(); // TODO: Change the autogenerated stub
	}


	public function doRead() {
		parent::doRead(); // TODO: Change the autogenerated stub
	}


	public function doUpdate() {
		parent::doUpdate(); // TODO: Change the autogenerated stub
	}


	public function doDelete() {
		parent::doDelete(); // TODO: Change the autogenerated stub
	}


	/**
	 * @param ilObjDhbwTraining $new_obj Instance of
	 * @param int                   $a_target_id obj_id of the new created object
	 * @param int                   $a_copy_id
	 *
	 * @return bool|void
	 */
	public function doCloneObject($new_obj, $a_target_id, $a_copy_id = NULL) {
		assert(is_a($new_obj, ilObjAssistedExercise::class));
	}

	/**
	 * Get all participants ids with LP status completed
	 *
	 * @return array
	 */
	public function getLPCompleted()
	{
		return xdhtParticipant::where(array(
			'status' => ilLPStatus::LP_STATUS_COMPLETED_NUM,
			'training_obj_id' => $this->getId()
		))->getArray(null, 'usr_id');
	}

	/**
	 * Get all participants ids with LP status not attempted
	 *
	 * @return array
	 */
	public function getLPNotAttempted()
	{
		$operators = array(
			'status' => '!=',
			'training_obj_id' => '='
		);
		$other_than_not_attempted = xdhtParticipant::where(array(
			'status' => ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM,
			'training_obj_id' => $this->getId()
		), $operators)->getArray(null, 'usr_id');

		return array_diff($this->plugin->getMembers($this->plugin->lookupRefId($this->getId())), $other_than_not_attempted);
	}
	/**
	 * Get all participants ids with LP status failed
	 *
	 * @return array
	 */
	public function getLPFailed()
	{
		return xdhtParticipant::where(array(
			'status' => ilLPStatus::LP_STATUS_FAILED_NUM,
			'training_obj_id' => $this->getId()
		))->getArray(null, 'usr_id');
	}
	/**
	 * Get all participants ids with LP status in progress
	 *
	 * @return array
	 */
	public function getLPInProgress()
	{
		return xdhtParticipant::where(array(
			'status' => ilLPStatus::LP_STATUS_IN_PROGRESS_NUM,
			'training_obj_id' => $this->getId()
		))->getArray(null, 'usr_id');
	}
	/**
	 * Get current status for given participant
	 *
	 * @param int $a_participant_id
	 * @return int
	 */
	public function getLPStatusForUser($a_participant_id)
	{
		$participant = xdhtParticipant::where(array(
			'usr_id' => $a_participant_id,
			'training_obj_id' => $this->getId()
		))->first();
		if ($participant) {
			return $participant->getStatus();
		}
		return ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
	}



}
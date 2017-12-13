<?php
/**
 * Class xdhtParticipantTableGUI
 *
 * @author: Benjamin Seglias   <bs@studer-raimann.ch>
 */

class xdhtParticipantTableGUI extends ilTable2GUI {
	use xdhtDIC;

	const TBL_ID = 'tbl_xdht_participants';
	/**
	 * @var array
	 */
	protected $filter = [];
	/**
	 * @var xdhtParticipantGUI
	 */
	protected $parent_obj;
	/**
	 * @var xdhtParticipant
	 */
	public $xdht_participant;
	/**
	 * @var xdhtObjectFacadeInterface
	 */
	protected $facade;


	/**
	 * ilLocationDataTableGUI constructor.
	 *
	 * @param xdhtParticipantGUI $a_parent_obj
	 */
	function __construct($a_parent_obj, $a_parent_cmd, xdhtObjectFacadeInterface $facade) {
		$this->parent_obj = $a_parent_obj;
		$this->facade = $facade;

		$this->setId(self::TBL_ID);
		$this->setPrefix(self::TBL_ID);
		$this->setFormName(self::TBL_ID);
		$this->ctrl()->saveParameter($a_parent_obj, $this->getNavParameter());
		$this->xdht_participant = new xdhtParticipant($_GET[xdhtParticipantGUI::PARTICIPANT_IDENTIFIER]);

		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setRowTemplate("tpl.participants.html", "Customizing/global/plugins/Services/Repository/RepositoryObject/DhbwTraining");

		$this->setFormAction($this->ctrl()->getFormActionByClass(xdhtParticipantGUI::class));
		$this->setExternalSorting(true);

		$this->setDefaultOrderField("item_title");
		$this->setDefaultOrderDirection("asc");
		$this->setExternalSegmentation(true);
		$this->setEnableHeader(true);

		$this->initColums();
		$this->addFilterItems();
		$this->parseData();
	}


	protected function addFilterItems() {
		$participant_name = new ilTextInputGUI($this->pl()->txt('participant_name'), 'participant_name');
		$this->addAndReadFilterItem($participant_name);

		$usr_name = new ilTextInputGUI($this->pl()->txt('usr_name'), 'usr_name');
		$this->addAndReadFilterItem($usr_name);

		include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
		$option[0] = $this->pl()->txt('not_attempted');
		$option[1] = $this->pl()->txt('in_progress');
		$option[2] = $this->pl()->txt('edited');

		$status = new ilSelectInputGUI($this->pl()->txt("learning_progress"), "learning_progress");
		$status->setOptions($option);
		$this->addAndReadFilterItem($status);
	}


	/**
	 * @param $item
	 */
	protected function addAndReadFilterItem(ilFormPropertyGUI $item) {
		$this->addFilterItem($item);
		$item->readFromSession();
		if ($item instanceof ilCheckboxInputGUI) {
			$this->filter[$item->getPostVar()] = $item->getChecked();
		} else {
			$this->filter[$item->getPostVar()] = $item->getValue();
		}
	}

	protected function getUserDataById($usr_id) {
		global $ilDB;
		$q = "SELECT * FROM usr_data WHERE id = " . $ilDB->quote($usr_id, "integer");
		$usr_set = $ilDB->query($q);
		$usr_rec = $ilDB->fetchAssoc($usr_set);

		return $usr_rec;
	}

	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		/**
		 * @var xdhtParticipant $xdhtParticipant
		 */
		$xdhtParticipant = xdhtParticipant::find($a_set['id']);

		$usr_data = $this->getUserDataById($xdhtParticipant->getUsrId());

		if ($this->isColumnSelected('participant_name')) {
			$this->tpl->setCurrentBlock("PARTICIPANT_NAME");
			$this->tpl->setVariable('PARTICIPANT_NAME', $usr_data['firstname'] . " " . $usr_data['lastname']);
			$this->tpl->parseCurrentBlock();
		}

		if ($this->isColumnSelected('usr_name')) {
			$this->tpl->setCurrentBlock("USR_NAME");
			$this->tpl->setVariable('USR_NAME', $usr_data['login']);
			$this->tpl->parseCurrentBlock();
		}

		if ($this->isColumnSelected('learning_progress')) {
			$this->tpl->setCurrentBlock("LEARNING_PROGRESS");
			$this->tpl->setVariable('LEARNING_PROGRESS', $xdhtParticipant->getStatus());
			$this->tpl->parseCurrentBlock();
		}

		if ($this->isColumnSelected('first_access')) {
			$this->tpl->setCurrentBlock("FIRST_ACCESS");
			$this->tpl->setVariable('FIRST_ACCESS', $xdhtParticipant->getCreated());
			$this->tpl->parseCurrentBlock();
		}

		if ($this->isColumnSelected('last_access')) {
			$this->tpl->setCurrentBlock("LAST_ACCESS");
			$this->tpl->setVariable('LAST_ACCESS', $xdhtParticipant->getUpdated());
			$this->tpl->parseCurrentBlock();
		}

		$this->addActionMenu($xdhtParticipant);
	}

	protected function initColums() {

		$number_of_selected_columns = count($this->getSelectedColumns());
		//add one to the number of columns for the action
		$number_of_selected_columns ++;
		$column_width = 100 / $number_of_selected_columns . '%';

		$all_cols = $this->getSelectableColumns();
		foreach ($this->getSelectedColumns() as $col) {

			$this->addColumn($all_cols[$col]['txt'], "", $column_width);

		}

		$this->addColumn($this->pl()->txt('common_actions'), '', $column_width);
	}


	/**
	 * @param xdhtParticipant $xdhtParticipant
	 */
	protected function addActionMenu(xdhtParticipant $xdhtParticipant) {
		$current_selection_list = new ilAdvancedSelectionListGUI();
		$current_selection_list->setListTitle($this->pl()->txt('common_actions'));
		$current_selection_list->setId('participants_actions_' . $xdhtParticipant->getId());
		$current_selection_list->setUseImages(false);

		$this->ctrl()->setParameter($this->parent_obj, xdhtParticipantGUI::PARTICIPANT_IDENTIFIER, $xdhtParticipant->getId());

		if (ilObjDhbwTrainingAccess::hasWriteAccess()) {
			$current_selection_list->addItem($this->pl()->txt('view_participant'), xdhtParticipantGUI::CMD_STANDARD, $this->ctrl()->getLinkTargetByClass(xdhtParticipantGUI::class, xdhtParticipantGUI::CMD_STANDARD));
		}
		$this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
	}

	protected function parseData() {
		$this->determineOffsetAndOrder();
		$this->determineLimit();

		$collection = xdhtParticipant::getCollection();
		$collection->where(array( 'training_obj_id' => $this->facade->settings()->getDhbwTrainingObjectId() ));

		$sorting_column = $this->getOrderField() ? $this->getOrderField() : 'name';
		$offset = $this->getOffset() ? $this->getOffset() : 0;

		$sorting_direction = $this->getOrderDirection();
		$num = $this->getLimit();

		$collection->orderBy($sorting_column, $sorting_direction);
		$collection->limit($offset, $num);

		foreach ($this->filter as $filter_key => $filter_value) {
			switch ($filter_key) {
				case 'name':
				case 'usr_name':
					$collection->where(array( $filter_key => '%' . $filter_value . '%' ), 'LIKE');
					break;
				case 'learning_progress':
					if (!empty($filter_value)) {
						$collection->where(array( $filter_key => $filter_value ), '=');
						break;
					}
			}
		}
		$this->setData($collection->getArray());
	}


	public function getSelectableColumns() {
		$cols["participant_name"] = array(
			"txt" => $this->pl()->txt("participant_name"),
			"default" => true
		);
		$cols["usr_name"] = array(
			"txt" => $this->pl()->txt("usr_name"),
			"default" => true
		);
		$cols["learning_progress"] = array(
			"txt" => $this->pl()->txt("learning_progress"),
			"default" => true
		);
		$cols["first_access"] = array(
			"txt" => $this->pl()->txt("first_access"),
			"default" => true
		);
		$cols["last_access"] = array(
			"txt" => $this->pl()->txt("last_access"),
			"default" => true
		);

		return $cols;
	}

}
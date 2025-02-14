<?php

use srag\DIC\DhbwTraining\DICTrait;

/**
 * Class xdhtParticipantTableGUI
 *
 * @author: Benjamin Seglias   <bs@studer-raimann.ch>
 */
class xdhtParticipantTableGUI extends ilTable2GUI
{

    use DICTrait;
    const PLUGIN_CLASS_NAME = ilDhbwTrainingPlugin::class;
    const TBL_ID = 'tbl_xdht_participants';

    /**
     * @var xdhtParticipant
     */
    public $xdht_participant;

    /**
     * @var array
     */
    protected $filter = [];

    /**
     * #SUR# type ?object
     */
    protected ?object $parent_obj;

    /**
     * @var xdhtObjectFacadeInterface
     */
    protected $facade;


    /**
     * ilLocationDataTableGUI constructor.
     *
     * @param xdhtParticipantGUI $a_parent_obj
     */
    function __construct($a_parent_obj, $a_parent_cmd, xdhtObjectFacadeInterface $facade)
    {
        $this->parent_obj = $a_parent_obj;
        $this->facade = $facade;

        $this->setId(self::TBL_ID);
        $this->setPrefix(self::TBL_ID);
        $this->setFormName(self::TBL_ID);
        self::dic()->ctrl()->saveParameter($a_parent_obj, $this->getNavParameter());
        $this->xdht_participant = new xdhtParticipant($_GET[xdhtParticipantGUI::PARTICIPANT_IDENTIFIER]);

        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->setRowTemplate("tpl.participants.html", "Customizing/global/plugins/Services/Repository/RepositoryObject/DhbwTraining");

        $this->setFormAction(self::dic()->ctrl()->getFormActionByClass(xdhtParticipantGUI::class));
        $this->setExternalSorting(true);

        $this->setDefaultOrderField("full_name");
        $this->setDefaultOrderDirection("asc");
        $this->setExternalSegmentation(true);
        $this->setEnableHeader(true);

        $this->initColums();
        $this->addFilterItems();
        $this->parseData();
    }


    protected function initColums()
    {

        $number_of_selected_columns = count($this->getSelectedColumns());
        $column_width = 100 / $number_of_selected_columns . '%';

        $all_cols = $this->getSelectableColumns();
        foreach ($this->getSelectedColumns() as $col) {

            $this->addColumn($all_cols[$col]['txt'], "$col", $column_width);
        }
    }

    /**
     * #SUR# return type definition
     * @return array
     * @throws \srag\DIC\DhbwTraining\Exception\DICException
     */
    public function getSelectableColumns(): array
    {
        $cols["full_name"] = array(
            "txt"     => self::plugin()->translate("participant_name"),
            "default" => true
        );
        $cols["login"] = array(
            "txt"     => self::plugin()->translate("usr_name"),
            "default" => true
        );
        $cols["status"] = array(
            "txt"     => self::plugin()->translate("learning_progress"),
            "default" => true
        );
        $cols["created"] = array(
            "txt"     => self::plugin()->translate("first_access"),
            "default" => true
        );
        $cols["last_access"] = array(
            "txt"     => self::plugin()->translate("last_access"),
            "default" => true
        );

        return $cols;
    }


    protected function addFilterItems()
    {
        $participant_name = new ilTextInputGUI(self::plugin()->translate('participant_name'), 'full_name');
        $this->addAndReadFilterItem($participant_name);

        $usr_name = new ilTextInputGUI(self::plugin()->translate('usr_name'), 'login');
        $this->addAndReadFilterItem($usr_name);

        /*		$option[0] = self::plugin()->translate('not_attempted');
                $option[1] = self::plugin()->translate('in_progress');
                $option[2] = self::plugin()->translate('edited');*/

        $status = new ilSelectInputGUI(self::plugin()->translate("learning_progress"), "status");
        //$status->setOptions($option);
        $status->setOptions(LearningProgressStatusRepresentation::getDropdownDataLocalized(self::plugin()->getPluginObject()));
        $this->addAndReadFilterItem($status);
    }


    /**
     * @param $item
     */
    protected function addAndReadFilterItem(ilFormPropertyGUI $item)
    {
        $this->addFilterItem($item);
        $item->readFromSession();
        if ($item instanceof ilCheckboxInputGUI) {
            $this->filter[$item->getPostVar()] = $item->getChecked();
        } else {
            $this->filter[$item->getPostVar()] = $item->getValue();
        }
    }


    protected function parseData()
    {
        $this->determineOffsetAndOrder();
        $this->determineLimit();

        $collection = xdhtParticipant::getCollection();
        $collection->where(array('training_obj_id' => $this->facade->settings()->getDhbwTrainingObjectId()));
        $collection->leftjoin('usr_data', 'usr_id', 'usr_id');

        $sorting_column = $this->getOrderField() ? $this->getOrderField() : 'full_name';
        $offset = $this->getOffset() ? $this->getOffset() : 0;

        $sorting_direction = $this->getOrderDirection();
        $num = $this->getLimit();

        $collection->orderBy($sorting_column, $sorting_direction);
        $collection->limit($offset, $num);

        foreach ($this->filter as $filter_key => $filter_value) {
            switch ($filter_key) {
                case 'full_name':
                case 'login':
                    $collection->where(array($filter_key => '%' . $filter_value . '%'), 'LIKE');
                    break;
                case 'status':
                    if (!empty($filter_value)) {
                        $filter_value = LearningProgressStatusRepresentation::mappProgrStatusToLPStatus($filter_value);
                        $collection->where(array($filter_key => $filter_value), '=');
                        break;
                    }
            }
        }
        $this->setData($collection->getArray());
    }

    /**
     * #SUR# return type definition
     * @param $a_set
     * @return void
     * @throws ilDateTimeException
     * @throws ilTemplateException
     */
    public function fillRow($a_set): void
    {
        /**
         * @var xdhtParticipant $xdhtParticipant
         */
        $xdhtParticipant = xdhtParticipant::find($a_set['id']);

        $usr_data = $this->getUserDataById($xdhtParticipant->getUsrId());

        if ($this->isColumnSelected('full_name')) {
            $this->tpl->setCurrentBlock("PARTICIPANT_NAME");
            $this->tpl->setVariable('PARTICIPANT_NAME', $usr_data['firstname'] . " " . $usr_data['lastname']);
            $this->tpl->parseCurrentBlock();
        }

        if ($this->isColumnSelected('login')) {
            $this->tpl->setCurrentBlock("USR_NAME");
            $this->tpl->setVariable('USR_NAME', $usr_data['login']);
            $this->tpl->parseCurrentBlock();
        }
        if ($this->isColumnSelected('status')) {
            $this->tpl->setCurrentBlock("LEARNING_PROGRESS");
            $this->tpl->setVariable('LP_STATUS_ALT', LearningProgressStatusRepresentation::statusToRepr($xdhtParticipant->getStatus()));
            $this->tpl->setVariable('LP_STATUS_PATH', LearningProgressStatusRepresentation::getStatusImage($xdhtParticipant->getStatus()));
            $this->tpl->parseCurrentBlock();
        }

        if ($this->isColumnSelected('created')) {
            $this->tpl->setCurrentBlock("CREATED");
            $this->tpl->setVariable('CREATED', ilDatePresentation::formatDate(new ilDateTime($xdhtParticipant->getCreated(), IL_CAL_DATETIME)));
            $this->tpl->parseCurrentBlock();
        }

        if ($this->isColumnSelected('last_access')) {
            $this->tpl->setCurrentBlock("LAST_ACCESS");
            $this->tpl->setVariable('LAST_ACCESS', ilDatePresentation::formatDate(new ilDateTime($xdhtParticipant->getLastAccess(), IL_CAL_DATETIME)));
            $this->tpl->parseCurrentBlock();
        }
    }


    protected function getUserDataById($usr_id)
    {
        global $ilDB;
        $q = "SELECT * FROM usr_data WHERE usr_id = " . $ilDB->quote($usr_id, "integer");
        $usr_set = $ilDB->query($q);
        $usr_rec = $ilDB->fetchAssoc($usr_set);

        return $usr_rec;
    }
}
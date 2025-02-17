<?php

use srag\DIC\DhbwTraining\DICTrait;
use srag\Plugins\DhbwTraining\Config\Config;

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Class ilDhbwTrainingPlugin
 *
 * @author: Benjamin Seglias   <bs@studer-raimann.ch>
 */
class ilDhbwTrainingPlugin extends ilRepositoryObjectPlugin
{

    use DICTrait;
    const PLUGIN_CLASS_NAME = self::class;
    const PLUGIN_PREFIX = 'xdht';
    const PLUGIN_NAME = 'DhbwTraining';
    /**
     * @var ilDhbwTrainingPlugin
     */
    protected static $instance;


    /**
     * #SUR# Plugin object initialization
     * @return ilDhbwTrainingPlugin
     */
    public static function getInstance()
    {
        global $DIC;
        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC["component.factory"];
        return $component_factory->getPlugin('xdht');

        if (!isset(self::$instance)) {

            self::$instance = new self(array(), 0, true);
        }

        return self::$instance;
    }


    /**
     * #SUR# return type definition
     * @return string
     */
    function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * #SUR# return type definition
     * @return void
     * @throws \srag\DIC\DhbwTraining\Exception\DICException
     */
    protected function uninstallCustom(): void
    {
        self::dic()->database()->dropTable(Config::TABLE_NAME, false);
        self::dic()->database()->dropTable(xdhtSettings::TABLE_NAME, false);
        self::dic()->database()->dropTable(xdhtParticipant::TABLE_NAME, false);
    }

    /**
     * Before activation processing
     * #SUR# return type definition
     * @return bool
     * @throws ilPluginException
     */
    protected function beforeActivation(): bool
    {
        global $ilDB, $DIC;
        parent::beforeActivation();

        // check whether type exists in object data, if not, create the type
        $set = $ilDB->query("SELECT * FROM object_data " .
            " WHERE type = " . $ilDB->quote("typ", "text") .
            " AND title = " . $ilDB->quote('xdht', "text")
        );
        if ($rec = $ilDB->fetchAssoc($set)) {
            $t_id = $rec["obj_id"];
        }

        // add rbac operations
        // 1: edit_permissions, 2: visible, 3: read, 4:write, 6:delete
        $ops = array_map(function (array $operation) : int {
            return $operation["ops_id"];
        }, $DIC->database()->fetchAll($DIC->database()->query("SELECT ops_id FROM rbac_operations WHERE " . $DIC->database()->in("operation", ["read_learning_progress", "edit_learning_progress", "copy"], false, ilDBConstants::T_TEXT))));
        foreach ($ops as $op) {
            // check whether type exists in object data, if not, create the type
            $set = $ilDB->query("SELECT * FROM rbac_ta " .
                " WHERE typ_id = " . $ilDB->quote($t_id, "integer") .
                " AND ops_id = " . $ilDB->quote($op, "integer")
            );
            if (!$ilDB->fetchAssoc($set)) {
                $ilDB->manipulate("INSERT INTO rbac_ta " .
                    "(typ_id, ops_id) VALUES (" .
                    $ilDB->quote($t_id, "integer") . "," .
                    $ilDB->quote($op, "integer") .
                    ")");
            }
        }

        return true;
    }
}
<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';


class PlgFabrik_FormRecursive_Tree extends PlgFabrik_Form
{
    /**
     * Run right at the end of the form processing
     * form needs to be set to record in database for this to hook to be called
     *
     * @return	bool
     */
    public function onAfterProcess()
    {
        return $this->_process();
    }

    public function deleteConnections($rowId, $table) {
        $db = FabrikWorker::getDbo();
        $db->setQuery("DELETE FROM " . $table . " WHERE parent_id = " . $rowId);
        $db->execute();
    }

    public function insertConnection($table, $parent_id, $dado, $column_name) {
        $obj = array();
        $obj["id"] = 0;
        $obj["parent_id"] = $parent_id;
        $obj[$column_name] = $dado;
        $obj = (Object)$obj;
        $insert = JFactory::getDbo()->insertObject($table, $obj, "id");
    }

    public function getParent($id, $table, $join_key, $parent_column) {
        $db = FabrikWorker::getDbo();
        $query = $db->getQuery(true);
        $query->select($parent_column)->from($table)->where($join_key . " = '" . $id . "'");
        $db->setQuery($query);

        return $db->loadResult();
    }

    public function doConnections($id_dado, $origem, $destino, $rowId, $table) {
        if (!$id_dado) {
            return;
        }
        else {
            $this->insertConnection($table . "_repeat_" . $destino->name, $rowId, $id_dado, $destino->name);
            $parent = $this->getParent($id_dado, $origem->params->join_db_name, $origem->params->join_key_column, $origem->params->tree_parent_id);
            $this->doConnections($parent, $origem, $destino, $rowId, $table);
        }
    }

    public function _process() {
        $formModel = $this->getModel();
        $params = $this->getParams();
        $table = $formModel->getListModel()->getTable()->db_table_name;
        $formData = $formModel->formData;
        $plugin = FabrikWorker::getPluginManager();

        $elementos_origem = json_decode($params->get('map_elements'))->elemento_origem;
        $elementos_destino = json_decode($params->get('map_elements'))->elemento_destino;

        for ($i=0; $i<count($elementos_destino); $i++) {
            $elemento_origem = $plugin->getElementPlugin($elementos_origem[$i])->getElement(true);
            $elemento_destino = $plugin->getElementPlugin($elementos_destino[$i])->getElement(true);
            $elemento_origem->params = json_decode($elemento_origem->params);
            $elemento_destino->params = json_decode($elemento_destino->params);

            $this->deleteConnections($formData["id"], $table . "_repeat_" . $elemento_destino->name);
            foreach ($formData[$elemento_origem->name] as $item) {
                $this->doConnections($item, $elemento_origem, $elemento_destino, $formData["id"], $table);
            }
        }
    }


}

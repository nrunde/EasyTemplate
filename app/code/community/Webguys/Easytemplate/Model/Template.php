<?php

/**
 * Class Webguys_Easytemplate_Model_Template
 *
 * @method setGroupId
 * @method getGroupId
 * @method setCode
 * @method getCode
 * @method setName
 * @method getName
 * @method setActive
 * @method getActive
 * @method setPosition
 * @method getPosition
 * @method setValidFrom
 * @method getValidFrom
 * @method setValidTo
 * @method getValidTo
 */
class Webguys_Easytemplate_Model_Template extends Mage_Core_Model_Abstract
{

    protected $_field_data;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'easytemplate_template';

    /**
     * $field_data = array(
     *      $code => $value
     * );
     */

    protected function _construct()
    {
        $this->_init('easytemplate/template');
    }

    public function importData( Array $data)
    {
        $this->setCode( $data['code'] );
        $this->setName( $data['name'] );
        $this->setPosition( $data['sort_order'] );

        if (($time = strtotime($data['valid_from'])) !== false) {
            $this->setValidFrom( date('Y-m-d', $time) );
        }

        if (($time = strtotime($data['valid_to'])) !== false) {
            $this->setValidTo( date('Y-m-d', $time) );
        }

        // TODO: Other Fields

        $this->_field_data = $data['fields'];
    }

    protected function _isValid()
    {
        $code = $this->getCode();
        return (isset($code));
    }

    protected function _beforeSave()
    {
        if (!$this->_isValid()) {
            $this->_dataSaveAllowed = false;
            throw new Exception('Required template data missing');
        }

        return parent::_beforeSave();
    }

    public function _afterSave()
    {
        /** @var $configModel Webguys_Easytemplate_Model_Input_Parser */
        $configModel = Mage::getSingleton('easytemplate/input_parser');

        if ($model = $configModel->getTemplate( $this->getCode() )) {

            foreach ($model->getFields() as $field) {

                $backendModel = $field->getBackendModel();
                $backendModel->importData( $this->_field_data[ $field->getCode() ] );
                $backendModel->setElementId( $this->getId() ); // TODO: Change naming to template!!
                $backendModel->save();

            }

        }
    }

    /**
     * @return Webguys_Easytemplate_Model_Input_Parser_Template
     */
    public function getConfig()
    {
        /** @var $configModel Webguys_Easytemplate_Model_Input_Parser */
        $configModel = Mage::getSingleton('easytemplate/input_parser');
        if ($model = $configModel->getTemplate( $this->getCode() )) {
            return $model;
        }
        return Mage::getModel('easytemplate/input_parser_template');
    }

    public function getFields()
    {
        return $this->getConfig()->getFields();
    }

    public function _afterLoad()
    {

        /** @var $models Webguys_Easytemplate_Model_Template_Data_Abstract[] */
        $models = array();

        // collect all input-resources
        foreach( $this->getFields() AS $field )
        {
            $backend_model_name = $field->getBackendModel()->getInternalName();
            $models[ $backend_model_name ] = $field->getBackendModel();
        }

        // iterate all models and get data using collections
        foreach( $models AS $backend_model )
        {
            /** @var $data_collection Webguys_Easytemplate_Model_Resource_Template_Data_Collection_Abstract */
            $data_collection = $backend_model->getCollection();
            $data_collection->addTemplateFilter( $this );

            /** @var $data Webguys_Easytemplate_Model_Resource_Template_Data_Abstract */
            foreach( $data_collection AS $data )
            {
                $this->_field_data[ $data->getField() ] = $data->getValue();
            }
        }

        return $this;
    }

    public function getFieldData( $field = null )
    {
        if( $field === null )
        {
            return $this->_field_data;
        }
        return $this->_field_data[ $field ];
    }
}
<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Adminhtml Catalog Category Attributes per Group Tab block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Catalog_Category_Tab_Attributes extends Mage_Adminhtml_Block_Catalog_Form
{
    /**
     * Retrieve Category object
     *
     * @return Mage_Catalog_Model_Category
     */
    public function getCategory()
    {
        return AO::registry('current_category');
    }

    /**
     * Initialize tab
     *
     */
    public function __construct() {
        parent::__construct();
        $this->setShowGlobalIcon(true);
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return Mage_Adminhtml_Block_Catalog_Category_Tab_Attributes
     */
    protected function _prepareForm() {
        $group      = $this->getGroup();
        $attributes = $this->getAttributes();

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('group_' . $group->getId());
        $form->setDataObject($this->getCategory());

        $fieldset = $form->addFieldset('fieldset_group_' . $group->getId(), array(
            'legend'    => AO::helper('catalog')->__($group->getAttributeGroupName())
        ));

        if ($this->getAddHiddenFields()) {
            if (!$this->getCategory()->getId()) {
                // path
                if ($this->getRequest()->getParam('parent')) {
                    $fieldset->addField('path', 'hidden', array(
                        'name'  => 'path',
                        'value' => $this->getRequest()->getParam('parent')
                    ));
                }
                else {
                    $fieldset->addField('path', 'hidden', array(
                        'name'  => 'path',
                        'value' => 1
                    ));
                }
            }
            else {
                $fieldset->addField('id', 'hidden', array(
                    'name'  => 'id',
                    'value' => $this->getCategory()->getId()
                ));
                $fieldset->addField('path', 'hidden', array(
                    'name'  => 'path',
                    'value' => $this->getCategory()->getPath()
                ));
            }
        }

        $this->_setFieldset($attributes, $fieldset);

        foreach ($attributes as $attribute) {
            /* @var $attribute Mage_Eav_Model_Entity_Attribute */
            if ($attribute->getAttributeCode() == 'url_key') {
                if ($this->getCategory()->getLevel() == 1) {
                    $fieldset->removeField('url_key');
                    $fieldset->addField('url_key', 'hidden', array(
                        'name'  => 'url_key',
                        'value' => $this->getCategory()->getUrlKey()
                    ));
                }
            }
        }

        $form->addValues($this->getCategory()->getData());

        $form->setFieldNameSuffix('general');
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Retrieve Additional Element Types
     *
     * @return array
     */
    protected function _getAdditionalElementTypes()
    {
        return array(
            'image' => AO::getConfig()->getBlockClassName('adminhtml/catalog_category_helper_image')
        );
    }
}

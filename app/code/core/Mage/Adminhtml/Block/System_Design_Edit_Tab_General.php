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
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Mage_Adminhtml_Block_System_Design_Edit_Tab_General extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('general', array('legend'=>AO::helper('core')->__('General Settings')));

        if (!AO::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'select', array(
                'label'    => AO::helper('core')->__('Store'),
                'title'    => AO::helper('core')->__('Store'),
                'values'   => AO::getSingleton('adminhtml/system_store')->getStoreValuesForForm(),
                'name'     => 'store_id',
                'required' => true,
            ));
        } else {
            $fieldset->addField('store_id', 'hidden', array(
                'name'      => 'store_id',
                'value'     => AO::app()->getStore(true)->getId(),
            ));
        }

        $fieldset->addField('design', 'select', array(
            'label'    => AO::helper('core')->__('Custom Design'),
            'title'    => AO::helper('core')->__('Custom Design'),
            'values'   => AO::getSingleton('core/design_source_design')->getAllOptions(),
            'name'     => 'design',
            'required' => true,
        ));

        $dateFormatIso = AO::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldset->addField('date_from', 'date', array(
            'label'    => AO::helper('core')->__('Date From'),
            'title'    => AO::helper('core')->__('Date From'),
            'name'     => 'date_from',
            'image'    => $this->getSkinUrl('images/grid-cal.gif'),
            'format'   => $dateFormatIso,
            //'required' => true,
        ));
        $fieldset->addField('date_to', 'date', array(
            'label'    => AO::helper('core')->__('Date To'),
            'title'    => AO::helper('core')->__('Date To'),
            'name'     => 'date_to',
            'image'    => $this->getSkinUrl('images/grid-cal.gif'),
            'format'   => $dateFormatIso,
            //'required' => true,
        ));

        $formData = AO::getSingleton('adminhtml/session')->getDesignData(true);
        if (!$formData){
            $formData = AO::registry('design')->getData();
        } else {
            $formData = $formData['design'];
        }

        $form->addValues($formData);
        $form->setFieldNameSuffix('design');
        $this->setForm($form);
    }

}

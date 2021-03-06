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
 * @package    Mage_GoogleBase
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * GoogleBase Admin Item Types Controller
 *
 * @category   Mage
 * @package    Mage_GoogleBase
 * @name       Mage_GoogleBase_ItemTypesController
 * @author     Magento Core Team <core@magentocommerce.com>
*/
class Mage_GoogleBase_TypesController extends Mage_Adminhtml_Controller_Action
{
    protected function _initItemType()
    {
        AO::register('current_item_type', AO::getModel('googlebase/type'));
        $typeId = $this->getRequest()->getParam('id');
        if (!is_null($typeId)) {
            AO::registry('current_item_type')->load($typeId);
        }
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('catalog/googlebase/types')
            ->_addBreadcrumb(AO::helper('adminhtml')->__('Catalog'), AO::helper('adminhtml')->__('Catalog'))
            ->_addBreadcrumb(AO::helper('adminhtml')->__('Google Base'), AO::helper('adminhtml')->__('Google Base'));
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->_addBreadcrumb(AO::helper('googlebase')->__('Item Types'), AO::helper('googlebase')->__('Item Types'))
            ->_addContent($this->getLayout()->createBlock('googlebase/adminhtml_types'))
            ->renderLayout();
    }

    /**
     * Grid for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('googlebase/adminhtml_types_grid')->toHtml()
        );
    }

    public function newAction()
    {
        try {
            $this->_initItemType();
            $this->_initAction()
                ->_addBreadcrumb(AO::helper('googlebase')->__('New Item Type'), AO::helper('adminhtml')->__('New Item Type'))
                ->_addContent($this->getLayout()->createBlock('googlebase/adminhtml_types_edit'))
                ->renderLayout();
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/*/index', array('store' => $this->_getStore()->getId()));
        }
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = AO::getModel('googlebase/type');

        try {
            $result = array();
            if ($id) {
                $model->load($id);
                $collection = AO::getResourceModel('googlebase/attribute_collection')
                    ->addTypeFilter($model->getTypeId())
                    ->load();
                foreach ($collection as $attribute) {
                    $result[] = $attribute->getData();
                }
            }

            AO::register('current_item_type', $model);
            AO::register('attributes', $result);

            $this->_initAction()
                ->_addBreadcrumb($id ? AO::helper('googlebase')->__('Edit Item Type') : AO::helper('googlebase')->__('New Item Type'), $id ? AO::helper('googlebase')->__('Edit Item Type') : AO::helper('googlebase')->__('New Item Type'))
                ->_addContent($this->getLayout()->createBlock('googlebase/adminhtml_types_edit'))
                ->renderLayout();
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/*/index');
        }
    }

    public function saveAction()
    {
        $typeModel = AO::getModel('googlebase/type');
        $id = $this->getRequest()->getParam('type_id');
        if (!is_null($id)) {
            $typeModel->load($id);
        }

        try {
            if ($typeModel->getId()) {
                $collection = AO::getResourceModel('googlebase/attribute_collection')
                    ->addTypeFilter($typeModel->getId())
                    ->load();
                foreach ($collection as $attribute) {
                    $attribute->delete();
                }
            }
            $typeModel->setAttributeSetId($this->getRequest()->getParam('attribute_set_id'))
                ->setGbaseItemtype($this->getRequest()->getParam('gbase_itemtype'))
                ->setTargetCountry($this->getRequest()->getParam('target_country'))
                ->save();


            $attributes = $this->getRequest()->getParam('attributes');
            if (is_array($attributes)) {
                $typeId = $typeModel->getId();
                foreach ($attributes as $attrInfo) {
                    if (isset($attrInfo['delete']) && $attrInfo['delete'] == 1) {
                        continue;
                    }
                    AO::getModel('googlebase/attribute')
                        ->setAttributeId($attrInfo['attribute_id'])
                        ->setGbaseAttribute($attrInfo['gbase_attribute'])
                        ->setTypeId($typeId)
                        ->save();
                }
            }

            AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('googlebase')->__('Item type was successfully saved'));
        } catch (Exception $e) {
            AO::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*/index', array('store' => $this->_getStore()->getId()));
    }

    public function deleteAction ()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            $model = AO::getModel('googlebase/type');
            $model->load($id);
            if ($model->getTypeId()) {
                $model->delete();
            }
            $this->_getSession()->addSuccess($this->__('Item Type was deleted'));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/*/index', array('store' => $this->_getStore()->getId()));
    }

    public function loadAttributesAction ()
    {
        try {
            $this->getResponse()->setBody(
            $this->getLayout()->createBlock('googlebase/adminhtml_types_edit_attributes')
                ->setAttributeSetId($this->getRequest()->getParam('attribute_set_id'))
                ->setGbaseItemtype($this->getRequest()->getParam('gbase_itemtype'))
                ->setTargetCountry($this->getRequest()->getParam('target_country'))
                ->setAttributeSetSelected(true)
                ->toHtml()
            );
        } catch (Exception $e) {
            // just need to output text with error
            $this->_getSession()->addError($e->getMessage());
        }
    }

    public function loadItemTypesAction()
    {
        try {
            $this->getResponse()->setBody(
                $this->getLayout()->getBlockSingleton('googlebase/adminhtml_types_edit_form')
                    ->getItemTypesSelectElement($this->getRequest()->getParam('target_country'))
                    ->toHtml()
            );
        } catch (Exception $e) {
            // just need to output text with error
            $this->_getSession()->addError($e->getMessage());
        }
    }

    protected function loadAttributeSetsAction()
    {
        try {
            $this->getResponse()->setBody(
                $this->getLayout()->getBlockSingleton('googlebase/adminhtml_types_edit_form')
                    ->getAttributeSetsSelectElement($this->getRequest()->getParam('target_country'))
                    ->toHtml()
            );
        } catch (Exception $e) {
            // just need to output text with error
            $this->_getSession()->addError($e->getMessage());
        }
    }

    public function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        if ($storeId == 0) {
            return AO::app()->getDefaultStoreView();
        }
        return AO::app()->getStore($storeId);
    }

    protected function _isAllowed()
    {
        return AO::getSingleton('admin/session')->isAllowed('catalog/googlebase/types');
    }
}
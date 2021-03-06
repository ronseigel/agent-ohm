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

/**
 * Adminhtml customer recent orders grid block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Customer_Edit_Tab_View_Orders extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('customer_view_orders_grid');
        $this->setDefaultSort('created_at', 'desc');
        $this->setSortable(false);
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
    }

    protected function _preparePage()
    {
        $this->getCollection()
            ->setPageSize(5)
            ->setCurPage(1);
    }

    protected function _prepareCollection()
    {
        //TODO: add full name logic
        $collection = AO::getResourceModel('sales/order_collection')
            ->addAttributeToSelect('increment_id')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('grand_total')
            ->addAttributeToSelect('order_currency_code')
            ->addAttributeToSelect('store_id')
            ->joinAttribute('shipping_firstname', 'order_address/firstname', 'shipping_address_id')
            ->joinAttribute('shipping_lastname', 'order_address/lastname', 'shipping_address_id')
            ->addAttributeToFilter('customer_id', AO::registry('current_customer')->getId());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('increment_id', array(
            'header'    => AO::helper('customer')->__('Order #'),
            'align'     => 'center',
            'index'     => 'increment_id',
            'width'     => '100px',
        ));

        $this->addColumn('created_at', array(
            'header'    => AO::helper('customer')->__('Purchased at'),
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('shipping_firstname', array(
            'header'    => AO::helper('customer')->__('Ship to First name'),
            'index'     => 'shipping_firstname',
        ));

        $this->addColumn('shipping_lastname', array(
            'header'    => AO::helper('customer')->__('Ship to Last name'),
            'index'     => 'shipping_lastname',
        ));

        $this->addColumn('grand_total', array(
            'header'    => AO::helper('customer')->__('Grand Total'),
            'index'     => 'grand_total',
            'type'      => 'currency',
            'currency'  => 'order_currency_code',
        ));

        if (!AO::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => AO::helper('customer')->__('Bought From'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view' => true,
            ));
        }

        $this->addColumn('action', array(
            'header'    =>  ' ',
            'filter'    =>  false,
            'sortable'  =>  false,
            'width'     => '100px',
            'renderer'  =>  'adminhtml/sales_reorder_renderer_action'
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/sales_order/view', array('order_id' => $row->getId()));
    }

    public function getHeadersVisibility()
    {
        return ($this->getCollection()->getSize() > 0);
    }

}

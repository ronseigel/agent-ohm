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
 * description
 *
 * @category    Mage
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Catalog_Search_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Init Grid default properties
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('catalog_search_grid');
        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare collection for Grid
     *
     * @return Mage_Adminhtml_Block_Catalog_Search_Grid
     */
    protected function _prepareCollection()
    {
        $collection = AO::getModel('catalogsearch/query')
            ->getResourceCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare Grid columns
     *
     * @return Mage_Adminhtml_Block_Catalog_Search_Grid
     */
    protected function _prepareColumns()
    {
        /*$this->addColumn('query_id', array(
            'header'    => AO::helper('catalog')->__('ID'),
            'width'     => '50px',
            'index'     => 'query_id',
        ));*/

        $this->addColumn('search_query', array(
            'header'    => AO::helper('catalog')->__('Search Query'),
            'index'     => 'query_text',
        ));

        if (!AO::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'        => AO::helper('catalog')->__('Store'),
                'index'         => 'store_id',
                'type'          => 'store',
                'store_view'    => true,
                'sortable'      => false
            ));
        }

        $this->addColumn('num_results', array(
            'header'    => AO::helper('catalog')->__('Results'),
            'index'     => 'num_results',
            'type'      => 'number'
        ));

        $this->addColumn('popularity', array(
            'header'    => AO::helper('catalog')->__('Number of Uses'),
            'index'     => 'popularity',
            'type'      => 'number'
        ));

        $this->addColumn('synonim_for', array(
            'header'    => AO::helper('catalog')->__('Synonym for'),
            'align'     => 'left',
            'index'     => 'synonim_for',
            'width'     => '160px'
        ));

        $this->addColumn('redirect', array(
            'header'    => AO::helper('catalog')->__('Redirect'),
            'align'     => 'left',
            'index'     => 'redirect',
            'width'     => '200px'
        ));

        $this->addColumn('display_in_terms', array(
            'header'=>AO::helper('catalog')->__('Display in Suggested Terms'),
            'sortable'=>true,
            'index'=>'display_in_terms',
            'type' => 'options',
            'width' => '100px',
            'options' => array(
                '1' => AO::helper('catalog')->__('Yes'),
                '0' => AO::helper('catalog')->__('No'),
            ),
            'align' => 'left',
        ));
        $this->addColumn('action',
            array(
                'header'    => AO::helper('catalog')->__('Action'),
                'width'     => '100px',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(array(
                    'caption'   => AO::helper('catalog')->__('Edit'),
                    'url'       => array(
                        'base'=>'*/*/edit'
                    ),
                    'field'   => 'id'
                )),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'catalog',
        ));
        return parent::_prepareColumns();
    }

    /**
     * Prepare grid massaction actions
     *
     * @return Mage_Adminhtml_Block_Catalog_Search_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('query_id');
        $this->getMassactionBlock()->setFormFieldName('search');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => AO::helper('catalog')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => AO::helper('catalog')->__('Are you sure?')
        ));

        return parent::_prepareMassaction();
    }

    /**
     * Retrieve Row Click callback URL
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}

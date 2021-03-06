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
 * @package    Mage_Core
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Locale model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Core_Model_Locale
{
    /**
     * Default locale name
     */
    const DEFAULT_LOCALE    = 'en_US';
    const DEFAULT_TIMEZONE  = 'UTC';
    const DEFAULT_CURRENCY  = 'USD';

    /**
     * XML path constants
     */
    const XML_PATH_DEFAULT_LOCALE   = 'general/locale/code';
    const XML_PATH_DEFAULT_TIMEZONE = 'general/locale/timezone';
    const XML_PATH_DEFAULT_COUNTRY  = 'general/country/default';
    const XML_PATH_ALLOW_CODES      = 'global/locale/allow/codes';
    const XML_PATH_ALLOW_CURRENCIES = 'global/locale/allow/currencies';
    const XML_PATH_ALLOW_CURRENCIES_INSTALLED = 'system/currency/installed';

    /**
     * Date and time format codes
     */
    const FORMAT_TYPE_FULL  = 'full';
    const FORMAT_TYPE_LONG  = 'long';
    const FORMAT_TYPE_MEDIUM= 'medium';
    const FORMAT_TYPE_SHORT = 'short';

    /**
     * Default locale code
     *
     * @var string
     */
    protected $_defaultLocale;

    /**
     * Locale object
     *
     * @var Zend_Locale
     */
    protected $_locale;

    /**
     * Locale code
     *
     * @var string
     */
    protected $_localeCode;

    /**
     * Emulated locales stack
     *
     * @var array
     */
    protected $_emulatedLocales = array();

    protected static $_currencyCache = array();

    public function __construct($locale = null)
    {
        if (empty($locale)) {
            $locale = $this->getDefaultLocale();
        }
        $this->_localeCode = $locale;
    }

    /**
     * Set default locale code
     *
     * @param   string $locale
     * @return  Mage_Core_Model_Locale
     */
    public function setDefaultLocale($locale)
    {
        $this->_defaultLocale = $locale;
        return $this;
    }

    /**
     * REtrieve default locale code
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        if (!$this->_defaultLocale) {
            $locale = AO::getStoreConfig(self::XML_PATH_DEFAULT_LOCALE);
            if (!$locale) {
                $locale = self::DEFAULT_LOCALE;
            }
            $this->_defaultLocale = $locale;
        }
        return $this->_defaultLocale;
    }

    /**
     * Set locale
     *
     * @param   string $locale
     * @return  Mage_Core_Model_Locale
     */
    public function setLocale($locale = null)
    {
        AO::dispatchEvent('core_locale_set_locale', array('locale'=>$this));
        Zend_Locale_Data::setCache(AO::app()->getCache());
        if ($locale === null) {
        	$locale = $this->_localeCode;
        }
        $this->_locale = new Zend_Locale($locale);
        return $this;
    }

    /**
     * Retrieve timezone code
     *
     * @return string
     */
    public function getTimezone()
    {
        return self::DEFAULT_TIMEZONE;
    }

    /**
     * Retrieve currency code
     *
     * @return string
     */
    public function getCurrency()
    {
        return self::DEFAULT_CURRENCY;
    }

    /**
     * Retrieve locale object
     *
     * @return Zend_Locale
     */
    public function getLocale()
    {
        if (!$this->_locale) {
            $this->setLocale();
        } elseif ($this->_locale->__toString() != $this->_localeCode) {
        	$this->setLocale($this->_localeCode);
        }

        return $this->_locale;
    }

    /**
     * Retrieve locale code
     *
     * @return string
     */
    public function getLocaleCode()
    {
        return $this->_localeCode;
    }

    /**
     * Specify current locale code
     *
     * @param   string $code
     * @return  Mage_Core_Model_Locale
     */
    public function setLocaleCode($code)
    {
        $this->_localeCode = $code;
        return $this;
    }

    /**
     * Retrieve options array for locale dropdown
     *
     * @return array
     */
    public function getOptionLocales()
    {
        $options    = array();
        $locales    = $this->getLocale()->getLocaleList();
        $languages  = $this->getLocale()->getLanguageTranslationList($this->getLocale());
        $countries  = $this->getCountryTranslationList();

        $allowed    = $this->getAllowLocales();
        foreach ($locales as $code=>$active) {
            if (strstr($code, '_')) {
                if (!in_array($code, $allowed)) {
                    continue;
                }
                $data = explode('_', $code);
                if (!isset($languages[$data[0]]) || !isset($countries[$data[1]])) {
                    continue;
                }
                $options[] = array(
                    'value' => $code,
                    'label' => $languages[$data[0]] . ' (' . $countries[$data[1]] . ')'
                );
            }
        }
        return $this->_sortOptionArray($options);
    }

    /**
     * Retrieve timezone option list
     *
     * @return array
     */
    public function getOptionTimezones()
    {
        $options= array();
        $zones  = $this->getTranslationList('windowstotimezone');
        ksort($zones);
        foreach ($zones as $code=>$name) {
            $name = trim($name);
            $options[] = array(
               'label' => empty($name) ? $code : $name . ' (' . $code . ')',
               'value' => $code,
            );
        }
        return $this->_sortOptionArray($options);
    }

    /**
     * Retrieve days of week option list
     *
     * @return array
     */
    public function getOptionWeekdays()
    {
        $options= array();
        $days = $this->getTranslationList('days');
        foreach (array_values($days['format']['wide']) as $code => $name) {
            $options[] = array(
               'label' => $name,
               'value' => $code,
            );
        }
        return $options;
    }

    /**
     * Retrieve country option list
     *
     * @return array
     */
    public function getOptionCountries()
    {
        $options    = array();
        $countries  = $this->getCountryTranslationList();

        foreach ($countries as $code=>$name) {
            $options[] = array(
               'label' => $name,
               'value' => $code,
            );
        }
        return $this->_sortOptionArray($options);
    }

    /**
     * Retrieve currency option list
     *
     * @return unknown
     */
    public function getOptionCurrencies()
    {
        $currencies = $this->getTranslationList('currencytoname');
        $options = array();
        $allowed = $this->getAllowCurrencies();

        foreach ($currencies as $name=>$code) {
            if (!in_array($code, $allowed)) {
                continue;
            }

            $options[] = array(
               'label' => $name,
               'value' => $code,
            );
        }
        return $this->_sortOptionArray($options);
    }

    /**
     * Retrieve all currency option list
     *
     * @return unknown
     */
    public function getOptionAllCurrencies()
    {
        $currencies = $this->getTranslationList('currencytoname');
        $options = array();
        foreach ($currencies as $name=>$code) {
            $options[] = array(
               'label' => $name,
               'value' => $code,
            );
        }
        return $this->_sortOptionArray($options);
    }

    protected function _sortOptionArray($option)
    {
        $data = array();
        foreach ($option as $item) {
            $data[$item['value']] = $item['label'];
        }
        asort($data);
        $option = array();
        foreach ($data as $key => $label) {
            $option[] = array(
               'value' => $key,
               'label' => $label
            );
        }
        return $option;
    }

    /**
     * Retrieve array of allowed locales
     *
     * @return array
     */
    public function getAllowLocales()
    {
        $data = AO::getConfig()->asArray(AO::getConfig()->getNode(self::XML_PATH_ALLOW_CODES));
        if ($data) {
            return array_keys($data);
        }
        return array();
    }

    /**
     * Retrieve array of allowed currencies
     *
     * @return unknown
     */
    public function getAllowCurrencies()
    {
        $data = array();
        if (AO::isInstalled()) {
            $data = AO::app()->getStore()->getConfig(self::XML_PATH_ALLOW_CURRENCIES_INSTALLED);
            return explode(',', $data);
        }
        else {
            $data = AO::getConfig()->asArray(AO::getConfig()->getNode(self::XML_PATH_ALLOW_CURRENCIES));
            if ($data) {
                return array_keys($data);
            }
        }
        return $data;
    }

    /**
     * Retrieve ISO date format
     *
     * @param   string $type
     * @return  string
     */
    public function getDateFormat($type=null)
    {
        return $this->getTranslation($type, 'date');
    }

    /**
     * Retrieve ISO time format
     *
     * @param   string $type
     * @return  string
     */
    public function getTimeFormat($type=null)
    {
        return $this->getTranslation($type, 'time');
    }

    /**
     * Retrieve ISO datetime format
     *
     * @param   string $type
     * @return  string
     */
    public function getDateTimeFormat($type)
    {
        return $this->getDateFormat($type) . ' ' . $this->getTimeFormat($type);
    }

    /**
     * Retrieve date format by strftime function
     *
     * @param   string $type
     * @return  string
     */
    public function getDateStrFormat($type)
    {
        return Varien_Date::convertZendToStrftime($this->getDateFormat($type), true, false);
    }

    /**
     * Retrieve time format by strftime function
     *
     * @param   string $type
     * @return  string
     */
    public function getTimeStrFormat($type)
    {
        return Varien_Date::convertZendToStrftime($this->getTimeFormat($type), false, true);
    }

    /**
     * Create Zend_Date object for current locale
     *
     * @param   mixed $date
     * @param   string $part
     * @return  Zend_Date
     * @exception Zend_Date_Exception
     */
    public function date($date=null, $part=null, $locale=null, $useTimezone=true)
    {
        if (is_null($locale)) {
            $locale = $this->getLocale();
        }

        // try-catch block was here
        $date = new Zend_Date($date, $part, $locale);
        if ($useTimezone) {
            if ($timezone = AO::app()->getStore()->getConfig(self::XML_PATH_DEFAULT_TIMEZONE)) {
                $date->setTimezone($timezone);
            }
        }
        //$date->add(-(substr($date->get(Zend_Date::GMT_DIFF), 0,3)), Zend_Date::HOUR);

        return $date;
    }

    /**
     * Create Zend_Date object with date converted to store timezone and store Locale
     *
     * @param   mixed $store Information about store
     * @param   string|integer|Zend_Date|array|null $date date in UTC
     * @param   boolean $includeTime flag for including time to date
     * @return  Zend_Date
     */
    public function storeDate($store=null, $date=null, $includeTime=false)
    {
        $timezone = AO::app()->getStore($store)->getConfig(self::XML_PATH_DEFAULT_TIMEZONE);
        $date = new Zend_Date($date, null, $this->getLocale());
        $date->setTimezone($timezone);
        if (!$includeTime) {
            $date->setHour(0)
                ->setMinute(0)
                ->setSecond(0);
        }
        return $date;
    }

    /**
     * Get store timestamp
     * Timstamp will be builded with store timezone settings
     *
     * @param   mixed $store
     * @return  int
     */
    public function storeTimeStamp($store=null)
    {
        $timezone = AO::app()->getStore($store)->getConfig(self::XML_PATH_DEFAULT_TIMEZONE);
        $currentTimezone = @date_default_timezone_get();
        @date_default_timezone_set($timezone);
        $date = date('Y-m-d H:i:s');
        @date_default_timezone_set($currentTimezone);
        return strtotime($date);
    }

    /**
     * Create Mage_Core_Model_Locale_Currency object for current locale
     *
     * @param   string $currency
     * @return  Mage_Core_Model_Locale_Currency
     */
    public function currency($currency)
    {
        if (VPROF) Varien_Profiler::start('locale/currency');
        if (!isset(self::$_currencyCache[$this->getLocaleCode()][$currency])) {
            try {
                $currencyObject = new Mage_Core_Model_Locale_Currency($currency, $this->getLocale());
            } catch (Exception $e) {
                $currencyObject = new Mage_Core_Model_Locale_Currency($this->getCurrency(), $this->getLocale());
                $options = array(
                        'name'      => $currency,
                        'currency'  => $currency,
                        'symbol'    => $currency
                );
                $currencyObject->setFormat($options);
            }

            self::$_currencyCache[$this->getLocaleCode()][$currency] = $currencyObject;
        }
        if (VPROF) Varien_Profiler::stop('locale/currency');
        return self::$_currencyCache[$this->getLocaleCode()][$currency];
    }

    /**
     * Returns the first found number from an string
     * Parsing depends on given locale (grouping and decimal)
     *
     * Examples for input:
     * '  2345.4356,1234' = 23455456.1234
     * '+23,3452.123' = 233452.123
     * ' 12343 ' = 12343
     * '-9456km' = -9456
     * '0' = 0
     * '2 054,10' = 2054.1
     * '2'054.52' = 2054.52
     * '2,46 GB' = 2.46
     *
     * @param string|int $value
     * @return float
     */
    public function getNumber($value)
    {
        if (is_null($value)) {
            return null;
        }

        if (!is_string($value)) {
            return floatval($value);
        }

        //trim space and apos
        $value = str_replace('\'', '', $value);
        $value = str_replace(' ', '', $value);

        $separatorComa = strpos($value, ',');
        $separatorDot  = strpos($value, '.');

        if ($separatorComa !== false && $separatorDot !== false) {
            if ($separatorComa > $separatorDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
            else {
                $value = str_replace(',', '', $value);
            }
        }
        elseif ($separatorComa !== false) {
            $value = str_replace(',', '.', $value);
        }

        return floatval($value);
        //return Zend_Locale_Format::getNumber($value, array('locale' => $this->getLocaleCode()));
    }

    /**
     * Functions returns array with price formating info for js function
     * formatCurrency in js/varien/js.js
     *
     * @return array
     */
    public function getJsPriceFormat()
    {
        $format = Zend_Locale_Data::getContent($this->getLocaleCode(), 'currencynumber');
        $symbols = Zend_Locale_Data::getList($this->getLocaleCode(), 'symbols');

        $pos = strpos($format, ';');
        if ($pos !== false){
            $format = substr($format, 0, $pos);
        }
        $format = preg_replace("/[^0\#\.,]/", "", $format);
        $totalPrecision = 0;
        $decimalPoint = strpos($format, '.');
        if ($decimalPoint !== false) {
            $totalPrecision = (strlen($format) - (strrpos($format, '.')+1));
        } else {
            $decimalPoint = strlen($format);
        }
        $requiredPrecision = $totalPrecision;
        $t = substr($format, $decimalPoint);
        $pos = strpos($t, '#');
        if ($pos !== false){
            $requiredPrecision = strlen($t) - $pos - $totalPrecision;
        }
        $group = 0;
        if (strrpos($format, ',') !== false) {
            $group = ($decimalPoint - strrpos($format, ',') - 1);
        } else {
            $group = strrpos($format, '.');
        }
        $integerRequired = (strpos($format, '.') - strpos($format, '0'));

        $result = array(
            'pattern' => AO::app()->getStore()->getCurrentCurrency()->getOutputFormat(),
            'precision' => $totalPrecision,
            'requiredPrecision' => $requiredPrecision,
            'decimalSymbol' => $symbols['decimal'],
            'groupSymbol' => $symbols['group'],
            'groupLength' => $group,
            'integerRequired' => $integerRequired
        );

        return $result;
    }

    /**
     * Push current locale to stack and replace with locale from specified store
     * Event is not dispatched.
     *
     * @param int $storeId
     */
    public function emulate($storeId)
    {
        if ($storeId) {
            $this->_emulatedLocales[] = clone $this->getLocale();
            $this->_locale = new Zend_Locale(AO::getStoreConfig(self::XML_PATH_DEFAULT_LOCALE, $storeId));
            $this->_localeCode = $this->_locale->toString();
            AO::getSingleton('core/translate')->setLocale($this->_locale)->init('frontend', true);
        }
        else {
            $this->_emulatedLocales[] = false;
        }
    }

    /**
     * Get last locale, used before last emulation
     *
     */
    public function revert()
    {
        if ($locale = array_pop($this->_emulatedLocales)) {
            $this->_locale = $locale;
            $this->_localeCode = $this->_locale->toString();
            AO::getSingleton('core/translate')->setLocale($this->_locale)->init('adminhtml', true);
        }
    }

    /**
     * Returns localized informations as array, supported are several
     * types of informations.
     * For detailed information about the types look into the documentation
     *
     * @param  string             $path   (Optional) Type of information to return
     * @param  string             $value  (Optional) Value for detail list
     * @return array Array with the wished information in the given language
     */
    public function getTranslationList($path = null, $value = null)
    {
        return $this->getLocale()->getTranslationList($path, $this->getLocale(), $value);
    }

    /**
     * Returns a localized information string, supported are several types of informations.
     * For detailed information about the types look into the documentation
     *
     * @param  string             $value  Name to get detailed information about
     * @param  string             $path   (Optional) Type of information to return
     * @return string|false The wished information in the given language
     */
    public function getTranslation($value = null, $path = null)
    {
        return $this->getLocale()->getTranslation($value, $path, $this->getLocale());
    }

/**
     * Returns the localized country name
     *
     * @param  string             $value  Name to get detailed information about
     * @return array
     */
    public function getCountryTranslation($value)
    {
        return $this->getLocale()->getCountryTranslation($value, $this->getLocale());
    }

    /**
     * Returns an array with the name of all countries translated to the given language
     *
     * @return array
     */
    public function getCountryTranslationList()
    {
        return $this->getLocale()->getCountryTranslationList($this->getLocale());
    }
}

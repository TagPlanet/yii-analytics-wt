<?php
/**
 * Webtrends Analytics Component
 *
 * @author Philip Lawrence <philip@misterphilip.com>
 * @link http://misterphilip.com
 * @link http://tagpla.net
 * @link https://github.com/TagPlanet/yii-analytics-wt
 * @copyright Copyright &copy; 2012 Philip Lawrence
 * @license http://tagpla.net/licenses/MIT.txt
 * @version 1.0.0
 */
class TPWebtrendsAnalytics extends CApplicationComponent
{
    // Define the extension settings
    protected $extensionSettings = array('autoTrack', 'autoRender', 'fileLocation');
    public $autoTrack = false;
    public $autoRender = false;
    public $renderHead = false;
    
    // Define which are settings instead of variables (different location to output to)
    protected $webtrendsSettings = array('dcsid','domain','timezone','fpcdom','enabled','i18n','fpc','paidsearchparams','splitvalue','preserve');
    
    public $dcsid = '';
    public $domain = 'statse.webtrendslive.com';
    public $timezone = -3;
    public $fpcdom = '';
    public $enabled = true;
    public $i18n = false;
    public $fpc = 'WT_FPC';
    public $paidsearchparams = 'gclid';
    public $splitvalue = '';
    public $preserve = false;
    
            
    /**
     * Type of quotes to use for values
     */
    const Q = "'";

    /**
     * Method data to be pushed into the dcs object
     * @var array
     */
    private $_data = array();

    /**
     * init function - Yii automatically calls this
     */
    public function init()
    {
        // Nothing needs to be done initially, huzzah!
    }

    /**
     * Render and return the SiteCatalyst data
     * @return mixed
     */
    public function render()
    {
        // Get the render location
        $renderLocation = ($this->renderHead) ? CClientScript::POS_HEAD : CClientScript::POS_END;
        
        // Get the namespace
        $n = (($this->namespace != '' && ctype_alnum($this->namespace)) ? $this->namespace : 's');
        
        // Check for s_code rendering
        if($this->s_codeLocation != '')
            Yii::app()->clientScript->registerScriptFile($this->s_codeLocations, $renderLocation);
        
        // Start the rendering...
        $js = '';
        
        // Grab the settings
        $settings = array();
        foreach($this->settings as $setting)
        {
            if(!isset($this->$setting))
            {
                throw new CException('Missing required parameter "' . $setting . '" for TPWebtrendsAnalytics');
            }
            if(is_string($this->$setting))
                $settings[] = $setting . ': ' . self::Q . preg_replace('~(?<!\\\)'. self::Q . '~', '\\'. self::Q, $this->$setting) . self::Q;
            elseif(is_bool($this->$setting))
                $settings[] = $setting . ': ' . ($this->$setting) ? 'true' : 'false';
            else
                $settings[] = $setting . ': ' . $this->$setting;
            
            unset($this->data[$setting]);
        }
        $settings = implode(',' . PHP_EOL . '        ', $settings);
        $js.= <<<EOJS
window.webtrendsAsyncInit = function(){
    var dcs=new Webtrends.dcs().init({
        {$settings}
    });
EOJS;
        
        // Go through the data
        foreach($this->_data as $var => $value)
        {
            $js.= '    dcs.DCSext.' . $var . ' = ' . self::Q . preg_replace('~(?<!\\\)'. self::Q . '~', '\\'. self::Q, $value) . self::Q . ';' . PHP_EOL;
        }
        
        // Should we auto track?
        if($this->autoTrack)
        {
            $js.= '    dcs.track();' . PHP_EOL;
        }
        
        // Close up the async code
        $js.= '});' . PHP_EOL;
        
        // TagPla.net copyright... please leave in here!
        $js.= '// WebTrends Extension provided by TagPla.net' . PHP_EOL;
        $js.= '// https://github.com/TagPlanet/yii-analytics-wt' . PHP_EOL;
        $js.= '// Copyright 2012, TagPla.net & Philip Lawrence' . PHP_EOL;
        
        
        // Should we auto add in the analytics tag?
        if($this->autoRender)
        {
            Yii::app()->clientScript
                    ->registerScript('TPSiteCatalyst', $js, CClientScript::POS_HEAD);
        }
        else
        {
            return $js;
        }
        
        return;
    }
    
    /**
     * Wrapper for getting / setting options
     *
     * @param string $name
     * @param mixed  $value
     * @return mixed (success if set / value if get)
     */
    public function setting($name, $value = null)
    {
        if(in_array($name, $this->settings))
        {
            // Get value
            if($value === null)
            {
                return $this->$name;
            }
            
            $this->$name = $value;
            return true;
        }
        return false;
    }
    
    /**
     * Magic Method for setting settings
     * @param string $name
     * @param mixed $value
     * @param array  $arguments
     */
    public function __set($name, $value)
    {        
        if(in_array($name, $this->extensionSettings))
        {
            $this->$name = $value;
        } 
        else
        {
            $this->$name = $value;
            $this->_data[$name] = $value;
        }
    }
}